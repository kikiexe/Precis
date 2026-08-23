<script lang="ts">
  import { CreditCard, Upload, CheckCircle2, AlertCircle, Plus } from 'lucide-svelte';
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
  <div class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4 select-none">
    <div class="bg-white border border-[#e0e0e0] max-w-md w-full p-5 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
        <div class="flex items-center gap-2">
          <CreditCard class="w-4 h-4 text-[#0f62fe]" />
          <h2 class="text-sm font-bold text-[#161616]">Status Langganan &amp; Billing Tenant</h2>
        </div>
        <button type="button" onclick={onClose} class="text-[#8c8c8c] hover:text-[#161616] text-xs cursor-pointer">✕</button>
      </div>

      {#if actionMessage}
        <div class="p-3 bg-[#24a148]/10 border border-[#24a148]/30 text-[#24a148] text-xs font-mono">
          {actionMessage}
        </div>
      {/if}

      {#if errorMessage}
        <div class="p-3 bg-[#da1e28]/10 border border-[#da1e28]/30 text-[#da1e28] text-xs font-mono flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <!-- status langganan akun saat ini -->
      <div class="p-3.5 bg-[#f4f4f4] border border-[#e0e0e0] space-y-1.5 text-xs font-mono">
        <div class="flex justify-between items-center">
          <span class="text-[#525252]">Status Akun:</span>
          <span class={`text-[10px] px-2 py-0.5 border font-bold ${
            userProfile?.subscription_status === 'ACTIVE'
              ? 'bg-[#24a148]/10 text-[#24a148] border-[#24a148]/30'
              : userProfile?.subscription_status === 'GRACE_PERIOD'
              ? 'bg-[#f1c21b]/10 text-[#8a6d00] border-[#f1c21b]/30'
              : userProfile?.subscription_status === 'SUSPENDED'
              ? 'bg-[#da1e28]/10 text-[#da1e28] border-[#da1e28]/30'
              : 'bg-[#0f62fe]/10 text-[#0f62fe] border-[#0f62fe]/30'
          }`}>
            {getStatusLabel(userProfile?.subscription_status || 'TRIAL')}
          </span>
        </div>
        <div class="flex justify-between text-[#525252]">
          <span>Maksimal Outlet:</span>
          <span class="font-bold text-[#161616]">{userProfile?.max_workspaces || 1} Cabang</span>
        </div>
        <div class="flex justify-between text-[#525252]">
          <span>Masa Aktif Hingga:</span>
          <span class="text-[#161616]">
            {userProfile?.subscription_expires_at ? userProfile.subscription_expires_at.split('T')[0] : 'Uji Coba'}
          </span>
        </div>
      </div>

      <!-- kartu faktur tagihan transfer manual -->
      {#if !activeInvoice}
        <div class="p-4 border border-[#e0e0e0] text-center space-y-2">
          <p class="text-xs text-[#525252]">Belum ada faktur tagihan yang sedang aktif.</p>
          <button
            type="button"
            disabled={isCreatingInvoice || plans.length === 0}
            onclick={handleCreateInvoice}
            class="px-4 py-2 bg-[#0f62fe] hover:bg-[#0050e6] text-white text-xs font-semibold flex items-center justify-center gap-1.5 mx-auto cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>{isCreatingInvoice ? 'Menerbitkan...' : 'Terbitkan Faktur Baru'}</span>
          </button>
        </div>
      {:else}
        <div class="border border-[#e0e0e0] p-3.5 space-y-3 text-xs bg-white shadow-xs">
          <div class="font-bold text-[#161616] text-xs">Faktur #{activeInvoice.invoice_number}</div>

          <div class="space-y-1.5 bg-[#f4f4f4] p-3 font-mono text-[11px]">
            <div class="flex justify-between text-[#525252]">
              <span>Nominal Dasar:</span>
              <span>{formatRp(activeInvoice.amount_base)}</span>
            </div>
            <div class="flex justify-between text-[#0f62fe] font-bold">
              <span>Kode Unik 3-Digit:</span>
              <span>+{activeInvoice.unique_code}</span>
            </div>
            <div class="flex justify-between font-bold text-sm text-[#161616] border-t border-[#e0e0e0] pt-1 mt-1">
              <span>Total Transfer Tepat:</span>
              <span class="text-[#da1e28]">{formatRp(activeInvoice.total_amount)}</span>
            </div>
          </div>

          <div class="text-[11px] text-[#525252] space-y-1 font-mono">
            <div>Transfer ke Rekening Resmi:</div>
            <div class="p-2 bg-white border border-[#e0e0e0]">
              <div class="font-bold text-[#161616]">BCA: 8412-0099-3311</div>
              <div class="text-[10px] text-[#8c8c8c]">a.n. PT Precis Ekosistem Digital</div>
            </div>
          </div>

          {#if activeInvoice.status === 'UNPAID'}
            <div class="space-y-2 pt-2 border-t border-[#e0e0e0]">
              <div>
                <label for="modal-sender-input" class="block font-mono text-[#525252] text-[11px] mb-1">Nama Pemilik Rekening Pengirim:</label>
                <input
                  id="modal-sender-input"
                  type="text"
                  bind:value={senderAccountName}
                  placeholder="e.g. Arief Hadinata"
                  class="w-full bg-[#f4f4f4] border border-[#8c8c8c] p-2 text-xs font-mono text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
                />
              </div>

              <div>
                <label for="modal-proof-file" class="block font-mono text-[#525252] text-[11px] mb-1">Foto Bukti Transfer:</label>
                <input
                  id="modal-proof-file"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  onchange={handleFileSelected}
                  class="w-full bg-[#f4f4f4] border border-[#8c8c8c] p-2 text-xs font-mono text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
                />
              </div>

              <button
                type="button"
                onclick={handleSubmitProof}
                disabled={isUploadingProof}
                class="w-full py-2.5 bg-[#0f62fe] hover:bg-[#0050e6] text-white font-semibold text-xs flex items-center justify-center gap-1.5 cursor-pointer shadow-xs transition-colors disabled:opacity-50"
              >
                <Upload class="w-3.5 h-3.5" />
                <span>{isUploadingProof ? 'Mengunggah...' : 'Kirim Bukti Pembayaran'}</span>
              </button>
            </div>
          {:else if activeInvoice.status === 'PENDING_VERIFICATION'}
            <div class="p-2.5 bg-[#f1c21b]/10 border border-[#f1c21b]/30 text-[#8a6d00] text-[11px] flex items-center gap-1.5 font-mono">
              <CheckCircle2 class="w-4 h-4 shrink-0" />
              <span>Bukti terkirim. Menunggu verifikasi tim Superadmin.</span>
            </div>
          {:else}
            <div class="p-2.5 bg-[#24a148]/10 border border-[#24a148]/30 text-[#24a148] text-[11px] flex items-center gap-1.5 font-mono">
              <CheckCircle2 class="w-4 h-4 shrink-0" />
              <span>Tagihan telah lunas diverifikasi.</span>
            </div>
          {/if}
        </div>
      {/if}
    </div>
  </div>
{/if}
