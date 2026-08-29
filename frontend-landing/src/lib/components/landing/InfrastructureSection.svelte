<script lang="ts">
  import { onMount } from 'svelte';

  const locations = [
    { city: 'Jakarta Selatan', region: 'Hub Pusat Outlet #01', latency: '3ms' },
    { city: 'Sleman Yogyakarta', region: 'Cabang Toko #02', latency: '4ms' },
    { city: 'Bandung Dago', region: 'Cabang Toko #03', latency: '5ms' },
    { city: 'Surabaya Barat', region: 'Cabang Toko #04', latency: '6ms' },
    { city: 'Denpasar Bali', region: 'Cabang Toko #05', latency: '8ms' },
    { city: 'Medan Petisah', region: 'Cabang Toko #06', latency: '9ms' },
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
  class="relative overflow-hidden border-t border-[#e0e0e0] bg-white py-24 lg:py-32"
>
  <div class="mx-auto max-w-350 px-6 lg:px-12">
    <div class="grid items-center gap-16 lg:grid-cols-2 lg:gap-24">
      <!-- Left: Content -->
      <div
        class={`transition-all duration-700 ${
          isVisible ? 'translate-x-0 opacity-100' : '-translate-x-8 opacity-0'
        }`}
      >
        <span class="mb-6 inline-flex items-center gap-3 font-mono text-sm text-[#525252]">
          <span class="h-px w-8 bg-[#0f62fe]"></span>
          Keandalan Multi-Outlet
        </span>
        <h2 class="mb-8 font-display text-4xl tracking-tight text-[#161616] lg:text-6xl">
          Real-time di
          <br />
          seluruh cabang.
        </h2>
        <p class="mb-12 text-xl leading-relaxed text-[#525252]">
          Pantau omzet kasir, stok menu, dan kehadiran karyawan di puluhan cabang secara bersamaan
          dari satu aplikasi tanpa delay.
        </p>

        <!-- Stats - 3 columns side-by-side horizontal on all devices -->
        <div class="grid grid-cols-3 gap-4 lg:gap-8">
          <div>
            <div class="mb-2 font-display text-3xl text-[#161616] sm:text-4xl lg:text-5xl">
              0 Detik
            </div>
            <div class="text-xs text-[#525252] sm:text-sm">Kasir macet saat offline</div>
          </div>
          <div>
            <div class="mb-2 font-display text-3xl text-[#161616] sm:text-4xl lg:text-5xl">
              99.9%
            </div>
            <div class="text-xs text-[#525252] sm:text-sm">Uptime server</div>
          </div>
          <div>
            <div class="mb-2 font-display text-3xl text-[#161616] sm:text-4xl lg:text-5xl">
              &lt;5ms
            </div>
            <div class="text-xs text-[#525252] sm:text-sm">Kecepatan sinkronisasi</div>
          </div>
        </div>
      </div>

      <!-- Right: Location list -->
      <div
        class={`transition-all delay-200 duration-700 ${
          isVisible ? 'translate-x-0 opacity-100' : 'translate-x-8 opacity-0'
        }`}
      >
        <div class="border border-[#e0e0e0] bg-[#f4f4f4]">
          <!-- Header -->
          <div
            class="flex items-center justify-between border-b border-[#e0e0e0] bg-white px-6 py-4"
          >
            <span class="font-mono text-sm text-[#525252]">Jaringan Cabang Toko</span>
            <span class="flex items-center gap-2 font-mono text-xs text-[#24a148]">
              <span class="size-2 animate-pulse rounded-full bg-[#24a148]"></span>
              Semua Cabang Aktif
            </span>
          </div>

          <!-- Locations -->
          <div>
            {#each locations as location, index}
              <div
                class={`flex items-center justify-between border-b border-[#e0e0e0] px-6 py-5 transition-all duration-300 last:border-b-0 ${
                  activeLocation === index ? 'bg-white' : ''
                }`}
              >
                <div class="flex items-center gap-4">
                  <span
                    class={`size-2 rounded-full transition-colors duration-300 ${
                      activeLocation === index ? 'bg-[#0f62fe]' : 'bg-[#8c8c8c]'
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
