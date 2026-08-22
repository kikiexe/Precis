<script lang="ts">
  import { ArrowUpRight } from "lucide-svelte";
  import AnimatedWave from "./AnimatedWave.svelte";

  const footerLinks = {
    Produk: [
      { name: "Presensi Selfie GPS", href: "#features" },
      { name: "Kasir POS Layar Sentuh", href: "#features" },
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
  <!-- Animated wave background -->
  <div class="absolute inset-0 h-64 opacity-25 pointer-events-none overflow-hidden">
    <AnimatedWave />
  </div>

  <div class="relative z-10 max-w-[1400px] mx-auto px-6 lg:px-12">
    <!-- Main Footer -->
    <div class="py-16 lg:py-24">
      <div class="grid grid-cols-2 md:grid-cols-6 gap-12 lg:gap-8">
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

    <!-- Bottom Bar -->
    <div class="py-8 border-t border-[#e0e0e0] flex flex-col md:flex-row items-center justify-between gap-4">
      <p class="text-sm text-[#8c8c8c]">
        &copy; 2026 Précis. Hak cipta dilindungi undang-undang.
      </p>

      <div class="flex items-center gap-4 text-sm text-[#525252]">
        <span class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-[#24a148]"></span>
          Sistem Operasional 99.9% Normal
        </span>
      </div>
    </div>
  </div>
</footer>
