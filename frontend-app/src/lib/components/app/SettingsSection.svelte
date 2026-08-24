<script lang="ts">
  import {
    Building2,
    CreditCard,
    Check,
    Sparkles
  } from 'lucide-svelte';
  import type {
    SubscriptionInvoice,
    SubscriptionPlanItem,
    User
  } from '../../types/app';

  interface Props {
    currentUser: User;
    initialSubTab?: string;
    subscriptionInvoices?: SubscriptionInvoice[];
    subscriptionPlans?: SubscriptionPlanItem[];
    onOpenBillingModal: () => void;
    onSubmitPaymentProof: (invoiceId: string, accountName: string, amount: number, proofUrl: string) => Promise<void>;
  }

  let {
    currentUser,
    initialSubTab = 'outlet',
    subscriptionInvoices = [],
    subscriptionPlans = [],
    onOpenBillingModal,
    onSubmitPaymentProof,
  }: Props = $props();

  let activeSubTab = $state<'outlet' | 'billing'>('outlet');

  $effect(() => {
    if (initialSubTab === 'billing' || initialSubTab === 'outlet') {
      activeSubTab = initialSubTab;
    }
  });

  // Outlet Settings Form
  let branchName = $state('');
  let geofenceRadius = $state(50);
  let latePenaltyRate = $state(5000);
  let isSavedSuccess = $state(false);

  $effect(() => {
    branchName = currentUser.branch_name || 'Amore Outlet Sleman #01';
  });

  // Proof Modal
  let isProofModalOpen = $state(false);
  let selectedInvoiceId = $state<string | null>(null);
  let proofBankName = $state('');
  let proofAmount = $state(299000);
  let proofUrl = $state('https://r2.precis.id/proofs/bukti_transfer_sample.webp');
  let isUploadingProof = $state(false);

  function formatRp(num: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
  }

  function handleSaveOutlet() {
    isSavedSuccess = true;
    setTimeout(() => {
      isSavedSuccess = false;
    }, 3000);
  }

  async function handleSendProof() {
    if (!selectedInvoiceId || !proofBankName.trim()) return;
    isUploadingProof = true;
    try {
      await onSubmitPaymentProof(selectedInvoiceId, proofBankName.trim(), proofAmount, proofUrl);
      isProofModalOpen = false;
    } catch (e) {
      console.error(e);
    } finally {
      isUploadingProof = false;
    }
  }
</script>

<div class="space-y-6 font-sans">
  <!-- Top Segmented Navigation Wrapper -->
  <div class="bg-white border border-[#d9d9dd] rounded-[24px] p-2 sm:p-2.5 flex items-center justify-between gap-2">
    <div class="flex items-center gap-1.5 w-full sm:w-auto bg-[#eeece7]/40 sm:bg-transparent p-1 sm:p-0 rounded-full">
      <button
        type="button"
        title="Outlet"
        onclick={() => (activeSubTab = 'outlet')}
        class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
          activeSubTab === 'outlet'
            ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
            : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
        }`}
      >
        <Building2 class="w-4 h-4 shrink-0" />
        {#if activeSubTab === 'outlet'}
          <span class="whitespace-nowrap truncate">Outlet</span>
        {/if}
      </button>

      <button
        type="button"
        title="Langganan"
        onclick={() => (activeSubTab = 'billing')}
        class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
          activeSubTab === 'billing'
            ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
            : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
        }`}
      >
        <CreditCard class="w-4 h-4 shrink-0" />
        {#if activeSubTab === 'billing'}
          <span class="whitespace-nowrap truncate">Langganan</span>
        {/if}
      </button>
    </div>
  </div>

  <!-- SUBTAB 1: OUTLET & OPERASIONAL -->
  {#if activeSubTab === 'outlet'}
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] p-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-medium text-[#212121]">Pengaturan Cabang &amp; Presensi Geofence</h2>
          <p class="text-xs text-[#75758a]">Konfigurasi toleransi GPS radius dan tarif denda kedisiplinan</p>
        </div>

        {#if isSavedSuccess}
          <div class="px-3.5 py-1.5 rounded-full bg-[#edfce9] text-[#003c33] text-xs font-medium flex items-center gap-1.5">
            <Check class="w-3.5 h-3.5" />
            <span>Pengaturan berhasil disimpan</span>
          </div>
        {/if}
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
        <div class="space-y-4">
          <div class="space-y-1.5">
            <label for="setting-branch-name" class="block font-medium text-[#212121]">Nama Cabang Outlet</label>
            <input
              id="setting-branch-name"
              type="text"
              bind:value={branchName}
              class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="space-y-1.5">
            <label for="setting-geofence" class="font-medium text-[#212121] flex justify-between">
              <span>Radius Toleransi GPS Presensi ({geofenceRadius} Meter)</span>
              <span class="text-[10px] text-[#75758a]">Maksimal jarak selfie sah</span>
            </label>
            <input
              id="setting-geofence"
              type="range"
              min="20"
              max="200"
              step="5"
              bind:value={geofenceRadius}
              class="w-full accent-[#17171c] cursor-pointer"
            />
          </div>
        </div>

        <div class="space-y-4">
          <div class="space-y-1.5">
            <label for="setting-penalty" class="block font-medium text-[#212121]">Tarif Denda Terlambat per Menit (IDR)</label>
            <input
              id="setting-penalty"
              type="number"
              bind:value={latePenaltyRate}
              class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
            <span class="text-[10px] text-[#75758a]">Otomatis memotong estimasi take home pay slip gaji</span>
          </div>

          <div class="pt-3">
            <button
              type="button"
              onclick={handleSaveOutlet}
              class="w-full py-2.5 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full transition-all cursor-pointer"
            >
              Simpan Konfigurasi Operasional
            </button>
          </div>
        </div>
      </div>
    </div>
  {/if}

  <!-- SUBTAB 2: BILLING & LANGGANAN SAAS -->
  {#if activeSubTab === 'billing'}
    <div class="space-y-6">
      <!-- Active Plan Card -->
      <div class="bg-white border border-[#d9d9dd] rounded-[24px] p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-mono uppercase px-2.5 py-0.5 rounded-full bg-[#17171c] text-white font-medium">
              Multi-Tenant SaaS
            </span>
            <span class="text-xs text-[#003c33] font-medium bg-[#edfce9] px-2 py-0.5 rounded-full">
              Status: {currentUser.role === 'OWNER' ? 'AKTIF (TRIAL 14 HARI / SUBSCRIPTION)' : 'AKTIF'}
            </span>
          </div>
          <h2 class="text-xl font-medium text-[#212121]">Langganan {currentUser.branch_name || 'Bisnis Anda'}</h2>
          <p class="text-xs text-[#616161]">
            Kelola faktur langganan, tambah cabang, atau lakukan perpanjangan layanan.
          </p>

        <button
          type="button"
          onclick={onOpenBillingModal}
          class="px-5 py-2.5 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full transition-all cursor-pointer flex items-center gap-2 self-start md:self-auto"
        >
          <Sparkles class="w-4 h-4" />
          <span>Tingkatkan / Perpanjang Paket</span>
        </button>
      </div>

      {#if subscriptionPlans.length > 0}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          {#each subscriptionPlans as plan}
            <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-5 space-y-3">
              <div class="flex items-center justify-between">
                <h4 class="font-medium text-[#212121]">{plan.name}</h4>
                <span class="text-[10px] font-mono bg-[#eeece7] px-2 py-0.5 rounded-full text-[#616161]">
                  Hingga {plan.max_workspaces} Outlet
                </span>
              </div>
              <div class="text-xl font-medium font-mono text-[#17171c]">
                {formatRp(plan.monthly_price)} <span class="text-xs font-normal text-[#75758a]">/bln</span>
              </div>
            </div>
          {/each}
        </div>
      {/if}

      <!-- Invoices History -->
      <div class="bg-white border border-[#d9d9dd] rounded-[24px] overflow-hidden">
        <div class="p-6 border-b border-[#d9d9dd]">
          <h3 class="text-base font-medium text-[#212121]">Riwayat Tagihan &amp; Faktur SaaS</h3>
          <p class="text-xs text-[#75758a]">Daftar pembayaran langganan portal multi-tenant</p>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-[#eeece7]/40 border-b border-[#d9d9dd] text-[#75758a] font-mono uppercase text-[10px]">
                <th class="py-3.5 px-5">Nomor Invoice</th>
                <th class="py-3.5 px-5">Paket</th>
                <th class="py-3.5 px-5 text-right">Total Tagihan</th>
                <th class="py-3.5 px-5 text-center">Status</th>
                <th class="py-3.5 px-5 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#d9d9dd]">
              {#if subscriptionInvoices.length === 0}
                <tr>
                  <td colspan="5" class="py-12 text-center text-[#75758a]">
                    Belum ada riwayat tagihan faktur.
                  </td>
                </tr>
              {:else}
                {#each subscriptionInvoices as inv}
                  <tr class="hover:bg-[#eeece7]/20 transition-all">
                    <td class="py-4 px-5">
                      <div class="font-mono font-medium text-[#212121]">{inv.invoice_number}</div>
                      <div class="text-[10px] text-[#75758a] font-mono">Due: {inv.due_date}</div>
                    </td>
                    <td class="py-4 px-5 text-[#616161]">{inv.plan_name || 'Standard Tier'}</td>
                    <td class="py-4 px-5 text-right font-mono font-medium text-[#17171c]">{formatRp(inv.total_amount)}</td>
                    <td class="py-4 px-5 text-center">
                      <span class={`text-[10px] font-mono font-medium px-2 py-0.5 rounded-full ${
                        inv.status === 'PAID' ? 'bg-[#edfce9] text-[#003c33]' : inv.status === 'PENDING_VERIFICATION' ? 'bg-[#f1f5ff] text-[#1863dc]' : 'bg-[#ffefef] text-[#e5484d]'
                      }`}>
                        {inv.status}
                      </span>
                    </td>
                    <td class="py-4 px-5 text-center">
                      {#if inv.status === 'UNPAID'}
                        <button
                          type="button"
                          onclick={() => {
                            selectedInvoiceId = inv.id;
                            proofAmount = inv.total_amount;
                            isProofModalOpen = true;
                          }}
                          class="px-3 py-1 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full transition-all cursor-pointer"
                        >
                          Kirim Bukti Bayar
                        </button>
                      {:else}
                        <span class="text-xs text-[#75758a] font-mono">-</span>
                      {/if}
                    </td>
                  </tr>
                {/each}
              {/if}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  {/if}
</div>

<!-- Modal Kirim Bukti Pembayaran -->
{#if isProofModalOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-md p-6 space-y-4 shadow-2xl">
      <h3 class="text-base font-medium text-[#212121]">Kirim Bukti Pembayaran Faktur</h3>
      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="proof-sender-name" class="block font-medium text-[#212121]">Nama Rekening Pengirim (Atas Nama)</label>
          <input
            id="proof-sender-name"
            type="text"
            bind:value={proofBankName}
            placeholder="Contoh: Arief Wicaksono"
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
        <div class="space-y-1">
          <label for="proof-amount" class="block font-medium text-[#212121]">Nominal Transfer (IDR)</label>
          <input
            id="proof-amount"
            type="number"
            bind:value={proofAmount}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
      </div>
      <div class="flex gap-3 pt-2">
        <button
          type="button"
          onclick={() => (isProofModalOpen = false)}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSendProof}
          disabled={isUploadingProof}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer disabled:opacity-50"
        >
          Kirim Bukti
        </button>
      </div>
    </div>
  </div>
{/if}
