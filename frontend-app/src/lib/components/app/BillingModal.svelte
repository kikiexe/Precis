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
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
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
  <div class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4 select-none font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-xl space-y-5 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#eff6ff] text-[#2563eb] flex items-center justify-center border border-[#bfdbfe]">
            <CreditCard class="w-5 h-5" />
          </div>
          <div>
            <h2 class="text-base font-bold text-[#17171c]">Status Langganan &amp; Billing</h2>
            <p class="text-xs text-[#8e8e93]">Informasi paket SaaS dan konfirmasi pembayaran</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="p-2 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-xl cursor-pointer transition-all"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      {#if actionMessage}
        <div class="p-3.5 bg-[#ecfdf5] border border-[#a7f3d0] text-[#065f46] text-xs font-semibold rounded-2xl">
          {actionMessage}
        </div>
      {/if}

      {#if errorMessage}
        <div class="p-3.5 bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs font-semibold rounded-2xl flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <!-- Status Langganan Akun -->
      <div class="p-4 bg-[#f8f8fa] border border-[#e5e5ea] rounded-2xl space-y-2.5 text-xs font-mono shadow-2xs">
        <div class="flex justify-between items-center">
          <span class="text-[#8e8e93] font-sans">Status Layanan:</span>
          <span class={`text-[10.5px] px-2.5 py-0.5 rounded-full font-bold ${
            userProfile?.subscription_status === 'ACTIVE'
              ? 'bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0]'
              : userProfile?.subscription_status === 'GRACE_PERIOD'
              ? 'bg-[#fffbeb] text-[#d97706] border border-[#fef3c7]'
              : userProfile?.subscription_status === 'SUSPENDED'
              ? 'bg-[#fef2f2] text-[#dc2626] border border-[#fecaca]'
              : 'bg-[#eff6ff] text-[#2563eb] border border-[#bfdbfe]'
          }`}>
            {getStatusLabel(userProfile?.subscription_status || 'TRIAL')}
          </span>
        </div>
        <div class="flex justify-between text-[#686873]">
          <span class="font-sans">Maksimal Outlet:</span>
          <span class="font-bold text-[#17171c]">{userProfile?.max_workspaces || 1} Cabang</span>
        </div>
        <div class="flex justify-between text-[#686873]">
          <span class="font-sans">Masa Aktif Hingga:</span>
          <span class="text-[#17171c] font-bold">
            {userProfile?.subscription_expires_at ? userProfile.subscription_expires_at.split('T')[0] : 'Uji Coba'}
          </span>
        </div>
      </div>

      <!-- Kartu Faktur Tagihan -->
      {#if !activeInvoice}
        <div class="p-8 border border-[#e5e5ea] rounded-2xl text-center space-y-3 bg-[#fafafc]">
          <p class="text-xs text-[#8e8e93]">Belum ada faktur tagihan yang sedang aktif.</p>
          <button
            type="button"
            disabled={isCreatingInvoice || plans.length === 0}
            onclick={handleCreateInvoice}
            class="px-5 py-2.5 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full flex items-center justify-center gap-2 mx-auto cursor-pointer transition-all shadow-xs disabled:opacity-50"
          >
            <Plus class="w-4 h-4" />
            <span>{isCreatingInvoice ? 'Menerbitkan...' : 'Terbitkan Faktur Baru'}</span>
          </button>
        </div>
      {:else}
        <div class="border border-[#e5e5ea] rounded-2xl p-5 space-y-4 text-xs bg-white shadow-2xs">
          <div class="font-bold text-[#17171c] text-sm">Faktur #{activeInvoice.invoice_number}</div>

          <div class="space-y-2 bg-[#f8f8fa] p-4 rounded-xl font-mono text-xs border border-[#ececee]">
            <div class="flex justify-between text-[#8e8e93]">
              <span class="font-sans">Nominal Dasar:</span>
              <span class="text-[#17171c]">{formatRp(activeInvoice.amount_base)}</span>
            </div>
            <div class="flex justify-between text-[#2563eb] font-semibold">
              <span class="font-sans">Kode Unik 3-Digit:</span>
              <span>+{activeInvoice.unique_code}</span>
            </div>
            <div class="flex justify-between font-bold text-sm text-[#17171c] border-t border-[#e5e5ea] pt-2 mt-1">
              <span class="font-sans">Total Transfer Tepat:</span>
              <span class="text-[#dc2626]">{formatRp(activeInvoice.total_amount)}</span>
            </div>
          </div>

          <div class="text-xs text-[#686873] space-y-1 font-mono">
            <div>Bank Tujuan: <strong class="text-[#17171c]">BCA 800-123-4567</strong> (PT Precis Digital)</div>
            <div class="text-[11px] text-[#8e8e93]">Wajib transfer tepat hingga 3 digit terakhir untuk verifikasi otomatis.</div>
          </div>

          <!-- Form Upload Bukti Transfer -->
          <div class="pt-3 border-t border-[#f2f2f4] space-y-3">
            <div class="space-y-1.5">
              <label for="sender-acc" class="font-bold text-[#17171c] block">Nama Pemilik Rekening Pengirim</label>
              <input
                id="sender-acc"
                type="text"
                bind:value={senderAccountName}
                placeholder="Contoh: PT Norde Kuliner Jaya"
                class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
              />
            </div>

            <div class="space-y-1.5">
              <label for="proof-file" class="font-bold text-[#17171c] block">Pilih Berkas Foto Bukti Transfer</label>
              <input
                id="proof-file"
                type="file"
                accept="image/*"
                onchange={handleFileSelected}
                class="w-full px-4 py-2 bg-[#f8f8fa] border border-[#e5e5ea] rounded-xl text-xs text-[#686873] file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#17171c] file:text-white cursor-pointer"
              />
            </div>

            <button
              type="button"
              disabled={isUploadingProof || !senderAccountName.trim() || !proofFile}
              onclick={handleSubmitProof}
              class="w-full py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full flex items-center justify-center gap-2 cursor-pointer transition-all disabled:opacity-50 shadow-xs"
            >
              <Upload class="w-4 h-4" />
              <span>{isUploadingProof ? 'Mengunggah Bukti...' : 'Kirim Bukti Pembayaran'}</span>
            </button>
          </div>
        </div>
      {/if}
    </div>
  </div>
{/if}
