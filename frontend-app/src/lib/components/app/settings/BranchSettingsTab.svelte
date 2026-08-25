<script lang="ts">
  import {
    Check,
    Save,
    MapPin,
    LocateFixed,
    ExternalLink,
    ChevronDown,
  } from 'lucide-svelte';
  import type { BranchItem } from '../../../types/app';
  import { inventoryService } from '../../../services/inventory-service';
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
  let branchName = $state('');
  let branchLatitude = $state(-7.782914);
  let branchLongitude = $state(110.36712);
  let geofenceRadius = $state(DEFAULT_GEOFENCE_RADIUS_METERS);
  let latePenaltyRate = $state(DEFAULT_LATE_PENALTY_PER_MINUTE_IDR);
  let overtimeHourlyRate = $state(DEFAULT_OVERTIME_PAY_PER_HOUR_IDR);
  let minOvertimeThreshold = $state(DEFAULT_MIN_OVERTIME_THRESHOLD_MINUTES);

  let isSavingBranch = $state(false);
  let branchSuccessMsg = $state<string | null>(null);
  let branchErrorMsg = $state<string | null>(null);

  $effect(() => {
    if (branches.length > 0) {
      const active = branches.find((b) => b.id === selectedBranchId) || branches[0];
      if (active) {
        selectedBranchId = active.id;
        branchName = active.name;
        branchLatitude = active.lat;
        branchLongitude = active.lng;
        geofenceRadius = active.radius_meters || DEFAULT_GEOFENCE_RADIUS_METERS;
        latePenaltyRate = active.late_penalty_per_minute || DEFAULT_LATE_PENALTY_PER_MINUTE_IDR;
        overtimeHourlyRate = active.overtime_pay_per_hour || DEFAULT_OVERTIME_PAY_PER_HOUR_IDR;
        minOvertimeThreshold = active.min_overtime_threshold_minutes || DEFAULT_MIN_OVERTIME_THRESHOLD_MINUTES;
      }
    }
  });

  function selectBranchToEdit(id: string) {
    selectedBranchId = id;
    const b = branches.find((item) => item.id === id);
    if (b) {
      branchName = b.name;
      branchLatitude = b.lat;
      branchLongitude = b.lng;
      geofenceRadius = b.radius_meters || DEFAULT_GEOFENCE_RADIUS_METERS;
      latePenaltyRate = b.late_penalty_per_minute || DEFAULT_LATE_PENALTY_PER_MINUTE_IDR;
      overtimeHourlyRate = b.overtime_pay_per_hour || DEFAULT_OVERTIME_PAY_PER_HOUR_IDR;
      minOvertimeThreshold = b.min_overtime_threshold_minutes || DEFAULT_MIN_OVERTIME_THRESHOLD_MINUTES;
    }
  }

  function handleUseCurrentGPS() {
    if ('geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          branchLatitude = Number(pos.coords.latitude.toFixed(6));
          branchLongitude = Number(pos.coords.longitude.toFixed(6));
        },
        () => {
          branchErrorMsg = 'Gagal mendeteksi lokasi GPS dari browser. Silakan ketik koordinat manual.';
          setTimeout(() => (branchErrorMsg = null), 4000);
        },
        { enableHighAccuracy: true }
      );
    }
  }

  async function handleSaveBranch() {
    if (!selectedBranchId) return;
    isSavingBranch = true;
    branchSuccessMsg = null;
    branchErrorMsg = null;
    try {
      const res = await inventoryService.updateBranch(selectedBranchId, {
        name: branchName.trim(),
        lat: Number(branchLatitude),
        lng: Number(branchLongitude),
        radius_meters: Number(geofenceRadius),
        late_penalty_per_minute: Number(latePenaltyRate),
        overtime_pay_per_hour: Number(overtimeHourlyRate),
        min_overtime_threshold_minutes: Number(minOvertimeThreshold),
      });
      if (res) {
        branchSuccessMsg = 'Pengaturan cabang & geofence berhasil disimpan.';
        onBranchUpdated?.();
        setTimeout(() => (branchSuccessMsg = null), 3000);
      } else {
        branchErrorMsg = 'Gagal menyimpan pengaturan cabang.';
      }
    } catch (e: unknown) {
      branchErrorMsg = e instanceof Error ? e.message : 'Gagal menyimpan pengaturan cabang.';
    } finally {
      isSavingBranch = false;
    }
  }
</script>

<div class="bg-white border border-[#d9d9dd] rounded-[24px] p-6 space-y-6 font-sans">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#f2f2f2] pb-4">
    <div>
      <h2 class="text-base font-medium text-[#212121]">Pengaturan Cabang &amp; Presensi Geofence</h2>
      <p class="text-xs text-[#75758a]">Konfigurasi titik koordinat GPS Google Maps, radius presensi, dan tarif keterlambatan</p>
    </div>

    {#if branches.length > 0}
      <div class="flex items-center gap-2">
        <span class="text-xs text-[#75758a]">Pilih Cabang:</span>
        <div class="relative">
          <select
            value={selectedBranchId}
            onchange={(e) => selectBranchToEdit((e.target as HTMLSelectElement).value)}
            class="appearance-none px-3 pr-7 py-1.5 bg-[#eeece7]/50 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:outline-hidden cursor-pointer transition-all shadow-2xs"
          >
            {#each branches as b}
              <option value={b.id}>{b.name}</option>
            {/each}
          </select>
          <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>
      </div>
    {/if}
  </div>

  {#if branchSuccessMsg}
    <div class="p-3 bg-[#edfce9] border border-[#bbf7d0] rounded-xl text-xs font-medium text-[#003c33] flex items-center gap-2">
      <Check class="w-4 h-4 shrink-0" />
      <span>{branchSuccessMsg}</span>
    </div>
  {/if}

  {#if branchErrorMsg}
    <div class="p-3 bg-[#ffefef] border border-[#fecaca] rounded-xl text-xs font-medium text-[#e5484d]">
      {branchErrorMsg}
    </div>
  {/if}

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
    <div class="space-y-4">
      <div class="space-y-1.5">
        <label for="branch-name-input" class="block font-medium text-[#212121]">Nama Cabang Outlet</label>
        <input
          id="branch-name-input"
          type="text"
          bind:value={branchName}
          class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl text-[#212121] focus:border-[#17171c] focus:outline-hidden"
        />
      </div>

      <!-- GPS Coordinates & Google Maps Pinpoint Helper -->
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <label for="branch-lat" class="font-medium text-[#212121] flex items-center gap-1.5">
            <MapPin class="w-3.5 h-3.5 text-[#1863dc]" />
            <span>Titik Koordinat Outlet (Latitude, Longitude)</span>
          </label>
          <button
            type="button"
            onclick={handleUseCurrentGPS}
            class="text-[11px] text-[#1863dc] hover:underline flex items-center gap-1 cursor-pointer"
            title="Gunakan posisi GPS perangkat saat ini"
          >
            <LocateFixed class="w-3 h-3" />
            <span>Deteksi GPS Saya</span>
          </button>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <span class="text-[10px] text-[#75758a] font-mono">Latitude</span>
            <input
              id="branch-lat"
              type="number"
              step="0.000001"
              bind:value={branchLatitude}
              class="w-full px-3 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
          <div class="space-y-1">
            <span class="text-[10px] text-[#75758a] font-mono">Longitude</span>
            <input
              id="branch-lng"
              type="number"
              step="0.000001"
              bind:value={branchLongitude}
              class="w-full px-3 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
        </div>

        <!-- Google Maps Lookup Direct Button -->
        <div class="p-3 bg-[#f1f5ff] border border-[#d9d9dd] rounded-xl flex items-center justify-between">
          <div class="text-[11px] text-[#1863dc]">
            Periksa titik koordinat cabang di Google Maps
          </div>
          <a
            href={`https://www.google.com/maps/search/?api=1&query=${branchLatitude},${branchLongitude}`}
            target="_blank"
            rel="noreferrer"
            class="px-2.5 py-1 bg-white hover:bg-[#eeece7] border border-[#d9d9dd] text-[#1863dc] rounded-lg text-[11px] font-medium flex items-center gap-1"
          >
            <span>Buka GMaps</span>
            <ExternalLink class="w-3 h-3" />
          </a>
        </div>
      </div>

      <div class="space-y-1.5 pt-1">
        <div class="flex justify-between items-center">
          <label for="branch-radius" class="font-medium text-[#212121]">Radius Toleransi Presensi</label>
          <span class="font-mono font-medium text-[#17171c] bg-[#eeece7] px-2 py-0.5 rounded-md text-[11px]">
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
          class="w-full accent-[#17171c] cursor-pointer"
        />
        <span class="text-[10px] text-[#75758a]">Maksimal jarak staf melakukan selfie presensi valid dari lokasi outlet.</span>
      </div>
    </div>

    <div class="space-y-4">
      <div class="space-y-1.5">
        <label for="setting-penalty" class="block font-medium text-[#212121]">Tarif Denda Keterlambatan per Menit</label>
        <div class="relative">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-mono text-[#75758a] text-xs">Rp</span>
          <input
            id="setting-penalty"
            type="number"
            bind:value={latePenaltyRate}
            class="w-full pl-10 pr-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
        <span class="text-[10px] text-[#75758a]">Dikalikan dengan total menit keterlambatan saat kalkulasi slip gaji.</span>
      </div>

      <div class="space-y-1.5">
        <label for="setting-overtime" class="block font-medium text-[#212121]">Upah Lembur per Jam</label>
        <div class="relative">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-mono text-[#75758a] text-xs">Rp</span>
          <input
            id="setting-overtime"
            type="number"
            bind:value={overtimeHourlyRate}
            class="w-full pl-10 pr-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
        <span class="text-[10px] text-[#75758a]">Dihitung otomatis bila durasi shift melebihi jam kerja normal.</span>
      </div>

      <div class="space-y-1.5">
        <label for="setting-threshold" class="block font-medium text-[#212121]">Ambang Batas Minimal Lembur (Menit)</label>
        <input
          id="setting-threshold"
          type="number"
          bind:value={minOvertimeThreshold}
          class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
        />
        <span class="text-[10px] text-[#75758a]">Lembur dihitung setelah melewati batas toleransi menit ini.</span>
      </div>

      <div class="pt-3">
        <button
          type="button"
          onclick={handleSaveBranch}
          disabled={isSavingBranch}
          class="w-full py-2.5 bg-[#17171c] hover:bg-black text-white font-medium rounded-full transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5 shadow-xs"
        >
          <Save class="w-3.5 h-3.5" />
          <span>Simpan Pengaturan Cabang</span>
        </button>
      </div>
    </div>
  </div>
</div>
