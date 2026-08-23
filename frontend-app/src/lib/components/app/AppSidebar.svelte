<script lang="ts">
  import { Camera, Calendar, Wallet, ShieldCheck, CreditCard, ChevronDown, Building2, LogOut, Check } from 'lucide-svelte';
  import type { User, Role, UserWorkspace } from '../../types/app';

  interface Props {
    currentUser: User;
    userWorkspaces?: UserWorkspace[];
    activeWorkspaceId?: string | null;
    activeTab: 'presensi' | 'shift' | 'finance' | 'admin' | 'billing';
    pendingApprovalsCount: number;
    onSelectTab: (tab: 'presensi' | 'shift' | 'finance' | 'admin' | 'billing') => void;
    onSwitchWorkspace?: (workspace: UserWorkspace) => void;
    onLogout?: () => void;
  }

  let {
    currentUser,
    userWorkspaces = [],
    activeWorkspaceId = null,
    activeTab = 'presensi',
    pendingApprovalsCount = 0,
    onSelectTab,
    onSwitchWorkspace,
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

  let badge = $derived(getRoleBadge(currentUser.role));
  let currentWorkspaceName = $derived(
    userWorkspaces.find((w) => w.workspace_id === activeWorkspaceId)?.workspace_name || currentUser.branch_name
  );

  const navItems = [
    { id: 'presensi' as const, label: 'Presensi & ESS', icon: Camera, desc: 'Selfie GPS & Hari Ini' },
    { id: 'shift' as const, label: 'Jadwal & Tukar Shift', icon: Calendar, desc: 'Kalender Kerja Staf' },
    { id: 'finance' as const, label: 'Kasbon & Payroll', icon: Wallet, desc: 'Pinjaman & Slip Gaji' },
    { id: 'admin' as const, label: 'Wall of Faces & Audit', icon: ShieldCheck, desc: 'Audit Visual & Approval' },
    { id: 'billing' as const, label: 'Billing & Langganan', icon: CreditCard, desc: 'Kuota Outlet & Tagihan' },
  ];
</script>

<aside class="hidden lg:flex w-64 bg-white border-r border-[#d9d9dd] flex-col justify-between select-none shrink-0 h-screen sticky top-0 font-sans shadow-none">
  <div>
    <!-- header merek dan logo -->
    <div class="h-16 border-b border-[#d9d9dd] px-5 flex items-center gap-3">
      <div class="w-8 h-8 bg-[#17171c] text-white flex items-center justify-center font-medium text-sm rounded-[10px] border border-[#d9d9dd]">
        P
      </div>
      <div>
        <div class="font-medium text-sm text-[#212121] tracking-tight">PRÉCIS PORTAL</div>
        <div class="text-[10px] font-mono text-[#75758a]">Multi-Tenant Enterprise</div>
      </div>
    </div>

    <!-- pengalih user aktif dan workspace -->
    <div class="p-3 border-b border-[#d9d9dd] relative">
      <button
        type="button"
        onclick={() => (isDropdownOpen = !isDropdownOpen)}
        class="w-full p-2.5 bg-[#eeece7]/40 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-[12px] text-left transition-all cursor-pointer group flex items-center justify-between"
      >
        <div class="min-w-0 flex-1">
          <div class="font-medium text-xs text-[#212121] truncate">{currentUser.name}</div>
          <div class="flex items-center gap-1.5 mt-1">
            <span class={`text-[9px] font-mono px-1.5 py-0.5 rounded-[4px] font-medium ${badge.bg}`}>
              {badge.label}
            </span>
            <span class="text-[10px] text-[#75758a] font-normal truncate">• {currentWorkspaceName}</span>
          </div>
        </div>
        <ChevronDown class="w-4 h-4 text-[#93939f] group-hover:text-[#212121] shrink-0 ml-1 transition-transform" />
      </button>

      {#if isDropdownOpen}
        <div class="absolute left-3 right-3 top-20 bg-white border border-[#d9d9dd] rounded-[14px] p-2 z-50 animate-in fade-in shadow-none font-sans">
          <div class="flex items-center gap-1.5 text-[10px] font-mono text-[#75758a] px-2.5 py-1 border-b border-[#d9d9dd] mb-1">
            <Building2 class="w-3 h-3 text-[#1863dc]" />
            <span>Workspace / Cabang:</span>
          </div>

          {#if userWorkspaces.length > 0}
            <div class="max-h-48 overflow-y-auto space-y-1 p-0.5">
              {#each userWorkspaces as ws}
                <button
                  type="button"
                  onclick={() => {
                    if (onSwitchWorkspace) onSwitchWorkspace(ws);
                    isDropdownOpen = false;
                  }}
                  class={`w-full text-left px-2.5 py-2 rounded-[8px] text-xs flex items-center justify-between cursor-pointer transition-all ${
                    ws.workspace_id === activeWorkspaceId
                      ? 'bg-[#eeece7] text-[#212121] font-medium'
                      : 'hover:bg-[#eeece7]/50 text-[#616161] hover:text-[#212121]'
                  }`}
                >
                  <div class="min-w-0 pr-1.5">
                    <div class="truncate font-medium">{ws.workspace_name}</div>
                    <div class="text-[9px] font-mono text-[#75758a] truncate mt-0.5">
                      {ws.branch_name ? `Cabang: ${ws.branch_name}` : 'Semua Cabang'}
                    </div>
                  </div>
                  <div class="flex items-center gap-1.5 shrink-0">
                    <span class={`text-[8px] font-mono px-1.5 py-0.5 rounded-[4px] ${getRoleBadge(ws.role).bg}`}>
                      {ws.role}
                    </span>
                    {#if ws.workspace_id === activeWorkspaceId}
                      <Check class="w-3 h-3 text-[#17171c]" />
                    {/if}
                  </div>
                </button>
              {/each}
            </div>
          {:else}
            <div class="px-2.5 py-2 text-xs text-[#75758a]">
              {currentUser.branch_name}
            </div>
          {/if}
        </div>
      {/if}
    </div>

    <!-- menu navigasi utama -->
    <nav class="p-3 space-y-1">
      {#each navItems as item}
        {@const Icon = item.icon}
        <button
          type="button"
          onclick={() => onSelectTab(item.id)}
          class={`w-full flex items-center justify-between px-3 py-2.5 rounded-[12px] text-xs transition-all cursor-pointer ${
            activeTab === item.id
              ? 'bg-[#eeece7] text-[#212121] font-medium'
              : 'text-[#616161] hover:text-[#212121] hover:bg-[#eeece7]/40'
          }`}
        >
          <div class="flex items-center gap-3">
            <Icon class={`w-4 h-4 ${activeTab === item.id ? 'text-[#17171c]' : 'text-[#75758a]'}`} />
            <div class="text-left">
              <div>{item.label}</div>
              <div class="text-[10px] text-[#75758a] font-normal">{item.desc}</div>
            </div>
          </div>

          {#if item.id === 'admin' && pendingApprovalsCount > 0}
            <span class="bg-[#ff7759] text-white text-[10px] font-mono font-medium px-2 py-0.5 rounded-full">
              {pendingApprovalsCount}
            </span>
          {/if}
        </button>
      {/each}
    </nav>
  </div>

  <!-- status tenant dan tombol logout di footer -->
  <div class="border-t border-[#d9d9dd] bg-[#eeece7]/20 p-3 space-y-3">
    <div class="text-xs space-y-1.5 px-1">
      <div class="flex items-center justify-between text-[#616161]">
        <span>Status Paket:</span>
        <span class="text-[#003c33] font-medium bg-[#edfce9] px-2 py-0.5 rounded-[6px] text-[10px] font-mono">TRIAL 1 BULAN</span>
      </div>
      <div class="flex items-center justify-between text-[#75758a] text-[11px]">
        <span>Kuota Outlet:</span>
        <span class="text-[#212121] font-medium font-mono">5 Cabang</span>
      </div>
    </div>

    {#if onLogout}
      <div>
        <button
          type="button"
          onclick={onLogout}
          class="w-full py-2 px-3 bg-white border border-[#d9d9dd] hover:bg-[#ffad9b]/15 hover:border-[#ffad9b] text-[#b30000] text-xs font-medium rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer"
        >
          <LogOut class="w-3.5 h-3.5" />
          <span>Keluar Sesi</span>
        </button>
      </div>
    {/if}
  </div>
</aside>
