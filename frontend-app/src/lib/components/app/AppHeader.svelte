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
        return {
          label: 'Store Manager',
          bg: 'bg-[#eff6ff] text-[#2563eb] border border-[#bfdbfe]',
        };
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
    userWorkspaces.find((w) => w.workspace_id === activeWorkspaceId)?.workspace_name ||
      (userWorkspaces.length === 0 ? 'Tanpa Workspace' : currentUser.branch_name || 'Outlet')
  );
</script>

<header
  class="sticky top-0 z-30 block shrink-0 border-b border-[#e5e5ea] bg-white/95 px-3.5 py-2.5 font-sans shadow-2xs backdrop-blur-md select-none sm:px-4 sm:py-3 lg:hidden"
>
  <div class="flex items-center justify-between gap-2">
    <div class="relative min-w-0 flex-1">
      <button
        type="button"
        onclick={() => (isDropdownOpen = !isDropdownOpen)}
        class="group flex max-w-full min-w-0 cursor-pointer items-center gap-2.5 text-left"
      >
        <img
          src="/logo.png"
          alt="Précis Logo"
          class="size-8 shrink-0 rounded-xl border border-[#e5e5ea] object-cover"
        />
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-1.5 leading-none">
            <span
              class="max-w-30 truncate text-xs font-bold tracking-tight text-[#17171c] sm:max-w-xs"
              >{currentUser.name}</span
            >
            <ChevronDown
              class="size-3.5 shrink-0 text-[#8e8e93] transition-transform group-hover:text-[#17171c]"
            />
          </div>
          <div class="mt-1 flex min-w-0 items-center gap-1.5">
            <span
              class={`shrink-0 rounded-full px-2 py-0.5 font-mono text-[9px] font-semibold whitespace-nowrap ${badge.bg}`}
            >
              {badge.label}
            </span>
            <span
              class="max-w-[110px] shrink truncate text-[11px] font-normal text-[#8e8e93] sm:max-w-44"
              >&bull; {currentWorkspaceName}</span
            >
          </div>
        </div>
      </button>

      {#if isDropdownOpen}
        <!-- Backdrop click-outside overlay -->
        <button
          type="button"
          class="fixed inset-0 z-40 cursor-default bg-black/10 backdrop-blur-[1px]"
          onclick={() => (isDropdownOpen = false)}
          aria-label="Tutup Pilihan Workspace"
        ></button>

        <div
          class="animate-in fade-in zoom-in-95 absolute top-14 left-0 z-50 w-[calc(100vw-32px)] max-w-xs rounded-3xl border border-[#e5e5ea] bg-white p-3 font-sans shadow-xl"
        >
          <div
            class="mb-1 flex items-center justify-between border-b border-[#f2f2f4] px-3 py-2 text-xs font-bold text-[#8e8e93]"
          >
            <div class="flex items-center gap-2">
              <Building2 class="size-4 text-[#1863dc]" />
              <span>Ganti Workspace Bisnis</span>
            </div>
          </div>

          {#if userWorkspaces.length > 0}
            <div class="max-h-48 space-y-1 overflow-y-auto py-1">
              {#each userWorkspaces as ws (ws.workspace_id)}
                <button
                  type="button"
                  onclick={() => {
                    isDropdownOpen = false;
                    if (onSwitchWorkspace) onSwitchWorkspace(ws);
                  }}
                  class={`flex w-full cursor-pointer items-center justify-between rounded-2xl px-3.5 py-2 text-left text-xs transition-all ${
                    ws.workspace_id === activeWorkspaceId
                      ? 'bg-[#f4f4f6] font-bold text-[#17171c]'
                      : 'text-[#686873] hover:bg-[#fafafc] hover:text-[#17171c]'
                  }`}
                >
                  <div class="min-w-0 pr-2">
                    <div class="truncate font-semibold">{ws.workspace_name}</div>
                  </div>
                  <div class="flex shrink-0 items-center gap-2">
                    <span
                      class={`rounded-full px-2 py-0.5 font-mono text-[9.5px] whitespace-nowrap ${getRoleBadge(ws.role).bg}`}
                    >
                      {ws.role}
                    </span>
                    {#if ws.workspace_id === activeWorkspaceId}
                      <Check class="size-4 text-[#17171c]" />
                    {/if}
                  </div>
                </button>
              {/each}
            </div>
          {/if}

          {#if onOpenCreateWorkspaceModal && (currentUser.role === 'OWNER' || currentUser.role === 'SUPERADMIN')}
            <div class="mt-2 border-t border-[#f2f2f4] pt-1.5">
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onOpenCreateWorkspaceModal();
                }}
                class="flex w-full cursor-pointer items-center gap-2 rounded-xl px-3.5 py-2 text-left text-xs font-semibold text-[#2563eb] transition-all hover:bg-[#eff6ff]"
              >
                <Plus class="size-4" />
                <span>Buat Workspace Bisnis Baru</span>
              </button>
            </div>
          {/if}

          {#if onLogout}
            <div class="mt-1 border-t border-[#f2f2f4] pt-1.5">
              <button
                type="button"
                onclick={() => {
                  isDropdownOpen = false;
                  onLogout();
                }}
                class="flex w-full cursor-pointer items-center gap-2 rounded-xl px-3.5 py-2 text-left text-xs font-semibold text-[#e5484d] transition-all hover:bg-[#fdf2f2]"
              >
                <LogOut class="size-4" />
                <span>Keluar Akun</span>
              </button>
            </div>
          {/if}
        </div>
      {/if}
    </div>

    <!-- Right: App Name Identifier -->
    <div class="shrink-0 text-right">
      <span
        class="font-mono text-[9.5px] font-semibold tracking-widest text-[#8e8e93] uppercase sm:text-[10px]"
        >PRÉCIS APP</span
      >
    </div>
  </div>
</header>
