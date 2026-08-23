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

<div class="space-y-6 max-w-6xl mx-auto p-4 sm:p-6 md:p-8 pb-24 lg:pb-8 font-sans">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-medium text-[#212121] tracking-tight">Manajemen Kasbon &amp; Slip Gaji Digital</h2>
      <p class="text-xs text-[#616161] font-normal mt-0.5">Pengajuan pinjaman darurat dan rincian payroll berjalan</p>
    </div>
    <button
      type="button"
      onclick={() => {
        errorMessage = null;
        isKasbonModalOpen = true;
      }}
      class="bg-[#17171c] hover:bg-[#000000] text-white px-5 py-2.5 text-xs font-medium rounded-full flex items-center gap-2 cursor-pointer transition-all shadow-none self-start sm:self-auto"
    >
      <Plus class="w-4 h-4" />
      <span>Ajukan Kasbon Baru</span>
    </button>
  </div>

  <!-- tabel riwayat kasbon di kiri dan rincian slip gaji -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- status kasbon aktif dan riwayat permohonan -->
    <div class="lg:col-span-7 space-y-4">
      <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-6 shadow-none flex items-center justify-between">
        <div>
          <div class="text-xs font-normal text-[#75758a]">Total Kasbon Belum Lunas</div>
          <div class={`text-2xl font-medium font-mono mt-1 ${totalActiveKasbon > 0 ? 'text-[#b30000]' : 'text-[#003c33]'}`}>
            {formatRp(totalActiveKasbon)}
          </div>
          <div class="text-xs text-[#93939f] mt-1 font-normal">
            {totalActiveKasbon > 0 ? 'Otomatis dipotong dari payroll periode berjalan' : 'Tidak ada pinjaman kasbon aktif'}
          </div>
        </div>
        {#if hasPendingKasbon}
          <div class="px-3 py-1.5 bg-[#eeece7] rounded-full text-[#616161] text-xs font-mono font-medium">
            Ada permohonan pending
          </div>
        {/if}
      </div>

      <div class="space-y-3">
        <h3 class="text-xs font-medium text-[#75758a] uppercase tracking-wider px-1">
          Riwayat Permohonan Kasbon Staf
        </h3>

        {#if cashAdvances.length === 0}
          <div class="p-8 bg-white border border-[#d9d9dd] rounded-[22px] text-center space-y-2 shadow-none">
            <Wallet class="w-8 h-8 text-[#93939f] mx-auto opacity-50" />
            <h3 class="text-sm font-medium text-[#212121]">Belum Ada Riwayat Kasbon</h3>
            <p class="text-xs text-[#75758a]">Permohonan pinjaman kasbon darurat yang diajukan akan muncul di sini.</p>
          </div>
        {:else}
          <div class="bg-white border border-[#d9d9dd] rounded-[22px] overflow-hidden shadow-none">
            <table class="w-full text-xs text-left border-collapse">
              <thead class="bg-[#eeece7]/50 border-b border-[#d9d9dd] font-mono text-[11px] text-[#616161]">
                <tr>
                  <th class="p-3.5 font-medium">Nominal</th>
                  <th class="p-3.5 font-medium">Tanggal</th>
                  <th class="p-3.5 text-right font-medium">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#d9d9dd]/60">
                {#each cashAdvances as adv}
                  <tr class="hover:bg-[#eeece7]/20 transition-colors">
                    <td class="p-3.5 font-mono font-medium text-[#212121]">{formatRp(adv.amount)}</td>
                    <td class="p-3.5 font-mono text-[#75758a] text-[11px]">{adv.request_date}</td>
                    <td class="p-3.5 text-right">
                      <span class={`text-[10px] font-mono px-2.5 py-0.5 rounded-full font-medium ${
                        adv.status === 'PENDING'
                          ? 'bg-[#eeece7] text-[#616161]'
                          : adv.status === 'APPROVED'
                          ? 'bg-[#edfce9] text-[#003c33]'
                          : adv.status === 'DEDUCTED'
                          ? 'bg-[#f1f5ff] text-[#1863dc]'
                          : 'bg-[#ffad9b]/20 text-[#b30000]'
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
        <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-8 text-center space-y-2 shadow-none">
          <FileText class="w-8 h-8 text-[#93939f] mx-auto opacity-50" />
          <h3 class="text-sm font-medium text-[#212121]">Slip Gaji Belum Diterbitkan</h3>
          <p class="text-xs text-[#75758a]">Rincian penggajian digital untuk periode ini akan tersedia setelah proses disbursement oleh tim finance.</p>
        </div>
      {:else}
        <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-6 space-y-5 shadow-none">
          <div class="border-b border-[#d9d9dd] pb-4 flex items-center justify-between">
            <div>
              <h3 class="text-base font-medium text-[#212121]">Slip Gaji Digital (THP)</h3>
              <p class="text-xs font-mono text-[#75758a] mt-0.5">Periode: {payrollSlip.period_start} s/d {payrollSlip.period_end}</p>
              {#if payrollSlip.user_name || payrollSlip.user?.name}
                <p class="text-[11px] text-[#93939f] font-mono mt-0.5">
                  {payrollSlip.user_name || payrollSlip.user?.name} {payrollSlip.branch_name ? `• ${payrollSlip.branch_name}` : ''}
                </p>
              {/if}
            </div>
            <span class={`px-3 py-1 text-xs font-mono rounded-full font-medium ${
              isDisbursed
                ? 'bg-[#edfce9] text-[#003c33]'
                : 'bg-[#f1f5ff] text-[#1863dc]'
            }`}>
              {isDisbursed ? 'Lunas Ditransfer' : 'Estimasi Berjalan'}
            </span>
          </div>

          <div class="space-y-3 text-xs">
            <div class="flex justify-between text-[#616161]">
              <span>Gaji Pokok (Base Salary)</span>
              <span class="font-mono text-[#212121] font-medium">{formatRp(payrollSlip.base_salary)}</span>
            </div>

            <div class="flex justify-between text-[#003c33]">
              <span>Upah Lembur ({payrollSlip.total_overtime_minutes || 0} Menit)</span>
              <span class="font-mono font-medium">+{formatRp(payrollSlip.overtime_pay)}</span>
            </div>

            <div class="flex justify-between text-[#b30000]">
              <span>Denda Telat ({payrollSlip.total_late_minutes || 0} Menit)</span>
              <span class="font-mono font-medium">-{formatRp(payrollSlip.late_penalty)}</span>
            </div>

            <div class="flex justify-between text-[#b30000]">
              <span>Potongan Pelunasan Kasbon</span>
              <span class="font-mono font-medium">-{formatRp(payrollSlip.cash_advance_deduction)}</span>
            </div>

            <div class="pt-4 border-t border-[#d9d9dd] flex items-center justify-between">
              <div>
                <span class="text-sm font-medium text-[#212121] block">Take Home Pay</span>
                <span class="text-[11px] text-[#93939f] font-normal">Rekening Karyawan</span>
              </div>
              <span class="text-2xl font-medium font-mono text-[#17171c]">
                {formatRp(payrollSlip.net_salary)}
              </span>
            </div>
          </div>

          <button
            type="button"
            onclick={handlePrintSlip}
            class="w-full py-3 bg-[#eeece7]/40 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs font-medium text-[#212121] flex items-center justify-center gap-2 cursor-pointer transition-all shadow-none"
          >
            <Printer class="w-4 h-4 text-[#17171c]" />
            <span>Cetak / Unduh Slip Gaji</span>
          </button>
        </div>
      {/if}
    </div>
  </div>
</div>

<!-- modal form pengajuan kasbon -->
{#if isKasbonModalOpen}
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-md w-full p-6 shadow-none space-y-5 animate-in fade-in zoom-in-95 font-sans">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <h3 class="text-base font-medium text-[#212121]">Formulir Kasbon Karyawan</h3>
        <button type="button" onclick={() => (isKasbonModalOpen = false)} class="text-[#93939f] hover:text-[#212121] cursor-pointer p-1">✕</button>
      </div>

      {#if errorMessage}
        <div class="p-3 bg-[#ffad9b]/15 border border-[#ffad9b] rounded-[12px] text-[#b30000] text-xs flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div>
          <label for="kasbon-nom-desk" class="block font-medium text-[#212121] mb-1.5">Nominal Pinjaman (Rp):</label>
          <input
            id="kasbon-nom-desk"
            type="number"
            bind:value={requestAmount}
            step="50000"
            class="w-full bg-white border border-[#d9d9dd] rounded-[12px] p-2.5 font-mono font-medium text-sm text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          />
        </div>

        <div>
          <label for="kasbon-purp-desk" class="block font-medium text-[#212121] mb-1.5">Keperluan Mendesak (Opsional):</label>
          <textarea
            id="kasbon-purp-desk"
            bind:value={requestPurpose}
            rows="3"
            placeholder="e.g. Servis motor harian operasional..."
            class="w-full bg-white border border-[#d9d9dd] rounded-[12px] p-2.5 text-xs text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          ></textarea>
        </div>

        <div class="pt-3 flex gap-2.5 border-t border-[#d9d9dd]">
          <button
            type="button"
            onclick={() => (isKasbonModalOpen = false)}
            class="flex-1 py-2.5 bg-white text-[#616161] hover:bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full text-xs font-medium cursor-pointer transition-all"
          >
            Batal
          </button>
          <button
            type="button"
            onclick={handleSendKasbon}
            disabled={requestAmount <= 0 || isSubmitting}
            class="flex-2 py-2.5 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full flex items-center justify-center gap-1.5 cursor-pointer transition-all disabled:opacity-50"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>{isSubmitting ? 'Mengajukan...' : 'Kirim Permohonan Kasbon'}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}
