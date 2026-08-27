export type PermissionStateStatus = 'granted' | 'denied' | 'prompt' | 'unsupported';

export interface AppPermissionsStatus {
  camera: PermissionStateStatus;
  geolocation: PermissionStateStatus;
  notification: PermissionStateStatus;
  allGranted: boolean;
  requiredGranted: boolean;
}

export class PermissionService {
  private readonly STORAGE_CAMERA = 'precis_camera_granted';
  private readonly STORAGE_GEO = 'precis_geo_granted';
  private readonly STORAGE_NOTIF = 'precis_notif_granted';
  private readonly STORAGE_DISMISSED = 'precis_perm_onboarding_done';

  /**
   * Mengecek status izin browser dengan toleransi WebKit/Safari.
   */
  public async checkPermissions(): Promise<AppPermissionsStatus> {
    if (typeof window === 'undefined') {
      return {
        camera: 'prompt',
        geolocation: 'prompt',
        notification: 'prompt',
        allGranted: false,
        requiredGranted: false,
      };
    }

    let cameraStatus: PermissionStateStatus = 'prompt';
    let geoStatus: PermissionStateStatus = 'prompt';
    let notifStatus: PermissionStateStatus = 'prompt';

    // 1. Cek Geolocation
    if ('geolocation' in navigator) {
      if (typeof navigator.permissions?.query === 'function') {
        try {
          const res = await navigator.permissions.query({ name: 'geolocation' });
          geoStatus = res.state as PermissionStateStatus;
        } catch {
          geoStatus = this.getStorageFlag(this.STORAGE_GEO);
        }
      } else {
        geoStatus = this.getStorageFlag(this.STORAGE_GEO);
      }
    } else {
      geoStatus = 'unsupported';
    }

    // 2. Cek Kamera
    if (typeof navigator.mediaDevices?.getUserMedia === 'function') {
      let queried = false;
      if (typeof navigator.permissions?.query === 'function') {
        try {
          const res = await navigator.permissions.query({ name: 'camera' as PermissionName });
          cameraStatus = res.state as PermissionStateStatus;
          queried = true;
        } catch {
          queried = false;
        }
      }
      if (!queried) {
        cameraStatus = this.getStorageFlag(this.STORAGE_CAMERA);
      }
    } else {
      cameraStatus = 'unsupported';
    }

    // 3. Cek Notifikasi (Opsional)
    if ('Notification' in window && typeof window.Notification === 'function') {
      const state = Notification.permission;
      if (state === 'granted') notifStatus = 'granted';
      else if (state === 'denied') notifStatus = 'denied';
      else notifStatus = 'prompt';
    } else {
      notifStatus = 'unsupported';
    }

    // Kamera dan GPS adalah syarat utama presensi (wajib)
    const requiredGranted = cameraStatus === 'granted' && geoStatus === 'granted';
    
    // allGranted terpenuhi jika required granted dan notif sudah ditentukan (granted/denied/unsupported)
    const allGranted = requiredGranted && (notifStatus === 'granted' || notifStatus === 'denied' || notifStatus === 'unsupported');

    return {
      camera: cameraStatus,
      geolocation: geoStatus,
      notification: notifStatus,
      allGranted,
      requiredGranted,
    };
  }

  /**
   * Minta izin akses Kamera
   */
  public async requestCamera(): Promise<PermissionStateStatus> {
    if (typeof window === 'undefined' || !navigator.mediaDevices?.getUserMedia) {
      return 'unsupported';
    }

    try {
      const stream = await navigator.mediaDevices.getUserMedia({ video: true });
      stream.getTracks().forEach((track) => track.stop());
      this.setStorageFlag(this.STORAGE_CAMERA, 'granted');
      return 'granted';
    } catch (err: unknown) {
      if (err instanceof DOMException && (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError')) {
        this.setStorageFlag(this.STORAGE_CAMERA, 'denied');
        return 'denied';
      }
      return 'prompt';
    }
  }

  /**
   * Minta izin akses Lokasi (GPS)
   */
  public async requestGeolocation(): Promise<PermissionStateStatus> {
    if (typeof window === 'undefined' || !('geolocation' in navigator)) {
      return 'unsupported';
    }

    return new Promise((resolve) => {
      navigator.geolocation.getCurrentPosition(
        () => {
          this.setStorageFlag(this.STORAGE_GEO, 'granted');
          resolve('granted');
        },
        (err) => {
          if (err.code === err.PERMISSION_DENIED) {
            this.setStorageFlag(this.STORAGE_GEO, 'denied');
            resolve('denied');
          } else {
            resolve('prompt');
          }
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
      );
    });
  }

  /**
   * Minta izin akses Notifikasi (Opsional)
   */
  public async requestNotification(): Promise<PermissionStateStatus> {
    if (typeof window === 'undefined' || !('Notification' in window) || typeof Notification.requestPermission !== 'function') {
      return 'unsupported';
    }

    try {
      let permissionResult: NotificationPermission = 'default';
      const promiseOrUndefined = Notification.requestPermission((status) => {
        permissionResult = status;
      });

      if (promiseOrUndefined && typeof promiseOrUndefined.then === 'function') {
        permissionResult = await promiseOrUndefined;
      }

      const status: PermissionStateStatus =
        permissionResult === 'granted' ? 'granted' : permissionResult === 'denied' ? 'denied' : 'prompt';
      
      this.setStorageFlag(this.STORAGE_NOTIF, status);
      return status;
    } catch {
      return 'prompt';
    }
  }

  public setDismissed(): void {
    try {
      localStorage.setItem(this.STORAGE_DISMISSED, 'true');
    } catch {
      // abaikan
    }
  }

  public isDismissed(): boolean {
    try {
      return localStorage.getItem(this.STORAGE_DISMISSED) === 'true';
    } catch {
      return false;
    }
  }

  private getStorageFlag(key: string): PermissionStateStatus {
    try {
      const val = localStorage.getItem(key);
      if (val === 'granted' || val === 'denied' || val === 'prompt' || val === 'unsupported') {
        return val;
      }
      return 'prompt';
    } catch {
      return 'prompt';
    }
  }

  private setStorageFlag(key: string, value: string): void {
    try {
      localStorage.setItem(key, value);
    } catch {
      // abaikan
    }
  }
}

export const permissionService = new PermissionService();
