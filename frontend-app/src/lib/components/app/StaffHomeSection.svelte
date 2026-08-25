<script lang="ts">
  import { Mail, RefreshCw, Plus } from 'lucide-svelte';
  import type {
    User,
    AttendanceRecord,
    ShiftRosterItem,
    UserWorkspace,
    PayrollSlipData,
    CashAdvance,
    TeamMember,
  } from '../../types/app';
  import LiveClockCard from './staff-home/LiveClockCard.svelte';
  import TodayShiftCard from './staff-home/TodayShiftCard.svelte';
  import ShiftRosterPreview from './staff-home/ShiftRosterPreview.svelte';
  import QuickActionsCard from './staff-home/QuickActionsCard.svelte';
  import RequestSwapModal from './staff-home/modals/RequestSwapModal.svelte';
  import StaffKasbonModal from './staff-home/modals/StaffKasbonModal.svelte';
  import StaffSlipModal from './staff-home/modals/StaffSlipModal.svelte';

  interface Props {
    currentUser: User;
    userWorkspaces?: UserWorkspace[];
    todayAttendance: AttendanceRecord | null;
    rosterShifts?: ShiftRosterItem[];
    myPayrollSlip?: PayrollSlipData | null;
    myCashAdvances?: CashAdvance[];
    teamMembers?: TeamMember[];
    onNavigate: (domain: 'home' | 'presensi' | 'shift' | 'finance', subTab?: string) => void;
    onOpenCreateWorkspaceModal?: () => void;
    onRefreshSession?: () => void;
    onSubmitSwap?: (shiftAssignmentId: string, targetUserId: string) => Promise<void>;
    onRequestKasbon?: (amount: number, purpose?: string) => Promise<void>;
  }

  let {
    currentUser,
    userWorkspaces = [],
    todayAttendance,
    rosterShifts = [],
    myPayrollSlip = null,
    myCashAdvances = [],
    teamMembers = [],
    onNavigate,
    onOpenCreateWorkspaceModal,
    onRefreshSession,
    onSubmitSwap,
    onRequestKasbon,
  }: Props = $props();

  let liveTime = $state('');
  let todayDateStr = $state('');
  let todayIso = $state('');
  let isCheckingInvitation = $state(false);
  let shiftCountdownText = $state('');

  // Modals state
  let isSwapModalOpen = $state(false);
  let isKasbonModalOpen = $state(false);
  let isSlipModalOpen = $state(false);

  $effect(() => {
    const update = () => {
      const now = new Date();
      liveTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
      todayDateStr = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });

      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      todayIso = `${year}-${month}-${day}`;

      updateCountdown(now);
    };

    update();
    const interval = setInterval(update, 1000);
    return () => clearInterval(interval);
  });

  function updateCountdown(now: Date) {
    if (!todayShift || !todayShift.template) {
      shiftCountdownText = '';
      return;
    }

    const [inH, inM] = todayShift.template.expected_clock_in.split(':').map(Number);
    const [outH, outM] = todayShift.template.expected_clock_out.split(':').map(Number);

    const shiftStart = new Date(now);
    shiftStart.setHours(inH || 0, inM || 0, 0, 0);

    const shiftEnd = new Date(now);
    shiftEnd.setHours(outH || 0, outM || 0, 0, 0);

    if (now < shiftStart) {
      const diffMs = shiftStart.getTime() - now.getTime();
      const diffHrs = Math.floor(diffMs / 3600000);
      const diffMins = Math.floor((diffMs % 3600000) / 60000);
      const diffSecs = Math.floor((diffMs % 60000) / 1000);
      shiftCountdownText = `Mulai dlm ${String(diffHrs).padStart(2, '0')}:${String(diffMins).padStart(2, '0')}:${String(diffSecs).padStart(2, '0')}`;
    } else if (now >= shiftStart && now < shiftEnd) {
      const diffMs = shiftEnd.getTime() - now.getTime();
      const diffHrs = Math.floor(diffMs / 3600000);
      const diffMins = Math.floor((diffMs % 3600000) / 60000);
      const diffSecs = Math.floor((diffMs % 60000) / 1000);
      shiftCountdownText = `Selesai dlm ${String(diffHrs).padStart(2, '0')}:${String(diffMins).padStart(2, '0')}:${String(diffSecs).padStart(2, '0')}`;
    } else {
      shiftCountdownText = 'Shift hari ini telah selesai';
    }
  }

  let hasWorkspace = $derived(
    userWorkspaces.length > 0 || (!!currentUser.branch_id && currentUser.branch_name !== '')
  );

  let todayShift = $derived.by<ShiftRosterItem | null>(() => {
    if (!todayIso || rosterShifts.length === 0) return null;
    return (
      rosterShifts.find(
        (s) =>
          s.date === todayIso &&
          (s.assigned_user.id === currentUser.id || s.actual_user?.id === currentUser.id)
      ) || null
    );
  });

  let eligibleColleagues = $derived(
    teamMembers.filter((m) => m.id !== currentUser.id && m.is_active)
  );

  let myActiveShifts = $derived(
    rosterShifts.filter(
      (s) => s.assigned_user.id === currentUser.id || s.actual_user?.id === currentUser.id
    )
  );

  let totalLateMinutes = $derived(myPayrollSlip?.total_late_minutes || 0);
  let totalLatePenalty = $derived(myPayrollSlip?.late_penalty || totalLateMinutes * 2000);
  let totalOvertimeMinutes = $derived(myPayrollSlip?.total_overtime_minutes || 0);
  let totalOvertimePay = $derived(myPayrollSlip?.overtime_pay || 0);
  let totalActiveKasbon = $derived(
    myCashAdvances.filter((k) => k.status === 'APPROVED').reduce((s, k) => s + k.amount, 0)
  );
  let estimatedTakeHomePay = $derived(
    myPayrollSlip?.net_salary || (currentUser.base_salary || 3000000) + totalOvertimePay - totalLatePenalty - totalActiveKasbon
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
  <LiveClockCard
    {currentUser}
    {hasWorkspace}
    {liveTime}
    {todayDateStr}
  />

  {#if !hasWorkspace}
    <div class="bg-white border border-[#d9d9dd] rounded-2xl p-5 space-y-4 shadow-none">
      <div class="flex items-start justify-between gap-3 border-b border-[#f2f2f2] pb-3.5">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-[#eeece7] border border-[#d9d9dd] flex items-center justify-center text-[#17171c]">
            <Mail class="w-4 h-4" />
          </div>
          <div>
            <h2 class="text-sm font-medium text-[#212121]">Belum Ada Outlet &amp; Jadwal Shift</h2>
            <p class="text-[11px] text-[#75758a]">Akun personal Anda belum terhubung ke workspace bisnis mana pun.</p>
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
    <TodayShiftCard
      {todayShift}
      {todayAttendance}
      {shiftCountdownText}
      onNavigatePresensi={() => onNavigate('presensi')}
    />

    <QuickActionsCard
      {currentUser}
      {myPayrollSlip}
      {myCashAdvances}
      onOpenSwapModal={() => (isSwapModalOpen = true)}
      onOpenKasbonModal={() => (isKasbonModalOpen = true)}
      onOpenSlipModal={() => (isSlipModalOpen = true)}
      onNavigateFinance={() => onNavigate('finance')}
    />

    <ShiftRosterPreview
      {currentUser}
      {rosterShifts}
      onNavigateShift={() => onNavigate('shift')}
    />
  {/if}
</div>

<RequestSwapModal
  isOpen={isSwapModalOpen}
  {myActiveShifts}
  {eligibleColleagues}
  onClose={() => (isSwapModalOpen = false)}
  onSubmit={async (shiftId, targetId) => {
    if (onSubmitSwap) await onSubmitSwap(shiftId, targetId);
  }}
/>

<StaffKasbonModal
  isOpen={isKasbonModalOpen}
  onClose={() => (isKasbonModalOpen = false)}
  onSubmit={async (amount, purpose) => {
    if (onRequestKasbon) await onRequestKasbon(amount, purpose);
  }}
/>

<StaffSlipModal
  isOpen={isSlipModalOpen}
  {currentUser}
  {myPayrollSlip}
  {todayIso}
  {totalLateMinutes}
  {totalLatePenalty}
  {totalOvertimeMinutes}
  {totalOvertimePay}
  {totalActiveKasbon}
  {estimatedTakeHomePay}
  onClose={() => (isSlipModalOpen = false)}
/>
