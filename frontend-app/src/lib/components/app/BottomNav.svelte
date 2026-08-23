<script lang="ts">
  import { Camera, Calendar, Wallet, ShieldCheck, CreditCard } from 'lucide-svelte';
  import type { User } from '../../types/app';

  interface Props {
    currentUser?: User;
    activeTab: 'presensi' | 'shift' | 'finance' | 'admin' | 'billing';
    pendingApprovalsCount?: number;
    onSelectTab: (tab: 'presensi' | 'shift' | 'finance' | 'admin' | 'billing') => void;
  }

  let {
    currentUser,
    activeTab = 'admin',
    pendingApprovalsCount = 0,
    onSelectTab,
  }: Props = $props();

  let navItems = $derived.by(() => {
    if (currentUser?.role === 'OWNER') {
      return [
        { id: 'admin' as const, label: 'Audit & Owner', icon: ShieldCheck, hasBadge: true },
        { id: 'shift' as const, label: 'Jadwal Shift', icon: Calendar, hasBadge: false },
        { id: 'finance' as const, label: 'Kasbon & Gaji', icon: Wallet, hasBadge: false },
        { id: 'billing' as const, label: 'Billing SaaS', icon: CreditCard, hasBadge: false },
      ];
    }

    if (currentUser?.role === 'ADMIN') {
      return [
        { id: 'admin' as const, label: 'Audit Cabang', icon: ShieldCheck, hasBadge: true },
        { id: 'shift' as const, label: 'Jadwal Shift', icon: Calendar, hasBadge: false },
        { id: 'finance' as const, label: 'Kasbon & Gaji', icon: Wallet, hasBadge: false },
        { id: 'presensi' as const, label: 'Presensi', icon: Camera, hasBadge: false },
      ];
    }

    return [
      { id: 'presensi' as const, label: 'Presensi', icon: Camera, hasBadge: false },
      { id: 'shift' as const, label: 'Jadwal Shift', icon: Calendar, hasBadge: false },
      { id: 'finance' as const, label: 'Kasbon & Gaji', icon: Wallet, hasBadge: false },
    ];
  });
</script>

<nav class="bg-white/95 backdrop-blur-md border-t border-[#d9d9dd] flex items-center justify-around h-16 shrink-0 fixed bottom-0 left-0 right-0 z-40 select-none max-w-md mx-auto font-sans shadow-none">
  {#each navItems as item}
    {@const Icon = item.icon}
    <button
      type="button"
      onclick={() => onSelectTab(item.id)}
      class={`flex-1 h-full flex flex-col items-center justify-center gap-1 transition-all cursor-pointer relative ${
        activeTab === item.id ? 'text-[#17171c] font-medium' : 'text-[#75758a] hover:text-[#212121]'
      }`}
    >
      <div class="relative">
        <div class={`p-1 rounded-full ${activeTab === item.id ? 'bg-[#eeece7]' : ''}`}>
          <Icon class="w-4 h-4" />
        </div>
        {#if item.hasBadge && pendingApprovalsCount > 0}
          <span class="absolute -top-1 -right-1.5 bg-[#ff7759] text-white text-[9px] w-4 h-4 rounded-full flex items-center justify-center font-mono font-medium">
            {pendingApprovalsCount}
          </span>
        {/if}
      </div>
      <span class="text-[10px]">{item.label}</span>
    </button>
  {/each}
</nav>
