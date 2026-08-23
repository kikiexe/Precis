<script lang="ts">
  import {
    CheckCircle,
    Eye,
    AlertCircle,
    FileText,
    ExternalLink,
    RefreshCw,
    Search,
    ShieldCheck,
    X,
  } from 'lucide-svelte';
  import type { InvoiceRecord } from '../types/superadmin';

  interface Props {
    invoices: InvoiceRecord[];
    isLoading: boolean;
    onVerifyInvoice: (invoiceId: string) => Promise<void>;
    onRefresh: () => void;
  }

  let { invoices, isLoading, onVerifyInvoice, onRefresh }: Props = $props();

  let selectedStatus = $state<'ALL' | 'PENDING_VERIFICATION' | 'PAID' | 'UNPAID'>('PENDING_VERIFICATION');
  let searchQuery = $state('');
  let inspectingInvoice = $state<InvoiceRecord | null>(null);
  let isVerifying = $state(false);
  let verificationSuccessMessage = $state<string | null>(null);
  let verificationErrorMessage = $state<string | null>(null);

  // Derived filtered invoices
  let filteredInvoices = $derived(
    invoices.filter((inv) => {
      const matchStatus = selectedStatus === 'ALL' || inv.status === selectedStatus;
      const q = searchQuery.toLowerCase();
      const matchQuery =
        !searchQuery ||
        inv.invoice_number.toLowerCase().includes(q) ||
        inv.user?.name.toLowerCase().includes(q) ||
        inv.user?.email.toLowerCase().includes(q) ||
        inv.confirmation?.bank_account_name.toLowerCase().includes(q);

      return matchStatus && matchQuery;
    })
  );

  let pendingCount = $derived(invoices.filter((inv) => inv.status === 'PENDING_VERIFICATION').length);

  function formatRupiah(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(amount);
  }

  async function handleVerify(invoiceId: string) {
    isVerifying = true;
    verificationSuccessMessage = null;
    verificationErrorMessage = null;

    try {
      await onVerifyInvoice(invoiceId);
      verificationSuccessMessage = 'Faktur berhasil diverifikasi! Masa aktif tenant telah diperpanjang 30 hari.';
      if (inspectingInvoice && inspectingInvoice.id === invoiceId) {
        inspectingInvoice = {
          ...inspectingInvoice,
          status: 'PAID',
        };
      }
    } catch (err: unknown) {
      if (err instanceof Error) {
        verificationErrorMessage = err.message;
      } else {
        verificationErrorMessage = 'Gagal memverifikasi faktur tagihan.';
      }
    } finally {
      isVerifying = false;
    }
  }

  function openInspection(invoice: InvoiceRecord) {
    inspectingInvoice = invoice;
    verificationSuccessMessage = null;
    verificationErrorMessage = null;
  }

  function closeInspection() {
    inspectingInvoice = null;
    verificationSuccessMessage = null;
    verificationErrorMessage = null;
  }
</script>

<div class="space-y-6">
  <!-- Top Bar Title -->
  <div class="bg-white p-4 border border-[#e0e0e0] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-[#161616] tracking-tight">Pusat Verifikasi Faktur & Pembayaran</h1>
      <p class="text-xs text-[#525252] mt-0.5">
        Validasi transfer manual 3-digit kode unik, periksa foto bukti transfer, dan lakukan persetujuan 1-klik.
      </p>
    </div>

    <button
      type="button"
      onclick={onRefresh}
      disabled={isLoading}
      class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-[#f4f4f4] hover:bg-[#e0e0e0] text-[#161616] text-xs font-medium border border-[#e0e0e0] transition-colors disabled:opacity-50"
    >
      <RefreshCw class={`w-3.5 h-3.5 ${isLoading ? 'animate-spin' : ''}`} />
      <span>Segarkan Data</span>
    </button>
  </div>

  <!-- Filter & Search Tabs Bar -->
  <div class="bg-white p-4 border border-[#e0e0e0] space-y-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
      <!-- Status Tabs -->
      <div class="flex flex-wrap gap-1 bg-[#f4f4f4] p-1 border border-[#e0e0e0]">
        <button
          type="button"
          onclick={() => (selectedStatus = 'PENDING_VERIFICATION')}
          class={`px-3 py-1.5 text-xs font-medium transition-colors flex items-center space-x-1.5 ${
            selectedStatus === 'PENDING_VERIFICATION'
              ? 'bg-[#161616] text-white shadow-sm'
              : 'text-[#525252] hover:text-[#161616]'
          }`}
        >
          <span>Menunggu Verifikasi</span>
          {#if pendingCount > 0}
            <span class="px-1.5 py-0.2 bg-[#da1e28] text-white text-[10px] font-mono font-bold">
              {pendingCount}
            </span>
          {/if}
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'ALL')}
          class={`px-3 py-1.5 text-xs font-medium transition-colors ${
            selectedStatus === 'ALL'
              ? 'bg-[#161616] text-white shadow-sm'
              : 'text-[#525252] hover:text-[#161616]'
          }`}
        >
          Semua ({invoices.length})
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'PAID')}
          class={`px-3 py-1.5 text-xs font-medium transition-colors ${
            selectedStatus === 'PAID'
              ? 'bg-[#161616] text-white shadow-sm'
              : 'text-[#525252] hover:text-[#161616]'
          }`}
        >
          Lunas
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'UNPAID')}
          class={`px-3 py-1.5 text-xs font-medium transition-colors ${
            selectedStatus === 'UNPAID'
              ? 'bg-[#161616] text-white shadow-sm'
              : 'text-[#525252] hover:text-[#161616]'
          }`}
        >
          Belum Bayar
        </button>
      </div>

      <!-- Search Box -->
      <div class="relative w-full md:w-72">
        <Search class="w-3.5 h-3.5 text-[#8c8c8c] absolute left-3 top-1/2 -translate-y-1/2" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari no. faktur / tenant / pengirim..."
          class="w-full pl-9 pr-3 py-1.5 text-xs bg-[#f4f4f4] border border-[#e0e0e0] focus:outline-none focus:border-[#0f62fe] focus:bg-white text-[#161616]"
        />
      </div>
    </div>
  </div>

  <!-- Invoices Table -->
  <div class="bg-white border border-[#e0e0e0] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-[#f4f4f4] border-b border-[#e0e0e0] text-[#525252] font-semibold uppercase tracking-wider">
          <tr>
            <th class="py-3 px-4">No. Faktur & Tanggal</th>
            <th class="py-3 px-4">Tenant / Pemilik</th>
            <th class="py-3 px-4">Nominal Pokok</th>
            <th class="py-3 px-4">Kode Unik</th>
            <th class="py-3 px-4">Total Tagihan</th>
            <th class="py-3 px-4">Bukti Bayar</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#f4f4f4]">
          {#if isLoading}
            <tr>
              <td colspan="8" class="py-12 text-center text-[#8c8c8c]">
                <RefreshCw class="w-5 h-5 animate-spin mx-auto mb-2 text-[#0f62fe]" />
                <span>Memuat data faktur...</span>
              </td>
            </tr>
          {:else if filteredInvoices.length === 0}
            <tr>
              <td colspan="8" class="py-12 text-center text-[#8c8c8c]">
                <FileText class="w-6 h-6 mx-auto mb-2 text-[#c6c6c6]" />
                <span>Tidak ada faktur tagihan yang sesuai kriteria.</span>
              </td>
            </tr>
          {:else}
            {#each filteredInvoices as invoice (invoice.id)}
              <tr class="hover:bg-[#fbfbfb] transition-colors">
                <!-- No. Faktur & Tanggal -->
                <td class="py-3 px-4">
                  <div class="font-mono font-semibold text-[#161616]">{invoice.invoice_number}</div>
                  <div class="text-[11px] text-[#8c8c8c]">
                    {new Date(invoice.created_at).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'short',
                      year: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </div>
                </td>

                <!-- Tenant / Pemilik -->
                <td class="py-3 px-4">
                  <div class="font-medium text-[#161616]">{invoice.user?.name || 'Tanpa Nama'}</div>
                  <div class="text-[11px] text-[#525252] font-mono">{invoice.user?.email}</div>
                  {#if invoice.user?.workspaces && invoice.user.workspaces.length > 0}
                    <div class="text-[10px] text-[#0f62fe] mt-0.5">
                      {invoice.user.workspaces.map((w) => w.name).join(', ')}
                    </div>
                  {/if}
                </td>

                <!-- Nominal Pokok -->
                <td class="py-3 px-4 font-mono text-[#525252]">
                  {formatRupiah(invoice.amount_base)}
                </td>

                <!-- 3-Digit Unique Code -->
                <td class="py-3 px-4">
                  <span class="inline-block px-2 py-0.5 bg-[#edf5ff] text-[#0f62fe] font-mono font-bold text-xs border border-[#d0e2ff]">
                    +{invoice.unique_code}
                  </span>
                </td>

                <!-- Total Amount -->
                <td class="py-3 px-4 font-mono font-bold text-[#161616]">
                  {formatRupiah(invoice.total_amount)}
                </td>

                <!-- Bukti Bayar Info -->
                <td class="py-3 px-4">
                  {#if invoice.confirmation}
                    <div>
                      <div class="font-medium text-[#161616] text-[11px]">{invoice.confirmation.bank_account_name}</div>
                      <div class="text-[10px] text-[#24a148] font-mono">
                        TF: {formatRupiah(invoice.confirmation.transfer_amount)}
                      </div>
                    </div>
                  {:else}
                    <span class="text-[11px] text-[#8c8c8c] italic">Belum diunggah</span>
                  {/if}
                </td>

                <!-- Status Badge -->
                <td class="py-3 px-4">
                  {#if invoice.status === 'PENDING_VERIFICATION'}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#fdf2d0] text-[#b28600] text-[11px] font-semibold border border-[#f1c21b]">
                      Menunggu Review
                    </span>
                  {:else if invoice.status === 'PAID'}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#defbe6] text-[#24a148] text-[11px] font-semibold border border-[#6fdc8c]">
                      Lunas Diverifikasi
                    </span>
                  {:else if invoice.status === 'UNPAID'}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#f4f4f4] text-[#525252] text-[11px] font-semibold border border-[#e0e0e0]">
                      Belum Dibayar
                    </span>
                  {:else}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#ffebee] text-[#da1e28] text-[11px] font-semibold">
                      Kedaluwarsa
                    </span>
                  {/if}
                </td>

                <!-- Action Button -->
                <td class="py-3 px-4 text-right">
                  <button
                    type="button"
                    onclick={() => openInspection(invoice)}
                    class={`inline-flex items-center space-x-1 px-2.5 py-1 text-xs font-medium transition-colors ${
                      invoice.status === 'PENDING_VERIFICATION'
                        ? 'bg-[#0f62fe] hover:bg-[#0050e6] text-white'
                        : 'bg-[#f4f4f4] hover:bg-[#e0e0e0] text-[#161616] border border-[#e0e0e0]'
                    }`}
                  >
                    <Eye class="w-3.5 h-3.5" />
                    <span>{invoice.status === 'PENDING_VERIFICATION' ? 'Inspeksi & Verifikasi' : 'Detail'}</span>
                  </button>
                </td>
              </tr>
            {/each}
          {/if}
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Inspeksi Bukti Transfer -->
{#if inspectingInvoice}
  <div class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white border border-[#161616] w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl">
      <!-- Modal Header -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-[#e0e0e0] bg-[#161616] text-white">
        <div class="flex items-center space-x-2">
          <ShieldCheck class="w-5 h-5 text-[#0f62fe]" />
          <div>
            <h2 class="text-sm font-bold tracking-tight">Inspeksi Bukti Pembayaran Faktur</h2>
            <p class="text-[11px] text-[#8c8c8c] font-mono">{inspectingInvoice.invoice_number}</p>
          </div>
        </div>
        <button
          type="button"
          onclick={closeInspection}
          class="text-[#8c8c8c] hover:text-white transition-colors"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-5 overflow-y-auto space-y-4 text-xs">
        {#if verificationSuccessMessage}
          <div class="p-3 bg-[#defbe6] border border-[#24a148] text-[#24a148] font-medium flex items-center space-x-2">
            <CheckCircle class="w-4 h-4 shrink-0" />
            <span>{verificationSuccessMessage}</span>
          </div>
        {/if}

        {#if verificationErrorMessage}
          <div class="p-3 bg-[#ffebee] border border-[#da1e28] text-[#da1e28] font-medium flex items-center space-x-2">
            <AlertCircle class="w-4 h-4 shrink-0" />
            <span>{verificationErrorMessage}</span>
          </div>
        {/if}

        <!-- Invoice & Payment Metadata Grid -->
        <div class="grid grid-cols-2 gap-4 bg-[#f4f4f4] p-4 border border-[#e0e0e0]">
          <div>
            <span class="text-[11px] text-[#525252] uppercase font-semibold">Tenant Pemilik:</span>
            <div class="font-bold text-[#161616] mt-0.5">{inspectingInvoice.user?.name}</div>
            <div class="font-mono text-[#525252] text-[11px]">{inspectingInvoice.user?.email}</div>
          </div>

          <div>
            <span class="text-[11px] text-[#525252] uppercase font-semibold">Status Langganan Saat Ini:</span>
            <div class="mt-0.5">
              <span class="inline-block px-2 py-0.5 bg-white border border-[#e0e0e0] font-mono font-bold text-[#161616]">
                {inspectingInvoice.user?.subscription_status || 'UNKNOWN'}
              </span>
            </div>
          </div>

          <div class="border-t border-[#e0e0e0] pt-2">
            <span class="text-[11px] text-[#525252] uppercase font-semibold">Nominal Wajib Transfer:</span>
            <div class="text-base font-bold font-mono text-[#0f62fe] mt-0.5">
              {formatRupiah(inspectingInvoice.total_amount)}
            </div>
            <div class="text-[10px] text-[#525252]">
              Pokok: {formatRupiah(inspectingInvoice.amount_base)} + Kode Unik: <span class="font-mono font-bold">+{inspectingInvoice.unique_code}</span>
            </div>
          </div>

          <div class="border-t border-[#e0e0e0] pt-2">
            <span class="text-[11px] text-[#525252] uppercase font-semibold">Nominal Ditransfer Tenant:</span>
            {#if inspectingInvoice.confirmation}
              <div class="text-base font-bold font-mono text-[#161616] mt-0.5">
                {formatRupiah(inspectingInvoice.confirmation.transfer_amount)}
              </div>
              <div class="text-[10px] text-[#525252]">
                Pengirim: <span class="font-semibold text-[#161616]">{inspectingInvoice.confirmation.bank_account_name}</span>
              </div>
            {:else}
              <div class="text-sm font-mono text-[#8c8c8c] italic mt-0.5">Belum ada konfirmasi</div>
            {/if}
          </div>
        </div>

        <!-- Payment Proof Image Box -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <span class="font-semibold uppercase tracking-wider text-[#161616] text-[11px]">
              Lampiran Bukti Struk Transfer:
            </span>
            {#if inspectingInvoice.confirmation?.proof_image_url}
              <a
                href={inspectingInvoice.confirmation.proof_image_url}
                target="_blank"
                rel="noreferrer"
                class="text-[#0f62fe] hover:underline inline-flex items-center space-x-1 text-[11px]"
              >
                <span>Buka Gambar Asli</span>
                <ExternalLink class="w-3 h-3" />
              </a>
            {/if}
          </div>

          {#if inspectingInvoice.confirmation?.proof_image_url}
            <div class="border border-[#e0e0e0] bg-[#161616] p-2 flex items-center justify-center max-h-72 overflow-hidden">
              <img
                src={inspectingInvoice.confirmation.proof_image_url}
                alt="Bukti Transfer Pembayaran"
                class="max-h-64 object-contain"
                loading="lazy"
              />
            </div>
          {:else}
            <div class="p-8 border border-dashed border-[#e0e0e0] text-center text-[#8c8c8c]">
              <AlertCircle class="w-6 h-6 mx-auto mb-1 text-[#c6c6c6]" />
              <span>Tenant belum mengunggah foto struk bukti transfer untuk faktur ini.</span>
            </div>
          {/if}
        </div>
      </div>

      <!-- Modal Footer / Actions -->
      <div class="px-5 py-3 border-t border-[#e0e0e0] bg-[#f4f4f4] flex items-center justify-between">
        <button
          type="button"
          onclick={closeInspection}
          class="px-4 py-2 bg-white hover:bg-[#e0e0e0] text-[#161616] text-xs font-medium border border-[#e0e0e0] transition-colors"
        >
          Tutup
        </button>

        {#if inspectingInvoice.status === 'PENDING_VERIFICATION'}
          <button
            type="button"
            onclick={() => handleVerify(inspectingInvoice!.id)}
            disabled={isVerifying}
            class="px-4 py-2 bg-[#24a148] hover:bg-[#1e833a] text-white text-xs font-bold transition-colors inline-flex items-center space-x-1.5 disabled:opacity-50"
          >
            {#if isVerifying}
              <RefreshCw class="w-3.5 h-3.5 animate-spin" />
              <span>Memproses Aktivasi...</span>
            {:else}
              <CheckCircle class="w-3.5 h-3.5" />
              <span>Verifikasi & Aktifkan (+30 Hari)</span>
            {/if}
          </button>
        {:else if inspectingInvoice.status === 'PAID'}
          <div class="text-[#24a148] font-semibold text-xs flex items-center space-x-1">
            <CheckCircle class="w-4 h-4" />
            <span>Faktur ini telah terverifikasi lunas.</span>
          </div>
        {/if}
      </div>
    </div>
  </div>
{/if}
