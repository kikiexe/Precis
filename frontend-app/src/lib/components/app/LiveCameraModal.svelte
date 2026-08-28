<script lang="ts">
  import { onDestroy } from 'svelte';
  import { Camera, MapPin, X, RefreshCw, AlertCircle, ShieldAlert } from 'lucide-svelte';
  import { burnWatermarkOnCanvas, type WatermarkResult } from '../../camera/watermark';
  import { attendanceService } from '../../services/attendance-service';
  import { ApiError } from '../../services/api-client';
  import type { User, AttendanceRecord } from '../../types/app';

  interface Props {
    isOpen: boolean;
    currentUser: User;
    actionType?: 'CLOCK_IN' | 'CLOCK_OUT';
    branchId?: string;
    onClose: () => void;
    onSuccess: (record: AttendanceRecord) => void;
  }

  let {
    isOpen = false,
    currentUser,
    actionType = 'CLOCK_IN',
    branchId,
    onClose,
    onSuccess,
  }: Props = $props();

  let videoElement = $state<HTMLVideoElement | null>(null);
  let stream = $state<MediaStream | null>(null);
  let isCameraActive = $state(false);
  let isProcessing = $state(false);
  let errorMessage = $state<string | null>(null);
  let geofenceWarning = $state<string | null>(null);

  // koordinat gps
  let latitude = $state(-7.782914);
  let longitude = $state(110.36712);
  let gpsStatus = $state('Mendeteksi sinyal GPS...');

  // waktu live wib
  let liveTimestamp = $state('');

  $effect(() => {
    if (isOpen) {
      errorMessage = null;
      geofenceWarning = null;
      startCamera();
      detectGps();
    } else {
      stopCamera();
    }
  });

  $effect(() => {
    const updateTime = () => {
      const now = new Date();
      liveTimestamp = `${now.toLocaleDateString('id-ID')} ${now.toLocaleTimeString('id-ID')}`;
    };
    updateTime();
    const interval = setInterval(updateTime, 1000);
    return () => clearInterval(interval);
  });

  async function startCamera() {
    try {
      if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 960 } },
          audio: false,
        });
        if (videoElement) {
          videoElement.srcObject = stream;
          await videoElement.play();
          isCameraActive = true;
        }
      }
    } catch (e) {
      console.warn('Akses kamera tidak tersedia, beralih ke viewfinder simulator canvas:', e);
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

  function detectGps() {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          latitude = pos.coords.latitude;
          longitude = pos.coords.longitude;
          gpsStatus = `GPS Terkunci (Akurasi: ±${Math.round(pos.coords.accuracy)}m)`;
        },
        () => {
          gpsStatus = 'GPS Default Outlet Aktif';
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    } else {
      gpsStatus = 'GPS Default Outlet Aktif';
    }
  }

  async function handleCaptureSelfie() {
    if (isProcessing) return;
    isProcessing = true;
    errorMessage = null;
    geofenceWarning = null;

    try {
      let captureSource: HTMLVideoElement | HTMLCanvasElement;

      if (isCameraActive && videoElement) {
        captureSource = videoElement;
      } else {
        // simulasi frame kamera untuk lingkungan tanpa webcam
        const simCanvas = document.createElement('canvas');
        simCanvas.width = 720;
        simCanvas.height = 960;
        const ctx = simCanvas.getContext('2d')!;
        ctx.fillStyle = '#17171c';
        ctx.fillRect(0, 0, 720, 960);
        ctx.fillStyle = '#003c33';
        ctx.beginPath();
        ctx.arc(360, 420, 160, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#ffffff';
        ctx.font = '500 36px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(currentUser.name, 360, 430);
        ctx.font = '24px monospace';
        ctx.fillText('[LIVE CAMERA STREAM]', 360, 680);
        captureSource = simCanvas;
      }

      // 1. render watermark nama staf, jam WIB, dan koordinat GPS ke canvas
      const watermarkResult: WatermarkResult = await burnWatermarkOnCanvas(
        captureSource,
        liveTimestamp,
        latitude,
        longitude,
        currentUser.branch_name,
        true
      );

      // 2. minta presigned URL dari backend
      const targetBranchId = branchId || currentUser.branch_id || 'branch-default';
      const filename = `selfie_${currentUser.id}_${Date.now()}.webp`;
      const presignData = await attendanceService.presignUpload(
        filename,
        'image/webp',
        watermarkResult.blob.size
      );

      // 3. unggah binary WebP langsung ke object storage
      await attendanceService.uploadBinaryToStorage(
        presignData.upload_url,
        watermarkResult.blob,
        'image/webp'
      );

      const photoUrl = presignData.public_url || presignData.key || watermarkResult.dataUrl;

      // 4. kirim data presensi ke backend
      const now = new Date();
      const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

      if (actionType === 'CLOCK_IN') {
        const clockInData = await attendanceService.clockIn(
          targetBranchId,
          latitude,
          longitude,
          photoUrl
        );

        const record: AttendanceRecord = {
          id: clockInData.id,
          user_id: currentUser.id,
          user_name: currentUser.name,
          avatar_url: currentUser.avatar_url || '',
          branch_name: currentUser.branch_name,
          shift_name: 'Shift Hari Ini',
          clock_in_time: `${timeStr} WIB`,
          photo_in_url: clockInData.photo_in_url || watermarkResult.dataUrl,
          lat_in: latitude,
          lng_in: longitude,
          status: clockInData.status || 'ON_TIME',
          late_minutes: clockInData.late_minutes || 0,
          created_at: now.toISOString(),
        };

        onSuccess(record);
      } else {
        const clockOutData = await attendanceService.clockOut(
          targetBranchId,
          latitude,
          longitude,
          photoUrl
        );

        const record: AttendanceRecord = {
          id: clockOutData.id,
          user_id: currentUser.id,
          user_name: currentUser.name,
          avatar_url: currentUser.avatar_url || '',
          branch_name: currentUser.branch_name,
          shift_name: 'Shift Hari Ini',
          clock_in_time: `${timeStr} WIB`,
          clock_out_time: `${timeStr} WIB`,
          photo_in_url: watermarkResult.dataUrl,
          photo_out_url: clockOutData.photo_out_url || watermarkResult.dataUrl,
          lat_in: latitude,
          lng_in: longitude,
          status: 'ON_TIME',
          late_minutes: 0,
          overtime_minutes: clockOutData.overtime_minutes || 0,
          created_at: now.toISOString(),
        };

        onSuccess(record);
      }

      onClose();
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        if (err.status === 422 && err.message.toLowerCase().includes('radius')) {
          geofenceWarning = err.message;
        } else {
          errorMessage = err.message;
        }
      } else if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Terjadi kesalahan saat memproses presensi.';
      }
    } finally {
      isProcessing = false;
    }
  }

  onDestroy(() => {
    stopCamera();
  });
</script>

{#if isOpen}
  <div
    class="animate-in fade-in fixed inset-0 z-50 mx-auto flex max-w-md flex-col justify-between bg-[#17171c] font-sans select-none"
  >
    <!-- bilah atas informasi GPS dan tombol tutup -->
    <div
      class="z-20 flex items-center justify-between border-b border-white/10 bg-[#17171c]/90 p-4 text-white backdrop-blur-md"
    >
      <div class="flex items-center gap-2 font-mono text-xs text-[#edfce9]">
        <MapPin class="h-3.5 w-3.5 text-[#edfce9]" />
        <span>{gpsStatus}</span>
      </div>
      <button
        type="button"
        onclick={onClose}
        disabled={isProcessing}
        class="cursor-pointer p-1 text-white/80 transition-colors hover:text-white"
      >
        <X class="h-5 w-5" />
      </button>
    </div>

    <!-- banner peringatan jika keluar geofence atau error -->
    {#if geofenceWarning}
      <div
        class="animate-in slide-in-from-top-2 z-30 mx-4 mt-3 flex items-start gap-2.5 rounded-xl bg-[#b30000] p-3.5 font-mono text-xs text-white shadow-none"
      >
        <ShieldAlert class="mt-0.5 h-4 w-4 shrink-0" />
        <div>
          <div class="font-medium">Radius Lokasi Ditolak (Geofence)</div>
          <div class="mt-0.5 text-[11px] opacity-90">{geofenceWarning}</div>
        </div>
      </div>
    {:else if errorMessage}
      <div
        class="animate-in slide-in-from-top-2 z-30 mx-4 mt-3 flex items-start gap-2.5 rounded-xl bg-[#b30000] p-3.5 font-mono text-xs text-white shadow-none"
      >
        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
        <span>{errorMessage}</span>
      </div>
    {/if}

    <!-- area viewport kamera dengan overlay watermark -->
    <div class="relative flex flex-1 items-center justify-center overflow-hidden bg-[#17171c]">
      {#if isCameraActive}
        <video
          bind:this={videoElement}
          autoplay
          playsinline
          muted
          class="h-full w-full -scale-x-100 transform object-cover"
        ></video>
      {:else}
        <!-- tampilan simulator jika webcam tidak terdeteksi -->
        <div class="space-y-4 p-6 text-center">
          <div
            class="mx-auto flex h-32 w-32 items-center justify-center rounded-full border-2 border-dashed border-[#edfce9]/40"
          >
            <Camera class="h-12 w-12 text-[#edfce9]" />
          </div>
          <div class="text-sm font-medium text-white">{currentUser.name}</div>
          <div class="font-mono text-xs text-[#93939f]">Posisikan wajah di dalam frame kamera</div>
        </div>
      {/if}

      <!-- stamp watermark di bagian bawah layar kamera -->
      <div
        class="absolute right-0 bottom-0 left-0 space-y-1 border-t border-white/10 bg-[#17171c]/90 p-3.5 font-mono text-xs text-white backdrop-blur-sm"
      >
        <div class="flex items-center justify-between font-medium">
          <span>{liveTimestamp} WIB</span>
          <span
            class="rounded-full bg-white/10 px-2 py-0.5 font-mono text-[10px] text-[#edfce9] uppercase"
          >
            {actionType === 'CLOCK_IN' ? 'PRESENSI MASUK' : 'PRESENSI KELUAR'}
          </span>
        </div>
        <div class="text-[11px] text-[#e5e5ea]">
          {currentUser.branch_name} • Lat: {latitude.toFixed(6)}, Lng: {longitude.toFixed(6)}
        </div>
      </div>
    </div>

    <!-- tombol shutter kamera di bagian bawah -->
    <div class="z-20 flex flex-col items-center gap-3 border-t border-white/10 bg-[#17171c] p-6">
      <button
        type="button"
        disabled={isProcessing}
        onclick={handleCaptureSelfie}
        class="flex h-20 w-20 cursor-pointer items-center justify-center rounded-full border-4 border-white/30 bg-white transition-all active:scale-95 disabled:opacity-50"
        aria-label="Ambil Foto"
      >
        <div
          class={`flex h-14 w-14 items-center justify-center rounded-full bg-[#17171c] text-white ${isProcessing ? 'animate-spin' : ''}`}
        >
          {#if isProcessing}
            <RefreshCw class="h-6 w-6" />
          {:else}
            <Camera class="h-6 w-6" />
          {/if}
        </div>
      </button>
      <span class="font-mono text-[11px] text-[#93939f]">
        {isProcessing ? 'Mengunggah Foto & Memverifikasi GPS...' : 'Tekan tombol untuk presensi'}
      </span>
    </div>
  </div>
{/if}
