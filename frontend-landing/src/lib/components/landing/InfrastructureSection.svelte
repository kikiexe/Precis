<script lang="ts">
  import { onMount } from "svelte";

  const locations = [
    { city: "Jakarta Selatan", region: "Hub Pusat Outlet #01", latency: "3ms" },
    { city: "Sleman Yogyakarta", region: "Cabang Toko #02", latency: "4ms" },
    { city: "Bandung Dago", region: "Cabang Toko #03", latency: "5ms" },
    { city: "Surabaya Barat", region: "Cabang Toko #04", latency: "6ms" },
    { city: "Denpasar Bali", region: "Cabang Toko #05", latency: "8ms" },
    { city: "Medan Petisah", region: "Cabang Toko #06", latency: "9ms" },
  ];

  let isVisible = $state(false);
  let activeLocation = $state(0);
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
      activeLocation = (activeLocation + 1) % locations.length;
    }, 2000);

    return () => {
      observer.disconnect();
      clearInterval(interval);
    };
  });
</script>

<section
  id="infrastructure"
  bind:this={sectionEl}
  class="relative py-24 lg:py-32 bg-white border-t border-[#e0e0e0] overflow-hidden"
>
  <div class="max-w-[1400px] mx-auto px-6 lg:px-12">
    <div class="grid lg:grid-cols-2 gap-16 lg:gap-24 items-center">
      <!-- Left: Content -->
      <div
        class={`transition-all duration-700 ${
          isVisible ? "opacity-100 translate-x-0" : "opacity-0 -translate-x-8"
        }`}
      >
        <span class="inline-flex items-center gap-3 text-sm font-mono text-[#525252] mb-6">
          <span class="w-8 h-px bg-[#0f62fe]"></span>
          Keandalan Multi-Outlet
        </span>
        <h2 class="text-4xl lg:text-6xl font-display tracking-tight mb-8 text-[#161616]">
          Real-time di
          <br />
          seluruh cabang.
        </h2>
        <p class="text-xl text-[#525252] leading-relaxed mb-12">
          Pantau omzet kasir, stok menu, dan kehadiran karyawan di puluhan cabang secara bersamaan dari satu aplikasi tanpa delay.
        </p>

        <!-- Stats - 3 columns side-by-side horizontal on all devices -->
        <div class="grid grid-cols-3 gap-4 lg:gap-8">
          <div>
            <div class="text-3xl sm:text-4xl lg:text-5xl font-display mb-2 text-[#161616]">0 Detik</div>
            <div class="text-xs sm:text-sm text-[#525252]">Kasir macet saat offline</div>
          </div>
          <div>
            <div class="text-3xl sm:text-4xl lg:text-5xl font-display mb-2 text-[#161616]">99.9%</div>
            <div class="text-xs sm:text-sm text-[#525252]">Uptime server</div>
          </div>
          <div>
            <div class="text-3xl sm:text-4xl lg:text-5xl font-display mb-2 text-[#161616]">&lt;5ms</div>
            <div class="text-xs sm:text-sm text-[#525252]">Kecepatan sinkronisasi</div>
          </div>
        </div>
      </div>

      <!-- Right: Location list -->
      <div
        class={`transition-all duration-700 delay-200 ${
          isVisible ? "opacity-100 translate-x-0" : "opacity-0 translate-x-8"
        }`}
      >
        <div class="border border-[#e0e0e0] bg-[#f4f4f4]">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-[#e0e0e0] flex items-center justify-between bg-white">
            <span class="text-sm font-mono text-[#525252]">Jaringan Cabang Toko</span>
            <span class="flex items-center gap-2 text-xs font-mono text-[#24a148]">
              <span class="w-2 h-2 rounded-full bg-[#24a148] animate-pulse"></span>
              Semua Cabang Aktif
            </span>
          </div>

          <!-- Locations -->
          <div>
            {#each locations as location, index}
              <div
                class={`px-6 py-5 border-b border-[#e0e0e0] last:border-b-0 flex items-center justify-between transition-all duration-300 ${
                  activeLocation === index ? "bg-white" : ""
                }`}
              >
                <div class="flex items-center gap-4">
                  <span
                    class={`w-2 h-2 rounded-full transition-colors duration-300 ${
                      activeLocation === index ? "bg-[#0f62fe]" : "bg-[#8c8c8c]"
                    }`}
                  ></span>
                  <div>
                    <div class="font-medium text-[#161616]">{location.city}</div>
                    <div class="text-sm text-[#525252]">{location.region}</div>
                  </div>
                </div>
                <span class="font-mono text-sm text-[#0f62fe]">{location.latency}</span>
              </div>
            {/each}
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
