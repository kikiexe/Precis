<script lang="ts">
  import { LayoutDashboard, Package, Users, Wallet, Camera, Calendar, Settings, User as UserIcon } from 'lucide-svelte';
  import type { Role } from '../../types/app';

  interface Props {
    role: Role;
    activeDomain: 'home' | 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings' | 'presensi' | 'shift';
    pendingApprovalsCount: number;
    onSelectNav: (domain: 'home' | 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings' | 'presensi' | 'shift') => void;
  }

  let { role, activeDomain = 'dashboard', pendingApprovalsCount = 0, onSelectNav }: Props = $props();
</script>

<nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-[#d9d9dd] px-4 py-2 flex items-center justify-around shadow-lg font-sans">
  {#if role === 'OWNER' || role === 'ADMIN'}
    <!-- Home / Dashboard -->
    <button
      type="button"
      onclick={() => onSelectNav('dashboard')}
      class={`flex flex-col items-center gap-1 transition-all cursor-pointer ${
        activeDomain === 'dashboard' ? 'text-[#17171c]' : 'text-[#75758a] hover:text-[#212121]'
      }`}
    >
      <LayoutDashboard class="w-5 h-5" />
      <span class="text-[10px] font-medium">Home</span>
    </button>

    <!-- Katalog -->
    <button
      type="button"
      onclick={() => onSelectNav('katalog')}
      class={`flex flex-col items-center gap-1 transition-all cursor-pointer ${
        activeDomain === 'katalog' ? 'text-[#17171c]' : 'text-[#75758a] hover:text-[#212121]'
      }`}
    >
      <Package class="w-5 h-5" />
      <span class="text-[10px] font-medium">Katalog</span>
    </button>

    <!-- Tim -->
    <button
      type="button"
      onclick={() => onSelectNav('tim')}
      class={`relative flex flex-col items-center gap-1 transition-all cursor-pointer ${
        activeDomain === 'tim' ? 'text-[#17171c]' : 'text-[#75758a] hover:text-[#212121]'
      }`}
    >
      <Users class="w-5 h-5" />
      <span class="text-[10px] font-medium">Tim</span>
      {#if pendingApprovalsCount > 0}
        <span class="absolute -top-1 right-1 w-2 h-2 rounded-full bg-[#e5484d]"></span>
      {/if}
    </button>

    <!-- Finansial -->
    <button
      type="button"
      onclick={() => onSelectNav('finance')}
      class={`flex flex-col items-center gap-1 transition-all cursor-pointer ${
        activeDomain === 'finance' ? 'text-[#17171c]' : 'text-[#75758a] hover:text-[#212121]'
      }`}
    >
      <Wallet class="w-5 h-5" />
      <span class="text-[10px] font-medium">Finansial</span>
    </button>

    <!-- Pengaturan -->
    <button
      type="button"
      onclick={() => onSelectNav('settings')}
      class={`flex flex-col items-center gap-1 transition-all cursor-pointer ${
        activeDomain === 'settings' ? 'text-[#17171c]' : 'text-[#75758a] hover:text-[#212121]'
      }`}
    >
      <Settings class="w-5 h-5" />
      <span class="text-[10px] font-medium">Sistem</span>
    </button>
  {:else}
    <!-- Staff Mobile Portal: Beranda, Presensi, Shift, Profil -->
    <button
      type="button"
      onclick={() => onSelectNav('home')}
      class={`flex flex-col items-center gap-1 transition-all cursor-pointer ${
        activeDomain === 'home' ? 'text-[#17171c]' : 'text-[#75758a] hover:text-[#212121]'
      }`}
    >
      <LayoutDashboard class="w-5 h-5" />
      <span class="text-[10px] font-medium">Beranda</span>
    </button>

    <button
      type="button"
      onclick={() => onSelectNav('presensi')}
      class={`flex flex-col items-center gap-1 transition-all cursor-pointer ${
        activeDomain === 'presensi' ? 'text-[#17171c]' : 'text-[#75758a] hover:text-[#212121]'
      }`}
    >
      <Camera class="w-5 h-5" />
      <span class="text-[10px] font-medium">Presensi</span>
    </button>

    <button
      type="button"
      onclick={() => onSelectNav('shift')}
      class={`flex flex-col items-center gap-1 transition-all cursor-pointer ${
        activeDomain === 'shift' ? 'text-[#17171c]' : 'text-[#75758a] hover:text-[#212121]'
      }`}
    >
      <Calendar class="w-5 h-5" />
      <span class="text-[10px] font-medium">Shift</span>
    </button>

    <button
      type="button"
      onclick={() => onSelectNav('finance')}
      class={`flex flex-col items-center gap-1 transition-all cursor-pointer ${
        activeDomain === 'finance' ? 'text-[#17171c]' : 'text-[#75758a] hover:text-[#212121]'
      }`}
    >
      <UserIcon class="w-5 h-5" />
      <span class="text-[10px] font-medium">Profil</span>
    </button>
  {/if}
</nav>
