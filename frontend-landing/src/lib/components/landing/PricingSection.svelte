<script lang="ts">
  import { ArrowRight, Check } from "lucide-svelte";

  let isAnnual = $state(true);

  const plans = [
    {
      name: "Single Outlet",
      description: "Untuk toko mandiri atau bisnis 1 lokasi",
      price: { monthly: "99.000", annual: "79.000" },
      features: [
        "1 Kuota Outlet / Cabang",
        "Kasir POS Offline-Ready",
        "Presensi Selfie & Watermark GPS",
        "Wall of Faces Audit Harian",
        "Rekonsiliasi Kas Harian",
        "Arsip Foto Selfie 60 Hari",
      ],
      cta: "Coba Gratis 1 Bulan",
      popular: false,
    },
    {
      name: "Growth Multi-Outlet",
      description: "Untuk bisnis bertumbuh 2 - 3 cabang",
      price: { monthly: "249.000", annual: "199.000" },
      features: [
        "Hingga 3 Kuota Outlet / Cabang",
        "Semua Fitur Single Outlet",
        "Hak Akses Kepala Toko (Store Manager)",
        "Modul Pinjaman Kasbon & Auto-Potong",
        "Cetak Struk Thermal Bluetooth",
        "Ekspor Slip Gaji Siap Transfer Bank",
      ],
      cta: "Paling Diminati - Coba Gratis",
      popular: true,
    },
    {
      name: "Scale Enterprise",
      description: "Untuk jaringan bisnis 4 - 10 cabang operasional",
      price: { monthly: "499.000", annual: "399.000" },
      features: [
        "Hingga 10 Kuota Outlet / Cabang",
        "Semua Fitur Growth Multi-Outlet",
        "Staf Karyawan & Kasir Tanpa Batas",
        "Laporan Finansial Konsolidasi",
        "Geofence Radius Kustom per Cabang",
        "Prioritas Dukungan Teknis 24/7",
      ],
      cta: "Mulai Paket Scale",
      popular: false,
    },
  ];
</script>

<section id="pricing" class="relative py-32 lg:py-40 bg-white border-t border-[#e0e0e0]">
  <div class="max-w-7xl mx-auto px-6 lg:px-12">
    <!-- Header -->
    <div class="max-w-3xl mb-20">
      <span class="font-mono text-xs tracking-widest text-[#525252] uppercase block mb-6">
        Paket &amp; Harga
      </span>
      <h2 class="font-display text-5xl md:text-6xl lg:text-7xl tracking-tight text-[#161616] mb-6">
        Biaya sederhana,
        <br />
        <span class="text-stroke">transparan</span>
      </h2>
      <p class="text-lg text-[#525252] max-w-xl">
        Mulai dengan uji coba gratis 1 bulan penuh. Tambah kuota outlet kapan saja tanpa kontrak rumit.
      </p>
    </div>

    <!-- Billing Toggle -->
    <div class="flex items-center gap-4 mb-16">
      <span
        class={`text-sm transition-colors ${
          !isAnnual ? "text-[#161616] font-semibold" : "text-[#525252]"
        }`}
      >
        Bulanan
      </span>
      <button
        type="button"
        onclick={() => (isAnnual = !isAnnual)}
        class="relative w-14 h-7 bg-[#e0e0e0] rounded-full p-1 transition-colors hover:bg-[#c6c6c6]"
        aria-label="Toggle annual billing"
      >
        <div
          class={`w-5 h-5 bg-[#0f62fe] rounded-full transition-transform duration-300 ${
            isAnnual ? "translate-x-7" : "translate-x-0"
          }`}
        ></div>
      </button>
      <span
        class={`text-sm transition-colors ${
          isAnnual ? "text-[#161616] font-semibold" : "text-[#525252]"
        }`}
      >
        Tahunan
      </span>
      {#if isAnnual}
        <span class="ml-2 px-2 py-1 bg-[#24a148] text-white text-xs font-mono rounded">
          Hemat 20%
        </span>
      {/if}
    </div>

    <!-- Pricing Cards -->
    <div class="grid md:grid-cols-3 gap-px bg-[#e0e0e0] border border-[#e0e0e0]">
      {#each plans as plan, idx}
        <div
          class={`relative p-8 lg:p-12 bg-white flex flex-col justify-between ${
            plan.popular ? "md:-my-4 md:py-12 lg:py-16 border-2 border-[#0f62fe] shadow-lg z-10" : ""
          }`}
        >
          <div>
            {#if plan.popular}
              <span class="absolute -top-3 left-8 px-3 py-1 bg-[#0f62fe] text-white text-xs font-mono uppercase tracking-widest">
                Paling Diminati
              </span>
            {/if}

            <!-- Plan Header -->
            <div class="mb-8">
              <span class="font-mono text-xs text-[#8c8c8c]">
                {String(idx + 1).padStart(2, "0")}
              </span>
              <h3 class="font-display text-3xl text-[#161616] mt-2">{plan.name}</h3>
              <p class="text-sm text-[#525252] mt-2">{plan.description}</p>
            </div>

            <!-- Price -->
            <div class="mb-8 pb-8 border-b border-[#e0e0e0]">
              <div class="flex items-baseline gap-2">
                <span class="text-sm font-mono text-[#525252]">Rp</span>
                <span class="font-display text-4xl lg:text-5xl text-[#161616]">
                  {isAnnual ? plan.price.annual : plan.price.monthly}
                </span>
                <span class="text-[#525252] text-sm font-mono">/bln</span>
              </div>
            </div>

            <!-- Features -->
            <ul class="space-y-4 mb-10">
              {#each plan.features as feature}
                <li class="flex items-start gap-3">
                  <Check class="w-4 h-4 text-[#0f62fe] mt-0.5 shrink-0" />
                  <span class="text-sm text-[#525252]">{feature}</span>
                </li>
              {/each}
            </ul>
          </div>

          <!-- CTA -->
          <a
            href="https://app.precis.com/register"
            class={`w-full py-4 flex items-center justify-center gap-2 text-sm font-medium transition-all rounded-none ${
              plan.popular
                ? "bg-[#0f62fe] text-white hover:bg-[#0050e6]"
                : "border border-[#161616] text-[#161616] hover:bg-[#f4f4f4]"
            }`}
          >
            {plan.cta}
            <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
          </a>
        </div>
      {/each}
    </div>

    <!-- Bottom Note -->
    <p class="mt-12 text-center text-sm text-[#525252]">
      Semua paket mencakup pembaruan sistem berkala, enkripsi SSL, dan backup cloud otomatis.
    </p>
  </div>
</section>
