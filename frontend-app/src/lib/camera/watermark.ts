export interface WatermarkResult {
  blob: Blob;
  dataUrl: string;
}

/**
 * Mencetak watermark permanen pada sudut kanan bawah (bottom-right) canvas foto.
 * Desain: Container transparan (tanpa box background), teks murni berwarna putih
 * dengan stroke/shadow hitam halus agar kontras dan terbaca jelas di segala pencahayaan.
 * Konten: Timestamp WIB + Koordinat GPS (contoh: 21-08-2026 07:15:32 WIB | Lat: -7.782914, Lng: 110.367120).
 */
export async function burnWatermarkOnCanvas(
  source: HTMLVideoElement | HTMLImageElement | HTMLCanvasElement,
  timestampStr: string,
  latitude: number,
  longitude: number,
  _branchName = '',
  isFrontCamera = false
): Promise<WatermarkResult> {
  const canvas = document.createElement('canvas');
  canvas.width = 720;
  canvas.height = 960; // Rasio 3:4 portrait
  const ctx = canvas.getContext('2d');
  if (!ctx) throw new Error('Gagal mendapatkan 2D canvas context');

  // 1. Gambar frame video / image mentah (mirror horizontal jika kamera depan)
  ctx.save();
  if (isFrontCamera) {
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
  }
  ctx.drawImage(source, 0, 0, canvas.width, canvas.height);
  ctx.restore();

  // 2. Format teks watermark presisi sesuai FE_EXECUTION_PLAN.md
  const formattedGps = `Lat: ${latitude.toFixed(6)}, Lng: ${longitude.toFixed(6)}`;
  const line1 = `${timestampStr}`;
  const line2 = formattedGps;

  // 3. Render watermark pada pojok kanan bawah dengan container transparan (tanpa box background)
  ctx.save();
  ctx.textAlign = 'right';
  ctx.textBaseline = 'bottom';

  const paddingRight = 24;
  const paddingBottom = 24;
  const x = canvas.width - paddingRight;
  const yLine2 = canvas.height - paddingBottom;
  const yLine1 = yLine2 - 26;

  // Efek shadow hitam halus untuk keterbacaan tinggi di atas latar terang
  ctx.shadowColor = 'rgba(0, 0, 0, 0.85)';
  ctx.shadowBlur = 6;
  ctx.shadowOffsetX = 0;
  ctx.shadowOffsetY = 1;

  // Teks Baris 1: Waktu Riil
  ctx.font = 'bold 20px "JetBrains Mono", "IBM Plex Mono", monospace';
  ctx.fillStyle = '#FFFFFF';
  ctx.fillText(line1, x, yLine1);

  // Teks Baris 2: Koordinat GPS
  ctx.font = '16px "JetBrains Mono", "IBM Plex Mono", monospace';
  ctx.fillStyle = '#FFFFFF';
  ctx.fillText(line2, x, yLine2);

  ctx.restore();

  // 4. Kompresi ke format WebP (kualitas 0.75, target ukuran ~100KB s/d 150KB)
  return new Promise((resolve) => {
    canvas.toBlob(
      (blob) => {
        const dataUrl = canvas.toDataURL('image/webp', 0.75);
        resolve({
          blob: blob || new Blob(),
          dataUrl,
        });
      },
      'image/webp',
      0.75
    );
  });
}
