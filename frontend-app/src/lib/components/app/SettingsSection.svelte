<script lang="ts">
  import { Building2, CreditCard, User as UserIcon } from 'lucide-svelte';
  import type {
    SubscriptionInvoice,
    SubscriptionPlanItem,
    User,
    BranchItem,
  } from '../../types/app';
  import UserProfileTab from './settings/UserProfileTab.svelte';
  import ChangePasswordCard from './settings/ChangePasswordCard.svelte';
  import BranchSettingsTab from './settings/BranchSettingsTab.svelte';
  import BillingHistoryTab from './settings/BillingHistoryTab.svelte';

  interface Props {
    currentUser: User;
    initialSubTab?: string;
    branches?: BranchItem[];
    subscriptionInvoices?: SubscriptionInvoice[];
    subscriptionPlans?: SubscriptionPlanItem[];
    onOpenBillingModal: () => void;
    onSubmitPaymentProof: (invoiceId: string, accountName: string, amount: number, proofUrl: string) => Promise<void>;
    onBranchUpdated?: () => void;
  }

  let {
    currentUser,
    initialSubTab = 'profil',
    branches = [],
    subscriptionInvoices = [],
    subscriptionPlans = [],
    onOpenBillingModal,
    onSubmitPaymentProof,
    onBranchUpdated,
  }: Props = $props();

  let isOwner = $derived(currentUser.role === 'OWNER' || currentUser.role === 'SUPERADMIN');

  let activeSubTab = $state<'profil' | 'cabang' | 'billing'>('profil');

  $effect(() => {
    if (initialSubTab === 'billing' && isOwner) {
      activeSubTab = 'billing';
    } else if (initialSubTab === 'cabang' || initialSubTab === 'outlet') {
      activeSubTab = 'cabang';
    } else {
      activeSubTab = 'profil';
    }
  });

  let settingsTabs = $derived([
    { id: 'profil' as const, label: 'Profil Pengguna', icon: UserIcon },
    { id: 'cabang' as const, label: 'Pengaturan Cabang', icon: Building2 },
    ...(isOwner ? [{ id: 'billing' as const, label: 'Langganan & Tagihan', icon: CreditCard }] : []),
  ]);
</script>

<div class="space-y-6 font-sans pb-8">
  <!-- Top Segmented Navigation -->
  <div class="flex items-center justify-between gap-4 overflow-x-auto no-scrollbar py-1">
    <div class="inline-flex items-center gap-1.5 p-1.5 bg-white border border-[#e5e5ea] rounded-2xl shadow-2xs">
      {#each settingsTabs as tab}
        {@const Icon = tab.icon}
        {@const isActive = activeSubTab === tab.id}
        <button
          type="button"
          onclick={() => (activeSubTab = tab.id)}
          class={`px-4 py-2 rounded-xl text-xs font-medium transition-all duration-200 cursor-pointer flex items-center gap-2 shrink-0 ${
            isActive
              ? 'bg-[#17171c] text-white shadow-xs font-semibold'
              : 'text-[#686873] hover:text-[#17171c] hover:bg-[#f4f4f6]'
          }`}
        >
          <Icon class={`w-4 h-4 ${isActive ? 'text-white' : 'text-[#8e8e93]'}`} />
          <span class="whitespace-nowrap">{tab.label}</span>
        </button>
      {/each}
    </div>
  </div>

  <div class="min-w-0 animate-in fade-in duration-200">
    {#if activeSubTab === 'profil'}
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <UserProfileTab {currentUser} />
        <ChangePasswordCard />
      </div>
    {:else if activeSubTab === 'cabang'}
      <BranchSettingsTab {branches} {onBranchUpdated} />
    {:else if activeSubTab === 'billing'}
      <BillingHistoryTab
        {subscriptionInvoices}
        {subscriptionPlans}
        {onOpenBillingModal}
        {onSubmitPaymentProof}
      />
    {/if}
  </div>
</div>
