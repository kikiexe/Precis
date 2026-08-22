<script lang="ts">
  import { Calculator, Download, FileSpreadsheet, CheckCircle2 } from 'lucide-svelte';

  let baseSalary = $state(2800000);
  let overtimeHours = $state(4);
  let hourlyOvertimeRate = 25000;
  let lateMinutes = $state(18);
  let latePenaltyPerMinute = 2000;
  let cashAdvanceDeduction = $state(150000);

  let overtimePay = $derived(overtimeHours * hourlyOvertimeRate);
  let latePenalty = $derived(lateMinutes * latePenaltyPerMinute);
  let netSalary = $derived(baseSalary + overtimePay - latePenalty - cashAdvanceDeduction);

  let exportFeedback = $state<string | null>(null);

  function triggerExport(type: 'excel' | 'bank') {
    exportFeedback = `File ${type === 'excel' ? 'Excel Rekapitulasi Gaji' : 'CSV Format Transfer Bank (BCA/Mandiri/BRI)'} berhasil dibuat dan siap diunduh!`;
    setTimeout(() => {
      exportFeedback = null;
    }, 4000);
  }
</script>

<section id="payroll" class="bg-white py-20 lg:py-28 border-b border-[#e0e0e0]">
  <div class="max-w-[1584px] mx-auto px-4 lg:px-8">
    <div class="max-w-3xl mb-16">
      <span class="text-sm text-[#525252] block mb-4 font-mono">
        Otomatisasi Payroll &amp; Manajemen Kasbon
      </span>
      <h2 class="font-display-lg text-[#161616] tracking-tight mb-6">
        Kalkulasi gaji akurat dalam 1 klik tanpa spreadsheet manual.
      </h2>
      <p class="font-subhead text-[#525252]">
        Uji coba kalkulator penggajian di bawah ini untuk melihat bagaimana denda keterlambatan dari presensi harian, upah lembur, dan cicilan pinjaman kasbon terpotong otomatis.
      </p>
    </div>

    <!-- Simulator Interactive Box -->
    <div class="grid lg:grid-cols-12 gap-8 items-start">
      <!-- Calculator Controls -->
      <div class="lg:col-span-6 bg-[#f4f4f4] border border-[#e0e0e0] p-6 lg:p-8">
        <div class="flex items-center gap-2 pb-4 border-b border-[#e0e0e0] mb-6">
          <Calculator class="w-5 h-5 text-[#0f62fe]" />
          <span class="font-semibold text-[#161616] text-sm">Atur Parameter Simulasi Gaji</span>
        </div>

        <div class="space-y-5">
          <!-- Base Salary -->
          <div>
            <label for="baseSalary" class="block text-xs font-mono text-[#525252] mb-1.5">
              Gaji Pokok Bulanan (Rp)
            </label>
            <input
              id="baseSalary"
              type="number"
              bind:value={baseSalary}
              step="100000"
              class="w-full bg-white text-[#161616] border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-none px-4 py-2.5 text-sm font-mono"
            />
          </div>

          <!-- Overtime Hours -->
          <div>
            <div class="flex justify-between text-xs font-mono mb-1.5">
              <span class="text-[#525252]">Jam Lembur (Rp 25.000 / jam)</span>
              <span class="text-[#161616] font-semibold">{overtimeHours} Jam</span>
            </div>
            <input
              type="range"
              min="0"
              max="20"
              bind:value={overtimeHours}
              class="w-full accent-[#0f62fe] bg-[#e0e0e0] h-2"
            />
          </div>

          <!-- Late Minutes -->
          <div>
            <div class="flex justify-between text-xs font-mono mb-1.5">
              <span class="text-[#525252]">Akumulasi Keterlambatan (Denda Rp 2.000 / menit)</span>
              <span class="text-[#da1e28] font-semibold">{lateMinutes} Menit</span>
            </div>
            <input
              type="range"
              min="0"
              max="120"
              bind:value={lateMinutes}
              class="w-full accent-[#da1e28] bg-[#e0e0e0] h-2"
            />
          </div>

          <!-- Cash Advance Deduction -->
          <div>
            <label for="cashAdvance" class="block text-xs font-mono text-[#525252] mb-1.5">
              Potongan Cicilan Kasbon Karyawan (Rp)
            </label>
            <input
              id="cashAdvance"
              type="number"
              bind:value={cashAdvanceDeduction}
              step="50000"
              class="w-full bg-white text-[#161616] border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-none px-4 py-2.5 text-sm font-mono"
            />
          </div>
        </div>

        <div class="mt-6 pt-4 border-t border-[#e0e0e0] text-xs text-[#525252]">
          Tarif denda keterlambatan dan upah lembur dapat disesuaikan bebas oleh Owner pada menu pengaturan kedai.
        </div>
      </div>

      <!-- Live Generated Payslip Output -->
      <div class="lg:col-span-6 bg-white border border-[#e0e0e0] p-6 lg:p-8">
        <div class="flex items-center justify-between pb-4 border-b border-[#e0e0e0] mb-6">
          <div>
            <span class="text-xs font-mono text-[#8c8c8c] block">HASIL SLIP GAJI ELEKTRONIK</span>
            <span class="font-semibold text-[#161616] text-sm">Rian Hidayat • Barista Outlet Sleman</span>
          </div>
          <span class="text-xs font-mono px-2.5 py-1 bg-[#f4f4f4] border border-[#e0e0e0] text-[#525252]">
            Agustus 2026
          </span>
        </div>

        <!-- Breakdown Details -->
        <div class="space-y-3 font-mono text-xs mb-6">
          <div class="flex justify-between py-2 border-b border-[#f4f4f4]">
            <span class="text-[#525252]">Gaji Pokok (26 Hari Kerja)</span>
            <span class="text-[#161616]">Rp {baseSalary.toLocaleString('id-ID')}</span>
          </div>
          <div class="flex justify-between py-2 border-b border-[#f4f4f4] text-[#24a148]">
            <span>Tambahan Upah Lembur ({overtimeHours} Jam x Rp 25.000)</span>
            <span>+ Rp {overtimePay.toLocaleString('id-ID')}</span>
          </div>
          <div class="flex justify-between py-2 border-b border-[#f4f4f4] text-[#da1e28]">
            <span>Potongan Denda Telat ({lateMinutes} Menit x Rp 2.000)</span>
            <span>- Rp {latePenalty.toLocaleString('id-ID')}</span>
          </div>
          <div class="flex justify-between py-2 border-b border-[#f4f4f4] text-[#da1e28]">
            <span>Potongan Pelunasan Pinjaman Kasbon</span>
            <span>- Rp {cashAdvanceDeduction.toLocaleString('id-ID')}</span>
          </div>
        </div>

        <!-- Net Salary Grand Total Box -->
        <div class="p-4 bg-[#161616] text-white flex items-center justify-between font-mono mb-6">
          <div>
            <span class="text-xs text-[#c6c6c6] block">TOTAL GAJI BERSIH (NET)</span>
            <span class="text-[11px] text-[#8c8c8c]">Nominal yang ditransfer ke rekening staf</span>
          </div>
          <span class="text-xl lg:text-2xl font-bold">
            Rp {netSalary.toLocaleString('id-ID')}
          </span>
        </div>

        {#if exportFeedback}
          <div class="mb-4 p-3 bg-[#24a148]/10 border border-[#24a148] text-[#161616] text-xs font-mono flex items-center gap-2">
            <CheckCircle2 class="w-4 h-4 text-[#24a148] shrink-0" />
            <span>{exportFeedback}</span>
          </div>
        {/if}

        <!-- Export Buttons -->
        <div class="grid grid-cols-2 gap-3">
          <button
            type="button"
            onclick={() => triggerExport('excel')}
            class="py-3 px-4 text-xs font-semibold text-[#161616] bg-[#f4f4f4] border border-[#e0e0e0] hover:bg-[#e0e0e0] flex items-center justify-center gap-2 transition-colors"
          >
            <FileSpreadsheet class="w-4 h-4 text-[#24a148]" />
            Ekspor Excel Lengkap
          </button>
          <button
            type="button"
            onclick={() => triggerExport('bank')}
            class="py-3 px-4 text-xs font-semibold text-white bg-[#0f62fe] hover:bg-[#0050e6] flex items-center justify-center gap-2 transition-colors"
          >
            <Download class="w-4 h-4" />
            Format Transfer Bank
          </button>
        </div>
      </div>
    </div>
  </div>
</section>
