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

  const navItems = [
    { id: 'presensi' as const, label: 'Presensi & ESS', icon: Camera, desc: 'Selfie GPS & Hari Ini' },
    { id: 'shift' as const, label: 'Jadwal & Tukar Shift', icon: Calendar, desc: 'Kalender Kerja Staf' },
    { id: 'finance' as const, label: 'Kasbon & Payroll', icon: Wallet, desc: 'Pinjaman & Slip Gaji' },
    { id: 'admin' as const, label: 'Wall of Faces & Audit', icon: ShieldCheck, desc: 'Audit Visual & Approval' },
    { id: 'billing' as const, label: 'Billing & Langganan', icon: CreditCard, desc: 'Kuota Outlet & Tagihan' },
  ];
</script>

<aside class="hidden lg:flex w-64 bg-white border-r border-[#e0e0e0] flex-col justify-between select-none shrink-0 h-screen sticky top-0 shadow-xs">
  <div>
    <!-- header merek dan logo -->
    <div class="h-16 border-b border-[#e0e0e0] px-5 flex items-center gap-3">
      <div class="w-8 h-8 bg-[#0f62fe] text-white flex items-center justify-center font-bold text-base font-display">
        P
      </div>
      <div>
        <div class="font-bold text-sm text-[#161616] tracking-tight font-display">PRÉCIS PORTAL</div>
        <div class="text-[10px] font-mono text-[#8c8c8c]">Multi-Tenant Enterprise</div>
      </div>
    </div>

    <!-- pengalih user aktif dan workspace -->
    <div class="p-3 border-b border-[#e0e0e0] relative">
      <button
        type="button"
        onclick={() => (isDropdownOpen = !isDropdownOpen)}
        class="w-full p-2.5 bg-[#f4f4f4] hover:bg-[#e0e0e0] border border-[#e0e0e0] text-left transition-colors cursor-pointer group flex items-center justify-between"
      >
        <div class="min-w-0 flex-1">
          <div class="font-bold text-xs text-[#161616] truncate">{currentUser.name}</div>
          <div class="flex items-center gap-1.5 mt-1">
            <span class={`text-[9px] font-mono px-1.5 py-0.2 font-semibold ${badge.bg}`}>
              {badge.label}
            </span>
            <span class="text-[10px] font-mono text-[#8c8c8c] truncate">• {currentWorkspaceName}</span>
          </div>
        </div>
        <ChevronDown class="w-4 h-4 text-[#8c8c8c] group-hover:text-[#161616] shrink-0 ml-1" />
      </button>

      {#if isDropdownOpen}
        <div class="absolute left-3 right-3 top-18 bg-white border border-[#e0e0e0] shadow-xl p-2 z-50 animate-in fade-in">
          <div class="flex items-center gap-1 text-[10px] font-mono text-[#8c8c8c] px-2 py-1 border-b border-[#e0e0e0] mb-1">
            <Building2 class="w-3 h-3 text-[#0f62fe]" />
            <span>Workspace / Cabang:</span>
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
                  class={`w-full text-left px-2 py-1.5 text-xs flex items-center justify-between cursor-pointer transition-colors ${
                    ws.workspace_id === activeWorkspaceId
                      ? 'bg-[#0f62fe]/10 text-[#0f62fe] font-bold'
                      : 'hover:bg-[#f4f4f4] text-[#161616]'
                  }`}
                >
                  <div class="min-w-0 pr-1.5">
                    <div class="truncate">{ws.workspace_name}</div>
                    <div class="text-[9px] font-mono text-[#8c8c8c] truncate">
                      {ws.branch_name ? `Cabang: ${ws.branch_name}` : 'Semua Cabang'}
                    </div>
                  </div>
                  <div class="flex items-center gap-1 shrink-0">
                    <span class={`text-[8px] font-mono px-1 py-0.5 ${getRoleBadge(ws.role).bg}`}>
                      {ws.role}
                    </span>
                    {#if ws.workspace_id === activeWorkspaceId}
                      <Check class="w-3 h-3 text-[#0f62fe]" />
                    {/if}
                  </div>
                </button>
              {/each}
            </div>
          {:else}
            <div class="px-2 py-1.5 text-xs text-[#8c8c8c]">
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
          class={`w-full flex items-center justify-between px-3 py-2.5 text-xs border-l-2 transition-colors cursor-pointer ${
            activeTab === item.id
              ? 'bg-[#0f62fe]/10 text-[#0f62fe] border-[#0f62fe] font-bold'
              : 'text-[#525252] hover:text-[#161616] hover:bg-[#f4f4f4] border-transparent'
          }`}
        >
          <div class="flex items-center gap-2.5">
            <Icon class="w-4 h-4 text-[#0f62fe]" />
            <div class="text-left">
              <div>{item.label}</div>
              <div class="text-[10px] font-mono text-[#8c8c8c] font-normal">{item.desc}</div>
            </div>
          </div>

          {#if item.id === 'admin' && pendingApprovalsCount > 0}
            <span class="bg-[#da1e28] text-white text-[10px] font-mono font-bold px-1.5 py-0.5 rounded-full">
              {pendingApprovalsCount}
            </span>
          {/if}
        </button>
      {/each}
    </nav>
  </div>

  <!-- status tenant dan tombol logout di footer -->
  <div class="border-t border-[#e0e0e0] bg-[#f4f4f4]">
    <div class="p-3 text-xs font-mono space-y-1">
      <div class="flex items-center justify-between text-[#525252]">
        <span>Status Paket:</span>
        <span class="text-[#24a148] font-bold">TRIAL 1 BULAN</span>
      </div>
      <div class="flex items-center justify-between text-[#8c8c8c] text-[11px]">
        <span>Kuota Outlet:</span>
        <span class="text-[#161616]">5 Cabang</span>
      </div>
    </div>

    {#if onLogout}
      <div class="p-3 pt-0">
        <button
          type="button"
          onclick={onLogout}
          class="w-full py-2 px-3 bg-white border border-[#e0e0e0] hover:bg-[#da1e28]/10 hover:border-[#da1e28] text-[#da1e28] text-xs font-bold flex items-center justify-center gap-2 transition-colors cursor-pointer"
        >
          <LogOut class="w-3.5 h-3.5" />
          <span>Keluar Sesi</span>
        </button>
      </div>
    {/if}
  </div>
</aside>
