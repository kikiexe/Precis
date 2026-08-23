<script lang="ts">
  import { Plus, Wallet, FileText, AlertCircle, Printer } from 'lucide-svelte';
  import type { CashAdvance, PayrollSlipData } from '../../types/app';

  interface Props {
    cashAdvances?: CashAdvance[];
    payrollSlip?: PayrollSlipData | null;
    onRequestKasbon: (amount: number, purpose?: string) => Promise<void> | void;
  }

  let { cashAdvances = [], payrollSlip = null, onRequestKasbon }: Props = $props();

  let isKasbonModalOpen = $state(false);
  let requestAmount = $state(200000);
  let requestPurpose = $state('');
  let isSubmitting = $state(false);
  let errorMessage = $state<string | null>(null);

  function formatRp(num: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
  }

  async function handleSendKasbon() {
    if (requestAmount <= 0) {
      errorMessage = 'Masukkan nominal kasbon yang valid.';
      return;
    }

    isSubmitting = true;
    errorMessage = null;

    try {
      await onRequestKasbon(requestAmount, requestPurpose);
      isKasbonModalOpen = false;
      requestPurpose = '';
    } catch (err: unknown) {
      errorMessage = err instanceof Error ? err.message : 'Gagal mengajukan kasbon.';
    } finally {
      isSubmitting = false;
    }
  }

  function handlePrintSlip() {
    window.print();
  }

  let totalActiveKasbon = $derived(
    cashAdvances
      .filter((k) => k.status === 'APPROVED')
      .reduce((sum, k) => sum + k.amount, 0)
  );

  let hasPendingKasbon = $derived(
    cashAdvances.some((k) => k.status === 'PENDING')
  );

  let isDisbursed = $derived(
    payrollSlip?.status === 'DISBURSED' || payrollSlip?.disbursement_status === 'PAID'
  );
</script>

<div class="space-y-6 max-w-6xl mx-auto p-4 md:p-8 pb-24 lg:pb-8">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-bold text-[#161616] font-display">Manajemen Kasbon &amp; Slip Gaji Digital</h2>
      <p class="text-xs text-[#525252] font-mono">Pengajuan pinjaman darurat dan rincian payroll berjalan</p>
    </div>
    <button
      type="button"
      onclick={() => {
        errorMessage = null;
        isKasbonModalOpen = true;
      }}
      class="bg-[#0f62fe] hover:bg-[#0050e6] text-white px-4 py-2.5 text-xs font-semibold flex items-center gap-2 cursor-pointer shadow-xs transition-colors self-start sm:self-auto"
    >
      <Plus class="w-4 h-4" />
      <span>Ajukan Kasbon Baru</span>
    </button>
  </div>

  <!-- tabel riwayat kasbon di kiri dan rincian slip gaji -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- status kasbon aktif dan riwayat permohonan -->
    <div class="lg:col-span-7 space-y-4">
      <div class="bg-white border border-[#e0e0e0] p-5 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-xs font-mono text-[#525252]">Total Kasbon Belum Lunas</div>
          <div class={`text-2xl font-bold font-mono mt-1 ${totalActiveKasbon > 0 ? 'text-[#da1e28]' : 'text-[#24a148]'}`}>
            {formatRp(totalActiveKasbon)}
          </div>
          <div class="text-xs text-[#8c8c8c] mt-1">
            {totalActiveKasbon > 0 ? 'Otomatis dipotong dari payroll periode berjalan' : 'Tidak ada pinjaman kasbon aktif'}
          </div>
        </div>
        {#if hasPendingKasbon}
          <div class="p-2 bg-[#f1c21b]/10 border border-[#f1c21b]/30 text-[#8a6d00] text-xs font-mono">
            Ada permohonan pending
          </div>
        {/if}
      </div>

      <div class="space-y-3">
        <h3 class="text-xs font-bold font-mono text-[#525252] uppercase tracking-wider">
          Riwayat Permohonan Kasbon Staf
        </h3>

        {#if cashAdvances.length === 0}
          <div class="p-8 bg-white border border-[#e0e0e0] text-center space-y-2 shadow-xs">
            <Wallet class="w-8 h-8 text-[#8c8c8c] mx-auto opacity-40" />
            <h3 class="text-sm font-bold text-[#161616]">Belum Ada Riwayat Kasbon</h3>
            <p class="text-xs text-[#8c8c8c]">Permohonan pinjaman kasbon darurat yang diajukan akan muncul di sini.</p>
          </div>
        {:else}
          <div class="bg-white border border-[#e0e0e0] overflow-x-auto shadow-xs">
            <table class="w-full text-xs text-left">
              <thead class="bg-[#f4f4f4] border-b border-[#e0e0e0] font-mono text-[11px] text-[#525252]">
                <tr>
                  <th class="p-3">Nominal</th>
                  <th class="p-3">Tanggal</th>
                  <th class="p-3 text-right">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#f4f4f4]">
                {#each cashAdvances as adv}
                  <tr>
                    <td class="p-3 font-mono font-bold text-[#161616]">{formatRp(adv.amount)}</td>
                    <td class="p-3 font-mono text-[#8c8c8c]">{adv.request_date}</td>
                    <td class="p-3 text-right">
                      <span class={`text-[10px] font-mono px-2 py-0.5 border ${
                        adv.status === 'PENDING'
                          ? 'bg-[#f1c21b]/10 text-[#b28900] border-[#f1c21b]/30'
                          : adv.status === 'APPROVED'
                          ? 'bg-[#24a148]/10 text-[#24a148] border-[#24a148]/30'
                          : adv.status === 'DEDUCTED'
                          ? 'bg-[#0f62fe]/10 text-[#0f62fe] border-[#0f62fe]/30'
                          : 'bg-[#da1e28]/10 text-[#da1e28] border-[#da1e28]/30'
                      }`}>
                        {adv.status === 'PENDING' ? 'Menunggu Approval' : adv.status === 'APPROVED' ? 'Disetujui' : adv.status === 'DEDUCTED' ? 'Lunas (Payroll)' : 'Ditolak'}
                      </span>
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        {/if}
      </div>
    </div>

    <!-- digital slip gaji card -->
    <div class="lg:col-span-5">
      {#if !payrollSlip}
        <div class="bg-white border border-[#e0e0e0] p-8 text-center space-y-2 shadow-xs">
          <FileText class="w-8 h-8 text-[#8c8c8c] mx-auto opacity-40" />
          <h3 class="text-sm font-bold text-[#161616]">Slip Gaji Belum Diterbitkan</h3>
          <p class="text-xs text-[#8c8c8c]">Rincian penggajian digital untuk periode ini akan tersedia setelah proses disbursement oleh tim finance.</p>
        </div>
      {:else}
        <div class="bg-white border border-[#e0e0e0] p-6 space-y-5 shadow-xs">
          <div class="border-b border-[#e0e0e0] pb-4 flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-[#161616]">Slip Gaji Digital (THP)</h3>
              <p class="text-xs font-mono text-[#525252]">Periode: {payrollSlip.period_start} s/d {payrollSlip.period_end}</p>
              {#if payrollSlip.user_name || payrollSlip.user?.name}
                <p class="text-[11px] text-[#8c8c8c] font-mono mt-0.5">
                  {payrollSlip.user_name || payrollSlip.user?.name} {payrollSlip.branch_name ? `• ${payrollSlip.branch_name}` : ''}
                </p>
              {/if}
            </div>
            <span class={`px-2.5 py-1 text-xs font-mono border font-semibold ${
              isDisbursed
                ? 'bg-[#24a148]/10 text-[#24a148] border-[#24a148]/30'
                : 'bg-[#0f62fe]/10 text-[#0f62fe] border-[#0f62fe]/30'
            }`}>
              {isDisbursed ? 'Lunas Ditransfer' : 'Estimasi Berjalan'}
            </span>
          </div>

          <div class="space-y-2.5 text-xs">
            <div class="flex justify-between text-[#525252]">
              <span>Gaji Pokok (Base Salary)</span>
              <span class="font-mono text-[#161616] font-semibold">{formatRp(payrollSlip.base_salary)}</span>
            </div>

            <div class="flex justify-between text-[#24a148]">
              <span>Upah Lembur ({payrollSlip.total_overtime_minutes || 0} Menit)</span>
              <span class="font-mono font-semibold">+{formatRp(payrollSlip.overtime_pay)}</span>
            </div>

            <div class="flex justify-between text-[#da1e28]">
              <span>Denda Telat ({payrollSlip.total_late_minutes || 0} Menit)</span>
              <span class="font-mono font-semibold">-{formatRp(payrollSlip.late_penalty)}</span>
            </div>

            <div class="flex justify-between text-[#da1e28]">
              <span>Potongan Pelunasan Kasbon</span>
              <span class="font-mono font-semibold">-{formatRp(payrollSlip.cash_advance_deduction)}</span>
            </div>

            <div class="pt-4 border-t border-[#e0e0e0] flex items-center justify-between">
              <div>
                <span class="text-sm font-bold text-[#161616] block">Take Home Pay</span>
                <span class="text-[10px] text-[#8c8c8c] font-mono">Rekening Karyawan</span>
              </div>
              <span class="text-2xl font-bold font-mono text-[#0f62fe]">
                {formatRp(payrollSlip.net_salary)}
              </span>
            </div>
          </div>

          <button
            type="button"
            onclick={handlePrintSlip}
            class="w-full py-3 bg-[#f4f4f4] hover:bg-[#e0e0e0] border border-[#e0e0e0] text-xs font-semibold text-[#161616] flex items-center justify-center gap-2 cursor-pointer transition-colors"
          >
            <Printer class="w-4 h-4 text-[#0f62fe]" />
            <span>Cetak / Unduh Slip Gaji</span>
          </button>
        </div>
      {/if}
    </div>
  </div>
</div>

<!-- modal form pengajuan kasbon -->
{#if isKasbonModalOpen}
  <div class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white border border-[#e0e0e0] max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
        <h3 class="text-sm font-bold text-[#161616]">Formulir Kasbon Karyawan</h3>
        <button type="button" onclick={() => (isKasbonModalOpen = false)} class="text-[#8c8c8c] hover:text-[#161616] cursor-pointer">✕</button>
      </div>

      {#if errorMessage}
        <div class="p-3 bg-[#da1e28]/10 border border-[#da1e28]/30 text-[#da1e28] text-xs font-mono flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <div class="space-y-3 text-xs">
        <div>
          <label for="kasbon-nom-desk" class="block font-mono text-[#525252] mb-1">Nominal Pinjaman (Rp):</label>
          <input
            id="kasbon-nom-desk"
            type="number"
            bind:value={requestAmount}
            step="50000"
            class="w-full bg-[#f4f4f4] border border-[#e0e0e0] p-2.5 font-mono font-bold text-sm text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
          />
        </div>

        <div>
          <label for="kasbon-purp-desk" class="block font-mono text-[#525252] mb-1">Keperluan Mendesak (Opsional):</label>
          <textarea
            id="kasbon-purp-desk"
            bind:value={requestPurpose}
            rows="3"
            placeholder="e.g. Servis motor harian operasional..."
            class="w-full bg-[#f4f4f4] border border-[#e0e0e0] p-2.5 text-xs text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
          ></textarea>
        </div>

        <div class="pt-2 flex gap-2">
          <button
            type="button"
            onclick={() => (isKasbonModalOpen = false)}
            class="flex-1 py-2.5 bg-[#f4f4f4] text-[#525252] border border-[#e0e0e0] text-xs font-semibold cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            onclick={handleSendKasbon}
            disabled={requestAmount <= 0 || isSubmitting}
            class="flex-2 py-2.5 bg-[#0f62fe] hover:bg-[#0050e6] text-white text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>{isSubmitting ? 'Mengajukan...' : 'Kirim Permohonan Kasbon'}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}
