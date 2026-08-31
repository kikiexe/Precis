<script lang="ts">
  import {
    Check,
    Save,
    MapPin,
    LocateFixed,
    ExternalLink,
    RefreshCw,
    AlertCircle,
    Tablet,
    Key,
    Copy,
    Plus,
    Trash2,
    QrCode,
    Upload,
    Receipt,
  } from 'lucide-svelte';
  import type { BranchItem } from '../../../types/app';
  import { inventoryService } from '../../../services/inventory-service';
  import InteractiveMapPicker from './InteractiveMapPicker.svelte';
  import {
    DEFAULT_GEOFENCE_RADIUS_METERS,
    DEFAULT_LATE_PENALTY_PER_MINUTE_IDR,
    DEFAULT_OVERTIME_PAY_PER_HOUR_IDR,
    DEFAULT_MIN_OVERTIME_THRESHOLD_MINUTES,
  } from '../../../constants/defaults';
  import { normalizeMediaUrl } from '@precis/shared-utils/formatters';

  interface Props {
    branches: BranchItem[];
    onBranchUpdated?: () => void;
  }

  let { branches = [], onBranchUpdated }: Props = $props();

  let selectedBranchId = $state<string>('');
  let lastLoadedBranchId = $state<string | null>(null);

  let branchName = $state('');
  let branchLatitude = $state(-7.782914);
  let branchLongitude = $state(110.36712);
  let geofenceRadius = $state(DEFAULT_GEOFENCE_RADIUS_METERS);
  let latePenaltyRate = $state(DEFAULT_LATE_PENALTY_PER_MINUTE_IDR);
  let overtimeHourlyRate = $state(DEFAULT_OVERTIME_PAY_PER_HOUR_IDR);
  let minOvertimeThreshold = $state(DEFAULT_MIN_OVERTIME_THRESHOLD_MINUTES);
  let branchQrisUrl = $state<string | null>(null);

  // State Konfigurasi Pajak Restoran & Biaya Layanan
  let isTaxEnabled = $state(false);
  let taxName = $state('PB1');
  let taxRate = $state(10.0);
  let taxType = $state<'INCLUSIVE' | 'EXCLUSIVE'>('INCLUSIVE');
  let showTaxOnReceipt = $state(true);

  let isDetectingGps = $state(false);
  let isSavingBranch = $state(false);
  let isUploadingQris = $state(false);
  let isDraggingQris = $state(false);
  let fileInputRef = $state<HTMLInputElement | null>(null);
  let branchSuccessMsg = $state<string | null>(null);
  let branchErrorMsg = $state<string | null>(null);

  // State untuk manajemen Terminal Kasir POS & Token Pairing
  let copiedTokenId = $state<string | null>(null);
  let isCreatingTerminal = $state(false);
  let newTerminalName = $state('');
  let isShowAddTerminalModal = $state(false);
  let terminalActionMsg = $state<string | null>(null);

  let activeBranch = $derived(branches.find((b) => b.id === selectedBranchId) || branches[0]);

  // Sync initial branch data only when the branch changes, without overwriting live user inputs
  $effect(() => {
    if (branches.length > 0) {
      const active = branches.find((b) => b.id === selectedBranchId) || branches[0];
      if (active && active.id !== lastLoadedBranchId) {
        lastLoadedBranchId = active.id;
        selectedBranchId = active.id;
        branchName = active.name || '';
        branchLatitude = Number(active.lat) || -7.782914;
        branchLongitude = Number(active.lng) || 110.36712;
        geofenceRadius = active.radius_meters || DEFAULT_GEOFENCE_RADIUS_METERS;
        latePenaltyRate = active.late_penalty_per_minute ?? DEFAULT_LATE_PENALTY_PER_MINUTE_IDR;
        overtimeHourlyRate = active.overtime_pay_per_hour ?? DEFAULT_OVERTIME_PAY_PER_HOUR_IDR;
        minOvertimeThreshold =
          active.min_overtime_threshold_minutes ?? DEFAULT_MIN_OVERTIME_THRESHOLD_MINUTES;
        branchQrisUrl = active.qris_image_url || null;
        isTaxEnabled = Boolean(active.tax_enabled);
        taxName = active.tax_name || 'PB1';
        taxRate = typeof active.tax_rate === 'number' ? active.tax_rate : 10.0;
        taxType = (active.tax_type as 'INCLUSIVE' | 'EXCLUSIVE') || 'INCLUSIVE';
        showTaxOnReceipt = active.show_tax_on_receipt !== false;
      }
    }
  });

  function handleUseCurrentGPS() {
    if (typeof window === 'undefined') return;

    if (!('geolocation' in navigator)) {
      branchErrorMsg = 'Browser tidak mendukung deteksi lokasi.';
      setTimeout(() => (branchErrorMsg = null), 4000);
      return;
    }

    if (
      !window.isSecureContext &&
      window.location.hostname !== 'localhost' &&
      window.location.hostname !== '127.0.0.1'
    ) {
      branchErrorMsg =
        'Browser memblokir GPS via HTTP IP lokal. Gunakan HTTPS, localhost, atau aktifkan flags insecure origin di Chrome.';
      setTimeout(() => (branchErrorMsg = null), 6000);
      return;
    }

    isDetectingGps = true;
    branchErrorMsg = null;
    branchSuccessMsg = null;

    const onSuccess = (pos: GeolocationPosition) => {
      isDetectingGps = false;
      const lat = Number(pos.coords.latitude.toFixed(6));
      const lng = Number(pos.coords.longitude.toFixed(6));
      const acc = Math.round(pos.coords.accuracy || 0);
      branchLatitude = lat;
      branchLongitude = lng;
      branchSuccessMsg = `GPS Terkunci: ${lat}, ${lng} (Akurasi: ±${acc}m)`;
      setTimeout(() => (branchSuccessMsg = null), 4000);
    };

    const onError = (err: GeolocationPositionError) => {
      if (err.code === err.TIMEOUT || err.code === err.POSITION_UNAVAILABLE) {
        // Fallback low accuracy
        navigator.geolocation.getCurrentPosition(
          onSuccess,
          (fallbackErr) => {
            isDetectingGps = false;
            let msg = 'Gagal membaca GPS perangkat.';
            if (fallbackErr.code === fallbackErr.PERMISSION_DENIED) {
              msg = 'Izin lokasi ditolak. Buka Pengaturan HP > Aplikasi > Chrome > Izin > Lokasi.';
            } else if (fallbackErr.code === fallbackErr.POSITION_UNAVAILABLE) {
              msg = 'Sinyal lokasi tidak tersedia. Pastikan tombol Lokasi/GPS di HP sudah aktif.';
            }
            branchErrorMsg = msg;
            setTimeout(() => (branchErrorMsg = null), 5000);
          },
          { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
        );
        return;
      }

      isDetectingGps = false;
      let msg = 'Gagal membaca GPS perangkat.';
      if (err.code === err.PERMISSION_DENIED) {
        msg = 'Izin lokasi ditolak. Buka Pengaturan HP > Aplikasi > Chrome > Izin > Lokasi.';
      }
      branchErrorMsg = msg;
      setTimeout(() => (branchErrorMsg = null), 5000);
    };

    navigator.geolocation.getCurrentPosition(onSuccess, onError, {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0,
    });
  }

  async function handleSaveBranch() {
    const targetId = selectedBranchId || (branches.length > 0 ? branches[0].id : '');
    if (!targetId) {
      branchErrorMsg = 'Data cabang belum tersedia.';
      setTimeout(() => (branchErrorMsg = null), 4000);
      return;
    }

    if (!branchName.trim()) {
      branchErrorMsg = 'Nama cabang tidak boleh kosong.';
      setTimeout(() => (branchErrorMsg = null), 4000);
      return;
    }

    const latNum = Number(branchLatitude);
    const lngNum = Number(branchLongitude);

    if (isNaN(latNum) || latNum < -90 || latNum > 90) {
      branchErrorMsg = 'Nilai latitude tidak valid.';
      setTimeout(() => (branchErrorMsg = null), 4000);
      return;
    }

    if (isNaN(lngNum) || lngNum < -180 || lngNum > 180) {
      branchErrorMsg = 'Nilai longitude tidak valid.';
      setTimeout(() => (branchErrorMsg = null), 4000);
      return;
    }

    isSavingBranch = true;
    branchSuccessMsg = null;
    branchErrorMsg = null;

    try {
      const res = await inventoryService.updateBranch(targetId, {
        name: branchName.trim(),
        lat: latNum,
        lng: lngNum,
        radius_meters: Number(geofenceRadius) || DEFAULT_GEOFENCE_RADIUS_METERS,
        qris_image_url: branchQrisUrl,
        late_penalty_per_minute: Number(latePenaltyRate) || 0,
        overtime_pay_per_hour: Number(overtimeHourlyRate) || 0,
        min_overtime_threshold_minutes: Number(minOvertimeThreshold) || 0,
        tax_enabled: isTaxEnabled,
        tax_name: taxName.trim() || 'PB1',
        tax_rate: Number(taxRate) || 0,
        tax_type: taxType,
        show_tax_on_receipt: showTaxOnReceipt,
      });

      if (res) {
        branchSuccessMsg = 'Pengaturan cabang berhasil disimpan.';
        onBranchUpdated?.();
        setTimeout(() => (branchSuccessMsg = null), 3500);
      } else {
        branchErrorMsg = 'Gagal menyimpan pengaturan cabang.';
      }
    } catch (e: unknown) {
      branchErrorMsg = e instanceof Error ? e.message : 'Gagal menyimpan pengaturan cabang.';
    } finally {
      isSavingBranch = false;
    }
  }

  async function handleQrisFileSelected(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
      await processQrisUpload(file);
    }
    if (target) target.value = '';
  }

  async function handleQrisDrop(event: DragEvent) {
    event.preventDefault();
    isDraggingQris = false;
    const file = event.dataTransfer?.files?.[0];
    if (file) {
      await processQrisUpload(file);
    }
  }

  async function processQrisUpload(file: File) {
    const targetId = selectedBranchId || (branches.length > 0 ? branches[0].id : '');
    if (!targetId) {
      branchErrorMsg = 'Pilih cabang terlebih dahulu.';
      setTimeout(() => (branchErrorMsg = null), 4000);
      return;
    }

    const allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowedMimeTypes.includes(file.type)) {
      branchErrorMsg = 'Format berkas QRIS harus PNG, JPG, atau WebP.';
      setTimeout(() => (branchErrorMsg = null), 4000);
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      branchErrorMsg = 'Ukuran berkas QRIS tidak boleh melebihi 2MB.';
      setTimeout(() => (branchErrorMsg = null), 4000);
      return;
    }

    isUploadingQris = true;
    branchErrorMsg = null;
    branchSuccessMsg = null;

    try {
      const publicUrl = await inventoryService.uploadQrisImage(file);
      branchQrisUrl = publicUrl;
      await inventoryService.updateBranch(targetId, {
        qris_image_url: publicUrl,
      });
      branchSuccessMsg = 'Gambar QRIS toko berhasil disimpan.';
      onBranchUpdated?.();
      setTimeout(() => (branchSuccessMsg = null), 4000);
    } catch (err: unknown) {
      branchErrorMsg = err instanceof Error ? err.message : 'Gagal mengunggah gambar QRIS.';
      setTimeout(() => (branchErrorMsg = null), 4000);
    } finally {
      isUploadingQris = false;
    }
  }

  async function handleDeleteQris() {
    const targetId = selectedBranchId || (branches.length > 0 ? branches[0].id : '');
    if (!targetId) return;

    if (!confirm('Apakah Anda yakin ingin menghapus foto barcode QRIS cabang ini?')) {
      return;
    }

    isUploadingQris = true;
    branchErrorMsg = null;
    branchSuccessMsg = null;

    try {
      await inventoryService.updateBranch(targetId, {
        qris_image_url: null,
      });
      branchQrisUrl = null;
      branchSuccessMsg = 'Gambar QRIS toko berhasil dihapus.';
      onBranchUpdated?.();
      setTimeout(() => (branchSuccessMsg = null), 4000);
    } catch (err: unknown) {
      branchErrorMsg = err instanceof Error ? err.message : 'Gagal menghapus gambar QRIS.';
      setTimeout(() => (branchErrorMsg = null), 4000);
    } finally {
      isUploadingQris = false;
    }
  }

  async function handleCopyToken(terminalId: string, token: string) {
    if (!token) return;
    try {
      await navigator.clipboard.writeText(token);
      copiedTokenId = terminalId;
      setTimeout(() => (copiedTokenId = null), 3000);
    } catch {
      // fallback
    }
  }

  let customDeviceToken = $state('');
  let freshTokens = $state<Record<string, string>>({});

  async function handleCreateTerminal() {
    if (!activeBranch) return;
    isCreatingTerminal = true;
    try {
      const res = await inventoryService.createTerminal(
        activeBranch.id,
        newTerminalName || undefined,
        customDeviceToken.trim() || undefined
      );
      if (res && res.id && res.device_token) {
        freshTokens[res.id] = res.device_token;
      }
      newTerminalName = '';
      customDeviceToken = '';
      isShowAddTerminalModal = false;
      terminalActionMsg = 'Terminal kasir POS baru berhasil ditambahkan.';
      setTimeout(() => (terminalActionMsg = null), 4000);
      onBranchUpdated?.();
    } catch (err: unknown) {
      branchErrorMsg = err instanceof Error ? err.message : 'Gagal membuat terminal kasir.';
      setTimeout(() => (branchErrorMsg = null), 4000);
    } finally {
      isCreatingTerminal = false;
    }
  }

  async function handleRegenerateToken(terminalId: string) {
    if (!activeBranch) return;
    if (
      !confirm(
        'Apakah Anda yakin ingin memperbarui token terminal ini? Tablet kasir yang sedang terhubung perlu memasukkan token baru.'
      )
    )
      return;
    try {
      const res = await inventoryService.regenerateTerminalToken(activeBranch.id, terminalId);
      if (res && res.device_token) {
        freshTokens[terminalId] = res.device_token;
      }
      terminalActionMsg = 'Device token baru berhasil diterbitkan.';
      setTimeout(() => (terminalActionMsg = null), 4000);
      onBranchUpdated?.();
    } catch (err: unknown) {
      branchErrorMsg = err instanceof Error ? err.message : 'Gagal memperbarui token terminal.';
      setTimeout(() => (branchErrorMsg = null), 4000);
    }
  }

  async function handleDeleteTerminal(terminalId: string) {
    if (!activeBranch) return;
    if (
      !confirm(
        'Hapus terminal kasir ini? Tablet kasir tidak akan dapat mengakses sistem POS cabang ini.'
      )
    )
      return;
    try {
      await inventoryService.deleteTerminal(activeBranch.id, terminalId);
      terminalActionMsg = 'Terminal kasir berhasil dihapus.';
      setTimeout(() => (terminalActionMsg = null), 4000);
      onBranchUpdated?.();
    } catch (err: unknown) {
      branchErrorMsg = err instanceof Error ? err.message : 'Gagal menghapus terminal.';
      setTimeout(() => (branchErrorMsg = null), 4000);
    }
  }
</script>

<div
  class="space-y-5 rounded-2xl border border-[#e5e5ea] bg-white p-4 font-sans shadow-2xs sm:rounded-3xl sm:p-6"
>
  <!-- Header -->
  <div class="border-b border-[#f2f2f4] pb-3">
    <h3 class="text-sm font-bold text-[#17171c] sm:text-base">Pengaturan Cabang &amp; Presensi</h3>
    <p class="mt-0.5 text-xs text-[#8e8e93]">
      Lokasi GPS, radius batas selfie, dan parameter gaji lembur
    </p>
  </div>

  {#if branchSuccessMsg}
    <div
      class="animate-in fade-in flex items-center gap-2 rounded-xl border border-[#a7f3d0] bg-[#ecfdf5] p-3 text-xs font-semibold text-[#065f46]"
    >
      <Check class="size-4 shrink-0 text-[#059669]" />
      <span>{branchSuccessMsg}</span>
    </div>
  {/if}

  {#if branchErrorMsg}
    <div
      class="animate-in fade-in flex items-center gap-2 rounded-xl border border-[#fecaca] bg-[#fef2f2] p-3 text-xs font-semibold text-[#991b1b]"
    >
      <AlertCircle class="size-4 shrink-0 text-[#dc2626]" />
      <span>{branchErrorMsg}</span>
    </div>
  {/if}

  <!-- Nama Cabang -->
  <div class="space-y-1 text-xs">
    <label for="branch-name-input" class="block font-bold text-[#17171c]">Nama Cabang Outlet</label>
    <input
      id="branch-name-input"
      type="text"
      bind:value={branchName}
      placeholder="Contoh: Norde Coffee - Seturan"
      class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-3.5 py-2 text-xs font-medium text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
    />
  </div>

  <!-- Peta Lokasi (Simple & Clean) -->
  <div class="space-y-1.5">
    <div class="flex items-center justify-between">
      <span class="flex items-center gap-1.5 text-xs font-bold text-[#17171c]">
        <MapPin class="size-3.5 text-[#2563eb]" />
        <span>Titik Koordinat &amp; Geofence</span>
      </span>
      <span class="text-[11px] text-[#8e8e93]">Geser pin hitam ke atap outlet</span>
    </div>

    <!-- Map Component -->
    <InteractiveMapPicker
      latitude={branchLatitude}
      longitude={branchLongitude}
      radiusMeters={geofenceRadius}
      {branchName}
      savedLatitude={activeBranch ? Number(activeBranch.lat) : undefined}
      savedLongitude={activeBranch ? Number(activeBranch.lng) : undefined}
      onChangeCoordinates={(lat, lng) => {
        branchLatitude = lat;
        branchLongitude = lng;
      }}
    />
  </div>

  <!-- Detail Koordinat & Parameter Grid -->
  <div class="grid grid-cols-1 gap-4 pt-1 text-xs md:grid-cols-2">
    <!-- Kolom Kiri: Angka Koordinat & Slider Radius -->
    <div class="space-y-3.5 rounded-2xl border border-[#e5e5ea] bg-[#fafafc] p-3.5">
      <!-- Input Latitude & Longitude -->
      <div class="space-y-1.5">
        <div class="flex items-center justify-between gap-2">
          <span class="font-bold whitespace-nowrap text-[#17171c]">Koordinat</span>
          <div class="flex shrink-0 items-center gap-1.5">
            <button
              type="button"
              onclick={handleUseCurrentGPS}
              disabled={isDetectingGps}
              class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-[#bfdbfe] bg-[#eff6ff] px-2.5 py-1 text-[11px] font-bold whitespace-nowrap text-[#1d4ed8] shadow-2xs transition-all hover:bg-[#dbeafe] active:scale-95 disabled:opacity-50"
              title="Baca sensor GPS perangkat saat ini"
            >
              {#if isDetectingGps}
                <RefreshCw class="size-3.5 animate-spin text-[#2563eb]" />
                <span>Mendeteksi...</span>
              {:else}
                <LocateFixed class="size-3.5 text-[#2563eb]" />
                <span>Lokasiku</span>
              {/if}
            </button>
            <a
              href={`https://www.google.com/maps?q=${branchLatitude},${branchLongitude}`}
              target="_blank"
              rel="noreferrer"
              class="flex items-center gap-1 rounded-lg border border-[#e5e5ea] bg-white px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap text-[#4b5563] shadow-2xs transition-all hover:bg-[#f8f8fa] hover:text-[#111827]"
              title="Buka titik koordinat pada Google Maps"
            >
              <span>GMaps</span>
              <ExternalLink class="size-3.5 text-[#6b7280]" />
            </a>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div class="space-y-0.5">
            <span class="font-mono text-[10px] text-[#8e8e93]">Latitude</span>
            <input
              id="branch-lat"
              type="number"
              step="any"
              bind:value={branchLatitude}
              class="w-full rounded-lg border border-[#e5e5ea] bg-white px-3 py-1.5 font-mono text-xs font-semibold text-[#17171c] shadow-2xs focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
          <div class="space-y-0.5">
            <span class="font-mono text-[10px] text-[#8e8e93]">Longitude</span>
            <input
              id="branch-lng"
              type="number"
              step="any"
              bind:value={branchLongitude}
              class="w-full rounded-lg border border-[#e5e5ea] bg-white px-3 py-1.5 font-mono text-xs font-semibold text-[#17171c] shadow-2xs focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
        </div>
      </div>

      <!-- Slider Radius Geofence -->
      <div class="space-y-1.5 border-t border-[#f0f0f3] pt-1">
        <div class="flex items-center justify-between">
          <label for="branch-radius" class="font-bold text-[#17171c]"
            >Radius Toleransi Presensi</label
          >
          <span
            class="rounded-full border border-[#e5e5ea] bg-white px-2.5 py-0.5 font-mono text-xs font-bold text-[#17171c] shadow-2xs"
          >
            {geofenceRadius} Meter
          </span>
        </div>
        <input
          id="branch-radius"
          type="range"
          min="20"
          max="300"
          step="10"
          bind:value={geofenceRadius}
          class="h-1.5 w-full cursor-pointer rounded-lg bg-[#e5e5ea] accent-[#17171c]"
        />
      </div>
    </div>

    <!-- Kolom Kanan: Aturan Denda & Lembur -->
    <div class="space-y-3 rounded-2xl border border-[#e5e5ea] bg-[#fafafc] p-3.5">
      <span class="block font-bold text-[#17171c]">Parameter Denda &amp; Lembur</span>

      <div class="grid grid-cols-2 gap-2.5">
        <div class="space-y-0.5">
          <label for="setting-penalty" class="block text-[10.5px] font-medium text-[#686873]"
            >Denda Telat / Menit</label
          >
          <div class="relative">
            <span
              class="absolute top-1/2 left-2.5 -translate-y-1/2 font-mono text-[10px] text-[#8e8e93]"
              >Rp</span
            >
            <input
              id="setting-penalty"
              type="number"
              bind:value={latePenaltyRate}
              class="w-full rounded-lg border border-[#e5e5ea] bg-white py-1.5 pr-2.5 pl-7 font-mono text-xs font-semibold text-[#17171c] shadow-2xs focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
        </div>

        <div class="space-y-0.5">
          <label for="setting-overtime" class="block text-[10.5px] font-medium text-[#686873]"
            >Upah Lembur / Jam</label
          >
          <div class="relative">
            <span
              class="absolute top-1/2 left-2.5 -translate-y-1/2 font-mono text-[10px] text-[#8e8e93]"
              >Rp</span
            >
            <input
              id="setting-overtime"
              type="number"
              bind:value={overtimeHourlyRate}
              class="w-full rounded-lg border border-[#e5e5ea] bg-white py-1.5 pr-2.5 pl-7 font-mono text-xs font-semibold text-[#17171c] shadow-2xs focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
        </div>
      </div>

      <div class="space-y-0.5">
        <label for="setting-threshold" class="block text-[10.5px] font-medium text-[#686873]"
          >Ambang Batas Mulai Lembur (Menit)</label
        >
        <input
          id="setting-threshold"
          type="number"
          bind:value={minOvertimeThreshold}
          class="w-full rounded-lg border border-[#e5e5ea] bg-white px-3 py-1.5 font-mono text-xs font-semibold text-[#17171c] shadow-2xs focus:border-[#17171c] focus:outline-hidden"
        />
      </div>
    </div>
  </div>

  <!-- Card Konfigurasi Pajak Restoran & Biaya Layanan (Dynamic Tax System) -->
  <div class="space-y-3.5 rounded-2xl border border-[#e5e5ea] bg-[#fafafc] p-4 shadow-2xs">
    <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-center">
      <div class="flex items-center gap-2">
        <Receipt class="size-4 text-[#17171c]" />
        <h4 class="text-xs font-bold text-[#17171c]">Pengaturan Pajak (PB1 / PPN) & Biaya Layanan</h4>
      </div>
      <span class="text-[11px] text-[#8e8e93]">
        Perhitungan dinamis di kasir POS tablet dan cetak struk
      </span>
    </div>

    <!-- Toggle Aktifkan Pajak -->
    <div class="flex items-center justify-between rounded-xl border border-[#e5e5ea] bg-white p-3 shadow-2xs">
      <div class="space-y-0.5">
        <span class="text-xs font-semibold text-[#17171c]">Aktifkan Perhitungan Pajak</span>
        <p class="text-[11px] text-[#686873]">
          Otomatis hitung pajak saat transaksi POS berlangsung
        </p>
      </div>
      <label class="relative inline-flex cursor-pointer items-center">
        <input
          type="checkbox"
          bind:checked={isTaxEnabled}
          class="peer sr-only"
        />
        <div
          class="peer h-5 w-9 rounded-full bg-[#e5e5ea] peer-checked:bg-[#17171c] peer-focus:outline-hidden after:absolute after:top-[2px] after:left-[2px] after:size-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"
        ></div>
      </label>
    </div>

    {#if isTaxEnabled}
      <div class="space-y-3.5 pt-1">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="space-y-0.5">
            <label for="setting-tax-name" class="block text-[10.5px] font-medium text-[#686873]">
              Nama Pajak / Label di Struk
            </label>
            <input
              id="setting-tax-name"
              type="text"
              bind:value={taxName}
              placeholder="Contoh: PB1, PPN, Pajak Resto"
              class="w-full rounded-lg border border-[#e5e5ea] bg-white px-3 py-1.5 text-xs font-semibold text-[#17171c] shadow-2xs focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="space-y-0.5">
            <label for="setting-tax-rate" class="block text-[10.5px] font-medium text-[#686873]">
              Tarif Pajak (%)
            </label>
            <div class="relative">
              <input
                id="setting-tax-rate"
                type="number"
                step="0.01"
                min="0"
                max="100"
                bind:value={taxRate}
                class="w-full rounded-lg border border-[#e5e5ea] bg-white py-1.5 pr-7 pl-3 font-mono text-xs font-semibold text-[#17171c] shadow-2xs focus:border-[#17171c] focus:outline-hidden"
              />
              <span class="absolute top-1/2 right-2.5 -translate-y-1/2 font-mono text-[10px] text-[#8e8e93]">%</span>
            </div>
          </div>
        </div>

        <!-- Tipe Perhitungan Pajak (Inclusive vs Exclusive) -->
        <div class="space-y-1.5">
          <span class="block text-[10.5px] font-medium text-[#686873]">
            Metode Perhitungan Pajak
          </span>
          <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            <!-- Inclusive -->
            <label
              class={`flex cursor-pointer flex-col gap-1 rounded-xl border p-3 transition-all ${
                taxType === 'INCLUSIVE'
                  ? 'border-[#17171c] bg-white ring-1 ring-[#17171c]'
                  : 'border-[#e5e5ea] bg-white hover:border-[#8e8e93]'
              }`}
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[#17171c]">Tax Inclusive (Nett)</span>
                <input
                  type="radio"
                  name="taxType"
                  value="INCLUSIVE"
                  bind:group={taxType}
                  class="size-3.5 text-[#17171c] focus:ring-0"
                />
              </div>
              <p class="text-[10.5px] leading-relaxed text-[#686873]">
                Harga menu sudah termasuk pajak. Total tagihan pelanggan tidak bertambah, pajak dipisahkan di belakang layar.
              </p>
            </label>

            <!-- Exclusive -->
            <label
              class={`flex cursor-pointer flex-col gap-1 rounded-xl border p-3 transition-all ${
                taxType === 'EXCLUSIVE'
                  ? 'border-[#17171c] bg-white ring-1 ring-[#17171c]'
                  : 'border-[#e5e5ea] bg-white hover:border-[#8e8e93]'
              }`}
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[#17171c]">Tax Exclusive (Plus-Plus)</span>
                <input
                  type="radio"
                  name="taxType"
                  value="EXCLUSIVE"
                  bind:group={taxType}
                  class="size-3.5 text-[#17171c] focus:ring-0"
                />
              </div>
              <p class="text-[10.5px] leading-relaxed text-[#686873]">
                Pajak ditambahkan di akhir transaksi. Total tagihan pelanggan bertambah sebesar persentase pajak.
              </p>
            </label>
          </div>
        </div>

        <!-- Checkbox Cetak di Struk -->
        <label class="flex cursor-pointer items-center gap-2 pt-1 text-xs text-[#17171c]">
          <input
            type="checkbox"
            bind:checked={showTaxOnReceipt}
            class="rounded border-[#e5e5ea] text-[#17171c] focus:ring-0"
          />
          <span>Cetak rincian nilai pajak di struk thermal POS</span>
        </label>
      </div>
    {/if}
  </div>

  <!-- Card Barcode QRIS Statis Toko (Pembayaran POS) -->
  <div class="space-y-3.5 rounded-2xl border border-[#e5e5ea] bg-[#fafafc] p-4 shadow-2xs">
    <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-center">
      <div class="flex items-center gap-2">
        <QrCode class="size-4 text-[#17171c]" />
        <h4 class="text-xs font-bold text-[#17171c]">Stiker QRIS Statis Toko</h4>
      </div>
      <span class="text-[11px] text-[#8e8e93]">
        Ditampilkan di tablet POS saat pelanggan memilih pembayaran QRIS
      </span>
    </div>

    <!-- Hidden file input -->
    <input
      type="file"
      accept="image/png,image/jpeg,image/webp"
      class="hidden"
      bind:this={fileInputRef}
      onchange={handleQrisFileSelected}
    />

    {#if branchQrisUrl}
      <!-- Tampilan QRIS -->
      <div class="flex flex-col items-center justify-center gap-4 py-2">
        <img
          src={normalizeMediaUrl(branchQrisUrl)}
          alt="Preview QRIS Toko"
          class="max-h-72 w-auto max-w-full rounded-xl object-contain shadow-2xs sm:max-h-80"
        />

        <div class="flex items-center gap-2.5">
          <button
            type="button"
            onclick={() => fileInputRef?.click()}
            disabled={isUploadingQris}
            class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-[#e5e5ea] bg-white px-4 py-2 text-xs font-semibold text-[#17171c] shadow-2xs transition-all hover:bg-[#f8f8fa] disabled:opacity-50"
          >
            {#if isUploadingQris}
              <RefreshCw class="size-3.5 animate-spin" />
              <span>Mengunggah...</span>
            {:else}
              <Upload class="size-3.5" />
              <span>Ganti Foto</span>
            {/if}
          </button>

          <button
            type="button"
            onclick={handleDeleteQris}
            disabled={isUploadingQris}
            class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-[#fecaca] bg-[#fef2f2] px-4 py-2 text-xs font-semibold text-[#dc2626] shadow-2xs transition-all hover:bg-[#fee2e2] disabled:opacity-50"
            title="Hapus barcode QRIS cabang ini"
          >
            <Trash2 class="size-3.5" />
            <span>Hapus</span>
          </button>
        </div>
      </div>
    {:else}
      <!-- Drag and drop area -->
      <div
        role="button"
        tabindex="0"
        onclick={() => fileInputRef?.click()}
        onkeydown={(e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            fileInputRef?.click();
          }
        }}
        ondragover={(e) => {
          e.preventDefault();
          isDraggingQris = true;
        }}
        ondragleave={() => (isDraggingQris = false)}
        ondrop={handleQrisDrop}
        class={`flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-6 text-center transition-all ${
          isDraggingQris
            ? 'border-[#17171c] bg-[#f0f0f3]'
            : 'border-[#d2d2d7] bg-white hover:border-[#17171c] hover:bg-[#fafafc]'
        }`}
      >
        {#if isUploadingQris}
          <RefreshCw class="size-8 animate-spin text-[#17171c]" />
          <p class="text-xs font-bold text-[#17171c]">Mengunggah foto QRIS ke storage...</p>
        {:else}
          <div
            class="flex size-10 items-center justify-center rounded-full bg-[#f0f0f3] text-[#17171c]"
          >
            <Upload class="size-5" />
          </div>
          <div>
            <p class="text-xs font-bold text-[#17171c]">
              Klik atau tarik foto stiker QRIS toko ke sini
            </p>
            <p class="mt-0.5 text-[11px] text-[#8e8e93]">
              Format PNG, JPG, atau WebP (Maksimal 2MB)
            </p>
          </div>
        {/if}
      </div>
    {/if}
  </div>

  <!-- Tombol Simpan -->
  <div class="pt-2">
    <button
      type="button"
      onclick={handleSaveBranch}
      disabled={isSavingBranch || (branches.length === 0 && !selectedBranchId)}
      class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-2.5 text-xs font-bold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
    >
      {#if isSavingBranch}
        <RefreshCw class="size-3.5 animate-spin" />
        <span>Menyimpan...</span>
      {:else}
        <Save class="size-3.5" />
        <span>Simpan Pengaturan Cabang</span>
      {/if}
    </button>
  </div>

  <!-- Card Manajemen Terminal Kasir POS & Token Pairing -->
  <div class="space-y-4 border-t border-[#e5e5ea] pt-4">
    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
      <div>
        <div class="flex items-center gap-2">
          <Tablet class="size-4 text-[#17171c]" />
          <h3 class="text-sm font-bold text-[#17171c]">Terminal Kasir POS &amp; Device Token</h3>
        </div>
        <p class="mt-0.5 text-xs text-[#8e8e93]">
          Gunakan Device Token untuk menghubungkan tablet/perangkat kasir fisik ke cabang ini
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          onclick={() => (isShowAddTerminalModal = true)}
          class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-[#e5e5ea] bg-white px-3 py-1.5 text-xs font-semibold text-[#17171c] shadow-2xs transition-all hover:bg-[#f8f8fa]"
        >
          <Plus class="size-3.5" />
          <span>Tambah Terminal</span>
        </button>

        <a
          href="https://pos.precis.com"
          target="_blank"
          rel="noreferrer"
          class="flex items-center gap-1.5 rounded-xl bg-[#17171c] px-3 py-1.5 text-xs font-semibold text-white shadow-2xs transition-all hover:bg-black"
        >
          <span>Buka POS Kiosk</span>
          <ExternalLink class="size-3 text-white/80" />
        </a>
      </div>
    </div>

    {#if terminalActionMsg}
      <div
        class="animate-in fade-in flex items-center gap-2 rounded-xl border border-[#a7f3d0] bg-[#ecfdf5] p-3 text-xs font-medium text-[#065f46]"
      >
        <Check class="size-4 text-[#059669]" />
        <span>{terminalActionMsg}</span>
      </div>
    {/if}

    <!-- Terminal Items List -->
    {#if activeBranch?.terminals && activeBranch.terminals.length > 0}
      <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        {#each activeBranch.terminals as terminal}
          <div class="space-y-3 rounded-2xl border border-[#e5e5ea] bg-[#fafafc] p-4 shadow-2xs">
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2.5">
                <div
                  class="flex size-8 items-center justify-center rounded-xl border border-[#e5e5ea] bg-white text-[#17171c] shadow-2xs"
                >
                  <Tablet class="size-4" />
                </div>
                <div>
                  <h4 class="text-xs font-bold text-[#17171c]">{terminal.terminal_name}</h4>
                  <span
                    class="inline-flex items-center gap-1 text-[10px] font-semibold text-[#059669]"
                  >
                    <span class="size-1.5 rounded-full bg-[#10b981]"></span>
                    {terminal.is_active ? 'Siap Pairing / Aktif' : 'Nonaktif'}
                  </span>
                </div>
              </div>

              <div class="flex items-center gap-1">
                <button
                  type="button"
                  onclick={() => handleRegenerateToken(terminal.id)}
                  title="Terbitkan Token Baru"
                  class="cursor-pointer rounded-lg border border-transparent p-1.5 text-[#8e8e93] transition-colors hover:border-[#e5e5ea] hover:bg-white hover:text-[#17171c]"
                >
                  <RefreshCw class="size-3.5" />
                </button>
                <button
                  type="button"
                  onclick={() => handleDeleteTerminal(terminal.id)}
                  title="Hapus Terminal"
                  class="cursor-pointer rounded-lg p-1.5 text-[#8e8e93] transition-colors hover:bg-[#ffe4e6] hover:text-[#e11d48]"
                >
                  <Trash2 class="size-3.5" />
                </button>
              </div>
            </div>

            <!-- Token Box -->
            <div class="space-y-1">
              <span class="block text-[10px] font-medium text-[#8e8e93]"
                >Device Token (Salin ke Kiosk POS):</span
              >
              <div
                class="flex items-center gap-2 rounded-xl border border-[#e5e5ea] bg-white p-2 shadow-2xs"
              >
                <Key class="size-3.5 shrink-0 text-[#8e8e93]" />
                <code
                  class="flex-1 truncate font-mono text-xs font-bold tracking-wide text-[#17171c] select-all"
                >
                  {freshTokens[terminal.id] || terminal.device_token || terminal.device_token_preview || '-'}
                </code>
                <button
                  type="button"
                  onclick={() =>
                    handleCopyToken(
                      terminal.id,
                      freshTokens[terminal.id] || terminal.device_token || terminal.device_token_preview || ''
                    )}
                  class={`flex cursor-pointer items-center gap-1 rounded-lg px-2.5 py-1 text-[11px] font-semibold transition-all ${
                    copiedTokenId === terminal.id
                      ? 'bg-[#059669] text-white shadow-2xs'
                      : 'bg-[#f0f0f3] text-[#17171c] hover:bg-[#e5e5ea]'
                  }`}
                >
                  {#if copiedTokenId === terminal.id}
                    <Check class="size-3" />
                    <span>Tersalin</span>
                  {:else}
                    <Copy class="size-3" />
                    <span>Salin</span>
                  {/if}
                </button>
              </div>
            </div>
          </div>
        {/each}
      </div>
    {:else}
      <div
        class="space-y-3 rounded-2xl border border-dashed border-[#d2d2d7] bg-[#fafafc] p-6 text-center"
      >
        <Tablet class="mx-auto size-8 text-[#8e8e93] opacity-50" />
        <div>
          <p class="text-xs font-semibold text-[#17171c]">
            Belum ada terminal kasir untuk cabang ini
          </p>
          <p class="mt-0.5 text-[11px] text-[#8e8e93]">
            Buat terminal kasir pertama untuk mendapatkan Device Token tablet POS.
          </p>
        </div>
        <button
          type="button"
          onclick={() => handleCreateTerminal()}
          disabled={isCreatingTerminal}
          class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-[#17171c] px-4 py-2 text-xs font-bold text-white shadow-2xs transition-all hover:bg-black"
        >
          <Plus class="size-3.5" />
          <span>Buat Terminal Kasir Utama</span>
        </button>
      </div>
    {/if}
  </div>
</div>

<!-- Modal Tambah Terminal Kasir Baru -->
{#if isShowAddTerminalModal}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#17171c]/60 p-4 backdrop-blur-xs"
  >
    <div
      class="animate-in zoom-in-95 w-full max-w-sm space-y-4 rounded-3xl border border-[#e5e5ea] bg-white p-5 font-sans shadow-xl sm:p-6"
    >
      <div class="flex items-center gap-3">
        <div class="flex size-10 items-center justify-center rounded-2xl bg-[#17171c] text-white">
          <Tablet class="size-5" />
        </div>
        <div>
          <h3 class="text-sm font-bold text-[#17171c]">Tambah Terminal Kasir</h3>
          <p class="text-xs text-[#8e8e93]">Buat token pairing baru untuk tablet kasir</p>
        </div>
      </div>

      <div class="space-y-1.5">
        <label for="new-terminal-name" class="block text-xs font-semibold text-[#17171c]">
          Nama Perangkat / Terminal
        </label>
        <input
          id="new-terminal-name"
          type="text"
          bind:value={newTerminalName}
          placeholder="e.g. Kasir Utama Lantai 1"
          class="w-full rounded-xl border border-[#e5e5ea] bg-[#fafafc] px-3.5 py-2 text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
        />
      </div>

      <div class="space-y-1.5">
        <label for="new-terminal-token" class="block text-xs font-semibold text-[#17171c]">
          Kustom Device Token (Mudah Dihafal)
        </label>
        <input
          id="new-terminal-token"
          type="text"
          bind:value={customDeviceToken}
          placeholder="e.g. KASIR-01 atau SETURAN-BAR"
          class="w-full rounded-xl border border-[#e5e5ea] bg-[#fafafc] px-3.5 py-2 font-mono text-xs font-bold text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
        />
        <p class="text-[10px] text-[#8e8e93]">
          Kosongkan jika ingin token di-generate otomatis oleh sistem.
        </p>
      </div>

      <div class="flex items-center gap-2 pt-2">
        <button
          type="button"
          onclick={() => (isShowAddTerminalModal = false)}
          class="flex-1 cursor-pointer rounded-xl bg-[#f0f0f3] py-2 text-xs font-bold text-[#17171c] transition-all hover:bg-[#e5e5ea]"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleCreateTerminal}
          disabled={isCreatingTerminal}
          class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-[#17171c] py-2 text-xs font-bold text-white transition-all hover:bg-black disabled:opacity-50"
        >
          {#if isCreatingTerminal}
            <RefreshCw class="size-3.5 animate-spin" />
            <span>Membuat...</span>
          {:else}
            <Plus class="size-3.5" />
            <span>Buat Token</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
