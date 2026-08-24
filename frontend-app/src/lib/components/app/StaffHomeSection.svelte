<script lang="ts">
  import {
    Clock,
    Camera,
    MapPin,
    Calendar,
    ChevronRight,
    AlertCircle,
    CheckCircle2,
    ArrowRight,
    Mail,
    Plus,
    RefreshCw
  } from 'lucide-svelte';
  import type { User, AttendanceRecord, ShiftRosterItem, UserWorkspace } from '../../types/app';

  interface Props {
    currentUser: User;
    userWorkspaces?: UserWorkspace[];
    todayAttendance: AttendanceRecord | null;
    rosterShifts?: ShiftRosterItem[];
    onNavigate: (domain: 'home' | 'presensi' | 'shift' | 'finance') => void;
    onOpenCreateWorkspaceModal?: () => void;
    onRefreshSession?: () => void;
  }

  let {
    currentUser,
    userWorkspaces = [],
    todayAttendance,
    rosterShifts = [],
    onNavigate,
    onOpenCreateWorkspaceModal,
    onRefreshSession,
  }: Props = $props();

  let liveTime = $state('');
  let todayDateStr = $state('');
  let isCheckingInvitation = $state(false);

  $effect(() => {
    const update = () => {
      const now = new Date();
      liveTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
      todayDateStr = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
    };
    update();
    const interval = setInterval(update, 1000);
    return () => clearInterval(interval);
  });

  let hasWorkspace = $derived(userWorkspaces.length > 0 || (!!currentUser.branch_id && currentUser.branch_name !== ''));

  let upcomingShifts = $derived(
    rosterShifts
      .filter((s) => s.assigned_user.id === currentUser.id || s.actual_user?.id === currentUser.id)
      .slice(0, 3)
  );

  async function handleCheckInvite() {
    if (!onRefreshSession) return;
    isCheckingInvitation = true;
    try {
      await onRefreshSession();
    } finally {
      setTimeout(() => {
        isCheckingInvitation = false;
      }, 500);
    }
  }
</script>

<div class="space-y-3 sm:space-y-4 max-w-4xl mx-auto font-sans pb-6">
  <!-- Status Strip: Branch Info, Geofence & Live WIB Clock -->
  <div class="bg-[#17171c] text-white p-3.5 sm:p-4 rounded-2xl flex items-center justify-between gap-3 shadow-none">
    <div class="flex items-center gap-2.5 min-w-0">
      <div class={`w-2 h-2 rounded-full shrink-0 ${hasWorkspace ? 'bg-[#00875a]' : 'bg-[#93939f]'}`}></div>
      <div class="truncate">
        <div class="text-xs sm:text-sm font-medium text-white truncate">
          {hasWorkspace ? (currentUser.branch_name || 'Outlet Aktif') : 'Belum Terhubung ke Workspace'}
        </div>
        <div class="text-[10px] text-[#93939f] font-mono flex items-center gap-1">
          {#if hasWorkspace}
            <MapPin class="w-3 h-3 text-[#00875a] shrink-0" />
            <span class="truncate">GPS Geofence Aktif (Radius 50m)</span>
          {:else}
            <span class="truncate">Status: Akun Standalone / Menunggu Undangan Tim</span>
          {/if}
        </div>
      </div>
    </div>

    <div class="text-right shrink-0 font-mono">
      <div class="text-xs sm:text-sm font-medium text-white">{liveTime} <span class="text-[10px] text-[#93939f]">WIB</span></div>
      <div class="text-[10px] text-[#93939f]">{todayDateStr}</div>
    </div>
  </div>

  {#if !hasWorkspace}
    <!-- Standalone / Unlinked Workspace Card -->
    <div class="bg-white border border-[#d9d9dd] rounded-2xl p-5 space-y-4 shadow-none">
      <div class="flex items-start justify-between gap-3 border-b border-[#f2f2f2] pb-3.5">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-[#eeece7] border border-[#d9d9dd] flex items-center justify-center text-[#17171c]">
            <Mail class="w-4 h-4" />
          </div>
          <div>
            <h2 class="text-sm font-medium text-[#212121]">Belum Ada Outlet &amp; Jadwal Shift</h2>
            <p class="text-[11px] text-[#75758a]">Akun personal Anda belum terhubung ke workspace outlet bisnis mana pun.</p>
          </div>
        </div>
        <span class="text-[10px] font-mono px-2.5 py-1 rounded-full bg-[#eeece7] text-[#616161] font-medium shrink-0">
          Standalone
        </span>
      </div>

      <div class="bg-[#eeece7]/40 border border-[#d9d9dd] rounded-xl p-3.5 text-xs text-[#616161] space-y-1 leading-relaxed">
        <div>Minta pemilik bisnis outlet Anda untuk mengirimkan email undangan ke:</div>
        <div class="font-mono text-xs font-semibold text-[#17171c]">{currentUser.email}</div>
      </div>

      <div class="flex flex-col sm:flex-row items-center gap-2 pt-1">
        {#if onRefreshSession}
          <button
            type="button"
            onclick={handleCheckInvite}
            disabled={isCheckingInvitation}
            class="w-full sm:w-auto flex-1 py-2.5 px-4 border border-[#d9d9dd] hover:bg-[#eeece7] text-[#17171c] text-xs font-medium rounded-xl flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50"
          >
            <RefreshCw class={`w-3.5 h-3.5 ${isCheckingInvitation ? 'animate-spin' : ''}`} />
            <span>{isCheckingInvitation ? 'Memeriksa...' : 'Cek Status Undangan'}</span>
          </button>
        {/if}

        {#if onOpenCreateWorkspaceModal}
          <button
            type="button"
            onclick={onOpenCreateWorkspaceModal}
            class="w-full sm:w-auto py-2.5 px-4 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-xl flex items-center justify-center gap-2 transition-all cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Buat Bisnis Baru</span>
          </button>
        {/if}
      </div>
    </div>
  {:else}
    <!-- Today's Shift Card -->
    <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 space-y-3.5 shadow-none">
      <div class="flex items-center justify-between gap-2 border-b border-[#f2f2f2] pb-3">
        <div class="flex items-center gap-2">
          <Clock class="w-4 h-4 text-[#1863dc]" />
          <div>
            <h2 class="text-xs sm:text-sm font-medium text-[#212121]">Shift Pagi Hari Ini</h2>
            <p class="text-[10px] font-mono text-[#75758a]">07:00 - 15:00 WIB</p>
          </div>
        </div>

        <div>
          {#if !todayAttendance}
            <span class="inline-flex items-center gap-1 text-[10px] font-mono text-[#e5484d] bg-[#ffefef] px-2.5 py-1 rounded-full font-medium">
              <AlertCircle class="w-3 h-3" />
              <span>Belum Presensi</span>
            </span>
          {:else if !todayAttendance.clock_out_time}
            <span class="inline-flex items-center gap-1 text-[10px] font-mono text-[#1863dc] bg-[#f1f5ff] px-2.5 py-1 rounded-full font-medium">
              <CheckCircle2 class="w-3 h-3" />
              <span>Shift Berjalan</span>
            </span>
          {:else}
            <span class="inline-flex items-center gap-1 text-[10px] font-mono text-[#00875a] bg-[#edfce9] px-2.5 py-1 rounded-full font-medium">
              <CheckCircle2 class="w-3 h-3" />
              <span>Shift Selesai</span>
            </span>
          {/if}
        </div>
      </div>

      <!-- Shift Action CTA -->
      {#if !todayAttendance}
        <div class="space-y-3">
          <div class="text-[11px] text-[#616161] bg-[#fbfbfb] border border-[#d9d9dd]/60 p-2.5 rounded-xl flex items-center justify-between">
            <span>Toleransi batas masuk: <strong>15 Menit</strong></span>
            <span class="font-mono text-[#e5484d] text-[10px]">Denda: Rp 2.000/mnt</span>
          </div>

          <button
            type="button"
            onclick={() => onNavigate('presensi')}
            class="w-full py-3 bg-[#17171c] hover:bg-black active:scale-[0.99] text-white font-medium text-xs rounded-xl flex items-center justify-center gap-2 cursor-pointer transition-all shadow-none"
          >
            <Camera class="w-4 h-4" />
            <span>Buka Kamera Presensi Masuk</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </button>
        </div>
      {:else if !todayAttendance.clock_out_time}
        <div class="space-y-3">
          <div class="p-3 bg-[#fbfbfb] border border-[#d9d9dd] rounded-xl flex items-center gap-3">
            <div class="w-12 aspect-[3/4] rounded-lg overflow-hidden border border-[#d9d9dd] bg-[#17171c] shrink-0">
              <img
                src={todayAttendance.photo_in_url}
                alt="Selfie Presensi Masuk"
                class="w-full h-full object-cover"
              />
            </div>
            <div class="flex-1 text-xs min-w-0 space-y-0.5">
              <div class="font-medium text-[#212121] truncate">Presensi Masuk Tercatat</div>
              <div class="font-mono text-[#75758a] text-[10px]">Masuk: {todayAttendance.clock_in_time} WIB</div>
              <div class={`text-[10px] font-mono font-medium ${todayAttendance.status === 'ON_TIME' ? 'text-[#00875a]' : 'text-[#e5484d]'}`}>
                {todayAttendance.status === 'ON_TIME' ? 'Status: Tepat Waktu' : `Terlambat ${todayAttendance.late_minutes} menit`}
              </div>
            </div>
          </div>

          <button
            type="button"
            onclick={() => onNavigate('presensi')}
            class="w-full py-3 bg-[#e5484d] hover:bg-[#c93b40] active:scale-[0.99] text-white font-medium text-xs rounded-xl flex items-center justify-center gap-2 cursor-pointer transition-all shadow-none"
          >
            <Camera class="w-4 h-4" />
            <span>Buka Kamera Presensi Keluar (Clock-Out)</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </button>
        </div>
      {:else}
        <div class="p-3 bg-[#edfce9] border border-[#00875a]/20 rounded-xl flex items-center gap-3">
          <div class="w-12 aspect-[3/4] rounded-lg overflow-hidden border border-[#00875a]/30 bg-[#17171c] shrink-0">
            <img
              src={todayAttendance.photo_out_url || todayAttendance.photo_in_url}
              alt="Selfie Presensi"
              class="w-full h-full object-cover"
            />
          </div>
          <div class="flex-1 text-xs min-w-0 space-y-0.5">
            <div class="font-medium text-[#003c33] truncate">Presensi Hari Ini Selesai</div>
            <div class="font-mono text-[#616161] text-[10px]">
              Masuk: {todayAttendance.clock_in_time} • Keluar: {todayAttendance.clock_out_time}
            </div>
            <div class="text-[10px] font-mono text-[#00875a]">
              {todayAttendance.overtime_minutes ? `Lembur: ${todayAttendance.overtime_minutes} menit` : 'Jam kerja terpenuhi'}
            </div>
          </div>
        </div>
      {/if}
    </div>
  {/if}

  <!-- Financial Shortcuts: 2-Column Grid -->
  <div class="grid grid-cols-2 gap-3">
    <!-- Kasbon Card -->
    <button
      type="button"
      onclick={() => onNavigate('finance')}
      class="bg-white border border-[#d9d9dd] hover:border-[#17171c] rounded-2xl p-3.5 text-left transition-all cursor-pointer group shadow-none flex flex-col justify-between space-y-2"
    >
      <div class="flex justify-between items-center text-[10px] font-mono text-[#75758a]">
        <span>Sisa Kasbon</span>
        <ChevronRight class="w-3.5 h-3.5 text-[#93939f] group-hover:text-[#212121] transition-transform" />
      </div>
      <div>
        <div class="text-base sm:text-lg font-medium font-mono text-[#212121]">
          {hasWorkspace ? 'Rp 150.000' : 'Rp 0'}
        </div>
        <div class="text-[9px] text-[#93939f] truncate mt-0.5">
          {hasWorkspace ? 'Ajukan pinjaman →' : 'Profil & Rekening →'}
        </div>
      </div>
    </button>

    <!-- Slip Gaji Card -->
    <button
      type="button"
      onclick={() => onNavigate('finance')}
      class="bg-white border border-[#d9d9dd] hover:border-[#17171c] rounded-2xl p-3.5 text-left transition-all cursor-pointer group shadow-none flex flex-col justify-between space-y-2"
    >
      <div class="flex justify-between items-center text-[10px] font-mono text-[#75758a]">
        <span>Gaji Bersih</span>
        <ChevronRight class="w-3.5 h-3.5 text-[#93939f] group-hover:text-[#212121] transition-transform" />
      </div>
      <div>
        <div class={`text-base sm:text-lg font-medium font-mono ${hasWorkspace ? 'text-[#00875a]' : 'text-[#616161]'}`}>
          {hasWorkspace ? 'Rp 3.120.000' : 'Rp 0'}
        </div>
        <div class="text-[9px] text-[#93939f] truncate mt-0.5">
          {hasWorkspace ? 'Periode Agustus 2026' : 'Belum terdaftar payroll'}
        </div>
      </div>
    </button>
  </div>

  <!-- Upcoming Schedule Mini-List -->
  {#if upcomingShifts.length > 0}
    <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 space-y-2.5 shadow-none">
      <div class="flex items-center justify-between border-b border-[#f2f2f2] pb-2">
        <div class="flex items-center gap-2">
          <Calendar class="w-4 h-4 text-[#17171c]" />
          <h3 class="text-xs font-medium text-[#212121]">Jadwal Shift Mendatang</h3>
        </div>
        <button
          type="button"
          onclick={() => onNavigate('shift')}
          class="text-[10px] font-mono text-[#1863dc] hover:underline cursor-pointer"
        >
          Lihat Semua &rarr;
        </button>
      </div>

      <div class="divide-y divide-[#f2f2f2]">
        {#each upcomingShifts as shift}
          <div class="py-2 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
            <div>
              <div class="font-medium text-[#212121]">{shift.template?.name || 'Shift Reguler'}</div>
              <div class="text-[10px] text-[#75758a] font-mono">
                {shift.date} • {shift.template?.expected_clock_in || '07:00'} - {shift.template?.expected_clock_out || '15:00'} WIB
              </div>
            </div>
            <span class="text-[9px] font-mono px-2 py-0.5 rounded-full bg-[#eeece7] text-[#616161]">
              {shift.is_swap ? 'Tukar' : 'Reguler'}
            </span>
          </div>
        {/each}
      </div>
    </div>
  {/if}
</div>
