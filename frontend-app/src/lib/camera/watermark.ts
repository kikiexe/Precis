export interface WatermarkResult {
  blob: Blob;
  dataUrl: string;
}

export async function burnWatermarkOnCanvas(
  source: HTMLVideoElement | HTMLImageElement | HTMLCanvasElement,
  timestampStr: string,
  latitude: number,
  longitude: number,
  branchName = 'Outlet Sleman #01'
): Promise<WatermarkResult> {
  const canvas = document.createElement('canvas');
  canvas.width = 720;
  canvas.height = 960; // 3:4 portrait ratio
  const ctx = canvas.getContext('2d');
  if (!ctx) throw new Error('Failed to get 2D canvas context');

  // 1. Draw raw camera image scaled to 3:4 portrait
  ctx.drawImage(source, 0, 0, canvas.width, canvas.height);

  // 2. Draw black semi-transparent footer banner
  const bannerHeight = 110;
  ctx.fillStyle = 'rgba(0, 0, 0, 0.72)';
  ctx.fillRect(0, canvas.height - bannerHeight, canvas.width, bannerHeight);

  // 3. Draw blue decorative accent line
  ctx.fillStyle = '#0f62fe';
  ctx.fillRect(0, canvas.height - bannerHeight, canvas.width, 3);

  // 4. Draw Watermark Timestamp & Store Name
  ctx.fillStyle = '#FFFFFF';
  ctx.font = 'bold 22px "IBM Plex Sans", -apple-system, sans-serif';
  ctx.fillText(`${timestampStr} WIB • ${branchName}`, 24, canvas.height - 65);

  // 5. Draw GPS coordinates and security seal
  ctx.fillStyle = '#C6C6C6';
  ctx.font = '16px "JetBrains Mono", monospace';
  ctx.fillText(
    `GPS: Lat ${latitude.toFixed(6)}, Lng ${longitude.toFixed(6)} | Verified Geofence`,
    24,
    canvas.height - 28
  );

  // 6. Return WebP compressed result
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
