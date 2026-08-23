<script lang="ts">
  import { Lock, X, Delete } from 'lucide-svelte';
  import type { CashierUser } from '../../types/pos';

  interface Props {
    isOpen: boolean;
    cashiers: CashierUser[];
    activeCashier: CashierUser | null;
    onClose: () => void;
    onSelectCashier: (cashier: CashierUser) => void;
  }

  let {
    isOpen = false,
    cashiers = [],
    activeCashier,
    onClose,
    onSelectCashier,
  }: Props = $props();

  let enteredPin = $state('');
  let selectedUser = $state<CashierUser | null>(null);
  let errorMessage = $state('');

  $effect(() => {
    if (isOpen) {
      enteredPin = '';
      errorMessage = '';
      selectedUser = activeCashier || cashiers[0] || null;
    }
  });

  function handleNumClick(num: string) {
    if (enteredPin.length < 4) {
      enteredPin += num;
      errorMessage = '';
      if (enteredPin.length === 4) {
        verifyPin();
      }
    }
  }

  function handleBackspace() {
    enteredPin = enteredPin.slice(0, -1);
    errorMessage = '';
  }

  function handleClear() {
    enteredPin = '';
    errorMessage = '';
  }

  function verifyPin() {
    if (!selectedUser) {
      errorMessage = 'Pilih karyawan terlebih dahulu.';
      return;
    }

    if (enteredPin === selectedUser.pin) {
      onSelectCashier(selectedUser);
      onClose();
    } else {
      errorMessage = 'PIN salah. Coba lagi.';
      enteredPin = '';
    }
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#e0e0e0] max-w-sm w-full p-6 shadow-2xl">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3 mb-4">
        <div class="flex items-center gap-2">
          <Lock class="w-4 h-4 text-[#0f62fe]" />
          <h2 class="text-sm font-bold text-[#161616]">Otorisasi PIN Kasir</h2>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="text-[#8c8c8c] hover:text-[#161616] cursor-pointer"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Cashier User Selector -->
      <div class="mb-4">
        <div class="text-[11px] font-mono text-[#525252] mb-1.5">Pilih Karyawan Bertugas:</div>
        <div class="grid grid-cols-2 gap-1.5">
          {#each cashiers as cashier}
            <button
              type="button"
              onclick={() => {
                selectedUser = cashier;
                enteredPin = '';
                errorMessage = '';
              }}
              class={`p-2 border text-left flex items-center gap-2 transition-colors cursor-pointer ${
                selectedUser?.id === cashier.id
                  ? 'bg-[#0f62fe] text-white border-[#0f62fe] font-semibold shadow-xs'
                  : 'bg-[#f4f4f4] text-[#161616] border-[#e0e0e0] hover:bg-[#e0e0e0]'
              }`}
            >
              <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-[10px] font-bold">
                {cashier.name.charAt(0)}
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-xs truncate">{cashier.name}</div>
                <div class="text-[9px] font-mono opacity-80">{cashier.role}</div>
              </div>
            </button>
          {/each}
        </div>
      </div>

      <!-- PIN Input Indicator (4 dots) -->
      <div class="my-4 text-center">
        <div class="flex justify-center gap-3 mb-2">
          {#each [0, 1, 2, 3] as idx}
            <div
              class={`w-4 h-4 rounded-full border transition-all ${
                enteredPin.length > idx
                  ? 'bg-[#0f62fe] border-[#0f62fe] scale-110'
                  : 'bg-[#f4f4f4] border-[#e0e0e0]'
              }`}
            ></div>
          {/each}
        </div>

        {#if errorMessage}
          <div class="text-xs text-[#da1e28] font-mono mt-1">{errorMessage}</div>
        {:else}
          <div class="text-[11px] font-mono text-[#8c8c8c]">
            Masukkan 4-digit PIN {selectedUser ? `untuk ${selectedUser.name}` : ''}
          </div>
        {/if}
      </div>

      <!-- Numpad Grid -->
      <div class="grid grid-cols-3 gap-2 mt-4">
        {#each ['1', '2', '3', '4', '5', '6', '7', '8', '9'] as num}
          <button
            type="button"
            onclick={() => handleNumClick(num)}
            class="h-12 bg-[#f4f4f4] hover:bg-[#e0e0e0] active:bg-[#0f62fe] active:text-white border border-[#e0e0e0] font-mono text-base font-bold text-[#161616] flex items-center justify-center cursor-pointer transition-colors"
          >
            {num}
          </button>
        {/each}
        <button
          type="button"
          onclick={handleClear}
          class="h-12 bg-[#f4f4f4] hover:bg-[#e0e0e0] border border-[#e0e0e0] font-mono text-xs text-[#da1e28] flex items-center justify-center cursor-pointer"
        >
          C
        </button>
        <button
          type="button"
          onclick={() => handleNumClick('0')}
          class="h-12 bg-[#f4f4f4] hover:bg-[#e0e0e0] active:bg-[#0f62fe] active:text-white border border-[#e0e0e0] font-mono text-base font-bold text-[#161616] flex items-center justify-center cursor-pointer transition-colors"
        >
          0
        </button>
        <button
          type="button"
          onclick={handleBackspace}
          class="h-12 bg-[#f4f4f4] hover:bg-[#e0e0e0] border border-[#e0e0e0] font-mono text-xs text-[#525252] flex items-center justify-center cursor-pointer"
        >
          <Delete class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
{/if}
