<script lang="ts">
  import { Camera, MonitorSmartphone, Receipt, Building2, ArrowRight } from 'lucide-svelte';

  const features = [
    {
      number: '01',
      icon: Camera,
      title: 'Presensi Selfie & Stempel Lokasi',
      description:
        'Karyawan wajib mengambil foto langsung saat jam shift dimulai. Lokasi toko dan stempel waktu WIB dibakar permanen ke atas foto untuk mencegah titip absen dan klaim palsu.',
      linkHref: '#attendance',
      linkLabel: 'Lihat Cara Kerja Presensi'
    },
    {
      number: '02',
      icon: MonitorSmartphone,
      title: 'Kasir POS Offline-Ready (60 FPS)',
      description:
        'Aplikasi kasir tablet yang tidak pernah macet. Tetap dapat mencatat pesanan pelanggan dan mencetak struk kasir Bluetooth meski koneksi Wi-Fi kedai terputus.',
      linkHref: '#pos',
      linkLabel: 'Coba Simulator Kasir'
    },
    {
      number: '03',
      icon: Receipt,
      title: 'Otomatisasi Payroll & Kasbon',
      description:
        'Kalkulasi gaji bersih akhir bulan otomatis memperhitungkan shift aktual, denda keterlambatan per menit, lembur, dan cicilan kasbon tanpa pusing rekap manual.',
      linkHref: '#payroll',
      linkLabel: 'Coba Kalkulator Payroll'
    },
    {
      number: '04',
      icon: Building2,
      title: 'Sistem Terintegrasi 1 Akun Terpusat',
      description:
        'Owner dapat memantau penjualan harian dan presensi seluruh outlet dari satu smartphone. Kepala Toko (Store Manager) memiliki akses aman khusus untuk cabang mereka.',
      linkHref: '#pricing',
      linkLabel: 'Cek Paket Multi-Outlet'
    }
  ];
</script>

<section id="features" class="bg-white py-20 lg:py-28 border-b border-[#e0e0e0]">
  <div class="max-w-[1584px] mx-auto px-4 lg:px-8">
    <!-- Section Header -->
    <div class="max-w-3xl mb-16">
      <span class="text-sm text-[#525252] block mb-4 font-mono">
        4 Pilar Solusi Operasional
      </span>
      <h2 class="font-display-lg text-[#161616] tracking-tight mb-6">
        Semua kebutuhan kedai terhubung dalam satu sistem.
      </h2>
      <p class="font-subhead text-[#525252]">
        Menghilangkan kerugian akibat kecurangan absen, antrean kasir macet, dan kesalahan hitung gaji bulanan.
      </p>
    </div>

    <!-- 4-Card Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-px bg-[#e0e0e0] border border-[#e0e0e0]">
      {#each features as feature}
        <div class="bg-white p-6 lg:p-8 flex flex-col justify-between group hover:bg-[#f4f4f4] transition-colors">
          <div>
            <div class="flex items-center justify-between mb-8">
              <span class="font-mono text-xs text-[#8c8c8c]">{feature.number}</span>
              <div class="w-10 h-10 flex items-center justify-center bg-[#f4f4f4] text-[#161616] group-hover:bg-[#0f62fe] group-hover:text-white transition-colors">
                <feature.icon class="w-5 h-5" />
              </div>
            </div>

            <h3 class="font-card-title text-[#161616] mb-4">
              {feature.title}
            </h3>

            <p class="font-body-sm text-[#525252] leading-relaxed mb-8">
              {feature.description}
            </p>
          </div>

          <a
            href={feature.linkHref}
            class="inline-flex items-center gap-2 text-sm font-semibold text-[#0f62fe] hover:text-[#0043ce] transition-colors"
          >
            {feature.linkLabel}
            <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
          </a>
        </div>
      {/each}
    </div>
  </div>
</section>
