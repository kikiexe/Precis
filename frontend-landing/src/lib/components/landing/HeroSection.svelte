<script lang="ts">
  import { onMount } from 'svelte';
  import { ArrowRight } from 'lucide-svelte';
  import AnimatedSphere from './AnimatedSphere.svelte';

  const words = ['presensi', 'kasir POS', 'payroll', 'outlet'];
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

<section class="relative flex min-h-screen flex-col justify-center overflow-hidden bg-white">
  <!-- Animated sphere background - Brightened & Positioned -->
  <div
    class="pointer-events-none absolute top-1/2 -right-8 size-170 -translate-y-1/2 opacity-60 sm:-right-10 sm:size-195 lg:right-0 lg:size-237.5"
  >
    <AnimatedSphere />
  </div>

  <!-- Subtle grid lines -->
  <div class="pointer-events-none absolute inset-0 overflow-hidden opacity-20">
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

  <div class="relative z-10 mx-auto max-w-350 px-6 pt-32 pb-44 lg:px-12 lg:pt-40 lg:pb-52">
    <!-- Eyebrow -->
    <div
      class={`mb-8 transition-all duration-700 ${
        isVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'
      }`}
    >
      <span
        class="inline-flex items-center gap-2 font-mono text-xs text-[#525252] sm:gap-3 sm:text-sm"
      >
        <span class="h-px w-6 shrink-0 bg-[#0f62fe] sm:w-8"></span>
        Platform Operasional Bisnis
      </span>
    </div>

    <!-- Main headline -->
    <div class="mb-12">
      <h1
        class={`leading-0.9 font-display text-[clamp(3rem,12vw,10rem)] tracking-tight text-[#161616] transition-all duration-1000 ${
          isVisible ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'
        }`}
      >
        <span class="block">Satu platform</span>
        <span class="block">
          kelola
          <span class="relative inline-block">
            {#key wordIndex}
              <span class="inline-flex text-[#161616]">
                {#each words[wordIndex].split('') as char, i}
                  <span
                    class="animate-char-in inline-block"
                    style={`animation-delay: ${i * 45}ms;`}
                  >
                    {char === ' ' ? '\u00A0' : char}
                  </span>
                {/each}
              </span>
            {/key}
            <span class="absolute inset-x-0 -bottom-2 h-3 bg-[#0f62fe]/20"></span>
          </span>
        </span>
      </h1>
    </div>

    <!-- Description & CTAs -->
    <div class="grid items-end gap-12 lg:grid-cols-2 lg:gap-24">
      <p
        class={`max-w-xl text-xl leading-relaxed text-[#525252] transition-all delay-200 duration-700 lg:text-2xl ${
          isVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'
        }`}
      >
        Presensi selfie ber-watermark GPS anti-titip absen, kasir POS yang tetap jalan saat internet
        mati, dan kalkulasi payroll otomatis tanpa pusing rekap manual.
      </p>

      <!-- CTAs -->
      <div
        class={`flex flex-col items-start gap-4 transition-all delay-300 duration-700 sm:flex-row ${
          isVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'
        }`}
      >
        <a
          href="https://app.precis.com/register"
          class="group inline-flex h-14 items-center justify-center rounded-full bg-[#0f62fe] px-8 text-base font-medium text-white shadow-sm transition-colors hover:bg-[#0050e6]"
        >
          Mulai Uji Coba 1 Bulan
          <ArrowRight class="ml-2 size-4 transition-transform group-hover:translate-x-1" />
        </a>
        <a
          href="#features"
          class="inline-flex h-14 items-center justify-center rounded-full border border-[#e0e0e0] px-8 text-base font-medium text-[#161616] transition-colors hover:bg-[#f4f4f4]"
        >
          Pelajari Fitur
        </a>
      </div>
    </div>
  </div>

  <!-- Stats Marquee Ticker - Refined size & Positioned -->
  <div
    class={`absolute inset-x-0 bottom-4 transition-all delay-500 duration-700 sm:bottom-6 ${
      isVisible ? 'opacity-100' : 'opacity-0'
    }`}
  >
    <div class="marquee flex gap-16 whitespace-nowrap lg:gap-20">
      {#each Array(2) as _}
        <div class="flex items-center gap-16 lg:gap-20">
          {#each [{ value: '0 Detik', label: 'Kasir macet saat offline', company: 'DATABASE LOKAL TABLET' }, { value: '100%', label: 'Kalkulasi gaji otomatis', company: 'POTONG KASBON & TELAT' }, { value: '60 Hari', label: 'Arsip foto selfie tersimpan', company: 'AUDIT VISUAL HARIAN' }, { value: '~3 ms', label: 'Kecepatan response sistem', company: 'PERFORMA TINGGI' }] as stat}
            <div class="flex items-baseline gap-3 sm:gap-4">
              <span
                class="font-display text-3xl tracking-tight text-[#161616] sm:text-4xl lg:text-5xl"
                >{stat.value}</span
              >
              <span class="text-xs text-[#525252] sm:text-sm">
                {stat.label}
                <span
                  class="mt-0.5 block font-mono text-[10px] font-medium text-[#0f62fe] sm:text-xs"
                  >{stat.company}</span
                >
              </span>
            </div>
          {/each}
        </div>
      {/each}
    </div>
  </div>
</section>
