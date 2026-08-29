<script lang="ts">
  import {
    LayoutDashboard,
    Package,
    Users,
    Wallet,
    Camera,
    Calendar,
    Settings,
    User as UserIcon,
  } from 'lucide-svelte';
  import type { Role } from '../../types/app';

  interface Props {
    role: Role;
    activeDomain:
      'home' | 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings' | 'presensi' | 'shift';
    pendingApprovalsCount: number;
    onSelectNav: (
      domain:
        'home' | 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings' | 'presensi' | 'shift'
    ) => void;
  }

  let {
    role,
    activeDomain = 'dashboard',
    pendingApprovalsCount = 0,
    onSelectNav,
  }: Props = $props();
</script>

<nav
  class="pb-safe fixed inset-x-0 bottom-0 z-40 flex items-center justify-around border-t border-[#e5e5ea] bg-white/90 px-3 py-2 font-sans shadow-sm backdrop-blur-xl lg:hidden"
>
  {#if role === 'OWNER' || role === 'ADMIN' || role === 'MANAGER'}
    <!-- Home / Dashboard -->
    <button
      type="button"
      onclick={() => onSelectNav('dashboard')}
      class={`flex min-w-14 cursor-pointer flex-col items-center justify-center rounded-2xl px-3 py-1 transition-all duration-200 ${
        activeDomain === 'dashboard'
          ? 'font-bold text-[#17171c]'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div
        class={`rounded-xl p-1 transition-all ${activeDomain === 'dashboard' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}
      >
        <LayoutDashboard class="size-4" />
      </div>
      <span class="mt-0.5 text-[10px] tracking-tight">Ringkasan</span>
    </button>

    <!-- Katalog -->
    <button
      type="button"
      onclick={() => onSelectNav('katalog')}
      class={`flex min-w-14 cursor-pointer flex-col items-center justify-center rounded-2xl px-3 py-1 transition-all duration-200 ${
        activeDomain === 'katalog'
          ? 'font-bold text-[#17171c]'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div
        class={`rounded-xl p-1 transition-all ${activeDomain === 'katalog' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}
      >
        <Package class="size-4" />
      </div>
      <span class="mt-0.5 text-[10px] tracking-tight">Katalog</span>
    </button>

    <!-- Tim -->
    <button
      type="button"
      onclick={() => onSelectNav('tim')}
      class={`relative flex min-w-14 cursor-pointer flex-col items-center justify-center rounded-2xl px-3 py-1 transition-all duration-200 ${
        activeDomain === 'tim' ? 'font-bold text-[#17171c]' : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div
        class={`relative rounded-xl p-1 transition-all ${activeDomain === 'tim' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}
      >
        <Users class="size-4" />
        {#if pendingApprovalsCount > 0}
          <span
            class="absolute -top-0.5 -right-0.5 size-2 rounded-full bg-[#e5484d] ring-2 ring-white"
          ></span>
        {/if}
      </div>
      <span class="mt-0.5 text-[10px] tracking-tight">Tim</span>
    </button>

    <!-- Finansial -->
    <button
      type="button"
      onclick={() => onSelectNav('finance')}
      class={`flex min-w-14 cursor-pointer flex-col items-center justify-center rounded-2xl px-3 py-1 transition-all duration-200 ${
        activeDomain === 'finance'
          ? 'font-bold text-[#17171c]'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div
        class={`rounded-xl p-1 transition-all ${activeDomain === 'finance' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}
      >
        <Wallet class="size-4" />
      </div>
      <span class="mt-0.5 text-[10px] tracking-tight">Keuangan</span>
    </button>

    <!-- Pengaturan -->
    <button
      type="button"
      onclick={() => onSelectNav('settings')}
      class={`flex min-w-14 cursor-pointer flex-col items-center justify-center rounded-2xl px-3 py-1 transition-all duration-200 ${
        activeDomain === 'settings'
          ? 'font-bold text-[#17171c]'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div
        class={`rounded-xl p-1 transition-all ${activeDomain === 'settings' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}
      >
        <Settings class="size-4" />
      </div>
      <span class="mt-0.5 text-[10px] tracking-tight">Sistem</span>
    </button>
  {:else}
    <!-- Staff Mobile Portal -->
    <button
      type="button"
      onclick={() => onSelectNav('home')}
      class={`flex min-w-14 cursor-pointer flex-col items-center justify-center rounded-2xl px-3 py-1 transition-all duration-200 ${
        activeDomain === 'home' ? 'font-bold text-[#17171c]' : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div
        class={`rounded-xl p-1 transition-all ${activeDomain === 'home' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}
      >
        <LayoutDashboard class="size-4" />
      </div>
      <span class="mt-0.5 text-[10px] tracking-tight">Beranda</span>
    </button>

    <button
      type="button"
      onclick={() => onSelectNav('presensi')}
      class={`flex min-w-14 cursor-pointer flex-col items-center justify-center rounded-2xl px-3 py-1 transition-all duration-200 ${
        activeDomain === 'presensi'
          ? 'font-bold text-[#17171c]'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div
        class={`rounded-xl p-1 transition-all ${activeDomain === 'presensi' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}
      >
        <Camera class="size-4" />
      </div>
      <span class="mt-0.5 text-[10px] tracking-tight">Presensi</span>
    </button>

    <button
      type="button"
      onclick={() => onSelectNav('shift')}
      class={`flex min-w-14 cursor-pointer flex-col items-center justify-center rounded-2xl px-3 py-1 transition-all duration-200 ${
        activeDomain === 'shift'
          ? 'font-bold text-[#17171c]'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div
        class={`rounded-xl p-1 transition-all ${activeDomain === 'shift' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}
      >
        <Calendar class="size-4" />
      </div>
      <span class="mt-0.5 text-[10px] tracking-tight">Shift</span>
    </button>

    <button
      type="button"
      onclick={() => onSelectNav('finance')}
      class={`flex min-w-14 cursor-pointer flex-col items-center justify-center rounded-2xl px-3 py-1 transition-all duration-200 ${
        activeDomain === 'finance'
          ? 'font-bold text-[#17171c]'
          : 'text-[#8e8e93] hover:text-[#17171c]'
      }`}
    >
      <div
        class={`rounded-xl p-1 transition-all ${activeDomain === 'finance' ? 'bg-[#17171c] text-white shadow-xs' : ''}`}
      >
        <UserIcon class="size-4" />
      </div>
      <span class="mt-0.5 text-[10px] tracking-tight">Profil</span>
    </button>
  {/if}
</nav>
