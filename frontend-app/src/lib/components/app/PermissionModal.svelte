<script lang="ts">
  import {
    Camera,
    MapPin,
    Bell,
    Check,
    AlertCircle,
    ShieldCheck,
    ChevronRight,
    RefreshCw,
    X,
  } from 'lucide-svelte';
  import {
    permissionService,
    type AppPermissionsStatus,
  } from '../../services/permission-service';

  interface Props {
    isOpen: boolean;
    onClose: () => void;
  }

  let { isOpen, onClose }: Props = $props();

  let permissions = $state<AppPermissionsStatus>({
    camera: 'prompt',
    geolocation: 'prompt',
    notification: 'prompt',
    allGranted: false,
    requiredGranted: false,
  });

  let requestingKey = $state<string | null>(null);

  $effect(() => {
    if (isOpen) {
      loadStatus();
    }
  });

  async function loadStatus() {
    permissions = await permissionService.checkPermissions();
  }

  async function handleRequest(type: 'camera' | 'geolocation' | 'notification') {
    requestingKey = type;
    try {
      if (type === 'camera') {
        const res = await permissionService.requestCamera();
        permissions.camera = res;
      } else if (type === 'geolocation') {
        const res = await permissionService.requestGeolocation();
        permissions.geolocation = res;
      } else if (type === 'notification') {
        const res = await permissionService.requestNotification();
        permissions.notification = res;
      }
      
      const fresh = await permissionService.checkPermissions();
      permissions = fresh;

      if (fresh.requiredGranted) {
        permissionService.setDismissed();
      }
    } finally {
      requestingKey = null;
    }
  }

  function handleDismiss() {
    permissionService.setDismissed();
    onClose();
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-3 sm:p-4 bg-[#17171c]/60 backdrop-blur-xs font-sans">
    <div class="w-full max-w-sm sm:max-w-md bg-white border border-[#e5e5ea] rounded-3xl p-5 sm:p-6 space-y-4 shadow-2xl relative">
      
      <!-- Compact Header -->
      <div class="flex items-center justify-between pb-1 border-b border-[#f2f2f4]">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-[#eff6ff] border border-[#bfdbfe] flex items-center justify-center text-[#2563eb] shrink-0">
            <ShieldCheck class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-[#17171c]">Izin Akses Presensi</h3>
            <p class="text-[11px] text-[#8e8e93]">Kamera &amp; Lokasi diperlukan untuk absensi</p>
          </div>
        </div>
        
        <button
          type="button"
          onclick={handleDismiss}
          class="p-1.5 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-full transition-all cursor-pointer"
          title="Tutup"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Permission Compact List -->
      <div class="space-y-2 text-xs">
        
        <!-- 1. Kamera (Wajib) -->
        <div class={`p-3 rounded-2xl border transition-all flex items-center justify-between gap-2.5 ${
          permissions.camera === 'granted'
            ? 'bg-[#f0fdf4] border-[#bbf7d0]'
            : permissions.camera === 'denied'
            ? 'bg-[#fef2f2] border-[#fecaca]'
            : 'bg-[#fafafc] border-[#e5e5ea]'
        }`}>
          <div class="flex items-center gap-2.5 min-w-0">
            <div class={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 ${
              permissions.camera === 'granted'
                ? 'bg-[#dcfce7] text-[#16a34a]'
                : permissions.camera === 'denied'
                ? 'bg-[#fee2e2] text-[#dc2626]'
                : 'bg-[#f0f0f3] text-[#17171c]'
            }`}>
              <Camera class="w-3.5 h-3.5" />
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5">
                <span class="font-bold text-[#17171c] truncate">Kamera Selfie</span>
                <span class="text-[9.5px] font-bold px-1.5 py-0.2 rounded-full bg-[#f4f4f6] text-[#686873]">Wajib</span>
              </div>
              <span class="text-[10.5px] text-[#8e8e93] block truncate">Presensi selfie anti-spoofing</span>
            </div>
          </div>

          <div class="shrink-0">
            {#if permissions.camera === 'granted'}
              <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#dcfce7] text-[#15803d] rounded-full text-[11px] font-bold">
                <Check class="w-3 h-3" />
                <span>Aktif</span>
              </span>
            {:else if permissions.camera === 'denied'}
              <button
                type="button"
                onclick={() => handleRequest('camera')}
                class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#fee2e2] hover:bg-[#fecaca] text-[#b91c1c] rounded-full text-[10.5px] font-bold cursor-pointer transition-all"
                title="Klik untuk mencoba lagi atau buka pengaturan browser"
              >
                <AlertCircle class="w-3 h-3" />
                <span>Ditolak</span>
              </button>
            {:else}
              <button
                type="button"
                onclick={() => handleRequest('camera')}
                disabled={requestingKey === 'camera'}
                class="px-3 py-1 bg-[#17171c] hover:bg-black text-white rounded-full text-[11px] font-bold flex items-center gap-1 cursor-pointer transition-all disabled:opacity-50"
              >
                {#if requestingKey === 'camera'}
                  <RefreshCw class="w-2.5 h-2.5 animate-spin" />
                  <span>...</span>
                {:else}
                  <span>Izinkan</span>
                  <ChevronRight class="w-2.5 h-2.5" />
                {/if}
              </button>
            {/if}
          </div>
        </div>

        <!-- 2. Lokasi / GPS (Wajib) -->
        <div class={`p-3 rounded-2xl border transition-all flex items-center justify-between gap-2.5 ${
          permissions.geolocation === 'granted'
            ? 'bg-[#f0fdf4] border-[#bbf7d0]'
            : permissions.geolocation === 'denied'
            ? 'bg-[#fef2f2] border-[#fecaca]'
            : 'bg-[#fafafc] border-[#e5e5ea]'
        }`}>
          <div class="flex items-center gap-2.5 min-w-0">
            <div class={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 ${
              permissions.geolocation === 'granted'
                ? 'bg-[#dcfce7] text-[#16a34a]'
                : permissions.geolocation === 'denied'
                ? 'bg-[#fee2e2] text-[#dc2626]'
                : 'bg-[#f0f0f3] text-[#17171c]'
            }`}>
              <MapPin class="w-3.5 h-3.5" />
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5">
                <span class="font-bold text-[#17171c] truncate">Lokasi GPS</span>
                <span class="text-[9.5px] font-bold px-1.5 py-0.2 rounded-full bg-[#f4f4f6] text-[#686873]">Wajib</span>
              </div>
              <span class="text-[10.5px] text-[#8e8e93] block truncate">Validasi radius outlet kedai</span>
            </div>
          </div>

          <div class="shrink-0">
            {#if permissions.geolocation === 'granted'}
              <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#dcfce7] text-[#15803d] rounded-full text-[11px] font-bold">
                <Check class="w-3 h-3" />
                <span>Aktif</span>
              </span>
            {:else if permissions.geolocation === 'denied'}
              <button
                type="button"
                onclick={() => handleRequest('geolocation')}
                class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#fee2e2] hover:bg-[#fecaca] text-[#b91c1c] rounded-full text-[10.5px] font-bold cursor-pointer transition-all"
                title="Klik untuk mencoba lagi atau buka pengaturan browser"
              >
                <AlertCircle class="w-3 h-3" />
                <span>Ditolak</span>
              </button>
            {:else}
              <button
                type="button"
                onclick={() => handleRequest('geolocation')}
                disabled={requestingKey === 'geolocation'}
                class="px-3 py-1 bg-[#17171c] hover:bg-black text-white rounded-full text-[11px] font-bold flex items-center gap-1 cursor-pointer transition-all disabled:opacity-50"
              >
                {#if requestingKey === 'geolocation'}
                  <RefreshCw class="w-2.5 h-2.5 animate-spin" />
                  <span>...</span>
                {:else}
                  <span>Izinkan</span>
                  <ChevronRight class="w-2.5 h-2.5" />
                {/if}
              </button>
            {/if}
          </div>
        </div>

        <!-- 3. Notifikasi (Opsional) -->
        <div class={`p-3 rounded-2xl border transition-all flex items-center justify-between gap-2.5 ${
          permissions.notification === 'granted'
            ? 'bg-[#f0fdf4] border-[#bbf7d0]'
            : permissions.notification === 'denied'
            ? 'bg-[#fafafc] border-[#e5e5ea] opacity-80'
            : 'bg-[#fafafc] border-[#e5e5ea]'
        }`}>
          <div class="flex items-center gap-2.5 min-w-0">
            <div class={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 ${
              permissions.notification === 'granted'
                ? 'bg-[#dcfce7] text-[#16a34a]'
                : permissions.notification === 'denied'
                ? 'bg-[#f0f0f3] text-[#8e8e93]'
                : 'bg-[#f0f0f3] text-[#17171c]'
            }`}>
              <Bell class="w-3.5 h-3.5" />
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5">
                <span class="font-bold text-[#17171c] truncate">Notifikasi</span>
                <span class="text-[9.5px] font-medium px-1.5 py-0.2 rounded-full bg-[#f4f4f6] text-[#8e8e93]">Opsional</span>
              </div>
              <span class="text-[10.5px] text-[#8e8e93] block truncate">Pengingat jadwal &amp; kasbon</span>
            </div>
          </div>

          <div class="shrink-0">
            {#if permissions.notification === 'granted'}
              <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#dcfce7] text-[#15803d] rounded-full text-[11px] font-bold">
                <Check class="w-3 h-3" />
                <span>Aktif</span>
              </span>
            {:else if permissions.notification === 'denied'}
              <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-[#f4f4f6] text-[#8e8e93] rounded-full text-[10.5px] font-semibold">
                <span>Nonaktif</span>
              </span>
            {:else if permissions.notification === 'unsupported'}
              <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-[#f4f4f6] text-[#8e8e93] rounded-full text-[10.5px] font-semibold">
                <span>Tidak Didukung</span>
              </span>
            {:else}
              <button
                type="button"
                onclick={() => handleRequest('notification')}
                disabled={requestingKey === 'notification'}
                class="px-3 py-1 bg-[#f4f4f6] hover:bg-[#e5e5ea] text-[#17171c] rounded-full text-[11px] font-bold flex items-center gap-1 cursor-pointer transition-all disabled:opacity-50"
              >
                {#if requestingKey === 'notification'}
                  <RefreshCw class="w-2.5 h-2.5 animate-spin" />
                  <span>...</span>
                {:else}
                  <span>Izinkan</span>
                {/if}
              </button>
            {/if}
          </div>
        </div>

      </div>

      <!-- Action Footer -->
      <div class="pt-1 flex items-center justify-between gap-2">
        <button
          type="button"
          onclick={handleDismiss}
          class="px-4 py-2 text-xs text-[#8e8e93] hover:text-[#17171c] font-semibold rounded-full transition-all cursor-pointer"
        >
          Lewati
        </button>

        {#if permissions.requiredGranted}
          <button
            type="button"
            onclick={handleDismiss}
            class="px-5 py-2 bg-[#15803d] hover:bg-[#166534] text-white text-xs font-bold rounded-full transition-all cursor-pointer shadow-2xs flex items-center gap-1.5"
          >
            <Check class="w-3.5 h-3.5" />
            <span>Izin Siap &bull; Lanjutkan</span>
          </button>
        {:else}
          <button
            type="button"
            onclick={handleDismiss}
            class="px-5 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-bold rounded-full transition-all cursor-pointer shadow-2xs"
          >
            <span>Tutup &amp; Lanjutkan</span>
          </button>
        {/if}
      </div>

    </div>
  </div>
{/if}
