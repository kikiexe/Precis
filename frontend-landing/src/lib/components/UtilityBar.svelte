<script lang="ts">
  import { ShieldCheck, PhoneCall, Store } from 'lucide-svelte';
</script>

<div class="bg-[#f4f4f4] text-[#525252] border-b border-[#e0e0e0] h-8 hidden md:flex items-center px-4 lg:px-8 text-xs tracking-[0.32px]">
  <div class="max-w-[1584px] w-full mx-auto flex items-center justify-between">
    <div class="flex items-center gap-6">
      <span class="flex items-center gap-1.5 text-[#161616] font-medium">
        <Store class="w-3.5 h-3.5 text-[#0f62fe]" />
        Solusi Khusus Kedai Kopi, Kafe &amp; Resto Modern
      </span>
      <span class="text-[#8c8c8c]">|</span>
      <span class="flex items-center gap-1.5 text-[#525252]">
        <ShieldCheck class="w-3.5 h-3.5 text-[#24a148]" />
        Garansi Transaksi Kasir Tetap Berjalan Saat Internet Mati
      </span>
    </div>

    <div class="flex items-center gap-6">
      <a href="#faq" class="text-[#525252] hover:text-[#161616] transition-colors">
        Pusat Bantuan &amp; FAQ
      </a>
      <span class="text-[#8c8c8c]">|</span>
      <span class="flex items-center gap-1.5 text-[#525252]">
        <PhoneCall class="w-3 h-3 text-[#0f62fe]" />
        Konsultasi WhatsApp: 0812-3456-7890
      </span>
    </div>
  </div>
</div>
