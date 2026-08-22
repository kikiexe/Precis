<script lang="ts">
  import { onMount } from "svelte";

  const steps = [
    {
      number: "I",
      title: "Pasang Tablet di Meja Kasir Toko",
      description: "Cukup buka peramban di tablet toko Anda dan masukkan PIN aktivasi outlet. Kasir tidak perlu membuat akun pribadi dan langsung siap melayani pesanan.",
      lines: [
        "LOKASI: Outlet Sleman #01 (Aktif)",
        "DEVICE: Tablet Kasir Terkunci",
        "PRINTER: Bluetooth Thermal Ready",
        "STATUS: Offline Mode Siap Melayani"
      ],
    },
    {
      number: "II",
      title: "Staf Selfie Presensi Masuk Shift",
      description: "Karyawan membuka portal presensi di smartphone saat tiba di outlet. Sistem secara otomatis menyematkan koordinat toko dan jam WIB di atas foto.",
      lines: [
        "STAF: Rian Hidayat (Kasir)",
        "JAM MASUK: 07:18 WIB (Shift Pagi)",
        "GPS: Sesuai Radius Toko (45m)",
        "AUDIT: Watermark Waktu & GPS Terbakar"
      ],
    },
    {
      number: "III",
      title: "Tutup Kas Harian & Transfer Gaji",
      description: "Setiap malam kasir mencatat rekonsiliasi kas tunai. Di akhir bulan, Owner langsung mengunduh rekap gaji bersih yang sudah terpotong denda dan kasbon.",
      lines: [
        "REKAP KAS: Tunai & QRIS Seimbang",
        "PERIODE: Gaji Bulanan (12 Staf)",
        "POTONGAN: Denda Telat & Kasbon",
        "SLIP GAJI: Siap Transfer Bank"
      ],
    },
  ];

  let activeStep = $state(0);
  let isVisible = $state(false);
  let sectionEl: HTMLElement;

  onMount(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) isVisible = true;
      },
      { threshold: 0.1 }
    );
    if (sectionEl) observer.observe(sectionEl);

    const interval = setInterval(() => {
      activeStep = (activeStep + 1) % steps.length;
    }, 5000);

    return () => {
      observer.disconnect();
      clearInterval(interval);
    };
  });
</script>

<section
  id="how-it-works"
  bind:this={sectionEl}
  class="relative py-16 sm:py-24 lg:py-32 bg-[#161616] text-white overflow-hidden"
>
  <!-- Diagonal lines pattern -->
  <div class="absolute inset-0 opacity-[0.03] pointer-events-none">
    <div
      class="absolute inset-0"
      style="background-image: repeating-linear-gradient(-45deg, transparent, transparent 40px, currentColor 40px, currentColor 41px);"
    ></div>
  </div>

  <div class="relative z-10 max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-12">
    <!-- Header -->
    <div class="mb-12 sm:mb-16 lg:mb-24">
      <span class="inline-flex items-center gap-3 text-xs sm:text-sm font-mono text-white/50 mb-4 sm:mb-6">
        <span class="w-6 sm:w-8 h-px bg-[#0f62fe]"></span>
        Alur Praktis Toko
      </span>
      <h2
        class={`text-3xl sm:text-4xl lg:text-6xl font-display tracking-tight transition-all duration-700 ${
          isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
        }`}
      >
        Tiga langkah mudah.
        <br />
        <span class="text-white/50">Operasional bisnis otomatis rapi.</span>
      </h2>
    </div>

    <!-- Main content -->
    <div class="grid lg:grid-cols-2 gap-10 lg:gap-24 items-start">
      <!-- Steps -->
      <div class="space-y-0">
        {#each steps as step, index}
          <button
            type="button"
            onclick={() => (activeStep = index)}
            class={`w-full text-left py-6 sm:py-8 border-b border-white/10 transition-all duration-500 group ${
              activeStep === index ? "opacity-100" : "opacity-40 hover:opacity-70"
            }`}
          >
            <div class="flex items-start gap-4 sm:gap-6">
              <span class="font-display text-2xl sm:text-3xl text-white/30 shrink-0">{step.number}</span>
              <div class="flex-1 min-w-0">
                <h3 class="text-xl sm:text-2xl lg:text-3xl font-display mb-2 sm:mb-3 group-hover:translate-x-1 sm:group-hover:translate-x-2 transition-transform duration-300">
                  {step.title}
                </h3>
                <p class="text-xs sm:text-sm text-white/60 leading-relaxed">
                  {step.description}
                </p>

                <!-- Progress indicator moving bar -->
                {#if activeStep === index}
                  <div class="mt-3 sm:mt-4 h-0.5 bg-white/20 overflow-hidden">
                    {#key activeStep}
                      <div class="h-full bg-[#0f62fe] progress-bar"></div>
                    {/key}
                  </div>
                {/if}
              </div>
            </div>
          </button>
        {/each}
      </div>

      <!-- Monitor display window -->
      <div class="lg:sticky lg:top-32 self-start w-full">
        <div class="border border-white/10 overflow-hidden bg-[#161616] rounded-sm shadow-xl">
          <!-- Window header -->
          <div class="px-4 py-3 sm:px-6 sm:py-4 border-b border-white/10 flex items-center justify-between">
            <div class="flex gap-1.5 sm:gap-2">
              <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-white/20"></div>
              <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-white/20"></div>
              <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-white/20"></div>
            </div>
            <span class="text-[11px] sm:text-xs font-mono text-white/40">monitor-operasional.live</span>
          </div>

          <!-- Monitor content -->
          <div class="p-4 sm:p-6 lg:p-8 font-mono text-xs sm:text-sm min-h-[180px] sm:min-h-[220px]">
            <div class="text-white/70 space-y-2.5 sm:space-y-3">
              {#key activeStep}
                {#each steps[activeStep].lines as line, lineIndex}
                  <div
                    class="leading-relaxed code-line-reveal flex items-center gap-2 sm:gap-3 overflow-x-hidden"
                    style={`animation-delay: ${lineIndex * 70}ms;`}
                  >
                    <span class="text-white/20 select-none w-4 sm:w-6 inline-block shrink-0 text-[11px] sm:text-xs">{lineIndex + 1}</span>
                    <span class="text-[#24a148] font-mono select-none shrink-0 text-xs sm:text-sm">➜</span>
                    <span class="inline-flex text-white/90 text-[11px] sm:text-xs md:text-sm truncate">
                      {#each line.split('') as char, charIndex}
                        <span
                          class="code-char-reveal"
                          style={`animation-delay: ${lineIndex * 70 + charIndex * 12}ms;`}
                        >
                          {char === ' ' ? '\u00A0' : char}
                        </span>
                      {/each}
                    </span>
                  </div>
                {/each}
              {/key}
            </div>
          </div>

          <!-- Status bar -->
          <div class="px-4 py-3 sm:px-6 sm:py-4 border-t border-white/10 flex items-center gap-2 sm:gap-3">
            <span class="w-2 h-2 rounded-full bg-[#24a148] animate-pulse shrink-0"></span>
            <span class="text-[11px] sm:text-xs font-mono text-white/40">Status: Sistem Berjalan Normal</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
  .progress-bar {
    width: 0%;
    animation: progressAnim 5s linear forwards;
  }

  @keyframes progressAnim {
    from {
      width: 0%;
    }
    to {
      width: 100%;
    }
  }

  .code-line-reveal {
    opacity: 0;
    transform: translateX(-8px);
    animation: lineReveal 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
  }

  @keyframes lineReveal {
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  .code-char-reveal {
    opacity: 0;
    filter: blur(8px);
    animation: charReveal 0.3s cubic-bezier(0.22, 1, 0.36, 1) forwards;
  }

  @keyframes charReveal {
    to {
      opacity: 1;
      filter: blur(0);
    }
  }
</style>
