<script lang="ts">
  import { ArrowUpRight } from 'lucide-svelte';
  import AnimatedWave from './AnimatedWave.svelte';

  const footerLinks = {
    Produk: [
      { name: 'Presensi Selfie GPS', href: '#features' },
      { name: 'Kasir POS', href: '#features' },
      { name: 'Payroll Otomatis', href: '#features' },
      { name: 'Cara Kerja', href: '#how-it-works' },
      { name: 'Paket Harga', href: '#pricing' },
    ],
    Portal: [
      { name: 'Portal Owner', href: 'https://app.precis.com/login' },
      { name: 'Portal Store Manager', href: 'https://app.precis.com/login' },
      { name: 'Kiosk Kasir Toko', href: 'https://pos.precis.com' },
      {
        name: 'Daftar Akun Baru',
        href: 'https://app.precis.com/register',
        badge: '1 Bulan Gratis',
      },
    ],
    Perusahaan: [
      { name: 'Tentang Précis', href: '#' },
      { name: 'Pusat Bantuan', href: '#' },
      { name: 'Panduan Outlet', href: '#how-it-works' },
      { name: 'WhatsApp Support', href: 'https://wa.me/6281234567890' },
    ],
    Legalitas: [
      { name: 'Kebijakan Privasi', href: '#' },
      { name: 'Syarat & Ketentuan', href: '#' },
      { name: 'Keamanan Data', href: '#security' },
    ],
  };

  const socialLinks = [
    { name: 'Instagram', href: '#' },
    { name: 'LinkedIn', href: '#' },
    { name: 'WhatsApp', href: 'https://wa.me/6281234567890' },
  ];
</script>

<footer class="relative overflow-hidden border-t border-[#e0e0e0] bg-white">
  <div class="relative mx-auto max-w-350 px-6 lg:px-12">
    <!-- Main Footer Content Area with full Animated Wave Background -->
    <div class="relative py-16 lg:py-24">
      <!-- Animated wave background filling the entire upper section above the bottom bar -->
      <div
        class="pointer-events-none absolute -inset-x-6 inset-y-0 overflow-hidden opacity-25 lg:-inset-x-12"
      >
        <AnimatedWave />
      </div>

      <div class="relative z-10 grid grid-cols-2 gap-12 md:grid-cols-6 lg:gap-8">
        <!-- Brand Column -->
        <div class="col-span-2">
          <a href="/" class="mb-6 inline-flex items-baseline gap-2">
            <span class="font-display text-2xl font-bold text-[#161616]">Précis</span>
          </a>

          <p class="mb-8 max-w-xs text-sm leading-relaxed text-[#525252]">
            Platform operasional SaaS terpadu khusus bisnis ritel, kuliner, dan jasa. Presensi jujur
            ber-watermark GPS, kasir POS offline-ready, dan payroll otomatis.
          </p>

          <!-- Social Links -->
          <div class="flex gap-6">
            {#each socialLinks as link}
              <a
                href={link.href}
                class="group flex items-center gap-1 text-sm text-[#525252] transition-colors hover:text-[#161616]"
              >
                {link.name}
                <ArrowUpRight
                  class="h-3 w-3 -translate-x-1 text-[#0f62fe] opacity-0 transition-all group-hover:translate-x-0 group-hover:opacity-100"
                />
              </a>
            {/each}
          </div>
        </div>

        <!-- Link Columns -->
        {#each Object.entries(footerLinks) as [title, links]}
          <div>
            <h3 class="mb-6 text-sm font-semibold text-[#161616]">{title}</h3>
            <ul class="space-y-4">
              {#each links as link}
                <li>
                  <a
                    href={link.href}
                    class="inline-flex items-center gap-2 text-sm text-[#525252] transition-colors hover:text-[#161616]"
                  >
                    {link.name}
                    {#if 'badge' in link && link.badge}
                      <span
                        class="rounded-full bg-[#0f62fe] px-2 py-0.5 font-mono text-[10px] text-white"
                      >
                        {link.badge}
                      </span>
                    {/if}
                  </a>
                </li>
              {/each}
            </ul>
          </div>
        {/each}
      </div>
    </div>

    <!-- Bottom Bar (Clean boundary above copyright) -->
    <div
      class="relative z-10 flex items-center justify-center border-t border-[#e0e0e0] bg-white py-8 text-center"
    >
      <p class="text-sm text-[#8c8c8c]">
        &copy; 2026 <span
          class="font-medium text-[#525252] underline decoration-[#8c8c8c] decoration-1 underline-offset-4"
          >Munk Studio</span
        >. Hak cipta dilindungi undang-undang.
      </p>
    </div>
  </div>
</footer>
