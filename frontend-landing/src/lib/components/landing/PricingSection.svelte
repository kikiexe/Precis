<script lang="ts">
  import { ArrowRight, Check } from 'lucide-svelte';

  let isAnnual = $state(true);

  const plans = [
    {
      name: 'Single Outlet',
      description: 'Untuk toko mandiri atau bisnis 1 lokasi',
      price: { monthly: '99.000', annual: '79.000' },
      features: [
        '1 Kuota Outlet / Cabang',
        'Kasir POS Offline-Ready',
        'Presensi Selfie & Watermark GPS',
        'Wall of Faces Audit Harian',
        'Rekonsiliasi Kas Harian',
        'Arsip Foto Selfie 60 Hari',
      ],
      cta: 'Coba Gratis 1 Bulan',
      popular: false,
    },
    {
      name: 'Growth Multi-Outlet',
      description: 'Untuk bisnis bertumbuh 2 - 3 cabang',
      price: { monthly: '249.000', annual: '199.000' },
      features: [
        'Hingga 3 Kuota Outlet / Cabang',
        'Semua Fitur Single Outlet',
        'Hak Akses Kepala Toko (Store Manager)',
        'Modul Pinjaman Kasbon & Auto-Potong',
        'Cetak Struk Thermal Bluetooth',
        'Ekspor Slip Gaji Siap Transfer Bank',
      ],
      cta: 'Paling Diminati - Coba Gratis',
      popular: true,
    },
    {
      name: 'Scale Enterprise',
      description: 'Untuk jaringan bisnis 4 - 10 cabang operasional',
      price: { monthly: '499.000', annual: '399.000' },
      features: [
        'Hingga 10 Kuota Outlet / Cabang',
        'Semua Fitur Growth Multi-Outlet',
        'Staf Karyawan & Kasir Tanpa Batas',
        'Laporan Finansial Konsolidasi',
        'Geofence Radius Kustom per Cabang',
        'Prioritas Dukungan Teknis 24/7',
      ],
      cta: 'Mulai Paket Scale',
      popular: false,
    },
  ];
</script>

<section id="pricing" class="relative border-t border-[#e0e0e0] bg-white py-32 lg:py-40">
  <div class="mx-auto max-w-7xl px-6 lg:px-12">
    <!-- Header -->
    <div class="mb-20 max-w-3xl">
      <span class="mb-6 block font-mono text-xs tracking-widest text-[#525252] uppercase">
        Paket &amp; Harga
      </span>
      <h2 class="mb-6 font-display text-5xl tracking-tight text-[#161616] md:text-6xl lg:text-7xl">
        Biaya sederhana,
        <br />
        <span class="text-stroke">transparan</span>
      </h2>
      <p class="max-w-xl text-lg text-[#525252]">
        Mulai dengan uji coba gratis 1 bulan penuh. Tambah kuota outlet kapan saja tanpa kontrak
        rumit.
      </p>
    </div>

    <!-- Billing Toggle -->
    <div class="mb-16 flex items-center gap-4">
      <span
        class={`text-sm transition-colors ${
          !isAnnual ? 'font-semibold text-[#161616]' : 'text-[#525252]'
        }`}
      >
        Bulanan
      </span>
      <button
        type="button"
        onclick={() => (isAnnual = !isAnnual)}
        class="relative h-7 w-14 rounded-full bg-[#e0e0e0] p-1 transition-colors hover:bg-[#c6c6c6]"
        aria-label="Toggle annual billing"
      >
        <div
          class={`size-5 rounded-full bg-[#0f62fe] transition-transform duration-300 ${
            isAnnual ? 'translate-x-7' : 'translate-x-0'
          }`}
        ></div>
      </button>
      <span
        class={`text-sm transition-colors ${
          isAnnual ? 'font-semibold text-[#161616]' : 'text-[#525252]'
        }`}
      >
        Tahunan
      </span>
      {#if isAnnual}
        <span class="ml-2 rounded bg-[#24a148] px-2 py-1 font-mono text-xs text-white">
          Hemat 20%
        </span>
      {/if}
    </div>

    <!-- Pricing Cards -->
    <div class="grid gap-px border border-[#e0e0e0] bg-[#e0e0e0] md:grid-cols-3">
      {#each plans as plan, idx}
        <div
          class={`relative flex flex-col justify-between bg-white p-8 lg:p-12 ${
            plan.popular
              ? 'z-10 border-2 border-[#0f62fe] shadow-lg md:-my-4 md:py-12 lg:py-16'
              : ''
          }`}
        >
          <div>
            {#if plan.popular}
              <span
                class="absolute -top-3 left-8 bg-[#0f62fe] px-3 py-1 font-mono text-xs tracking-widest text-white uppercase"
              >
                Paling Diminati
              </span>
            {/if}

            <!-- Plan Header -->
            <div class="mb-8">
              <span class="font-mono text-xs text-[#8c8c8c]">
                {String(idx + 1).padStart(2, '0')}
              </span>
              <h3 class="mt-2 font-display text-3xl text-[#161616]">{plan.name}</h3>
              <p class="mt-2 text-sm text-[#525252]">{plan.description}</p>
            </div>

            <!-- Price -->
            <div class="mb-8 border-b border-[#e0e0e0] pb-8">
              <div class="flex items-baseline gap-2">
                <span class="font-mono text-sm text-[#525252]">Rp</span>
                <span class="font-display text-4xl text-[#161616] lg:text-5xl">
                  {isAnnual ? plan.price.annual : plan.price.monthly}
                </span>
                <span class="font-mono text-sm text-[#525252]">/bln</span>
              </div>
            </div>

            <!-- Features -->
            <ul class="mb-10 space-y-4">
              {#each plan.features as feature}
                <li class="flex items-start gap-3">
                  <Check class="mt-0.5 size-4 shrink-0 text-[#0f62fe]" />
                  <span class="text-sm text-[#525252]">{feature}</span>
                </li>
              {/each}
            </ul>
          </div>

          <!-- CTA -->
          <a
            href="https://app.precis.com/register"
            class={`flex w-full items-center justify-center gap-2 rounded-none py-4 text-sm font-medium transition-all ${
              plan.popular
                ? 'bg-[#0f62fe] text-white hover:bg-[#0050e6]'
                : 'border border-[#161616] text-[#161616] hover:bg-[#f4f4f4]'
            }`}
          >
            {plan.cta}
            <ArrowRight class="size-4 transition-transform group-hover:translate-x-1" />
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
