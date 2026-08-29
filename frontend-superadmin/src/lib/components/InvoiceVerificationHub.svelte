<script lang="ts">
  import {
    CheckCircle,
    Eye,
    AlertCircle,
    RefreshCw,
    Search,
    ShieldCheck,
    X,
    Receipt,
  } from 'lucide-svelte';
  import type { InvoiceRecord } from '../types/superadmin';

  interface Props {
    invoices: InvoiceRecord[];
    isLoading: boolean;
    onVerifyInvoice: (invoiceId: string) => Promise<void>;
    onRefresh: () => void;
  }

  let { invoices, isLoading, onVerifyInvoice, onRefresh }: Props = $props();

  let selectedStatus = $state<'ALL' | 'PENDING_VERIFICATION' | 'PAID' | 'UNPAID'>(
    'PENDING_VERIFICATION'
  );
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
        (inv.user?.name && inv.user.name.toLowerCase().includes(q)) ||
        (inv.user?.email && inv.user.email.toLowerCase().includes(q)) ||
        (inv.confirmation?.bank_account_name &&
          inv.confirmation.bank_account_name.toLowerCase().includes(q));

      return matchStatus && matchQuery;
    })
  );

  let pendingCount = $derived(
    invoices.filter((inv) => inv.status === 'PENDING_VERIFICATION').length
  );

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
      verificationSuccessMessage =
        'Faktur berhasil diverifikasi! Masa aktif tenant telah diperpanjang 30 hari.';
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

<div class="space-y-5 font-sans">
  <!-- Clean Page Header -->
  <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
    <div>
      <h1 class="text-xl font-semibold tracking-tight text-[#17171c]">Verifikasi Faktur</h1>
      <p class="mt-0.5 text-xs text-[#75758a]">
        Validasi transfer manual, periksa foto struk pembayaran, dan lakukan persetujuan 1-klik.
      </p>
    </div>

    <button
      type="button"
      onclick={onRefresh}
      disabled={isLoading}
      class="inline-flex cursor-pointer items-center gap-1.5 self-start rounded-lg border border-[#d9d9dd] bg-white px-3.5 py-2 text-xs font-medium text-[#17171c] shadow-xs transition-all hover:bg-[#f4f4f4] disabled:opacity-50 sm:self-auto"
    >
      <RefreshCw class={`size-3.5 ${isLoading ? 'animate-spin' : ''}`} />
      <span>Segarkan</span>
    </button>
  </div>

  <!-- Unified Main Container Card -->
  <div class="overflow-hidden rounded-xl border border-[#d9d9dd] bg-white shadow-xs">
    <!-- Toolbar: Filter Pills & Search -->
    <div
      class="flex flex-col justify-between gap-3 border-b border-[#e5e5e5] bg-[#fafafa] p-3.5 sm:p-4 md:flex-row md:items-center"
    >
      <!-- Status Tabs -->
      <div class="flex flex-wrap items-center gap-1">
        <button
          type="button"
          onclick={() => (selectedStatus = 'PENDING_VERIFICATION')}
          class={`flex cursor-pointer items-center gap-1.5 rounded-md px-3 py-1 text-xs font-medium transition-all ${
            selectedStatus === 'PENDING_VERIFICATION'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:bg-[#eaeaea] hover:text-[#17171c]'
          }`}
        >
          <span>Menunggu Verifikasi</span>
          {#if pendingCount > 0}
            <span
              class="py-0.2 rounded-md bg-[#e5484d] px-1.5 font-mono text-[10px] font-bold text-white"
            >
              {pendingCount}
            </span>
          {/if}
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'ALL')}
          class={`cursor-pointer rounded-md px-3 py-1 text-xs font-medium transition-all ${
            selectedStatus === 'ALL'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:bg-[#eaeaea] hover:text-[#17171c]'
          }`}
        >
          Semua ({invoices.length})
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'PAID')}
          class={`cursor-pointer rounded-md px-3 py-1 text-xs font-medium transition-all ${
            selectedStatus === 'PAID'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:bg-[#eaeaea] hover:text-[#17171c]'
          }`}
        >
          Lunas
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'UNPAID')}
          class={`cursor-pointer rounded-md px-3 py-1 text-xs font-medium transition-all ${
            selectedStatus === 'UNPAID'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:bg-[#eaeaea] hover:text-[#17171c]'
          }`}
        >
          Belum Bayar
        </button>
      </div>

      <!-- Search Box -->
      <div class="relative w-full md:w-64">
        <Search class="absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-[#93939f]" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari no. faktur / tenant..."
          class="w-full rounded-lg border border-[#d9d9dd] bg-white py-1.5 pr-3 pl-8 text-xs text-[#17171c] transition-all focus:border-[#17171c] focus:outline-hidden"
        />
      </div>
    </div>

    <!-- Content State: Loading / Empty / Data -->
    {#if isLoading}
      <div class="space-y-2 py-16 text-center text-[#93939f]">
        <RefreshCw class="mx-auto size-5 animate-spin text-[#17171c]" />
        <p class="text-xs">Memuat data faktur tagihan...</p>
      </div>
    {:else if filteredInvoices.length === 0}
      <div class="space-y-2 py-16 text-center text-[#93939f]">
        <Receipt class="mx-auto size-8 text-[#93939f] opacity-40" />
        <p class="text-xs font-medium text-[#17171c]">Tidak ada faktur yang sesuai filter</p>
        <p class="text-[11px] text-[#75758a]">
          Faktur tagihan baru akan otomatis muncul di sini saat tenant membuat pesanan.
        </p>
      </div>
    {:else}
      <!-- Desktop Table View -->
      <div class="hidden overflow-x-auto md:block">
        <table class="w-full border-collapse text-left text-xs">
          <thead
            class="border-b border-[#e5e5e5] bg-[#fafafa] text-[11px] font-medium text-[#75758a]"
          >
            <tr>
              <th class="px-4 py-3">No. Faktur &amp; Tanggal</th>
              <th class="px-4 py-3">Tenant / Pemilik</th>
              <th class="px-4 py-3 text-right">Nominal Pokok</th>
              <th class="px-4 py-3 text-center">Kode Unik</th>
              <th class="px-4 py-3 text-right">Total Tagihan</th>
              <th class="px-4 py-3">Bukti Bayar</th>
              <th class="px-4 py-3 text-center">Status</th>
              <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#e5e5e5]">
            {#each filteredInvoices as invoice (invoice.id)}
              <tr class="transition-colors hover:bg-[#f9fafb]">
                <!-- No. Faktur & Tanggal -->
                <td class="px-4 py-3.5">
                  <div class="font-mono font-medium text-[#17171c]">{invoice.invoice_number}</div>
                  <div class="mt-0.5 font-mono text-[10px] text-[#75758a]">
                    {new Date(invoice.created_at).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'short',
                      year: 'numeric',
                    })}
                  </div>
                </td>

                <!-- Tenant / Pemilik -->
                <td class="px-4 py-3.5">
                  <div class="font-medium text-[#17171c]">{invoice.user?.name || 'Tanpa Nama'}</div>
                  <div class="font-mono text-[10px] text-[#75758a]">{invoice.user?.email}</div>
                  {#if invoice.user?.workspaces && invoice.user.workspaces.length > 0}
                    <div class="mt-0.5 text-[10px] font-medium text-[#1863dc]">
                      {invoice.user.workspaces.map((w) => w.name).join(', ')}
                    </div>
                  {/if}
                </td>

                <!-- Nominal Pokok -->
                <td class="px-4 py-3.5 text-right font-mono text-[#616161]">
                  {formatRupiah(invoice.amount_base)}
                </td>

                <!-- 3-Digit Unique Code -->
                <td class="px-4 py-3.5 text-center">
                  <span
                    class="py-0.2 inline-block rounded-md bg-[#f1f5ff] px-1.5 font-mono text-[11px] font-medium text-[#1863dc]"
                  >
                    +{invoice.unique_code}
                  </span>
                </td>

                <!-- Total Amount -->
                <td class="px-4 py-3.5 text-right font-mono font-semibold text-[#17171c]">
                  {formatRupiah(invoice.total_amount)}
                </td>

                <!-- Bukti Bayar Info -->
                <td class="px-4 py-3.5">
                  {#if invoice.confirmation}
                    <div>
                      <div class="max-w-35 truncate text-[11px] font-medium text-[#17171c]">
                        {invoice.confirmation.bank_account_name}
                      </div>
                      <div class="font-mono text-[10px] font-medium text-[#16a34a]">
                        TF: {formatRupiah(invoice.confirmation.transfer_amount)}
                      </div>
                    </div>
                  {:else}
                    <span class="text-[10px] text-[#93939f] italic">Belum diunggah</span>
                  {/if}
                </td>

                <!-- Status Badge -->
                <td class="px-4 py-3.5 text-center">
                  {#if invoice.status === 'PENDING_VERIFICATION'}
                    <span
                      class="inline-flex items-center rounded-md bg-[#fee2e2] px-2 py-0.5 text-[10px] font-semibold text-[#991b1b]"
                    >
                      Menunggu Review
                    </span>
                  {:else if invoice.status === 'PAID'}
                    <span
                      class="inline-flex items-center rounded-md bg-[#edfce9] px-2 py-0.5 text-[10px] font-semibold text-[#003c33]"
                    >
                      Lunas
                    </span>
                  {:else if invoice.status === 'UNPAID'}
                    <span
                      class="inline-flex items-center rounded-md bg-[#f1f5ff] px-2 py-0.5 text-[10px] font-semibold text-[#1863dc]"
                    >
                      Belum Bayar
                    </span>
                  {:else}
                    <span
                      class="inline-flex items-center rounded-md bg-[#f4f4f5] px-2 py-0.5 text-[10px] font-semibold text-[#71717a]"
                    >
                      Kedaluwarsa
                    </span>
                  {/if}
                </td>

                <!-- Action Button -->
                <td class="px-4 py-3.5 text-right">
                  <button
                    type="button"
                    onclick={() => openInspection(invoice)}
                    class={`inline-flex cursor-pointer items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium transition-all ${
                      invoice.status === 'PENDING_VERIFICATION'
                        ? 'bg-[#17171c] text-white hover:bg-black'
                        : 'border border-[#d9d9dd] bg-white text-[#17171c] hover:bg-[#f4f4f4]'
                    }`}
                  >
                    <Eye class="size-3.5" />
                    <span
                      >{invoice.status === 'PENDING_VERIFICATION' ? 'Verifikasi' : 'Detail'}</span
                    >
                  </button>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>

      <!-- Mobile Clean Card View -->
      <div class="divide-y divide-[#e5e5e5] md:hidden">
        {#each filteredInvoices as invoice (invoice.id)}
          <div class="space-y-3 p-4">
            <div class="flex items-start justify-between gap-2">
              <div>
                <div class="font-mono text-xs font-medium text-[#17171c]">
                  {invoice.invoice_number}
                </div>
                <div class="text-xs text-[#75758a]">
                  {invoice.user?.name || 'Tanpa Nama'} ({invoice.user?.email})
                </div>
              </div>

              {#if invoice.status === 'PENDING_VERIFICATION'}
                <span
                  class="inline-flex shrink-0 items-center rounded-md bg-[#fee2e2] px-2 py-0.5 text-[10px] font-semibold text-[#991b1b]"
                >
                  Menunggu Review
                </span>
              {:else if invoice.status === 'PAID'}
                <span
                  class="inline-flex shrink-0 items-center rounded-md bg-[#edfce9] px-2 py-0.5 text-[10px] font-semibold text-[#003c33]"
                >
                  Lunas
                </span>
              {:else if invoice.status === 'UNPAID'}
                <span
                  class="inline-flex shrink-0 items-center rounded-md bg-[#f1f5ff] px-2 py-0.5 text-[10px] font-semibold text-[#1863dc]"
                >
                  Belum Bayar
                </span>
              {:else}
                <span
                  class="inline-flex shrink-0 items-center rounded-md bg-[#f4f4f5] px-2 py-0.5 text-[10px] font-semibold text-[#71717a]"
                >
                  Kedaluwarsa
                </span>
              {/if}
            </div>

            <!-- Total Amount & Meta Grid -->
            <div
              class="grid grid-cols-2 gap-2 rounded-lg border border-[#e5e5e5] bg-[#fafafa] p-2.5 text-xs"
            >
              <div>
                <div class="text-[10px] text-[#75758a]">Total Tagihan</div>
                <div class="mt-0.5 font-mono text-sm font-bold text-[#17171c]">
                  {formatRupiah(invoice.total_amount)}
                </div>
                <div class="font-mono text-[10px] text-[#75758a]">
                  Kode Unik: +{invoice.unique_code}
                </div>
              </div>

              <div>
                <div class="text-[10px] text-[#75758a]">Pengirim Rekening</div>
                {#if invoice.confirmation}
                  <div class="mt-0.5 truncate font-medium text-[#17171c]">
                    {invoice.confirmation.bank_account_name}
                  </div>
                  <div class="font-mono text-[10px] text-[#16a34a]">
                    TF: {formatRupiah(invoice.confirmation.transfer_amount)}
                  </div>
                {:else}
                  <div class="mt-0.5 text-[#93939f] italic">Belum diunggah</div>
                {/if}
              </div>
            </div>

            <button
              type="button"
              onclick={() => openInspection(invoice)}
              class={`flex w-full items-center justify-center gap-1.5 rounded-lg py-2 text-xs font-medium transition-all ${
                invoice.status === 'PENDING_VERIFICATION'
                  ? 'bg-[#17171c] text-white'
                  : 'border border-[#d9d9dd] bg-white text-[#17171c] hover:bg-[#f4f4f4]'
              }`}
            >
              <Eye class="size-3.5" />
              <span
                >{invoice.status === 'PENDING_VERIFICATION'
                  ? 'Inspeksi & Verifikasi'
                  : 'Lihat Detail'}</span
              >
            </button>
          </div>
        {/each}
      </div>
    {/if}
  </div>
</div>

<!-- Modal Inspeksi Bukti Transfer -->
{#if inspectingInvoice}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-[#d9d9dd] bg-white shadow-xl"
    >
      <!-- Modal Header -->
      <div
        class="flex items-center justify-between border-b border-[#e5e5e5] bg-[#17171c] px-6 py-4 text-white"
      >
        <div class="flex items-center gap-2.5">
          <ShieldCheck class="size-5 text-[#edfce9]" />
          <div>
            <h2 class="text-sm font-semibold tracking-tight">Inspeksi Bukti Pembayaran</h2>
            <p class="font-mono text-[10px] text-[#93939f]">{inspectingInvoice.invoice_number}</p>
          </div>
        </div>
        <button
          type="button"
          onclick={closeInspection}
          class="cursor-pointer p-1 text-[#93939f] transition-colors hover:text-white"
        >
          <X class="size-5" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="space-y-4 overflow-y-auto p-6 text-xs">
        {#if verificationSuccessMessage}
          <div
            class="flex items-center gap-2 rounded-xl border border-[#bbf7d0] bg-[#edfce9] p-3 font-medium text-[#003c33]"
          >
            <CheckCircle class="size-4 shrink-0 text-[#16a34a]" />
            <span>{verificationSuccessMessage}</span>
          </div>
        {/if}

        {#if verificationErrorMessage}
          <div
            class="flex items-center gap-2 rounded-xl border border-[#fecaca] bg-[#ffefef] p-3 font-medium text-[#e5484d]"
          >
            <AlertCircle class="size-4 shrink-0 text-[#dc2626]" />
            <span>{verificationErrorMessage}</span>
          </div>
        {/if}

        <!-- Invoice Summary Grid -->
        <div
          class="grid grid-cols-1 gap-3.5 rounded-xl border border-[#e5e5e5] bg-[#fafafa] p-4 sm:grid-cols-2"
        >
          <div class="space-y-2">
            <div>
              <span class="font-mono text-[10px] text-[#75758a] uppercase">Pemilik Tenant:</span>
              <div class="mt-0.5 font-medium text-[#17171c]">
                {inspectingInvoice.user?.name || 'Tanpa Nama'}
              </div>
              <div class="font-mono text-[11px] text-[#75758a]">
                {inspectingInvoice.user?.email}
              </div>
            </div>

            <div>
              <span class="font-mono text-[10px] text-[#75758a] uppercase">Nominal Tagihan:</span>
              <div class="mt-0.5 font-mono text-sm font-bold text-[#17171c]">
                {formatRupiah(inspectingInvoice.total_amount)}
              </div>
              <div class="font-mono text-[10px] text-[#75758a]">
                Pokok: {formatRupiah(inspectingInvoice.amount_base)} | Kode: +{inspectingInvoice.unique_code}
              </div>
            </div>
          </div>

          <div class="space-y-2">
            <div>
              <span class="font-mono text-[10px] text-[#75758a] uppercase">Status Faktur:</span>
              <div class="mt-0.5">
                {#if inspectingInvoice.status === 'PAID'}
                  <span
                    class="rounded-md bg-[#edfce9] px-2 py-0.5 font-mono text-[10px] font-semibold text-[#003c33]"
                  >
                    LUNAS TERVERIFIKASI
                  </span>
                {:else if inspectingInvoice.status === 'PENDING_VERIFICATION'}
                  <span
                    class="rounded-md bg-[#fee2e2] px-2 py-0.5 font-mono text-[10px] font-semibold text-[#991b1b]"
                  >
                    MENUNGGU VERIFIKASI
                  </span>
                {:else}
                  <span
                    class="rounded-md bg-[#f1f5ff] px-2 py-0.5 font-mono text-[10px] font-semibold text-[#1863dc]"
                  >
                    {inspectingInvoice.status}
                  </span>
                {/if}
              </div>
            </div>

            <div>
              <span class="font-mono text-[10px] text-[#75758a] uppercase">Pengirim Rekening:</span>
              {#if inspectingInvoice.confirmation}
                <div class="mt-0.5 font-medium text-[#17171c]">
                  {inspectingInvoice.confirmation.bank_account_name}
                </div>
                <div class="font-mono text-[11px] font-medium text-[#16a34a]">
                  Jumlah: {formatRupiah(inspectingInvoice.confirmation.transfer_amount)}
                </div>
              {:else}
                <div class="mt-0.5 text-[#93939f] italic">Belum ada konfirmasi bayar</div>
              {/if}
            </div>
          </div>
        </div>

        <!-- Proof Image Preview -->
        {#if inspectingInvoice.confirmation && inspectingInvoice.confirmation.proof_image_url}
          <div class="space-y-1.5">
            <span class="font-mono text-[10px] text-[#75758a] uppercase"
              >Foto Bukti Struk Transfer:</span
            >
            <div
              class="overflow-hidden rounded-xl border border-[#e5e5e5] bg-[#fafafa] p-2 text-center"
            >
              <img
                src={inspectingInvoice.confirmation.proof_image_url}
                alt="Bukti Transfer"
                class="mx-auto max-h-72 rounded-lg border border-[#e5e5e5] object-contain"
              />
            </div>
          </div>
        {/if}
      </div>

      <!-- Modal Footer -->
      <div
        class="flex items-center justify-between gap-3 border-t border-[#e5e5e5] bg-[#fafafa] px-6 py-3.5"
      >
        <button
          type="button"
          onclick={closeInspection}
          class="cursor-pointer rounded-lg border border-[#d9d9dd] bg-white px-4 py-2 text-xs font-medium text-[#616161] transition-all hover:bg-[#f4f4f4]"
        >
          Tutup
        </button>

        {#if inspectingInvoice.status === 'PENDING_VERIFICATION'}
          <button
            type="button"
            onclick={() => handleVerify(inspectingInvoice!.id)}
            disabled={isVerifying}
            class="flex cursor-pointer items-center gap-1.5 rounded-lg bg-[#17171c] px-4 py-2 text-xs font-medium text-white transition-all hover:bg-black disabled:opacity-50"
          >
            {#if isVerifying}
              <RefreshCw class="size-3.5 animate-spin" />
              <span>Memproses...</span>
            {:else}
              <CheckCircle class="size-3.5 text-[#bbf7d0]" />
              <span>Setujui Pembayaran &amp; Aktifkan</span>
            {/if}
          </button>
        {/if}
      </div>
    </div>
  </div>
{/if}
