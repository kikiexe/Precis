<script lang="ts">
  import { onMount } from "svelte";

  const features = [
    {
      number: "01",
      title: "Presensi Selfie & Watermark GPS",
      description: "Karyawan wajib foto langsung saat tiba di toko. Jam WIB aktual dan koordinat toko dibakar permanen ke atas foto untuk mencegah titip absen.",
      visual: "deploy",
    },
    {
      number: "02",
      title: "Kasir POS Layar Sentuh Offline",
      description: "Kasir tidak pernah macet saat internet mati. Transaksi tersimpan aman di database lokal tablet dan otomatis sinkron saat online.",
      visual: "ai",
    },
    {
      number: "03",
      title: "Otomatisasi Payroll & Kasbon",
      description: "Kalkulasi gaji bersih otomatis memotong denda keterlambatan per menit, upah lembur, dan cicilan kasbon staf dalam 1 klik.",
      visual: "collab",
    },
    {
      number: "04",
      title: "Keamanan Sistem & Otorisasi PIN",
      description: "Penguncian tablet per outlet, proteksi laporan finansial dari kebocoran data, dan kontrol hak akses terpusat untuk Owner.",
      visual: "security",
    },
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
  id="features"
  bind:this={sectionEl}
  class="relative py-24 lg:py-32 bg-white border-t border-[#e0e0e0]"
>
  <div class="max-w-[1400px] mx-auto px-6 lg:px-12">
    <!-- Header -->
    <div class="mb-16 lg:mb-24">
      <span class="inline-flex items-center gap-3 text-sm font-mono text-[#525252] mb-6">
        <span class="w-8 h-px bg-[#0f62fe]"></span>
        Pilar Fitur Utama
      </span>
      <h2
        class={`text-4xl lg:text-6xl font-display tracking-tight transition-all duration-700 text-[#161616] ${
          isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
        }`}
      >
        Semua kebutuhan bisnis.
        <br />
        <span class="text-[#525252]">Terhubung dalam satu sistem.</span>
      </h2>
    </div>

    <!-- Features List -->
    <div>
      {#each features as feature, index}
        <div
          class={`group relative transition-all duration-700 ${
            isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-12"
          }`}
          style={`transition-delay: ${index * 100}ms;`}
        >
          <div class="flex flex-col lg:flex-row gap-8 lg:gap-16 py-12 lg:py-20 border-b border-[#e0e0e0]">
            <!-- Number -->
            <div class="shrink-0">
              <span class="font-mono text-sm text-[#8c8c8c]">{feature.number}</span>
            </div>

            <!-- Content -->
            <div class="flex-1 grid lg:grid-cols-2 gap-8 items-center">
              <div>
                <h3 class="text-3xl lg:text-4xl font-display mb-4 text-[#161616] group-hover:translate-x-2 transition-transform duration-500">
                  {feature.title}
                </h3>
                <p class="text-lg text-[#525252] leading-relaxed">
                  {feature.description}
                </p>
              </div>

              <!-- Animated Visual SVGs -->
              <div class="flex justify-center lg:justify-end">
                <div class="w-48 h-40 text-[#0f62fe]">
                  {#if feature.visual === "deploy"}
                    <svg viewBox="0 0 200 160" class="w-full h-full">
                      <defs>
                        <clipPath id="deployClip">
                          <rect x="30" y="20" width="140" height="120" rx="4" />
                        </clipPath>
                      </defs>
                      <rect x="30" y="20" width="140" height="120" rx="4" fill="none" stroke="currentColor" stroke-width="2" />
                      <g clip-path="url(#deployClip)">
                        {#each [0, 1, 2, 3, 4, 5] as i}
                          <rect
                            x="40"
                            y={35 + i * 16}
                            width="120"
                            height="10"
                            rx="2"
                            fill="currentColor"
                            opacity="0.15"
                          >
                            <animate
                              attributeName="opacity"
                              values="0.15;0.8;0.15"
                              dur="2s"
                              begin={`${i * 0.15}s`}
                              repeatCount="indefinite"
                            />
                            <animate
                              attributeName="width"
                              values="20;120;20"
                              dur="2s"
                              begin={`${i * 0.15}s`}
                              repeatCount="indefinite"
                            />
                          </rect>
                        {/each}
                      </g>
                      <circle cx="100" cy="155" r="3" fill="currentColor" opacity="0.3">
                        <animate attributeName="opacity" values="0.3;1;0.3" dur="1s" repeatCount="indefinite" />
                      </circle>
                    </svg>
                  {:else if feature.visual === "ai"}
                    <svg viewBox="0 0 200 160" class="w-full h-full">
                      <circle cx="100" cy="80" r="12" fill="currentColor">
                        <animate attributeName="r" values="12;14;12" dur="2s" repeatCount="indefinite" />
                      </circle>
                      {#each [0, 1, 2, 3, 4, 5] as i}
                        {@const angle = (i * 60) * (Math.PI / 180)}
                        {@const radius = 50}
                        <line
                          x1="100"
                          y1="80"
                          x2={100 + Math.cos(angle) * radius}
                          y2={80 + Math.sin(angle) * radius}
                          stroke="currentColor"
                          stroke-width="1"
                          opacity="0.3"
                        >
                          <animate
                            attributeName="opacity"
                            values="0.3;0.8;0.3"
                            dur="2s"
                            begin={`${i * 0.3}s`}
                            repeatCount="indefinite"
                          />
                        </line>
                        <circle
                          cx={100 + Math.cos(angle) * radius}
                          cy={80 + Math.sin(angle) * radius}
                          r="6"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="2"
                        >
                          <animate
                            attributeName="r"
                            values="6;8;6"
                            dur="2s"
                            begin={`${i * 0.3}s`}
                            repeatCount="indefinite"
                          />
                        </circle>
                      {/each}
                      <circle cx="100" cy="80" r="30" fill="none" stroke="currentColor" stroke-width="1" opacity="0">
                        <animate attributeName="r" values="20;60" dur="2s" repeatCount="indefinite" />
                        <animate attributeName="opacity" values="0.5;0" dur="2s" repeatCount="indefinite" />
                      </circle>
                    </svg>
                  {:else if feature.visual === "collab"}
                    <svg viewBox="0 0 200 160" class="w-full h-full">
                      <!-- User A -->
                      <g>
                        <rect x="30" y="50" width="50" height="60" rx="4" fill="none" stroke="currentColor" stroke-width="2" />
                        <text x="55" y="85" text-anchor="middle" font-size="20" font-family="monospace" fill="currentColor">A</text>
                        <circle cx="55" cy="35" r="12" fill="none" stroke="currentColor" stroke-width="2" />
                      </g>
                      <!-- User B -->
                      <g>
                        <rect x="120" y="50" width="50" height="60" rx="4" fill="none" stroke="currentColor" stroke-width="2" />
                        <text x="145" y="85" text-anchor="middle" font-size="20" font-family="monospace" fill="currentColor">B</text>
                        <circle cx="145" cy="35" r="12" fill="none" stroke="currentColor" stroke-width="2" />
                      </g>
                      <!-- Connection Line -->
                      <line x1="80" y1="80" x2="120" y2="80" stroke="currentColor" stroke-width="2" stroke-dasharray="4 4">
                        <animate attributeName="stroke-dashoffset" values="0;-8" dur="0.5s" repeatCount="indefinite" />
                      </line>
                      <!-- Data packet -->
                      <path id="dataPath" d="M 80 80 L 120 80" fill="none" />
                      <circle r="4" fill="currentColor">
                        <animateMotion dur="1.5s" repeatCount="indefinite">
                          <mpath href="#dataPath" />
                        </animateMotion>
                      </circle>
                      <!-- Sync indicator -->
                      <g transform="translate(100, 130)">
                        <circle r="6" fill="none" stroke="currentColor" stroke-width="2">
                          <animate attributeName="r" values="6;10;6" dur="1s" repeatCount="indefinite" />
                          <animate attributeName="opacity" values="1;0.3;1" dur="1s" repeatCount="indefinite" />
                        </circle>
                      </g>
                    </svg>
                  {:else if feature.visual === "security"}
                    <svg viewBox="0 0 200 160" class="w-full h-full">
                      <!-- Shield -->
                      <path
                        d="M 100 20 L 150 40 L 150 90 Q 150 130 100 145 Q 50 130 50 90 L 50 40 Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                      />
                      <!-- Inner shield -->
                      <path
                        d="M 100 35 L 135 50 L 135 85 Q 135 115 100 128 Q 65 115 65 85 L 65 50 Z"
                        fill="currentColor"
                        opacity="0.1"
                      >
                        <animate attributeName="opacity" values="0.1;0.2;0.1" dur="2s" repeatCount="indefinite" />
                      </path>
                      <!-- Lock icon -->
                      <rect x="85" y="70" width="30" height="25" rx="3" fill="currentColor" />
                      <path
                        d="M 90 70 L 90 60 Q 90 50 100 50 Q 110 50 110 60 L 110 70"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round"
                      />
                      <!-- Keyhole -->
                      <circle cx="100" cy="80" r="4" fill="white" />
                      <rect x="98" y="82" width="4" height="8" fill="white" />
                      <!-- Scan lines -->
                      <line x1="60" y1="60" x2="140" y2="60" stroke="currentColor" stroke-width="1" opacity="0">
                        <animate attributeName="y1" values="40;120;40" dur="3s" repeatCount="indefinite" />
                        <animate attributeName="y2" values="40;120;40" dur="3s" repeatCount="indefinite" />
                        <animate attributeName="opacity" values="0;0.5;0" dur="3s" repeatCount="indefinite" />
                      </line>
                    </svg>
                  {/if}
                </div>
              </div>
            </div>
          </div>
        </div>
      {/each}
    </div>
  </div>
</section>
