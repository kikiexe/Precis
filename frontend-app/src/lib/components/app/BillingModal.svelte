<script lang="ts">
  import { CreditCard, Upload, AlertCircle, Plus, X } from 'lucide-svelte';
  import type { SubscriptionInvoice, SubscriptionPlanItem, UserProfile } from '../../types/app';
  import { billingService } from '../../services/billing-service';

  interface Props {
    isOpen: boolean;
    userProfile?: UserProfile | null;
    activeInvoice?: SubscriptionInvoice | null;
    plans?: SubscriptionPlanItem[];
    onClose: () => void;
    onInvoiceUpdated?: () => Promise<void> | void;
  }

  let {
    isOpen = false,
    userProfile = null,
    activeInvoice = null,
    plans = [],
    onClose,
    onInvoiceUpdated,
  }: Props = $props();

  let senderAccountName = $state('');
  let transferAmount = $state(0);
  let proofFile = $state<File | null>(null);
  let isUploadingProof = $state(false);
  let isCreatingInvoice = $state(false);
  let errorMessage = $state<string | null>(null);
  let actionMessage = $state<string | null>(null);

  $effect(() => {
    if (activeInvoice) {
      transferAmount = activeInvoice.total_amount;
    }
  });

  function formatRp(num: number): string {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(num);
  }

  function getStatusLabel(status: string): string {
    switch (status) {
      case 'ACTIVE':
        return 'AKTIF NORMAL';
      case 'TRIAL':
        return 'MASA UJI COBA';
      case 'GRACE_PERIOD':
        return 'MASA TENGGANG';
      case 'SUSPENDED':
        return 'LAYANAN DITANGGUHKAN';
      default:
        return status;
    }
  }

  function handleFileSelected(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
      proofFile = target.files[0];
    }
  }

  async function handleCreateInvoice() {
    if (plans.length === 0) {
      errorMessage = 'Daftar paket belum tersedia.';
      return;
    }

    isCreatingInvoice = true;
    errorMessage = null;
    actionMessage = null;

    try {
      await billingService.createInvoice(plans[0].id, 'MONTHLY');
      actionMessage = 'Faktur perpanjangan baru berhasil diterbitkan.';
      if (onInvoiceUpdated) {
        await onInvoiceUpdated();
      }
    } catch (err: unknown) {
      errorMessage = err instanceof Error ? err.message : 'Gagal membuat faktur tagihan.';
    } finally {
      isCreatingInvoice = false;
    }
  }

  async function handleSubmitProof() {
    if (!activeInvoice) return;

    if (!senderAccountName.trim()) {
      errorMessage = 'Nama pemilik rekening pengirim wajib diisi.';
      return;
    }

    if (transferAmount <= 0) {
      errorMessage = 'Nominal transfer tidak valid.';
      return;
    }

    if (!proofFile) {
      errorMessage = 'Pilih berkas foto bukti transfer.';
      return;
    }

    isUploadingProof = true;
    errorMessage = null;
    actionMessage = null;

    try {
      const proofUrl = await billingService.uploadProofImage(proofFile);
      await billingService.submitProof(
        activeInvoice.id,
        senderAccountName.trim(),
        transferAmount,
        proofUrl
      );

      actionMessage = 'Bukti pembayaran terkirim. Menunggu verifikasi tim Superadmin.';
      proofFile = null;
      senderAccountName = '';

      if (onInvoiceUpdated) {
        await onInvoiceUpdated();
      }
    } catch (err: unknown) {
      errorMessage = err instanceof Error ? err.message : 'Gagal mengirim bukti transfer.';
    } finally {
      isUploadingProof = false;
    }
  }
</script>

{#if isOpen}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in fade-in zoom-in-95 max-h-[90vh] w-full max-w-lg space-y-5 overflow-y-auto rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl sm:p-7"
    >
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-2xl border border-[#bfdbfe] bg-[#eff6ff] text-[#2563eb]"
          >
            <CreditCard class="size-5" />
          </div>
          <div>
            <h2 class="text-base font-bold text-[#17171c]">Status Langganan &amp; Billing</h2>
            <p class="text-xs text-[#8e8e93]">Informasi paket SaaS dan konfirmasi pembayaran</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
        >
          <X class="size-5" />
        </button>
      </div>

      {#if actionMessage}
        <div
          class="rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] p-3.5 text-xs font-semibold text-[#065f46]"
        >
          {actionMessage}
        </div>
      {/if}

      {#if errorMessage}
        <div
          class="flex items-start gap-2 rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-semibold text-[#991b1b]"
        >
          <AlertCircle class="mt-0.5 size-4 shrink-0" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <!-- Status Langganan Akun -->
      <div
        class="space-y-2.5 rounded-2xl border border-[#e5e5ea] bg-[#f8f8fa] p-4 font-mono text-xs shadow-2xs"
      >
        <div class="flex items-center justify-between">
          <span class="font-sans text-[#8e8e93]">Status Layanan:</span>
          <span
            class={`rounded-full px-2.5 py-0.5 text-[10.5px] font-bold ${
              userProfile?.subscription_status === 'ACTIVE'
                ? 'border border-[#a7f3d0] bg-[#ecfdf5] text-[#059669]'
                : userProfile?.subscription_status === 'GRACE_PERIOD'
                  ? 'border border-[#fef3c7] bg-[#fffbeb] text-[#d97706]'
                  : userProfile?.subscription_status === 'SUSPENDED'
                    ? 'border border-[#fecaca] bg-[#fef2f2] text-[#dc2626]'
                    : 'border border-[#bfdbfe] bg-[#eff6ff] text-[#2563eb]'
            }`}
          >
            {getStatusLabel(userProfile?.subscription_status || 'TRIAL')}
          </span>
        </div>
        <div class="flex justify-between text-[#686873]">
          <span class="font-sans">Maksimal Outlet:</span>
          <span class="font-bold text-[#17171c]">{userProfile?.max_workspaces || 1} Cabang</span>
        </div>
        <div class="flex justify-between text-[#686873]">
          <span class="font-sans">Masa Aktif Hingga:</span>
          <span class="font-bold text-[#17171c]">
            {userProfile?.subscription_expires_at
              ? userProfile.subscription_expires_at.split('T')[0]
              : 'Uji Coba'}
          </span>
        </div>
      </div>

      <!-- Kartu Faktur Tagihan -->
      {#if !activeInvoice}
        <div class="space-y-3 rounded-2xl border border-[#e5e5ea] bg-[#fafafc] p-8 text-center">
          <p class="text-xs text-[#8e8e93]">Belum ada faktur tagihan yang sedang aktif.</p>
          <button
            type="button"
            disabled={isCreatingInvoice || plans.length === 0}
            onclick={handleCreateInvoice}
            class="mx-auto flex cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
          >
            <Plus class="size-4" />
            <span>{isCreatingInvoice ? 'Menerbitkan...' : 'Terbitkan Faktur Baru'}</span>
          </button>
        </div>
      {:else}
        <div class="space-y-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 text-xs shadow-2xs">
          <div class="text-sm font-bold text-[#17171c]">Faktur #{activeInvoice.invoice_number}</div>

          <div
            class="space-y-2 rounded-xl border border-[#ececee] bg-[#f8f8fa] p-4 font-mono text-xs"
          >
            <div class="flex justify-between text-[#8e8e93]">
              <span class="font-sans">Nominal Dasar:</span>
              <span class="text-[#17171c]">{formatRp(activeInvoice.amount_base)}</span>
            </div>
            <div class="flex justify-between font-semibold text-[#2563eb]">
              <span class="font-sans">Kode Unik 3-Digit:</span>
              <span>+{activeInvoice.unique_code}</span>
            </div>
            <div
              class="mt-1 flex justify-between border-t border-[#e5e5ea] pt-2 text-sm font-bold text-[#17171c]"
            >
              <span class="font-sans">Total Transfer Tepat:</span>
              <span class="text-[#dc2626]">{formatRp(activeInvoice.total_amount)}</span>
            </div>
          </div>

          <div class="space-y-1 font-mono text-xs text-[#686873]">
            <div>
              Bank Tujuan: <strong class="text-[#17171c]">BCA 800-123-4567</strong> (PT Precis Digital)
            </div>
            <div class="text-[11px] text-[#8e8e93]">
              Wajib transfer tepat hingga 3 digit terakhir untuk verifikasi otomatis.
            </div>
          </div>

          <!-- Form Upload Bukti Transfer -->
          <div class="space-y-3 border-t border-[#f2f2f4] pt-3">
            <div class="space-y-1.5">
              <label for="sender-acc" class="block font-bold text-[#17171c]"
                >Nama Pemilik Rekening Pengirim</label
              >
              <input
                id="sender-acc"
                type="text"
                bind:value={senderAccountName}
                placeholder="Contoh: PT Norde Kuliner Jaya"
                class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              />
            </div>

            <div class="space-y-1.5">
              <label for="proof-file" class="block font-bold text-[#17171c]"
                >Pilih Berkas Foto Bukti Transfer</label
              >
              <input
                id="proof-file"
                type="file"
                accept="image/*"
                onchange={handleFileSelected}
                class="w-full cursor-pointer rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2 text-xs text-[#686873] file:mr-3 file:rounded-lg file:border-0 file:bg-[#17171c] file:px-3 file:py-1 file:text-xs file:font-semibold file:text-white"
              />
            </div>

            <button
              type="button"
              disabled={isUploadingProof || !senderAccountName.trim() || !proofFile}
              onclick={handleSubmitProof}
              class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
            >
              <Upload class="size-4" />
              <span>{isUploadingProof ? 'Mengunggah Bukti...' : 'Kirim Bukti Pembayaran'}</span>
            </button>
          </div>
        </div>
      {/if}
    </div>
  </div>
{/if}
