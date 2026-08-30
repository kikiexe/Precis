<script lang="ts">
  import { Sparkles, CheckCircle2, Upload, X } from 'lucide-svelte';
  import type { SubscriptionInvoice, SubscriptionPlanItem } from '../../../types/app';
  import { formatRupiah } from '../../../utils/formatters';
  import { billingService } from '../../../services/billing-service';

  interface Props {
    subscriptionInvoices: SubscriptionInvoice[];
    subscriptionPlans: SubscriptionPlanItem[];
    onOpenBillingModal: () => void;
    onSubmitPaymentProof: (
      invoiceId: string,
      accountName: string,
      amount: number,
      proofUrl: string
    ) => Promise<void>;
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
  let proofUrl = $state('');
  let proofFile = $state<File | null>(null);
  let isUploadingProof = $state(false);

  function handleFileSelected(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
      proofFile = target.files[0];
    }
  }

  async function handleSendProof() {
    if (!selectedInvoiceId || !proofBankName.trim()) return;
    if (!proofFile && !proofUrl) return;

    isUploadingProof = true;
    try {
      let finalUrl = proofUrl;
      if (proofFile) {
        finalUrl = await billingService.uploadProofImage(proofFile);
      }
      await onSubmitPaymentProof(selectedInvoiceId, proofBankName.trim(), proofAmount, finalUrl);
      isProofModalOpen = false;
      proofFile = null;
    } finally {
      isUploadingProof = false;
    }
  }
</script>

<div class="space-y-6 font-sans">
  <!-- Active Plan Card -->
  <div
    class="flex flex-col justify-between gap-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:flex-row sm:items-center sm:rounded-3xl sm:p-6"
  >
    <div class="space-y-1">
      <div class="flex items-center gap-2">
        <span
          class="rounded-full bg-[#17171c] px-2.5 py-0.5 font-mono text-[10px] font-bold text-white uppercase"
        >
          Langganan SaaS
        </span>
        <span
          class="rounded-full border border-[#a7f3d0] bg-[#ecfdf5] px-2.5 py-0.5 text-xs font-bold text-[#059669]"
        >
          Status: AKTIF
        </span>
      </div>
      <p class="mt-1 text-xs text-[#8e8e93]">
        Kuota 2 Outlet Aktif &bull; {subscriptionPlans.length > 0
          ? subscriptionPlans.map((p) => p.name).join(' / ')
          : 'Sinkronisasi POS Realtime'}
      </p>
    </div>

    <button
      type="button"
      onclick={onOpenBillingModal}
      class="flex cursor-pointer items-center justify-center gap-2 self-stretch rounded-full bg-[#17171c] px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black sm:self-auto"
    >
      <Sparkles class="size-4 text-[#f59e0b]" />
      <span>Perpanjang / Upgrade Paket</span>
    </button>
  </div>

  <!-- Invoices History -->
  <div
    class="overflow-hidden rounded-2xl border border-[#e5e5ea] bg-white shadow-2xs sm:rounded-3xl"
  >
    <div class="flex items-center justify-between border-b border-[#e5e5ea] p-5">
      <div>
        <h3 class="text-base font-bold text-[#17171c]">Riwayat Faktur &amp; Tagihan</h3>
        <p class="text-xs text-[#8e8e93]">Daftar status pembayaran invoice langganan tenant</p>
      </div>
      <span
        class="rounded-full bg-[#f4f4f6] px-3 py-1 font-mono text-xs font-semibold text-[#686873]"
        >{subscriptionInvoices.length} Faktur</span
      >
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-left text-xs">
        <thead>
          <tr
            class="border-b border-[#e5e5ea] bg-[#fafafc] font-mono text-[10.5px] text-[#8e8e93] uppercase"
          >
            <th class="px-5 py-3.5 font-bold">Nomor Invoice</th>
            <th class="px-5 py-3.5 font-bold">Paket Layanan</th>
            <th class="px-5 py-3.5 text-right font-bold">Total Tagihan</th>
            <th class="px-5 py-3.5 text-center font-bold">Status</th>
            <th class="px-5 py-3.5 text-center font-bold">Aksi</th>
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
              <tr class="transition-all hover:bg-[#fafafc]">
                <td class="px-5 py-4">
                  <div class="font-mono font-bold text-[#17171c]">{inv.invoice_number}</div>
                  <div class="font-mono text-[11px] text-[#8e8e93]">
                    Jatuh Tempo: {inv.due_date}
                  </div>
                </td>
                <td class="px-5 py-4">
                  <span class="font-bold text-[#17171c]">{inv.plan_name || 'Standard Tier'}</span>
                </td>
                <td class="px-5 py-4 text-right font-mono font-bold text-[#17171c]">
                  {formatRupiah(inv.total_amount)}
                </td>
                <td class="px-5 py-4 text-center">
                  {#if inv.status === 'PAID'}
                    <span
                      class="rounded-full border border-[#a7f3d0] bg-[#ecfdf5] px-2.5 py-0.5 font-mono text-[10.5px] font-semibold text-[#059669]"
                    >
                      LUNAS
                    </span>
                  {:else if inv.status === 'PENDING_VERIFICATION'}
                    <span
                      class="rounded-full border border-[#fef3c7] bg-[#fffbeb] px-2.5 py-0.5 font-mono text-[10.5px] font-semibold text-[#d97706]"
                    >
                      PROSES VERIFIKASI
                    </span>
                  {:else}
                    <span
                      class="rounded-full border border-[#fecaca] bg-[#fef2f2] px-2.5 py-0.5 font-mono text-[10.5px] font-semibold text-[#dc2626]"
                    >
                      BELUM BAYAR
                    </span>
                  {/if}
                </td>
                <td class="px-5 py-4 text-center">
                  {#if inv.status !== 'PAID'}
                    <button
                      type="button"
                      onclick={() => {
                        selectedInvoiceId = inv.id;
                        proofAmount = inv.total_amount;
                        isProofModalOpen = true;
                      }}
                      class="cursor-pointer rounded-xl bg-[#17171c] px-3.5 py-1.5 text-xs font-semibold text-white shadow-2xs transition-all hover:bg-black"
                    >
                      Unggah Bukti
                    </button>
                  {:else}
                    <span
                      class="flex items-center justify-center gap-1 text-xs font-medium text-[#059669]"
                    >
                      <CheckCircle2 class="size-3.5" />
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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-md space-y-5 rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl sm:p-7"
    >
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-2xl border border-[#bfdbfe] bg-[#eff6ff] text-[#2563eb]"
          >
            <Upload class="size-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Konfirmasi Pembayaran</h3>
            <p class="text-xs text-[#8e8e93]">Kirim bukti transfer bank langganan SaaS</p>
          </div>
        </div>
        <button
          type="button"
          onclick={() => (isProofModalOpen = false)}
          class="cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
        >
          <X class="size-5" />
        </button>
      </div>

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="proof-bank" class="block font-bold text-[#17171c]"
            >Nama Pemilik Rekening Pengirim</label
          >
          <input
            id="proof-bank"
            type="text"
            bind:value={proofBankName}
            placeholder="Contoh: PT Norde Kuliner Jaya"
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1.5">
          <label for="proof-amt" class="block font-bold text-[#17171c]"
            >Nominal yang Ditransfer</label
          >
          <input
            id="proof-amt"
            type="number"
            bind:value={proofAmount}
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1.5">
          <label for="proof-file" class="block font-bold text-[#17171c]"
            >Berkas Foto Bukti Transfer</label
          >
          <input
            id="proof-file"
            type="file"
            accept="image/png,image/jpeg,image/webp"
            onchange={handleFileSelected}
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2 text-xs text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
          {#if proofFile}
            <p class="text-[11px] font-medium text-[#059669]">Terpilih: {proofFile.name}</p>
          {/if}
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="button"
          onclick={() => (isProofModalOpen = false)}
          class="flex-1 cursor-pointer rounded-full border border-[#e5e5ea] py-3 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isUploadingProof || !proofBankName.trim() || (!proofFile && !proofUrl)}
          onclick={handleSendProof}
          class="flex-1 cursor-pointer rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
        >
          {isUploadingProof ? 'Mengirim...' : 'Kirim Bukti'}
        </button>
      </div>
    </div>
  </div>
{/if}
