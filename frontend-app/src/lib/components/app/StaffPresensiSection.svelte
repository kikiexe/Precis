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

    const angle =
      2 *
      Math.asin(
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
      liveTimestamp =
        now.toLocaleDateString('id-ID', {
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
          gpsErrorMessage =
            'Izin akses lokasi (GPS) ditolak. Aktifkan izin lokasi pada browser/perangkat Anda.';
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

        let sX = 0,
          sY = 0,
          sW = vW,
          sH = vH;
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
          ctx.fillText(
            `SELFIE PRESENSI (${currentUser.name})`,
            targetWidth / 2,
            targetHeight / 2 - 20
          );

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

<div class="mx-auto max-w-md space-y-4 pb-8 font-sans">
  <!-- Top Navigation & Action Mode Selector -->
  <div class="flex items-center justify-between gap-2 px-1">
    <div class="flex items-center gap-3">
      {#if onNavigateHome}
        <button
          type="button"
          onclick={onNavigateHome}
          class="cursor-pointer rounded-2xl border border-[#e5e5ea] bg-white p-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-[#f4f4f6]"
          title="Kembali ke Home"
        >
          <ArrowLeft class="size-4" />
        </button>
      {/if}
      <div>
        <h1 class="text-base font-bold text-[#17171c]">Kamera Presensi</h1>
        <p class="font-mono text-xs text-[#8e8e93]">{activeBranch.name}</p>
      </div>
    </div>

    <!-- Mode Selector: Clock-In vs Clock-Out -->
    <div
      class="flex items-center rounded-full border border-[#e5e5ea] bg-[#f4f4f6] p-1 font-mono text-xs font-semibold"
    >
      <button
        type="button"
        onclick={() => (actionType = 'CLOCK_IN')}
        class={`cursor-pointer rounded-full px-3 py-1.5 transition-all ${
          actionType === 'CLOCK_IN'
            ? 'bg-[#059669] text-white shadow-xs'
            : 'text-[#686873] hover:text-[#17171c]'
        }`}
      >
        Masuk
      </button>
      <button
        type="button"
        onclick={() => (actionType = 'CLOCK_OUT')}
        class={`cursor-pointer rounded-full px-3 py-1.5 transition-all ${
          actionType === 'CLOCK_OUT'
            ? 'bg-[#e5484d] text-white shadow-xs'
            : 'text-[#686873] hover:text-[#17171c]'
        }`}
      >
        Keluar
      </button>
    </div>
  </div>

  {#if successRecord}
    <!-- Success Celebration View -->
    <div
      class="animate-in zoom-in-95 space-y-5 rounded-3xl border border-[#a7f3d0] bg-white p-6 text-center shadow-xl sm:p-8"
    >
      <div
        class="mx-auto flex size-16 items-center justify-center rounded-3xl border border-[#a7f3d0] bg-[#ecfdf5] text-[#059669]"
      >
        <CheckCircle2 class="size-8" />
      </div>

      <div class="space-y-1">
        <h2 class="text-lg font-bold text-[#17171c]">
          {actionType === 'CLOCK_IN' ? 'Presensi Masuk Berhasil!' : 'Presensi Keluar Berhasil!'}
        </h2>
        <p class="text-xs text-[#8e8e93]">
          Tercatat pada <strong class="font-mono text-[#17171c]"
            >{successRecord.clock_in_time || liveTimestamp}</strong
          >
        </p>
      </div>

      {#if capturedPhotoUrl}
        <div
          class="mx-auto aspect-3/4 w-36 overflow-hidden rounded-2xl border border-[#e5e5ea] bg-[#17171c] shadow-sm"
        >
          <img src={capturedPhotoUrl} alt="Selfie Presensi" class="size-full object-cover" />
        </div>
      {/if}

      <div class="flex flex-col gap-2.5 pt-2">
        {#if onNavigateHome}
          <button
            type="button"
            onclick={onNavigateHome}
            class="w-full cursor-pointer rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
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
          class="w-full cursor-pointer rounded-full border border-[#e5e5ea] py-2.5 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
        >
          Ambil Foto Baru
        </button>
      </div>
    </div>
  {:else if isCheckingGps}
    <!-- GPS Loading Screen -->
    <div class="space-y-4 rounded-3xl border border-[#e5e5ea] bg-white p-8 text-center shadow-2xs">
      <div
        class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-[#f4f4f6] text-[#17171c]"
      >
        <Navigation class="size-6 animate-spin text-[#1863dc]" />
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
    <div class="space-y-5 rounded-3xl border border-[#fecaca] bg-white p-7 text-center shadow-2xs">
      <div
        class="mx-auto flex size-14 items-center justify-center rounded-2xl border border-[#fecaca] bg-[#fef2f2] text-[#e5484d]"
      >
        <Lock class="size-6" />
      </div>
      <div class="space-y-1.5">
        <h3 class="text-sm font-bold text-[#17171c]">Izin Lokasi (GPS) Diperlukan</h3>
        <p class="text-xs leading-relaxed text-[#8e8e93]">
          {gpsErrorMessage}
        </p>
      </div>

      <button
        type="button"
        onclick={detectGps}
        class="flex w-full cursor-pointer items-center justify-center gap-1.5 rounded-full bg-[#17171c] py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
      >
        <RefreshCw class="size-3.5" />
        <span>Deteksi Ulang GPS</span>
      </button>
    </div>
  {:else if !isInsideRadius}
    <!-- CAMERA ACCESS BLOCKED: OUTSIDE GEOFENCE RADIUS -->
    <div
      class="animate-in fade-in space-y-5 rounded-3xl border border-[#fecaca] bg-white p-6 shadow-2xs sm:p-7"
    >
      <div class="space-y-3 text-center">
        <div
          class="mx-auto flex size-16 items-center justify-center rounded-3xl border border-[#fecaca] bg-[#fef2f2] text-[#e5484d] shadow-xs"
        >
          <ShieldAlert class="size-8" />
        </div>

        <div class="space-y-1">
          <span
            class="inline-flex items-center gap-1.5 rounded-full border border-[#fecaca] bg-[#fef2f2] px-3 py-1 font-mono text-[10.5px] font-bold text-[#dc2626]"
          >
            <span class="size-1.5 animate-ping rounded-full bg-[#dc2626]"></span>
            DI LUAR RADIUS TOKO
          </span>
          <h2 class="pt-1 text-base font-bold text-[#17171c]">Akses Kamera Terkunci</h2>
          <p class="text-xs leading-relaxed text-[#8e8e93]">
            Kamera presensi tidak dapat diakses karena Anda berada di luar radius toko yang
            ditentukan.
          </p>
        </div>
      </div>

      <!-- Distance & Coordinates Comparison Box -->
      <div class="space-y-3 rounded-2xl border border-[#e5e5ea] bg-[#f8f8fa] p-4 text-xs">
        <div class="flex items-center justify-between border-b border-[#ececee] pb-2.5">
          <span class="text-[#8e8e93]">Toko Penugasan</span>
          <strong class="text-[#17171c]">{activeBranch.name}</strong>
        </div>

        <div class="flex items-center justify-between border-b border-[#ececee] pb-2.5">
          <span class="text-[#8e8e93]">Batas Radius Diizinkan</span>
          <span class="font-mono font-bold text-[#059669]">{activeBranch.radius_meters} meter</span>
        </div>

        <div class="flex items-center justify-between border-b border-[#ececee] pb-2.5">
          <span class="text-[#8e8e93]">Jarak Anda Saat Ini</span>
          <span class="font-mono font-bold text-[#dc2626]">
            {distanceMeters !== null ? `${Math.round(distanceMeters)} meter` : 'Tidak terdeteksi'}
          </span>
        </div>

        <div class="flex items-center justify-between font-mono text-[11px] text-[#8e8e93]">
          <span>Koordinat Anda:</span>
          <span>{latitude?.toFixed(5)}, {longitude?.toFixed(5)}</span>
        </div>
      </div>

      <div class="space-y-2 pt-1">
        <button
          type="button"
          onclick={detectGps}
          class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
        >
          <RefreshCw class="size-3.5" />
          <span>Perbarui Posisi GPS</span>
        </button>

        {#if onNavigateHome}
          <button
            type="button"
            onclick={onNavigateHome}
            class="w-full cursor-pointer rounded-full border border-[#e5e5ea] py-2.5 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
          >
            Kembali ke Beranda
          </button>
        {/if}
      </div>
    </div>
  {:else}
    <!-- CAMERA ACCESS GRANTED: INSIDE RADIUS -->
    <!-- 3:4 Camera Viewfinder Container -->
    <div
      class="relative flex aspect-3/4 w-full flex-col justify-between overflow-hidden rounded-3xl border border-[#e5e5ea] bg-black p-4 shadow-2xl select-none"
    >
      {#if !capturedPhotoUrl}
        <!-- Live Video Element -->
        <video
          bind:this={videoElement}
          autoplay
          playsinline
          muted
          class={`absolute inset-0 size-full object-cover ${facingMode === 'user' ? '-scale-x-1' : ''}`}
        ></video>

        {#if !isCameraActive}
          <!-- Video Stream Fallback Graphic -->
          <div
            class="absolute inset-0 flex flex-col items-center justify-center space-y-2 bg-[#17171c] p-6 text-center text-white"
          >
            <Camera class="size-12 animate-pulse text-[#a1a1aa]" />
            <div class="text-xs font-semibold">{currentUser.name}</div>
            <div class="font-mono text-[10px] text-[#a1a1aa]">
              [KAMERA AKTIF: SIAP JEPRET SELFIE]
            </div>
          </div>
        {/if}
      {:else}
        <!-- Captured Snapshot Preview -->
        <img
          src={capturedPhotoUrl}
          alt="Snapshot Preview"
          class="absolute inset-0 size-full object-cover"
        />
      {/if}

      <!-- Top Overlay: Live GPS status & Camera Flip -->
      <div class="relative z-10 flex items-center justify-between text-xs text-white">
        <div
          class="flex items-center gap-1.5 rounded-full border border-white/10 bg-black/60 px-3 py-1 font-mono text-[10px] backdrop-blur-md"
        >
          <ShieldCheck class="size-3.5 text-[#34d399]" />
          <span
            >DI DALAM RADIUS ({distanceMeters !== null ? Math.round(distanceMeters) : 0}m / maks {activeBranch.radius_meters}m)</span
          >
        </div>

        {#if !capturedPhotoUrl}
          <button
            type="button"
            onclick={toggleCameraFlip}
            class="cursor-pointer rounded-full border border-white/10 bg-black/60 p-2.5 text-white backdrop-blur-md transition-all hover:bg-black"
            title="Putar Kamera"
          >
            <RotateCcw class="size-4" />
          </button>
        {/if}
      </div>

      <!-- Center Watermark Overlay (Simulated Live) -->
      <div class="pointer-events-none relative z-10 self-start">
        <div
          class="max-w-56 space-y-0.5 rounded-2xl border border-white/10 bg-black/50 p-2.5 font-mono text-[9.5px] text-white backdrop-blur-xs"
        >
          <div class="truncate font-bold text-white">{currentUser.name}</div>
          <div class="text-white/80">{liveTimestamp}</div>
          <div class="truncate text-[#a7f3d0]">{latitude?.toFixed(5)}, {longitude?.toFixed(5)}</div>
        </div>
      </div>

      <!-- Bottom Control Bar -->
      <div class="relative z-10 flex flex-col items-center gap-3">
        {#if errorMessage}
          <div
            class="flex items-center gap-2 rounded-full border border-[#fecaca] bg-[#fef2f2]/90 px-4 py-2 text-xs font-semibold text-[#991b1b] backdrop-blur-xs"
          >
            <AlertCircle class="size-4 shrink-0" />
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
              class="group flex size-20 cursor-pointer items-center justify-center rounded-full border-4 border-white bg-white/30 p-1.5 shadow-xl backdrop-blur-xs transition-all active:scale-95"
              title="Ambil Foto Selfie"
            >
              <div
                class="flex size-full items-center justify-center rounded-full bg-white shadow-inner transition-colors group-hover:bg-[#f4f4f6]"
              >
                <Camera class="size-7 text-[#17171c]" />
              </div>
            </button>
          </div>
        {:else}
          <!-- Preview Confirmation Actions -->
          <div class="flex w-full items-center gap-3 pb-2">
            <button
              type="button"
              onclick={handleRetake}
              disabled={isSubmitting}
              class="flex-1 cursor-pointer rounded-2xl border border-white/20 bg-black/70 py-3 text-center text-xs font-semibold text-white backdrop-blur-md transition-all hover:bg-black"
            >
              Ulangi Foto
            </button>
            <button
              type="button"
              onclick={handleSubmitAttendance}
              disabled={isSubmitting}
              class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-[#059669] py-3 text-xs font-semibold text-white shadow-xl transition-all hover:bg-[#047857]"
            >
              {#if isSubmitting}
                <RefreshCw class="size-4 animate-spin" />
                <span>Mengirim...</span>
              {:else}
                <Check class="size-4" />
                <span>Kirim Presensi</span>
              {/if}
            </button>
          </div>
        {/if}
      </div>
    </div>
  {/if}
</div>
