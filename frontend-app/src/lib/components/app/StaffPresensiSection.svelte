<script lang="ts">
  import { onDestroy, onMount } from 'svelte';
  import {
    Camera,
    RotateCcw,
    Check,
    AlertCircle,
    CheckCircle2,
    ArrowLeft,
    RefreshCw,
    ShieldCheck,
    ShieldAlert,
    Navigation,
    Lock,
  } from 'lucide-svelte';
  import { burnWatermarkOnCanvas, type WatermarkResult } from '../../camera/watermark';
  import { attendanceService } from '../../services/attendance-service';
  import { inventoryService } from '../../services/inventory-service';
  import type { User, AttendanceRecord, BranchItem } from '../../types/app';

  interface Props {
    currentUser: User;
    todayAttendance: AttendanceRecord | null;
    branches?: BranchItem[];
    onSuccessAttendance?: (record: AttendanceRecord) => void;
    onNavigateHome?: () => void;
  }

  let {
    currentUser,
    todayAttendance,
    branches = [],
    onSuccessAttendance,
    onNavigateHome,
  }: Props = $props();

  let actionType = $state<'CLOCK_IN' | 'CLOCK_OUT'>('CLOCK_IN');

  // Auto-select CLOCK_OUT if already clocked in but not clocked out
  $effect(() => {
    if (todayAttendance && !todayAttendance.clock_out_time) {
      actionType = 'CLOCK_OUT';
    } else {
      actionType = 'CLOCK_IN';
    }
  });

  let loadedBranches = $state<BranchItem[]>([]);

  // Active Store / Branch Resolution
  let activeBranch = $derived.by(() => {
    const list = branches.length > 0 ? branches : loadedBranches;
    if (currentUser.branch_id) {
      const found = list.find((b) => b.id === currentUser.branch_id);
      if (found) return found;
    }
    if (list.length > 0) return list[0];

    // Default fallback coordinates (Seturan Outlet)
    return {
      id: currentUser.branch_id || 'branch-default',
      workspace_id: '',
      name: currentUser.branch_name || 'Outlet Cabang',
      lat: -7.76543,
      lng: 110.40912,
      radius_meters: 50,
      late_penalty_per_minute: 1000,
      overtime_pay_per_hour: 20000,
      min_overtime_threshold_minutes: 30,
    } as BranchItem;
  });

  let videoElement = $state<HTMLVideoElement | null>(null);
  let stream = $state<MediaStream | null>(null);
  let isCameraActive = $state(false);
  let facingMode = $state<'user' | 'environment'>('user');

  let capturedPhotoUrl = $state<string | null>(null);
  let capturedBlob = $state<Blob | null>(null);
  let isCapturing = $state(false);
  let isSubmitting = $state(false);
  let errorMessage = $state<string | null>(null);
  let successRecord = $state<AttendanceRecord | null>(null);

  // Geolocation & Radius Verification State
  let latitude = $state<number | null>(null);
  let longitude = $state<number | null>(null);
  let isCheckingGps = $state(true);
  let gpsErrorMessage = $state<string | null>(null);
  let geoWatchId = $state<number | null>(null);

  // Haversine Distance Calculation
  function calculateDistanceMeters(lat1: number, lng1: number, lat2: number, lng2: number): number {
    const EARTH_RADIUS_METERS = 6371000.0;
    const toRad = (deg: number) => (deg * Math.PI) / 180;
    const latFrom = toRad(lat1);
    const lngFrom = toRad(lng1);
    const latTo = toRad(lat2);
    const lngTo = toRad(lng2);

    const latDelta = latTo - latFrom;
    const lngDelta = lngTo - lngFrom;

    const angle = 2 * Math.asin(
      Math.sqrt(
        Math.sin(latDelta / 2) ** 2 +
        Math.cos(latFrom) * Math.cos(latTo) * Math.sin(lngDelta / 2) ** 2
      )
    );

    return Math.round(angle * EARTH_RADIUS_METERS * 100) / 100;
  }

  let distanceMeters = $derived.by(() => {
    if (latitude === null || longitude === null || !activeBranch) return null;
    return calculateDistanceMeters(
      Number(activeBranch.lat),
      Number(activeBranch.lng),
      latitude,
      longitude
    );
  });

  let isInsideRadius = $derived.by(() => {
    if (distanceMeters === null || !activeBranch) return false;
    return distanceMeters <= Number(activeBranch.radius_meters);
  });

  // Live WIB Clock
  let liveTimestamp = $state('');
  let timeInterval: ReturnType<typeof setInterval> | null = null;

  async function loadBranchData() {
    if (loadedBranches.length === 0) {
      try {
        const list = await inventoryService.getBranches();
        if (list && list.length > 0) {
          loadedBranches = list;
        }
      } catch {
        // fallback
      }
    }
  }

  onMount(() => {
    loadBranchData();
    detectGps();

    const updateTime = () => {
      const now = new Date();
      liveTimestamp = now.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      }) + ' WIB';
    };
    updateTime();
    timeInterval = setInterval(updateTime, 1000);
  });

  onDestroy(() => {
    stopCamera();
    if (geoWatchId !== null && typeof navigator !== 'undefined' && 'geolocation' in navigator) {
      navigator.geolocation.clearWatch(geoWatchId);
    }
    if (timeInterval) clearInterval(timeInterval);
  });

  // Effect to automatically start or stop camera based on radius validation
  $effect(() => {
    if (isInsideRadius && !capturedPhotoUrl && !successRecord) {
      if (!isCameraActive && !stream) {
        startCamera();
      }
    } else {
      if (isCameraActive || stream) {
        stopCamera();
      }
    }
  });

  function detectGps() {
    isCheckingGps = true;
    gpsErrorMessage = null;

    if (typeof navigator === 'undefined' || !('geolocation' in navigator)) {
      isCheckingGps = false;
      gpsErrorMessage = 'Perangkat Anda tidak mendukung fitur geolokasi GPS.';
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        latitude = pos.coords.latitude;
        longitude = pos.coords.longitude;
        isCheckingGps = false;
        gpsErrorMessage = null;
      },
      (err) => {
        isCheckingGps = false;
        if (err.code === 1) {
          gpsErrorMessage = 'Izin akses lokasi (GPS) ditolak. Aktifkan izin lokasi pada browser/perangkat Anda.';
        } else if (err.code === 2) {
          gpsErrorMessage = 'Lokasi GPS tidak dapat diperoleh. Pastikan GPS perangkat Anda aktif.';
        } else {
          gpsErrorMessage = 'Waktu permintaan lokasi habis. Silakan coba perbarui kembali.';
        }
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );

    // Watch position in real-time
    if (geoWatchId !== null) {
      navigator.geolocation.clearWatch(geoWatchId);
    }
    geoWatchId = navigator.geolocation.watchPosition(
      (pos) => {
        latitude = pos.coords.latitude;
        longitude = pos.coords.longitude;
        isCheckingGps = false;
        gpsErrorMessage = null;
      },
      () => {},
      { enableHighAccuracy: true, maximumAge: 3000 }
    );
  }

  async function startCamera() {
    if (!isInsideRadius) return;
    stopCamera();
    errorMessage = null;
    try {
      if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        stream = await navigator.mediaDevices.getUserMedia({
          video: {
            facingMode,
            width: { ideal: 720 },
            height: { ideal: 960 },
          },
          audio: false,
        });

        if (videoElement) {
          videoElement.srcObject = stream;
          await videoElement.play();
          isCameraActive = true;
        }
      }
    } catch {
      isCameraActive = false;
    }
  }

  function stopCamera() {
    if (stream) {
      stream.getTracks().forEach((track) => track.stop());
      stream = null;
      isCameraActive = false;
    }
    if (videoElement) {
      videoElement.srcObject = null;
    }
  }

  function toggleCameraFlip() {
    facingMode = facingMode === 'user' ? 'environment' : 'user';
    startCamera();
  }

  async function handleTakeSnapshot() {
    if (isCapturing || !isInsideRadius) return;
    isCapturing = true;
    errorMessage = null;

    try {
      let canvas: HTMLCanvasElement;
      const targetWidth = 720;
      const targetHeight = 960; // 3:4 portrait ratio

      if (videoElement && isCameraActive) {
        canvas = document.createElement('canvas');
        canvas.width = targetWidth;
        canvas.height = targetHeight;
        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Canvas context failure');

        // Draw cropped 3:4 from video source
        const vW = videoElement.videoWidth;
        const vH = videoElement.videoHeight;
        const videoRatio = vW / vH;
        const targetRatio = 3 / 4;

        let sX = 0, sY = 0, sW = vW, sH = vH;
        if (videoRatio > targetRatio) {
          sW = vH * targetRatio;
          sX = (vW - sW) / 2;
        } else {
          sH = vW / targetRatio;
          sY = (vH - sH) / 2;
        }

        if (facingMode === 'user') {
          ctx.translate(targetWidth, 0);
          ctx.scale(-1, 1);
        }

        ctx.drawImage(videoElement, sX, sY, sW, sH, 0, 0, targetWidth, targetHeight);
      } else {
        // Fallback canvas if hardware camera is not available in mock/testing
        canvas = document.createElement('canvas');
        canvas.width = targetWidth;
        canvas.height = targetHeight;
        const ctx = canvas.getContext('2d');
        if (ctx) {
          ctx.fillStyle = '#1e1e24';
          ctx.fillRect(0, 0, targetWidth, targetHeight);

          ctx.fillStyle = '#ffffff';
          ctx.font = 'bold 24px sans-serif';
          ctx.textAlign = 'center';
          ctx.fillText(`SELFIE PRESENSI (${currentUser.name})`, targetWidth / 2, targetHeight / 2 - 20);

          ctx.font = '16px monospace';
          ctx.fillStyle = '#93939f';
          ctx.fillText('CAMERA SIMULATOR PREVIEW', targetWidth / 2, targetHeight / 2 + 20);
        }
      }

      // Burn Cryptographic Anti-Spoofing Watermark (WIB Timestamp + GPS Coordinates + User ID)
      const watermarkResult: WatermarkResult = await burnWatermarkOnCanvas(
        canvas,
        liveTimestamp,
        latitude || 0,
        longitude || 0,
        currentUser.branch_name,
        facingMode === 'user'
      );

      capturedPhotoUrl = watermarkResult.dataUrl;
      capturedBlob = watermarkResult.blob;
      stopCamera();
    } catch (e: unknown) {
      errorMessage = e instanceof Error ? e.message : 'Gagal mengambil foto presensi.';
    } finally {
      isCapturing = false;
    }
  }

  function handleRetake() {
    capturedPhotoUrl = null;
    capturedBlob = null;
    errorMessage = null;
    if (isInsideRadius) {
      startCamera();
    }
  }

  async function handleSubmitAttendance() {
    if (!capturedPhotoUrl || !capturedBlob) {
      errorMessage = 'Ambil foto selfie presensi terlebih dahulu.';
      return;
    }

    if (!isInsideRadius || latitude === null || longitude === null) {
      errorMessage = 'Presensi ditolak. Anda berada di luar radius toko.';
      return;
    }

    isSubmitting = true;
    errorMessage = null;

    try {
      const targetBranchId = activeBranch?.id || currentUser.branch_id || 'branch-default';
      const filename = `selfie_${currentUser.id}_${Date.now()}.webp`;
      const presignData = await attendanceService.presignUpload(
        filename,
        'image/webp',
        capturedBlob.size
      );

      await attendanceService.uploadBinaryToStorage(
        presignData.upload_url,
        capturedBlob,
        'image/webp'
      );

      const photoUrl = presignData.public_url || presignData.key || capturedPhotoUrl;
      const now = new Date();
      const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

      let record: AttendanceRecord;
      if (actionType === 'CLOCK_IN') {
        const clockInData = await attendanceService.clockIn(
          targetBranchId,
          latitude,
          longitude,
          photoUrl
        );

        record = {
          id: clockInData.id,
          user_id: currentUser.id,
          user_name: currentUser.name,
          avatar_url: currentUser.avatar_url || '',
          branch_name: currentUser.branch_name || activeBranch?.name || '',
          shift_name: 'Shift Hari Ini',
          clock_in_time: `${timeStr} WIB`,
          photo_in_url: clockInData.photo_in_url || capturedPhotoUrl,
          lat_in: latitude,
          lng_in: longitude,
          status: clockInData.status || 'ON_TIME',
          late_minutes: clockInData.late_minutes || 0,
          created_at: now.toISOString(),
        };
      } else {
        const clockOutData = await attendanceService.clockOut(
          targetBranchId,
          latitude,
          longitude,
          photoUrl
        );

        record = {
          id: clockOutData.id,
          user_id: currentUser.id,
          user_name: currentUser.name,
          avatar_url: currentUser.avatar_url || '',
          branch_name: currentUser.branch_name || activeBranch?.name || '',
          shift_name: 'Shift Hari Ini',
          clock_in_time: todayAttendance?.clock_in_time || `${timeStr} WIB`,
          clock_out_time: `${timeStr} WIB`,
          photo_in_url: todayAttendance?.photo_in_url || capturedPhotoUrl,
          photo_out_url: clockOutData.photo_out_url || capturedPhotoUrl,
          lat_in: latitude,
          lng_in: longitude,
          status: todayAttendance?.status || 'ON_TIME',
          late_minutes: todayAttendance?.late_minutes || 0,
          overtime_minutes: clockOutData.overtime_minutes || 0,
          created_at: now.toISOString(),
        };
      }

      successRecord = record;
      onSuccessAttendance?.(record);
    } catch (e: unknown) {
      errorMessage = e instanceof Error ? e.message : 'Gagal mengirim presensi ke server.';
    } finally {
      isSubmitting = false;
    }
  }
</script>

<div class="max-w-md mx-auto font-sans pb-8 space-y-4">
  <!-- Top Navigation & Action Mode Selector -->
  <div class="flex items-center justify-between gap-2 px-1">
    <div class="flex items-center gap-3">
      {#if onNavigateHome}
        <button
          type="button"
          onclick={onNavigateHome}
          class="p-2.5 rounded-2xl bg-white border border-[#e5e5ea] hover:bg-[#f4f4f6] text-[#17171c] cursor-pointer transition-all shadow-2xs"
          title="Kembali ke Home"
        >
          <ArrowLeft class="w-4 h-4" />
        </button>
      {/if}
      <div>
        <h1 class="text-base font-bold text-[#17171c]">Kamera Presensi</h1>
        <p class="text-xs font-mono text-[#8e8e93]">{activeBranch.name}</p>
      </div>
    </div>

    <!-- Mode Selector: Clock-In vs Clock-Out -->
    <div class="flex items-center p-1 bg-[#f4f4f6] rounded-full border border-[#e5e5ea] text-xs font-mono font-semibold">
      <button
        type="button"
        onclick={() => (actionType = 'CLOCK_IN')}
        class={`px-3 py-1.5 rounded-full transition-all cursor-pointer ${
          actionType === 'CLOCK_IN' ? 'bg-[#059669] text-white shadow-xs' : 'text-[#686873] hover:text-[#17171c]'
        }`}
      >
        Masuk
      </button>
      <button
        type="button"
        onclick={() => (actionType = 'CLOCK_OUT')}
        class={`px-3 py-1.5 rounded-full transition-all cursor-pointer ${
          actionType === 'CLOCK_OUT' ? 'bg-[#e5484d] text-white shadow-xs' : 'text-[#686873] hover:text-[#17171c]'
        }`}
      >
        Keluar
      </button>
    </div>
  </div>

  {#if successRecord}
    <!-- Success Celebration View -->
    <div class="bg-white border border-[#a7f3d0] rounded-3xl p-6 sm:p-8 text-center space-y-5 shadow-xl animate-in zoom-in-95">
      <div class="w-16 h-16 rounded-3xl bg-[#ecfdf5] text-[#059669] flex items-center justify-center mx-auto border border-[#a7f3d0]">
        <CheckCircle2 class="w-8 h-8" />
      </div>

      <div class="space-y-1">
        <h2 class="text-lg font-bold text-[#17171c]">
          {actionType === 'CLOCK_IN' ? 'Presensi Masuk Berhasil!' : 'Presensi Keluar Berhasil!'}
        </h2>
        <p class="text-xs text-[#8e8e93]">
          Tercatat pada <strong class="font-mono text-[#17171c]">{successRecord.clock_in_time || liveTimestamp}</strong>
        </p>
      </div>

      {#if capturedPhotoUrl}
        <div class="w-36 aspect-[3/4] rounded-2xl overflow-hidden border border-[#e5e5ea] mx-auto shadow-sm bg-[#17171c]">
          <img src={capturedPhotoUrl} alt="Selfie Presensi" class="w-full h-full object-cover" />
        </div>
      {/if}

      <div class="pt-2 flex flex-col gap-2.5">
        {#if onNavigateHome}
          <button
            type="button"
            onclick={onNavigateHome}
            class="w-full py-3 bg-[#17171c] hover:bg-black text-white font-semibold text-xs rounded-full cursor-pointer transition-all shadow-xs"
          >
            Kembali ke Dashboard Home
          </button>
        {/if}
        <button
          type="button"
          onclick={() => {
            successRecord = null;
            capturedPhotoUrl = null;
            capturedBlob = null;
            if (isInsideRadius) {
              startCamera();
            }
          }}
          class="w-full py-2.5 text-xs font-semibold border border-[#e5e5ea] text-[#686873] hover:bg-[#f4f4f6] rounded-full cursor-pointer transition-all"
        >
          Ambil Foto Baru
        </button>
      </div>
    </div>
  {:else if isCheckingGps}
    <!-- GPS Loading Screen -->
    <div class="bg-white border border-[#e5e5ea] rounded-3xl p-8 text-center space-y-4 shadow-2xs">
      <div class="w-14 h-14 rounded-2xl bg-[#f4f4f6] text-[#17171c] flex items-center justify-center mx-auto">
        <Navigation class="w-6 h-6 animate-spin text-[#1863dc]" />
      </div>
      <div class="space-y-1">
        <h3 class="text-sm font-bold text-[#17171c]">Memverifikasi Lokasi GPS Toko</h3>
        <p class="text-xs text-[#8e8e93]">
          Menghitung radius jarak Anda ke {activeBranch.name}...
        </p>
      </div>
    </div>
  {:else if gpsErrorMessage}
    <!-- GPS Error / Permission Denied Guard Screen -->
    <div class="bg-white border border-[#fecaca] rounded-3xl p-7 text-center space-y-5 shadow-2xs">
      <div class="w-14 h-14 rounded-2xl bg-[#fef2f2] text-[#e5484d] flex items-center justify-center mx-auto border border-[#fecaca]">
        <Lock class="w-6 h-6" />
      </div>
      <div class="space-y-1.5">
        <h3 class="text-sm font-bold text-[#17171c]">Izin Lokasi (GPS) Diperlukan</h3>
        <p class="text-xs text-[#8e8e93] leading-relaxed">
          {gpsErrorMessage}
        </p>
      </div>

      <button
        type="button"
        onclick={detectGps}
        class="w-full py-2.5 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all flex items-center justify-center gap-1.5 shadow-xs"
      >
        <RefreshCw class="w-3.5 h-3.5" />
        <span>Deteksi Ulang GPS</span>
      </button>
    </div>
  {:else if !isInsideRadius}
    <!-- CAMERA ACCESS BLOCKED: OUTSIDE GEOFENCE RADIUS -->
    <div class="bg-white border border-[#fecaca] rounded-3xl p-6 sm:p-7 space-y-5 shadow-2xs animate-in fade-in">
      <div class="text-center space-y-3">
        <div class="w-16 h-16 rounded-3xl bg-[#fef2f2] text-[#e5484d] flex items-center justify-center mx-auto border border-[#fecaca] shadow-xs">
          <ShieldAlert class="w-8 h-8" />
        </div>

        <div class="space-y-1">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#fef2f2] text-[#dc2626] border border-[#fecaca] text-[10.5px] font-mono font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-[#dc2626] animate-ping"></span>
            DI LUAR RADIUS TOKO
          </span>
          <h2 class="text-base font-bold text-[#17171c] pt-1">Akses Kamera Terkunci</h2>
          <p class="text-xs text-[#8e8e93] leading-relaxed">
            Kamera presensi tidak dapat diakses karena Anda berada di luar radius toko yang ditentukan.
          </p>
        </div>
      </div>

      <!-- Distance & Coordinates Comparison Box -->
      <div class="bg-[#f8f8fa] border border-[#e5e5ea] rounded-2xl p-4 space-y-3 text-xs">
        <div class="flex items-center justify-between pb-2.5 border-b border-[#ececee]">
          <span class="text-[#8e8e93]">Toko Penugasan</span>
          <strong class="text-[#17171c]">{activeBranch.name}</strong>
        </div>

        <div class="flex items-center justify-between pb-2.5 border-b border-[#ececee]">
          <span class="text-[#8e8e93]">Batas Radius Diizinkan</span>
          <span class="font-mono font-bold text-[#059669]">{activeBranch.radius_meters} meter</span>
        </div>

        <div class="flex items-center justify-between pb-2.5 border-b border-[#ececee]">
          <span class="text-[#8e8e93]">Jarak Anda Saat Ini</span>
          <span class="font-mono font-bold text-[#dc2626]">
            {distanceMeters !== null ? `${Math.round(distanceMeters)} meter` : 'Tidak terdeteksi'}
          </span>
        </div>

        <div class="flex items-center justify-between text-[11px] font-mono text-[#8e8e93]">
          <span>Koordinat Anda:</span>
          <span>{latitude?.toFixed(5)}, {longitude?.toFixed(5)}</span>
        </div>
      </div>

      <div class="space-y-2 pt-1">
        <button
          type="button"
          onclick={detectGps}
          class="w-full py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all flex items-center justify-center gap-2 shadow-xs"
        >
          <RefreshCw class="w-3.5 h-3.5" />
          <span>Perbarui Posisi GPS</span>
        </button>

        {#if onNavigateHome}
          <button
            type="button"
            onclick={onNavigateHome}
            class="w-full py-2.5 border border-[#e5e5ea] hover:bg-[#f4f4f6] text-[#686873] text-xs font-semibold rounded-full cursor-pointer transition-all"
          >
            Kembali ke Beranda
          </button>
        {/if}
      </div>
    </div>
  {:else}
    <!-- CAMERA ACCESS GRANTED: INSIDE RADIUS -->
    <!-- 3:4 Camera Viewfinder Container -->
    <div class="relative aspect-[3/4] w-full bg-black rounded-3xl overflow-hidden shadow-2xl border border-[#e5e5ea] flex flex-col justify-between p-4 select-none">
      {#if !capturedPhotoUrl}
        <!-- Live Video Element -->
        <video
          bind:this={videoElement}
          autoplay
          playsinline
          muted
          class={`absolute inset-0 w-full h-full object-cover ${facingMode === 'user' ? 'scale-x-[-1]' : ''}`}
        ></video>

        {#if !isCameraActive}
          <!-- Video Stream Fallback Graphic -->
          <div class="absolute inset-0 flex flex-col items-center justify-center bg-[#17171c] text-white p-6 text-center space-y-2">
            <Camera class="w-12 h-12 text-[#a1a1aa] animate-pulse" />
            <div class="text-xs font-semibold">{currentUser.name}</div>
            <div class="text-[10px] text-[#a1a1aa] font-mono">[KAMERA AKTIF: SIAP JEPRET SELFIE]</div>
          </div>
        {/if}
      {:else}
        <!-- Captured Snapshot Preview -->
        <img
          src={capturedPhotoUrl}
          alt="Snapshot Preview"
          class="absolute inset-0 w-full h-full object-cover"
        />
      {/if}

      <!-- Top Overlay: Live GPS status & Camera Flip -->
      <div class="relative z-10 flex items-center justify-between text-white text-xs">
        <div class="flex items-center gap-1.5 bg-black/60 backdrop-blur-md px-3 py-1 rounded-full border border-white/10 text-[10px] font-mono">
          <ShieldCheck class="w-3.5 h-3.5 text-[#34d399]" />
          <span>DI DALAM RADIUS ({distanceMeters !== null ? Math.round(distanceMeters) : 0}m / maks {activeBranch.radius_meters}m)</span>
        </div>

        {#if !capturedPhotoUrl}
          <button
            type="button"
            onclick={toggleCameraFlip}
            class="p-2.5 rounded-full bg-black/60 backdrop-blur-md border border-white/10 text-white hover:bg-black cursor-pointer transition-all"
            title="Putar Kamera"
          >
            <RotateCcw class="w-4 h-4" />
          </button>
        {/if}
      </div>

      <!-- Center Watermark Overlay (Simulated Live) -->
      <div class="relative z-10 pointer-events-none self-start">
        <div class="bg-black/50 backdrop-blur-xs p-2.5 rounded-2xl text-white font-mono text-[9.5px] space-y-0.5 border border-white/10 max-w-56">
          <div class="font-bold text-white truncate">{currentUser.name}</div>
          <div class="text-white/80">{liveTimestamp}</div>
          <div class="text-[#a7f3d0] truncate">{latitude?.toFixed(5)}, {longitude?.toFixed(5)}</div>
        </div>
      </div>

      <!-- Bottom Control Bar -->
      <div class="relative z-10 flex flex-col items-center gap-3">
        {#if errorMessage}
          <div class="bg-[#fef2f2]/90 backdrop-blur-xs text-[#991b1b] text-xs font-semibold px-4 py-2 rounded-full border border-[#fecaca] flex items-center gap-2">
            <AlertCircle class="w-4 h-4 shrink-0" />
            <span>{errorMessage}</span>
          </div>
        {/if}

        {#if !capturedPhotoUrl}
          <!-- Shutter Button -->
          <div class="flex items-center justify-center pb-2">
            <button
              type="button"
              onclick={handleTakeSnapshot}
              disabled={isCapturing || !isInsideRadius}
              class="w-20 h-20 rounded-full border-4 border-white bg-white/30 backdrop-blur-xs p-1.5 flex items-center justify-center active:scale-95 transition-all cursor-pointer group shadow-xl"
              title="Ambil Foto Selfie"
            >
              <div class="w-full h-full rounded-full bg-white group-hover:bg-[#f4f4f6] transition-colors flex items-center justify-center shadow-inner">
                <Camera class="w-7 h-7 text-[#17171c]" />
              </div>
            </button>
          </div>
        {:else}
          <!-- Preview Confirmation Actions -->
          <div class="w-full flex items-center gap-3 pb-2">
            <button
              type="button"
              onclick={handleRetake}
              disabled={isSubmitting}
              class="flex-1 py-3 bg-black/70 hover:bg-black text-white text-xs font-semibold rounded-2xl border border-white/20 backdrop-blur-md cursor-pointer transition-all text-center"
            >
              Ulangi Foto
            </button>
            <button
              type="button"
              onclick={handleSubmitAttendance}
              disabled={isSubmitting}
              class="flex-1 py-3 bg-[#059669] hover:bg-[#047857] text-white text-xs font-semibold rounded-2xl shadow-xl cursor-pointer transition-all flex items-center justify-center gap-2"
            >
              {#if isSubmitting}
                <RefreshCw class="w-4 h-4 animate-spin" />
                <span>Mengirim...</span>
              {:else}
                <Check class="w-4 h-4" />
                <span>Kirim Presensi</span>
              {/if}
            </button>
          </div>
        {/if}
      </div>
    </div>
  {/if}
</div>
