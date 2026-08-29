import { normalizeMediaUrl } from '@precis/shared-utils/formatters';

export const IMAGE_CACHE_NAME = 'precis-pos-images-v1';

/**
 * mengambil URL gambar dari Cache Storage lokal jika tersedia saat offline,
 * atau mengunduh dan menyimpannya ke cache secara otomatis saat online
 */
export async function getCachedImageUrl(url: string | null | undefined): Promise<string | null> {
  if (!url || typeof window === 'undefined') {
    return url || null;
  }

  const normalized = normalizeMediaUrl(url);
  if (!normalized) {
    return null;
  }

  // Jika URL berupa data URI atau Blob URL, kembalikan langsung
  if (normalized.startsWith('data:') || normalized.startsWith('blob:')) {
    return normalized;
  }

  // fallback jika browser tidak mendukung Cache Storage API
  if (!('caches' in window)) {
    return normalized;
  }

  try {
    const cache = await caches.open(IMAGE_CACHE_NAME);
    const cachedResponse = await cache.match(normalized);

    if (cachedResponse) {
      const blob = await cachedResponse.blob();
      return URL.createObjectURL(blob);
    }

    // jika belum ada di cache dan online, unduh dan simpan
    if (navigator.onLine) {
      const response = await fetch(normalized, { mode: 'cors' });
      if (response.ok) {
        await cache.put(normalized, response.clone());
        const blob = await response.blob();
        return URL.createObjectURL(blob);
      }
    }
  } catch {
    // network failure atau CORS error: fallback ke URL normal
  }

  return normalized;
}

/**
 * Preload gambar ke cache storage di background (misal saat sync katalog / QRIS).
 */
export async function preloadAndCacheImage(url: string | null | undefined): Promise<boolean> {
  if (!url || typeof window === 'undefined' || !('caches' in window)) {
    return false;
  }

  const normalized = normalizeMediaUrl(url);
  if (!normalized) {
    return false;
  }

  if (normalized.startsWith('data:') || normalized.startsWith('blob:')) {
    return true;
  }

  try {
    const cache = await caches.open(IMAGE_CACHE_NAME);
    const cachedResponse = await cache.match(normalized);
    if (cachedResponse) {
      return true;
    }

    const response = await fetch(normalized, { mode: 'cors' });
    if (response.ok) {
      await cache.put(normalized, response);
      return true;
    }
  } catch {
    // Fallback silent failure
  }

  return false;
}

/**
 * hapus seluruh cache gambar lokal jika user ingin membersihkan storage  
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
