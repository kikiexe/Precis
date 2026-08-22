<script lang="ts">
  import { Menu, X, ArrowUpRight } from 'lucide-svelte';

  let isMobileMenuOpen = $state(false);

  const navLinks = [
    { label: 'Fitur Utama', href: '#features' },
    { label: 'Cara Kerja', href: '#how-it-works' },
    { label: 'Kasir POS', href: '#pos' },
    { label: 'Presensi Karyawan', href: '#attendance' },
    { label: 'Payroll Otomatis', href: '#payroll' },
    { label: 'Paket Harga', href: '#pricing' },
    { label: 'FAQ', href: '#faq' }
  ];
</script>

<header class="sticky top-0 z-50 bg-white border-b border-[#e0e0e0] h-12">
  <div class="max-w-[1584px] h-full mx-auto px-4 lg:px-8 flex items-center justify-between">
    <!-- Brand Mark -->
    <a href="/" class="flex items-baseline gap-2 group">
      <span class="text-xl font-bold tracking-tight text-[#161616] group-hover:text-[#0f62fe] transition-colors">
        Précis
      </span>
      <span class="text-xs font-mono text-[#8c8c8c]">F&amp;B</span>
    </a>

    <!-- Desktop Navigation Links -->
    <nav class="hidden lg:flex items-center gap-7 h-full">
      {#each navLinks as link}
        <a
          href={link.href}
          class="h-full inline-flex items-center text-sm text-[#525252] hover:text-[#161616] border-b-2 border-transparent hover:border-[#0f62fe] transition-all"
        >
          {link.label}
        </a>
      {/each}
    </nav>

    <!-- Desktop Action Buttons -->
    <div class="hidden sm:flex items-center gap-3">
      <a
        href="https://app.precis.com/login"
        class="h-9 px-4 inline-flex items-center text-sm text-[#161616] hover:bg-[#f4f4f4] transition-colors"
      >
        Masuk Portal
      </a>
      <a
        href="https://app.precis.com/register"
        class="h-9 px-4 inline-flex items-center gap-1.5 text-sm text-white bg-[#0f62fe] hover:bg-[#0050e6] active:bg-[#002d9c] transition-colors"
      >
        Coba 14 Hari Gratis
        <ArrowUpRight class="w-3.5 h-3.5" />
      </a>
    </div>

    <!-- Mobile Hamburger Toggle -->
    <button
      type="button"
      onclick={() => (isMobileMenuOpen = !isMobileMenuOpen)}
      class="lg:hidden p-2 text-[#161616] hover:bg-[#f4f4f4]"
      aria-label="Menu navigasi"
    >
      {#if isMobileMenuOpen}
        <X class="w-5 h-5" />
      {:else}
        <Menu class="w-5 h-5" />
      {/if}
    </button>
  </div>

  <!-- Mobile Drawer Menu -->
  {#if isMobileMenuOpen}
    <div class="lg:hidden bg-white border-b border-[#e0e0e0] px-4 py-6 shadow-sm">
      <nav class="flex flex-col gap-4 mb-6">
        {#each navLinks as link}
          <a
            href={link.href}
            onclick={() => (isMobileMenuOpen = false)}
            class="text-base text-[#161616] hover:text-[#0f62fe] py-2 border-b border-[#f4f4f4]"
          >
            {link.label}
          </a>
        {/each}
      </nav>

      <div class="flex flex-col gap-3">
        <a
          href="https://app.precis.com/login"
          class="w-full text-center py-3 text-sm text-[#161616] bg-[#f4f4f4] border border-[#e0e0e0]"
        >
          Masuk Portal Admin
        </a>
        <a
          href="https://app.precis.com/register"
          class="w-full text-center py-3 text-sm text-white bg-[#0f62fe]"
        >
          Daftar Owner Baru (14 Hari Gratis)
        </a>
      </div>
    </div>
  {/if}
</header>
