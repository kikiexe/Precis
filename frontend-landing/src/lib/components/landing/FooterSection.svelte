<script lang="ts">
  import { ArrowUpRight } from "lucide-svelte";
  import AnimatedWave from "./AnimatedWave.svelte";

  const footerLinks = {
    Produk: [
      { name: "Presensi Selfie GPS", href: "#features" },
      { name: "Kasir POS", href: "#features" },
      { name: "Payroll Otomatis", href: "#features" },
      { name: "Cara Kerja", href: "#how-it-works" },
      { name: "Paket Harga", href: "#pricing" },
    ],
    Portal: [
      { name: "Portal Owner", href: "https://app.precis.com/login" },
      { name: "Portal Store Manager", href: "https://app.precis.com/login" },
      { name: "Kiosk Kasir Toko", href: "https://pos.precis.com" },
      { name: "Daftar Akun Baru", href: "https://app.precis.com/register", badge: "1 Bulan Gratis" },
    ],
    Perusahaan: [
      { name: "Tentang Précis", href: "#" },
      { name: "Pusat Bantuan", href: "#" },
      { name: "Panduan Outlet", href: "#how-it-works" },
      { name: "WhatsApp Support", href: "https://wa.me/6281234567890" },
    ],
    Legalitas: [
      { name: "Kebijakan Privasi", href: "#" },
      { name: "Syarat & Ketentuan", href: "#" },
      { name: "Keamanan Data", href: "#security" },
    ],
  };

  const socialLinks = [
    { name: "Instagram", href: "#" },
    { name: "LinkedIn", href: "#" },
    { name: "WhatsApp", href: "https://wa.me/6281234567890" },
  ];
</script>

<footer class="relative bg-white border-t border-[#e0e0e0] overflow-hidden">
  <div class="relative max-w-350 mx-auto px-6 lg:px-12">
    <!-- Main Footer Content Area with full Animated Wave Background -->
    <div class="relative py-16 lg:py-24">
      <!-- Animated wave background filling the entire upper section above the bottom bar -->
      <div class="absolute -inset-x-6 lg:-inset-x-12 inset-y-0 opacity-25 pointer-events-none overflow-hidden">
        <AnimatedWave />
      </div>

      <div class="relative z-10 grid grid-cols-2 md:grid-cols-6 gap-12 lg:gap-8">
        <!-- Brand Column -->
        <div class="col-span-2">
          <a href="/" class="inline-flex items-baseline gap-2 mb-6">
            <span class="text-2xl font-display font-bold text-[#161616]">Précis</span>
          </a>

          <p class="text-[#525252] leading-relaxed mb-8 max-w-xs text-sm">
            Platform operasional SaaS terpadu khusus bisnis ritel, kuliner, dan jasa. Presensi jujur ber-watermark GPS, kasir POS offline-ready, dan payroll otomatis.
          </p>

          <!-- Social Links -->
          <div class="flex gap-6">
            {#each socialLinks as link}
              <a
                href={link.href}
                class="text-sm text-[#525252] hover:text-[#161616] transition-colors flex items-center gap-1 group"
              >
                {link.name}
                <ArrowUpRight class="w-3 h-3 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all text-[#0f62fe]" />
              </a>
            {/each}
          </div>
        </div>

        <!-- Link Columns -->
        {#each Object.entries(footerLinks) as [title, links]}
          <div>
            <h3 class="text-sm font-semibold text-[#161616] mb-6">{title}</h3>
            <ul class="space-y-4">
              {#each links as link}
                <li>
                  <a
                    href={link.href}
                    class="text-sm text-[#525252] hover:text-[#161616] transition-colors inline-flex items-center gap-2"
                  >
                    {link.name}
                    {#if "badge" in link && link.badge}
                      <span class="text-[10px] font-mono px-2 py-0.5 bg-[#0f62fe] text-white rounded-full">
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
    <div class="relative z-10 py-8 border-t border-[#e0e0e0] bg-white flex items-center justify-center text-center">
      <p class="text-sm text-[#8c8c8c]">
        &copy; 2026 <span class="underline underline-offset-4 decoration-1 decoration-[#8c8c8c] text-[#525252] font-medium">Munk Studio</span>. Hak cipta dilindungi undang-undang.
      </p>
    </div>
  </div>
</footer>
