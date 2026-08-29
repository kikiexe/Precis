import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { getCachedImageUrl, preloadAndCacheImage, clearImageCache, IMAGE_CACHE_NAME } from './image-cache';

describe('image-cache service', () => {
  let mockCacheStore: Map<string, Response>;
  let mockCache: {
    match: ReturnType<typeof vi.fn>;
    put: ReturnType<typeof vi.fn>;
    delete: ReturnType<typeof vi.fn>;
  };
  let mockCaches: {
    open: ReturnType<typeof vi.fn>;
    delete: ReturnType<typeof vi.fn>;
  };

  beforeEach(() => {
    mockCacheStore = new Map();

    mockCache = {
      match: vi.fn(async (url: string) => mockCacheStore.get(url) || null),
      put: vi.fn(async (url: string, res: Response) => {
        mockCacheStore.set(url, res);
      }),
      delete: vi.fn(async (url: string) => mockCacheStore.delete(url)),
    };

    mockCaches = {
      open: vi.fn(async () => mockCache),
      delete: vi.fn(async () => {
        mockCacheStore.clear();
        return true;
      }),
    };

    vi.stubGlobal('window', { caches: mockCaches });
    vi.stubGlobal('caches', mockCaches);
    vi.stubGlobal('navigator', { onLine: true });
    vi.stubGlobal('URL', {
      createObjectURL: vi.fn((_blob: Blob) => `blob:http://localhost/${Math.random()}`),
      revokeObjectURL: vi.fn(),
    });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('handles null, undefined, or empty url safely', async () => {
    expect(await getCachedImageUrl(null)).toBeNull();
    expect(await getCachedImageUrl(undefined)).toBeNull();
    expect(await getCachedImageUrl('')).toBeNull();
  });

  it('returns blob: and data: URLs directly without accessing Cache Storage', async () => {
    const dataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    const blobUri = 'blob:http://localhost/mock-uuid-123';

    expect(await getCachedImageUrl(dataUri)).toBe(dataUri);
    expect(await getCachedImageUrl(blobUri)).toBe(blobUri);
    expect(mockCaches.open).not.toHaveBeenCalled();
  });

  it('returns cached ObjectURL when image is already in Cache Storage', async () => {
    const targetUrl = 'https://assets.precis.com/qris/seturan-qris.webp';
    const fakeBlob = new Blob(['mock-binary-data'], { type: 'image/webp' });
    const fakeResponse = new Response(fakeBlob);

    mockCacheStore.set(targetUrl, fakeResponse);

    const result = await getCachedImageUrl(targetUrl);

    expect(mockCaches.open).toHaveBeenCalledWith(IMAGE_CACHE_NAME);
    expect(result).toMatch(/^blob:http:\/\/localhost\//);
  });

  it('fetches and caches image when missing from Cache Storage online', async () => {
    const targetUrl = 'https://assets.precis.com/products/americano.webp';
    const fakeBlob = new Blob(['product-image-content'], { type: 'image/webp' });

    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response(fakeBlob, { status: 200, statusText: 'OK' }))
    );

    const result = await getCachedImageUrl(targetUrl);

    expect(mockCaches.open).toHaveBeenCalledWith(IMAGE_CACHE_NAME);
    expect(mockCache.put).toHaveBeenCalled();
    expect(result).toMatch(/^blob:http:\/\/localhost\//);
  });

  it('falls back to original url if fetch fails during network error', async () => {
    const targetUrl = 'https://assets.precis.com/products/failed-image.webp';

    vi.stubGlobal(
      'fetch',
      vi.fn(async () => {
        throw new Error('Network error');
      })
    );

    const result = await getCachedImageUrl(targetUrl);

    expect(result).toBe(targetUrl);
  });

  it('preloads and stores image via preloadAndCacheImage', async () => {
    const targetUrl = 'https://assets.precis.com/qris/preload-target.webp';
    const fakeBlob = new Blob(['preload-content'], { type: 'image/webp' });

    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response(fakeBlob, { status: 200 }))
    );

    const success = await preloadAndCacheImage(targetUrl);

    expect(success).toBe(true);
    expect(mockCache.put).toHaveBeenCalled();
  });

  it('clears all cached images via clearImageCache', async () => {
    const result = await clearImageCache();

    expect(result).toBe(true);
    expect(mockCaches.delete).toHaveBeenCalledWith(IMAGE_CACHE_NAME);
  });
});
