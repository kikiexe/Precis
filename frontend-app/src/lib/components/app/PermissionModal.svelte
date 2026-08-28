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
  import { permissionService, type AppPermissionsStatus } from '../../services/permission-service';

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
  <div
    class="fixed inset-0 z-50 flex items-end justify-center bg-[#17171c]/60 p-3 font-sans backdrop-blur-xs sm:items-center sm:p-4"
  >
    <div
      class="relative w-full max-w-sm space-y-4 rounded-3xl border border-[#e5e5ea] bg-white p-5 shadow-2xl sm:max-w-md sm:p-6"
    >
      <!-- Compact Header -->
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-1">
        <div class="flex items-center gap-2.5">
          <div
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-[#bfdbfe] bg-[#eff6ff] text-[#2563eb]"
          >
            <ShieldCheck class="h-4 w-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-[#17171c]">Izin Akses Presensi</h3>
            <p class="text-[11px] text-[#8e8e93]">Kamera &amp; Lokasi diperlukan untuk absensi</p>
          </div>
        </div>

        <button
          type="button"
          onclick={handleDismiss}
          class="cursor-pointer rounded-full p-1.5 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
          title="Tutup"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <!-- Permission Compact List -->
      <div class="space-y-2 text-xs">
        <!-- 1. Kamera (Wajib) -->
        <div
          class={`flex items-center justify-between gap-2.5 rounded-2xl border p-3 transition-all ${
            permissions.camera === 'granted'
              ? 'border-[#bbf7d0] bg-[#f0fdf4]'
              : permissions.camera === 'denied'
                ? 'border-[#fecaca] bg-[#fef2f2]'
                : 'border-[#e5e5ea] bg-[#fafafc]'
          }`}
        >
          <div class="flex min-w-0 items-center gap-2.5">
            <div
              class={`flex h-8 w-8 shrink-0 items-center justify-center rounded-xl ${
                permissions.camera === 'granted'
                  ? 'bg-[#dcfce7] text-[#16a34a]'
                  : permissions.camera === 'denied'
                    ? 'bg-[#fee2e2] text-[#dc2626]'
                    : 'bg-[#f0f0f3] text-[#17171c]'
              }`}
            >
              <Camera class="h-3.5 w-3.5" />
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5">
                <span class="truncate font-bold text-[#17171c]">Kamera Selfie</span>
                <span
                  class="py-0.2 rounded-full bg-[#f4f4f6] px-1.5 text-[9.5px] font-bold text-[#686873]"
                  >Wajib</span
                >
              </div>
              <span class="block truncate text-[10.5px] text-[#8e8e93]"
                >Presensi selfie anti-spoofing</span
              >
            </div>
          </div>

          <div class="shrink-0">
            {#if permissions.camera === 'granted'}
              <span
                class="inline-flex items-center gap-1 rounded-full bg-[#dcfce7] px-2.5 py-1 text-[11px] font-bold text-[#15803d]"
              >
                <Check class="h-3 w-3" />
                <span>Aktif</span>
              </span>
            {:else if permissions.camera === 'denied'}
              <button
                type="button"
                onclick={() => handleRequest('camera')}
                class="inline-flex cursor-pointer items-center gap-1 rounded-full bg-[#fee2e2] px-2.5 py-1 text-[10.5px] font-bold text-[#b91c1c] transition-all hover:bg-[#fecaca]"
                title="Klik untuk mencoba lagi atau buka pengaturan browser"
              >
                <AlertCircle class="h-3 w-3" />
                <span>Ditolak</span>
              </button>
            {:else}
              <button
                type="button"
                onclick={() => handleRequest('camera')}
                disabled={requestingKey === 'camera'}
                class="flex cursor-pointer items-center gap-1 rounded-full bg-[#17171c] px-3 py-1 text-[11px] font-bold text-white transition-all hover:bg-black disabled:opacity-50"
              >
                {#if requestingKey === 'camera'}
                  <RefreshCw class="h-2.5 w-2.5 animate-spin" />
                  <span>...</span>
                {:else}
                  <span>Izinkan</span>
                  <ChevronRight class="h-2.5 w-2.5" />
                {/if}
              </button>
            {/if}
          </div>
        </div>

        <!-- 2. Lokasi / GPS (Wajib) -->
        <div
          class={`flex items-center justify-between gap-2.5 rounded-2xl border p-3 transition-all ${
            permissions.geolocation === 'granted'
              ? 'border-[#bbf7d0] bg-[#f0fdf4]'
              : permissions.geolocation === 'denied'
                ? 'border-[#fecaca] bg-[#fef2f2]'
                : 'border-[#e5e5ea] bg-[#fafafc]'
          }`}
        >
          <div class="flex min-w-0 items-center gap-2.5">
            <div
              class={`flex h-8 w-8 shrink-0 items-center justify-center rounded-xl ${
                permissions.geolocation === 'granted'
                  ? 'bg-[#dcfce7] text-[#16a34a]'
                  : permissions.geolocation === 'denied'
                    ? 'bg-[#fee2e2] text-[#dc2626]'
                    : 'bg-[#f0f0f3] text-[#17171c]'
              }`}
            >
              <MapPin class="h-3.5 w-3.5" />
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5">
                <span class="truncate font-bold text-[#17171c]">Lokasi GPS</span>
                <span
                  class="py-0.2 rounded-full bg-[#f4f4f6] px-1.5 text-[9.5px] font-bold text-[#686873]"
                  >Wajib</span
                >
              </div>
              <span class="block truncate text-[10.5px] text-[#8e8e93]"
                >Validasi radius outlet kedai</span
              >
            </div>
          </div>

          <div class="shrink-0">
            {#if permissions.geolocation === 'granted'}
              <span
                class="inline-flex items-center gap-1 rounded-full bg-[#dcfce7] px-2.5 py-1 text-[11px] font-bold text-[#15803d]"
              >
                <Check class="h-3 w-3" />
                <span>Aktif</span>
              </span>
            {:else if permissions.geolocation === 'denied'}
              <button
                type="button"
                onclick={() => handleRequest('geolocation')}
                class="inline-flex cursor-pointer items-center gap-1 rounded-full bg-[#fee2e2] px-2.5 py-1 text-[10.5px] font-bold text-[#b91c1c] transition-all hover:bg-[#fecaca]"
                title="Klik untuk mencoba lagi atau buka pengaturan browser"
              >
                <AlertCircle class="h-3 w-3" />
                <span>Ditolak</span>
              </button>
            {:else}
              <button
                type="button"
                onclick={() => handleRequest('geolocation')}
                disabled={requestingKey === 'geolocation'}
                class="flex cursor-pointer items-center gap-1 rounded-full bg-[#17171c] px-3 py-1 text-[11px] font-bold text-white transition-all hover:bg-black disabled:opacity-50"
              >
                {#if requestingKey === 'geolocation'}
                  <RefreshCw class="h-2.5 w-2.5 animate-spin" />
                  <span>...</span>
                {:else}
                  <span>Izinkan</span>
                  <ChevronRight class="h-2.5 w-2.5" />
                {/if}
              </button>
            {/if}
          </div>
        </div>

        <!-- 3. Notifikasi (Opsional) -->
        <div
          class={`flex items-center justify-between gap-2.5 rounded-2xl border p-3 transition-all ${
            permissions.notification === 'granted'
              ? 'border-[#bbf7d0] bg-[#f0fdf4]'
              : permissions.notification === 'denied'
                ? 'border-[#e5e5ea] bg-[#fafafc] opacity-80'
                : 'border-[#e5e5ea] bg-[#fafafc]'
          }`}
        >
          <div class="flex min-w-0 items-center gap-2.5">
            <div
              class={`flex h-8 w-8 shrink-0 items-center justify-center rounded-xl ${
                permissions.notification === 'granted'
                  ? 'bg-[#dcfce7] text-[#16a34a]'
                  : permissions.notification === 'denied'
                    ? 'bg-[#f0f0f3] text-[#8e8e93]'
                    : 'bg-[#f0f0f3] text-[#17171c]'
              }`}
            >
              <Bell class="h-3.5 w-3.5" />
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5">
                <span class="truncate font-bold text-[#17171c]">Notifikasi</span>
                <span
                  class="py-0.2 rounded-full bg-[#f4f4f6] px-1.5 text-[9.5px] font-medium text-[#8e8e93]"
                  >Opsional</span
                >
              </div>
              <span class="block truncate text-[10.5px] text-[#8e8e93]"
                >Pengingat jadwal &amp; kasbon</span
              >
            </div>
          </div>

          <div class="shrink-0">
            {#if permissions.notification === 'granted'}
              <span
                class="inline-flex items-center gap-1 rounded-full bg-[#dcfce7] px-2.5 py-1 text-[11px] font-bold text-[#15803d]"
              >
                <Check class="h-3 w-3" />
                <span>Aktif</span>
              </span>
            {:else if permissions.notification === 'denied'}
              <span
                class="inline-flex items-center gap-1 rounded-full bg-[#f4f4f6] px-2 py-0.5 text-[10.5px] font-semibold text-[#8e8e93]"
              >
                <span>Nonaktif</span>
              </span>
            {:else if permissions.notification === 'unsupported'}
              <span
                class="inline-flex items-center gap-1 rounded-full bg-[#f4f4f6] px-2 py-0.5 text-[10.5px] font-semibold text-[#8e8e93]"
              >
                <span>Tidak Didukung</span>
              </span>
            {:else}
              <button
                type="button"
                onclick={() => handleRequest('notification')}
                disabled={requestingKey === 'notification'}
                class="flex cursor-pointer items-center gap-1 rounded-full bg-[#f4f4f6] px-3 py-1 text-[11px] font-bold text-[#17171c] transition-all hover:bg-[#e5e5ea] disabled:opacity-50"
              >
                {#if requestingKey === 'notification'}
                  <RefreshCw class="h-2.5 w-2.5 animate-spin" />
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
      <div class="flex items-center justify-between gap-2 pt-1">
        <button
          type="button"
          onclick={handleDismiss}
          class="cursor-pointer rounded-full px-4 py-2 text-xs font-semibold text-[#8e8e93] transition-all hover:text-[#17171c]"
        >
          Lewati
        </button>

        {#if permissions.requiredGranted}
          <button
            type="button"
            onclick={handleDismiss}
            class="flex cursor-pointer items-center gap-1.5 rounded-full bg-[#15803d] px-5 py-2 text-xs font-bold text-white shadow-2xs transition-all hover:bg-[#166534]"
          >
            <Check class="h-3.5 w-3.5" />
            <span>Izin Siap &bull; Lanjutkan</span>
          </button>
        {:else}
          <button
            type="button"
            onclick={handleDismiss}
            class="cursor-pointer rounded-full bg-[#17171c] px-5 py-2 text-xs font-bold text-white shadow-2xs transition-all hover:bg-black"
          >
            <span>Tutup &amp; Lanjutkan</span>
          </button>
        {/if}
      </div>
    </div>
  </div>
{/if}
