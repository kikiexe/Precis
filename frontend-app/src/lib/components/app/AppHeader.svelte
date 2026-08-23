<script lang="ts">
  import { ChevronDown, Building2, LogOut, Check } from 'lucide-svelte';
  import type { User, Role, UserWorkspace } from '../../types/app';

  interface Props {
    currentUser: User;
    userWorkspaces?: UserWorkspace[];
    activeWorkspaceId?: string | null;
    onSwitchWorkspace?: (workspace: UserWorkspace) => void;
    onOpenBilling: () => void;
    onLogout?: () => void;
  }

  let {
    currentUser,
    userWorkspaces = [],
    activeWorkspaceId = null,
    onSwitchWorkspace,
    onOpenBilling,
    onLogout,
  }: Props = $props();

  let isDropdownOpen = $state(false);

  function getRoleBadge(role: Role) {
    switch (role) {
      case 'OWNER':
        return { label: 'Owner', bg: 'bg-[#161616] text-white' };
      case 'ADMIN':
        return { label: 'Store Manager', bg: 'bg-[#0f62fe] text-white' };
      case 'STAFF':
        return { label: 'Staf Outlet', bg: 'bg-[#24a148] text-white' };
      case 'SUPERADMIN':
        return { label: 'Superadmin', bg: 'bg-[#8a3ffc] text-white' };
    }
  }

  let badge = $derived(getRoleBadge(currentUser.role));
  let currentWorkspaceName = $derived(
    userWorkspaces.find((w) => w.workspace_id === activeWorkspaceId)?.workspace_name || currentUser.branch_name
  );
</script>

<header class="bg-white border-b border-[#e0e0e0] px-4 py-3 select-none shrink-0 sticky top-0 z-30 shadow-xs">
  <div class="flex items-center justify-between">
    <div class="relative">
      <button
        type="button"
        onclick={() => (isDropdownOpen = !isDropdownOpen)}
        class="flex items-center gap-2 cursor-pointer text-left group"
      >
        <div class="w-8 h-8 bg-[#0f62fe] text-white flex items-center justify-center font-bold text-sm">
          P
        </div>
        <div>
          <div class="flex items-center gap-1.5 leading-none">
            <span class="font-bold text-sm text-[#161616] tracking-tight">{currentUser.name}</span>
            <ChevronDown class="w-3.5 h-3.5 text-[#8c8c8c] group-hover:text-[#161616] transition-transform" />
          </div>
          <div class="flex items-center gap-1.5 mt-1">
            <span class={`text-[10px] font-mono px-1.5 py-0.2 font-semibold ${badge.bg}`}>
              {badge.label}
            </span>
            <span class="text-[10px] text-[#8c8c8c] font-mono">• {currentWorkspaceName}</span>
          </div>
        </div>
      </button>

      {#if isDropdownOpen}
        <div class="absolute left-0 top-12 w-72 bg-white border border-[#e0e0e0] shadow-xl p-2 z-50 animate-in fade-in zoom-in-95">
          <div class="flex items-center justify-between text-[11px] font-mono text-[#8c8c8c] px-2 py-1 border-b border-[#e0e0e0] mb-1">
            <div class="flex items-center gap-1.5">
              <Building2 class="w-3.5 h-3.5 text-[#0f62fe]" />
              <span>Daftar Workspace / Outlet:</span>
            </div>
          </div>

          {#if userWorkspaces.length > 0}
            <div class="max-h-48 overflow-y-auto space-y-0.5">
              {#each userWorkspaces as ws}
                <button
                  type="button"
                  onclick={() => {
                    if (onSwitchWorkspace) onSwitchWorkspace(ws);
                    isDropdownOpen = false;
                  }}
                  class={`w-full text-left px-2.5 py-2 text-xs flex items-center justify-between transition-colors cursor-pointer ${
                    ws.workspace_id === activeWorkspaceId
                      ? 'bg-[#f4f4f4] font-semibold text-[#0f62fe]'
                      : 'hover:bg-[#f4f4f4] text-[#161616]'
                  }`}
                >
                  <div class="min-w-0 pr-2">
                    <div class="truncate">{ws.workspace_name}</div>
                    <div class="text-[10px] font-mono text-[#8c8c8c] truncate">
                      {ws.branch_name ? `Cabang: ${ws.branch_name}` : 'Semua Cabang'}
                    </div>
                  </div>
                  <div class="flex items-center gap-1.5 shrink-0">
                    <span class={`text-[9px] font-mono px-1.5 py-0.5 ${getRoleBadge(ws.role).bg}`}>
                      {ws.role}
                    </span>
                    {#if ws.workspace_id === activeWorkspaceId}
                      <Check class="w-3.5 h-3.5 text-[#0f62fe]" />
                    {/if}
                  </div>
                </button>
              {/each}
            </div>
          {:else}
            <div class="px-2.5 py-2 text-xs text-[#8c8c8c]">
              {currentUser.branch_name}
            </div>
          {/if}

          {#if onLogout}
            <div class="border-t border-[#e0e0e0] mt-1 pt-1">
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onLogout();
                }}
                class="w-full text-left px-2.5 py-2 text-xs text-[#da1e28] hover:bg-[#da1e28]/10 flex items-center gap-2 transition-colors cursor-pointer"
              >
                <LogOut class="w-3.5 h-3.5" />
                <span>Keluar Akun (Logout)</span>
              </button>
            </div>
          {/if}
        </div>
      {/if}
    </div>

    <div class="flex items-center gap-2">
      <button
        type="button"
        onclick={onOpenBilling}
        class="text-[11px] font-mono bg-[#24a148]/10 text-[#24a148] border border-[#24a148]/30 px-2 py-1 hover:bg-[#24a148]/20 transition-colors cursor-pointer"
      >
        Trial 1 Bulan
      </button>
    </div>
  </div>
</header>
