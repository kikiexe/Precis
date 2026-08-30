<script lang="ts">
  import { X, Check, AlertCircle, Plus, Minus, Layers } from 'lucide-svelte';
  import type { Product, AddonCategory, Addon, SelectedModifier } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';

  interface Props {
    isOpen: boolean;
    product: Product | null;
    addonCategories: AddonCategory[];
    initialModifiers?: SelectedModifier[];
    initialNotes?: string;
    initialQuantity?: number;
    onClose: () => void;
    onAddToCart: (item: {
      product: Product;
      quantity: number;
      unit_price: number;
      notes: string;
      modifiers: SelectedModifier[];
    }) => void;
  }

  let {
    isOpen = false,
    product = null,
    addonCategories = [],
    initialModifiers = [],
    initialNotes = '',
    initialQuantity = 1,
    onClose,
    onAddToCart,
  }: Props = $props();

  let quantity = $state(1);
  let notes = $state('');
  let selectedAddons = $state<Record<string, SelectedModifier[]>>({});
  let validationError = $state<string | null>(null);

  // Filter kategori addon yang terhubung ke produk ini
  let relevantCategories = $derived(
    addonCategories.filter((cat) => {
      if (!product) return false;
      const matchInProduct =
        product.addon_category_ids && product.addon_category_ids.includes(cat.id);
      const matchInCat = cat.product_ids && cat.product_ids.includes(product.id);
      // jika tidak ada mapping khusus, tapi produk punya addon_category_ids
      return matchInProduct || matchInCat;
    })
  );

  $effect(() => {
    if (isOpen && product) {
      quantity = Math.max(1, initialQuantity);
      notes = initialNotes || '';
      validationError = null;

      const initialMap: Record<string, SelectedModifier[]> = {};

      relevantCategories.forEach((cat) => {
        const activeCatAddons = cat.addons.filter((a) => a.is_active);

        // cek apakah ada initial modifier yang sesuai
        const matchingInitial = initialModifiers.filter(
          (m) =>
            m.addon_category_id === cat.id ||
            activeCatAddons.some((a) => a.id === m.addon_id)
        );

        if (matchingInitial.length > 0) {
          initialMap[cat.id] = matchingInitial;
        } else if (cat.is_required && cat.selection_type === 'SINGLE' && activeCatAddons.length > 0) {
          // default pilih item pertama untuk single required (contoh: Regular / Hot)
          initialMap[cat.id] = [
            {
              addon_id: activeCatAddons[0].id,
              addon_category_id: cat.id,
              name: activeCatAddons[0].name,
              price: activeCatAddons[0].price,
            },
          ];
        } else {
          initialMap[cat.id] = [];
        }
      });

      selectedAddons = initialMap;
    }
  });

  // Gabungkan semua addon terpilih menjadi satu flat array
  let allSelectedModifiers = $derived<SelectedModifier[]>(
    Object.values(selectedAddons).flat()
  );

  let modifierTotal = $derived(
    allSelectedModifiers.reduce((sum, mod) => sum + (mod.price || 0), 0)
  );

  let unitPrice = $derived(
    product ? product.base_price + modifierTotal : 0
  );

  let lineSubtotal = $derived(unitPrice * quantity);

  function toggleAddon(cat: AddonCategory, addon: Addon) {
    const current = selectedAddons[cat.id] || [];
    const isAlreadySelected = current.some((m) => m.addon_id === addon.id);

    if (cat.selection_type === 'SINGLE') {
      if (cat.is_required && isAlreadySelected) {
        // jika required single, tidak bisa di-unselect tanpa memilih opsi lain
        return;
      }
      if (isAlreadySelected) {
        selectedAddons[cat.id] = [];
      } else {
        selectedAddons[cat.id] = [
          {
            addon_id: addon.id,
            addon_category_id: cat.id,
            name: addon.name,
            price: addon.price,
          },
        ];
      }
    } else {
      // MULTIPLE
      if (isAlreadySelected) {
        selectedAddons[cat.id] = current.filter((m) => m.addon_id !== addon.id);
      } else {
        if (cat.max_selection > 0 && current.length >= cat.max_selection) {
          validationError = `Maksimal pilihan untuk ${cat.name} adalah ${cat.max_selection}.`;
          return;
        }
        selectedAddons[cat.id] = [
          ...current,
          {
            addon_id: addon.id,
            addon_category_id: cat.id,
            name: addon.name,
            price: addon.price,
          },
        ];
      }
    }
    validationError = null;
  }

  function handleSave() {
    if (!product) return;

    // validasi apakah semua required category sudah terisi
    for (const cat of relevantCategories) {
      const selected = selectedAddons[cat.id] || [];
      if (cat.is_required && selected.length === 0) {
        validationError = `Pilihan pada kategori "${cat.name}" wajib dipilih.`;
        return;
      }
      if (cat.min_selection > 0 && selected.length < cat.min_selection) {
        validationError = `Minimal pilih ${cat.min_selection} item pada "${cat.name}".`;
        return;
      }
    }

    onAddToCart({
      product,
      quantity,
      unit_price: unitPrice,
      notes: notes.trim(),
      modifiers: allSelectedModifiers,
    });

    onClose();
  }
</script>

{#if isOpen && product}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#17171c]/40 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in fade-in zoom-in-95 flex max-h-[90vh] w-full max-w-lg flex-col rounded-[22px] border border-[#d9d9dd] bg-white shadow-2xl"
    >
      <!-- Header Modal -->
      <div class="flex items-center justify-between border-b border-[#d9d9dd] p-5 pb-4">
        <div class="flex items-center gap-2.5">
          <div class="flex size-9 items-center justify-center rounded-xl bg-zinc-100 text-zinc-800">
            <Layers class="size-5" />
          </div>
          <div>
            <h2 class="text-sm font-bold text-[#212121]">{product.name}</h2>
            <p class="font-mono text-xs font-semibold text-zinc-500">
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

      <!-- Modifiers & Addons Selection List -->
      <div class="flex-1 space-y-6 overflow-y-auto p-5">
        {#each relevantCategories as cat (cat.id)}
          <div class="space-y-2.5">
            <!-- Category Header -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-zinc-900">{cat.name}</span>
                {#if cat.is_required}
                  <span class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700 uppercase">
                    Wajib
                  </span>
                {:else}
                  <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-600">
                    Opsional
                  </span>
                {/if}
              </div>
              <span class="text-[11px] text-zinc-400">
                {cat.selection_type === 'SINGLE' ? 'Pilih 1' : cat.max_selection > 0 ? `Maks ${cat.max_selection}` : 'Pilih banyak'}
              </span>
            </div>

            <!-- Addon Options Grid -->
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
              {#each cat.addons.filter((a) => a.is_active) as addon (addon.id)}
                {@const isSelected = (selectedAddons[cat.id] || []).some((m) => m.addon_id === addon.id)}
                <button
                  type="button"
                  onclick={() => toggleAddon(cat, addon)}
                  class={`flex cursor-pointer items-center justify-between rounded-xl border p-3 text-left transition-all ${
                    isSelected
                      ? 'border-zinc-900 bg-zinc-900 text-white shadow-xs'
                      : 'border-zinc-200 bg-zinc-50/70 text-zinc-800 hover:bg-zinc-100'
                  }`}
                >
                  <div class="flex items-center gap-2">
                    <div
                      class={`flex size-4 items-center justify-center rounded-${cat.selection_type === 'SINGLE' ? 'full' : 'md'} border ${
                        isSelected
                          ? 'border-white bg-white text-zinc-900'
                          : 'border-zinc-300 bg-white'
                      }`}
                    >
                      {#if isSelected}
                        <Check class="size-3 stroke-3" />
                      {/if}
                    </div>
                    <span class="text-xs font-semibold">{addon.name}</span>
                  </div>
                  <span
                    class={`font-mono text-xs font-bold ${
                      isSelected ? 'text-zinc-200' : 'text-zinc-600'
                    }`}
                  >
                    {addon.price > 0 ? `+${formatCurrency(addon.price)}` : 'Gratis'}
                  </span>
                </button>
              {/each}
            </div>
          </div>
        {/each}

        <!-- Catatan Barista / Khusus -->
        <div class="space-y-1.5 pt-2">
          <label for="modifier-notes" class="block text-xs font-bold text-zinc-900">
            Catatan Khusus (Opsional):
          </label>
          <input
            id="modifier-notes"
            type="text"
            bind:value={notes}
            placeholder="Contoh: Less ice 30%, less sweet 50%..."
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3.5 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:border-zinc-900 focus:outline-hidden"
          />
        </div>
      </div>

      <!-- Footer: Quantity & Add to Cart -->
      <div class="flex items-center justify-between border-t border-zinc-200 bg-zinc-50/90 p-4">
        <!-- Quantity Selector -->
        <div class="flex items-center gap-2">
          <button
            type="button"
            onclick={() => (quantity = Math.max(1, quantity - 1))}
            disabled={quantity <= 1}
            class="flex size-9 cursor-pointer items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 shadow-2xs hover:bg-zinc-100 disabled:opacity-30"
          >
            <Minus class="size-4" />
          </button>
          <span class="w-8 text-center font-mono text-sm font-bold text-zinc-900">{quantity}</span>
          <button
            type="button"
            onclick={() => (quantity += 1)}
            class="flex size-9 cursor-pointer items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 shadow-2xs hover:bg-zinc-100"
          >
            <Plus class="size-4" />
          </button>
        </div>

        <!-- Submit Button -->
        <button
          type="button"
          onclick={handleSave}
          class="flex cursor-pointer items-center gap-2 rounded-full bg-zinc-900 px-6 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-black"
        >
          <Check class="size-4" />
          <span>Simpan ({formatCurrency(lineSubtotal)})</span>
        </button>
      </div>
    </div>
  </div>
{/if}
