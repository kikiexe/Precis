<script lang="ts">
  import { onMount } from "svelte";

  const testimonials = [
    {
      quote: "Sistem presensi selfie ber-watermark GPS dan Wall of Faces mengeliminasi kasus titip absen secara total di 3 cabang toko kami.",
      author: "Arief Pratama",
      role: "Owner",
      company: "Omore Coffee & Roastery",
      metric: "0 Kasus Titip Absen",
    },
    {
      quote: "Saat koneksi Wi-Fi toko putus di jam sibuk, kasir POS Précis tetap melayani transaksi dan cetak struk tanpa delay sedetik pun.",
      author: "Hendra Wijaya",
      role: "Store Manager",
      company: "Modis Fashion Retail",
      metric: "100% Kasir Offline Ready",
    },
    {
      quote: "Dulu butuh 2 hari hitung denda telat, lembur, dan kasbon staf. Sekarang tinggal klik ekspor CSV slip gaji siap impor bank BCA.",
      author: "Dewi Lestari",
      role: "Finance Lead",
      company: "Kala Retail & F&B Group",
      metric: "Hemat 14 Jam Payroll Bulanan",
    },
    {
      quote: "Dashboard owner memberi saya visibilitas penuh terhadap performa penjualan seluruh cabang langsung dari smartphone kapan saja.",
      author: "Budi Santoso",
      role: "Founder",
      company: "Artisan Bakery & Cafe",
      metric: "Kontrol Operasional Penuh",
    },
  ];

  const brandPartners = [
    "Omore Coffee", "Modis Fashion", "Kala Retail Group", "Artisan Bakery",
    "Kedai Senja", "Pojok Rasa", "Optik Cemerlang", "Urban Barber"
  ];

  let activeIndex = $state(0);
  let isAnimating = $state(false);

  onMount(() => {
    const interval = setInterval(() => {
      isAnimating = true;
      setTimeout(() => {
        activeIndex = (activeIndex + 1) % testimonials.length;
        isAnimating = false;
      }, 300);
    }, 5000);

    return () => clearInterval(interval);
  });
</script>

<section class="relative py-32 lg:py-40 bg-white border-t border-[#e0e0e0] lg:pb-14">
  <div class="max-w-7xl mx-auto px-6 lg:px-12">
    <!-- Section Label -->
    <div class="flex items-center gap-4 mb-16">
      <span class="font-mono text-xs tracking-widest text-[#525252] uppercase">
        Pengalaman Pengguna
      </span>
      <div class="flex-1 h-px bg-[#e0e0e0]"></div>
      <span class="font-mono text-xs text-[#525252]">
        {String(activeIndex + 1).padStart(2, "0")} / {String(testimonials.length).padStart(2, "0")}
      </span>
    </div>

    <!-- Main Quote -->
    <div class="grid lg:grid-cols-12 gap-12 lg:gap-20">
      <div class="lg:col-span-8">
        <blockquote
          class={`transition-all duration-300 ${
            isAnimating ? "opacity-0 translate-y-4" : "opacity-100 translate-y-0"
          }`}
        >
          <p class="font-display text-4xl md:text-5xl lg:text-6xl leading-[1.1] tracking-tight text-[#161616]">
            "{testimonials[activeIndex].quote}"
          </p>
        </blockquote>

        <!-- Author -->
        <div
          class={`mt-12 flex items-center gap-6 transition-all duration-300 delay-100 ${
            isAnimating ? "opacity-0" : "opacity-100"
          }`}
        >
          <div class="w-16 h-16 rounded-full bg-[#f4f4f4] border border-[#e0e0e0] flex items-center justify-center">
            <span class="font-display text-2xl text-[#0f62fe]">
              {testimonials[activeIndex].author.charAt(0)}
            </span>
          </div>
          <div>
            <p class="text-lg font-medium text-[#161616]">{testimonials[activeIndex].author}</p>
            <p class="text-[#525252] text-sm">
              {testimonials[activeIndex].role}, {testimonials[activeIndex].company}
            </p>
          </div>
        </div>
      </div>

      <!-- Metric Highlight -->
      <div class="lg:col-span-4 flex flex-col justify-center">
        <div
          class={`p-8 border border-[#e0e0e0] bg-[#f4f4f4] transition-all duration-300 ${
            isAnimating ? "opacity-0 scale-95" : "opacity-100 scale-100"
          }`}
        >
          <span class="font-mono text-xs tracking-widest text-[#0f62fe] uppercase block mb-4">
            Dampak Langsung
          </span>
          <p class="font-display text-3xl md:text-4xl text-[#161616]">
            {testimonials[activeIndex].metric}
          </p>
        </div>

        <!-- Navigation Dots -->
        <div class="flex gap-2 mt-8">
          {#each testimonials as _, idx}
            <button
              type="button"
              onclick={() => {
                isAnimating = true;
                setTimeout(() => {
                  activeIndex = idx;
                  isAnimating = false;
                }, 300);
              }}
              class={`h-2 transition-all duration-300 ${
                idx === activeIndex ? "w-8 bg-[#0f62fe]" : "w-2 bg-[#e0e0e0] hover:bg-[#8c8c8c]"
              }`}
              aria-label={`Testimoni ${idx + 1}`}
            ></button>
          {/each}
        </div>
      </div>
    </div>

    <!-- Partner Label -->
    <div class="mt-24 pt-12 border-t border-[#e0e0e0]">
      <p class="font-mono text-xs tracking-widest text-[#525252] uppercase mb-8 text-center">
        Dipercaya oleh berbagai pemilik bisnis ritel dan kuliner
      </p>
    </div>
  </div>

  <!-- Marquee -->
  <div class="w-full">
    <div class="flex gap-16 items-center marquee">
      {#each Array(2) as _, setIdx}
        <div class="flex gap-16 items-center shrink-0">
          {#each brandPartners as company}
            <span
              class="font-display text-xl md:text-2xl text-[#8c8c8c] whitespace-nowrap hover:text-[#161616] transition-colors duration-300"
            >
              {company}
            </span>
          {/each}
        </div>
      {/each}
    </div>
  </div>
</section>
