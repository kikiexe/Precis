<script lang="ts">
  import { onMount } from "svelte";
  import { ArrowRight } from "lucide-svelte";
  import AnimatedSphere from "./AnimatedSphere.svelte";

  const words = ["presensi", "kasir POS", "payroll", "outlet"];
  let isVisible = $state(false);
  let wordIndex = $state(0);

  onMount(() => {
    isVisible = true;
    const interval = setInterval(() => {
      wordIndex = (wordIndex + 1) % words.length;
    }, 2500);
    return () => clearInterval(interval);
  });
</script>

<section class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-white">
  <!-- Animated sphere background - Brightened & Positioned -->
  <div
    class="absolute -right-8 sm:-right-10 lg:right-0 top-1/2 -translate-y-1/2 w-[680px] h-[680px] sm:w-[780px] sm:h-[780px] lg:w-[950px] lg:h-[950px] opacity-60 pointer-events-none"
  >
    <AnimatedSphere />
  </div>

  <!-- Subtle grid lines -->
  <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-20">
    {#each Array(8) as _, i}
      <div
        class="absolute h-px bg-[#161616]/15"
        style={`top: ${12.5 * (i + 1)}%; left: 0; right: 0;`}
      ></div>
    {/each}
    {#each Array(12) as _, i}
      <div
        class="absolute w-px bg-[#161616]/15"
        style={`left: ${8.33 * (i + 1)}%; top: 0; bottom: 0;`}
      ></div>
    {/each}
  </div>

  <div class="relative z-10 max-w-[1400px] mx-auto px-6 lg:px-12 pt-32 pb-44 lg:pt-40 lg:pb-52">
    <!-- Eyebrow -->
    <div
      class={`mb-8 transition-all duration-700 ${
        isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
      }`}
    >
      <span class="inline-flex items-center gap-2 sm:gap-3 text-xs sm:text-sm font-mono text-[#525252]">
        <span class="w-6 sm:w-8 h-px bg-[#0f62fe] shrink-0"></span>
        Platform Operasional Bisnis
      </span>
    </div>

    <!-- Main headline -->
    <div class="mb-12">
      <h1
        class={`text-[clamp(3rem,12vw,10rem)] font-display leading-[0.9] tracking-tight transition-all duration-1000 text-[#161616] ${
          isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-8"
        }`}
      >
        <span class="block">Satu platform</span>
        <span class="block">
          kelola{" "}
          <span class="relative inline-block">
            {#key wordIndex}
              <span class="inline-flex text-[#161616]">
                {#each words[wordIndex].split("") as char, i}
                  <span
                    class="inline-block animate-char-in"
                    style={`animation-delay: ${i * 45}ms;`}
                  >
                    {char === " " ? "\u00A0" : char}
                  </span>
                {/each}
              </span>
            {/key}
            <span class="absolute -bottom-2 left-0 right-0 h-3 bg-[#0f62fe]/20"></span>
          </span>
        </span>
      </h1>
    </div>

    <!-- Description & CTAs -->
    <div class="grid lg:grid-cols-2 gap-12 lg:gap-24 items-end">
      <p
        class={`text-xl lg:text-2xl text-[#525252] leading-relaxed max-w-xl transition-all duration-700 delay-200 ${
          isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
        }`}
      >
        Presensi selfie ber-watermark GPS anti-titip absen, kasir POS layar sentuh yang tetap jalan saat internet mati, dan kalkulasi payroll otomatis tanpa pusing rekap manual.
      </p>

      <!-- CTAs -->
      <div
        class={`flex flex-col sm:flex-row items-start gap-4 transition-all duration-700 delay-300 ${
          isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
        }`}
      >
        <a
          href="https://app.precis.com/register"
          class="bg-[#0f62fe] hover:bg-[#0050e6] text-white px-8 h-14 text-base rounded-full inline-flex items-center justify-center font-medium group transition-colors shadow-sm"
        >
          Mulai Uji Coba 1 Bulan
          <ArrowRight class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" />
        </a>
        <a
          href="#features"
          class="h-14 px-8 text-base rounded-full border border-[#e0e0e0] text-[#161616] hover:bg-[#f4f4f4] inline-flex items-center justify-center font-medium transition-colors"
        >
          Pelajari Fitur
        </a>
      </div>
    </div>
  </div>

  <!-- Stats Marquee Ticker - Refined size & Positioned -->
  <div
    class={`absolute bottom-4 sm:bottom-6 left-0 right-0 transition-all duration-700 delay-500 ${
      isVisible ? "opacity-100" : "opacity-0"
    }`}
  >
    <div class="flex gap-16 lg:gap-20 marquee whitespace-nowrap">
      {#each Array(2) as _}
        <div class="flex gap-16 lg:gap-20 items-center">
          {#each [
            { value: "0 Detik", label: "Kasir macet saat offline", company: "DATABASE LOKAL TABLET" },
            { value: "100%", label: "Kalkulasi gaji otomatis", company: "POTONG KASBON & TELAT" },
            { value: "60 Hari", label: "Arsip foto selfie tersimpan", company: "AUDIT VISUAL HARIAN" },
            { value: "~3 ms", label: "Kecepatan response sistem", company: "PERFORMA TINGGI" },
          ] as stat}
            <div class="flex items-baseline gap-3 sm:gap-4">
              <span class="text-3xl sm:text-4xl lg:text-5xl font-display text-[#161616] tracking-tight">{stat.value}</span>
              <span class="text-xs sm:text-sm text-[#525252]">
                {stat.label}
                <span class="block font-mono text-[10px] sm:text-xs text-[#0f62fe] font-medium mt-0.5">{stat.company}</span>
              </span>
            </div>
          {/each}
        </div>
      {/each}
    </div>
  </div>
</section>
