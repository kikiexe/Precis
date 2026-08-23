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
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-sm w-full p-6 shadow-none">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5 mb-4">
        <div class="flex items-center gap-2">
          <Lock class="w-4 h-4 text-[#1863dc]" />
          <h2 class="text-sm font-medium text-[#212121]">Otorisasi PIN Kasir</h2>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="text-[#93939f] hover:text-[#212121] cursor-pointer p-1"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Cashier User Selector -->
      <div class="mb-4">
        <div class="text-[11px] font-mono text-[#75758a] mb-2">Pilih Karyawan Bertugas:</div>
        <div class="grid grid-cols-2 gap-2">
          {#each cashiers as cashier}
            <button
              type="button"
              onclick={() => {
                selectedUser = cashier;
                enteredPin = '';
                errorMessage = '';
              }}
              class={`p-2.5 border rounded-[14px] text-left flex items-center gap-2 transition-all cursor-pointer ${
                selectedUser?.id === cashier.id
                  ? 'bg-[#17171c] text-white border-[#17171c] font-medium shadow-none'
                  : 'bg-[#eeece7]/40 text-[#212121] border-[#d9d9dd] hover:bg-[#eeece7]'
              }`}
            >
              <div class={`w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-medium ${selectedUser?.id === cashier.id ? 'bg-white/20 text-white' : 'bg-[#eeece7] text-[#212121]'}`}>
                {cashier.name.charAt(0)}
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-xs truncate font-medium">{cashier.name}</div>
                <div class={`text-[9px] font-mono ${selectedUser?.id === cashier.id ? 'text-[#edfce9]' : 'text-[#75758a]'}`}>{cashier.role}</div>
              </div>
            </button>
          {/each}
        </div>
      </div>

      <!-- PIN Input Indicator (4 dots) -->
      <div class="my-5 text-center">
        <div class="flex justify-center gap-3.5 mb-2">
          {#each [0, 1, 2, 3] as idx}
            <div
              class={`w-3.5 h-3.5 rounded-full transition-all ${
                enteredPin.length > idx
                  ? 'bg-[#17171c] scale-110'
                  : 'bg-[#eeece7] border border-[#d9d9dd]'
              }`}
            ></div>
          {/each}
        </div>

        {#if errorMessage}
          <div class="text-xs text-[#b30000] font-mono mt-1">{errorMessage}</div>
        {:else}
          <div class="text-[11px] font-mono text-[#75758a]">
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
            class="h-12 bg-[#eeece7]/40 hover:bg-[#eeece7] active:bg-[#17171c] active:text-white border border-[#d9d9dd] rounded-[12px] font-mono text-base font-medium text-[#212121] flex items-center justify-center cursor-pointer transition-all"
          >
            {num}
          </button>
        {/each}
        <button
          type="button"
          onclick={handleClear}
          class="h-12 bg-[#eeece7]/40 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-[12px] font-mono text-xs text-[#b30000] font-medium flex items-center justify-center cursor-pointer"
        >
          C
        </button>
        <button
          type="button"
          onclick={() => handleNumClick('0')}
          class="h-12 bg-[#eeece7]/40 hover:bg-[#eeece7] active:bg-[#17171c] active:text-white border border-[#d9d9dd] rounded-[12px] font-mono text-base font-medium text-[#212121] flex items-center justify-center cursor-pointer transition-all"
        >
          0
        </button>
        <button
          type="button"
          onclick={handleBackspace}
          class="h-12 bg-[#eeece7]/40 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-[12px] font-mono text-xs text-[#616161] flex items-center justify-center cursor-pointer"
        >
          <Delete class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
{/if}
