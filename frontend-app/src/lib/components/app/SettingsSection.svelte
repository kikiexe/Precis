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
    onSubmitPaymentProof: (
      invoiceId: string,
      accountName: string,
      amount: number,
      proofUrl: string
    ) => Promise<void>;
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
    ...(isOwner
      ? [{ id: 'billing' as const, label: 'Langganan & Tagihan', icon: CreditCard }]
      : []),
  ]);
</script>

<div class="space-y-6 pb-8 font-sans">
  <!-- Top Segmented Navigation -->
  <div class="no-scrollbar flex items-center justify-between gap-4 overflow-x-auto py-1">
    <div
      class="inline-flex items-center gap-1.5 rounded-2xl border border-[#e5e5ea] bg-white p-1.5 shadow-2xs"
    >
      {#each settingsTabs as tab}
        {@const Icon = tab.icon}
        {@const isActive = activeSubTab === tab.id}
        <button
          type="button"
          onclick={() => (activeSubTab = tab.id)}
          class={`flex shrink-0 cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-xs font-medium transition-all duration-200 ${
            isActive
              ? 'bg-[#17171c] font-semibold text-white shadow-xs'
              : 'text-[#686873] hover:bg-[#f4f4f6] hover:text-[#17171c]'
          }`}
        >
          <Icon class={`size-4 ${isActive ? 'text-white' : 'text-[#8e8e93]'}`} />
          <span class="whitespace-nowrap">{tab.label}</span>
        </button>
      {/each}
    </div>
  </div>

  <div class="animate-in fade-in min-w-0 duration-200">
    {#if activeSubTab === 'profil'}
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
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
