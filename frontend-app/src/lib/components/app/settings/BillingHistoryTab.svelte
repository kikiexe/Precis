<script lang="ts">
  import { Sparkles } from 'lucide-svelte';
  import type { SubscriptionInvoice, SubscriptionPlanItem } from '../../../types/app';
  import { formatRupiah } from '../../../utils/formatters';

  interface Props {
    subscriptionInvoices: SubscriptionInvoice[];
    subscriptionPlans: SubscriptionPlanItem[];
    onOpenBillingModal: () => void;
    onSubmitPaymentProof: (invoiceId: string, accountName: string, amount: number, proofUrl: string) => Promise<void>;
  }

  let {
    subscriptionInvoices = [],
    subscriptionPlans = [],
    onOpenBillingModal,
    onSubmitPaymentProof,
  }: Props = $props();

  let isProofModalOpen = $state(false);
  let selectedInvoiceId = $state<string | null>(null);
  let proofBankName = $state('');
  let proofAmount = $state(299000);
  let proofUrl = $state('https://r2.precis.id/proofs/bukti_transfer_sample.webp');
  let isUploadingProof = $state(false);

  async function handleSendProof() {
    if (!selectedInvoiceId || !proofBankName.trim()) return;
    isUploadingProof = true;
    try {
      await onSubmitPaymentProof(selectedInvoiceId, proofBankName.trim(), proofAmount, proofUrl);
      isProofModalOpen = false;
    } finally {
      isUploadingProof = false;
    }
  }
</script>

<div class="space-y-6 font-sans">
  <!-- Active Plan Card -->
  <div class="bg-white border border-[#d9d9dd] rounded-[24px] p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2">
        <span class="text-[10px] font-mono uppercase px-2.5 py-0.5 rounded-full bg-[#17171c] text-white font-medium">
          Langganan SaaS
        </span>
        <span class="text-xs text-[#003c33] font-medium bg-[#edfce9] px-2.5 py-0.5 rounded-full border border-[#bbf7d0]">
          Status: AKTIF
        </span>
      </div>
      <p class="text-xs text-[#75758a] mt-1">
        Kuota 2 Outlet Aktif &bull; {subscriptionPlans.length > 0 ? subscriptionPlans.map((p) => p.name).join(' / ') : 'Sinkronisasi POS Realtime'}
      </p>
    </div>

    <button
      type="button"
      onclick={onOpenBillingModal}
      class="px-4 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5 self-stretch sm:self-auto"
    >
      <Sparkles class="w-3.5 h-3.5" />
      <span>Perpanjang / Upgrade Paket</span>
    </button>
  </div>

  <!-- Invoices History -->
  <div class="bg-white border border-[#d9d9dd] rounded-[24px] overflow-hidden">
    <div class="p-5 border-b border-[#d9d9dd] flex items-center justify-between">
      <div>
        <h3 class="text-sm font-medium text-[#212121]">Riwayat Faktur &amp; Tagihan</h3>
        <p class="text-xs text-[#75758a]">Daftar status pembayaran invoice langganan tenant</p>
      </div>
      <span class="text-[10px] font-mono text-[#75758a]">{subscriptionInvoices.length} Faktur</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-[#eeece7]/40 border-b border-[#d9d9dd] text-[#75758a] font-mono uppercase text-[10px]">
            <th class="py-3 px-4">Nomor Invoice</th>
            <th class="py-3 px-4">Paket Layanan</th>
            <th class="py-3 px-4 text-right">Total Tagihan</th>
            <th class="py-3 px-4 text-center">Status</th>
            <th class="py-3 px-4 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#d9d9dd]">
          {#if subscriptionInvoices.length === 0}
            <tr>
              <td colspan="5" class="py-8 text-center text-[#75758a]">
                Belum ada riwayat tagihan faktur.
              </td>
            </tr>
          {:else}
            {#each subscriptionInvoices as inv}
              <tr class="hover:bg-[#eeece7]/20 transition-all">
                <td class="py-3 px-4">
                  <div class="font-mono font-medium text-[#212121]">{inv.invoice_number}</div>
                  <div class="text-[10px] text-[#75758a] font-mono">Jatuh Tempo: {inv.due_date}</div>
                </td>
                <td class="py-3 px-4 text-[#616161]">{inv.plan_name || 'Standard Tier'}</td>
                <td class="py-3 px-4 text-right font-mono font-medium text-[#17171c]">{formatRupiah(inv.total_amount)}</td>
                <td class="py-3 px-4 text-center">
                  <span class={`text-[10px] font-mono font-medium px-2 py-0.5 rounded-full ${
                    inv.status === 'PAID' ? 'bg-[#edfce9] text-[#003c33]' : inv.status === 'PENDING_VERIFICATION' ? 'bg-[#f1f5ff] text-[#1863dc]' : 'bg-[#ffefef] text-[#e5484d]'
                  }`}>
                    {inv.status}
                  </span>
                </td>
                <td class="py-3 px-4 text-center">
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
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
        <div class="space-y-1">
          <label for="proof-amount" class="block font-medium text-[#212121]">Nominal Transfer (IDR)</label>
          <input
            id="proof-amount"
            type="number"
            bind:value={proofAmount}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
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
