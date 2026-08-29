<script lang="ts">
  import { onMount } from 'svelte';

  const steps = [
    {
      number: 'I',
      title: 'Pasang Tablet di Meja Kasir Toko',
      description:
        'Cukup buka peramban di tablet toko Anda dan masukkan Token Aktivasi outlet. Kasir tidak perlu membuat akun pribadi dan langsung siap melayani pesanan.',
      lines: [
        'LOKASI: Outlet Sleman #01 (Aktif)',
        'DEVICE: Tablet Kasir Terkunci',
        'PRINTER: Bluetooth Thermal Ready',
        'STATUS: Offline Mode Siap Melayani',
      ],
    },
    {
      number: 'II',
      title: 'Staf Selfie Presensi Masuk Shift',
      description:
        'Karyawan membuka portal presensi di smartphone saat tiba di outlet. Sistem secara otomatis menyematkan koordinat toko dan jam WIB di atas foto.',
      lines: [
        'STAF: Rian Hidayat (Kasir)',
        'JAM MASUK: 07:18 WIB (Shift Pagi)',
        'GPS: Sesuai Radius Toko (45m)',
        'AUDIT: Watermark Waktu & GPS Terbakar',
      ],
    },
    {
      number: 'III',
      title: 'Tutup Kas Harian & Transfer Gaji',
      description:
        'Setiap malam kasir mencatat rekonsiliasi kas tunai. Di akhir bulan, Owner langsung mengunduh rekap gaji bersih yang sudah terpotong denda dan kasbon.',
      lines: [
        'REKAP KAS: Tunai & QRIS Seimbang',
        'PERIODE: Gaji Bulanan (12 Staf)',
        'POTONGAN: Denda Telat & Kasbon',
        'SLIP GAJI: Siap Transfer Bank',
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
  class="relative overflow-hidden bg-[#161616] py-16 text-white sm:py-24 lg:py-32"
>
  <!-- Diagonal lines pattern -->
  <div class="opacity-0.03 pointer-events-none absolute inset-0">
    <div
      class="absolute inset-0"
      style="background-image: repeating-linear-gradient(-45deg, transparent, transparent 40px, currentColor 40px, currentColor 41px);"
    ></div>
  </div>

  <div class="relative z-10 mx-auto max-w-350 px-4 sm:px-6 lg:px-12">
    <!-- Header -->
    <div class="mb-12 sm:mb-16 lg:mb-24">
      <span
        class="mb-4 inline-flex items-center gap-3 font-mono text-xs text-white/50 sm:mb-6 sm:text-sm"
      >
        <span class="h-px w-6 bg-[#0f62fe] sm:w-8"></span>
        Alur Praktis Toko
      </span>
      <h2
        class={`font-display text-3xl tracking-tight transition-all duration-700 sm:text-4xl lg:text-6xl ${
          isVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'
        }`}
      >
        Tiga langkah mudah.
        <br />
        <span class="text-white/50">Operasional bisnis otomatis rapi.</span>
      </h2>
    </div>

    <!-- Main content -->
    <div class="grid items-start gap-10 lg:grid-cols-2 lg:gap-24">
      <!-- Steps -->
      <div class="space-y-0">
        {#each steps as step, index}
          <button
            type="button"
            onclick={() => (activeStep = index)}
            class={`group w-full border-b border-white/10 py-6 text-left transition-all duration-500 sm:py-8 ${
              activeStep === index ? 'opacity-100' : 'opacity-40 hover:opacity-70'
            }`}
          >
            <div class="flex items-start gap-4 sm:gap-6">
              <span class="shrink-0 font-display text-2xl text-white/30 sm:text-3xl"
                >{step.number}</span
              >
              <div class="min-w-0 flex-1">
                <h3
                  class="mb-2 font-display text-xl transition-transform duration-300 group-hover:translate-x-1 sm:mb-3 sm:text-2xl sm:group-hover:translate-x-2 lg:text-3xl"
                >
                  {step.title}
                </h3>
                <p class="text-xs leading-relaxed text-white/60 sm:text-sm">
                  {step.description}
                </p>

                <!-- Progress indicator moving bar -->
                {#if activeStep === index}
                  <div class="mt-3 h-0.5 overflow-hidden bg-white/20 sm:mt-4">
                    {#key activeStep}
                      <div class="progress-bar h-full bg-[#0f62fe]"></div>
                    {/key}
                  </div>
                {/if}
              </div>
            </div>
          </button>
        {/each}
      </div>

      <!-- Monitor display window -->
      <div class="w-full self-start lg:sticky lg:top-32">
        <div class="overflow-hidden rounded-sm border border-white/10 bg-[#161616] shadow-xl">
          <!-- Window header -->
          <div
            class="flex items-center justify-between border-b border-white/10 px-4 py-3 sm:px-6 sm:py-4"
          >
            <div class="flex gap-1.5 sm:gap-2">
              <div class="size-2.5 rounded-full bg-white/20 sm:size-3"></div>
              <div class="size-2.5 rounded-full bg-white/20 sm:size-3"></div>
              <div class="size-2.5 rounded-full bg-white/20 sm:size-3"></div>
            </div>
            <span class="font-mono text-[11px] text-white/40 sm:text-xs"
              >monitor-operasional.live</span
            >
          </div>

          <!-- Monitor content -->
          <div class="min-h-45 p-4 font-mono text-xs sm:min-h-55 sm:p-6 sm:text-sm lg:p-8">
            <div class="space-y-2.5 text-white/70 sm:space-y-3">
              {#key activeStep}
                {#each steps[activeStep].lines as line, lineIndex}
                  <div
                    class="code-line-reveal flex items-center gap-2 overflow-x-hidden leading-relaxed sm:gap-3"
                    style={`animation-delay: ${lineIndex * 70}ms;`}
                  >
                    <span
                      class="inline-block w-4 shrink-0 text-[11px] text-white/20 select-none sm:w-6 sm:text-xs"
                      >{lineIndex + 1}</span
                    >
                    <span class="shrink-0 font-mono text-xs text-[#24a148] select-none sm:text-sm"
                      >➜</span
                    >
                    <span
                      class="inline-flex truncate text-[11px] text-white/90 sm:text-xs md:text-sm"
                    >
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
          <div
            class="flex items-center gap-2 border-t border-white/10 px-4 py-3 sm:gap-3 sm:px-6 sm:py-4"
          >
            <span class="size-2 shrink-0 animate-pulse rounded-full bg-[#24a148]"></span>
            <span class="font-mono text-[11px] text-white/40 sm:text-xs"
              >Status: Sistem Berjalan Normal</span
            >
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
