<script lang="ts">
  import { onMount } from 'svelte';
  import {
    LayoutDashboard,
    Package,
    Users,
    Wallet,
    Settings,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    LogOut,
    Check,
    Plus,
    Camera,
    Calendar,
    User as UserIcon,
  } from 'lucide-svelte';
  import type { User, Role, UserWorkspace } from '../../types/app';

  interface Props {
    currentUser: User;
    userWorkspaces?: UserWorkspace[];
    activeWorkspaceId?: string | null;
    activeDomain:
      'home' | 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings' | 'presensi' | 'shift';
    activeSubTab?: string;
    pendingApprovalsCount: number;
    onSelectNav: (
      domain:
        'home' | 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings' | 'presensi' | 'shift',
      subTab?: string
    ) => void;
    onSwitchWorkspace?: (workspace: UserWorkspace) => void;
    onOpenCreateWorkspaceModal?: () => void;
    onLogout?: () => void;
  }

  let {
    currentUser,
    userWorkspaces = [],
    activeWorkspaceId = null,
    activeDomain = 'dashboard',
    activeSubTab: _activeSubTab = '',
    pendingApprovalsCount = 0,
    onSelectNav,
    onSwitchWorkspace,
    onOpenCreateWorkspaceModal,
    onLogout,
  }: Props = $props();

  let isCollapsed = $state(false);
  let isDropdownOpen = $state(false);

  onMount(() => {
    if (typeof window !== 'undefined') {
      const saved = localStorage.getItem('precis_sidebar_collapsed');
      if (saved !== null) {
        isCollapsed = saved === 'true';
      }
    }
  });

  function toggleSidebar() {
    isCollapsed = !isCollapsed;
    isDropdownOpen = false;
    if (typeof window !== 'undefined') {
      localStorage.setItem('precis_sidebar_collapsed', String(isCollapsed));
    }
  }

  function getRoleBadge(role: Role) {
    switch (role) {
      case 'OWNER':
        return { label: 'Owner', bg: 'bg-[#17171c] text-white', short: 'OW' };
      case 'ADMIN':
        return {
          label: 'Admin',
          bg: 'bg-[#eff6ff] text-[#2563eb] border border-[#bfdbfe]',
          short: 'AD',
        };
      case 'MANAGER':
        return {
          label: 'Manager',
          bg: 'bg-[#eff6ff] text-[#2563eb] border border-[#bfdbfe]',
          short: 'MG',
        };
      case 'STAFF':
        return {
          label: 'Staf',
          bg: 'bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0]',
          short: 'ST',
        };
      case 'SUPERADMIN':
        return {
          label: 'Superadmin',
          bg: 'bg-[#f4f4f6] text-[#17171c] border border-[#e5e5ea]',
          short: 'SA',
        };
      default:
        return {
          label: role,
          bg: 'bg-[#f4f4f6] text-[#686873] border border-[#e5e5ea]',
          short: 'US',
        };
    }
  }

  let badge = $derived(
    userWorkspaces.length === 0
      ? {
          label: 'Standalone',
          bg: 'bg-[#f4f4f6] text-[#686873] border border-[#e5e5ea]',
          short: 'SA',
        }
      : getRoleBadge(currentUser.role)
  );

  let currentWorkspaceName = $derived(
    userWorkspaces.find((w) => w.workspace_id === activeWorkspaceId)?.workspace_name ||
      (userWorkspaces.length === 0 ? 'Tanpa Workspace' : currentUser.branch_name)
  );

  let userInitial = $derived(currentUser.name ? currentUser.name.charAt(0).toUpperCase() : 'U');
</script>

<aside
  class={`sticky top-0 hidden h-screen shrink-0 flex-col justify-between border-r border-[#e5e5ea] bg-white font-sans shadow-2xs transition-[width] duration-200 ease-in-out select-none lg:flex ${
    isCollapsed ? 'w-18' : 'w-64'
  }`}
>
  <!-- Floating Edge Toggle Button in the Vertical Center of the Right Border (Mini & Maxi) -->
  <button
    type="button"
    onclick={toggleSidebar}
    class="group absolute top-1/2 -right-3.5 z-40 flex size-7 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-[#e5e5ea] bg-white text-[#17171c] shadow-md transition-all hover:scale-110 hover:bg-[#f4f4f6] active:scale-95"
    title={isCollapsed ? 'Besarkan Sidebar' : 'Kecilkan Sidebar'}
    aria-label={isCollapsed ? 'Besarkan Sidebar' : 'Kecilkan Sidebar'}
  >
    {#if isCollapsed}
      <ChevronRight
        class="size-4 text-[#17171c] transition-transform group-hover:translate-x-0.5"
      />
    {:else}
      <ChevronLeft
        class="size-4 text-[#17171c] transition-transform group-hover:-translate-x-0.5"
      />
    {/if}
  </button>

  <div class="flex min-h-0 flex-1 flex-col overflow-x-hidden overflow-y-auto">
    <!-- Header Brand -->
    <div
      class={`flex h-16 shrink-0 items-center border-b border-[#f2f2f4] transition-all ${
        isCollapsed ? 'justify-center px-3' : 'gap-2.5 px-4'
      }`}
    >
      <img
        src="/logo.png"
        alt="Précis Logo"
        class="size-8 shrink-0 rounded-xl border border-[#e5e5ea] object-cover"
      />
      {#if !isCollapsed}
        <div class="min-w-0">
          <div class="truncate text-sm font-bold tracking-tight text-[#17171c]">PRÉCIS APP</div>
          <div class="truncate font-mono text-[10px] tracking-wider text-[#8e8e93] uppercase">
            F&amp;B Operating System
          </div>
        </div>
      {/if}
    </div>

    <!-- User & Workspace Switcher -->
    <div
      class={`relative shrink-0 border-b border-[#f2f2f4] p-2.5 ${isCollapsed ? 'flex justify-center' : ''}`}
    >
      {#if !isCollapsed}
        <button
          type="button"
          onclick={() => (isDropdownOpen = !isDropdownOpen)}
          class="group flex w-full cursor-pointer items-center justify-between rounded-2xl border border-[#e5e5ea] bg-[#f8f8fa] p-2.5 text-left shadow-2xs transition-all hover:bg-[#f2f2f5]"
        >
          <div class="min-w-0 flex-1">
            <div class="truncate text-xs font-bold text-[#17171c]">{currentUser.name}</div>
            <div class="mt-0.5 flex items-center gap-1.5">
              <span
                class={`py-0.2 rounded-full px-1.5 font-mono text-[9.5px] font-semibold ${badge.bg}`}
              >
                {badge.label}
              </span>
              <span class="max-w-32 truncate text-[11px] font-normal text-[#8e8e93]"
                >&bull; {currentWorkspaceName}</span
              >
            </div>
          </div>
          <ChevronDown
            class="size-4 text-[#8e8e93] transition-transform duration-200 group-hover:text-[#17171c]"
          />
        </button>
      {:else}
        <!-- Minimized Avatar Button with Flyout Menu -->
        <div class="group relative">
          <button
            type="button"
            onclick={() => (isDropdownOpen = !isDropdownOpen)}
            class="relative flex size-10 cursor-pointer items-center justify-center rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] text-xs font-bold text-[#17171c] shadow-2xs transition-all hover:bg-[#eaeaea]"
            title={`${currentUser.name} (${badge.label}) - ${currentWorkspaceName}`}
            aria-label="Pilih Workspace"
          >
            <span>{userInitial}</span>
            <span
              class="absolute -right-0.5 -bottom-0.5 flex size-3.5 items-center justify-center rounded-full border border-white bg-[#17171c] font-mono text-[8px] font-bold text-white"
            >
              {badge.short.charAt(0)}
            </span>
          </button>

          <!-- Hover Tooltip in collapsed mode -->
          {#if !isDropdownOpen}
            <div
              class="pointer-events-none absolute top-1/2 left-full z-50 ml-3 -translate-y-1/2 rounded-xl bg-[#17171c] px-2.5 py-1.5 text-xs font-medium whitespace-nowrap text-white opacity-0 shadow-xl transition-all duration-150 group-hover:opacity-100"
            >
              <div class="text-[11.5px] font-bold">{currentUser.name}</div>
              <div class="font-mono text-[10px] text-white/60">
                {badge.label} &bull; {currentWorkspaceName}
              </div>
            </div>
          {/if}
        </div>
      {/if}

      <!-- Dropdown Workspace List -->
      {#if isDropdownOpen}
        <button
          type="button"
          class="fixed inset-0 z-40 cursor-default bg-black/5"
          onclick={() => (isDropdownOpen = false)}
          aria-label="Tutup Pilihan Workspace"
        ></button>

        <div
          class={`animate-in fade-in zoom-in-95 z-50 space-y-1 rounded-2xl border border-[#e5e5ea] bg-white p-2 shadow-xl ${
            isCollapsed
              ? 'absolute top-0 left-full ml-3 w-60'
              : 'absolute inset-x-2.5 top-full mt-2'
          }`}
        >
          {#if userWorkspaces.length > 0}
            <div
              class="px-2.5 py-1 font-mono text-[10px] font-semibold tracking-wider text-[#8e8e93] uppercase"
            >
              Pilih Workspace
            </div>
            {#each userWorkspaces as ws}
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onSwitchWorkspace?.(ws);
                }}
                class={`flex w-full cursor-pointer items-center justify-between rounded-xl px-3 py-2 text-left text-xs transition-colors ${
                  ws.workspace_id === activeWorkspaceId
                    ? 'bg-[#f4f4f6] font-bold text-[#17171c]'
                    : 'text-[#686873] hover:bg-[#fafafc]'
                }`}
              >
                <div class="min-w-0 flex-1">
                  <div class="truncate font-semibold">{ws.workspace_name}</div>
                  <div class="font-mono text-[10px] text-[#8e8e93]">{ws.role}</div>
                </div>
                {#if ws.workspace_id === activeWorkspaceId}
                  <Check class="size-3.5 text-[#17171c]" />
                {/if}
              </button>
            {/each}
          {/if}

          {#if onOpenCreateWorkspaceModal && (currentUser.role === 'OWNER' || currentUser.role === 'SUPERADMIN')}
            <div class="border-t border-[#f2f2f4] pt-1.5">
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onOpenCreateWorkspaceModal();
                }}
                class="flex w-full cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-left text-xs font-semibold text-[#2563eb] transition-colors hover:bg-[#eff6ff]"
              >
                <Plus class="size-3.5" />
                <span>Buat Workspace Baru</span>
              </button>
            </div>
          {/if}
        </div>
      {/if}
    </div>

    <!-- Navigation Menu Items -->
    <nav class={`flex-1 space-y-1.5 p-2.5 ${isCollapsed ? 'flex flex-col items-center' : ''}`}>
      {#if currentUser.role === 'OWNER' || currentUser.role === 'ADMIN' || currentUser.role === 'MANAGER'}
        {#if !isCollapsed}
          <div
            class="px-3 py-1 font-mono text-[10px] font-semibold tracking-wider text-[#8e8e93] uppercase"
          >
            Menu Utama
          </div>
        {/if}

        <!-- Dashboard Ringkasan -->
        <div class="group relative w-full">
          <button
            type="button"
            onclick={() => onSelectNav('dashboard')}
            class={`flex w-full cursor-pointer items-center rounded-xl py-2.5 text-left text-xs transition-all ${
              isCollapsed ? 'justify-center px-0' : 'gap-3 px-3.5'
            } ${
              activeDomain === 'dashboard'
                ? 'bg-[#17171c] font-bold text-white shadow-xs'
                : 'text-[#686873] hover:bg-[#f4f4f6] hover:text-[#17171c]'
            }`}
          >
            <LayoutDashboard class="size-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Dashboard Ringkasan</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div
              class="pointer-events-none absolute top-1/2 left-full z-50 ml-3 -translate-y-1/2 rounded-lg bg-[#17171c] px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-white opacity-0 shadow-xl transition-all duration-150 group-hover:opacity-100"
            >
              Dashboard Ringkasan
            </div>
          {/if}
        </div>

        <!-- Katalog Produk -->
        <div class="group relative w-full">
          <button
            type="button"
            onclick={() => onSelectNav('katalog')}
            class={`flex w-full cursor-pointer items-center rounded-xl py-2.5 text-left text-xs transition-all ${
              isCollapsed ? 'justify-center px-0' : 'gap-3 px-3.5'
            } ${
              activeDomain === 'katalog'
                ? 'bg-[#17171c] font-bold text-white shadow-xs'
                : 'text-[#686873] hover:bg-[#f4f4f6] hover:text-[#17171c]'
            }`}
          >
            <Package class="size-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Katalog Produk</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div
              class="pointer-events-none absolute top-1/2 left-full z-50 ml-3 -translate-y-1/2 rounded-lg bg-[#17171c] px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-white opacity-0 shadow-xl transition-all duration-150 group-hover:opacity-100"
            >
              Katalog Produk
            </div>
          {/if}
        </div>

        <!-- Manajemen Tim -->
        <div class="group relative w-full">
          <button
            type="button"
            onclick={() => onSelectNav('tim')}
            class={`flex w-full cursor-pointer items-center rounded-xl py-2.5 text-left text-xs transition-all ${
              isCollapsed ? 'relative justify-center px-0' : 'justify-between px-3.5'
            } ${
              activeDomain === 'tim'
                ? 'bg-[#17171c] font-bold text-white shadow-xs'
                : 'text-[#686873] hover:bg-[#f4f4f6] hover:text-[#17171c]'
            }`}
          >
            <div class={`flex items-center ${isCollapsed ? '' : 'gap-3 truncate'}`}>
              <Users class="size-4 shrink-0" />
              {#if !isCollapsed}
                <span class="truncate">Manajemen Tim</span>
              {/if}
            </div>

            {#if pendingApprovalsCount > 0}
              {#if !isCollapsed}
                <span
                  class="rounded-full bg-[#e5484d] px-2 py-0.5 font-mono text-[10px] font-bold text-white"
                >
                  {pendingApprovalsCount}
                </span>
              {:else}
                <span
                  class="absolute top-1.5 right-2 size-2.5 rounded-full border-2 border-white bg-[#e5484d]"
                ></span>
              {/if}
            {/if}
          </button>
          {#if isCollapsed}
            <div
              class="pointer-events-none absolute top-1/2 left-full z-50 ml-3 flex -translate-y-1/2 items-center gap-2 rounded-lg bg-[#17171c] px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-white opacity-0 shadow-xl transition-all duration-150 group-hover:opacity-100"
            >
              <span>Manajemen Tim</span>
              {#if pendingApprovalsCount > 0}
                <span
                  class="py-0.2 rounded-full bg-[#e5484d] px-1.5 font-mono text-[10px] text-white"
                >
                  {pendingApprovalsCount}
                </span>
              {/if}
            </div>
          {/if}
        </div>

        <!-- Keuangan & Payroll -->
        <div class="group relative w-full">
          <button
            type="button"
            onclick={() => onSelectNav('finance')}
            class={`flex w-full cursor-pointer items-center rounded-xl py-2.5 text-left text-xs transition-all ${
              isCollapsed ? 'justify-center px-0' : 'gap-3 px-3.5'
            } ${
              activeDomain === 'finance'
                ? 'bg-[#17171c] font-bold text-white shadow-xs'
                : 'text-[#686873] hover:bg-[#f4f4f6] hover:text-[#17171c]'
            }`}
          >
            <Wallet class="size-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Keuangan &amp; Payroll</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div
              class="pointer-events-none absolute top-1/2 left-full z-50 ml-3 -translate-y-1/2 rounded-lg bg-[#17171c] px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-white opacity-0 shadow-xl transition-all duration-150 group-hover:opacity-100"
            >
              Keuangan &amp; Payroll
            </div>
          {/if}
        </div>

        <!-- Pengaturan -->
        <div class="group relative w-full">
          <button
            type="button"
            onclick={() => onSelectNav('settings')}
            class={`flex w-full cursor-pointer items-center rounded-xl py-2.5 text-left text-xs transition-all ${
              isCollapsed ? 'justify-center px-0' : 'gap-3 px-3.5'
            } ${
              activeDomain === 'settings'
                ? 'bg-[#17171c] font-bold text-white shadow-xs'
                : 'text-[#686873] hover:bg-[#f4f4f6] hover:text-[#17171c]'
            }`}
          >
            <Settings class="size-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Pengaturan</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div
              class="pointer-events-none absolute top-1/2 left-full z-50 ml-3 -translate-y-1/2 rounded-lg bg-[#17171c] px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-white opacity-0 shadow-xl transition-all duration-150 group-hover:opacity-100"
            >
              Pengaturan
            </div>
          {/if}
        </div>
      {:else}
        <!-- STAFF ESS SIMPLE NAVIGATION -->
        {#if !isCollapsed}
          <div
            class="px-3 py-1 font-mono text-[10px] font-semibold tracking-wider text-[#8e8e93] uppercase"
          >
            Portal Karyawan
          </div>
        {/if}

        <div class="group relative w-full">
          <button
            type="button"
            onclick={() => onSelectNav('home')}
            class={`flex w-full cursor-pointer items-center rounded-xl py-2.5 text-left text-xs transition-all ${
              isCollapsed ? 'justify-center px-0' : 'gap-3 px-3.5'
            } ${
              activeDomain === 'home'
                ? 'bg-[#17171c] font-bold text-white shadow-xs'
                : 'text-[#686873] hover:bg-[#f4f4f6] hover:text-[#17171c]'
            }`}
          >
            <LayoutDashboard class="size-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Beranda Staf</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div
              class="pointer-events-none absolute top-1/2 left-full z-50 ml-3 -translate-y-1/2 rounded-lg bg-[#17171c] px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-white opacity-0 shadow-xl transition-all duration-150 group-hover:opacity-100"
            >
              Beranda Staf
            </div>
          {/if}
        </div>

        <div class="group relative w-full">
          <button
            type="button"
            onclick={() => onSelectNav('presensi')}
            class={`flex w-full cursor-pointer items-center rounded-xl py-2.5 text-left text-xs transition-all ${
              isCollapsed ? 'justify-center px-0' : 'gap-3 px-3.5'
            } ${
              activeDomain === 'presensi'
                ? 'bg-[#17171c] font-bold text-white shadow-xs'
                : 'text-[#686873] hover:bg-[#f4f4f6] hover:text-[#17171c]'
            }`}
          >
            <Camera class="size-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Presensi Harian</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div
              class="pointer-events-none absolute top-1/2 left-full z-50 ml-3 -translate-y-1/2 rounded-lg bg-[#17171c] px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-white opacity-0 shadow-xl transition-all duration-150 group-hover:opacity-100"
            >
              Presensi Harian
            </div>
          {/if}
        </div>

        <div class="group relative w-full">
          <button
            type="button"
            onclick={() => onSelectNav('shift')}
            class={`flex w-full cursor-pointer items-center rounded-xl py-2.5 text-left text-xs transition-all ${
              isCollapsed ? 'justify-center px-0' : 'gap-3 px-3.5'
            } ${
              activeDomain === 'shift'
                ? 'bg-[#17171c] font-bold text-white shadow-xs'
                : 'text-[#686873] hover:bg-[#f4f4f6] hover:text-[#17171c]'
            }`}
          >
            <Calendar class="size-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Jadwal Shift</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div
              class="pointer-events-none absolute top-1/2 left-full z-50 ml-3 -translate-y-1/2 rounded-lg bg-[#17171c] px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-white opacity-0 shadow-xl transition-all duration-150 group-hover:opacity-100"
            >
              Jadwal Shift
            </div>
          {/if}
        </div>

        <div class="group relative w-full">
          <button
            type="button"
            onclick={() => onSelectNav('finance')}
            class={`flex w-full cursor-pointer items-center rounded-xl py-2.5 text-left text-xs transition-all ${
              isCollapsed ? 'justify-center px-0' : 'gap-3 px-3.5'
            } ${
              activeDomain === 'finance'
                ? 'bg-[#17171c] font-bold text-white shadow-xs'
                : 'text-[#686873] hover:bg-[#f4f4f6] hover:text-[#17171c]'
            }`}
          >
            <UserIcon class="size-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Slip Gaji &amp; Profil</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div
              class="pointer-events-none absolute top-1/2 left-full z-50 ml-3 -translate-y-1/2 rounded-lg bg-[#17171c] px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-white opacity-0 shadow-xl transition-all duration-150 group-hover:opacity-100"
            >
              Slip Gaji &amp; Profil
            </div>
          {/if}
        </div>
      {/if}
    </nav>

    <!-- Logout Footer -->
    <div
      class={`shrink-0 border-t border-[#f2f2f4] p-2.5 ${isCollapsed ? 'flex justify-center' : ''}`}
    >
      <div class="group relative w-full">
        <button
          type="button"
          onclick={onLogout}
          class={`flex w-full cursor-pointer items-center rounded-xl py-2.5 text-xs font-semibold text-[#dc2626] transition-colors hover:bg-[#fef2f2] ${
            isCollapsed ? 'justify-center px-0' : 'gap-2.5 px-3.5'
          }`}
          title="Keluar Portal"
        >
          <LogOut class="size-4 shrink-0" />
          {#if !isCollapsed}
            <span>Keluar Portal</span>
          {/if}
        </button>
        {#if isCollapsed}
          <div
            class="pointer-events-none absolute top-1/2 left-full z-50 ml-3 -translate-y-1/2 rounded-lg bg-[#dc2626] px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-white opacity-0 shadow-xl transition-all duration-150 group-hover:opacity-100"
          >
            Keluar Portal
          </div>
        {/if}
      </div>
    </div>
  </div>
</aside>
