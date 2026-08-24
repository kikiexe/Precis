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

<div class="space-y-6 max-w-6xl mx-auto p-4 sm:p-6 md:p-8 pb-24 lg:pb-8 font-sans">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#d9d9dd] pb-4">
    <div>
      <h2 class="text-xl font-medium text-[#212121] tracking-tight">Status Langganan &amp; Billing SaaS</h2>
      <p class="text-xs text-[#616161] font-normal mt-0.5">Manajemen paket langganan, kuota cabang outlet, dan faktur transfer manual</p>
    </div>
  </div>

  {#if actionMessage}
    <div class="p-3.5 bg-[#edfce9] border border-[#edfce9] text-[#003c33] text-xs font-mono rounded-xl flex items-center justify-between">
      <span>{actionMessage}</span>
      <button type="button" onclick={() => (actionMessage = null)} class="text-[#616161] hover:text-[#212121] cursor-pointer p-1">✕</button>
    </div>
  {/if}

  {#if errorMessage}
    <div class="p-3.5 bg-[#ffad9b]/15 border border-[#ffad9b] text-[#b30000] text-xs font-mono rounded-xl flex items-center justify-between">
      <div class="flex items-center gap-2">
        <AlertCircle class="w-4 h-4 shrink-0" />
        <span>{errorMessage}</span>
      </div>
      <button type="button" onclick={() => (errorMessage = null)} class="text-[#616161] hover:text-[#212121] cursor-pointer p-1">✕</button>
    </div>
  {/if}

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- kartu status paket langganan aktif -->
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-6 space-y-4 shadow-none">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <h3 class="font-medium text-sm text-[#212121] flex items-center gap-2">
          <CreditCard class="w-4 h-4 text-[#1863dc]" />
          <span>Status Akun Tenant</span>
        </h3>
        <span class={`text-[10px] font-mono px-2.5 py-0.5 rounded-full font-medium ${
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

      <div class="space-y-2.5 text-xs">
        <div class="flex justify-between text-[#616161]">
          <span>Nama Pemilik:</span>
          <span class="font-medium text-[#212121]">{userProfile?.name || 'Owner'}</span>
        </div>
        <div class="flex justify-between text-[#616161]">
          <span>Maksimal Kuota Outlet:</span>
          <span class="text-[#1863dc] font-medium font-mono">{userProfile?.max_workspaces || 1} Cabang</span>
        </div>
        <div class="flex justify-between text-[#616161]">
          <span>Masa Berlaku Hingga:</span>
          <span class="text-[#212121] font-medium font-mono">
            {userProfile?.subscription_expires_at ? userProfile.subscription_expires_at.split('T')[0] : 'Aktif (Masa Uji Coba)'}
          </span>
        </div>
      </div>

      <!-- formulir penerbitan invoice baru -->
      <div class="border-t border-[#d9d9dd] pt-4 space-y-3">
        <div class="font-medium text-xs text-[#212121]">Perpanjang / Upgrade Paket:</div>

        <div class="space-y-1.5">
          <label for="billing-plan-select" class="block text-[11px] text-[#75758a]">Pilih Paket:</label>
          <select
            id="billing-plan-select"
            bind:value={selectedPlanId}
            class="w-full bg-white border border-[#d9d9dd] rounded-xl p-2.5 text-xs font-mono text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          >
            {#each plans as plan}
              <option value={plan.id}>
                {plan.name} ({formatRp(billingCycle === 'ANNUAL' ? plan.annual_price : plan.monthly_price)})
              </option>
            {/each}
          </select>
        </div>

        <div class="flex gap-2 text-xs font-mono bg-[#eeece7]/40 p-1 rounded-full border border-[#d9d9dd]">
          <button
            type="button"
            onclick={() => (billingCycle = 'MONTHLY')}
            class={`flex-1 py-1.5 rounded-full text-center cursor-pointer transition-all ${
              billingCycle === 'MONTHLY'
                ? 'bg-[#17171c] text-white font-medium shadow-none'
                : 'text-[#616161] hover:text-[#212121]'
            }`}
          >
            Bulanan
          </button>
          <button
            type="button"
            onclick={() => (billingCycle = 'ANNUAL')}
            class={`flex-1 py-1.5 rounded-full text-center cursor-pointer transition-all ${
              billingCycle === 'ANNUAL'
                ? 'bg-[#17171c] text-white font-medium shadow-none'
                : 'text-[#616161] hover:text-[#212121]'
            }`}
          >
            Tahunan (Hemat)
          </button>
        </div>

        <button
          type="button"
          disabled={isCreatingInvoice || plans.length === 0}
          onclick={handleCreateInvoice}
          class="w-full py-2.5 bg-[#17171c] hover:bg-[#000000] text-white font-medium text-xs rounded-full flex items-center justify-center gap-1.5 cursor-pointer transition-all shadow-none disabled:opacity-50"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>{isCreatingInvoice ? 'Menerbitkan...' : 'Terbitkan Faktur Baru'}</span>
        </button>
      </div>
    </div>

    <!-- kartu faktur aktif dan instruksi transfer bank -->
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-6 space-y-4 shadow-none lg:col-span-2">
      {#if !activeInvoice}
        <div class="p-12 text-center space-y-2.5">
          <FileText class="w-8 h-8 text-[#93939f] mx-auto opacity-50" />
          <h3 class="text-sm font-medium text-[#212121]">Tidak Ada Tagihan Aktif</h3>
          <p class="text-xs text-[#75758a]">Akun Anda dalam status aktif. Terbitkan faktur baru di samping jika ingin memperpanjang masa aktif.</p>
        </div>
      {:else}
        <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
          <div>
            <h3 class="font-medium text-sm text-[#212121]">Faktur Tagihan #{activeInvoice.invoice_number}</h3>
            <p class="text-xs text-[#616161] mt-0.5">Transfer sesuai nominal dan 3-digit kode unik acak untuk verifikasi otomatis</p>
          </div>
          <span class={`text-[10px] font-mono px-2.5 py-0.5 rounded-full font-medium ${
            activeInvoice.status === 'PAID'
              ? 'bg-[#edfce9] text-[#003c33]'
              : activeInvoice.status === 'PENDING_VERIFICATION'
              ? 'bg-[#eeece7] text-[#616161]'
              : 'bg-[#ffad9b]/20 text-[#b30000]'
          }`}>
            {getInvoiceStatusLabel(activeInvoice.status)}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- rincian tagihan -->
          <div class="p-4 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-2xl space-y-2 font-mono text-xs">
            <div class="flex justify-between text-[#616161]">
              <span>Nominal Dasar Paket:</span>
              <span class="text-[#212121]">{formatRp(activeInvoice.amount_base)}</span>
            </div>
            <div class="flex justify-between text-[#1863dc] font-medium">
              <span>Kode Unik Verifikasi:</span>
              <span>+{activeInvoice.unique_code}</span>
            </div>
            <div class="flex justify-between text-[#616161]">
              <span>Batas Waktu Bayar:</span>
              <span class="text-[#212121]">{activeInvoice.due_date ? activeInvoice.due_date.split('T')[0] : '3 Hari'}</span>
            </div>
            <div class="flex justify-between font-medium text-sm text-[#212121] border-t border-[#d9d9dd] pt-2">
              <span>Total Transfer Tepat:</span>
              <span class="text-[#b30000] text-base">{formatRp(activeInvoice.total_amount)}</span>
            </div>
          </div>

          <!-- rekening tujuan dan aksi unggah bukti -->
          <div class="space-y-3">
            <div class="p-3.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-2xl text-xs font-mono space-y-1">
              <div class="text-[#75758a]">Rekening Resmi Tujuan:</div>
              <div class="font-medium text-sm text-[#212121]">BCA: 8412-0099-3311</div>
              <div class="text-[11px] text-[#93939f]">a.n. PT Precis Ekosistem Digital</div>
            </div>

            {#if activeInvoice.status === 'UNPAID'}
              <button
                type="button"
                onclick={() => handleOpenProofModal(activeInvoice)}
                class="w-full py-3 bg-[#17171c] hover:bg-[#000000] text-white font-medium text-xs rounded-full flex items-center justify-center gap-2 cursor-pointer transition-all shadow-none"
              >
                <Upload class="w-4 h-4" />
                <span>Unggah Bukti Transfer Bank</span>
              </button>
            {:else if activeInvoice.status === 'PENDING_VERIFICATION'}
              <div class="p-3.5 bg-[#eeece7] text-[#616161] rounded-[14px] text-xs flex items-center gap-2.5 font-mono">
                <CheckCircle2 class="w-4 h-4 shrink-0 text-[#1863dc]" />
                <span>Bukti transfer telah diterima dan dalam antrean verifikasi Superadmin.</span>
              </div>
            {:else}
              <div class="p-3.5 bg-[#edfce9] text-[#003c33] rounded-[14px] text-xs flex items-center gap-2.5 font-mono">
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
  <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-6 space-y-4 shadow-none">
    <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
      <div>
        <h3 class="font-medium text-sm text-[#212121]">Riwayat Faktur &amp; Pembayaran</h3>
        <p class="text-xs text-[#616161] mt-0.5">Daftar seluruh tagihan langganan akun Anda</p>
      </div>
      <span class="text-xs font-mono text-[#75758a]">Total: {invoices.length} Faktur</span>
    </div>

    {#if invoices.length === 0}
      <div class="p-8 text-center text-xs text-[#75758a]">Belum ada riwayat faktur tagihan.</div>
    {:else}
      <div class="overflow-x-auto rounded-2xl border border-[#d9d9dd]">
        <table class="w-full text-xs text-left border-collapse">
          <thead class="bg-[#eeece7]/50 border-b border-[#d9d9dd] font-mono text-[11px] text-[#616161]">
            <tr>
              <th class="p-3.5 font-medium">Nomor Faktur</th>
              <th class="p-3.5 font-medium">Nominal Dasar</th>
              <th class="p-3.5 font-medium">Kode Unik</th>
              <th class="p-3.5 font-medium">Total Transfer</th>
              <th class="p-3.5 font-medium">Jatuh Tempo</th>
              <th class="p-3.5 text-right font-medium">Status</th>
              <th class="p-3.5 text-right font-medium">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#d9d9dd]/60">
            {#each invoices as inv (inv.id)}
              <tr class="hover:bg-[#eeece7]/20 transition-colors">
                <td class="p-3.5 font-mono font-medium text-[#212121]">{inv.invoice_number}</td>
                <td class="p-3.5 font-mono">{formatRp(inv.amount_base)}</td>
                <td class="p-3.5 font-mono text-[#1863dc]">+{inv.unique_code}</td>
                <td class="p-3.5 font-mono font-medium text-[#212121]">{formatRp(inv.total_amount)}</td>
                <td class="p-3.5 font-mono text-[#75758a]">{inv.due_date ? inv.due_date.split('T')[0] : '-'}</td>
                <td class="p-3.5 text-right">
                  <span class={`text-[10px] font-mono px-2.5 py-0.5 rounded-full font-medium ${
                    inv.status === 'PAID'
                      ? 'bg-[#edfce9] text-[#003c33]'
                      : inv.status === 'PENDING_VERIFICATION'
                      ? 'bg-[#eeece7] text-[#616161]'
                      : 'bg-[#ffad9b]/20 text-[#b30000]'
                  }`}>
                    {getInvoiceStatusLabel(inv.status)}
                  </span>
                </td>
                <td class="p-3.5 text-right">
                  {#if inv.status === 'UNPAID'}
                    <button
                      type="button"
                      onclick={() => handleOpenProofModal(inv)}
                      class="px-3.5 py-1 bg-[#17171c] text-white text-[11px] font-medium rounded-full cursor-pointer hover:bg-[#000000] transition-all"
                    >
                      Unggah Bukti
                    </button>
                  {:else if inv.confirmation?.proof_image_url}
                    <a
                      href={inv.confirmation.proof_image_url}
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-[#1863dc] hover:underline text-[11px] font-mono inline-flex items-center gap-1 font-medium"
                    >
                      <span>Lihat Bukti</span>
                      <ExternalLink class="w-3 h-3" />
                    </a>
                  {:else}
                    <span class="text-[#93939f] font-mono text-[11px]">-</span>
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
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-md w-full p-6 shadow-none space-y-4 animate-in fade-in zoom-in-95 font-sans">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <h3 class="text-base font-medium text-[#212121]">Unggah Bukti Transfer Bank</h3>
        <button type="button" onclick={() => (selectedInvoiceForProof = null)} class="text-[#93939f] hover:text-[#212121] cursor-pointer p-1">✕</button>
      </div>

      {#if errorMessage}
        <div class="p-3 bg-[#ffad9b]/15 border border-[#ffad9b] rounded-xl text-[#b30000] text-xs flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <div class="space-y-3.5 text-xs">
        <div class="p-3.5 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-[14px] font-mono space-y-1">
          <div class="flex justify-between text-[#616161]">
            <span>Nomor Faktur:</span>
            <span class="font-medium text-[#212121]">{selectedInvoiceForProof.invoice_number}</span>
          </div>
          <div class="flex justify-between text-[#616161]">
            <span>Nominal Tepat Transfer:</span>
            <span class="font-medium text-[#b30000]">{formatRp(selectedInvoiceForProof.total_amount)}</span>
          </div>
        </div>

        <div>
          <label for="sender-name-input" class="block font-medium text-[#212121] mb-1.5">Nama Pemilik Rekening Pengirim:</label>
          <input
            id="sender-name-input"
            type="text"
            bind:value={senderAccountName}
            placeholder="e.g. Arief Hadinata"
            class="w-full bg-white border border-[#d9d9dd] rounded-xl p-2.5 text-xs text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          />
        </div>

        <div>
          <label for="transfer-amount-input" class="block font-medium text-[#212121] mb-1.5">Nominal yang Ditransfer (Rp):</label>
          <input
            id="transfer-amount-input"
            type="number"
            bind:value={transferAmount}
            class="w-full bg-white border border-[#d9d9dd] rounded-xl p-2.5 text-xs font-mono font-medium text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          />
        </div>

        <div>
          <label for="proof-file-input" class="block font-medium text-[#212121] mb-1.5">Foto Struk / Tangkapan Layar M-Banking:</label>
          <input
            id="proof-file-input"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            onchange={handleFileSelected}
            class="w-full bg-white border border-[#d9d9dd] rounded-xl p-2.5 text-xs text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
          <p class="text-[11px] text-[#75758a] font-normal mt-1">Format: JPG, PNG, atau WebP. Maksimal 2MB.</p>
        </div>

        <div class="pt-3 flex gap-2.5 border-t border-[#d9d9dd]">
          <button
            type="button"
            onclick={() => (selectedInvoiceForProof = null)}
            class="flex-1 py-2.5 bg-white text-[#616161] hover:bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full text-xs font-medium cursor-pointer transition-all"
          >
            Batal
          </button>
          <button
            type="button"
            onclick={handleSubmitProof}
            disabled={isUploadingProof}
            class="flex-2 py-2.5 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full flex items-center justify-center gap-1.5 cursor-pointer transition-all disabled:opacity-50"
          >
            <Upload class="w-3.5 h-3.5" />
            <span>{isUploadingProof ? 'Mengunggah...' : 'Kirim Bukti Pembayaran'}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}
