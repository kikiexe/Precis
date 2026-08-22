<script lang="ts">
  import { Camera, MapPin, Clock, ShieldCheck, RefreshCw, AlertTriangle } from 'lucide-svelte';

  let employeeName = $state('Rian Hidayat (Barista)');
  let shiftTime = $state('Shift Pagi: 07:00 - 15:00 WIB');
  let clockInTime = $state('07:18:24 WIB');
  let outletLocation = $state('Outlet Sleman (Radius 45m dari Bar Toko)');
  let isAuditing = $state(false);

  function refreshAudit() {
    isAuditing = true;
    setTimeout(() => {
      isAuditing = false;
    }, 600);
  }
</script>

<section id="attendance" class="bg-[#f4f4f4] py-20 lg:py-28 border-b border-[#e0e0e0]">
  <div class="max-w-[1584px] mx-auto px-4 lg:px-8">
    <div class="max-w-3xl mb-16">
      <span class="text-sm text-[#525252] block mb-4 font-mono">
        Presensi Karyawan &amp; Wall of Faces
      </span>
      <h2 class="font-display-lg text-[#161616] tracking-tight mb-6">
        Kejujuran tim dengan audit visual harian.
      </h2>
      <p class="font-subhead text-[#525252]">
        Tidak ada lagi celah titip absen atau manipulasi lokasi. Stempel waktu WIB dan titik GPS toko langsung dicetak menyatu dengan foto selfie karyawan saat jam kerja dimulai.
      </p>
    </div>

    <div class="grid lg:grid-cols-12 gap-8 items-start">
      <!-- Live Interactive Visualizer -->
      <div class="lg:col-span-7 bg-white border border-[#e0e0e0] p-6 lg:p-8">
        <div class="flex items-center justify-between pb-4 border-b border-[#e0e0e0] mb-6">
          <div class="flex items-center gap-2">
            <Camera class="w-5 h-5 text-[#0f62fe]" />
            <span class="font-semibold text-[#161616] text-sm">Tampilan Feed Presensi Toko (Wall of Faces)</span>
          </div>
          <button
            type="button"
            onclick={refreshAudit}
            class="inline-flex items-center gap-1.5 text-xs font-mono text-[#525252] hover:text-[#161616] bg-[#f4f4f4] px-3 py-1.5 border border-[#e0e0e0]"
          >
            <RefreshCw class={`w-3 h-3 ${isAuditing ? 'animate-spin' : ''}`} />
            Perbarui Feed
          </button>
        </div>

        <!-- Render Card -->
        <div class="relative bg-[#161616] aspect-[4/3] flex flex-col justify-between p-6 text-white overflow-hidden border border-[#e0e0e0]">
          <!-- Top Status Badge -->
          <div class="flex justify-between items-start">
            <span class="bg-[#24a148] text-white text-xs font-mono px-3 py-1 flex items-center gap-1.5">
              <ShieldCheck class="w-3.5 h-3.5" />
              Lokasi Toko Terverifikasi
            </span>
            <span class="bg-black/70 text-white text-xs font-mono px-2.5 py-1">
              Foto Langsung Kamera Depan
            </span>
          </div>

          <!-- Center Placeholder -->
          <div class="flex flex-col items-center justify-center text-center opacity-40">
            <Camera class="w-16 h-16 mb-2" />
            <span class="text-xs font-mono">Tangkapan Kamera Selfie Karyawan</span>
          </div>

          <!-- Bottom Burned Watermark -->
          <div class="bg-black/85 p-4 border-l-4 border-[#0f62fe]">
            <div class="flex justify-between items-center mb-1">
              <span class="font-bold text-sm font-mono tracking-wide text-white">{employeeName}</span>
              <span class="text-xs font-mono text-[#f1c21b] bg-[#f1c21b]/20 px-2 py-0.5">
                Telat 18 Menit
              </span>
            </div>
            <div class="text-xs font-mono text-[#c6c6c6] flex items-center gap-2">
              <Clock class="w-3 h-3 text-[#0f62fe]" />
              <span>{clockInTime} ({shiftTime})</span>
            </div>
            <div class="text-[11px] font-mono text-[#8c8c8c] flex items-center gap-2 mt-1">
              <MapPin class="w-3 h-3 text-[#0f62fe]" />
              <span>{outletLocation}</span>
            </div>
          </div>
        </div>

        <div class="mt-6 pt-4 border-t border-[#e0e0e0] grid grid-cols-3 text-center text-xs font-mono text-[#525252]">
          <div>
            <span class="block text-[#161616] font-semibold">100% Anti-Titip Absen</span>
            Wajib Foto Live
          </div>
          <div>
            <span class="block text-[#161616] font-semibold">60 Hari</span>
            Arsip Foto Tersimpan
          </div>
          <div>
            <span class="block text-[#161616] font-semibold">Otomatis Hitung</span>
            Denda Telat per Menit
          </div>
        </div>
      </div>

      <!-- Explanation Panel -->
      <div class="lg:col-span-5 space-y-6">
        <div class="bg-white border border-[#e0e0e0] p-6">
          <h3 class="font-card-title text-[#161616] mb-3">
            Pemeriksaan Cepat oleh Store Manager
          </h3>
          <p class="font-body-sm text-[#525252] leading-relaxed mb-4">
            Kepala Toko atau Owner cukup membuka satu halaman Wall of Faces setiap pagi. Dalam 2 menit, Anda bisa langsung melihat wajah barista yang masuk, kesesuaian seragam, dan jam kedatangan yang sebenarnya.
          </p>
          <div class="bg-[#f4f4f4] p-3 text-xs text-[#161616] border-l-2 border-[#0f62fe] flex items-start gap-2">
            <AlertTriangle class="w-4 h-4 text-[#0f62fe] shrink-0 mt-0.5" />
            <span>Karyawan tidak bisa mengunggah foto lama dari galeri galeri HP.</span>
          </div>
        </div>

        <div class="bg-white border border-[#e0e0e0] p-6">
          <h3 class="font-card-title text-[#161616] mb-3">
            Otomatis Masuk ke Rekap Gaji
          </h3>
          <p class="font-body-sm text-[#525252] leading-relaxed">
            Setiap menit keterlambatan akan otomatis dikonversikan menjadi denda sesuai aturan kedai Anda, dan lembur akan dihitung otomatis saat jam shift ditutup.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>
