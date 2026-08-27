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
    activeDomain: 'home' | 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings' | 'presensi' | 'shift';
    activeSubTab?: string;
    pendingApprovalsCount: number;
    onSelectNav: (domain: 'home' | 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings' | 'presensi' | 'shift', subTab?: string) => void;
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
        return { label: 'Admin', bg: 'bg-[#eff6ff] text-[#2563eb] border border-[#bfdbfe]', short: 'AD' };
      case 'MANAGER':
        return { label: 'Manager', bg: 'bg-[#eff6ff] text-[#2563eb] border border-[#bfdbfe]', short: 'MG' };
      case 'STAFF':
        return { label: 'Staf', bg: 'bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0]', short: 'ST' };
      case 'SUPERADMIN':
        return { label: 'Superadmin', bg: 'bg-[#f4f4f6] text-[#17171c] border border-[#e5e5ea]', short: 'SA' };
      default:
        return { label: role, bg: 'bg-[#f4f4f6] text-[#686873] border border-[#e5e5ea]', short: 'US' };
    }
  }

  let badge = $derived(
    userWorkspaces.length === 0
      ? { label: 'Standalone', bg: 'bg-[#f4f4f6] text-[#686873] border border-[#e5e5ea]', short: 'SA' }
      : getRoleBadge(currentUser.role)
  );

  let currentWorkspaceName = $derived(
    userWorkspaces.find((w) => w.workspace_id === activeWorkspaceId)?.workspace_name || (userWorkspaces.length === 0 ? 'Tanpa Workspace' : currentUser.branch_name)
  );

  let userInitial = $derived(
    currentUser.name ? currentUser.name.charAt(0).toUpperCase() : 'U'
  );
</script>

<aside
  class={`hidden lg:flex bg-white border-r border-[#e5e5ea] flex-col justify-between select-none shrink-0 h-screen sticky top-0 font-sans shadow-2xs transition-[width] duration-200 ease-in-out ${
    isCollapsed ? 'w-18' : 'w-64'
  }`}
>
  <!-- Floating Edge Toggle Button in the Vertical Center of the Right Border (Mini & Maxi) -->
  <button
    type="button"
    onclick={toggleSidebar}
    class="absolute -right-3.5 top-1/2 -translate-y-1/2 z-40 w-7 h-7 rounded-full bg-white border border-[#e5e5ea] text-[#17171c] shadow-md flex items-center justify-center hover:bg-[#f4f4f6] hover:scale-110 active:scale-95 transition-all cursor-pointer group"
    title={isCollapsed ? 'Besarkan Sidebar' : 'Kecilkan Sidebar'}
    aria-label={isCollapsed ? 'Besarkan Sidebar' : 'Kecilkan Sidebar'}
  >
    {#if isCollapsed}
      <ChevronRight class="w-4 h-4 text-[#17171c] group-hover:translate-x-0.5 transition-transform" />
    {:else}
      <ChevronLeft class="w-4 h-4 text-[#17171c] group-hover:-translate-x-0.5 transition-transform" />
    {/if}
  </button>

  <div class="flex-1 flex flex-col min-h-0 overflow-y-auto overflow-x-hidden">
    <!-- Header Brand -->
    <div
      class={`h-16 border-b border-[#f2f2f4] flex items-center shrink-0 transition-all ${
        isCollapsed ? 'px-3 justify-center' : 'px-4 gap-2.5'
      }`}
    >
      <img
        src="/logo.png"
        alt="Précis Logo"
        class="w-8 h-8 rounded-xl object-cover border border-[#e5e5ea] shrink-0"
      />
      {#if !isCollapsed}
        <div class="min-w-0">
          <div class="font-bold text-sm text-[#17171c] tracking-tight truncate">PRÉCIS APP</div>
          <div class="text-[10px] font-mono text-[#8e8e93] tracking-wider uppercase truncate">F&amp;B Operating System</div>
        </div>
      {/if}
    </div>

    <!-- User & Workspace Switcher -->
    <div class={`p-2.5 border-b border-[#f2f2f4] relative shrink-0 ${isCollapsed ? 'flex justify-center' : ''}`}>
      {#if !isCollapsed}
        <button
          type="button"
          onclick={() => (isDropdownOpen = !isDropdownOpen)}
          class="w-full p-2.5 bg-[#f8f8fa] hover:bg-[#f2f2f5] border border-[#e5e5ea] rounded-2xl text-left transition-all cursor-pointer group flex items-center justify-between shadow-2xs"
        >
          <div class="min-w-0 flex-1">
            <div class="font-bold text-xs text-[#17171c] truncate">{currentUser.name}</div>
            <div class="flex items-center gap-1.5 mt-0.5">
              <span class={`text-[9.5px] font-mono px-1.5 py-0.2 rounded-full font-semibold ${badge.bg}`}>
                {badge.label}
              </span>
              <span class="text-[11px] text-[#8e8e93] font-normal truncate max-w-32">&bull; {currentWorkspaceName}</span>
            </div>
          </div>
          <ChevronDown class="w-4 h-4 text-[#8e8e93] group-hover:text-[#17171c] transition-transform duration-200" />
        </button>
      {:else}
        <!-- Minimized Avatar Button with Flyout Menu -->
        <div class="relative group">
          <button
            type="button"
            onclick={() => (isDropdownOpen = !isDropdownOpen)}
            class="w-10 h-10 rounded-2xl bg-[#f4f4f6] hover:bg-[#eaeaea] border border-[#e5e5ea] flex items-center justify-center font-bold text-xs text-[#17171c] transition-all cursor-pointer shadow-2xs relative"
            title={`${currentUser.name} (${badge.label}) - ${currentWorkspaceName}`}
            aria-label="Pilih Workspace"
          >
            <span>{userInitial}</span>
            <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-[#17171c] text-white text-[8px] flex items-center justify-center font-mono font-bold border border-white">
              {badge.short.charAt(0)}
            </span>
          </button>

          <!-- Hover Tooltip in collapsed mode -->
          {#if !isDropdownOpen}
            <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-150 absolute left-full ml-3 top-1/2 -translate-y-1/2 px-2.5 py-1.5 bg-[#17171c] text-white text-xs font-medium rounded-xl shadow-xl whitespace-nowrap z-50">
              <div class="font-bold text-[11.5px]">{currentUser.name}</div>
              <div class="text-[10px] text-white/60 font-mono">{badge.label} &bull; {currentWorkspaceName}</div>
            </div>
          {/if}
        </div>
      {/if}

      <!-- Dropdown Workspace List -->
      {#if isDropdownOpen}
        <button
          type="button"
          class="fixed inset-0 z-40 bg-black/5 cursor-default"
          onclick={() => (isDropdownOpen = false)}
          aria-label="Tutup Pilihan Workspace"
        ></button>

        <div
          class={`bg-white border border-[#e5e5ea] rounded-2xl shadow-xl p-2 z-50 space-y-1 animate-in fade-in zoom-in-95 ${
            isCollapsed
              ? 'absolute left-full ml-3 top-0 w-60'
              : 'absolute left-2.5 right-2.5 top-full mt-2'
          }`}
        >
          {#if userWorkspaces.length > 0}
            <div class="text-[10px] font-mono text-[#8e8e93] px-2.5 py-1 uppercase tracking-wider font-semibold">
              Pilih Workspace
            </div>
            {#each userWorkspaces as ws}
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onSwitchWorkspace?.(ws);
                }}
                class={`w-full px-3 py-2 rounded-xl text-left text-xs transition-colors flex items-center justify-between cursor-pointer ${
                  ws.workspace_id === activeWorkspaceId ? 'bg-[#f4f4f6] font-bold text-[#17171c]' : 'hover:bg-[#fafafc] text-[#686873]'
                }`}
              >
                <div class="min-w-0 flex-1">
                  <div class="font-semibold truncate">{ws.workspace_name}</div>
                  <div class="text-[10px] text-[#8e8e93] font-mono">{ws.role}</div>
                </div>
                {#if ws.workspace_id === activeWorkspaceId}
                  <Check class="w-3.5 h-3.5 text-[#17171c]" />
                {/if}
              </button>
            {/each}
          {/if}

          {#if onOpenCreateWorkspaceModal && (currentUser.role === 'OWNER' || currentUser.role === 'SUPERADMIN')}
            <div class="pt-1.5 border-t border-[#f2f2f4]">
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onOpenCreateWorkspaceModal();
                }}
                class="w-full px-3 py-1.5 rounded-xl text-left text-xs text-[#2563eb] hover:bg-[#eff6ff] transition-colors flex items-center gap-1.5 cursor-pointer font-semibold"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>Buat Workspace Baru</span>
              </button>
            </div>
          {/if}
        </div>
      {/if}
    </div>

    <!-- Navigation Menu Items -->
    <nav class={`p-2.5 space-y-1.5 flex-1 ${isCollapsed ? 'flex flex-col items-center' : ''}`}>
      {#if currentUser.role === 'OWNER' || currentUser.role === 'ADMIN' || currentUser.role === 'MANAGER'}
        {#if !isCollapsed}
          <div class="text-[10px] font-mono text-[#8e8e93] uppercase tracking-wider px-3 py-1 font-semibold">
            Menu Utama
          </div>
        {/if}

        <!-- Dashboard Ringkasan -->
        <div class="relative group w-full">
          <button
            type="button"
            onclick={() => onSelectNav('dashboard')}
            class={`w-full py-2.5 text-xs rounded-xl transition-all flex items-center cursor-pointer text-left ${
              isCollapsed ? 'justify-center px-0' : 'px-3.5 gap-3'
            } ${
              activeDomain === 'dashboard'
                ? 'bg-[#17171c] text-white font-bold shadow-xs'
                : 'text-[#686873] hover:text-[#17171c] hover:bg-[#f4f4f6]'
            }`}
          >
            <LayoutDashboard class="w-4 h-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Dashboard Ringkasan</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-150 absolute left-full ml-3 top-1/2 -translate-y-1/2 px-2.5 py-1 bg-[#17171c] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50">
              Dashboard Ringkasan
            </div>
          {/if}
        </div>

        <!-- Katalog Produk -->
        <div class="relative group w-full">
          <button
            type="button"
            onclick={() => onSelectNav('katalog')}
            class={`w-full py-2.5 text-xs rounded-xl transition-all flex items-center cursor-pointer text-left ${
              isCollapsed ? 'justify-center px-0' : 'px-3.5 gap-3'
            } ${
              activeDomain === 'katalog'
                ? 'bg-[#17171c] text-white font-bold shadow-xs'
                : 'text-[#686873] hover:text-[#17171c] hover:bg-[#f4f4f6]'
            }`}
          >
            <Package class="w-4 h-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Katalog Produk</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-150 absolute left-full ml-3 top-1/2 -translate-y-1/2 px-2.5 py-1 bg-[#17171c] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50">
              Katalog Produk
            </div>
          {/if}
        </div>

        <!-- Manajemen Tim -->
        <div class="relative group w-full">
          <button
            type="button"
            onclick={() => onSelectNav('tim')}
            class={`w-full py-2.5 text-xs rounded-xl transition-all flex items-center cursor-pointer text-left ${
              isCollapsed ? 'justify-center px-0 relative' : 'px-3.5 justify-between'
            } ${
              activeDomain === 'tim'
                ? 'bg-[#17171c] text-white font-bold shadow-xs'
                : 'text-[#686873] hover:text-[#17171c] hover:bg-[#f4f4f6]'
            }`}
          >
            <div class={`flex items-center ${isCollapsed ? '' : 'gap-3 truncate'}`}>
              <Users class="w-4 h-4 shrink-0" />
              {#if !isCollapsed}
                <span class="truncate">Manajemen Tim</span>
              {/if}
            </div>

            {#if pendingApprovalsCount > 0}
              {#if !isCollapsed}
                <span class="px-2 py-0.5 rounded-full bg-[#e5484d] text-white text-[10px] font-mono font-bold">
                  {pendingApprovalsCount}
                </span>
              {:else}
                <span class="absolute top-1.5 right-2 w-2.5 h-2.5 rounded-full bg-[#e5484d] border-2 border-white"></span>
              {/if}
            {/if}
          </button>
          {#if isCollapsed}
            <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-150 absolute left-full ml-3 top-1/2 -translate-y-1/2 px-2.5 py-1 bg-[#17171c] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50 flex items-center gap-2">
              <span>Manajemen Tim</span>
              {#if pendingApprovalsCount > 0}
                <span class="px-1.5 py-0.2 rounded-full bg-[#e5484d] text-white text-[10px] font-mono">
                  {pendingApprovalsCount}
                </span>
              {/if}
            </div>
          {/if}
        </div>

        <!-- Keuangan & Payroll -->
        <div class="relative group w-full">
          <button
            type="button"
            onclick={() => onSelectNav('finance')}
            class={`w-full py-2.5 text-xs rounded-xl transition-all flex items-center cursor-pointer text-left ${
              isCollapsed ? 'justify-center px-0' : 'px-3.5 gap-3'
            } ${
              activeDomain === 'finance'
                ? 'bg-[#17171c] text-white font-bold shadow-xs'
                : 'text-[#686873] hover:text-[#17171c] hover:bg-[#f4f4f6]'
            }`}
          >
            <Wallet class="w-4 h-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Keuangan &amp; Payroll</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-150 absolute left-full ml-3 top-1/2 -translate-y-1/2 px-2.5 py-1 bg-[#17171c] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50">
              Keuangan &amp; Payroll
            </div>
          {/if}
        </div>

        <!-- Pengaturan -->
        <div class="relative group w-full">
          <button
            type="button"
            onclick={() => onSelectNav('settings')}
            class={`w-full py-2.5 text-xs rounded-xl transition-all flex items-center cursor-pointer text-left ${
              isCollapsed ? 'justify-center px-0' : 'px-3.5 gap-3'
            } ${
              activeDomain === 'settings'
                ? 'bg-[#17171c] text-white font-bold shadow-xs'
                : 'text-[#686873] hover:text-[#17171c] hover:bg-[#f4f4f6]'
            }`}
          >
            <Settings class="w-4 h-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Pengaturan</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-150 absolute left-full ml-3 top-1/2 -translate-y-1/2 px-2.5 py-1 bg-[#17171c] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50">
              Pengaturan
            </div>
          {/if}
        </div>
      {:else}
        <!-- STAFF ESS SIMPLE NAVIGATION -->
        {#if !isCollapsed}
          <div class="text-[10px] font-mono text-[#8e8e93] uppercase tracking-wider px-3 py-1 font-semibold">
            Portal Karyawan
          </div>
        {/if}

        <div class="relative group w-full">
          <button
            type="button"
            onclick={() => onSelectNav('home')}
            class={`w-full py-2.5 text-xs rounded-xl transition-all flex items-center cursor-pointer text-left ${
              isCollapsed ? 'justify-center px-0' : 'px-3.5 gap-3'
            } ${
              activeDomain === 'home'
                ? 'bg-[#17171c] text-white font-bold shadow-xs'
                : 'text-[#686873] hover:text-[#17171c] hover:bg-[#f4f4f6]'
            }`}
          >
            <LayoutDashboard class="w-4 h-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Beranda Staf</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-150 absolute left-full ml-3 top-1/2 -translate-y-1/2 px-2.5 py-1 bg-[#17171c] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50">
              Beranda Staf
            </div>
          {/if}
        </div>

        <div class="relative group w-full">
          <button
            type="button"
            onclick={() => onSelectNav('presensi')}
            class={`w-full py-2.5 text-xs rounded-xl transition-all flex items-center cursor-pointer text-left ${
              isCollapsed ? 'justify-center px-0' : 'px-3.5 gap-3'
            } ${
              activeDomain === 'presensi'
                ? 'bg-[#17171c] text-white font-bold shadow-xs'
                : 'text-[#686873] hover:text-[#17171c] hover:bg-[#f4f4f6]'
            }`}
          >
            <Camera class="w-4 h-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Presensi Harian</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-150 absolute left-full ml-3 top-1/2 -translate-y-1/2 px-2.5 py-1 bg-[#17171c] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50">
              Presensi Harian
            </div>
          {/if}
        </div>

        <div class="relative group w-full">
          <button
            type="button"
            onclick={() => onSelectNav('shift')}
            class={`w-full py-2.5 text-xs rounded-xl transition-all flex items-center cursor-pointer text-left ${
              isCollapsed ? 'justify-center px-0' : 'px-3.5 gap-3'
            } ${
              activeDomain === 'shift'
                ? 'bg-[#17171c] text-white font-bold shadow-xs'
                : 'text-[#686873] hover:text-[#17171c] hover:bg-[#f4f4f6]'
            }`}
          >
            <Calendar class="w-4 h-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Jadwal Shift</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-150 absolute left-full ml-3 top-1/2 -translate-y-1/2 px-2.5 py-1 bg-[#17171c] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50">
              Jadwal Shift
            </div>
          {/if}
        </div>

        <div class="relative group w-full">
          <button
            type="button"
            onclick={() => onSelectNav('finance')}
            class={`w-full py-2.5 text-xs rounded-xl transition-all flex items-center cursor-pointer text-left ${
              isCollapsed ? 'justify-center px-0' : 'px-3.5 gap-3'
            } ${
              activeDomain === 'finance'
                ? 'bg-[#17171c] text-white font-bold shadow-xs'
                : 'text-[#686873] hover:text-[#17171c] hover:bg-[#f4f4f6]'
            }`}
          >
            <UserIcon class="w-4 h-4 shrink-0" />
            {#if !isCollapsed}
              <span class="truncate">Slip Gaji &amp; Profil</span>
            {/if}
          </button>
          {#if isCollapsed}
            <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-150 absolute left-full ml-3 top-1/2 -translate-y-1/2 px-2.5 py-1 bg-[#17171c] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50">
              Slip Gaji &amp; Profil
            </div>
          {/if}
        </div>
      {/if}
    </nav>

    <!-- Logout Footer -->
    <div class={`p-2.5 border-t border-[#f2f2f4] shrink-0 ${isCollapsed ? 'flex justify-center' : ''}`}>
      <div class="relative group w-full">
        <button
          type="button"
          onclick={onLogout}
          class={`w-full py-2.5 text-xs text-[#dc2626] hover:bg-[#fef2f2] rounded-xl transition-colors flex items-center cursor-pointer font-semibold ${
            isCollapsed ? 'justify-center px-0' : 'px-3.5 gap-2.5'
          }`}
          title="Keluar Portal"
        >
          <LogOut class="w-4 h-4 shrink-0" />
          {#if !isCollapsed}
            <span>Keluar Portal</span>
          {/if}
        </button>
        {#if isCollapsed}
          <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-150 absolute left-full ml-3 top-1/2 -translate-y-1/2 px-2.5 py-1 bg-[#dc2626] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50">
            Keluar Portal
          </div>
        {/if}
      </div>
    </div>
  </div>
</aside>
