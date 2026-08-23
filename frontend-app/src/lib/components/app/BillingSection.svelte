<script lang="ts">
  import { CreditCard, Upload, CheckCircle2, AlertCircle, Plus, FileText, ExternalLink } from 'lucide-svelte';
  import type { SubscriptionInvoice, SubscriptionPlanItem, UserProfile } from '../../types/app';
  import { billingService } from '../../services/billing-service';

  interface Props {
    userProfile?: UserProfile | null;
    invoices?: SubscriptionInvoice[];
    plans?: SubscriptionPlanItem[];
    onInvoiceUpdated?: () => Promise<void> | void;
  }

  let {
    userProfile = null,
    invoices = [],
    plans = [],
    onInvoiceUpdated,
  }: Props = $props();

  let selectedPlanId = $state<string>('');
  let billingCycle = $state<'MONTHLY' | 'ANNUAL'>('MONTHLY');
  let isCreatingInvoice = $state(false);

  // formulir unggah bukti transfer
  let selectedInvoiceForProof = $state<SubscriptionInvoice | null>(null);
  let senderAccountName = $state('');
  let transferAmount = $state(0);
  let proofFile = $state<File | null>(null);
  let isUploadingProof = $state(false);
  let actionMessage = $state<string | null>(null);
  let errorMessage = $state<string | null>(null);

  // sinkronkan pilihan paket awal
  $effect(() => {
    if (plans.length > 0 && !selectedPlanId) {
      selectedPlanId = plans[0].id;
    }
  });

  // invoice aktif yang belum dibayar atau sedang diverifikasi
  let activeInvoice = $derived(
    invoices.find((inv) => inv.status === 'UNPAID' || inv.status === 'PENDING_VERIFICATION') || invoices[0] || null
  );

  function formatRp(num: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
  }

  function getStatusLabel(status: string): string {
    switch (status) {
      case 'ACTIVE':
        return 'Aktif Normal';
      case 'TRIAL':
        return 'Uji Coba Gratis';
      case 'GRACE_PERIOD':
        return 'Masa Tenggang';
      case 'SUSPENDED':
        return 'Layanan Ditangguhkan';
      default:
        return status;
    }
  }

  function getInvoiceStatusLabel(status: string): string {
    switch (status) {
      case 'UNPAID':
        return 'Belum Dibayar';
      case 'PENDING_VERIFICATION':
        return 'Menunggu Verifikasi';
      case 'PAID':
        return 'Lunas / Terverifikasi';
      case 'EXPIRED':
        return 'Kedaluwarsa';
      default:
        return status;
    }
  }

  async function handleCreateInvoice() {
    if (!selectedPlanId) {
      errorMessage = 'Pilih salah satu paket langganan terlebih dahulu.';
      return;
    }

    isCreatingInvoice = true;
    errorMessage = null;
    actionMessage = null;

    try {
      const created = await billingService.createInvoice(selectedPlanId, billingCycle);
      actionMessage = `Faktur ${created.invoice_number} berhasil diterbitkan dengan 3-digit kode unik.`;
      if (onInvoiceUpdated) {
        await onInvoiceUpdated();
      }
    } catch (err: unknown) {
      errorMessage = err instanceof Error ? err.message : 'Gagal membuat faktur tagihan.';
    } finally {
      isCreatingInvoice = false;
    }
  }

  function handleOpenProofModal(inv: SubscriptionInvoice) {
    selectedInvoiceForProof = inv;
    transferAmount = inv.total_amount;
    senderAccountName = '';
    proofFile = null;
    errorMessage = null;
  }

  function handleFileSelected(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
      proofFile = target.files[0];
    }
  }

  async function handleSubmitProof() {
    if (!selectedInvoiceForProof) return;

    if (!senderAccountName.trim()) {
      errorMessage = 'Nama pemilik rekening pengirim wajib diisi.';
      return;
    }

    if (transferAmount <= 0) {
      errorMessage = 'Nominal transfer tidak valid.';
      return;
    }

    if (!proofFile) {
      errorMessage = 'Pilih berkas foto struk bukti transfer.';
      return;
    }

    isUploadingProof = true;
    errorMessage = null;
    actionMessage = null;

    try {
      // 1. unggah gambar ke storage
      const proofUrl = await billingService.uploadProofImage(proofFile);

      // 2. kirim konfirmasi pembayaran
      await billingService.submitProof(
        selectedInvoiceForProof.id,
        senderAccountName.trim(),
        transferAmount,
        proofUrl
      );

      actionMessage = 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi tim Superadmin.';
      selectedInvoiceForProof = null;

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

<div class="space-y-6 max-w-6xl mx-auto p-4 md:p-8 pb-24 lg:pb-8">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#e0e0e0] pb-4">
    <div>
      <h2 class="text-xl font-bold text-[#161616] font-display">Status Langganan &amp; Billing SaaS</h2>
      <p class="text-xs text-[#525252] font-mono">Manajemen paket langganan, kuota cabang outlet, dan faktur transfer manual</p>
    </div>
  </div>

  {#if actionMessage}
    <div class="p-3.5 bg-[#24a148]/10 border border-[#24a148]/30 text-[#24a148] text-xs font-mono flex items-center justify-between">
      <span>{actionMessage}</span>
      <button type="button" onclick={() => (actionMessage = null)} class="text-[#525252] hover:text-[#161616] cursor-pointer">✕</button>
    </div>
  {/if}

  {#if errorMessage}
    <div class="p-3.5 bg-[#da1e28]/10 border border-[#da1e28]/30 text-[#da1e28] text-xs font-mono flex items-center justify-between">
      <div class="flex items-center gap-2">
        <AlertCircle class="w-4 h-4 shrink-0" />
        <span>{errorMessage}</span>
      </div>
      <button type="button" onclick={() => (errorMessage = null)} class="text-[#525252] hover:text-[#161616] cursor-pointer">✕</button>
    </div>
  {/if}

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- kartu status paket langganan aktif -->
    <div class="bg-white border border-[#e0e0e0] p-6 space-y-4 shadow-xs">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
        <h3 class="font-bold text-sm text-[#161616] flex items-center gap-2">
          <CreditCard class="w-4 h-4 text-[#0f62fe]" />
          <span>Status Akun Tenant</span>
        </h3>
        <span class={`text-[10px] font-mono px-2 py-0.5 border font-bold ${
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

      <div class="space-y-2.5 text-xs font-mono">
        <div class="flex justify-between text-[#525252]">
          <span>Nama Pemilik:</span>
          <span class="font-bold text-[#161616]">{userProfile?.name || 'Owner'}</span>
        </div>
        <div class="flex justify-between text-[#525252]">
          <span>Maksimal Kuota Outlet:</span>
          <span class="text-[#0f62fe] font-bold">{userProfile?.max_workspaces || 1} Cabang</span>
        </div>
        <div class="flex justify-between text-[#525252]">
          <span>Masa Berlaku Hingga:</span>
          <span class="text-[#161616] font-bold">
            {userProfile?.subscription_expires_at ? userProfile.subscription_expires_at.split('T')[0] : 'Aktif (Masa Uji Coba)'}
          </span>
        </div>
      </div>

      <!-- formulir penerbitan invoice baru -->
      <div class="border-t border-[#e0e0e0] pt-4 space-y-3">
        <div class="font-bold text-xs text-[#161616]">Perpanjang / Upgrade Paket:</div>

        <div class="space-y-2">
          <label for="billing-plan-select" class="block text-[11px] font-mono text-[#525252]">Pilih Paket:</label>
          <select
            id="billing-plan-select"
            bind:value={selectedPlanId}
            class="w-full bg-[#f4f4f4] border border-[#8c8c8c] p-2 text-xs font-mono text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
          >
            {#each plans as plan}
              <option value={plan.id}>
                {plan.name} ({formatRp(billingCycle === 'ANNUAL' ? plan.annual_price : plan.monthly_price)})
              </option>
            {/each}
          </select>
        </div>

        <div class="flex gap-2 text-xs font-mono">
          <button
            type="button"
            onclick={() => (billingCycle = 'MONTHLY')}
            class={`flex-1 py-1.5 border text-center cursor-pointer transition-colors ${
              billingCycle === 'MONTHLY'
                ? 'bg-[#0f62fe] text-white border-[#0f62fe] font-bold'
                : 'bg-[#f4f4f4] text-[#525252] border-[#e0e0e0]'
            }`}
          >
            Bulanan
          </button>
          <button
            type="button"
            onclick={() => (billingCycle = 'ANNUAL')}
            class={`flex-1 py-1.5 border text-center cursor-pointer transition-colors ${
              billingCycle === 'ANNUAL'
                ? 'bg-[#0f62fe] text-white border-[#0f62fe] font-bold'
                : 'bg-[#f4f4f4] text-[#525252] border-[#e0e0e0]'
            }`}
          >
            Tahunan (Hemat)
          </button>
        </div>

        <button
          type="button"
          disabled={isCreatingInvoice || plans.length === 0}
          onclick={handleCreateInvoice}
          class="w-full py-2.5 bg-[#161616] hover:bg-black text-white font-semibold text-xs flex items-center justify-center gap-1.5 cursor-pointer shadow-xs transition-colors disabled:opacity-50"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>{isCreatingInvoice ? 'Menerbitkan...' : 'Terbitkan Faktur Baru'}</span>
        </button>
      </div>
    </div>

    <!-- kartu faktur aktif dan instruksi transfer bank -->
    <div class="bg-white border border-[#e0e0e0] p-6 space-y-4 shadow-xs lg:col-span-2">
      {#if !activeInvoice}
        <div class="p-12 text-center space-y-2">
          <FileText class="w-8 h-8 text-[#8c8c8c] mx-auto opacity-40" />
          <h3 class="text-sm font-bold text-[#161616]">Tidak Ada Tagihan Aktif</h3>
          <p class="text-xs text-[#8c8c8c]">Akun Anda dalam status aktif. Terbitkan faktur baru di samping jika ingin memperpanjang masa aktif.</p>
        </div>
      {:else}
        <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
          <div>
            <h3 class="font-bold text-sm text-[#161616]">Faktur Tagihan #{activeInvoice.invoice_number}</h3>
            <p class="text-xs text-[#525252] font-mono">Transfer sesuai nominal dan 3-digit kode unik acak untuk verifikasi otomatis</p>
          </div>
          <span class={`text-[10px] font-mono px-2 py-0.5 border font-bold ${
            activeInvoice.status === 'PAID'
              ? 'bg-[#24a148]/10 text-[#24a148] border-[#24a148]/30'
              : activeInvoice.status === 'PENDING_VERIFICATION'
              ? 'bg-[#f1c21b]/10 text-[#8a6d00] border-[#f1c21b]/30'
              : 'bg-[#da1e28]/10 text-[#da1e28] border-[#da1e28]/30'
          }`}>
            {getInvoiceStatusLabel(activeInvoice.status)}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- rincian tagihan -->
          <div class="p-4 bg-[#f4f4f4] border border-[#e0e0e0] space-y-2 font-mono text-xs">
            <div class="flex justify-between text-[#525252]">
              <span>Nominal Dasar Paket:</span>
              <span>{formatRp(activeInvoice.amount_base)}</span>
            </div>
            <div class="flex justify-between text-[#0f62fe] font-bold">
              <span>Kode Unik Verifikasi:</span>
              <span>+{activeInvoice.unique_code}</span>
            </div>
            <div class="flex justify-between text-[#525252]">
              <span>Batas Waktu Bayar:</span>
              <span class="text-[#161616]">{activeInvoice.due_date ? activeInvoice.due_date.split('T')[0] : '3 Hari'}</span>
            </div>
            <div class="flex justify-between font-bold text-sm text-[#161616] border-t border-[#e0e0e0] pt-2">
              <span>Total Transfer Tepat:</span>
              <span class="text-[#da1e28] text-base">{formatRp(activeInvoice.total_amount)}</span>
            </div>
          </div>

          <!-- rekening tujuan dan aksi unggah bukti -->
          <div class="space-y-3">
            <div class="p-3 bg-[#f4f4f4] border border-[#e0e0e0] text-xs font-mono space-y-1">
              <div class="text-[#525252]">Rekening Resmi Tujuan:</div>
              <div class="font-bold text-sm text-[#161616]">BCA: 8412-0099-3311</div>
              <div class="text-[10px] text-[#8c8c8c]">a.n. PT Precis Ekosistem Digital</div>
            </div>

            {#if activeInvoice.status === 'UNPAID'}
              <button
                type="button"
                onclick={() => handleOpenProofModal(activeInvoice)}
                class="w-full py-3 bg-[#0f62fe] hover:bg-[#0050e6] text-white font-semibold text-xs flex items-center justify-center gap-2 cursor-pointer shadow-xs transition-colors"
              >
                <Upload class="w-4 h-4" />
                <span>Unggah Bukti Transfer Bank</span>
              </button>
            {:else if activeInvoice.status === 'PENDING_VERIFICATION'}
              <div class="p-3 bg-[#f1c21b]/10 border border-[#f1c21b]/30 text-[#8a6d00] text-xs flex items-center gap-2 font-mono">
                <CheckCircle2 class="w-4 h-4 shrink-0" />
                <span>Bukti transfer telah diterima dan dalam antrean verifikasi Superadmin.</span>
              </div>
            {:else}
              <div class="p-3 bg-[#24a148]/10 border border-[#24a148]/30 text-[#24a148] text-xs flex items-center gap-2 font-mono">
                <CheckCircle2 class="w-4 h-4 shrink-0" />
                <span>Faktur ini telah terverifikasi lunas.</span>
              </div>
            {/if}
          </div>
        </div>
      {/if}
    </div>
  </div>

  <!-- tabel riwayat seluruh faktur tagihan -->
  <div class="bg-white border border-[#e0e0e0] p-6 space-y-4 shadow-xs">
    <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
      <div>
        <h3 class="font-bold text-sm text-[#161616]">Riwayat Faktur &amp; Pembayaran</h3>
        <p class="text-xs text-[#525252] font-mono">Daftar seluruh tagihan langganan akun Anda</p>
      </div>
      <span class="text-xs font-mono text-[#8c8c8c]">Total: {invoices.length} Faktur</span>
    </div>

    {#if invoices.length === 0}
      <div class="p-8 text-center text-xs text-[#8c8c8c]">Belum ada riwayat faktur tagihan.</div>
    {:else}
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="bg-[#f4f4f4] border-b border-[#e0e0e0] font-mono text-[11px] text-[#525252]">
            <tr>
              <th class="p-3">Nomor Faktur</th>
              <th class="p-3">Nominal Dasar</th>
              <th class="p-3">Kode Unik</th>
              <th class="p-3">Total Transfer</th>
              <th class="p-3">Jatuh Tempo</th>
              <th class="p-3 text-right">Status</th>
              <th class="p-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#f4f4f4]">
            {#each invoices as inv (inv.id)}
              <tr class="hover:bg-[#f4f4f4]/60 transition-colors">
                <td class="p-3 font-mono font-bold text-[#161616]">{inv.invoice_number}</td>
                <td class="p-3 font-mono">{formatRp(inv.amount_base)}</td>
                <td class="p-3 font-mono text-[#0f62fe]">+{inv.unique_code}</td>
                <td class="p-3 font-mono font-bold text-[#161616]">{formatRp(inv.total_amount)}</td>
                <td class="p-3 font-mono text-[#8c8c8c]">{inv.due_date ? inv.due_date.split('T')[0] : '-'}</td>
                <td class="p-3 text-right">
                  <span class={`text-[10px] font-mono px-2 py-0.5 border ${
                    inv.status === 'PAID'
                      ? 'bg-[#24a148]/10 text-[#24a148] border-[#24a148]/30'
                      : inv.status === 'PENDING_VERIFICATION'
                      ? 'bg-[#f1c21b]/10 text-[#8a6d00] border-[#f1c21b]/30'
                      : 'bg-[#da1e28]/10 text-[#da1e28] border-[#da1e28]/30'
                  }`}>
                    {getInvoiceStatusLabel(inv.status)}
                  </span>
                </td>
                <td class="p-3 text-right">
                  {#if inv.status === 'UNPAID'}
                    <button
                      type="button"
                      onclick={() => handleOpenProofModal(inv)}
                      class="px-2.5 py-1 bg-[#0f62fe] text-white text-[11px] font-semibold cursor-pointer hover:bg-[#0050e6]"
                    >
                      Unggah Bukti
                    </button>
                  {:else if inv.confirmation?.proof_image_url}
                    <a
                      href={inv.confirmation.proof_image_url}
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-[#0f62fe] hover:underline text-[11px] font-mono inline-flex items-center gap-1"
                    >
                      <span>Lihat Bukti</span>
                      <ExternalLink class="w-3 h-3" />
                    </a>
                  {:else}
                    <span class="text-[#8c8c8c] font-mono text-[11px]">-</span>
                  {/if}
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {/if}
  </div>
</div>

<!-- modal upload bukti transfer -->
{#if selectedInvoiceForProof}
  <div class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white border border-[#e0e0e0] max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
        <h3 class="text-sm font-bold text-[#161616]">Unggah Bukti Transfer Bank</h3>
        <button type="button" onclick={() => (selectedInvoiceForProof = null)} class="text-[#8c8c8c] hover:text-[#161616] cursor-pointer">✕</button>
      </div>

      {#if errorMessage}
        <div class="p-3 bg-[#da1e28]/10 border border-[#da1e28]/30 text-[#da1e28] text-xs font-mono flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <div class="space-y-3 text-xs">
        <div class="p-3 bg-[#f4f4f4] border border-[#e0e0e0] font-mono space-y-1">
          <div class="flex justify-between text-[#525252]">
            <span>Nomor Faktur:</span>
            <span class="font-bold text-[#161616]">{selectedInvoiceForProof.invoice_number}</span>
          </div>
          <div class="flex justify-between text-[#525252]">
            <span>Nominal Tepat Transfer:</span>
            <span class="font-bold text-[#da1e28]">{formatRp(selectedInvoiceForProof.total_amount)}</span>
          </div>
        </div>

        <div>
          <label for="sender-name-input" class="block font-mono text-[#525252] mb-1">Nama Pemilik Rekening Pengirim:</label>
          <input
            id="sender-name-input"
            type="text"
            bind:value={senderAccountName}
            placeholder="e.g. Arief Hadinata"
            class="w-full bg-[#f4f4f4] border border-[#8c8c8c] p-2 text-xs font-mono text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
          />
        </div>

        <div>
          <label for="transfer-amount-input" class="block font-mono text-[#525252] mb-1">Nominal yang Ditransfer (Rp):</label>
          <input
            id="transfer-amount-input"
            type="number"
            bind:value={transferAmount}
            class="w-full bg-[#f4f4f4] border border-[#8c8c8c] p-2 text-xs font-mono font-bold text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
          />
        </div>

        <div>
          <label for="proof-file-input" class="block font-mono text-[#525252] mb-1">Foto Struk / Tangkapan Layar M-Banking:</label>
          <input
            id="proof-file-input"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            onchange={handleFileSelected}
            class="w-full bg-[#f4f4f4] border border-[#8c8c8c] p-2 text-xs font-mono text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
          />
          <p class="text-[10px] text-[#8c8c8c] font-mono mt-1">Format: JPG, PNG, atau WebP. Maksimal 2MB.</p>
        </div>

        <div class="pt-2 flex gap-2">
          <button
            type="button"
            onclick={() => (selectedInvoiceForProof = null)}
            class="flex-1 py-2.5 bg-[#f4f4f4] text-[#525252] border border-[#e0e0e0] text-xs font-semibold cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            onclick={handleSubmitProof}
            disabled={isUploadingProof}
            class="flex-2 py-2.5 bg-[#0f62fe] hover:bg-[#0050e6] text-white text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <Upload class="w-3.5 h-3.5" />
            <span>{isUploadingProof ? 'Mengunggah...' : 'Kirim Bukti Pembayaran'}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}
