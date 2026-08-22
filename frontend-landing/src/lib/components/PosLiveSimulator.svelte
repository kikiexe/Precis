<script lang="ts">
  import { ShoppingBag, Wifi, WifiOff, RefreshCw, Printer, Plus, Trash2, CheckCircle2 } from 'lucide-svelte';

  interface CartItem {
    id: string;
    name: string;
    price: number;
    qty: number;
  }

  const menuCatalog = [
    { id: 'm1', name: 'Es Kopi Susu Gula Aren', price: 20000, category: 'Signature Coffee' },
    { id: 'm2', name: 'Iced Americano 12oz', price: 22000, category: 'Black Coffee' },
    { id: 'm3', name: 'Caffe Latte Double Shot', price: 28000, category: 'Espresso Bar' },
    { id: 'm4', name: 'Matcha Green Tea Iced', price: 28000, category: 'Non-Coffee' },
    { id: 'm5', name: 'Butter Croissant Premium', price: 24000, category: 'Bakery & Pastry' },
    { id: 'm6', name: 'Choco Lava Toast', price: 26000, category: 'Snack & Bites' },
  ];

  let cart = $state<CartItem[]>([
    { id: 'm1', name: 'Es Kopi Susu Gula Aren', price: 20000, qty: 2 },
    { id: 'm5', name: 'Butter Croissant Premium', price: 24000, qty: 1 }
  ]);

  let isOnline = $state(false); // Default offline to showcase offline resiliency
  let savedOrders = $state<Array<{ id: string; total: number; time: string; status: 'PENDING' | 'SYNCED' }>>([
    { id: 'NOTA-0841', total: 44000, time: '14:10:22', status: 'PENDING' },
    { id: 'NOTA-0842', total: 64000, time: '14:15:45', status: 'PENDING' }
  ]);
  let notification = $state<string | null>(null);

  let totalAmount = $derived(cart.reduce((sum, item) => sum + item.price * item.qty, 0));

  function addItem(item: typeof menuCatalog[0]) {
    const found = cart.find((c) => c.id === item.id);
    if (found) {
      found.qty += 1;
    } else {
      cart.push({ id: item.id, name: item.name, price: item.price, qty: 1 });
    }
  }

  function removeItem(id: string) {
    cart = cart.filter((c) => c.id !== id);
  }

  function handleCheckout() {
    if (cart.length === 0) return;

    const orderNum = `NOTA-${Math.floor(1000 + Math.random() * 9000)}`;
    const newOrder = {
      id: orderNum,
      total: totalAmount,
      time: new Date().toLocaleTimeString('id-ID'),
      status: isOnline ? ('SYNCED' as const) : ('PENDING' as const)
    };

    savedOrders.unshift(newOrder);
    cart = [];
    notification = `Pesanan ${orderNum} berhasil diproses dan struk kasir tercetak! ${
      isOnline ? 'Data langsung tersimpan di cloud.' : 'Data tersimpan aman di database lokal tablet (tanpa internet).'
    }`;

    setTimeout(() => {
      notification = null;
    }, 4500);
  }

  function triggerSync() {
    isOnline = true;
    savedOrders = savedOrders.map((o) => ({ ...o, status: 'SYNCED' }));
    notification = 'Koneksi kembali normal: Seluruh antrean pesanan offline otomatis tersinkronisasi!';
    setTimeout(() => {
      notification = null;
    }, 4500);
  }
</script>

<section id="pos" class="bg-white py-20 lg:py-28 border-b border-[#e0e0e0]">
  <div class="max-w-[1584px] mx-auto px-4 lg:px-8">
    <div class="max-w-3xl mb-16">
      <span class="text-sm text-[#525252] block mb-4 font-mono">
        Simulasi Kasir POS Layar Sentuh
      </span>
      <h2 class="font-display-lg text-[#161616] tracking-tight mb-6">
        Kasir tetap melayani pelanggan saat internet mati.
      </h2>
      <p class="font-subhead text-[#525252]">
        Uji coba langsung antarmuka kasir Précis di bawah ini. Ubah status koneksi ke mode Offline untuk membuktikan bahwa transaksi dan cetak struk tetap bekerja 100% normal.
      </p>
    </div>

    <!-- Interactive Simulator Box -->
    <div class="border border-[#e0e0e0] bg-[#f4f4f4] p-6 lg:p-8">
      <!-- Top Control Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 mb-6 border-b border-[#e0e0e0] bg-white p-4">
        <div class="flex items-center gap-3">
          <ShoppingBag class="w-5 h-5 text-[#0f62fe]" />
          <div>
            <p class="text-sm font-semibold text-[#161616]">Terminal Kasir Toko: Outlet Sleman</p>
            <p class="text-xs text-[#525252]">Status Tablet: Mode Kasir Aktif • Siap Melayani</p>
          </div>
        </div>

        <!-- Connection Toggle Button -->
        <div class="flex items-center gap-3">
          <span class="text-xs font-mono text-[#525252]">Simulasi Koneksi:</span>
          <button
            type="button"
            onclick={() => (isOnline = !isOnline)}
            class={`px-3 py-1.5 text-xs font-mono font-medium flex items-center gap-1.5 border transition-colors ${
              isOnline
                ? 'bg-[#24a148] text-white border-[#24a148]'
                : 'bg-[#da1e28] text-white border-[#da1e28]'
            }`}
          >
            {#if isOnline}
              <Wifi class="w-3.5 h-3.5" />
              ONLINE (Terhubung Cloud)
            {:else}
              <WifiOff class="w-3.5 h-3.5" />
              OFFLINE (Mode Lokal Aktif)
            {/if}
          </button>

          {#if !isOnline && savedOrders.some((o) => o.status === 'PENDING')}
            <button
              type="button"
              onclick={triggerSync}
              class="px-3 py-1.5 text-xs font-mono bg-[#0f62fe] text-white hover:bg-[#0050e6] flex items-center gap-1.5"
            >
              <RefreshCw class="w-3.5 h-3.5" />
              Simulasi Internet Nyala &amp; Sync
            </button>
          {/if}
        </div>
      </div>

      {#if notification}
        <div class="mb-6 p-4 bg-[#24a148]/10 border border-[#24a148] text-[#161616] text-xs font-mono flex items-center gap-2">
          <CheckCircle2 class="w-4 h-4 text-[#24a148] shrink-0" />
          <span>{notification}</span>
        </div>
      {/if}

      <!-- Grid Catalog & Cart -->
      <div class="grid lg:grid-cols-12 gap-6 items-start">
        <!-- Catalog Items -->
        <div class="lg:col-span-7 bg-white border border-[#e0e0e0] p-6">
          <p class="text-xs font-mono text-[#8c8c8c] uppercase tracking-wider mb-4">Pilih Menu Kasir</p>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            {#each menuCatalog as item}
              <button
                type="button"
                onclick={() => addItem(item)}
                class="p-4 bg-white border border-[#e0e0e0] hover:border-[#0f62fe] hover:bg-[#f4f4f4] text-left transition-all group flex flex-col justify-between min-h-[105px]"
              >
                <div>
                  <span class="text-[10px] font-mono text-[#8c8c8c] block">{item.category}</span>
                  <span class="text-sm font-medium text-[#161616] block mt-1">{item.name}</span>
                </div>
                <div class="flex items-center justify-between mt-3 pt-2 border-t border-[#f4f4f4]">
                  <span class="text-xs font-mono text-[#0f62fe] font-semibold">Rp {item.price.toLocaleString('id-ID')}</span>
                  <Plus class="w-3.5 h-3.5 text-[#525252] group-hover:text-[#0f62fe]" />
                </div>
              </button>
            {/each}
          </div>
        </div>

        <!-- Cart Sidebar -->
        <div class="lg:col-span-5 space-y-6">
          <!-- Cart Box -->
          <div class="bg-white border border-[#e0e0e0] p-6">
            <p class="text-xs font-mono text-[#8c8c8c] uppercase tracking-wider mb-4">Keranjang Transaksi</p>

            {#if cart.length === 0}
              <p class="text-xs text-[#8c8c8c] italic py-8 text-center">Keranjang kosong. Silakan klik menu di samping.</p>
            {:else}
              <div class="space-y-3 mb-6 max-h-48 overflow-y-auto">
                {#each cart as item}
                  <div class="flex items-center justify-between text-xs pb-2 border-b border-[#f4f4f4]">
                    <div>
                      <p class="font-medium text-[#161616]">{item.name}</p>
                      <p class="text-[#8c8c8c] font-mono">{item.qty}x @ Rp {item.price.toLocaleString('id-ID')}</p>
                    </div>
                    <div class="flex items-center gap-3">
                      <span class="font-mono font-medium text-[#161616]">
                        Rp {(item.price * item.qty).toLocaleString('id-ID')}
                      </span>
                      <button
                        type="button"
                        onclick={() => removeItem(item.id)}
                        class="text-[#da1e28] hover:opacity-75 p-1"
                        aria-label="Hapus item pesanan"
                      >
                        <Trash2 class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </div>
                {/each}
              </div>

              <!-- Total & Pay -->
              <div class="pt-4 border-t border-[#e0e0e0] flex items-center justify-between mb-4">
                <span class="text-xs font-mono text-[#525252]">Total Pembayaran:</span>
                <span class="text-base font-mono font-bold text-[#161616]">
                  Rp {totalAmount.toLocaleString('id-ID')}
                </span>
              </div>

              <button
                type="button"
                onclick={handleCheckout}
                class="w-full py-3.5 text-xs font-semibold text-white bg-[#0f62fe] hover:bg-[#0050e6] flex items-center justify-center gap-2"
              >
                <Printer class="w-4 h-4" />
                Bayar &amp; Cetak Struk ({isOnline ? 'Online Cloud' : 'Offline Mode'})
              </button>
            {/if}
          </div>

          <!-- Offline Queue Store Box -->
          <div class="bg-white border border-[#e0e0e0] p-6">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-mono text-[#8c8c8c] uppercase">Riwayat &amp; Antrean Pesanan</span>
              <span class="text-xs font-mono text-[#525252]">{savedOrders.length} Transaksi</span>
            </div>

            <div class="space-y-2">
              {#each savedOrders as order}
                <div class="flex items-center justify-between p-2.5 bg-[#f4f4f4] text-xs font-mono border border-[#e0e0e0]">
                  <div>
                    <span class="font-bold text-[#161616]">{order.id}</span>
                    <span class="text-[#8c8c8c] text-[11px] block">{order.time}</span>
                  </div>
                  <div class="text-right">
                    <span class="block text-[#161616]">Rp {order.total.toLocaleString('id-ID')}</span>
                    {#if order.status === 'PENDING'}
                      <span class="text-[#da1e28] text-[10px]">Tersimpan di Tablet Lokal</span>
                    {:else}
                      <span class="text-[#24a148] text-[10px]">Tersinkronisasi Lengkap</span>
                    {/if}
                  </div>
                </div>
              {/each}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
