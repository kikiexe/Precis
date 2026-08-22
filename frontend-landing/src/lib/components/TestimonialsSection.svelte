<script lang="ts">
  const testimonials = [
    {
      quote:
        'Sistem presensi selfie ber-watermark GPS dan Wall of Faces mengeliminasi kasus titip absen secara total di kedai kami. Store Manager kami cukup mengaudit kehadiran dalam 3 menit tiap pagi.',
      author: 'Arief Pratama',
      role: 'Owner',
      outlet: 'Omore Coffee & Roastery (2 Cabang)',
      metric: '0 Kasus Titip Absen'
    },
    {
      quote:
        'Saat internet FO kedai putus di jam sibuk 5 sore, kasir POS Précis tetap jalan lancar tanpa jeda. Pas Wi-Fi nyala lagi jam 8 malam, semua pesanan langsung sinkron tanpa duplikasi.',
      author: 'Rian Hidayat',
      role: 'Store Manager',
      outlet: 'Kedai Kopi Titik Temu',
      metric: '100% Transaksi Offline Aman'
    },
    {
      quote:
        'Dulu butuh 2 hari hitung denda telat, lembur, dan kasbon staf. Sekarang tinggal klik ekspor CSV slip gaji siap impor ke portal transfer massal bank BCA.',
      author: 'Dewi Lestari',
      role: 'Finance & Operations Lead',
      outlet: 'Kala Kopi Group (3 Cabang)',
      metric: 'Hemat 14 Jam Payroll Bulanan'
    }
  ];
</script>

<section class="bg-white py-20 lg:py-28 border-b border-[#e0e0e0]">
  <div class="max-w-[1584px] mx-auto px-4 lg:px-8">
    <div class="max-w-3xl mb-16">
      <span class="text-sm text-[#525252] block mb-4 font-mono">
        Cerita Sukses Pelanggan
      </span>
      <h2 class="font-display-lg text-[#161616] tracking-tight mb-6">
        Dipercaya oleh para pemilik kedai kopi dan resto.
      </h2>
      <p class="font-subhead text-[#525252]">
        Dengarkan bagaimana Précis mengubah ritme kerja harian bisnis kuliner menjadi jauh lebih tenang dan teratur.
      </p>
    </div>

    <div class="grid md:grid-cols-3 gap-px bg-[#e0e0e0] border border-[#e0e0e0]">
      {#each testimonials as item}
        <div class="bg-white p-8 flex flex-col justify-between hover:bg-[#f4f4f4] transition-colors">
          <div>
            <span class="inline-block px-2.5 py-1 bg-[#f4f4f4] text-[#0f62fe] font-mono text-xs font-semibold mb-6 border border-[#e0e0e0]">
              {item.metric}
            </span>
            <p class="font-body text-[#161616] leading-relaxed mb-8">
              "{item.quote}"
            </p>
          </div>

          <div class="pt-6 border-t border-[#e0e0e0]">
            <p class="font-semibold text-[#161616]">{item.author}</p>
            <p class="text-xs text-[#525252]">{item.role} • {item.outlet}</p>
          </div>
        </div>
      {/each}
    </div>
  </div>
</section>
