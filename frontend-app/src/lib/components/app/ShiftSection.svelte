<script lang="ts">
  import { RefreshCw, Calendar, Plus, Clock, AlertCircle, Layers, ChevronDown, X } from 'lucide-svelte';
  import type { User, ShiftRosterItem, ShiftTemplateItem } from '../../types/app';
  import { apiClient } from '../../services/api-client';

  interface Props {
    currentUser: User;
    allUsers?: User[];
    rosterShifts?: ShiftRosterItem[];
    shiftTemplates?: ShiftTemplateItem[];
    onSubmitSwap: (shiftAssignmentId: string, targetUserId: string) => Promise<void>;
    onAssignShift?: (shiftTemplateId: string, assignedUserId: string, date: string) => Promise<void>;
    onRefreshTemplates?: () => Promise<void>;
  }

  let {
    currentUser,
    allUsers = [],
    rosterShifts = [],
    shiftTemplates = [],
    onSubmitSwap,
    onAssignShift,
    onRefreshTemplates,
  }: Props = $props();

  let isSwapModalOpen = $state(false);
  let isAssignModalOpen = $state(false);
  let isTemplateBuilderOpen = $state(false);

  // form swap
  let selectedShiftAssignmentId = $state('');
  let targetUserId = $state('');
  let isSubmittingSwap = $state(false);
  let swapErrorMessage = $state<string | null>(null);

  // form assign
  let assignUserId = $state('');
  let assignDate = $state('');
  let assignTemplateId = $state('');
  let isSubmittingAssign = $state(false);
  let assignErrorMessage = $state<string | null>(null);

  // Shift Template Builder
  let templateName = $state('');
  let templateClockIn = $state('07:00');
  let templateClockOut = $state('15:00');
  let templateLateTolerance = $state(15);
  let templateMinOvertime = $state(60);
  let isSubmittingTemplate = $state(false);
  let templateError = $state<string | null>(null);
  let templateSuccess = $state<string | null>(null);

  let myShifts = $derived(
    rosterShifts.filter(
      (s) => s.assigned_user.id === currentUser.id || s.actual_user?.id === currentUser.id
    )
  );

  let availableColleagues = $derived(
    allUsers.filter((u) => u.id !== currentUser.id)
  );

  async function handleConfirmSwap() {
    if (!selectedShiftAssignmentId || !targetUserId) {
      swapErrorMessage = 'Pilih shift Anda dan rekan kerja pengganti.';
      return;
    }

    isSubmittingSwap = true;
    swapErrorMessage = null;

    try {
      await onSubmitSwap(selectedShiftAssignmentId, targetUserId);
      isSwapModalOpen = false;
      selectedShiftAssignmentId = '';
      targetUserId = '';
    } catch (err: unknown) {
      swapErrorMessage = err instanceof Error ? err.message : 'Gagal mengajukan pertukaran shift.';
    } finally {
      isSubmittingSwap = false;
    }
  }

  async function handleConfirmAssign() {
    if (!onAssignShift) return;
    if (!assignTemplateId || !assignUserId || !assignDate) {
      assignErrorMessage = 'Lengkapi seluruh data penetapan shift.';
      return;
    }

    isSubmittingAssign = true;
    assignErrorMessage = null;

    try {
      await onAssignShift(assignTemplateId, assignUserId, assignDate);
      isAssignModalOpen = false;
      assignUserId = '';
      assignDate = '';
      assignTemplateId = '';
    } catch (err: unknown) {
      assignErrorMessage = err instanceof Error ? err.message : 'Gagal menetapkan shift.';
    } finally {
      isSubmittingAssign = false;
    }
  }

  async function handleCreateTemplate() {
    if (!templateName.trim() || !templateClockIn || !templateClockOut) {
      templateError = 'Nama shift, jam masuk, dan jam pulang wajib diisi.';
      return;
    }

    isSubmittingTemplate = true;
    templateError = null;
    templateSuccess = null;

    try {
      await apiClient.post('/shifts/templates', {
        name: templateName.trim(),
        expected_clock_in: templateClockIn,
        expected_clock_out: templateClockOut,
        late_tolerance_minutes: templateLateTolerance,
        minimum_overtime_minutes: templateMinOvertime,
      });
      templateSuccess = `Template "${templateName}" berhasil dibuat.`;
      templateName = '';
      templateClockIn = '07:00';
      templateClockOut = '15:00';
      templateLateTolerance = 15;
      templateMinOvertime = 60;
      if (onRefreshTemplates) {
        await onRefreshTemplates();
      }
    } catch (err: unknown) {
      templateError = err instanceof Error ? err.message : 'Gagal membuat template shift.';
    } finally {
      isSubmittingTemplate = false;
    }
  }
</script>

<div class="space-y-3 sm:space-y-4 max-w-4xl mx-auto font-sans pb-6">
  <!-- Header Bar -->
  <div class="flex items-center justify-between gap-3 pb-1">
    <div>
      <h2 class="text-sm sm:text-base font-medium text-[#212121]">Roster Jadwal Shift</h2>
      <p class="text-[11px] text-[#75758a]">Penugasan staf di {currentUser.branch_name || 'Outlet'}</p>
    </div>

    <div class="flex items-center gap-2 shrink-0">
      {#if currentUser.role === 'OWNER' || currentUser.role === 'ADMIN' || currentUser.role === 'MANAGER'}
        <button
          type="button"
          onclick={() => {
            templateError = null;
            templateSuccess = null;
            isTemplateBuilderOpen = !isTemplateBuilderOpen;
          }}
          class={`px-3 py-1.5 text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer transition-all ${
            isTemplateBuilderOpen
              ? 'bg-[#1863dc] text-white'
              : 'bg-white hover:bg-[#eeece7] border border-[#d9d9dd] text-[#212121]'
          }`}
        >
          <Layers class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Template</span>
        </button>

        <button
          type="button"
          onclick={() => {
            assignErrorMessage = null;
            isAssignModalOpen = true;
          }}
          class="bg-[#17171c] hover:bg-black text-white px-3 py-1.5 text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer transition-all"
        >
          <Plus class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Tetapkan</span>
        </button>
      {/if}

      <button
        type="button"
        onclick={() => {
          swapErrorMessage = null;
          isSwapModalOpen = true;
        }}
        class="bg-[#17171c] hover:bg-black text-white px-3 py-1.5 text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer transition-all shadow-none"
      >
        <RefreshCw class="w-3 h-3 text-white" />
        <span>Tukar Shift</span>
      </button>
    </div>
  </div>

  <!-- Shift Template Builder Form (Collapsible) -->
  {#if isTemplateBuilderOpen}
    <div class="bg-white border border-[#1863dc]/30 rounded-2xl p-4 sm:p-5 space-y-4 animate-in fade-in slide-in-from-top-2">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3">
        <div class="flex items-center gap-2">
          <Layers class="w-4 h-4 text-[#1863dc]" />
          <h3 class="text-xs font-bold uppercase tracking-wider text-[#17171c]">Shift Template Builder</h3>
        </div>
        <span class="text-[10px] font-mono text-[#75758a]">POST /shifts/templates</span>
      </div>

      {#if templateError}
        <div class="p-3 bg-[#ffefef] border border-[#fecaca] text-[#e5484d] text-xs rounded-xl flex items-start gap-2">
          <AlertCircle class="w-3.5 h-3.5 shrink-0 mt-0.5" />
          <span>{templateError}</span>
        </div>
      {/if}

      {#if templateSuccess}
        <div class="p-3 bg-[#edfce9] border border-[#bbf7d0] text-[#003c33] text-xs rounded-xl">
          {templateSuccess}
        </div>
      {/if}

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <div class="sm:col-span-2 space-y-1.5">
          <label for="tpl-name" class="block font-medium text-[#212121]">Nama Pola Shift</label>
          <input
            id="tpl-name"
            type="text"
            bind:value={templateName}
            placeholder="Contoh: Middle Barista, Shift Malam, Weekend Open"
            class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1.5">
          <label for="tpl-clock-in" class="block font-medium text-[#212121]">Jam Masuk (Clock In)</label>
          <input
            id="tpl-clock-in"
            type="time"
            bind:value={templateClockIn}
            class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1.5">
          <label for="tpl-clock-out" class="block font-medium text-[#212121]">Jam Pulang (Clock Out)</label>
          <input
            id="tpl-clock-out"
            type="time"
            bind:value={templateClockOut}
            class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1.5">
          <label for="tpl-late-tolerance" class="block font-medium text-[#212121]">
            Batas Toleransi Telat ({templateLateTolerance} menit)
          </label>
          <input
            id="tpl-late-tolerance"
            type="range"
            min="0"
            max="60"
            step="5"
            bind:value={templateLateTolerance}
            class="w-full accent-[#17171c] cursor-pointer"
          />
          <span class="text-[10px] text-[#75758a]">Keterlambatan di atas nilai ini akan dihitung denda</span>
        </div>

        <div class="space-y-1.5">
          <label for="tpl-min-overtime" class="block font-medium text-[#212121]">
            Syarat Minimum Lembur ({templateMinOvertime} menit)
          </label>
          <input
            id="tpl-min-overtime"
            type="range"
            min="15"
            max="120"
            step="15"
            bind:value={templateMinOvertime}
            class="w-full accent-[#17171c] cursor-pointer"
          />
          <span class="text-[10px] text-[#75758a]">Kelebihan jam kerja di bawah batas ini tidak dihitung lembur</span>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2 border-t border-[#d9d9dd]">
        <button
          type="button"
          onclick={() => (isTemplateBuilderOpen = false)}
          class="px-4 py-2 border border-[#d9d9dd] hover:bg-[#eeece7]/40 text-[#616161] text-xs font-medium rounded-full cursor-pointer transition-all"
        >
          Tutup
        </button>
        <button
          type="button"
          disabled={isSubmittingTemplate || !templateName.trim()}
          onclick={handleCreateTemplate}
          class="px-5 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50 flex items-center gap-1.5"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>{isSubmittingTemplate ? 'Menyimpan...' : 'Buat Template Shift'}</span>
        </button>
      </div>
    </div>
  {/if}

  <!-- Kalender Roster Shift -->
  {#if rosterShifts.length === 0}
    <div class="bg-white border border-[#d9d9dd] rounded-2xl p-8 text-center space-y-2 shadow-none">
      <Calendar class="w-7 h-7 text-[#93939f] mx-auto opacity-50" />
      <h3 class="text-xs font-medium text-[#212121]">Belum Ada Jadwal Shift Roster</h3>
      <p class="text-[11px] text-[#75758a]">Jadwal penugasan shift mingguan belum diterbitkan oleh Store Manager / Owner.</p>
    </div>
  {:else}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      {#each rosterShifts as shift}
        <div class="p-3.5 border bg-white border-[#d9d9dd] hover:border-[#17171c]/40 rounded-2xl transition-all text-xs space-y-2.5">
          <div class="flex items-center justify-between gap-2">
            <span class="font-medium text-[#212121] font-mono text-[11px]">{shift.date}</span>
            {#if shift.is_swap}
              <span class={`text-[9px] font-mono px-2 py-0.5 rounded-full font-medium ${
                shift.swap_status === 'APPROVED'
                  ? 'bg-[#edfce9] text-[#00875a]'
                  : shift.swap_status === 'PENDING'
                  ? 'bg-[#eeece7] text-[#616161]'
                  : 'bg-[#ffefef] text-[#e5484d]'
              }`}>
                Tukar: {shift.swap_status}
              </span>
            {:else}
              <span class="text-[9px] font-mono bg-[#eeece7] text-[#616161] px-2 py-0.5 rounded-full">
                Reguler
              </span>
            {/if}
          </div>

          <div class="space-y-0.5">
            <div class="font-medium text-xs sm:text-sm text-[#212121] truncate">{shift.template?.name || 'Shift Pagi'}</div>
            <div class="flex items-center gap-1.5 text-[11px] text-[#75758a] font-mono">
              <Clock class="w-3 h-3 text-[#93939f] shrink-0" />
              <span>{shift.template?.expected_clock_in || '07:00'} - {shift.template?.expected_clock_out || '15:00'} WIB</span>
            </div>
          </div>

          <div class="pt-2 border-t border-[#f2f2f2] flex items-center justify-between text-[11px]">
            <span class="text-[#75758a]">Petugas:</span>
            <span class="font-medium text-[#17171c] truncate">{shift.assigned_user.name}</span>
          </div>
          {#if shift.is_swap && shift.actual_user}
            <div class="flex items-center justify-between text-[11px] text-[#1863dc]">
              <span>Pengganti:</span>
              <span class="font-medium truncate">{shift.actual_user.name}</span>
            </div>
          {/if}
        </div>
      {/each}
    </div>
  {/if}
</div>

<!-- modal pengajuan tukar shift staf -->
{#if isSwapModalOpen}
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-md w-full p-6 shadow-none space-y-5 animate-in fade-in zoom-in-95 font-sans">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2">
          <RefreshCw class="w-4 h-4 text-[#1863dc]" />
          <h3 class="font-medium text-base text-[#212121]">Permohonan Tukar Shift</h3>
        </div>
        <button
          type="button"
          onclick={() => (isSwapModalOpen = false)}
          class="text-[#93939f] hover:text-[#212121] cursor-pointer text-sm font-mono p-1"
        >
          ✕
        </button>
      </div>

      {#if swapErrorMessage}
        <div class="p-3 bg-[#ffad9b]/15 border border-[#ffad9b] rounded-xl text-[#b30000] text-xs flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{swapErrorMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div>
          <label for="swap-shift-select" class="block font-medium text-[#212121] mb-1.5">Pilih Jadwal Shift Anda</label>
          {#if myShifts.length === 0}
            <div class="p-3 bg-[#eeece7]/40 text-[#75758a] text-xs font-mono rounded-2xl border border-[#d9d9dd]">
              Anda tidak memiliki jadwal shift aktif pada roster saat ini.
            </div>
          {:else}
            <div class="relative">
              <select
                id="swap-shift-select"
                bind:value={selectedShiftAssignmentId}
                class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
              >
                <option value="">Pilih Shift yang Ingin Ditukar...</option>
                {#each myShifts as s}
                  <option value={s.id}>
                    {s.date} • {s.template?.name || 'Shift'} ({s.template?.expected_clock_in} - {s.template?.expected_clock_out})
                  </option>
                {/each}
              </select>
              <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          {/if}
        </div>

        <div>
          <label for="swap-target-user" class="block font-medium text-[#212121] mb-1.5">Pilih Rekan Pengganti (Staf Cabang)</label>
          <div class="relative">
            <select
              id="swap-target-user"
              bind:value={targetUserId}
              class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              <option value="">Pilih Rekan Kerja Pengganti...</option>
              {#each availableColleagues as col}
                <option value={col.id}>{col.name} ({col.role})</option>
              {/each}
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>
      </div>

      <div class="flex gap-2.5 pt-3 border-t border-[#d9d9dd]">
        <button
          type="button"
          onclick={() => (isSwapModalOpen = false)}
          class="flex-1 py-2.5 border border-[#d9d9dd] hover:bg-[#eeece7]/40 text-[#616161] text-xs font-medium rounded-full cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmittingSwap || !selectedShiftAssignmentId || !targetUserId}
          onclick={handleConfirmSwap}
          class="flex-1 py-2.5 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50"
        >
          {isSubmittingSwap ? 'Mengajukan...' : 'Kirim Permohonan'}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- modal penetapan shift oleh manager/owner -->
{#if isAssignModalOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl p-6 w-full max-w-md space-y-4 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between pb-3 border-b border-[#d9d9dd]">
        <div class="flex items-center gap-2">
          <div class="p-2 bg-[#eeece7] rounded-xl text-[#212121]">
            <Clock class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-medium text-[#212121]">Tetapkan Penugasan Shift</h3>
            <p class="text-xs text-[#75758a]">Alokasikan shift karyawan untuk tanggal tertentu</p>
          </div>
        </div>
        <button
          type="button"
          onclick={() => (isAssignModalOpen = false)}
          class="text-[#75758a] hover:text-[#212121] cursor-pointer"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if assignErrorMessage}
        <div class="p-3 bg-[#ffefef] border border-[#e5484d]/20 text-[#e5484d] text-xs rounded-xl flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{assignErrorMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div>
          <label for="assign-user-select" class="block font-medium text-[#212121] mb-1.5">Pilih Karyawan / Staf</label>
          <div class="relative">
            <select
              id="assign-user-select"
              bind:value={assignUserId}
              class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              <option value="">Pilih Karyawan...</option>
              {#each allUsers as u}
                <option value={u.id}>{u.name} ({u.role})</option>
              {/each}
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>

        <div>
          <label for="assign-date-input" class="block font-medium text-[#212121] mb-1.5">Tanggal Shift (YYYY-MM-DD)</label>
          <input
            id="assign-date-input"
            type="date"
            bind:value={assignDate}
            class="w-full border border-[#d9d9dd] rounded-full px-3.5 py-2 bg-[#eeece7]/40 text-[#212121] text-xs font-mono focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div>
          <label for="assign-template-select" class="block font-medium text-[#212121] mb-1.5">Pilih Template Shift</label>
          <div class="relative">
            <select
              id="assign-template-select"
              bind:value={assignTemplateId}
              class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              <option value="">Pilih Template Shift...</option>
              {#if shiftTemplates.length === 0}
                <option value="" disabled>Belum ada template (buat via tombol Template)</option>
              {:else}
                {#each shiftTemplates as tpl}
                  <option value={tpl.id}>
                    {tpl.name} ({tpl.expected_clock_in} - {tpl.expected_clock_out} WIB)
                  </option>
                {/each}
              {/if}
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>
      </div>

      <div class="flex gap-2.5 pt-3 border-t border-[#d9d9dd]">
        <button
          type="button"
          onclick={() => (isAssignModalOpen = false)}
          class="flex-1 py-2.5 border border-[#d9d9dd] hover:bg-[#eeece7]/40 text-[#616161] text-xs font-medium rounded-full cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmittingAssign || !assignUserId || !assignDate || !assignTemplateId}
          onclick={handleConfirmAssign}
          class="flex-1 py-2.5 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50"
        >
          {isSubmittingAssign ? 'Menyimpan...' : 'Simpan Penugasan'}
        </button>
      </div>
    </div>
  </div>
{/if}
