<script lang="ts">
  import { ShieldCheck } from 'lucide-svelte';
</script>

<footer class="bg-[#161616] text-[#c6c6c6] pt-16 pb-12 border-t border-[#262626]">
  <div class="max-w-[1584px] mx-auto px-4 lg:px-8">
    <div class="grid grid-cols-2 md:grid-cols-5 gap-10 mb-16">
      <!-- Col 1: Brand -->
      <div class="col-span-2">
        <a href="/" class="flex items-baseline gap-2 mb-4">
          <span class="text-2xl font-bold tracking-tight text-white">
            Précis
          </span>
          <span class="text-xs font-mono text-[#8c8c8c]">F&amp;B</span>
        </a>
        <p class="text-sm text-[#8c8c8c] max-w-sm mb-6 leading-relaxed">
          Platform operasional SaaS terpadu untuk bisnis kuliner dan kedai kopi. Presensi selfie ber-watermark GPS, kasir POS layar sentuh offline-ready, dan payroll otomatis.
        </p>
        <div class="flex items-center gap-2 text-xs font-mono text-[#24a148]">
          <ShieldCheck class="w-4 h-4" />
          <span>Sistem Operasional 99.9% Uptime</span>
        </div>
      </div>

      <!-- Col 2: Solusi -->
      <div>
        <p class="font-semibold text-white text-xs uppercase tracking-wider mb-4">
          Solusi Produk
        </p>
        <ul class="space-y-2.5 text-xs">
          <li><a href="#pos" class="hover:text-white transition-colors">Kasir POS Layar Sentuh</a></li>
          <li><a href="#attendance" class="hover:text-white transition-colors">Presensi Selfie GPS</a></li>
          <li><a href="#attendance" class="hover:text-white transition-colors">Wall of Faces Audit</a></li>
          <li><a href="#payroll" class="hover:text-white transition-colors">Kalkulator Gaji &amp; Denda</a></li>
          <li><a href="#payroll" class="hover:text-white transition-colors">Modul Kasbon Karyawan</a></li>
        </ul>
      </div>

      <!-- Col 3: Pelanggan -->
      <div>
        <p class="font-semibold text-white text-xs uppercase tracking-wider mb-4">
          Akses Portal
        </p>
        <ul class="space-y-2.5 text-xs">
          <li><a href="https://app.precis.com/login" class="hover:text-white transition-colors">Login Portal Owner</a></li>
          <li><a href="https://app.precis.com/login" class="hover:text-white transition-colors">Portal Store Manager</a></li>
          <li><a href="https://pos.precis.com" class="hover:text-white transition-colors">Buka Kiosk Kasir Toko</a></li>
          <li><a href="https://app.precis.com/register" class="hover:text-white transition-colors">Daftar Akun Baru</a></li>
          <li><a href="#pricing" class="hover:text-white transition-colors">Daftar Paket Langganan</a></li>
        </ul>
      </div>

      <!-- Col 4: Bantuan -->
      <div>
        <p class="font-semibold text-white text-xs uppercase tracking-wider mb-4">
          Pusat Bantuan
        </p>
        <ul class="space-y-2.5 text-xs">
          <li><a href="#faq" class="hover:text-white transition-colors">Tanya Jawab (FAQ)</a></li>
          <li><a href="#how-it-works" class="hover:text-white transition-colors">Panduan Setup Outlet</a></li>
          <li><a href="#advantages" class="hover:text-white transition-colors">Dukungan Printer Bluetooth</a></li>
          <li><a href="https://wa.me/6281234567890" class="hover:text-white transition-colors">WhatsApp Support 24/7</a></li>
        </ul>
      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="pt-8 border-t border-[#262626] flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#8c8c8c]">
      <p>&copy; 2026 Précis F&amp;B. Hak cipta dilindungi undang-undang.</p>
      <div class="flex items-center gap-6">
        <a href="#faq" class="hover:text-white transition-colors">Kebijakan Privasi</a>
        <a href="#faq" class="hover:text-white transition-colors">Syarat &amp; Ketentuan</a>
        <a href="#faq" class="hover:text-white transition-colors">Keamanan Data</a>
      </div>
    </div>
  </div>
</footer>
