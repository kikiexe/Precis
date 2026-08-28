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

  let isDetectingGps = $state(false);
  let isSavingBranch = $state(false);
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
        minOvertimeThreshold = active.min_overtime_threshold_minutes ?? DEFAULT_MIN_OVERTIME_THRESHOLD_MINUTES;
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

    if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
      branchErrorMsg = 'Browser memblokir GPS via HTTP IP lokal. Gunakan HTTPS, localhost, atau aktifkan flags insecure origin di Chrome.';
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
        late_penalty_per_minute: Number(latePenaltyRate) || 0,
        overtime_pay_per_hour: Number(overtimeHourlyRate) || 0,
        min_overtime_threshold_minutes: Number(minOvertimeThreshold) || 0,
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

  async function handleCreateTerminal() {
    if (!activeBranch) return;
    isCreatingTerminal = true;
    try {
      await inventoryService.createTerminal(
        activeBranch.id,
        newTerminalName || undefined,
        customDeviceToken.trim() || undefined
      );
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
    if (!confirm('Apakah Anda yakin ingin memperbarui token terminal ini? Tablet kasir yang sedang terhubung perlu memasukkan token baru.')) return;
    try {
      await inventoryService.regenerateTerminalToken(activeBranch.id, terminalId);
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
    if (!confirm('Hapus terminal kasir ini? Tablet kasir tidak akan dapat mengakses sistem POS cabang ini.')) return;
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

<div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-4 sm:p-6 space-y-5 shadow-2xs font-sans">
  
  <!-- Header -->
  <div class="border-b border-[#f2f2f4] pb-3">
    <h3 class="text-sm sm:text-base font-bold text-[#17171c]">Pengaturan Cabang &amp; Presensi</h3>
    <p class="text-xs text-[#8e8e93] mt-0.5">Lokasi GPS, radius batas selfie, dan parameter gaji lembur</p>
  </div>

  {#if branchSuccessMsg}
    <div class="p-3 bg-[#ecfdf5] border border-[#a7f3d0] rounded-xl text-xs font-semibold text-[#065f46] flex items-center gap-2 animate-in fade-in">
      <Check class="w-4 h-4 shrink-0 text-[#059669]" />
      <span>{branchSuccessMsg}</span>
    </div>
  {/if}

  {#if branchErrorMsg}
    <div class="p-3 bg-[#fef2f2] border border-[#fecaca] rounded-xl text-xs font-semibold text-[#991b1b] flex items-center gap-2 animate-in fade-in">
      <AlertCircle class="w-4 h-4 shrink-0 text-[#dc2626]" />
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
      class="w-full px-3.5 py-2 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs font-medium"
    />
  </div>

  <!-- Peta Lokasi (Simple & Clean) -->
  <div class="space-y-1.5">
    <div class="flex items-center justify-between">
      <span class="text-xs font-bold text-[#17171c] flex items-center gap-1.5">
        <MapPin class="w-3.5 h-3.5 text-[#2563eb]" />
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
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs pt-1">
    
    <!-- Kolom Kiri: Angka Koordinat & Slider Radius -->
    <div class="space-y-3.5 p-3.5 bg-[#fafafc] border border-[#e5e5ea] rounded-2xl">
      
      <!-- Input Latitude & Longitude -->
      <div class="space-y-1.5">
        <div class="flex items-center justify-between gap-2">
          <span class="font-bold text-[#17171c] whitespace-nowrap">Koordinat</span>
          <div class="flex items-center gap-1.5 shrink-0">
            <button
              type="button"
              onclick={handleUseCurrentGPS}
              disabled={isDetectingGps}
              class="px-2.5 py-1 bg-[#eff6ff] hover:bg-[#dbeafe] text-[#1d4ed8] border border-[#bfdbfe] rounded-lg text-[11px] font-bold flex items-center gap-1.5 cursor-pointer transition-all disabled:opacity-50 active:scale-95 shadow-2xs whitespace-nowrap"
              title="Baca sensor GPS perangkat saat ini"
            >
              {#if isDetectingGps}
                <RefreshCw class="w-3.5 h-3.5 animate-spin text-[#2563eb]" />
                <span>Mendeteksi...</span>
              {:else}
                <LocateFixed class="w-3.5 h-3.5 text-[#2563eb]" />
                <span>Lokasiku</span>
              {/if}
            </button>
            <a
              href={`https://www.google.com/maps?q=${branchLatitude},${branchLongitude}`}
              target="_blank"
              rel="noreferrer"
              class="px-2.5 py-1 bg-white hover:bg-[#f8f8fa] text-[#4b5563] hover:text-[#111827] border border-[#e5e5ea] rounded-lg text-[11px] font-semibold flex items-center gap-1 transition-all shadow-2xs whitespace-nowrap"
              title="Buka titik koordinat pada Google Maps"
            >
              <span>GMaps</span>
              <ExternalLink class="w-3.5 h-3.5 text-[#6b7280]" />
            </a>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div class="space-y-0.5">
            <span class="text-[10px] text-[#8e8e93] font-mono">Latitude</span>
            <input
              id="branch-lat"
              type="number"
              step="any"
              bind:value={branchLatitude}
              class="w-full px-3 py-1.5 bg-white border border-[#e5e5ea] rounded-lg font-mono text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden font-semibold shadow-2xs"
            />
          </div>
          <div class="space-y-0.5">
            <span class="text-[10px] text-[#8e8e93] font-mono">Longitude</span>
            <input
              id="branch-lng"
              type="number"
              step="any"
              bind:value={branchLongitude}
              class="w-full px-3 py-1.5 bg-white border border-[#e5e5ea] rounded-lg font-mono text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden font-semibold shadow-2xs"
            />
          </div>
        </div>
      </div>

      <!-- Slider Radius Geofence -->
      <div class="space-y-1.5 pt-1 border-t border-[#f0f0f3]">
        <div class="flex justify-between items-center">
          <label for="branch-radius" class="font-bold text-[#17171c]">Radius Toleransi Presensi</label>
          <span class="font-mono font-bold text-[#17171c] bg-white border border-[#e5e5ea] px-2.5 py-0.5 rounded-full text-xs shadow-2xs">
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
          class="w-full accent-[#17171c] cursor-pointer h-1.5 bg-[#e5e5ea] rounded-lg"
        />
      </div>

    </div>

    <!-- Kolom Kanan: Aturan Denda & Lembur -->
    <div class="space-y-3 p-3.5 bg-[#fafafc] border border-[#e5e5ea] rounded-2xl">
      <span class="font-bold text-[#17171c] block">Parameter Denda &amp; Lembur</span>

      <div class="grid grid-cols-2 gap-2.5">
        <div class="space-y-0.5">
          <label for="setting-penalty" class="text-[10.5px] text-[#686873] font-medium block">Denda Telat / Menit</label>
          <div class="relative">
            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 font-mono text-[#8e8e93] text-[10px]">Rp</span>
            <input
              id="setting-penalty"
              type="number"
              bind:value={latePenaltyRate}
              class="w-full pl-7 pr-2.5 py-1.5 bg-white border border-[#e5e5ea] rounded-lg font-mono text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden font-semibold shadow-2xs"
            />
          </div>
        </div>

        <div class="space-y-0.5">
          <label for="setting-overtime" class="text-[10.5px] text-[#686873] font-medium block">Upah Lembur / Jam</label>
          <div class="relative">
            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 font-mono text-[#8e8e93] text-[10px]">Rp</span>
            <input
              id="setting-overtime"
              type="number"
              bind:value={overtimeHourlyRate}
              class="w-full pl-7 pr-2.5 py-1.5 bg-white border border-[#e5e5ea] rounded-lg font-mono text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden font-semibold shadow-2xs"
            />
          </div>
        </div>
      </div>

      <div class="space-y-0.5">
        <label for="setting-threshold" class="text-[10.5px] text-[#686873] font-medium block">Ambang Batas Mulai Lembur (Menit)</label>
        <input
          id="setting-threshold"
          type="number"
          bind:value={minOvertimeThreshold}
          class="w-full px-3 py-1.5 bg-white border border-[#e5e5ea] rounded-lg font-mono text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden font-semibold shadow-2xs"
        />
      </div>
    </div>

  </div>

  <!-- Tombol Simpan -->
  <div class="pt-2">
    <button
      type="button"
      onclick={handleSaveBranch}
      disabled={isSavingBranch || (branches.length === 0 && !selectedBranchId)}
      class="w-full py-2.5 bg-[#17171c] hover:bg-black text-white font-bold text-xs rounded-full transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs"
    >
      {#if isSavingBranch}
        <RefreshCw class="w-3.5 h-3.5 animate-spin" />
        <span>Menyimpan...</span>
      {:else}
        <Save class="w-3.5 h-3.5" />
        <span>Simpan Pengaturan Cabang</span>
      {/if}
    </button>
  </div>

  <!-- Card Manajemen Terminal Kasir POS & Token Pairing -->
  <div class="space-y-4 pt-4 border-t border-[#e5e5ea]">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
      <div>
        <div class="flex items-center gap-2">
          <Tablet class="w-4 h-4 text-[#17171c]" />
          <h3 class="font-bold text-sm text-[#17171c]">Terminal Kasir POS &amp; Device Token</h3>
        </div>
        <p class="text-xs text-[#8e8e93] mt-0.5">
          Gunakan Device Token untuk menghubungkan tablet/perangkat kasir fisik ke cabang ini
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          onclick={() => (isShowAddTerminalModal = true)}
          class="px-3 py-1.5 bg-white hover:bg-[#f8f8fa] text-[#17171c] border border-[#e5e5ea] rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer shadow-2xs"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>Tambah Terminal</span>
        </button>

        <a
          href="https://pos.precis.com"
          target="_blank"
          rel="noreferrer"
          class="px-3 py-1.5 bg-[#17171c] hover:bg-black text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all shadow-2xs"
        >
          <span>Buka POS Kiosk</span>
          <ExternalLink class="w-3 h-3 text-white/80" />
        </a>
      </div>
    </div>

    {#if terminalActionMsg}
      <div class="p-3 bg-[#ecfdf5] border border-[#a7f3d0] rounded-xl text-xs text-[#065f46] font-medium flex items-center gap-2 animate-in fade-in">
        <Check class="w-4 h-4 text-[#059669]" />
        <span>{terminalActionMsg}</span>
      </div>
    {/if}

    <!-- Terminal Items List -->
    {#if activeBranch?.terminals && activeBranch.terminals.length > 0}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        {#each activeBranch.terminals as terminal}
          <div class="bg-[#fafafc] border border-[#e5e5ea] rounded-2xl p-4 space-y-3 shadow-2xs">
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-white border border-[#e5e5ea] text-[#17171c] flex items-center justify-center shadow-2xs">
                  <Tablet class="w-4 h-4" />
                </div>
                <div>
                  <h4 class="font-bold text-xs text-[#17171c]">{terminal.terminal_name}</h4>
                  <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-[#059669]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#10b981]"></span>
                    {terminal.is_active ? 'Siap Pairing / Aktif' : 'Nonaktif'}
                  </span>
                </div>
              </div>

              <div class="flex items-center gap-1">
                <button
                  type="button"
                  onclick={() => handleRegenerateToken(terminal.id)}
                  title="Terbitkan Token Baru"
                  class="p-1.5 text-[#8e8e93] hover:text-[#17171c] hover:bg-white rounded-lg border border-transparent hover:border-[#e5e5ea] transition-colors cursor-pointer"
                >
                  <RefreshCw class="w-3.5 h-3.5" />
                </button>
                <button
                  type="button"
                  onclick={() => handleDeleteTerminal(terminal.id)}
                  title="Hapus Terminal"
                  class="p-1.5 text-[#8e8e93] hover:text-[#e11d48] hover:bg-[#ffe4e6] rounded-lg transition-colors cursor-pointer"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <!-- Token Box -->
            <div class="space-y-1">
              <span class="text-[10px] text-[#8e8e93] font-medium block">Device Token (Salin ke Kiosk POS):</span>
              <div class="flex items-center gap-2 p-2 bg-white border border-[#e5e5ea] rounded-xl shadow-2xs">
                <Key class="w-3.5 h-3.5 text-[#8e8e93] shrink-0" />
                <code class="font-mono text-xs font-bold text-[#17171c] tracking-wide select-all truncate flex-1">
                  {terminal.device_token}
                </code>
                <button
                  type="button"
                  onclick={() => handleCopyToken(terminal.id, terminal.device_token)}
                  class={`px-2.5 py-1 rounded-lg text-[11px] font-semibold flex items-center gap-1 transition-all cursor-pointer ${
                    copiedTokenId === terminal.id
                      ? 'bg-[#059669] text-white shadow-2xs'
                      : 'bg-[#f0f0f3] hover:bg-[#e5e5ea] text-[#17171c]'
                  }`}
                >
                  {#if copiedTokenId === terminal.id}
                    <Check class="w-3 h-3" />
                    <span>Tersalin</span>
                  {:else}
                    <Copy class="w-3 h-3" />
                    <span>Salin</span>
                  {/if}
                </button>
              </div>
            </div>
          </div>
        {/each}
      </div>
    {:else}
      <div class="p-6 bg-[#fafafc] border border-dashed border-[#d2d2d7] rounded-2xl text-center space-y-3">
        <Tablet class="w-8 h-8 text-[#8e8e93] mx-auto opacity-50" />
        <div>
          <p class="text-xs font-semibold text-[#17171c]">Belum ada terminal kasir untuk cabang ini</p>
          <p class="text-[11px] text-[#8e8e93] mt-0.5">Buat terminal kasir pertama untuk mendapatkan Device Token tablet POS.</p>
        </div>
        <button
          type="button"
          onclick={() => handleCreateTerminal()}
          disabled={isCreatingTerminal}
          class="px-4 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-bold rounded-xl transition-all cursor-pointer inline-flex items-center gap-2 shadow-2xs"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>Buat Terminal Kasir Utama</span>
        </button>
      </div>
    {/if}
  </div>

</div>

<!-- Modal Tambah Terminal Kasir Baru -->
{#if isShowAddTerminalModal}
  <div class="fixed inset-0 z-50 bg-[#17171c]/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl p-5 sm:p-6 max-w-sm w-full space-y-4 shadow-xl animate-in zoom-in-95 font-sans">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-[#17171c] text-white flex items-center justify-center">
          <Tablet class="w-5 h-5" />
        </div>
        <div>
          <h3 class="font-bold text-sm text-[#17171c]">Tambah Terminal Kasir</h3>
          <p class="text-xs text-[#8e8e93]">Buat token pairing baru untuk tablet kasir</p>
        </div>
      </div>

      <div class="space-y-1.5">
        <label for="new-terminal-name" class="text-xs font-semibold text-[#17171c] block">
          Nama Perangkat / Terminal
        </label>
        <input
          id="new-terminal-name"
          type="text"
          bind:value={newTerminalName}
          placeholder="e.g. Kasir Utama Lantai 1"
          class="w-full px-3.5 py-2 bg-[#fafafc] border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] focus:outline-hidden focus:border-[#17171c]"
        />
      </div>

      <div class="space-y-1.5">
        <label for="new-terminal-token" class="text-xs font-semibold text-[#17171c] block">
          Kustom Device Token (Mudah Dihafal)
        </label>
        <input
          id="new-terminal-token"
          type="text"
          bind:value={customDeviceToken}
          placeholder="e.g. KASIR-01 atau SETURAN-BAR"
          class="w-full px-3.5 py-2 bg-[#fafafc] border border-[#e5e5ea] rounded-xl text-xs font-mono font-bold text-[#17171c] focus:outline-hidden focus:border-[#17171c]"
        />
        <p class="text-[10px] text-[#8e8e93]">Kosongkan jika ingin token di-generate otomatis oleh sistem.</p>
      </div>

      <div class="flex items-center gap-2 pt-2">
        <button
          type="button"
          onclick={() => (isShowAddTerminalModal = false)}
          class="flex-1 py-2 bg-[#f0f0f3] hover:bg-[#e5e5ea] text-[#17171c] text-xs font-bold rounded-xl transition-all cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleCreateTerminal}
          disabled={isCreatingTerminal}
          class="flex-1 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-bold rounded-xl transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5"
        >
          {#if isCreatingTerminal}
            <RefreshCw class="w-3.5 h-3.5 animate-spin" />
            <span>Membuat...</span>
          {:else}
            <Plus class="w-3.5 h-3.5" />
            <span>Buat Token</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
