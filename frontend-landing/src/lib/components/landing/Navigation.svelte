<script lang="ts">
  import { onMount } from "svelte";
  import { Menu, X } from "lucide-svelte";

  let isScrolled = $state(false);
  let isMobileMenuOpen = $state(false);

  const navLinks = [
    { name: "Fitur Unggulan", href: "#features" },
    { name: "Cara Kerja", href: "#how-it-works" },
    { name: "Keunggulan", href: "#infrastructure" },
    { name: "Harga Paket", href: "#pricing" },
  ];

  onMount(() => {
    const handleScroll = () => {
      isScrolled = window.scrollY > 20;
    };
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  });
</script>

<header
  class={`fixed z-50 transition-all duration-500 ${
    isScrolled ? "top-4 left-4 right-4" : "top-0 left-0 right-0"
  }`}
>
  <nav
    class={`mx-auto transition-all duration-500 ${
      isScrolled || isMobileMenuOpen
        ? "bg-white/85 backdrop-blur-xl border border-[#e0e0e0] rounded-2xl shadow-sm max-w-[1200px]"
        : "bg-transparent max-w-[1400px]"
    }`}
  >
    <div
      class={`flex items-center justify-between transition-all duration-500 px-6 lg:px-8 ${
        isScrolled ? "h-14" : "h-20"
      }`}
    >
      <!-- Logo -->
      <a href="/" class="flex items-baseline gap-2 group">
        <span
          class={`font-display tracking-tight font-bold transition-all duration-500 text-[#161616] group-hover:text-[#0f62fe] ${
            isScrolled ? "text-xl" : "text-2xl"
          }`}
        >
          Précis
        </span>
      </a>

      <!-- Desktop Navigation -->
      <div class="hidden md:flex items-center gap-10">
        {#each navLinks as link}
          <a
            href={link.href}
            class="text-sm text-[#525252] hover:text-[#161616] transition-colors duration-300 relative group"
          >
            {link.name}
            <span
              class="absolute -bottom-1 left-0 w-0 h-0.5 bg-[#0f62fe] transition-all duration-300 group-hover:w-full"
            ></span>
          </a>
        {/each}
      </div>

      <!-- Desktop CTA -->
      <div class="hidden md:flex items-center gap-4">
        <a
          href="https://app.precis.com/login"
          class={`text-[#525252] hover:text-[#161616] transition-all duration-500 ${
            isScrolled ? "text-xs" : "text-sm"
          }`}
        >
          Masuk Portal
        </a>
        <a
          href="https://app.precis.com/register"
          class={`inline-flex items-center justify-center bg-[#0f62fe] hover:bg-[#0050e6] text-white rounded-full transition-all duration-500 font-medium ${
            isScrolled ? "px-4 h-8 text-xs" : "px-6 h-10 text-sm"
          }`}
        >
          Coba Gratis 1 Bulan
        </a>
      </div>

      <!-- Mobile Menu Button -->
      <button
        type="button"
        onclick={() => (isMobileMenuOpen = !isMobileMenuOpen)}
        class="md:hidden p-2 text-[#161616]"
        aria-label="Toggle menu"
      >
        {#if isMobileMenuOpen}
          <X class="w-6 h-6" />
        {:else}
          <Menu class="w-6 h-6" />
        {/if}
      </button>
    </div>
  </nav>

  <!-- Mobile Menu - Full Screen Overlay -->
  <div
    class={`md:hidden fixed inset-0 bg-white z-40 transition-all duration-500 ${
      isMobileMenuOpen
        ? "opacity-100 pointer-events-auto"
        : "opacity-0 pointer-events-none"
    }`}
    style="top: 0;"
  >
    <div class="flex flex-col h-full px-8 pt-28 pb-8">
      <!-- Navigation Links -->
      <div class="flex-1 flex flex-col justify-center gap-8">
        {#each navLinks as link, i}
          <a
            href={link.href}
            onclick={() => (isMobileMenuOpen = false)}
            class={`text-4xl font-display text-[#161616] hover:text-[#0f62fe] transition-all duration-500 ${
              isMobileMenuOpen ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
            }`}
            style={`transition-delay: ${isMobileMenuOpen ? `${i * 75}ms` : "0ms"}`}
          >
            {link.name}
          </a>
        {/each}
      </div>

      <!-- Bottom CTAs -->
      <div
        class={`flex gap-4 pt-8 border-t border-[#e0e0e0] transition-all duration-500 ${
          isMobileMenuOpen ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
        }`}
        style={`transition-delay: ${isMobileMenuOpen ? "300ms" : "0ms"}`}
      >
        <a
          href="https://app.precis.com/login"
          onclick={() => (isMobileMenuOpen = false)}
          class="flex-1 rounded-full h-14 text-base border border-[#e0e0e0] text-[#161616] flex items-center justify-center font-medium"
        >
          Masuk Portal
        </a>
        <a
          href="https://app.precis.com/register"
          onclick={() => (isMobileMenuOpen = false)}
          class="flex-1 bg-[#0f62fe] hover:bg-[#0050e6] text-white rounded-full h-14 text-base flex items-center justify-center font-medium"
        >
          Coba Gratis 1 Bulan
        </a>
      </div>
    </div>
  </div>
</header>
