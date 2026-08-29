export const IMAGE_CACHE_NAME = 'precis-pos-images-v1';

/**
 * Mengambil URL gambar dari Cache Storage lokal jika tersedia saat offline,
 * atau mengunduh dan menyimpannya ke cache secara otomatis saat online.
 */
export async function getCachedImageUrl(url: string | null | undefined): Promise<string | null> {
  if (!url || typeof window === 'undefined') {
    return url || null;
  }

  // Jika URL berupa data URI atau Blob URL, kembalikan langsung
  if (url.startsWith('data:') || url.startsWith('blob:')) {
    return url;
  }

  // Fallback jika browser tidak mendukung Cache Storage API
  if (!('caches' in window)) {
    return url;
  }

  try {
    const cache = await caches.open(IMAGE_CACHE_NAME);
    const cachedResponse = await cache.match(url);

    if (cachedResponse) {
      const blob = await cachedResponse.blob();
      return URL.createObjectURL(blob);
    }

    // Jika belum ada di cache dan online, unduh dan simpan
    if (navigator.onLine) {
      const response = await fetch(url, { mode: 'cors' });
      if (response.ok) {
        await cache.put(url, response.clone());
        const blob = await response.blob();
        return URL.createObjectURL(blob);
      }
    }
  } catch {
    // Network failure atau CORS error: fallback ke URL asli
  }

  return url;
}

/**
 * Preload gambar ke cache storage di background (misal saat sync katalog / QRIS).
 */
export async function preloadAndCacheImage(url: string | null | undefined): Promise<boolean> {
  if (!url || typeof window === 'undefined' || !('caches' in window)) {
    return false;
  }

  if (url.startsWith('data:') || url.startsWith('blob:')) {
    return true;
  }

  try {
    const cache = await caches.open(IMAGE_CACHE_NAME);
    const cachedResponse = await cache.match(url);
    if (cachedResponse) {
      return true;
    }

    const response = await fetch(url, { mode: 'cors' });
    if (response.ok) {
      await cache.put(url, response);
      return true;
    }
  } catch {
    // Fallback silent failure
  }

  return false;
}

/**
 * Hapus seluruh cache gambar lokal jika user ingin membersihkan storage.
 */
export async function clearImageCache(): Promise<boolean> {
  if (typeof window === 'undefined' || !('caches' in window)) {
    return false;
  }

  try {
    return await caches.delete(IMAGE_CACHE_NAME);
  } catch {
    return false;
  }
}
