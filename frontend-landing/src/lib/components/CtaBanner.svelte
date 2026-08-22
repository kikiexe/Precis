<script lang="ts">
  import { ArrowRight, Sparkles } from 'lucide-svelte';
</script>

<section class="bg-[#0f62fe] text-white py-16 lg:py-20">
  <div class="max-w-[1584px] mx-auto px-4 lg:px-8">
    <div class="grid lg:grid-cols-12 gap-8 items-center">
      <div class="lg:col-span-8">
        <span class="inline-flex items-center gap-2 text-xs font-mono bg-white/10 px-3 py-1 mb-4 text-white">
          <Sparkles class="w-3.5 h-3.5" />
          Aktivasi Instan Tanpa Kartu Kredit
        </span>
        <h2 class="font-headline text-white mb-4">
          Siap mengotomatiskan operasional kedai kopi Anda?
        </h2>
        <p class="font-body-lg text-white/85 max-w-2xl">
          Coba gratis selama 14 hari penuh. Nikmati kemudahan kasir anti-macet, presensi jujur, dan rekap penggajian instan dalam satu aplikasi terpadu.
        </p>
      </div>

      <div class="lg:col-span-4 flex flex-col sm:flex-row lg:justify-end gap-3">
        <a
          href="https://app.precis.com/register"
          class="h-12 px-6 inline-flex items-center justify-center gap-2 text-sm font-semibold bg-white text-[#0f62fe] hover:bg-[#f4f4f4] transition-colors"
        >
          Mulai Coba 14 Hari Gratis
          <ArrowRight class="w-4 h-4" />
        </a>
      </div>
    </div>
  </div>
</section>
