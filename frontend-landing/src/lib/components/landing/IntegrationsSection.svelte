<script lang="ts">
  import { onMount } from 'svelte';

  const integrations = [
    { name: 'Printer Bluetooth ESC/POS', category: 'Hardware Struk 58/80mm' },
    { name: 'QRIS & EDC Kasir', category: 'Metode Pembayaran' },
    { name: 'Bank BCA Payroll', category: 'Ekspor Format Transfer' },
    { name: 'Mandiri MCM', category: 'Ekspor Format Transfer' },
    { name: 'Bank BRI Cash Management', category: 'Ekspor Format Transfer' },
    { name: 'Tablet Android & iPad', category: 'Layar Sentuh Kasir' },
    { name: 'Thermal Printer USB', category: 'Hardware Bar Toko' },
    { name: 'Ekspor Excel & CSV', category: 'Laporan Finansial' },
    { name: 'Cloudflare Edge', category: 'Keamanan Foto Presensi' },
    { name: 'WhatsApp Gateway', category: 'Notifikasi Karyawan' },
  ];

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
    return () => observer.disconnect();
  });
</script>

<section
  id="integrations"
  bind:this={sectionEl}
  class="relative overflow-hidden border-t border-[#e0e0e0] bg-[#f4f4f4] py-24 lg:py-32"
>
  <div class="mx-auto max-w-350 px-6 lg:px-12">
    <!-- Header -->
    <div
      class={`mx-auto mb-16 max-w-3xl text-center transition-all duration-700 lg:mb-24 ${
        isVisible ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'
      }`}
    >
      <span class="mb-6 inline-flex items-center gap-3 font-mono text-sm text-[#525252]">
        <span class="h-px w-8 bg-[#0f62fe]"></span>
        Kompatibilitas
        <span class="h-px w-8 bg-[#0f62fe]"></span>
      </span>
      <h2 class="mb-6 font-display text-4xl tracking-tight text-[#161616] lg:text-6xl">
        Kompatibel dengan hardware
        <br />
        yang sudah Anda miliki.
      </h2>
      <p class="text-xl text-[#525252]">
        Langsung terhubung dengan printer thermal Bluetooth, tablet toko, dan format payroll bank
        nasional.
      </p>
    </div>
  </div>

  <!-- Full-width marquees -->
  <div class="mb-6 w-full">
    <div class="marquee flex gap-6">
      {#each Array(2) as _, _setIndex}
        <div class="flex shrink-0 gap-6">
          {#each integrations as item}
            <div
              class="group shrink-0 border border-[#e0e0e0] bg-white px-8 py-6 transition-all duration-300 hover:border-[#0f62fe]"
            >
              <div
                class="text-lg font-medium text-[#161616] transition-transform group-hover:translate-x-1"
              >
                {item.name}
              </div>
              <div class="text-sm text-[#525252]">{item.category}</div>
            </div>
          {/each}
        </div>
      {/each}
    </div>
  </div>

  <div class="w-full">
    <div class="marquee-reverse flex gap-6">
      {#each Array(2) as _, _setIndex}
        <div class="flex shrink-0 gap-6">
          {#each [...integrations].reverse() as item}
            <div
              class="group shrink-0 border border-[#e0e0e0] bg-white px-8 py-6 transition-all duration-300 hover:border-[#0f62fe]"
            >
              <div
                class="text-lg font-medium text-[#161616] transition-transform group-hover:translate-x-1"
              >
                {item.name}
              </div>
              <div class="text-sm text-[#525252]">{item.category}</div>
            </div>
          {/each}
        </div>
      {/each}
    </div>
  </div>
</section>
