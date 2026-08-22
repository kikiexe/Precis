<script lang="ts">
  import { Tablet, Camera, FileCheck2, ArrowRight } from 'lucide-svelte';

  const steps = [
    {
      step: '01',
      icon: Tablet,
      title: 'Pasang Tablet di Meja Bar Kasir',
      subtitle: 'Siap Beroperasi dalam 1 Menit',
      description:
        'Cukup buka peramban di tablet toko Anda dan masukkan PIN aktivasi outlet. Kasir tidak perlu membuat akun pribadi dan langsung siap melayani pesanan pelanggan.',
      details: [
        'Tidak butuh instalasi rumit',
        'Mendukung printer struk Bluetooth thermal',
        'Otomatis aktif mode kasir offline'
      ]
    },
    {
      step: '02',
      icon: Camera,
      title: 'Barista Selfie Presensi Masuk Shift',
      subtitle: 'Stempel Lokasi & Waktu Otomatis',
      description:
        'Karyawan membuka portal presensi di smartphone masing-masing saat tiba di outlet. Sistem secara otomatis menyematkan koordinat lokasi toko dan jam WIB di atas foto.',
      details: [
        'Wajib foto langsung (anti ambil dari galeri)',
        'Otomatis terverifikasi radius toko',
        'Terdeteksi keterlambatan per menit'
      ]
    },
    {
      step: '03',
      icon: FileCheck2,
      title: 'Tutup Kas Harian & Transfer Gaji Bulanan',
      subtitle: '1-Klik Rekapitulasi Finansial',
      description:
        'Setiap malam kasir mencatat rekonsiliasi kas tunai untuk mencegah selisih. Di akhir bulan, Owner langsung mengunduh rekap gaji bersih yang sudah terpotong denda dan kasbon.',
      details: [
        'Laporan selisih kas harian akurat',
        'Slip gaji digital untuk setiap staf',
        'Format ekspor siap impor bank (BCA/Mandiri)'
      ]
    }
  ];
</script>

<section id="how-it-works" class="bg-[#f4f4f4] py-20 lg:py-28 border-b border-[#e0e0e0]">
  <div class="max-w-[1584px] mx-auto px-4 lg:px-8">
    <div class="max-w-3xl mb-16">
      <span class="text-sm text-[#525252] block mb-4 font-mono">
        Alur Praktis di Lapangan
      </span>
      <h2 class="font-display-lg text-[#161616] tracking-tight mb-6">
        Tiga langkah mudah menjalankan operasional kedai.
      </h2>
      <p class="font-subhead text-[#525252]">
        Dirancang agar mudah dipahami oleh seluruh tim kedai Anda tanpa perlu pelatihan teknis yang rumit.
      </p>
    </div>

    <!-- Steps Grid -->
    <div class="grid lg:grid-cols-3 gap-8">
      {#each steps as stepItem}
        <div class="bg-white p-8 border border-[#e0e0e0] flex flex-col justify-between hover:border-[#0f62fe] transition-colors">
          <div>
            <div class="flex items-center justify-between mb-6">
              <span class="font-mono text-2xl font-bold text-[#0f62fe]">
                Langkah {stepItem.step}
              </span>
              <div class="w-12 h-12 bg-[#f4f4f4] text-[#161616] flex items-center justify-center">
                <stepItem.icon class="w-6 h-6 text-[#0f62fe]" />
              </div>
            </div>

            <h3 class="font-card-title text-[#161616] mb-1">
              {stepItem.title}
            </h3>
            <p class="text-xs font-mono text-[#525252] mb-4">{stepItem.subtitle}</p>

            <p class="font-body-sm text-[#525252] leading-relaxed mb-6">
              {stepItem.description}
            </p>

            <ul class="space-y-2.5 pt-4 border-t border-[#f4f4f4] mb-8">
              {#each stepItem.details as detail}
                <li class="text-xs text-[#161616] flex items-center gap-2">
                  <span class="w-1.5 h-1.5 bg-[#0f62fe]"></span>
                  <span>{detail}</span>
                </li>
              {/each}
            </ul>
          </div>
        </div>
      {/each}
    </div>

    <div class="mt-12 text-center">
      <a
        href="https://app.precis.com/register"
        class="inline-flex items-center gap-2 text-sm font-semibold text-[#0f62fe] hover:text-[#0043ce]"
      >
        Mulai setup kedai Anda dalam 5 menit
        <ArrowRight class="w-4 h-4" />
      </a>
    </div>
  </div>
</section>
