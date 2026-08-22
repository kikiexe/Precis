<script lang="ts">
  import { Check, ArrowRight } from 'lucide-svelte';

  let isAnnual = $state(true);

  const plans = [
    {
      name: 'Single Outlet',
      description: 'Pilihan tepat untuk kedai kopi mandiri atau resto rintisan 1 lokasi.',
      monthlyPrice: '99.000',
      annualPrice: '79.000',
      features: [
        '1 Kuota Outlet / Cabang',
        'Kasir Layar Sentuh Offline-Ready',
        'Presensi Selfie & Stempel Lokasi GPS',
        'Wall of Faces Audit Harian',
        'Rekonsiliasi Kas Kasir Harian',
        'Kalkulasi Denda & Lembur Otomatis',
        'Arsip Foto 60 Hari'
      ],
      ctaLabel: 'Mulai Uji Coba Gratis',
      popular: false
    },
    {
      name: 'Growth Multi-Outlet',
      description: 'Untuk bisnis F&B yang sedang ekspansi memiliki 2 hingga 3 cabang.',
      monthlyPrice: '249.000',
      annualPrice: '199.000',
      features: [
        'Hingga 3 Kuota Outlet / Cabang',
        'Semua Fitur Single Outlet',
        'Hak Akses Kepala Toko (Store Manager)',
        'Modul Pinjaman Kasbon & Auto-Potong',
        'Cetak Struk Bluetooth Thermal',
        'Ekspor Slip Gaji Siap Transfer Bank',
        'Pengajuan & Persetujuan Tukar Shift'
      ],
      ctaLabel: 'Paling Diminati - Coba Gratis',
      popular: true
    },
    {
      name: 'Scale Pro',
      description: 'Untuk jaringan bisnis kuliner 4 hingga 10 cabang operasional.',
      monthlyPrice: '499.000',
      annualPrice: '399.000',
      features: [
        'Hingga 10 Kuota Outlet / Cabang',
        'Semua Fitur Growth Multi-Outlet',
        'Karyawan & Kasir Tanpa Batas',
        'Laporan Finansial Komprehensif',
        'Radius Geofence Kustom per Toko',
        'Prioritas Bantuan WhatsApp 24/7',
        'Bantuan Setup Awal & Onboarding Tim'
      ],
      ctaLabel: 'Mulai Paket Scale',
      popular: false
    }
  ];
</script>

<section id="pricing" class="bg-white py-20 lg:py-28 border-b border-[#e0e0e0]">
  <div class="max-w-[1584px] mx-auto px-4 lg:px-8">
    <div class="max-w-3xl mb-12">
      <span class="text-sm text-[#525252] block mb-4 font-mono">
        Paket &amp; Biaya Berlangganan
      </span>
      <h2 class="font-display-lg text-[#161616] tracking-tight mb-6">
        Biaya terjangkau, transparan, tanpa biaya tersembunyi.
      </h2>
      <p class="font-subhead text-[#525252]">
        Mulai dengan uji coba gratis 14 hari penuh. Anda dapat menambah kuota cabang kapan saja seiring pertumbuhan bisnis Anda.
      </p>
    </div>

    <!-- Monthly vs Annual Toggle -->
    <div class="flex items-center gap-4 mb-16">
      <button
        type="button"
        onclick={() => (isAnnual = false)}
        class={`text-sm transition-colors ${
          !isAnnual ? 'text-[#161616] font-semibold underline underline-offset-4' : 'text-[#525252]'
        }`}
      >
        Pembayaran Bulanan
      </button>

      <button
        type="button"
        onclick={() => (isAnnual = !isAnnual)}
        class="w-12 h-6 bg-[#e0e0e0] p-0.5 transition-colors relative"
        aria-label="Ubah periode tagihan"
      >
        <div
          class={`w-5 h-5 bg-[#0f62fe] transition-transform ${
            isAnnual ? 'translate-x-6' : 'translate-x-0'
          }`}
        ></div>
      </button>

      <button
        type="button"
        onclick={() => (isAnnual = true)}
        class={`text-sm transition-colors flex items-center gap-2 ${
          isAnnual ? 'text-[#161616] font-semibold underline underline-offset-4' : 'text-[#525252]'
        }`}
      >
        Pembayaran Tahunan
        <span class="text-[11px] font-mono bg-[#24a148]/10 text-[#24a148] px-2 py-0.5 font-medium">
          Hemat 20%
        </span>
      </button>
    </div>

    <!-- Pricing Cards Grid -->
    <div class="grid md:grid-cols-3 gap-px bg-[#e0e0e0] border border-[#e0e0e0]">
      {#each plans as plan}
        <div class={`bg-white p-8 flex flex-col justify-between ${plan.popular ? 'border-2 border-[#0f62fe] relative' : ''}`}>
          <div>
            {#if plan.popular}
              <div class="inline-block bg-[#0f62fe] text-white text-[11px] font-mono px-2.5 py-1 mb-4">
                PILIHAN PALING DIMINATI
              </div>
            {/if}

            <h3 class="font-card-title text-[#161616] mb-2">{plan.name}</h3>
            <p class="font-body-sm text-[#525252] leading-relaxed mb-6 min-h-[42px]">{plan.description}</p>

            <div class="pb-6 mb-6 border-b border-[#e0e0e0]">
              <div class="flex items-baseline gap-1 font-mono">
                <span class="text-sm text-[#525252]">Rp</span>
                <span class="text-3xl lg:text-4xl font-bold text-[#161616]">
                  {isAnnual ? plan.annualPrice : plan.monthlyPrice}
                </span>
                <span class="text-xs text-[#525252]">/bln</span>
              </div>
              <span class="text-[11px] font-mono text-[#8c8c8c]">
                {isAnnual ? 'Ditagih tahunan (hemat 20%)' : 'Ditagih bulanan'}
              </span>
            </div>

            <ul class="space-y-3 mb-8">
              {#each plan.features as feature}
                <li class="flex items-start gap-2.5 text-xs text-[#161616]">
                  <Check class="w-4 h-4 text-[#0f62fe] shrink-0 mt-0.5" />
                  <span>{feature}</span>
                </li>
              {/each}
            </ul>
          </div>

          <a
            href="https://app.precis.com/register"
            class={`w-full py-3.5 px-4 text-xs font-semibold flex items-center justify-center gap-2 transition-colors ${
              plan.popular
                ? 'bg-[#0f62fe] text-white hover:bg-[#0050e6]'
                : 'bg-white text-[#161616] border border-[#161616] hover:bg-[#f4f4f4]'
            }`}
          >
            {plan.ctaLabel}
            <ArrowRight class="w-4 h-4" />
          </a>
        </div>
      {/each}
    </div>
  </div>
</section>
