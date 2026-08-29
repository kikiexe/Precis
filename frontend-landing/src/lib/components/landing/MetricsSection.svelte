<script lang="ts">
  import { onMount } from 'svelte';

  const metrics = [
    { value: 1420850, suffix: '+', prefix: '', label: 'Transaksi kasir sukses terproses' },
    { value: 100, suffix: '%', prefix: '', label: 'Akurasi slip gaji & potongan kasbon' },
    { value: 0, suffix: ' Detik', prefix: '', label: 'Kasir macet saat internet mati' },
    { value: 500, suffix: '+', prefix: '', label: 'Outlet ritel, resto & toko terintegrasi' },
  ];

  let timeString = $state('');
  let isVisible = $state(false);
  let sectionEl: HTMLElement;

  onMount(() => {
    const updateTime = () => {
      timeString = new Date().toLocaleTimeString('id-ID');
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
  class="relative border-y border-[#e0e0e0] bg-white py-24 lg:py-32"
>
  <div class="mx-auto max-w-350 px-6 lg:px-12">
    <!-- Header -->
    <div class="mb-16 flex flex-col gap-8 lg:mb-24 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <span class="mb-6 inline-flex items-center gap-3 font-mono text-sm text-[#525252]">
          <span class="h-px w-8 bg-[#0f62fe]"></span>
          Metrik Nyata
        </span>
        <h2
          class={`font-display text-4xl tracking-tight text-[#161616] transition-all duration-700 lg:text-6xl ${
            isVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'
          }`}
        >
          Performa bisnis yang
          <br />
          dapat Anda ukur.
        </h2>
      </div>
      <div class="flex items-center gap-4 font-mono text-sm text-[#525252]">
        <span class="flex items-center gap-2">
          <span class="size-2 animate-pulse rounded-full bg-[#24a148]"></span>
          Live WIB
        </span>
        <span class="text-[#8c8c8c]">|</span>
        <span>{timeString}</span>
      </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 gap-px border border-[#e0e0e0] bg-[#e0e0e0] md:grid-cols-2">
      {#each metrics as metric, index}
        <div
          class={`bg-white p-8 transition-all duration-700 lg:p-12 ${
            isVisible ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'
          }`}
          style={`transition-delay: ${index * 100}ms;`}
        >
          <div class="font-display text-6xl tracking-tight text-[#161616] lg:text-7xl">
            {metric.prefix}{metric.value.toLocaleString('id-ID')}{metric.suffix}
          </div>
          <div class="mt-4 text-lg text-[#525252]">{metric.label}</div>
        </div>
      {/each}
    </div>
  </div>
</section>
