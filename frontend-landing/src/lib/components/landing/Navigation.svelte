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
      isScrolled
        ? "bg-white/85 backdrop-blur-xl border border-[#e0e0e0] rounded-2xl shadow-sm max-w-300"
        : "bg-transparent max-w-350"
    }`}
  >
    <div
      class={`flex items-center justify-between transition-all duration-500 px-6 lg:px-8 ${
        isScrolled ? "h-14" : "h-20"
      }`}
    >
      <!-- Logo -->
      <a href="/" class="flex items-center gap-2.5 group">
        <img src="/logo.png" alt="Précis Logo" class="w-7 h-7 rounded-xl object-cover border border-[#e0e0e0]" />
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

      <!-- Mobile Menu Hamburger Button -->
      <button
        type="button"
        onclick={() => (isMobileMenuOpen = !isMobileMenuOpen)}
        class="md:hidden p-2 text-[#161616] hover:bg-[#f4f4f4] rounded-lg transition-colors"
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

  <!-- Mobile Menu - Full Screen Overlay with Header Bar & Close Button -->
  <div
    class={`md:hidden fixed inset-0 bg-white z-50 transition-all duration-300 ${
      isMobileMenuOpen
        ? "opacity-100 pointer-events-auto"
        : "opacity-0 pointer-events-none"
    }`}
  >
    <div class="flex flex-col h-full px-6 pt-5 pb-8 justify-between">
      <!-- Top Bar inside Mobile Overlay -->
      <div class="flex items-center justify-between pb-4 border-b border-[#f0f0f0]">
        <a
          href="/"
          onclick={() => (isMobileMenuOpen = false)}
          class="flex items-baseline gap-2"
        >
          <span class="font-display tracking-tight font-bold text-2xl text-[#161616]">
            Précis
          </span>
        </a>
        <button
          type="button"
          onclick={() => (isMobileMenuOpen = false)}
          class="p-2 text-[#161616] hover:bg-[#f4f4f4] rounded-full transition-colors"
          aria-label="Tutup menu navigasi"
        >
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Navigation Links -->
      <div class="flex-1 flex flex-col justify-center gap-7 py-4">
        {#each navLinks as link, i}
          <a
            href={link.href}
            onclick={() => (isMobileMenuOpen = false)}
            class={`text-3xl font-display text-[#161616] hover:text-[#0f62fe] transition-all duration-300 ${
              isMobileMenuOpen ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
            }`}
            style={`transition-delay: ${isMobileMenuOpen ? `${i * 50}ms` : "0ms"}`}
          >
            {link.name}
          </a>
        {/each}
      </div>

      <!-- Bottom Action Buttons -->
      <div
        class={`flex gap-3 pt-6 border-t border-[#e0e0e0] transition-all duration-300 ${
          isMobileMenuOpen ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
        }`}
      >
        <a
          href="https://app.precis.com/login"
          onclick={() => (isMobileMenuOpen = false)}
          class="flex-1 rounded-full h-12 text-sm border border-[#e0e0e0] text-[#161616] flex items-center justify-center font-medium hover:bg-[#f4f4f4] transition-colors"
        >
          Masuk Portal
        </a>
        <a
          href="https://app.precis.com/register"
          onclick={() => (isMobileMenuOpen = false)}
          class="flex-1 bg-[#0f62fe] hover:bg-[#0050e6] text-white rounded-full h-12 text-sm flex items-center justify-center font-medium transition-colors"
        >
          Coba Gratis 1 Bulan
        </a>
      </div>
    </div>
  </div>
</header>
