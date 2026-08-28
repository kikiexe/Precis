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

<div class="mx-auto max-w-5xl space-y-6 pb-8 font-sans">
  <div
    class="flex flex-col justify-between gap-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:flex-row sm:items-center sm:rounded-3xl sm:p-6"
  >
    <div class="space-y-1">
      <h2 class="text-base font-bold text-[#17171c] sm:text-lg">Kasbon &amp; Slip Gaji Digital</h2>
      <p class="text-xs text-[#8e8e93]">
        Pengajuan pinjaman darurat &amp; rincian gaji periode berjalan
      </p>
    </div>
    <button
      type="button"
      onclick={onOpenKasbonModal}
      class="flex shrink-0 cursor-pointer items-center gap-2 self-start rounded-full bg-[#17171c] px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black sm:self-auto"
    >
      <Plus class="h-4 w-4" />
      <span>Ajukan Kasbon</span>
    </button>
  </div>

  <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
    <!-- Status Kasbon Aktif & Riwayat -->
    <div class="space-y-4 lg:col-span-6">
      <!-- Total Kasbon Card -->
      <div
        class="rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:rounded-3xl sm:p-6"
      >
        <span class="font-mono text-[10.5px] font-semibold text-[#8e8e93] uppercase"
          >Total Kasbon Belum Lunas</span
        >
        <div
          class={`mt-1 font-mono text-2xl font-bold ${totalActiveKasbon > 0 ? 'text-[#dc2626]' : 'text-[#059669]'}`}
        >
          {formatRupiah(totalActiveKasbon)}
        </div>
        <p class="mt-1 text-xs text-[#8e8e93]">
          {totalActiveKasbon > 0
            ? 'Dipotong otomatis saat pencairan payroll'
            : 'Tidak ada pinjaman kasbon aktif'}
        </p>
      </div>

      <!-- Riwayat Kasbon -->
      <div
        class="space-y-3 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:rounded-3xl sm:p-6"
      >
        <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
          <h3 class="text-sm font-bold text-[#17171c]">Riwayat Permohonan Kasbon</h3>
          <span class="font-mono text-xs text-[#8e8e93]">{cashAdvances.length} Pengajuan</span>
        </div>

        {#if cashAdvances.length === 0}
          <div class="py-8 text-center text-xs text-[#8e8e93]">Belum ada riwayat kasbon.</div>
        {:else}
          <div class="divide-y divide-[#f2f2f4]">
            {#each cashAdvances as k}
              <div class="flex items-center justify-between py-3 text-xs first:pt-0 last:pb-0">
                <div>
                  <div class="font-mono text-sm font-bold text-[#17171c]">
                    {formatRupiah(k.amount)}
                  </div>
                  <div class="mt-0.5 text-[11px] text-[#8e8e93]">
                    {k.purpose || 'Pinjaman'} &bull; {k.created_at || k.request_date || '-'}
                  </div>
                </div>
                <span
                  class={`rounded-full px-2.5 py-0.5 font-mono text-[10px] font-semibold ${
                    k.status === 'APPROVED'
                      ? 'border border-[#a7f3d0] bg-[#ecfdf5] text-[#059669]'
                      : k.status === 'PENDING'
                        ? 'border border-[#fef3c7] bg-[#fffbeb] text-[#d97706]'
                        : 'border border-[#fecaca] bg-[#fef2f2] text-[#dc2626]'
                  }`}
                >
                  {k.status}
                </span>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    </div>

    <!-- Slip Gaji Digital Preview -->
    <div class="space-y-4 lg:col-span-6">
      <div
        class="space-y-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:rounded-3xl sm:p-6"
      >
        <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Slip Gaji Periode Berjalan</h3>
            <div class="font-mono text-xs text-[#8e8e93]">
              {payrollSlip?.period_start || ''} s/d {payrollSlip?.period_end || ''}
            </div>
          </div>
          <button
            type="button"
            onclick={() => window.print()}
            class="cursor-pointer rounded-xl border border-[#e5e5ea] p-2 text-[#686873] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
            title="Cetak Slip Gaji"
          >
            <Printer class="h-4 w-4" />
          </button>
        </div>

        {#if payrollSlip}
          <div class="space-y-3 text-xs">
            <div class="flex justify-between border-b border-[#f2f2f4] py-2">
              <span class="text-[#686873]">Gaji Pokok</span>
              <span class="font-mono font-bold text-[#17171c]"
                >{formatRupiah(payrollSlip.base_salary)}</span
              >
            </div>
            <div class="flex justify-between border-b border-[#f2f2f4] py-2">
              <span class="text-[#686873]"
                >Upah Lembur ({Math.round((payrollSlip.total_overtime_minutes || 0) / 60)} Jam)</span
              >
              <span class="font-mono font-bold text-[#059669]"
                >+{formatRupiah(payrollSlip.overtime_pay)}</span
              >
            </div>
            <div class="flex justify-between border-b border-[#f2f2f4] py-2">
              <span class="text-[#686873]"
                >Denda Terlambat ({payrollSlip.total_late_minutes || 0} Menit)</span
              >
              <span class="font-mono font-bold text-[#e5484d]"
                >-{formatRupiah(payrollSlip.late_penalty)}</span
              >
            </div>
            <div class="flex justify-between border-b border-[#f2f2f4] py-2">
              <span class="text-[#686873]">Potongan Kasbon</span>
              <span class="font-mono font-bold text-[#e5484d]"
                >-{formatRupiah(payrollSlip.cash_advance_deduction)}</span
              >
            </div>
            <div
              class="flex justify-between rounded-2xl border border-[#e5e5ea] bg-[#fafafc] px-4 py-3.5 text-sm font-bold shadow-2xs"
            >
              <span class="text-[#17171c]">Take Home Pay (Gaji Bersih)</span>
              <span class="font-mono text-base text-[#059669]"
                >{formatRupiah(payrollSlip.net_salary)}</span
              >
            </div>
          </div>
        {:else}
          <div class="py-10 text-center text-xs text-[#8e8e93]">Data slip gaji belum tersedia.</div>
        {/if}
      </div>
    </div>
  </div>
</div>
