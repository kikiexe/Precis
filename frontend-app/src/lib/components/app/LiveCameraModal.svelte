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
        ctx.fillStyle = '#262626';
        ctx.fillRect(0, 0, 720, 960);
        ctx.fillStyle = '#0f62fe';
        ctx.beginPath();
        ctx.arc(360, 420, 160, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 36px sans-serif';
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
        currentUser.branch_name
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
  <div class="fixed inset-0 z-50 bg-black flex flex-col justify-between select-none max-w-md mx-auto animate-in fade-in">
    <!-- bilah atas informasi GPS dan tombol tutup -->
    <div class="p-4 flex items-center justify-between text-white bg-black/70 z-20">
      <div class="flex items-center gap-2 text-xs font-mono">
        <MapPin class="w-3.5 h-3.5 text-[#24a148]" />
        <span>{gpsStatus}</span>
      </div>
      <button
        type="button"
        onclick={onClose}
        disabled={isProcessing}
        class="text-white hover:text-[#8c8c8c] p-1 cursor-pointer"
      >
        <X class="w-6 h-6" />
      </button>
    </div>

    <!-- banner peringatan jika keluar geofence atau error -->
    {#if geofenceWarning}
      <div class="mx-4 mt-2 p-3 bg-[#da1e28] text-white text-xs font-mono z-30 flex items-start gap-2 shadow-lg animate-in slide-in-from-top-2">
        <ShieldAlert class="w-4 h-4 shrink-0 mt-0.5" />
        <div>
          <div class="font-bold">Radius Lokasi Ditolak (Geofence)</div>
          <div class="text-[11px] mt-0.5 opacity-90">{geofenceWarning}</div>
        </div>
      </div>
    {:else if errorMessage}
      <div class="mx-4 mt-2 p-3 bg-[#da1e28] text-white text-xs font-mono z-30 flex items-start gap-2 shadow-lg animate-in slide-in-from-top-2">
        <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
        <span>{errorMessage}</span>
      </div>
    {/if}

    <!-- area viewport kamera dengan overlay watermark -->
    <div class="relative flex-1 bg-[#161616] overflow-hidden flex items-center justify-center">
      {#if isCameraActive}
        <video
          bind:this={videoElement}
          autoplay
          playsinline
          muted
          class="w-full h-full object-cover transform -scale-x-100"
        ></video>
      {:else}
        <!-- tampilan simulator jika webcam tidak terdeteksi -->
        <div class="text-center p-6 space-y-4">
          <div class="w-32 h-32 rounded-full border-2 border-dashed border-[#0f62fe] mx-auto flex items-center justify-center">
            <Camera class="w-12 h-12 text-[#0f62fe]" />
          </div>
          <div class="text-white text-sm font-semibold">{currentUser.name}</div>
          <div class="text-xs text-[#8c8c8c] font-mono">Posisikan wajah di dalam lingkaran</div>
        </div>
      {/if}

      <!-- stamp watermark di bagian bawah layar kamera -->
      <div class="absolute bottom-0 left-0 right-0 p-3 bg-black/80 border-t-2 border-[#0f62fe] text-white font-mono text-xs space-y-1">
        <div class="font-bold flex items-center justify-between">
          <span>{liveTimestamp} WIB</span>
          <span class="text-[#24a148] text-[10px] uppercase font-semibold">
            {actionType === 'CLOCK_IN' ? 'PRESENSI MASUK' : 'PRESENSI KELUAR'}
          </span>
        </div>
        <div class="text-[11px] text-[#c6c6c6]">
          {currentUser.branch_name} • Lat: {latitude.toFixed(6)}, Lng: {longitude.toFixed(6)}
        </div>
      </div>
    </div>

    <!-- tombol shutter kamera di bagian bawah -->
    <div class="p-6 bg-black flex flex-col items-center gap-3 z-20">
      <button
        type="button"
        disabled={isProcessing}
        onclick={handleCaptureSelfie}
        class="w-20 h-20 rounded-full bg-white border-4 border-[#0f62fe] active:scale-95 flex items-center justify-center shadow-lg transition-transform cursor-pointer disabled:opacity-50"
        aria-label="Ambil Foto"
      >
        <div class={`w-14 h-14 rounded-full bg-[#0f62fe] flex items-center justify-center text-white ${isProcessing ? 'animate-spin' : ''}`}>
          {#if isProcessing}
            <RefreshCw class="w-6 h-6" />
          {:else}
            <Camera class="w-7 h-7" />
          {/if}
        </div>
      </button>
      <span class="text-[11px] text-[#8c8c8c] font-mono">
        {isProcessing ? 'Mengunggah Foto & Memverifikasi GPS...' : 'Tekan tombol untuk presensi'}
      </span>
    </div>
  </div>
{/if}
