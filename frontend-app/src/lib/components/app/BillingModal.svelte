<script lang="ts">
  import { CreditCard, Upload, CheckCircle2, AlertCircle, Plus, X } from 'lucide-svelte';
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
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4 select-none font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-md w-full p-6 shadow-none space-y-4 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2">
          <CreditCard class="w-4 h-4 text-[#1863dc]" />
          <h2 class="text-sm font-medium text-[#212121]">Status Langganan &amp; Billing Tenant</h2>
        </div>
        <button type="button" onclick={onClose} class="text-[#93939f] hover:text-[#212121] text-xs cursor-pointer p-1">
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if actionMessage}
        <div class="p-3.5 bg-[#edfce9] border border-[#edfce9] text-[#003c33] text-xs font-mono rounded-xl">
          {actionMessage}
        </div>
      {/if}

      {#if errorMessage}
        <div class="p-3.5 bg-[#ffad9b]/15 border border-[#ffad9b] text-[#b30000] text-xs font-mono rounded-xl flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <!-- status langganan akun saat ini -->
      <div class="p-4 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-2xl space-y-2 text-xs font-mono">
        <div class="flex justify-between items-center">
          <span class="text-[#616161]">Status Akun:</span>
          <span class={`text-[10px] px-2.5 py-0.5 rounded-full font-medium ${
            userProfile?.subscription_status === 'ACTIVE'
              ? 'bg-[#edfce9] text-[#003c33]'
              : userProfile?.subscription_status === 'GRACE_PERIOD'
              ? 'bg-[#eeece7] text-[#616161]'
              : userProfile?.subscription_status === 'SUSPENDED'
              ? 'bg-[#ffad9b]/20 text-[#b30000]'
              : 'bg-[#f1f5ff] text-[#1863dc]'
          }`}>
            {getStatusLabel(userProfile?.subscription_status || 'TRIAL')}
          </span>
        </div>
        <div class="flex justify-between text-[#616161]">
          <span>Maksimal Outlet:</span>
          <span class="font-medium text-[#212121]">{userProfile?.max_workspaces || 1} Cabang</span>
        </div>
        <div class="flex justify-between text-[#616161]">
          <span>Masa Aktif Hingga:</span>
          <span class="text-[#212121] font-medium">
            {userProfile?.subscription_expires_at ? userProfile.subscription_expires_at.split('T')[0] : 'Uji Coba'}
          </span>
        </div>
      </div>

      <!-- kartu faktur tagihan transfer manual -->
      {#if !activeInvoice}
        <div class="p-6 border border-[#d9d9dd] rounded-2xl text-center space-y-2.5">
          <p class="text-xs text-[#75758a]">Belum ada faktur tagihan yang sedang aktif.</p>
          <button
            type="button"
            disabled={isCreatingInvoice || plans.length === 0}
            onclick={handleCreateInvoice}
            class="px-5 py-2.5 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full flex items-center justify-center gap-1.5 mx-auto cursor-pointer transition-all shadow-none"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>{isCreatingInvoice ? 'Menerbitkan...' : 'Terbitkan Faktur Baru'}</span>
          </button>
        </div>
      {:else}
        <div class="border border-[#d9d9dd] rounded-2xl p-4 space-y-3 text-xs bg-white shadow-none">
          <div class="font-medium text-[#212121] text-xs">Faktur #{activeInvoice.invoice_number}</div>

          <div class="space-y-1.5 bg-[#eeece7]/40 p-3.5 rounded-xl font-mono text-[11px]">
            <div class="flex justify-between text-[#616161]">
              <span>Nominal Dasar:</span>
              <span class="text-[#212121]">{formatRp(activeInvoice.amount_base)}</span>
            </div>
            <div class="flex justify-between text-[#1863dc] font-medium">
              <span>Kode Unik 3-Digit:</span>
              <span>+{activeInvoice.unique_code}</span>
            </div>
            <div class="flex justify-between font-medium text-sm text-[#212121] border-t border-[#d9d9dd] pt-1.5 mt-1">
              <span>Total Transfer Tepat:</span>
              <span class="text-[#b30000]">{formatRp(activeInvoice.total_amount)}</span>
            </div>
          </div>

          <div class="text-[11px] text-[#616161] space-y-1 font-mono">
            <div>Transfer ke Rekening Resmi:</div>
            <div class="p-2.5 bg-white border border-[#d9d9dd] rounded-[10px]">
              <div class="font-medium text-[#212121]">BCA: 8412-0099-3311</div>
              <div class="text-[10px] text-[#93939f]">a.n. PT Precis Ekosistem Digital</div>
            </div>
          </div>

          {#if activeInvoice.status === 'UNPAID'}
            <div class="space-y-3 pt-2.5 border-t border-[#d9d9dd]">
              <div>
                <label for="modal-sender-input" class="block text-[#616161] text-[11px] mb-1 font-medium">Nama Pemilik Rekening Pengirim:</label>
                <input
                  id="modal-sender-input"
                  type="text"
                  bind:value={senderAccountName}
                  placeholder="e.g. Arief Hadinata"
                  class="w-full bg-white border border-[#d9d9dd] rounded-[10px] p-2.5 text-xs text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
                />
              </div>

              <div>
                <label for="modal-proof-file" class="block text-[#616161] text-[11px] mb-1 font-medium">Foto Bukti Transfer:</label>
                <input
                  id="modal-proof-file"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  onchange={handleFileSelected}
                  class="w-full bg-white border border-[#d9d9dd] rounded-[10px] p-2 text-xs text-[#212121] focus:border-[#17171c] focus:outline-hidden"
                />
              </div>

              <button
                type="button"
                onclick={handleSubmitProof}
                disabled={isUploadingProof}
                class="w-full py-2.5 bg-[#17171c] hover:bg-[#000000] text-white font-medium text-xs rounded-full flex items-center justify-center gap-1.5 cursor-pointer transition-all shadow-none disabled:opacity-50"
              >
                <Upload class="w-3.5 h-3.5" />
                <span>{isUploadingProof ? 'Mengunggah...' : 'Kirim Bukti Pembayaran'}</span>
              </button>
            </div>
          {:else if activeInvoice.status === 'PENDING_VERIFICATION'}
            <div class="p-3 bg-[#eeece7] text-[#616161] rounded-xl text-[11px] flex items-center gap-2 font-mono">
              <CheckCircle2 class="w-4 h-4 shrink-0 text-[#1863dc]" />
              <span>Bukti terkirim. Menunggu verifikasi tim Superadmin.</span>
            </div>
          {:else}
            <div class="p-3 bg-[#edfce9] text-[#003c33] rounded-xl text-[11px] flex items-center gap-2 font-mono">
              <CheckCircle2 class="w-4 h-4 shrink-0" />
              <span>Tagihan telah lunas diverifikasi.</span>
            </div>
          {/if}
        </div>
      {/if}
    </div>
  </div>
{/if}
