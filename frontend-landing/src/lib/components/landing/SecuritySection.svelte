<script lang="ts">
  import { onMount } from "svelte";
  import { Shield, Lock, Eye, FileCheck } from "lucide-svelte";

  const securityFeatures = [
    {
      icon: Lock,
      title: "Penguncian Tablet Kasir",
      description: "Setiap perangkat tablet kasir diikat dengan token otorisasi unik per cabang toko.",
    },
    {
      icon: Shield,
      title: "Isolasi Data Multi-Tenant",
      description: "Data penjualan, laba, dan gaji cabang Anda terisolasi aman di level database.",
    },
    {
      icon: Eye,
      title: "Verifikasi Geofence GPS",
      description: "Foto presensi diverifikasi otomatis dengan radius toleransi titik koordinat toko.",
    },
    {
      icon: FileCheck,
      title: "Audit Trail Finansial Lengkap",
      description: "Seluruh pencatatan kas, diskon kasir, dan penyesuaian gaji terekam permanen.",
    },
  ];

  const badges = ["Device Lock", "Multi-Tenant Safe", "GPS Geofenced", "Audit Trailed", "Cloudflare Encrypted"];

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

<section id="security" bind:this={sectionEl} class="relative py-24 lg:py-32 bg-white overflow-hidden border-t border-[#e0e0e0]">
  <div class="max-w-[1400px] mx-auto px-6 lg:px-12">
    <div class="grid lg:grid-cols-2 gap-16 lg:gap-24">
      <!-- Left: Content -->
      <div
        class={`transition-all duration-700 ${
          isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-8"
        }`}
      >
        <span class="inline-flex items-center gap-3 text-sm font-mono text-[#525252] mb-6">
          <span class="w-8 h-px bg-[#0f62fe]"></span>
          Keamanan &amp; Integritas
        </span>
        <h2 class="text-4xl lg:text-6xl font-display tracking-tight mb-8 text-[#161616]">
          Keamanan data adalah
          <br />
          prioritas mutlak.
        </h2>
        <p class="text-xl text-[#525252] leading-relaxed mb-12">
          Perlindungan data finansial dan integritas kehadiran dibangun di setiap lapisan sistem, menjamin bisnis Anda beroperasi dengan tenang.
        </p>

        <!-- Badges -->
        <div class="flex flex-wrap gap-3">
          {#each badges as badge, index}
            <span
              class={`px-4 py-2 border border-[#e0e0e0] text-sm font-mono text-[#161616] bg-[#f4f4f4] transition-all duration-500 ${
                isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
              }`}
              style={`transition-delay: ${index * 50 + 200}ms;`}
            >
              {badge}
            </span>
          {/each}
        </div>
      </div>

      <!-- Right: Features -->
      <div class="grid gap-6">
        {#each securityFeatures as feature, index}
          <div
            class={`p-6 border border-[#e0e0e0] bg-[#f4f4f4] hover:border-[#0f62fe] transition-all duration-500 group ${
              isVisible ? "opacity-100 translate-x-0" : "opacity-0 translate-x-8"
            }`}
            style={`transition-delay: ${index * 100}ms;`}
          >
            <div class="flex items-start gap-4">
              <div class="shrink-0 w-10 h-10 flex items-center justify-center bg-white border border-[#e0e0e0] group-hover:bg-[#0f62fe] group-hover:text-white transition-colors duration-300">
                <feature.icon class="w-5 h-5 text-[#0f62fe] group-hover:text-white" />
              </div>
              <div>
                <h3 class="text-lg font-medium text-[#161616] mb-1 group-hover:translate-x-1 transition-transform duration-300">
                  {feature.title}
                </h3>
                <p class="text-[#525252] text-sm leading-relaxed">{feature.description}</p>
              </div>
            </div>
          </div>
        {/each}
      </div>
    </div>
  </div>
</section>
