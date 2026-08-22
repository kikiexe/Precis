<script lang="ts">
  import { onMount } from "svelte";

  const integrations = [
    { name: "Printer Bluetooth ESC/POS", category: "Hardware Struk 58/80mm" },
    { name: "QRIS & EDC Kasir", category: "Metode Pembayaran" },
    { name: "Bank BCA Payroll", category: "Ekspor Format Transfer" },
    { name: "Mandiri MCM", category: "Ekspor Format Transfer" },
    { name: "Bank BRI Cash Management", category: "Ekspor Format Transfer" },
    { name: "Tablet Android & iPad", category: "Layar Sentuh Kasir" },
    { name: "Thermal Printer USB", category: "Hardware Bar Toko" },
    { name: "Ekspor Excel & CSV", category: "Laporan Finansial" },
    { name: "Cloudflare Edge", category: "Keamanan Foto Presensi" },
    { name: "WhatsApp Gateway", category: "Notifikasi Karyawan" },
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

<section id="integrations" bind:this={sectionEl} class="relative py-24 lg:py-32 bg-[#f4f4f4] overflow-hidden border-t border-[#e0e0e0]">
  <div class="max-w-[1400px] mx-auto px-6 lg:px-12">
    <!-- Header -->
    <div
      class={`text-center max-w-3xl mx-auto mb-16 lg:mb-24 transition-all duration-700 ${
        isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-8"
      }`}
    >
      <span class="inline-flex items-center gap-3 text-sm font-mono text-[#525252] mb-6">
        <span class="w-8 h-px bg-[#0f62fe]"></span>
        Kompatibilitas
        <span class="w-8 h-px bg-[#0f62fe]"></span>
      </span>
      <h2 class="text-4xl lg:text-6xl font-display tracking-tight mb-6 text-[#161616]">
        Kompatibel dengan hardware
        <br />
        yang sudah Anda miliki.
      </h2>
      <p class="text-xl text-[#525252]">
        Langsung terhubung dengan printer thermal Bluetooth, tablet toko, dan format payroll bank nasional.
      </p>
    </div>
  </div>

  <!-- Full-width marquees -->
  <div class="w-full mb-6">
    <div class="flex gap-6 marquee">
      {#each Array(2) as _, setIndex}
        <div class="flex gap-6 shrink-0">
          {#each integrations as item}
            <div class="shrink-0 px-8 py-6 bg-white border border-[#e0e0e0] hover:border-[#0f62fe] transition-all duration-300 group">
              <div class="text-lg font-medium text-[#161616] group-hover:translate-x-1 transition-transform">
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
    <div class="flex gap-6 marquee-reverse">
      {#each Array(2) as _, setIndex}
        <div class="flex gap-6 shrink-0">
          {#each [...integrations].reverse() as item}
            <div class="shrink-0 px-8 py-6 bg-white border border-[#e0e0e0] hover:border-[#0f62fe] transition-all duration-300 group">
              <div class="text-lg font-medium text-[#161616] group-hover:translate-x-1 transition-transform">
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
