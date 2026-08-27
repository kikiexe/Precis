<script lang="ts">
  import { Sparkles, CheckCircle2, Upload, X } from 'lucide-svelte';
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
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
    <div class="space-y-1">
      <div class="flex items-center gap-2">
        <span class="text-[10px] font-mono uppercase px-2.5 py-0.5 rounded-full bg-[#17171c] text-white font-bold">
          Langganan SaaS
        </span>
        <span class="text-xs text-[#059669] font-bold bg-[#ecfdf5] px-2.5 py-0.5 rounded-full border border-[#a7f3d0]">
          Status: AKTIF
        </span>
      </div>
      <p class="text-xs text-[#8e8e93] mt-1">
        Kuota 2 Outlet Aktif &bull; {subscriptionPlans.length > 0 ? subscriptionPlans.map((p) => p.name).join(' / ') : 'Sinkronisasi POS Realtime'}
      </p>
    </div>

    <button
      type="button"
      onclick={onOpenBillingModal}
      class="px-5 py-2.5 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 self-stretch sm:self-auto shadow-xs"
    >
      <Sparkles class="w-4 h-4 text-[#f59e0b]" />
      <span>Perpanjang / Upgrade Paket</span>
    </button>
  </div>

  <!-- Invoices History -->
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl overflow-hidden shadow-2xs">
    <div class="p-5 border-b border-[#e5e5ea] flex items-center justify-between">
      <div>
        <h3 class="text-base font-bold text-[#17171c]">Riwayat Faktur &amp; Tagihan</h3>
        <p class="text-xs text-[#8e8e93]">Daftar status pembayaran invoice langganan tenant</p>
      </div>
      <span class="text-xs font-mono font-semibold text-[#686873] bg-[#f4f4f6] px-3 py-1 rounded-full">{subscriptionInvoices.length} Faktur</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-[#fafafc] border-b border-[#e5e5ea] text-[#8e8e93] font-mono uppercase text-[10.5px]">
            <th class="py-3.5 px-5 font-bold">Nomor Invoice</th>
            <th class="py-3.5 px-5 font-bold">Paket Layanan</th>
            <th class="py-3.5 px-5 text-right font-bold">Total Tagihan</th>
            <th class="py-3.5 px-5 text-center font-bold">Status</th>
            <th class="py-3.5 px-5 text-center font-bold">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#e5e5ea]">
          {#if subscriptionInvoices.length === 0}
            <tr>
              <td colspan="5" class="py-12 text-center text-[#8e8e93]">
                Belum ada riwayat tagihan faktur.
              </td>
            </tr>
          {:else}
            {#each subscriptionInvoices as inv}
              <tr class="hover:bg-[#fafafc] transition-all">
                <td class="py-4 px-5">
                  <div class="font-mono font-bold text-[#17171c]">{inv.invoice_number}</div>
                  <div class="text-[11px] text-[#8e8e93] font-mono">Jatuh Tempo: {inv.due_date}</div>
                </td>
                <td class="py-4 px-5">
                  <span class="font-bold text-[#17171c]">{inv.plan_name || 'Standard Tier'}</span>
                </td>
                <td class="py-4 px-5 text-right font-mono font-bold text-[#17171c]">
                  {formatRupiah(inv.total_amount)}
                </td>
                <td class="py-4 px-5 text-center">
                  {#if inv.status === 'PAID'}
                    <span class="px-2.5 py-0.5 rounded-full bg-[#ecfdf5] text-[#059669] text-[10.5px] font-mono font-semibold border border-[#a7f3d0]">
                      LUNAS
                    </span>
                  {:else if inv.status === 'PENDING_VERIFICATION'}
                    <span class="px-2.5 py-0.5 rounded-full bg-[#fffbeb] text-[#d97706] text-[10.5px] font-mono font-semibold border border-[#fef3c7]">
                      PROSES VERIFIKASI
                    </span>
                  {:else}
                    <span class="px-2.5 py-0.5 rounded-full bg-[#fef2f2] text-[#dc2626] text-[10.5px] font-mono font-semibold border border-[#fecaca]">
                      BELUM BAYAR
                    </span>
                  {/if}
                </td>
                <td class="py-4 px-5 text-center">
                  {#if inv.status !== 'PAID'}
                    <button
                      type="button"
                      onclick={() => {
                        selectedInvoiceId = inv.id;
                        proofAmount = inv.total_amount;
                        isProofModalOpen = true;
                      }}
                      class="px-3.5 py-1.5 bg-[#17171c] hover:bg-black text-white rounded-xl text-xs font-semibold cursor-pointer transition-all shadow-2xs"
                    >
                      Unggah Bukti
                    </button>
                  {:else}
                    <span class="text-xs text-[#059669] font-medium flex items-center justify-center gap-1">
                      <CheckCircle2 class="w-3.5 h-3.5" />
                      <span>Terverifikasi</span>
                    </span>
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

<!-- Modal: Upload Bukti Transfer -->
{#if isProofModalOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl p-6 sm:p-7 w-full max-w-md space-y-5 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between pb-3 border-b border-[#f2f2f4]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#eff6ff] text-[#2563eb] flex items-center justify-center border border-[#bfdbfe]">
            <Upload class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Konfirmasi Pembayaran</h3>
            <p class="text-xs text-[#8e8e93]">Kirim bukti transfer bank langganan SaaS</p>
          </div>
        </div>
        <button
          type="button"
          onclick={() => (isProofModalOpen = false)}
          class="p-2 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-xl cursor-pointer transition-all"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="proof-bank" class="block font-bold text-[#17171c]">Nama Pemilik Rekening Pengirim</label>
          <input
            id="proof-bank"
            type="text"
            bind:value={proofBankName}
            placeholder="Contoh: PT Norde Kuliner Jaya"
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
        </div>

        <div class="space-y-1.5">
          <label for="proof-amt" class="block font-bold text-[#17171c]">Nominal yang Ditransfer</label>
          <input
            id="proof-amt"
            type="number"
            bind:value={proofAmount}
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="button"
          onclick={() => (isProofModalOpen = false)}
          class="flex-1 py-3 border border-[#e5e5ea] hover:bg-[#f4f4f6] text-[#686873] text-xs font-semibold rounded-full cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isUploadingProof || !proofBankName.trim()}
          onclick={handleSendProof}
          class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all disabled:opacity-50 shadow-xs"
        >
          {isUploadingProof ? 'Mengirim...' : 'Kirim Bukti'}
        </button>
      </div>
    </div>
  </div>
{/if}
