<script lang="ts">
  import { Plus, Printer } from 'lucide-svelte';
  import type { CashAdvance, PayrollSlipData, User } from '../../../types/app';
  import { formatRupiah } from '../../../utils/formatters';

  interface Props {
    currentUser: User;
    cashAdvances: CashAdvance[];
    payrollSlip: PayrollSlipData | null;
    onOpenKasbonModal: () => void;
  }

  let {
    currentUser: _currentUser,
    cashAdvances = [],
    payrollSlip = null,
    onOpenKasbonModal,
  }: Props = $props();

  let totalActiveKasbon = $derived(
    cashAdvances.filter((k) => k.status === 'APPROVED').reduce((sum, k) => sum + k.amount, 0)
  );
</script>

<div class="space-y-6 max-w-5xl mx-auto font-sans pb-8">
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
    <div class="space-y-1">
      <h2 class="text-base sm:text-lg font-bold text-[#17171c]">Kasbon &amp; Slip Gaji Digital</h2>
      <p class="text-xs text-[#8e8e93]">Pengajuan pinjaman darurat &amp; rincian gaji periode berjalan</p>
    </div>
    <button
      type="button"
      onclick={onOpenKasbonModal}
      class="bg-[#17171c] hover:bg-black text-white px-5 py-2.5 text-xs font-semibold rounded-full flex items-center gap-2 cursor-pointer transition-all shrink-0 shadow-xs self-start sm:self-auto"
    >
      <Plus class="w-4 h-4" />
      <span>Ajukan Kasbon</span>
    </button>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
    <!-- Status Kasbon Aktif & Riwayat -->
    <div class="lg:col-span-6 space-y-4">
      <!-- Total Kasbon Card -->
      <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-2xs">
        <span class="text-[10.5px] font-mono uppercase text-[#8e8e93] font-semibold">Total Kasbon Belum Lunas</span>
        <div class={`text-2xl font-bold font-mono mt-1 ${totalActiveKasbon > 0 ? 'text-[#dc2626]' : 'text-[#059669]'}`}>
          {formatRupiah(totalActiveKasbon)}
        </div>
        <p class="text-xs text-[#8e8e93] mt-1">
          {totalActiveKasbon > 0 ? 'Dipotong otomatis saat pencairan payroll' : 'Tidak ada pinjaman kasbon aktif'}
        </p>
      </div>

      <!-- Riwayat Kasbon -->
      <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 space-y-3 shadow-2xs">
        <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
          <h3 class="text-sm font-bold text-[#17171c]">Riwayat Permohonan Kasbon</h3>
          <span class="text-xs font-mono text-[#8e8e93]">{cashAdvances.length} Pengajuan</span>
        </div>

        {#if cashAdvances.length === 0}
          <div class="py-8 text-center text-xs text-[#8e8e93]">Belum ada riwayat kasbon.</div>
        {:else}
          <div class="divide-y divide-[#f2f2f4]">
            {#each cashAdvances as k}
              <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                <div>
                  <div class="font-mono font-bold text-sm text-[#17171c]">{formatRupiah(k.amount)}</div>
                  <div class="text-[11px] text-[#8e8e93] mt-0.5">{k.purpose || 'Pinjaman'} &bull; {k.created_at || k.request_date || '-'}</div>
                </div>
                <span class={`text-[10px] font-mono font-semibold px-2.5 py-0.5 rounded-full ${
                  k.status === 'APPROVED' ? 'bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0]' : k.status === 'PENDING' ? 'bg-[#fffbeb] text-[#d97706] border border-[#fef3c7]' : 'bg-[#fef2f2] text-[#dc2626] border border-[#fecaca]'
                }`}>
                  {k.status}
                </span>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    </div>

    <!-- Slip Gaji Digital Preview -->
    <div class="lg:col-span-6 space-y-4">
      <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 space-y-4 shadow-2xs">
        <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Slip Gaji Periode Berjalan</h3>
            <div class="text-xs font-mono text-[#8e8e93]">{payrollSlip?.period_start || ''} s/d {payrollSlip?.period_end || ''}</div>
          </div>
          <button
            type="button"
            onclick={() => window.print()}
            class="p-2 border border-[#e5e5ea] rounded-xl hover:bg-[#f4f4f6] text-[#686873] hover:text-[#17171c] cursor-pointer transition-all"
            title="Cetak Slip Gaji"
          >
            <Printer class="w-4 h-4" />
          </button>
        </div>

        {#if payrollSlip}
          <div class="space-y-3 text-xs">
            <div class="flex justify-between py-2 border-b border-[#f2f2f4]">
              <span class="text-[#686873]">Gaji Pokok</span>
              <span class="font-mono font-bold text-[#17171c]">{formatRupiah(payrollSlip.base_salary)}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-[#f2f2f4]">
              <span class="text-[#686873]">Upah Lembur ({Math.round((payrollSlip.total_overtime_minutes || 0) / 60)} Jam)</span>
              <span class="font-mono font-bold text-[#059669]">+{formatRupiah(payrollSlip.overtime_pay)}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-[#f2f2f4]">
              <span class="text-[#686873]">Denda Terlambat ({payrollSlip.total_late_minutes || 0} Menit)</span>
              <span class="font-mono font-bold text-[#e5484d]">-{formatRupiah(payrollSlip.late_penalty)}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-[#f2f2f4]">
              <span class="text-[#686873]">Potongan Kasbon</span>
              <span class="font-mono font-bold text-[#e5484d]">-{formatRupiah(payrollSlip.cash_advance_deduction)}</span>
            </div>
            <div class="flex justify-between py-3.5 text-sm font-bold bg-[#fafafc] border border-[#e5e5ea] px-4 rounded-2xl shadow-2xs">
              <span class="text-[#17171c]">Take Home Pay (Gaji Bersih)</span>
              <span class="font-mono text-[#059669] text-base">{formatRupiah(payrollSlip.net_salary)}</span>
            </div>
          </div>
        {:else}
          <div class="py-10 text-center text-xs text-[#8e8e93]">Data slip gaji belum tersedia.</div>
        {/if}
      </div>
    </div>
  </div>
</div>
