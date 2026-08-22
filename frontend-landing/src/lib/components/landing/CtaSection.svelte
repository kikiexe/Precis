<script lang="ts">
  import { onMount } from "svelte";
  import { ArrowRight } from "lucide-svelte";
  import AnimatedTetrahedron from "./AnimatedTetrahedron.svelte";

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

<section bind:this={sectionEl} class="relative py-24 lg:py-32 bg-white overflow-hidden border-t border-[#e0e0e0]">
  <div class="max-w-[1400px] mx-auto px-6 lg:px-12">
    <!-- Spotlight Box -->
    <div
      role="region"
      aria-label="Call to action box"
      class={`relative border border-[#161616] bg-white transition-all duration-1000 ${
        isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-8"
      }`}
      onmousemove={handleMouseMove}
    >
      <!-- Spotlight effect -->
      <div
        class="absolute inset-0 opacity-15 pointer-events-none transition-opacity duration-300"
        style={`background: radial-gradient(600px circle at ${mousePosition.x}% ${mousePosition.y}%, rgba(15, 98, 254, 0.2), transparent 40%);`}
      ></div>

      <div class="relative z-10 px-8 lg:px-16 py-16 lg:py-24">
        <div class="flex flex-col lg:flex-row items-center justify-between gap-12">
          <!-- Left content -->
          <div class="flex-1">
            <h2 class="text-4xl lg:text-7xl font-display tracking-tight mb-8 leading-[0.95] text-[#161616]">
              Siap merapikan
              <br />
              operasional bisnis Anda?
            </h2>

            <p class="text-xl text-[#525252] mb-12 leading-relaxed max-w-xl">
              Tinggalkan cara lama yang rentan kecurangan. Coba gratis 1 bulan penuh tanpa komitmen kartu kredit.
            </p>

            <div class="flex flex-col sm:flex-row items-start gap-4">
              <a
                href="https://app.precis.com/register"
                class="bg-[#0f62fe] hover:bg-[#0050e6] text-white px-8 h-14 text-base rounded-full inline-flex items-center justify-center font-medium group transition-colors"
              >
                Mulai Uji Coba 1 Bulan Gratis
                <ArrowRight class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" />
              </a>
              <a
                href="#features"
                class="h-14 px-8 text-base rounded-full border border-[#161616] text-[#161616] hover:bg-[#f4f4f4] inline-flex items-center justify-center font-medium transition-colors"
              >
                Pelajari Semua Fitur
              </a>
            </div>

            <p class="text-sm text-[#8c8c8c] mt-8 font-mono">
              Tanpa kartu kredit • Setup outlet dalam 5 menit
            </p>
          </div>

          <!-- Right animation -->
          <div class="hidden lg:flex items-center justify-center w-[460px] h-[460px] -mr-12">
            <AnimatedTetrahedron />
          </div>
        </div>
      </div>

      <!-- Decorative corners -->
      <div class="absolute top-0 right-0 w-32 h-32 border-b border-l border-[#e0e0e0] pointer-events-none"></div>
      <div class="absolute bottom-0 left-0 w-32 h-32 border-t border-r border-[#e0e0e0] pointer-events-none"></div>
    </div>
  </div>
</section>
