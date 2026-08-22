<script lang="ts">
  import { ShieldCheck, Zap, Printer, Lock, Smartphone, FileSpreadsheet } from 'lucide-svelte';

  const advantages = [
    {
      icon: Zap,
      title: 'Antrean Cepat 60 FPS Tanpa Macet',
      description:
        'Layar kasir tetap responsif seketika saat jam sibuk sore hari. Transaksi langsung tercatat dan struk tercetak tanpa perlu menunggu loading internet.'
    },
    {
      icon: ShieldCheck,
      title: 'Audit Kehadiran Transparan',
      description:
        'Foto selfie wajib diambil langsung di lokasi toko. Koordinat GPS dan jam aktual dicetak permanen sehingga tim bekerja dengan disiplin dan jujur.'
    },
    {
      icon: Lock,
      title: 'Penguncian Tablet Kasir (Device Token)',
      description:
        'Setiap tablet kasir diikat secara aman ke satu cabang spesifik. Akses keuangan dan laporan penjualan terlindungi dari kebocoran data.'
    },
    {
      icon: Printer,
      title: 'Kompatibel dengan Printer Bluetooth Anda',
      description:
        'Mendukung semua printer struk thermal standar 58mm dan 80mm via Bluetooth maupun kabel USB tanpa perlu membeli hardware mahal baru.'
    },
    {
      icon: Smartphone,
      title: 'Pantau Banyak Cabang dari Smartphone',
      description:
        'Owner dapat mengecek omzet harian, foto presensi barista, dan status kas kasir seluruh outlet secara langsung dari mana saja.'
    },
    {
      icon: FileSpreadsheet,
      title: 'Ekspor Gaji Siap Transfer Bank',
      description:
        'Satu klik untuk mengunduh rekap penggajian dalam format file yang langsung dapat diunggah ke portal perbankan (BCA, Mandiri, BRI, BNI).'
    }
  ];
</script>

<section id="advantages" class="bg-[#f4f4f4] py-20 lg:py-28 border-b border-[#e0e0e0]">
  <div class="max-w-[1584px] mx-auto px-4 lg:px-8">
    <div class="max-w-3xl mb-16">
      <span class="text-sm text-[#525252] block mb-4 font-mono">
        Keunggulan Operasional Nyata
      </span>
      <h2 class="font-display-lg text-[#161616] tracking-tight mb-6">
        Dibangun untuk kenyamanan dan keamanan bisnis Anda.
      </h2>
      <p class="font-subhead text-[#525252]">
        Menghilangkan kerugian tersembunyi yang sering dialami pemilik bisnis kedai kopi dan resto harian.
      </p>
    </div>

    <!-- Advantages Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-px bg-[#e0e0e0] border border-[#e0e0e0]">
      {#each advantages as item}
        <div class="bg-white p-8 flex flex-col justify-between hover:border-[#0f62fe] transition-colors">
          <div>
            <div class="w-12 h-12 bg-[#f4f4f4] text-[#0f62fe] flex items-center justify-center mb-6">
              <item.icon class="w-6 h-6" />
            </div>

            <h3 class="font-card-title text-[#161616] mb-3">
              {item.title}
            </h3>

            <p class="font-body-sm text-[#525252] leading-relaxed">
              {item.description}
            </p>
          </div>
        </div>
      {/each}
    </div>
  </div>
</section>
