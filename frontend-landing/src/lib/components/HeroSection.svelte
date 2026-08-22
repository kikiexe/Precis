<script lang="ts">
  import { onMount } from 'svelte';
  import { ArrowRight, Camera, ShoppingBag, Receipt, CheckCircle2 } from 'lucide-svelte';

  const words = ['presensi', 'kasir POS', 'payroll', 'outlet'];
  let currentWordIndex = $state(0);
  let activeTab = $state<'attendance' | 'pos' | 'payroll'>('attendance');

  onMount(() => {
    const interval = setInterval(() => {
      currentWordIndex = (currentWordIndex + 1) % words.length;
    }, 2400);

    return () => clearInterval(interval);
  });
</script>

<section class="bg-white border-b border-[#e0e0e0] py-16 lg:py-24">
  <div class="max-w-[1584px] mx-auto px-4 lg:px-8">
    <!-- Eyebrow -->
    <div class="mb-6">
      <span class="text-sm text-[#525252] inline-flex items-center gap-2 font-mono">
        <span class="w-2 h-2 bg-[#0f62fe]"></span>
        Platform SaaS Khusus Bisnis Kuliner &amp; Kedai Kopi
      </span>
    </div>

    <!-- Main Grid -->
    <div class="grid lg:grid-cols-12 gap-12 lg:gap-16 items-start">
      <!-- Left Column: Copy & Actions -->
      <div class="lg:col-span-6">
        <h1 class="font-display-xl text-[#161616] tracking-tight mb-8">
          Satu platform kelola
          <span class="relative inline-block ml-2 text-[#0f62fe]">
            {#key currentWordIndex}
              <span class="inline-flex">
                {#each words[currentWordIndex].split('') as char, i}
                  <span
                    class="inline-block animate-char-in"
                    style="animation-delay: {i * 45}ms;"
                  >
                    {char === ' ' ? '\u00A0' : char}
                  </span>
                {/each}
              </span>
            {/key}
            <span class="absolute -bottom-1 left-0 right-0 h-1 bg-[#0f62fe]/20"></span>
          </span>
          kedai F&amp;B Anda.
        </h1>

        <p class="font-subhead text-[#525252] leading-relaxed mb-10 max-w-xl">
          Hentikan kecurangan titip absen dengan watermark GPS permanen, pastikan transaksi kasir tetap berjalan lancar saat internet mati, dan hitung gaji akhir bulan otomatis tanpa rekap spreadsheet manual.
        </p>

        <!-- CTA Action Buttons -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 mb-12">
          <a
            href="https://app.precis.com/register"
            class="h-12 px-6 inline-flex items-center justify-center gap-2 text-sm text-white bg-[#0f62fe] hover:bg-[#0050e6] active:bg-[#002d9c] transition-colors"
          >
            Mulai Uji Coba 14 Hari Gratis
            <ArrowRight class="w-4 h-4" />
          </a>
          <a
            href="#features"
            class="h-12 px-6 inline-flex items-center justify-center text-sm text-[#161616] bg-white border border-[#161616] hover:bg-[#f4f4f4] transition-colors"
          >
            Pelajari Solusi
          </a>
        </div>

        <!-- Highlights Checklist -->
        <div class="grid grid-cols-2 gap-4 pt-8 border-t border-[#e0e0e0]">
          <div class="flex items-start gap-2.5">
            <CheckCircle2 class="w-4 h-4 text-[#0f62fe] shrink-0 mt-0.5" />
            <div>
              <p class="text-sm font-semibold text-[#161616]">Kasir Anti-Macet</p>
              <p class="text-xs text-[#525252]">Transaksi aman tersimpan saat koneksi Wi-Fi putus</p>
            </div>
          </div>
          <div class="flex items-start gap-2.5">
            <CheckCircle2 class="w-4 h-4 text-[#0f62fe] shrink-0 mt-0.5" />
            <div>
              <p class="text-sm font-semibold text-[#161616]">Audit Visual Harian</p>
              <p class="text-xs text-[#525252]">Foto selfie dengan stempel waktu &amp; GPS permanen</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Interactive Live Preview Card -->
      <div class="lg:col-span-6 bg-[#f4f4f4] border border-[#e0e0e0] p-6 lg:p-8">
        <!-- Tab Selector Header -->
        <div class="flex border-b border-[#e0e0e0] mb-6 bg-white">
          <button
            type="button"
            onclick={() => (activeTab = 'attendance')}
            class={`flex-1 py-3 px-4 text-xs sm:text-sm transition-all flex items-center justify-center gap-2 border-b-2 ${
              activeTab === 'attendance'
                ? 'text-[#161616] font-semibold border-[#0f62fe] bg-white'
                : 'text-[#525252] border-transparent hover:text-[#161616]'
            }`}
          >
            <Camera class="w-4 h-4" />
            Presensi Karyawan
          </button>

          <button
            type="button"
            onclick={() => (activeTab = 'pos')}
            class={`flex-1 py-3 px-4 text-xs sm:text-sm transition-all flex items-center justify-center gap-2 border-b-2 ${
              activeTab === 'pos'
                ? 'text-[#161616] font-semibold border-[#0f62fe] bg-white'
                : 'text-[#525252] border-transparent hover:text-[#161616]'
            }`}
          >
            <ShoppingBag class="w-4 h-4" />
            Kasir Layar Sentuh
          </button>

          <button
            type="button"
            onclick={() => (activeTab = 'payroll')}
            class={`flex-1 py-3 px-4 text-xs sm:text-sm transition-all flex items-center justify-center gap-2 border-b-2 ${
              activeTab === 'payroll'
                ? 'text-[#161616] font-semibold border-[#0f62fe] bg-white'
                : 'text-[#525252] border-transparent hover:text-[#161616]'
            }`}
          >
            <Receipt class="w-4 h-4" />
            Slip Gaji Otomatis
          </button>
        </div>

        <!-- Tab 1: Attendance Preview -->
        {#if activeTab === 'attendance'}
          <div class="bg-white border border-[#e0e0e0] p-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#f4f4f4] mb-4">
              <span class="text-xs font-mono text-[#525252]">AUDIT PRESENSI SELFIE</span>
              <span class="text-xs text-[#24a148] font-mono font-medium">Radius Toko: Sesuai</span>
            </div>

            <div class="relative bg-[#161616] aspect-[4/3] flex flex-col justify-end p-4 text-white overflow-hidden">
              <div class="absolute inset-0 flex items-center justify-center opacity-30">
                <Camera class="w-16 h-16 text-white" />
              </div>

              <!-- Watermark Overlay Bar -->
              <div class="relative z-10 bg-black/85 p-3 border-l-4 border-[#0f62fe]">
                <p class="text-xs font-bold font-mono tracking-wide text-white">BARISTA: RIAN HIDAYAT</p>
                <p class="text-[11px] font-mono text-[#c6c6c6] mt-0.5">22 Agustus 2026 • 07:18:24 WIB</p>
                <p class="text-[10px] font-mono text-[#8c8c8c]">Lokasi: Outlet Sleman (Lat: -7.7829, Lng: 110.3671)</p>
              </div>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs text-[#525252]">
              <span>Stempel Waktu Permanen</span>
              <span>Retensi Foto 60 Hari</span>
            </div>
          </div>
        {/if}

        <!-- Tab 2: POS Kiosk Preview -->
        {#if activeTab === 'pos'}
          <div class="bg-white border border-[#e0e0e0] p-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#f4f4f4] mb-4">
              <span class="text-xs font-mono text-[#525252]">KASIR TABLET OFFLINE-READY</span>
              <span class="text-xs text-[#0f62fe] font-mono font-medium">Mode Offline Siap</span>
            </div>

            <div class="grid grid-cols-2 gap-2 mb-4">
              <div class="p-3 bg-[#f4f4f4] border border-[#e0e0e0]">
                <p class="text-xs font-medium text-[#161616]">Es Kopi Susu Aren</p>
                <p class="text-xs text-[#0f62fe] font-mono mt-1">Rp 20.000</p>
              </div>
              <div class="p-3 bg-[#f4f4f4] border border-[#e0e0e0]">
                <p class="text-xs font-medium text-[#161616]">Croissant Butter</p>
                <p class="text-xs text-[#0f62fe] font-mono mt-1">Rp 25.000</p>
              </div>
            </div>

            <div class="p-3 bg-[#f4f4f4] border-t border-[#e0e0e0] flex items-center justify-between text-xs font-mono">
              <span class="text-[#525252]">Total Pesanan (2 items)</span>
              <span class="font-bold text-[#161616]">Rp 45.000</span>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs text-[#525252]">
              <span>Cetak Struk: Bluetooth Thermal</span>
              <span>Penyimpanan: Database Lokal</span>
            </div>
          </div>
        {/if}

        <!-- Tab 3: Payroll Preview -->
        {#if activeTab === 'payroll'}
          <div class="bg-white border border-[#e0e0e0] p-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#f4f4f4] mb-4">
              <span class="text-xs font-mono text-[#525252]">REKAP PENGGAJIAN OTOMATIS</span>
              <span class="text-xs text-[#24a148] font-mono font-medium">Periode: Agustus 2026</span>
            </div>

            <div class="space-y-2 text-xs font-mono mb-4">
              <div class="flex justify-between text-[#525252]">
                <span>Gaji Pokok (26 Hari Shift)</span>
                <span class="text-[#161616]">Rp 2.800.000</span>
              </div>
              <div class="flex justify-between text-[#24a148]">
                <span>Upah Lembur (4 Jam)</span>
                <span>+ Rp 100.000</span>
              </div>
              <div class="flex justify-between text-[#da1e28]">
                <span>Denda Telat (18 Menit)</span>
                <span>- Rp 36.000</span>
              </div>
              <div class="flex justify-between text-[#da1e28]">
                <span>Cicilan Kasbon Bulan Ini</span>
                <span>- Rp 150.000</span>
              </div>
            </div>

            <div class="p-3 bg-[#161616] text-white flex items-center justify-between text-xs font-mono">
              <span>Gaji Bersih Siap Transfer</span>
              <span class="font-bold text-sm">Rp 2.714.000</span>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs text-[#525252]">
              <span>Ekspor 1-Klik: Excel &amp; CSV Bank</span>
              <span>Bebas Salah Hitung</span>
            </div>
          </div>
        {/if}
      </div>
    </div>
  </div>

  <!-- Marquee Stats Bar -->
  <div class="mt-16 pt-8 border-t border-[#e0e0e0] overflow-hidden">
    <div class="flex gap-12 marquee-track whitespace-nowrap">
      {#each Array(2) as _}
        <div class="flex gap-12 items-center">
          <div class="flex items-baseline gap-3">
            <span class="text-2xl lg:text-3xl font-display font-semibold text-[#161616]">0 Detik</span>
            <span class="text-xs text-[#525252]">Kasir macet saat internet mati</span>
          </div>
          <span class="text-[#e0e0e0]">|</span>
          <div class="flex items-baseline gap-3">
            <span class="text-2xl lg:text-3xl font-display font-semibold text-[#161616]">100%</span>
            <span class="text-xs text-[#525252]">Kalkulasi payroll &amp; denda otomatis</span>
          </div>
          <span class="text-[#e0e0e0]">|</span>
          <div class="flex items-baseline gap-3">
            <span class="text-2xl lg:text-3xl font-display font-semibold text-[#161616]">60 Hari</span>
            <span class="text-xs text-[#525252]">Arsip foto selfie tersimpan aman</span>
          </div>
          <span class="text-[#e0e0e0]">|</span>
          <div class="flex items-baseline gap-3">
            <span class="text-2xl lg:text-3xl font-display font-semibold text-[#161616]">3 Menit</span>
            <span class="text-xs text-[#525252]">Setup outlet &amp; geofence radius</span>
          </div>
          <span class="text-[#e0e0e0]">|</span>
        </div>
      {/each}
    </div>
  </div>
</section>
