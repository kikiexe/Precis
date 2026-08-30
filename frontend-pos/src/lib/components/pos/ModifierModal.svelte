<script lang="ts">
  import { X, Check, AlertCircle, Layers } from 'lucide-svelte';
  import type { Product, AddonCategory, SelectedModifier, Addon } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';

  interface Props {
    isOpen: boolean;
    product: Product | null;
    addonCategories?: AddonCategory[];
    initialModifiers?: SelectedModifier[];
    initialNotes?: string;
    onClose: () => void;
    onConfirm: (data: {
      modifiers: SelectedModifier[];
      notes: string;
      unit_price: number;
    }) => void;
  }

  let {
    isOpen = false,
    product = null,
    addonCategories = [],
    initialModifiers = [],
    initialNotes = '',
    onClose,
    onConfirm,
  }: Props = $props();

  let selectedModifiers = $state<SelectedModifier[]>([]);
  let notes = $state('');
  let validationError = $state<string | null>(null);

  $effect(() => {
    if (isOpen && product) {
      selectedModifiers = initialModifiers ? [...initialModifiers] : [];
      notes = initialNotes || '';
      validationError = null;
    }
  });

  // Filter addon categories that apply to this product
  let applicableCategories = $derived(
    addonCategories.filter((cat) => {
      if (!product) return false;
      if (product.addon_category_ids && product.addon_category_ids.length > 0) {
        return product.addon_category_ids.includes(cat.id);
      }
      return true;
    })
  );

  let calculatedUnitPrice = $derived(
    product
      ? Number(product.base_price) +
          selectedModifiers.reduce((sum, mod) => sum + Number(mod.price), 0)
      : 0
  );

  function isAddonSelected(addonId: string): boolean {
    return selectedModifiers.some((m) => m.addon_id === addonId);
  }

  function handleToggleAddon(category: AddonCategory, addon: Addon) {
    validationError = null;
    const exists = isAddonSelected(addon.id);

    if (category.selection_type === 'SINGLE') {
      // Remove any other addon in the same category
      const otherCategoryAddonIds = category.addons?.map((a) => a.id) || [];
      const filtered = selectedModifiers.filter(
        (m) => !otherCategoryAddonIds.includes(m.addon_id)
      );

      if (!exists) {
        selectedModifiers = [
          ...filtered,
          { addon_id: addon.id, name: addon.name, price: Number(addon.price) },
        ];
      } else if (!category.is_required) {
        // Can unselect if not required
        selectedModifiers = filtered;
      }
    } else {
      // MULTIPLE selection
      if (exists) {
        selectedModifiers = selectedModifiers.filter((m) => m.addon_id !== addon.id);
      } else {
        // Check max selection limit if present
        const currentCategorySelectedCount = selectedModifiers.filter((m) =>
          category.addons?.some((a) => a.id === m.addon_id)
        ).length;

        if (category.max_selection && currentCategorySelectedCount >= category.max_selection) {
          validationError = `Maksimal pilihan untuk ${category.name} adalah ${category.max_selection}.`;
          return;
        }

        selectedModifiers = [
          ...selectedModifiers,
          { addon_id: addon.id, name: addon.name, price: Number(addon.price) },
        ];
      }
    }
  }

  function handleSave() {
    // Validate required categories
    for (const cat of applicableCategories) {
      if (cat.is_required) {
        const catAddonIds = cat.addons?.map((a) => a.id) || [];
        const hasSelection = selectedModifiers.some((m) => catAddonIds.includes(m.addon_id));
        if (!hasSelection) {
          validationError = `Kategori "${cat.name}" wajib dipilih minimal satu varian.`;
          return;
        }
      }
    }

    onConfirm({
      modifiers: selectedModifiers,
      notes: notes.trim(),
      unit_price: calculatedUnitPrice,
    });
    onClose();
  }
</script>

{#if isOpen && product}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#17171c]/40 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in fade-in zoom-in-95 flex max-h-[92vh] w-full max-w-lg flex-col rounded-[22px] border border-[#d9d9dd] bg-white shadow-2xl"
    >
      <!-- Header Modal -->
      <div class="flex items-center justify-between border-b border-[#d9d9dd] p-5 pb-4">
        <div class="flex items-center gap-2.5">
          <div class="flex size-9 items-center justify-center rounded-xl bg-zinc-900 text-white">
            <Layers class="size-5" />
          </div>
          <div>
            <h2 class="text-sm font-bold text-[#212121]">Kustomisasi {product.name}</h2>
            <p class="font-mono text-xs text-zinc-500">
              Harga Dasar: {formatCurrency(product.base_price)}
            </p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer p-1 text-[#93939f] hover:text-[#212121]"
        >
          <X class="size-5" />
        </button>
      </div>

      {#if validationError}
        <div
          class="mx-5 mt-4 flex items-center gap-2 rounded-xl border border-[#ffad9b] bg-[#ffad9b]/15 p-3 text-xs text-[#b30000]"
        >
          <AlertCircle class="size-4 shrink-0" />
          <span>{validationError}</span>
        </div>
      {/if}

      <!-- Modifiers & Addons Options Container -->
      <div class="flex-1 space-y-5 overflow-y-auto p-5">
        {#if applicableCategories.length === 0}
          <div class="py-8 text-center text-xs text-zinc-400">
            Tidak ada opsi modifier / add-on tambahan untuk produk ini.
          </div>
        {:else}
          {#each applicableCategories as cat (cat.id)}
            <div class="space-y-2 rounded-xl border border-zinc-200 bg-zinc-50/50 p-3.5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold text-zinc-900">{cat.name}</span>
                  {#if cat.is_required}
                    <span class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700">
                      Wajib
                    </span>
                  {:else}
                    <span class="rounded bg-zinc-200 px-1.5 py-0.5 text-[10px] font-medium text-zinc-600">
                      Opsional
                    </span>
                  {/if}
                </div>
                <span class="text-[10px] text-zinc-400">
                  {cat.selection_type === 'SINGLE' ? 'Pilih 1' : 'Bisa Banyak'}
                </span>
              </div>

              <!-- Options List -->
              <div class="grid grid-cols-2 gap-2 pt-1">
                {#each cat.addons || [] as addon (addon.id)}
                  {@const isSelected = isAddonSelected(addon.id)}
                  <button
                    type="button"
                    onclick={() => handleToggleAddon(cat, addon)}
                    class={`flex cursor-pointer items-center justify-between rounded-xl border p-2.5 text-left transition-all ${
                      isSelected
                        ? 'border-zinc-900 bg-zinc-900 text-white shadow-xs'
                        : 'border-zinc-200 bg-white text-zinc-800 hover:bg-zinc-100'
                    }`}
                  >
                    <div class="min-w-0 pr-1">
                      <div class="truncate text-xs font-semibold">{addon.name}</div>
                      <div
                        class={`font-mono text-[10px] ${
                          isSelected ? 'text-zinc-300' : 'text-zinc-500'
                        }`}
                      >
                        {addon.price > 0 ? `+${formatCurrency(addon.price)}` : 'Gratis'}
                      </div>
                    </div>
                    <div
                      class={`flex size-4 shrink-0 items-center justify-center rounded-full border ${
                        isSelected ? 'border-white bg-white text-zinc-900' : 'border-zinc-300'
                      }`}
                    >
                      {#if isSelected}
                        <Check class="size-3" />
                      {/if}
                    </div>
                  </button>
                {/each}
              </div>
            </div>
          {/each}
        {/if}

        <!-- Instruksi Khusus / Notes -->
        <div class="space-y-1.5 pt-1">
          <label for="modifier-notes" class="block text-xs font-bold text-zinc-900">
            Catatan Khusus Pesanan (Opsional):
          </label>
          <input
            id="modifier-notes"
            type="text"
            bind:value={notes}
            placeholder="Contoh: Less ice, no sugar, pisahkan sedotan..."
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3.5 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:border-zinc-900 focus:outline-hidden"
          />
        </div>
      </div>

      <!-- Footer Modal -->
      <div class="flex items-center justify-between border-t border-zinc-200 bg-zinc-50/90 p-4">
        <div>
          <span class="text-[11px] text-zinc-500">Harga Produk Disesuaikan:</span>
          <div class="font-mono text-base font-bold text-zinc-900">
            {formatCurrency(calculatedUnitPrice)}
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            onclick={onClose}
            class="cursor-pointer rounded-full border border-zinc-300 bg-white px-4 py-2 text-xs font-semibold text-zinc-700 transition-all hover:bg-zinc-100"
          >
            Batal
          </button>

          <button
            type="button"
            onclick={handleSave}
            class="flex cursor-pointer items-center gap-1.5 rounded-full bg-zinc-900 px-5 py-2 text-xs font-bold text-white shadow-sm transition-all hover:bg-black"
          >
            <Check class="size-4" />
            <span>Simpan Pilihan</span>
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}
