<script lang="ts">
  import { onMount } from 'svelte';
  import { Shield, Lock, Eye, FileCheck } from 'lucide-svelte';

  const securityFeatures = [
    {
      icon: Lock,
      title: 'Penguncian Tablet Kasir',
      description:
        'Setiap perangkat tablet kasir diikat dengan token otorisasi unik per cabang toko.',
    },
    {
      icon: Shield,
      title: 'Isolasi Data Multi-Tenant',
      description: 'Data penjualan, laba, dan gaji cabang Anda terisolasi aman di level database.',
    },
    {
      icon: Eye,
      title: 'Verifikasi Geofence GPS',
      description:
        'Foto presensi diverifikasi otomatis dengan radius toleransi titik koordinat toko.',
    },
    {
      icon: FileCheck,
      title: 'Audit Trail Finansial Lengkap',
      description: 'Seluruh pencatatan kas, diskon kasir, dan penyesuaian gaji terekam permanen.',
    },
  ];

  const badges = [
    'Device Lock',
    'Multi-Tenant Safe',
    'GPS Geofenced',
    'Audit Trailed',
    'Cloudflare Encrypted',
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
  id="security"
  bind:this={sectionEl}
  class="relative overflow-hidden border-t border-[#e0e0e0] bg-white py-24 lg:py-32"
>
  <div class="mx-auto max-w-[1400px] px-6 lg:px-12">
    <div class="grid gap-16 lg:grid-cols-2 lg:gap-24">
      <!-- Left: Content -->
      <div
        class={`transition-all duration-700 ${
          isVisible ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'
        }`}
      >
        <span class="mb-6 inline-flex items-center gap-3 font-mono text-sm text-[#525252]">
          <span class="h-px w-8 bg-[#0f62fe]"></span>
          Keamanan &amp; Integritas
        </span>
        <h2 class="font-display mb-8 text-4xl tracking-tight text-[#161616] lg:text-6xl">
          Keamanan data adalah
          <br />
          prioritas mutlak.
        </h2>
        <p class="mb-12 text-xl leading-relaxed text-[#525252]">
          Perlindungan data finansial dan integritas kehadiran dibangun di setiap lapisan sistem,
          menjamin bisnis Anda beroperasi dengan tenang.
        </p>

        <!-- Badges -->
        <div class="flex flex-wrap gap-3">
          {#each badges as badge, index}
            <span
              class={`border border-[#e0e0e0] bg-[#f4f4f4] px-4 py-2 font-mono text-sm text-[#161616] transition-all duration-500 ${
                isVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'
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
            class={`group border border-[#e0e0e0] bg-[#f4f4f4] p-6 transition-all duration-500 hover:border-[#0f62fe] ${
              isVisible ? 'translate-x-0 opacity-100' : 'translate-x-8 opacity-0'
            }`}
            style={`transition-delay: ${index * 100}ms;`}
          >
            <div class="flex items-start gap-4">
              <div
                class="flex h-10 w-10 shrink-0 items-center justify-center border border-[#e0e0e0] bg-white transition-colors duration-300 group-hover:bg-[#0f62fe] group-hover:text-white"
              >
                <feature.icon class="h-5 w-5 text-[#0f62fe] group-hover:text-white" />
              </div>
              <div>
                <h3
                  class="mb-1 text-lg font-medium text-[#161616] transition-transform duration-300 group-hover:translate-x-1"
                >
                  {feature.title}
                </h3>
                <p class="text-sm leading-relaxed text-[#525252]">{feature.description}</p>
              </div>
            </div>
          </div>
        {/each}
      </div>
    </div>
  </div>
</section>
