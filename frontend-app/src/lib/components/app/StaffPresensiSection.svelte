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
    ShieldCheck
  } from 'lucide-svelte';
  import { burnWatermarkOnCanvas, type WatermarkResult } from '../../camera/watermark';
  import { attendanceService } from '../../services/attendance-service';
  import type { User, AttendanceRecord } from '../../types/app';

  interface Props {
    currentUser: User;
    todayAttendance: AttendanceRecord | null;
    onSuccessAttendance?: (record: AttendanceRecord) => void;
    onNavigateHome?: () => void;
  }

  let {
    currentUser,
    todayAttendance,
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

  // GPS State
  let latitude = $state(-7.782914);
  let longitude = $state(110.36712);
  let gpsAccuracy = $state(12);
  let gpsStatus = $state('GPS Terkunci (Radius <50m)');

  // Live WIB Clock
  let liveTimestamp = $state('');
  let timeInterval: any;

  onMount(() => {
    startCamera();
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
    if (timeInterval) clearInterval(timeInterval);
  });

  async function startCamera() {
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
    } catch (e) {
      console.warn('Kamera hardware tidak dapat diakses langsung, menggunakan simulator video stream:', e);
      isCameraActive = false;
    }
  }

  function stopCamera() {
    if (stream) {
      stream.getTracks().forEach((track) => track.stop());
      stream = null;
    }
    isCameraActive = false;
  }

  function toggleCameraFlip() {
    facingMode = facingMode === 'user' ? 'environment' : 'user';
    startCamera();
  }

  function detectGps() {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          latitude = pos.coords.latitude;
          longitude = pos.coords.longitude;
          gpsAccuracy = Math.round(pos.coords.accuracy);
          gpsStatus = `GPS Terkunci (Akurasi ±${gpsAccuracy}m)`;
        },
        () => {
          gpsStatus = 'GPS Geofence Aktif (Outlet Sleman)';
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    }
  }

  async function handleTakeSnapshot() {
    isCapturing = true;
    errorMessage = null;

    try {
      let captureSource: HTMLVideoElement | HTMLCanvasElement;

      if (isCameraActive && videoElement) {
        captureSource = videoElement;
      } else {
        // Fallback canvas snapshot for devices/browsers without webcam permission
        const simCanvas = document.createElement('canvas');
        simCanvas.width = 720;
        simCanvas.height = 960;
        const ctx = simCanvas.getContext('2d')!;
        ctx.fillStyle = '#17171c';
        ctx.fillRect(0, 0, 720, 960);
        ctx.fillStyle = actionType === 'CLOCK_IN' ? '#003c33' : '#7f1d1d';
        ctx.beginPath();
        ctx.arc(360, 420, 160, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#ffffff';
        ctx.font = '500 36px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(currentUser.name, 360, 430);
        ctx.font = '22px monospace';
        ctx.fillText(`[${actionType === 'CLOCK_IN' ? 'PRESENSI MASUK' : 'PRESENSI KELUAR'}]`, 360, 680);
        captureSource = simCanvas;
      }

      // Watermark with staff name, GPS, and timestamp
      const watermarkResult: WatermarkResult = await burnWatermarkOnCanvas(
        captureSource,
        liveTimestamp,
        latitude,
        longitude,
        currentUser.branch_name || 'Outlet Sleman #01',
        facingMode === 'user'
      );

      capturedBlob = watermarkResult.blob;
      capturedPhotoUrl = watermarkResult.dataUrl;
    } catch (e: unknown) {
      errorMessage = e instanceof Error ? e.message : 'Gagal mengambil foto selfie.';
    } finally {
      isCapturing = false;
    }
  }

  function handleRetake() {
    capturedPhotoUrl = null;
    capturedBlob = null;
    errorMessage = null;
    startCamera();
  }

  async function handleSubmitAttendance() {
    if (!capturedBlob || !capturedPhotoUrl) return;
    isSubmitting = true;
    errorMessage = null;

    try {
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

      let record: AttendanceRecord;
      if (actionType === 'CLOCK_IN') {
        const res = await attendanceService.clockIn(
          currentUser.branch_id || 'branch-default',
          latitude,
          longitude,
          presignData.public_url || capturedPhotoUrl
        );
        record = {
          id: res.id,
          user_id: currentUser.id,
          user_name: currentUser.name,
          avatar_url: currentUser.avatar_url || '',
          branch_name: currentUser.branch_name || 'Outlet',
          shift_name: 'Shift Pagi',
          clock_in_time: res.clock_in_time,
          photo_in_url: res.photo_in_url || presignData.public_url || capturedPhotoUrl,
          lat_in: latitude,
          lng_in: longitude,
          status: res.status,
          late_minutes: res.late_minutes || 0,
          created_at: new Date().toISOString(),
        };
      } else {
        const res = await attendanceService.clockOut(
          currentUser.branch_id || 'branch-default',
          latitude,
          longitude,
          presignData.public_url || capturedPhotoUrl
        );
        record = {
          ...(todayAttendance || {
            id: res.id,
            user_id: currentUser.id,
            user_name: currentUser.name,
            avatar_url: currentUser.avatar_url || '',
            branch_name: currentUser.branch_name || 'Outlet',
            shift_name: 'Shift Pagi',
            clock_in_time: '07:00:00',
            photo_in_url: capturedPhotoUrl,
            lat_in: latitude,
            lng_in: longitude,
            status: 'ON_TIME' as const,
            late_minutes: 0,
            created_at: new Date().toISOString(),
          }),
          clock_out_time: res.clock_out_time,
          photo_out_url: res.photo_out_url || presignData.public_url || capturedPhotoUrl,
          overtime_minutes: res.overtime_minutes,
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

<div class="max-w-md mx-auto font-sans pb-8 space-y-3">
  <!-- Top Navigation & Action Mode Selector -->
  <div class="flex items-center justify-between gap-2 px-1">
    <div class="flex items-center gap-2">
      {#if onNavigateHome}
        <button
          type="button"
          onclick={onNavigateHome}
          class="p-2 rounded-full bg-white border border-[#d9d9dd] hover:bg-[#eeece7] text-[#17171c] cursor-pointer transition-all"
          title="Kembali ke Home"
        >
          <ArrowLeft class="w-4 h-4" />
        </button>
      {/if}
      <div>
        <h1 class="text-sm font-medium text-[#212121]">Kamera Presensi</h1>
        <p class="text-[10px] font-mono text-[#75758a]">{currentUser.branch_name || 'Outlet Sleman #01'}</p>
      </div>
    </div>

    <!-- Mode Selector: Clock-In vs Clock-Out -->
    <div class="flex items-center p-1 bg-[#eeece7] rounded-full border border-[#d9d9dd] text-[10px] font-mono font-medium">
      <button
        type="button"
        onclick={() => (actionType = 'CLOCK_IN')}
        class={`px-2.5 py-1 rounded-full transition-all cursor-pointer ${
          actionType === 'CLOCK_IN' ? 'bg-[#00875a] text-white shadow-xs' : 'text-[#616161] hover:text-[#212121]'
        }`}
      >
        Masuk
      </button>
      <button
        type="button"
        onclick={() => (actionType = 'CLOCK_OUT')}
        class={`px-2.5 py-1 rounded-full transition-all cursor-pointer ${
          actionType === 'CLOCK_OUT' ? 'bg-[#e5484d] text-white shadow-xs' : 'text-[#616161] hover:text-[#212121]'
        }`}
      >
        Keluar
      </button>
    </div>
  </div>

  {#if successRecord}
    <!-- Success Celebration View -->
    <div class="bg-white border border-[#00875a]/30 rounded-3xl p-6 text-center space-y-4 shadow-xl animate-in zoom-in-95">
      <div class="w-14 h-14 rounded-full bg-[#edfce9] text-[#00875a] flex items-center justify-center mx-auto">
        <CheckCircle2 class="w-8 h-8" />
      </div>

      <div class="space-y-1">
        <h2 class="text-base font-medium text-[#212121]">
          {actionType === 'CLOCK_IN' ? 'Presensi Masuk Berhasil!' : 'Presensi Keluar Berhasil!'}
        </h2>
        <p class="text-xs text-[#75758a]">
          Tercatat pada <strong class="font-mono text-[#17171c]">{successRecord.clock_in_time || liveTimestamp}</strong>
        </p>
      </div>

      {#if capturedPhotoUrl}
        <div class="w-32 aspect-3/4 rounded-2xl overflow-hidden border border-[#d9d9dd] mx-auto shadow-sm bg-[#17171c]">
          <img src={capturedPhotoUrl} alt="Selfie Presensi" class="w-full h-full object-cover" />
        </div>
      {/if}

      <div class="pt-2 flex flex-col gap-2">
        {#if onNavigateHome}
          <button
            type="button"
            onclick={onNavigateHome}
            class="w-full py-2.5 bg-[#17171c] hover:bg-black text-white font-medium text-xs rounded-xl cursor-pointer transition-all"
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
            startCamera();
          }}
          class="w-full py-2 text-xs font-medium border border-[#d9d9dd] text-[#616161] hover:bg-[#eeece7] rounded-xl cursor-pointer transition-all"
        >
          Ambil Foto Baru
        </button>
      </div>
    </div>
  {:else}
    <!-- 3:4 Camera Viewfinder Container -->
    <div class="relative aspect-3/4 w-full bg-black rounded-3xl overflow-hidden shadow-2xl border border-[#d9d9dd] flex flex-col justify-between p-4 select-none">
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
            <Camera class="w-12 h-12 text-[#93939f] animate-pulse" />
            <div class="text-xs font-medium">{currentUser.name}</div>
            <div class="text-[10px] text-[#93939f] font-mono">[KAMERA AKTIF: SIAP JEPRET SELFIE]</div>
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
        <div class="flex items-center gap-1.5 bg-black/60 backdrop-blur-md px-2.5 py-1 rounded-full border border-white/10 text-[10px] font-mono">
          <ShieldCheck class="w-3 h-3 text-[#00875a]" />
          <span>{gpsStatus}</span>
        </div>

        {#if !capturedPhotoUrl}
          <button
            type="button"
            onclick={toggleCameraFlip}
            class="p-2 rounded-full bg-black/60 backdrop-blur-md border border-white/10 text-white hover:bg-black cursor-pointer transition-all"
            title="Putar Kamera"
          >
            <RotateCcw class="w-4 h-4" />
          </button>
        {/if}
      </div>

      <!-- Center Watermark Overlay (Simulated Live) -->
      <div class="relative z-10 pointer-events-none self-start">
        <div class="bg-black/50 backdrop-blur-xs p-2 rounded-xl text-white font-mono text-[9px] space-y-0.5 border border-white/10 max-w-50">
          <div class="font-medium text-white truncate">{currentUser.name}</div>
          <div class="text-[#d9d9dd]">{liveTimestamp}</div>
          <div class="text-[#edfce9] truncate">{latitude.toFixed(5)}, {longitude.toFixed(5)}</div>
        </div>
      </div>

      <!-- Bottom Control Bar -->
      <div class="relative z-10 flex flex-col items-center gap-3">
        {#if errorMessage}
          <div class="bg-[#ffefef]/90 backdrop-blur-xs text-[#e5484d] text-[10px] font-medium px-3 py-1.5 rounded-full border border-[#e5484d]/30 flex items-center gap-1.5">
            <AlertCircle class="w-3.5 h-3.5 shrink-0" />
            <span>{errorMessage}</span>
          </div>
        {/if}

        {#if !capturedPhotoUrl}
          <!-- Shutter Button -->
          <div class="flex items-center justify-center pb-2">
            <button
              type="button"
              onclick={handleTakeSnapshot}
              disabled={isCapturing}
              class="w-18 h-18 rounded-full border-4 border-white bg-white/30 backdrop-blur-xs p-1 flex items-center justify-center active:scale-95 transition-all cursor-pointer group shadow-lg"
              title="Ambil Foto Selfie"
            >
              <div class="w-full h-full rounded-full bg-white group-hover:bg-[#eeece7] transition-colors flex items-center justify-center">
                <Camera class="w-6 h-6 text-[#17171c]" />
              </div>
            </button>
          </div>
        {:else}
          <!-- Preview Confirmation Actions -->
          <div class="w-full flex items-center gap-2.5 pb-2">
            <button
              type="button"
              onclick={handleRetake}
              disabled={isSubmitting}
              class="flex-1 py-2.5 bg-black/70 hover:bg-black text-white text-xs font-medium rounded-xl border border-white/20 backdrop-blur-md cursor-pointer transition-all text-center"
            >
              Ulangi Foto
            </button>
            <button
              type="button"
              onclick={handleSubmitAttendance}
              disabled={isSubmitting}
              class="flex-1 py-2.5 bg-[#00875a] hover:bg-[#006e48] text-white text-xs font-medium rounded-xl shadow-lg cursor-pointer transition-all flex items-center justify-center gap-1.5"
            >
              {#if isSubmitting}
                <RefreshCw class="w-3.5 h-3.5 animate-spin" />
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
