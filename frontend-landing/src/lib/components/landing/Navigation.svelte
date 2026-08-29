<script lang="ts">
  import { onMount } from 'svelte';
  import { Menu, X } from 'lucide-svelte';

  let isScrolled = $state(false);
  let isMobileMenuOpen = $state(false);

  const navLinks = [
    { name: 'Fitur Unggulan', href: '#features' },
    { name: 'Cara Kerja', href: '#how-it-works' },
    { name: 'Keunggulan', href: '#infrastructure' },
    { name: 'Harga Paket', href: '#pricing' },
  ];

  onMount(() => {
    const handleScroll = () => {
      isScrolled = window.scrollY > 20;
    };
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  });
</script>

<header
  class={`fixed z-50 transition-all duration-500 ${
    isScrolled ? 'inset-x-4 top-4' : 'inset-x-0 top-0'
  }`}
>
  <nav
    class={`mx-auto transition-all duration-500 ${
      isScrolled
        ? 'max-w-300 rounded-2xl border border-[#e0e0e0] bg-white/85 shadow-sm backdrop-blur-xl'
        : 'max-w-350 bg-transparent'
    }`}
  >
    <div
      class={`flex items-center justify-between px-6 transition-all duration-500 lg:px-8 ${
        isScrolled ? 'h-14' : 'h-20'
      }`}
    >
      <!-- Logo -->
      <a href="/" class="group flex items-center gap-2.5">
        <img
          src="/logo.png"
          alt="Précis Logo"
          class="size-7 rounded-xl border border-[#e0e0e0] object-cover"
        />
        <span
          class={`font-display font-bold tracking-tight text-[#161616] transition-all duration-500 group-hover:text-[#0f62fe] ${
            isScrolled ? 'text-xl' : 'text-2xl'
          }`}
        >
          Précis
        </span>
      </a>

      <!-- Desktop Navigation -->
      <div class="hidden items-center gap-10 md:flex">
        {#each navLinks as link}
          <a
            href={link.href}
            class="group relative text-sm text-[#525252] transition-colors duration-300 hover:text-[#161616]"
          >
            {link.name}
            <span
              class="absolute -bottom-1 left-0 h-0.5 w-0 bg-[#0f62fe] transition-all duration-300 group-hover:w-full"
            ></span>
          </a>
        {/each}
      </div>

      <!-- Desktop CTA -->
      <div class="hidden items-center gap-4 md:flex">
        <a
          href="https://app.precis.com/login"
          class={`text-[#525252] transition-all duration-500 hover:text-[#161616] ${
            isScrolled ? 'text-xs' : 'text-sm'
          }`}
        >
          Masuk Portal
        </a>
        <a
          href="https://app.precis.com/register"
          class={`inline-flex items-center justify-center rounded-full bg-[#0f62fe] font-medium text-white transition-all duration-500 hover:bg-[#0050e6] ${
            isScrolled ? 'h-8 px-4 text-xs' : 'h-10 px-6 text-sm'
          }`}
        >
          Coba Gratis 1 Bulan
        </a>
      </div>

      <!-- Mobile Menu Hamburger Button -->
      <button
        type="button"
        onclick={() => (isMobileMenuOpen = !isMobileMenuOpen)}
        class="rounded-lg p-2 text-[#161616] transition-colors hover:bg-[#f4f4f4] md:hidden"
        aria-label="Toggle menu"
      >
        {#if isMobileMenuOpen}
          <X class="size-6" />
        {:else}
          <Menu class="size-6" />
        {/if}
      </button>
    </div>
  </nav>

  <!-- Mobile Menu - Full Screen Overlay with Header Bar & Close Button -->
  <div
    class={`fixed inset-0 z-50 bg-white transition-all duration-300 md:hidden ${
      isMobileMenuOpen ? 'pointer-events-auto opacity-100' : 'pointer-events-none opacity-0'
    }`}
  >
    <div class="flex h-full flex-col justify-between px-6 pt-5 pb-8">
      <!-- Top Bar inside Mobile Overlay -->
      <div class="flex items-center justify-between border-b border-[#f0f0f0] pb-4">
        <a href="/" onclick={() => (isMobileMenuOpen = false)} class="flex items-baseline gap-2">
          <span class="font-display text-2xl font-bold tracking-tight text-[#161616]">
            Précis
          </span>
        </a>
        <button
          type="button"
          onclick={() => (isMobileMenuOpen = false)}
          class="rounded-full p-2 text-[#161616] transition-colors hover:bg-[#f4f4f4]"
          aria-label="Tutup menu navigasi"
        >
          <X class="size-6" />
        </button>
      </div>

      <!-- Navigation Links -->
      <div class="flex flex-1 flex-col justify-center gap-7 py-4">
        {#each navLinks as link, i}
          <a
            href={link.href}
            onclick={() => (isMobileMenuOpen = false)}
            class={`font-display text-3xl text-[#161616] transition-all duration-300 hover:text-[#0f62fe] ${
              isMobileMenuOpen ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'
            }`}
            style={`transition-delay: ${isMobileMenuOpen ? `${i * 50}ms` : '0ms'}`}
          >
            {link.name}
          </a>
        {/each}
      </div>

      <!-- Bottom Action Buttons -->
      <div
        class={`flex gap-3 border-t border-[#e0e0e0] pt-6 transition-all duration-300 ${
          isMobileMenuOpen ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'
        }`}
      >
        <a
          href="https://app.precis.com/login"
          onclick={() => (isMobileMenuOpen = false)}
          class="flex h-12 flex-1 items-center justify-center rounded-full border border-[#e0e0e0] text-sm font-medium text-[#161616] transition-colors hover:bg-[#f4f4f4]"
        >
          Masuk Portal
        </a>
        <a
          href="https://app.precis.com/register"
          onclick={() => (isMobileMenuOpen = false)}
          class="flex h-12 flex-1 items-center justify-center rounded-full bg-[#0f62fe] text-sm font-medium text-white transition-colors hover:bg-[#0050e6]"
        >
          Coba Gratis 1 Bulan
        </a>
      </div>
    </div>
  </div>
</header>
