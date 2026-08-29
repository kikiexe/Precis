<script lang="ts">
  import { onMount } from 'svelte';
  import { ArrowRight } from 'lucide-svelte';
  import AnimatedTetrahedron from './AnimatedTetrahedron.svelte';

  let isVisible = $state(false);
  let sectionEl: HTMLElement;
  let mousePosition = $state({ x: 50, y: 50 });

  onMount(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) isVisible = true;
      },
      { threshold: 0.2 }
    );
    if (sectionEl) observer.observe(sectionEl);
    return () => observer.disconnect();
  });

  function handleMouseMove(e: MouseEvent) {
    const target = e.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();
    mousePosition = {
      x: ((e.clientX - rect.left) / rect.width) * 100,
      y: ((e.clientY - rect.top) / rect.height) * 100,
    };
  }
</script>

<section
  bind:this={sectionEl}
  class="relative overflow-hidden border-t border-[#e0e0e0] bg-white py-24 lg:py-32"
>
  <div class="mx-auto max-w-350 px-6 lg:px-12">
    <!-- Spotlight Box -->
    <div
      role="region"
      aria-label="Call to action box"
      class={`relative border border-[#161616] bg-white transition-all duration-1000 ${
        isVisible ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'
      }`}
      onmousemove={handleMouseMove}
    >
      <!-- Spotlight effect -->
      <div
        class="pointer-events-none absolute inset-0 opacity-15 transition-opacity duration-300"
        style={`background: radial-gradient(600px circle at ${mousePosition.x}% ${mousePosition.y}%, rgba(15, 98, 254, 0.2), transparent 40%);`}
      ></div>

      <div class="relative z-10 px-8 py-16 lg:px-16 lg:py-24">
        <div class="flex flex-col items-center justify-between gap-12 lg:flex-row">
          <!-- Left content -->
          <div class="flex-1">
            <h2
              class="leading-0.95 mb-8 font-display text-4xl tracking-tight text-[#161616] lg:text-7xl"
            >
              Siap merapikan
              <br />
              operasional bisnis Anda?
            </h2>

            <p class="mb-12 max-w-xl text-xl leading-relaxed text-[#525252]">
              Tinggalkan cara lama yang rentan kecurangan. Coba gratis 1 bulan penuh tanpa komitmen
              kartu kredit.
            </p>

            <div class="flex flex-col items-start gap-4 sm:flex-row">
              <a
                href="https://app.precis.com/register"
                class="group inline-flex h-14 items-center justify-center rounded-full bg-[#0f62fe] px-8 text-base font-medium text-white transition-colors hover:bg-[#0050e6]"
              >
                Mulai Uji Coba 1 Bulan Gratis
                <ArrowRight class="ml-2 size-4 transition-transform group-hover:translate-x-1" />
              </a>
              <a
                href="#features"
                class="inline-flex h-14 items-center justify-center rounded-full border border-[#161616] px-8 text-base font-medium text-[#161616] transition-colors hover:bg-[#f4f4f4]"
              >
                Pelajari Semua Fitur
              </a>
            </div>

            <p class="mt-8 font-mono text-sm text-[#8c8c8c]">
              Tanpa kartu kredit • Setup outlet dalam 5 menit
            </p>
          </div>

          <!-- Right animation -->
          <div
            class="relative -mr-12 hidden size-[460px] items-center justify-center lg:flex"
          >
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
              <div class="flex size-[740px] shrink-0 items-center justify-center">
                <AnimatedTetrahedron />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Decorative corners -->
      <div
        class="pointer-events-none absolute top-0 right-0 size-32 border-b border-l border-[#e0e0e0]"
      ></div>
      <div
        class="pointer-events-none absolute bottom-0 left-0 size-32 border-t border-r border-[#e0e0e0]"
      ></div>
    </div>
  </div>
</section>
