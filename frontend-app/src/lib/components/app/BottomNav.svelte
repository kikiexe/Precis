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

<nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/90 backdrop-blur-xl border-t border-[#e5e5ea] px-3 py-2 flex items-center justify-around shadow-sm font-sans pb-safe">
  {#if role === 'OWNER' || role === 'ADMIN' || role === 'MANAGER'}
    <!-- Home / Dashboard -->
    <button
      type="button"
      onclick={() => onSelectNav('dashboard')}
      class={`flex flex-col items-center justify-center py-1 px-3 rounded-2xl transition-all duration-200 cursor-pointer min-w-[56px] ${
        activeDomain === 'dashboard'
          ? 'text-[#17171c] font-bold'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div class={`p-1 rounded-xl transition-all ${activeDomain === 'dashboard' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}>
        <LayoutDashboard class="w-4 h-4" />
      </div>
      <span class="text-[10px] mt-0.5 tracking-tight">Ringkasan</span>
    </button>

    <!-- Katalog -->
    <button
      type="button"
      onclick={() => onSelectNav('katalog')}
      class={`flex flex-col items-center justify-center py-1 px-3 rounded-2xl transition-all duration-200 cursor-pointer min-w-[56px] ${
        activeDomain === 'katalog'
          ? 'text-[#17171c] font-bold'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div class={`p-1 rounded-xl transition-all ${activeDomain === 'katalog' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}>
        <Package class="w-4 h-4" />
      </div>
      <span class="text-[10px] mt-0.5 tracking-tight">Katalog</span>
    </button>

    <!-- Tim -->
    <button
      type="button"
      onclick={() => onSelectNav('tim')}
      class={`relative flex flex-col items-center justify-center py-1 px-3 rounded-2xl transition-all duration-200 cursor-pointer min-w-[56px] ${
        activeDomain === 'tim'
          ? 'text-[#17171c] font-bold'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div class={`p-1 rounded-xl transition-all relative ${activeDomain === 'tim' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}>
        <Users class="w-4 h-4" />
        {#if pendingApprovalsCount > 0}
          <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-[#e5484d] ring-2 ring-white"></span>
        {/if}
      </div>
      <span class="text-[10px] mt-0.5 tracking-tight">Tim</span>
    </button>

    <!-- Finansial -->
    <button
      type="button"
      onclick={() => onSelectNav('finance')}
      class={`flex flex-col items-center justify-center py-1 px-3 rounded-2xl transition-all duration-200 cursor-pointer min-w-[56px] ${
        activeDomain === 'finance'
          ? 'text-[#17171c] font-bold'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div class={`p-1 rounded-xl transition-all ${activeDomain === 'finance' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}>
        <Wallet class="w-4 h-4" />
      </div>
      <span class="text-[10px] mt-0.5 tracking-tight">Keuangan</span>
    </button>

    <!-- Pengaturan -->
    <button
      type="button"
      onclick={() => onSelectNav('settings')}
      class={`flex flex-col items-center justify-center py-1 px-3 rounded-2xl transition-all duration-200 cursor-pointer min-w-[56px] ${
        activeDomain === 'settings'
          ? 'text-[#17171c] font-bold'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div class={`p-1 rounded-xl transition-all ${activeDomain === 'settings' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}>
        <Settings class="w-4 h-4" />
      </div>
      <span class="text-[10px] mt-0.5 tracking-tight">Sistem</span>
    </button>
  {:else}
    <!-- Staff Mobile Portal -->
    <button
      type="button"
      onclick={() => onSelectNav('home')}
      class={`flex flex-col items-center justify-center py-1 px-3 rounded-2xl transition-all duration-200 cursor-pointer min-w-[56px] ${
        activeDomain === 'home'
          ? 'text-[#17171c] font-bold'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div class={`p-1 rounded-xl transition-all ${activeDomain === 'home' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}>
        <LayoutDashboard class="w-4 h-4" />
      </div>
      <span class="text-[10px] mt-0.5 tracking-tight">Beranda</span>
    </button>

    <button
      type="button"
      onclick={() => onSelectNav('presensi')}
      class={`flex flex-col items-center justify-center py-1 px-3 rounded-2xl transition-all duration-200 cursor-pointer min-w-[56px] ${
        activeDomain === 'presensi'
          ? 'text-[#17171c] font-bold'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div class={`p-1 rounded-xl transition-all ${activeDomain === 'presensi' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}>
        <Camera class="w-4 h-4" />
      </div>
      <span class="text-[10px] mt-0.5 tracking-tight">Presensi</span>
    </button>

    <button
      type="button"
      onclick={() => onSelectNav('shift')}
      class={`flex flex-col items-center justify-center py-1 px-3 rounded-2xl transition-all duration-200 cursor-pointer min-w-[56px] ${
        activeDomain === 'shift'
          ? 'text-[#17171c] font-bold'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div class={`p-1 rounded-xl transition-all ${activeDomain === 'shift' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}>
        <Calendar class="w-4 h-4" />
      </div>
      <span class="text-[10px] mt-0.5 tracking-tight">Shift</span>
    </button>

    <button
      type="button"
      onclick={() => onSelectNav('finance')}
      class={`flex flex-col items-center justify-center py-1 px-3 rounded-2xl transition-all duration-200 cursor-pointer min-w-[56px] ${
        activeDomain === 'finance'
          ? 'text-[#17171c] font-bold'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div class={`p-1 rounded-xl transition-all ${activeDomain === 'finance' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}>
        <UserIcon class="w-4 h-4" />
      </div>
      <span class="text-[10px] mt-0.5 tracking-tight">Profil</span>
    </button>
  {/if}
</nav>
