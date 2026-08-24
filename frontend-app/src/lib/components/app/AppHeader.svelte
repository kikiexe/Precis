<script lang="ts">
  import { ChevronDown, Building2, LogOut, Check, Plus } from 'lucide-svelte';
  import type { User, Role, UserWorkspace } from '../../types/app';

  interface Props {
    currentUser: User;
    userWorkspaces?: UserWorkspace[];
    activeWorkspaceId?: string | null;
    onSwitchWorkspace?: (workspace: UserWorkspace) => void;
    onOpenCreateWorkspaceModal?: () => void;
    onOpenBilling?: () => void;
    onLogout?: () => void;
  }

  let {
    currentUser,
    userWorkspaces = [],
    activeWorkspaceId = null,
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
        return { label: 'Store Manager', bg: 'bg-[#f1f5ff] text-[#1863dc] border border-[#d9d9dd]' };
      case 'STAFF':
        return { label: 'Staf Outlet', bg: 'bg-[#edfce9] text-[#003c33] border border-[#edfce9]' };
      case 'SUPERADMIN':
        return { label: 'Superadmin', bg: 'bg-[#eeece7] text-[#17171c] border border-[#d9d9dd]' };
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
</script>

<header class="block lg:hidden bg-white border-b border-[#d9d9dd] px-4 py-3 select-none shrink-0 sticky top-0 z-30 font-sans">
  <div class="flex items-center justify-between">
    <div class="relative">
      <button
        type="button"
        onclick={() => (isDropdownOpen = !isDropdownOpen)}
        class="flex items-center gap-2.5 cursor-pointer text-left group"
      >
        <img src="/logo.png" alt="Précis Logo" class="w-8 h-8 rounded-[10px] object-cover border border-[#d9d9dd]" />
        <div>
          <div class="flex items-center gap-1.5 leading-none">
            <span class="font-medium text-xs text-[#212121] tracking-tight">{currentUser.name}</span>
            <ChevronDown class="w-3.5 h-3.5 text-[#93939f] group-hover:text-[#212121] transition-transform" />
          </div>
          <div class="flex items-center gap-1.5 mt-0.5">
            <span class={`text-[9px] font-mono px-1.5 py-0.2 rounded-sm font-medium ${badge.bg}`}>
              {badge.label}
            </span>
            <span class="text-[10px] text-[#75758a] font-normal truncate max-w-35">• {currentWorkspaceName}</span>
          </div>
        </div>
      </button>

      {#if isDropdownOpen}
        <div class="absolute left-0 top-12 w-72 bg-white border border-[#d9d9dd] rounded-2xl p-2 z-50 animate-in fade-in zoom-in-95 font-sans shadow-none">
          <div class="flex items-center justify-between text-[11px] font-mono text-[#75758a] px-3 py-1.5 border-b border-[#d9d9dd] mb-1">
            <div class="flex items-center gap-1.5">
              <Building2 class="w-3.5 h-3.5 text-[#1863dc]" />
              <span>Daftar Workspace / Outlet:</span>
            </div>
          </div>

          {#if userWorkspaces.length > 0}
            <div class="space-y-1 max-h-56 overflow-y-auto">
              {#each userWorkspaces as ws (ws.workspace_id)}
                <button
                  type="button"
                  onclick={() => {
                    isDropdownOpen = false;
                    if (onSwitchWorkspace) onSwitchWorkspace(ws);
                  }}
                  class={`w-full text-left px-3 py-2 rounded-[10px] text-xs flex items-center justify-between transition-all cursor-pointer ${
                    ws.workspace_id === activeWorkspaceId
                      ? 'bg-[#eeece7] font-medium text-[#212121]'
                      : 'hover:bg-[#eeece7]/50 text-[#616161] hover:text-[#212121]'
                  }`}
                >
                  <div class="min-w-0 pr-2">
                    <div class="truncate font-medium">{ws.workspace_name}</div>
                    <div class="text-[10px] font-mono text-[#75758a] truncate mt-0.5">
                      {ws.branch_name ? `Cabang: ${ws.branch_name}` : 'Semua Cabang'}
                    </div>
                  </div>
                  <div class="flex items-center gap-2 shrink-0">
                    <span class={`text-[9px] font-mono px-2 py-0.5 rounded-sm ${getRoleBadge(ws.role).bg}`}>
                      {ws.role}
                    </span>
                    {#if ws.workspace_id === activeWorkspaceId}
                      <Check class="w-3.5 h-3.5 text-[#17171c]" />
                    {/if}
                  </div>
                </button>
              {/each}
            </div>
          {:else}
            <div class="px-3 py-2 text-xs text-[#75758a]">
              Belum terhubung ke workspace bisnis.
            </div>
          {/if}

          {#if onOpenCreateWorkspaceModal}
            <div class="border-t border-[#d9d9dd] mt-1 pt-1">
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onOpenCreateWorkspaceModal();
                }}
                class="w-full text-left px-3 py-2 rounded-[10px] text-xs text-[#1863dc] hover:bg-[#f1f5ff] flex items-center gap-2 transition-all cursor-pointer font-medium"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>Buat Workspace Bisnis Baru</span>
              </button>
            </div>
          {/if}

          {#if onLogout}
            <div class="border-t border-[#d9d9dd] mt-1 pt-1">
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onLogout();
                }}
                class="w-full text-left px-3 py-2 rounded-[10px] text-xs text-[#b30000] hover:bg-[#ffad9b]/15 flex items-center gap-2 transition-all cursor-pointer font-medium"
              >
                <LogOut class="w-3.5 h-3.5" />
                <span>Keluar Akun (Logout)</span>
              </button>
            </div>
          {/if}
        </div>
      {/if}
    </div>

    <div class="text-[10px] font-mono text-[#75758a]">
      PRÉCIS APP
    </div>
  </div>
</header>
