<script lang="ts">
  import {
    LayoutDashboard,
    Package,
    FolderTree,
    Layers,
    Camera,
    Calendar,
    Wallet,
    FileText,
    Settings,
    ChevronDown,
    LogOut,
    Check,
    Plus,
    Building2,
    User as UserIcon
  } from 'lucide-svelte';
  import type { User, Role, UserWorkspace, BranchItem } from '../../types/app';

  interface Props {
    currentUser: User;
    userWorkspaces?: UserWorkspace[];
    activeWorkspaceId?: string | null;
    branches?: BranchItem[];
    selectedBranchId?: string;
    onSelectBranch?: (branchId: string) => void;
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
    branches = [],
    selectedBranchId = 'ALL',
    onSelectBranch,
    activeDomain = 'dashboard',
    activeSubTab = '',
    pendingApprovalsCount = 0,
    onSelectNav,
    onSwitchWorkspace,
    onOpenCreateWorkspaceModal,
    onLogout,
  }: Props = $props();

  let isDropdownOpen = $state(false);

  function getRoleBadge(role: Role) {
    switch (role) {
      case 'OWNER':
        return { label: 'Owner', bg: 'bg-[#17171c] text-white' };
      case 'ADMIN':
        return { label: 'Admin', bg: 'bg-[#f1f5ff] text-[#1863dc] border border-[#d9d9dd]' };
      case 'MANAGER':
        return { label: 'Store Manager', bg: 'bg-[#f1f5ff] text-[#1863dc] border border-[#d9d9dd]' };
      case 'STAFF':
        return { label: 'Staf Outlet', bg: 'bg-[#edfce9] text-[#003c33] border border-[#edfce9]' };
      case 'SUPERADMIN':
        return { label: 'Superadmin', bg: 'bg-[#eeece7] text-[#17171c] border border-[#d9d9dd]' };
      default:
        return { label: role, bg: 'bg-[#eeece7] text-[#75758a] border border-[#d9d9dd]' };
    }
  }

  let badge = $derived(
    userWorkspaces.length === 0
      ? { label: 'Standalone', bg: 'bg-[#eeece7] text-[#75758a] border border-[#d9d9dd]' }
      : getRoleBadge(currentUser.role)
  );
  let currentWorkspaceName = $derived(
    userWorkspaces.find((w) => w.workspace_id === activeWorkspaceId)?.workspace_name || (userWorkspaces.length === 0 ? 'Tanpa Workspace' : currentUser.branch_name)
  );
  let currentBranchName = $derived(
    selectedBranchId === 'ALL'
      ? 'Semua Cabang'
      : branches.find((b) => b.id === selectedBranchId)?.name || 'Semua Cabang'
  );
</script>

<aside class="hidden lg:flex w-64 bg-white border-r border-[#d9d9dd] flex-col justify-between select-none shrink-0 h-screen sticky top-0 font-sans shadow-none">
  <div class="flex-1 flex flex-col min-h-0 overflow-y-auto">
    <!-- Header Brand -->
    <div class="h-16 border-b border-[#d9d9dd] px-5 flex items-center gap-3 shrink-0">
      <img src="/logo.png" alt="Précis Logo" class="w-8 h-8 rounded-[10px] object-cover border border-[#d9d9dd]" />
      <div>
        <div class="font-medium text-sm text-[#212121] tracking-tight">PRÉCIS PORTAL</div>
        <div class="text-[10px] font-mono text-[#75758a]">Multi-Tenant F&amp;B</div>
      </div>
    </div>

    <!-- User & Workspace Switcher -->
    <div class="p-3 border-b border-[#d9d9dd] relative shrink-0">
      <button
        type="button"
        onclick={() => (isDropdownOpen = !isDropdownOpen)}
        class="w-full p-2.5 bg-[#eeece7]/40 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-xl text-left transition-all cursor-pointer group flex items-center justify-between"
      >
        <div class="min-w-0 flex-1">
          <div class="font-medium text-xs text-[#212121] truncate">{currentUser.name}</div>
          <div class="flex items-center gap-1.5 mt-1">
            <span class={`text-[9px] font-mono px-1.5 py-0.5 rounded-sm font-medium ${badge.bg}`}>
              {badge.label}
            </span>
            <span class="text-[10px] text-[#75758a] font-normal truncate">• {currentWorkspaceName} ({currentBranchName})</span>
          </div>
        </div>
        <ChevronDown class="w-4 h-4 text-[#75758a] group-hover:text-[#212121] transition-transform duration-200" />
      </button>

      {#if isDropdownOpen}
        <div class="absolute left-3 right-3 top-full mt-1.5 bg-white border border-[#d9d9dd] rounded-xl shadow-lg p-1.5 z-50 space-y-1.5">
          {#if userWorkspaces.length > 0}
            <div class="text-[9px] font-mono text-[#75758a] px-2 py-0.5 uppercase tracking-wider">
              Pilih Workspace
            </div>
            {#each userWorkspaces as ws}
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onSwitchWorkspace?.(ws);
                }}
                class="w-full px-2.5 py-1.5 rounded-lg text-left text-xs hover:bg-[#eeece7]/50 transition-colors flex items-center justify-between cursor-pointer"
              >
                <div class="min-w-0 flex-1">
                  <div class="font-medium text-[#212121] truncate">{ws.workspace_name}</div>
                  <div class="text-[10px] text-[#75758a] font-mono">{ws.role}</div>
                </div>
                {#if ws.workspace_id === activeWorkspaceId}
                  <Check class="w-3.5 h-3.5 text-[#17171c]" />
                {/if}
              </button>
            {/each}
          {/if}

          {#if branches.length > 0 && (currentUser.role === 'OWNER' || currentUser.role === 'ADMIN')}
            <div class="pt-1.5 border-t border-[#d9d9dd]">
              <div class="text-[9px] font-mono text-[#75758a] px-2 py-0.5 uppercase tracking-wider flex items-center gap-1">
                <Building2 class="w-3 h-3" />
                <span>Pilih Cabang Outlet</span>
              </div>
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onSelectBranch?.('ALL');
                }}
                class={`w-full px-2.5 py-1.5 rounded-lg text-left text-xs transition-colors flex items-center justify-between cursor-pointer ${
                  selectedBranchId === 'ALL' ? 'bg-[#eeece7] font-medium' : 'hover:bg-[#eeece7]/50'
                }`}
              >
                <span class="truncate text-[#212121]">Semua Cabang (Konsolidasi)</span>
                {#if selectedBranchId === 'ALL'}
                  <Check class="w-3.5 h-3.5 text-[#17171c]" />
                {/if}
              </button>
              {#each branches as b}
                <button
                  type="button"
                  onclick={() => {
                    isDropdownOpen = false;
                    onSelectBranch?.(b.id);
                  }}
                  class={`w-full px-2.5 py-1.5 rounded-lg text-left text-xs transition-colors flex items-center justify-between cursor-pointer ${
                    selectedBranchId === b.id ? 'bg-[#eeece7] font-medium' : 'hover:bg-[#eeece7]/50'
                  }`}
                >
                  <span class="truncate text-[#212121]">{b.name}</span>
                  {#if selectedBranchId === b.id}
                    <Check class="w-3.5 h-3.5 text-[#17171c]" />
                  {/if}
                </button>
              {/each}
            </div>
          {/if}

          {#if onOpenCreateWorkspaceModal}
            <div class="pt-1 border-t border-[#d9d9dd]">
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onOpenCreateWorkspaceModal();
                }}
                class="w-full px-2.5 py-1.5 rounded-lg text-left text-xs text-[#1863dc] hover:bg-[#f1f5ff] transition-colors flex items-center gap-1.5 cursor-pointer font-medium"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>Buat Workspace Bisnis Baru</span>
              </button>
            </div>
          {/if}
        </div>
      {/if}
    </div>

    <!-- Navigation Menu Items -->
    <nav class="p-3 space-y-4 flex-1">
      {#if currentUser.role === 'OWNER' || currentUser.role === 'ADMIN'}
        <!-- TOP LEVEL: DASHBOARD -->
        <div class="space-y-1">
          <button
            type="button"
            onclick={() => onSelectNav('dashboard')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center gap-2.5 cursor-pointer text-left ${
              activeDomain === 'dashboard'
                ? 'bg-[#17171c] text-white font-medium shadow-none'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <LayoutDashboard class="w-4 h-4 shrink-0" />
            <span class="truncate">Dashboard</span>
          </button>
        </div>

        <!-- GROUP: KATALOG -->
        <div class="space-y-1">
          <div class="text-[10px] font-mono font-medium text-[#75758a] uppercase tracking-wider px-3 py-1">
            Katalog
          </div>

          <button
            type="button"
            onclick={() => onSelectNav('katalog', 'menu')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center gap-2.5 cursor-pointer text-left ${
              activeDomain === 'katalog' && (activeSubTab === 'menu' || !activeSubTab)
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <Package class="w-4 h-4 shrink-0" />
            <span class="truncate">Menu</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectNav('katalog', 'kategori')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center gap-2.5 cursor-pointer text-left ${
              activeDomain === 'katalog' && activeSubTab === 'kategori'
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <FolderTree class="w-4 h-4 shrink-0" />
            <span class="truncate">Kategori</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectNav('katalog', 'bahan')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center gap-2.5 cursor-pointer text-left ${
              activeDomain === 'katalog' && activeSubTab === 'bahan'
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <Layers class="w-4 h-4 shrink-0" />
            <span class="truncate">Bahan</span>
          </button>
        </div>

        <!-- GROUP: TIM -->
        <div class="space-y-1">
          <div class="text-[10px] font-mono font-medium text-[#75758a] uppercase tracking-wider px-3 py-1">
            Tim
          </div>

          <button
            type="button"
            onclick={() => onSelectNav('tim', 'presensi')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center gap-2.5 cursor-pointer text-left ${
              activeDomain === 'tim' && (activeSubTab === 'presensi' || !activeSubTab)
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <Camera class="w-4 h-4 shrink-0" />
            <span class="truncate">Presensi</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectNav('tim', 'shift')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center justify-between cursor-pointer text-left ${
              activeDomain === 'tim' && activeSubTab === 'shift'
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <div class="flex items-center gap-2.5 truncate">
              <Calendar class="w-4 h-4 shrink-0" />
              <span class="truncate">Shift</span>
            </div>
          </button>

          <button
            type="button"
            onclick={() => onSelectNav('tim', 'kasbon')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center justify-between cursor-pointer text-left ${
              activeDomain === 'tim' && activeSubTab === 'kasbon'
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <div class="flex items-center gap-2.5 truncate">
              <Wallet class="w-4 h-4 shrink-0" />
              <span class="truncate">Kasbon</span>
            </div>
            {#if pendingApprovalsCount > 0}
              <span class="px-1.5 py-0.2 rounded-full bg-[#e5484d] text-white text-[9px] font-mono font-medium">
                {pendingApprovalsCount}
              </span>
            {/if}
          </button>
        </div>

        <!-- GROUP: FINANSIAL -->
        <div class="space-y-1">
          <div class="text-[10px] font-mono font-medium text-[#75758a] uppercase tracking-wider px-3 py-1">
            Finansial
          </div>

          <button
            type="button"
            onclick={() => onSelectNav('finance', 'payroll')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center gap-2.5 cursor-pointer text-left ${
              activeDomain === 'finance' && (activeSubTab === 'payroll' || !activeSubTab)
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <Wallet class="w-4 h-4 shrink-0" />
            <span class="truncate">Payroll</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectNav('finance', 'laporan')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center gap-2.5 cursor-pointer text-left ${
              activeDomain === 'finance' && activeSubTab === 'laporan'
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <FileText class="w-4 h-4 shrink-0" />
            <span class="truncate">Laporan</span>
          </button>
        </div>

        <!-- GROUP: PENGATURAN -->
        <div class="space-y-1">
          <div class="text-[10px] font-mono font-medium text-[#75758a] uppercase tracking-wider px-3 py-1">
            Pengaturan
          </div>

          <button
            type="button"
            onclick={() => onSelectNav('settings')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center gap-2.5 cursor-pointer text-left ${
              activeDomain === 'settings'
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <Settings class="w-4 h-4 shrink-0" />
            <span class="truncate">Profil &amp; Cabang</span>
          </button>
        </div>
      {:else}
        <!-- STAFF ESS SIMPLE NAVIGATION -->
        <div class="space-y-1">
          <div class="text-[10px] font-mono font-medium text-[#75758a] uppercase tracking-wider px-3 py-1">
            Menu
          </div>

          <button
            type="button"
            onclick={() => onSelectNav('home')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center gap-2.5 cursor-pointer text-left ${
              activeDomain === 'home'
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <LayoutDashboard class="w-4 h-4 shrink-0" />
            <span class="truncate">Beranda</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectNav('presensi')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center gap-2.5 cursor-pointer text-left ${
              activeDomain === 'presensi'
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <Camera class="w-4 h-4 shrink-0" />
            <span class="truncate">Presensi</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectNav('shift')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center gap-2.5 cursor-pointer text-left ${
              activeDomain === 'shift'
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <Calendar class="w-4 h-4 shrink-0" />
            <span class="truncate">Shift</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectNav('finance')}
            class={`w-full px-3 py-2 text-xs rounded-[10px] transition-all flex items-center gap-2.5 cursor-pointer text-left ${
              activeDomain === 'finance'
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/60'
            }`}
          >
            <UserIcon class="w-4 h-4 shrink-0" />
            <span class="truncate">Profil</span>
          </button>
        </div>
      {/if}
    </nav>

    <!-- Logout Footer -->
    <div class="p-3 border-t border-[#d9d9dd] shrink-0">
      <button
        type="button"
        onclick={onLogout}
        class="w-full px-3 py-2 text-xs text-[#e5484d] hover:bg-[#ffefef] rounded-[10px] transition-colors flex items-center gap-2 cursor-pointer font-medium"
      >
        <LogOut class="w-4 h-4" />
        <span>Keluar Portal</span>
      </button>
    </div>
  </div>
</aside>
