<script lang="ts">
  import { onMount } from "svelte";

  const metrics = [
    { value: 1420850, suffix: "+", prefix: "", label: "Transaksi kasir sukses terproses" },
    { value: 100, suffix: "%", prefix: "", label: "Akurasi slip gaji & potongan kasbon" },
    { value: 0, suffix: " Detik", prefix: "", label: "Kasir macet saat internet mati" },
    { value: 500, suffix: "+", prefix: "", label: "Outlet ritel, resto & toko terintegrasi" },
  ];

  let timeString = $state("");
  let isVisible = $state(false);
  let sectionEl: HTMLElement;

  onMount(() => {
    const updateTime = () => {
      timeString = new Date().toLocaleTimeString("id-ID");
    };
    updateTime();
    const interval = setInterval(updateTime, 1000);

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) isVisible = true;
      },
      { threshold: 0.1 }
    );
    if (sectionEl) observer.observe(sectionEl);

    return () => {
      clearInterval(interval);
      observer.disconnect();
    };
  });
</script>

<section
  id="metrics"
  bind:this={sectionEl}
  class="relative py-24 lg:py-32 bg-white border-y border-[#e0e0e0]"
>
  <div class="max-w-[1400px] mx-auto px-6 lg:px-12">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8 mb-16 lg:mb-24">
      <div>
        <span class="inline-flex items-center gap-3 text-sm font-mono text-[#525252] mb-6">
          <span class="w-8 h-px bg-[#0f62fe]"></span>
          Metrik Nyata
        </span>
        <h2
          class={`text-4xl lg:text-6xl font-display tracking-tight transition-all duration-700 text-[#161616] ${
            isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
          }`}
        >
          Performa bisnis yang
          <br />
          dapat Anda ukur.
        </h2>
      </div>
      <div class="flex items-center gap-4 font-mono text-sm text-[#525252]">
        <span class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-[#24a148] animate-pulse"></span>
          Live WIB
        </span>
        <span class="text-[#8c8c8c]">|</span>
        <span>{timeString}</span>
      </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-px bg-[#e0e0e0] border border-[#e0e0e0]">
      {#each metrics as metric, index}
        <div
          class={`bg-white p-8 lg:p-12 transition-all duration-700 ${
            isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-8"
          }`}
          style={`transition-delay: ${index * 100}ms;`}
        >
          <div class="text-6xl lg:text-7xl font-display tracking-tight text-[#161616]">
            {metric.prefix}{metric.value.toLocaleString("id-ID")}{metric.suffix}
          </div>
          <div class="mt-4 text-lg text-[#525252]">{metric.label}</div>
        </div>
      {/each}
    </div>
  </div>
</section>
