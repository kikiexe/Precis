<script lang="ts">
  import { ShieldAlert, RotateCcw, X, Check, AlertCircle, ChevronDown } from 'lucide-svelte';
  import type { OfflineOrder, CashierUser } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';
  import { posService } from '../../services/pos-service';

  interface Props {
    isOpen: boolean;
    mode: 'VOID' | 'REFUND';
    order: OfflineOrder | null;
    cashiers?: CashierUser[];
    onClose: () => void;
    onSuccess: (updatedOrder: {
      id: string;
      payment_status: string;
      void_reason?: string;
      refund_amount?: number;
    }) => void;
  }

  let {
    isOpen = false,
    mode = 'VOID',
    order = null,
    cashiers = [],
    onClose,
    onSuccess,
  }: Props = $props();

  let selectedApproverId = $state<string>('');
  let approverPin = $state('');
  let reason = $state('');
  let refundAmount = $state<number>(0);
  let refundMethod = $state<'CASH_DRAWER' | 'QRIS_TRANSFER' | 'STORE_CREDIT'>('CASH_DRAWER');
  let isLoading = $state(false);
  let errorMessage = $state<string | null>(null);

  let remainingRefundable = $derived(
    order ? Math.max(0, order.final_amount - (order.refund_amount || 0)) : 0
  );

  $effect(() => {
    if (isOpen && order) {
      errorMessage = null;
      approverPin = '';
      reason = '';
      refundMethod = 'CASH_DRAWER';
      refundAmount = remainingRefundable;

      // prioritaskan approver yang role-nya MANAGER, ADMIN, atau OWNER
      const managerUser = cashiers.find((c) =>
        ['MANAGER', 'ADMIN', 'OWNER', 'SUPERVISOR'].includes(c.role?.toUpperCase() || '')
      );
      selectedApproverId = managerUser?.id || cashiers[0]?.id || '';
    }
  });

  async function handleSubmit() {
    if (!order) return;

    if (!selectedApproverId) {
      errorMessage = 'Pilih akun manajer / approver yang berwenang.';
      return;
    }

    if (!approverPin || approverPin.length !== 6) {
      errorMessage = 'Masukkan 6 digit PIN otorisasi approver yang sah.';
      return;
    }

    if (!reason.trim()) {
      errorMessage = 'Alasan pembatalan atau pengembalian dana wajib diisi.';
      return;
    }

    isLoading = true;
    errorMessage = null;

    try {
      const orderTargetId = order.id || order.client_order_id;

      if (mode === 'VOID') {
        const res = await posService.voidOrder(
          orderTargetId,
          selectedApproverId,
          approverPin,
          reason.trim()
        );
        onSuccess(res);
        onClose();
      } else {
        if (refundAmount <= 0 || refundAmount > remainingRefundable) {
          errorMessage = `Nominal refund tidak boleh melebihi ${formatCurrency(remainingRefundable)}.`;
          isLoading = false;
          return;
        }

        const res = await posService.refundOrder(
          orderTargetId,
          selectedApproverId,
          approverPin,
          reason.trim(),
          refundAmount,
          refundMethod
        );
        onSuccess(res);
        onClose();
      }
    } catch (err: unknown) {
      if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Terjadi kesalahan saat memproses otorisasi.';
      }
    } finally {
      isLoading = false;
    }
  }

  function setPresetReason(preset: string) {
    reason = preset;
  }
</script>

{#if isOpen && order}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#17171c]/40 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-md rounded-[22px] border border-[#d9d9dd] bg-white p-6 shadow-none"
    >
      <!-- Header Modal -->
      <div class="mb-4 flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2">
          {#if mode === 'VOID'}
            <ShieldAlert class="size-4.5 text-[#b30000]" />
            <h2 class="text-sm font-bold text-[#212121]">Otorisasi Void Pesanan</h2>
          {:else}
            <RotateCcw class="size-4.5 text-[#1863dc]" />
            <h2 class="text-sm font-bold text-[#212121]">Pengembalian Dana (Refund)</h2>
          {/if}
        </div>
        <button
          type="button"
          onclick={onClose}
          disabled={isLoading}
          class="cursor-pointer p-1 text-[#93939f] hover:text-[#212121]"
        >
          <X class="size-4" />
        </button>
      </div>

      {#if errorMessage}
        <div
          class="mb-4 flex items-start gap-2 rounded-xl border border-[#ffad9b] bg-[#ffad9b]/15 p-3 text-xs text-[#b30000]"
        >
          <AlertCircle class="mt-0.5 size-4 shrink-0" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <!-- Order Summary Card -->
      <div class="mb-4 space-y-1.5 rounded-xl border border-[#d9d9dd] bg-[#eeece7]/30 p-3.5 text-xs">
        <div class="flex justify-between">
          <span class="text-[#616161]">Nomor Struk:</span>
          <span class="font-mono font-bold text-[#212121]">{order.order_number}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-[#616161]">Total Transaksi:</span>
          <span class="font-mono font-bold text-[#212121]">{formatCurrency(order.final_amount)}</span>
        </div>
        {#if mode === 'REFUND' && (order.refund_amount || 0) > 0}
          <div class="flex justify-between text-amber-700">
            <span>Sudah Di-refund:</span>
            <span class="font-mono font-bold">{formatCurrency(order.refund_amount || 0)}</span>
          </div>
          <div class="flex justify-between border-t border-[#d9d9dd] pt-1.5 font-bold text-emerald-800">
            <span>Maksimal Refund:</span>
            <span class="font-mono">{formatCurrency(remainingRefundable)}</span>
          </div>
        {/if}
      </div>

      <!-- Form Inputs -->
      <div class="space-y-4">
        <!-- Approver Selection -->
        <div>
          <label for="approver-select" class="mb-1.5 block text-xs font-medium text-[#616161]">
            Otorisasi Oleh (Manager / Supervisor):
          </label>
          <div class="relative">
            <select
              id="approver-select"
              bind:value={selectedApproverId}
              class="w-full cursor-pointer appearance-none rounded-xl border border-[#d9d9dd] bg-[#f8f8fa] px-3.5 py-2.5 pr-9 text-xs font-semibold text-[#17171c] transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
            >
              {#each cashiers as c}
                <option value={c.id}>{c.name} ({c.role})</option>
              {/each}
            </select>
            <ChevronDown
              class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-[#8e8e93]"
            />
          </div>
        </div>

        <!-- 6-digit PIN Input -->
        <div>
          <label for="approver-pin" class="mb-1.5 block text-xs font-medium text-[#616161]">
            PIN Otorisasi Approver (6 Digit):
          </label>
          <input
            id="approver-pin"
            type="password"
            maxlength="6"
            inputmode="numeric"
            pattern="[0-9]*"
            placeholder="••••••"
            bind:value={approverPin}
            class="w-full rounded-xl border border-[#d9d9dd] bg-[#eeece7]/40 px-4 py-2.5 text-center font-mono text-base font-medium tracking-widest text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          />
        </div>

        {#if mode === 'REFUND'}
          <!-- Refund Amount -->
          <div>
            <label for="refund-amount" class="mb-1.5 block text-xs font-medium text-[#616161]">
              Nominal Dana yang Dikembalikan (Rp):
            </label>
            <input
              id="refund-amount"
              type="number"
              min="1"
              max={remainingRefundable}
              step="1000"
              bind:value={refundAmount}
              class="w-full rounded-xl border border-[#d9d9dd] bg-[#eeece7]/40 px-4 py-2.5 font-mono text-sm font-medium text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
            />
          </div>

          <!-- Refund Method -->
          <div>
            <span class="mb-1.5 block text-xs font-medium text-[#616161]">
              Sumber Pengembalian Dana:
            </span>
            <div class="grid grid-cols-3 gap-2">
              <button
                type="button"
                onclick={() => (refundMethod = 'CASH_DRAWER')}
                class={`cursor-pointer rounded-xl border p-2 text-center text-xs font-semibold transition-all ${
                  refundMethod === 'CASH_DRAWER'
                    ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
                    : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:bg-zinc-100'
                }`}
              >
                Kas Laci
              </button>
              <button
                type="button"
                onclick={() => (refundMethod = 'QRIS_TRANSFER')}
                class={`cursor-pointer rounded-xl border p-2 text-center text-xs font-semibold transition-all ${
                  refundMethod === 'QRIS_TRANSFER'
                    ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
                    : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:bg-zinc-100'
                }`}
              >
                Transfer / QRIS
              </button>
              <button
                type="button"
                onclick={() => (refundMethod = 'STORE_CREDIT')}
                class={`cursor-pointer rounded-xl border p-2 text-center text-xs font-semibold transition-all ${
                  refundMethod === 'STORE_CREDIT'
                    ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
                    : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:bg-zinc-100'
                }`}
              >
                Kredit Toko
              </button>
            </div>
          </div>
        {/if}

        <!-- Reason Input & Presets -->
        <div>
          <label for="void-reason" class="mb-1.5 block text-xs font-medium text-[#616161]">
            Alasan {mode === 'VOID' ? 'Pembatalan (Void)' : 'Pengembalian Dana'}:
          </label>
          <input
            id="void-reason"
            type="text"
            bind:value={reason}
            placeholder="Tuliskan keterangan detail..."
            class="w-full rounded-xl border border-[#d9d9dd] bg-[#eeece7]/40 px-3.5 py-2.5 text-xs text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:outline-hidden"
          />
          <div class="mt-2 flex flex-wrap gap-1.5">
            {#if mode === 'VOID'}
              {#each ['Salah input menu kasir', 'Pelanggan membatalkan pesanan', 'Pergantian metode bayar', 'Duplikasi order'] as p}
                <button
                  type="button"
                  onclick={() => setPresetReason(p)}
                  class="cursor-pointer rounded-full border border-zinc-200 bg-zinc-100/80 px-2.5 py-0.5 text-[11px] text-zinc-700 hover:bg-zinc-200"
                >
                  {p}
                </button>
              {/each}
            {:else}
              {#each ['Komplain kualitas / rasa', 'Makanan / minuman reject', 'Item pesanan tertinggal', 'Retur barang'] as p}
                <button
                  type="button"
                  onclick={() => setPresetReason(p)}
                  class="cursor-pointer rounded-full border border-zinc-200 bg-zinc-100/80 px-2.5 py-0.5 text-[11px] text-zinc-700 hover:bg-zinc-200"
                >
                  {p}
                </button>
              {/each}
            {/if}
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3 pt-3">
          <button
            type="button"
            onclick={onClose}
            disabled={isLoading}
            class="flex-1 cursor-pointer rounded-full border border-[#d9d9dd] py-2.5 text-xs font-medium text-[#616161] transition-all hover:bg-[#eeece7]/40"
          >
            Batal
          </button>
          <button
            type="button"
            onclick={handleSubmit}
            disabled={isLoading || (mode === 'REFUND' && refundAmount <= 0)}
            class={`flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-full py-2.5 text-xs font-medium text-white transition-all disabled:opacity-50 ${
              mode === 'VOID'
                ? 'bg-[#b30000] hover:bg-[#800000]'
                : 'bg-zinc-900 hover:bg-black'
            }`}
          >
            {#if isLoading}
              <span>Memproses...</span>
            {:else}
              <Check class="size-3.5" />
              <span>{mode === 'VOID' ? 'Konfirmasi Void' : `Refund (${formatCurrency(refundAmount)})`}</span>
            {/if}
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}
