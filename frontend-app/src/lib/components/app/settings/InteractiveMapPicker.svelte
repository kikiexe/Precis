<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import {
    Search,
    MapPin,
    LocateFixed,
    Plus,
    Minus,
    RefreshCw,
    Navigation,
    RotateCcw,
  } from 'lucide-svelte';
  import L from 'leaflet';

  interface Props {
    latitude: number;
    longitude: number;
    radiusMeters: number;
    branchName?: string;
    savedLatitude?: number;
    savedLongitude?: number;
    onChangeCoordinates: (lat: number, lng: number) => void;
  }

  let {
    latitude = -7.782914,
    longitude = 110.36712,
    radiusMeters = 50,
    branchName = 'Cabang Outlet',
    savedLatitude,
    savedLongitude,
    onChangeCoordinates,
  }: Props = $props();

  let mapContainer = $state<HTMLDivElement | null>(null);
  let mapInstance: L.Map | null = null;
  let markerInstance: L.Marker | null = null;
  let circleInstance: L.Circle | null = null;

  let searchQuery = $state('');
  let isSearching = $state(false);
  let searchResults = $state<Array<{ display_name: string; lat: string; lon: string }>>([]);
  let isLocating = $state(false);
  let locateMessage = $state<string | null>(null);

  // Custom SVG Map Pin Icon (Clean Minimalist Ojol Style)
  const customPinIcon = L.divIcon({
    className: 'custom-map-pin',
    html: `
      <div class="relative flex items-center justify-center -translate-x-1/2 -translate-y-full cursor-grab active:cursor-grabbing">
        <div class="w-9 h-9 bg-[#17171c] text-white rounded-full flex items-center justify-center shadow-lg border-2 border-white transform transition-transform hover:scale-105">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0Z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
        </div>
        <div class="absolute -bottom-1 w-2.5 h-1 bg-[#17171c]/30 rounded-full blur-[1px]"></div>
      </div>
    `,
    iconSize: [36, 36],
    iconAnchor: [18, 36],
  });

  onMount(() => {
    if (!mapContainer) return;

    const initialLat = Number(latitude) || -7.782914;
    const initialLng = Number(longitude) || 110.36712;

    mapInstance = L.map(mapContainer, {
      center: [initialLat, initialLng],
      zoom: 16,
      zoomControl: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(mapInstance);

    circleInstance = L.circle([initialLat, initialLng], {
      radius: radiusMeters,
      color: '#2563eb',
      fillColor: '#3b82f6',
      fillOpacity: 0.16,
      weight: 2,
    }).addTo(mapInstance);

    markerInstance = L.marker([initialLat, initialLng], {
      icon: customPinIcon,
      draggable: true,
      title: branchName,
    }).addTo(mapInstance);

    markerInstance.on('dragend', () => {
      if (!markerInstance) return;
      const pos = markerInstance.getLatLng();
      updatePosition(pos.lat, pos.lng, false);
    });

    mapInstance.on('click', (e: L.LeafletMouseEvent) => {
      updatePosition(e.latlng.lat, e.latlng.lng, true);
    });
  });

  onDestroy(() => {
    if (mapInstance) {
      mapInstance.remove();
      mapInstance = null;
    }
  });

  $effect(() => {
    const lat = Number(latitude);
    const lng = Number(longitude);
    if (!isNaN(lat) && !isNaN(lng) && markerInstance && circleInstance && mapInstance) {
      const currentPos = markerInstance.getLatLng();
      if (Math.abs(currentPos.lat - lat) > 0.00001 || Math.abs(currentPos.lng - lng) > 0.00001) {
        markerInstance.setLatLng([lat, lng]);
        circleInstance.setLatLng([lat, lng]);
      }
    }
  });

  $effect(() => {
    if (circleInstance && radiusMeters > 0) {
      circleInstance.setRadius(radiusMeters);
    }
  });

  function updatePosition(lat: number, lng: number, panMap = true) {
    const cleanLat = Number(lat.toFixed(6));
    const cleanLng = Number(lng.toFixed(6));

    if (markerInstance) {
      markerInstance.setLatLng([cleanLat, cleanLng]);
    }
    if (circleInstance) {
      circleInstance.setLatLng([cleanLat, cleanLng]);
    }
    if (panMap && mapInstance) {
      mapInstance.panTo([cleanLat, cleanLng]);
    }

    onChangeCoordinates(cleanLat, cleanLng);
  }

  function handleResetToSaved() {
    const targetLat = savedLatitude !== undefined ? savedLatitude : latitude;
    const targetLng = savedLongitude !== undefined ? savedLongitude : longitude;
    if (targetLat !== undefined && targetLng !== undefined) {
      updatePosition(targetLat, targetLng, true);
      if (mapInstance) {
        mapInstance.flyTo([targetLat, targetLng], 17, { duration: 0.8 });
      }
    }
  }

  async function handleSearchLocation() {
    if (!searchQuery.trim()) return;
    isSearching = true;
    searchResults = [];

    try {
      const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(
        searchQuery.trim()
      )}&countrycodes=id&limit=5`;
      const res = await fetch(url, {
        headers: { 'Accept-Language': 'id,en' },
      });
      const data = await res.json();
      if (Array.isArray(data) && data.length > 0) {
        searchResults = data;
        if (data.length === 1) {
          selectSearchResult(data[0]);
        }
      } else {
        locateMessage = 'Lokasi tidak ditemukan.';
        setTimeout(() => (locateMessage = null), 3000);
      }
    } catch {
      locateMessage = 'Gagal mencari lokasi.';
      setTimeout(() => (locateMessage = null), 3000);
    } finally {
      isSearching = false;
    }
  }

  function selectSearchResult(item: { display_name: string; lat: string; lon: string }) {
    const lat = parseFloat(item.lat);
    const lng = parseFloat(item.lon);
    if (!isNaN(lat) && !isNaN(lng)) {
      updatePosition(lat, lng, true);
      if (mapInstance) {
        mapInstance.flyTo([lat, lng], 17, { duration: 1 });
      }
    }
    searchResults = [];
    searchQuery = item.display_name.split(',')[0];
  }

  function handleLocateDevice() {
    if (typeof window === 'undefined' || !('geolocation' in navigator)) {
      locateMessage = 'Geolocation tidak didukung browser.';
      setTimeout(() => (locateMessage = null), 4000);
      return;
    }

    if (
      !window.isSecureContext &&
      window.location.hostname !== 'localhost' &&
      window.location.hostname !== '127.0.0.1'
    ) {
      locateMessage = 'Browser memblokir GPS pada HTTP IP lokal. Gunakan HTTPS atau localhost.';
      setTimeout(() => (locateMessage = null), 5000);
      return;
    }

    isLocating = true;
    locateMessage = null;

    const onSuccess = (pos: GeolocationPosition) => {
      isLocating = false;
      const lat = Number(pos.coords.latitude.toFixed(6));
      const lng = Number(pos.coords.longitude.toFixed(6));
      updatePosition(lat, lng, true);
      if (mapInstance) {
        mapInstance.flyTo([lat, lng], 17, { duration: 1 });
      }
    };

    const onError = (err: GeolocationPositionError) => {
      if (err.code === err.TIMEOUT || err.code === err.POSITION_UNAVAILABLE) {
        // Fallback low accuracy
        navigator.geolocation.getCurrentPosition(
          onSuccess,
          (fallbackErr) => {
            isLocating = false;
            let msg = 'Gagal membaca GPS perangkat.';
            if (fallbackErr.code === fallbackErr.PERMISSION_DENIED) {
              msg = 'Izin lokasi ditolak. Periksa izin lokasi Chrome di Pengaturan HP.';
            } else if (fallbackErr.code === fallbackErr.POSITION_UNAVAILABLE) {
              msg = 'Sinyal lokasi tidak tersedia. Aktifkan GPS/Lokasi di HP.';
            }
            locateMessage = msg;
            setTimeout(() => (locateMessage = null), 4000);
          },
          { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
        );
        return;
      }

      isLocating = false;
      let msg = 'Gagal membaca GPS perangkat.';
      if (err.code === err.PERMISSION_DENIED) {
        msg = 'Izin lokasi ditolak. Periksa izin lokasi Chrome di Pengaturan HP.';
      }
      locateMessage = msg;
      setTimeout(() => (locateMessage = null), 4000);
    };

    navigator.geolocation.getCurrentPosition(onSuccess, onError, {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0,
    });
  }

  function zoomIn() {
    if (mapInstance) mapInstance.zoomIn();
  }

  function zoomOut() {
    if (mapInstance) mapInstance.zoomOut();
  }
</script>

<div class="space-y-2.5 font-sans">
  <!-- Sleek Search & Quick Action Controls -->
  <div class="relative">
    <div class="flex items-center gap-2">
      <!-- Search Input -->
      <div class="relative flex-1">
        <Search class="absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-[#8e8e93]" />
        <input
          type="text"
          bind:value={searchQuery}
          onkeydown={(e) => e.key === 'Enter' && handleSearchLocation()}
          placeholder="Cari jalan, tempat, atau area cabang..."
          class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] py-2 pr-20 pl-9 text-xs font-medium text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
        />
        <button
          type="button"
          onclick={handleSearchLocation}
          disabled={isSearching || !searchQuery.trim()}
          class="absolute top-1/2 right-1 flex -translate-y-1/2 cursor-pointer items-center gap-1 rounded-lg bg-[#17171c] px-2.5 py-1 text-[11px] font-bold text-white transition-all hover:bg-black disabled:opacity-40"
        >
          {#if isSearching}
            <RefreshCw class="size-3 animate-spin" />
          {:else}
            <span>Cari</span>
          {/if}
        </button>
      </div>

      <!-- Quick Reset to Saved Coordinates Button -->
      {#if savedLatitude !== undefined && savedLongitude !== undefined}
        <button
          type="button"
          onclick={handleResetToSaved}
          class="flex shrink-0 cursor-pointer items-center gap-1.5 rounded-xl border border-[#e5e5ea] bg-white px-2.5 py-2 text-xs font-semibold text-[#17171c] shadow-2xs transition-all hover:bg-[#f8f8fa]"
          title="Kembalikan pin ke koordinat cabang tersimpan"
        >
          <RotateCcw class="size-3.5 text-[#059669]" />
          <span class="hidden sm:inline">Titik Tersimpan</span>
        </button>
      {/if}

      <!-- Quick Locate Button -->
      <button
        type="button"
        onclick={handleLocateDevice}
        disabled={isLocating}
        class="shrink-0 cursor-pointer rounded-xl border border-[#e5e5ea] bg-white p-2 text-[#2563eb] shadow-2xs transition-all hover:bg-[#f8f8fa] disabled:opacity-50"
        title="Gunakan posisi GPS live perangkat saat ini"
      >
        {#if isLocating}
          <RefreshCw class="size-4 animate-spin" />
        {:else}
          <LocateFixed class="size-4" />
        {/if}
      </button>
    </div>

    <!-- Search Results Dropdown -->
    {#if searchResults.length > 0}
      <div
        class="animate-in fade-in absolute inset-x-0 top-full z-1000 mt-1.5 divide-y divide-[#f2f2f4] overflow-hidden rounded-2xl border border-[#e5e5ea] bg-white shadow-xl"
      >
        {#each searchResults as item}
          <button
            type="button"
            onclick={() => selectSearchResult(item)}
            class="group flex w-full cursor-pointer items-start gap-2 px-3.5 py-2.5 text-left text-xs transition-all hover:bg-[#f8f8fa]"
          >
            <MapPin class="mt-0.5 size-3.5 shrink-0 text-[#2563eb]" />
            <div class="min-w-0">
              <span class="block truncate font-bold text-[#17171c] group-hover:text-[#2563eb]">
                {item.display_name.split(',')[0]}
              </span>
              <span class="block truncate text-[10.5px] text-[#8e8e93]">
                {item.display_name}
              </span>
            </div>
          </button>
        {/each}
      </div>
    {/if}
  </div>

  {#if locateMessage}
    <div
      class="animate-in fade-in flex items-center gap-1.5 rounded-xl border border-[#bfdbfe] bg-[#eff6ff] p-2 text-xs font-medium text-[#1e40af]"
    >
      <Navigation class="size-3 shrink-0 text-[#2563eb]" />
      <span>{locateMessage}</span>
    </div>
  {/if}

  <!-- Interactive Leaflet Map Viewport (No top badge, super clean) -->
  <div
    class="relative h-72 w-full overflow-hidden rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] sm:h-80"
  >
    <div bind:this={mapContainer} class="z-0 size-full"></div>

    <!-- Zoom Controls (Bottom Right) -->
    <div class="absolute right-2.5 bottom-2.5 z-500 flex flex-col gap-1">
      <button
        type="button"
        onclick={zoomIn}
        class="flex size-7 cursor-pointer items-center justify-center rounded-lg border border-[#e5e5ea] bg-white text-[#17171c] shadow-xs transition-all hover:bg-[#f8f8fa] active:scale-95"
        title="Perbesar"
      >
        <Plus class="size-3.5" />
      </button>
      <button
        type="button"
        onclick={zoomOut}
        class="flex size-7 cursor-pointer items-center justify-center rounded-lg border border-[#e5e5ea] bg-white text-[#17171c] shadow-xs transition-all hover:bg-[#f8f8fa] active:scale-95"
        title="Perkecil"
      >
        <Minus class="size-3.5" />
      </button>
    </div>

    <!-- Compact Floating Coordinates & Geofence HUD (Bottom Left) -->
    <div
      class="absolute bottom-2.5 left-2.5 z-500 flex items-center gap-2 rounded-xl bg-[#17171c]/90 px-2.5 py-1.5 font-mono text-[11px] text-white shadow-md backdrop-blur-xs"
    >
      <div class="flex items-center gap-1">
        <MapPin class="size-3 text-[#60a5fa]" />
        <span>{latitude.toFixed(6)}, {longitude.toFixed(6)}</span>
      </div>
      <span class="text-[#71717a]">|</span>
      <span class="text-[#d4d4d8]">Radius: <strong>{radiusMeters}m</strong></span>
    </div>
  </div>
</div>
