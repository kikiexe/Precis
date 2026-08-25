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

  let activeSubTab = $state<'profil' | 'cabang' | 'billing'>('profil');

  $effect(() => {
    if (initialSubTab === 'billing' || initialSubTab === 'cabang' || initialSubTab === 'profil' || initialSubTab === 'outlet') {
      activeSubTab = initialSubTab === 'outlet' ? 'cabang' : (initialSubTab as 'profil' | 'cabang' | 'billing');
    }
  });
</script>

<div class="space-y-6 font-sans">
  <!-- Top Segmented Navigation Wrapper -->
  <div class="bg-white border border-[#d9d9dd] rounded-[24px] p-2 sm:p-2.5 flex items-center justify-between gap-2">
    <div class="flex items-center gap-1.5 w-full sm:w-auto bg-[#eeece7]/40 sm:bg-transparent p-1 sm:p-0 rounded-full">
      <button
        type="button"
        title="Profil Pengguna"
        onclick={() => (activeSubTab = 'profil')}
        class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
          activeSubTab === 'profil'
            ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
            : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
        }`}
      >
        <UserIcon class="w-4 h-4 shrink-0" />
        {#if activeSubTab === 'profil'}
          <span class="whitespace-nowrap truncate">Profil Pengguna</span>
        {/if}
      </button>

      <button
        type="button"
        title="Pengaturan Cabang"
        onclick={() => (activeSubTab = 'cabang')}
        class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
          activeSubTab === 'cabang'
            ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
            : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
        }`}
      >
        <Building2 class="w-4 h-4 shrink-0" />
        {#if activeSubTab === 'cabang'}
          <span class="whitespace-nowrap truncate">Pengaturan Cabang</span>
        {/if}
      </button>

      <button
        type="button"
        title="Langganan & Tagihan"
        onclick={() => (activeSubTab = 'billing')}
        class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
          activeSubTab === 'billing'
            ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
            : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
        }`}
      >
        <CreditCard class="w-4 h-4 shrink-0" />
        {#if activeSubTab === 'billing'}
          <span class="whitespace-nowrap truncate">Langganan &amp; Tagihan</span>
        {/if}
      </button>
    </div>
  </div>

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
