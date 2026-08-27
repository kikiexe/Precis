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
        return { label: 'Admin', bg: 'bg-[#eff6ff] text-[#2563eb] border border-[#bfdbfe]' };
      case 'MANAGER':
        return { label: 'Store Manager', bg: 'bg-[#eff6ff] text-[#2563eb] border border-[#bfdbfe]' };
      case 'STAFF':
        return { label: 'Staf Outlet', bg: 'bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0]' };
      case 'SUPERADMIN':
        return { label: 'Superadmin', bg: 'bg-[#f4f4f6] text-[#17171c] border border-[#e5e5ea]' };
      default:
        return { label: role, bg: 'bg-[#f4f4f6] text-[#686873] border border-[#e5e5ea]' };
    }
  }

  let badge = $derived(
    userWorkspaces.length === 0
      ? { label: 'Standalone', bg: 'bg-[#f4f4f6] text-[#686873] border border-[#e5e5ea]' }
      : getRoleBadge(currentUser.role)
  );

  let currentWorkspaceName = $derived(
    userWorkspaces.find((w) => w.workspace_id === activeWorkspaceId)?.workspace_name || (userWorkspaces.length === 0 ? 'Tanpa Workspace' : currentUser.branch_name || 'Outlet')
  );
</script>

<header class="block lg:hidden bg-white/95 backdrop-blur-md border-b border-[#e5e5ea] px-3.5 sm:px-4 py-2.5 sm:py-3 select-none shrink-0 sticky top-0 z-30 font-sans shadow-2xs">
  <div class="flex items-center justify-between gap-2">
    <div class="relative min-w-0 flex-1">
      <button
        type="button"
        onclick={() => (isDropdownOpen = !isDropdownOpen)}
        class="flex items-center gap-2.5 cursor-pointer text-left group min-w-0 max-w-full"
      >
        <img src="/logo.png" alt="Précis Logo" class="w-8 h-8 rounded-xl object-cover border border-[#e5e5ea] shrink-0" />
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-1.5 leading-none">
            <span class="font-bold text-xs text-[#17171c] tracking-tight truncate max-w-[120px] sm:max-w-xs">{currentUser.name}</span>
            <ChevronDown class="w-3.5 h-3.5 text-[#8e8e93] group-hover:text-[#17171c] transition-transform shrink-0" />
          </div>
          <div class="flex items-center gap-1.5 mt-1 min-w-0">
            <span class={`text-[9px] font-mono px-2 py-0.5 rounded-full font-semibold whitespace-nowrap shrink-0 ${badge.bg}`}>
              {badge.label}
            </span>
            <span class="text-[11px] text-[#8e8e93] font-normal truncate max-w-[110px] sm:max-w-44 shrink">&bull; {currentWorkspaceName}</span>
          </div>
        </div>
      </button>

      {#if isDropdownOpen}
        <!-- Backdrop click-outside overlay -->
        <button
          type="button"
          class="fixed inset-0 z-40 bg-black/10 backdrop-blur-[1px] cursor-default"
          onclick={() => (isDropdownOpen = false)}
          aria-label="Tutup Pilihan Workspace"
        ></button>

        <div class="absolute left-0 top-14 w-[calc(100vw-32px)] max-w-xs bg-white border border-[#e5e5ea] rounded-3xl p-3 z-50 animate-in fade-in zoom-in-95 font-sans shadow-xl">
          <div class="flex items-center justify-between text-xs font-bold text-[#8e8e93] px-3 py-2 border-b border-[#f2f2f4] mb-1">
            <div class="flex items-center gap-2">
              <Building2 class="w-4 h-4 text-[#1863dc]" />
              <span>Ganti Workspace Bisnis</span>
            </div>
          </div>

          {#if userWorkspaces.length > 0}
            <div class="space-y-1 max-h-48 overflow-y-auto py-1">
              {#each userWorkspaces as ws (ws.workspace_id)}
                <button
                  type="button"
                  onclick={() => {
                    isDropdownOpen = false;
                    if (onSwitchWorkspace) onSwitchWorkspace(ws);
                  }}
                  class={`w-full text-left px-3.5 py-2 rounded-2xl text-xs flex items-center justify-between transition-all cursor-pointer ${
                    ws.workspace_id === activeWorkspaceId
                      ? 'bg-[#f4f4f6] font-bold text-[#17171c]'
                      : 'hover:bg-[#fafafc] text-[#686873] hover:text-[#17171c]'
                  }`}
                >
                  <div class="min-w-0 pr-2">
                    <div class="truncate font-semibold">{ws.workspace_name}</div>
                  </div>
                  <div class="flex items-center gap-2 shrink-0">
                    <span class={`text-[9.5px] font-mono px-2 py-0.5 rounded-full whitespace-nowrap ${getRoleBadge(ws.role).bg}`}>
                      {ws.role}
                    </span>
                    {#if ws.workspace_id === activeWorkspaceId}
                      <Check class="w-4 h-4 text-[#17171c]" />
                    {/if}
                  </div>
                </button>
              {/each}
            </div>
          {/if}

          {#if onOpenCreateWorkspaceModal && (currentUser.role === 'OWNER' || currentUser.role === 'SUPERADMIN')}
            <div class="border-t border-[#f2f2f4] mt-2 pt-1.5">
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onOpenCreateWorkspaceModal();
                }}
                class="w-full text-left px-3.5 py-2 rounded-xl text-xs text-[#2563eb] hover:bg-[#eff6ff] flex items-center gap-2 transition-all cursor-pointer font-semibold"
              >
                <Plus class="w-4 h-4" />
                <span>Buat Workspace Bisnis Baru</span>
              </button>
            </div>
          {/if}

          {#if onLogout}
            <div class="border-t border-[#f2f2f4] mt-1 pt-1.5">
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onLogout();
                }}
                class="w-full text-left px-3.5 py-2 rounded-xl text-xs text-[#e5484d] hover:bg-[#fdf2f2] flex items-center gap-2 transition-all cursor-pointer font-semibold"
              >
                <LogOut class="w-4 h-4" />
                <span>Keluar Akun</span>
              </button>
            </div>
          {/if}
        </div>
      {/if}
    </div>

    <!-- Right: App Name Identifier -->
    <div class="text-right shrink-0">
      <span class="text-[9.5px] sm:text-[10px] font-mono tracking-widest uppercase text-[#8e8e93] font-semibold">PRÉCIS APP</span>
    </div>
  </div>
</header>
