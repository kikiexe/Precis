<script lang="ts">
  import { Camera, Calendar, Wallet, ShieldCheck } from 'lucide-svelte';

  interface Props {
    activeTab: 'presensi' | 'shift' | 'finance' | 'admin';
    pendingApprovalsCount?: number;
    onSelectTab: (tab: 'presensi' | 'shift' | 'finance' | 'admin') => void;
  }

  let { activeTab = 'presensi', pendingApprovalsCount = 0, onSelectTab }: Props = $props();
</script>

<nav class="bg-white border-t border-[#e0e0e0] flex items-center justify-around h-16 shrink-0 fixed bottom-0 left-0 right-0 z-40 select-none max-w-md mx-auto shadow-lg">
  <!-- Tab 1: Presensi / Home -->
  <button
    type="button"
    onclick={() => onSelectTab('presensi')}
    class={`flex-1 h-full flex flex-col items-center justify-center gap-1 transition-colors cursor-pointer ${
      activeTab === 'presensi' ? 'text-[#0f62fe] font-semibold' : 'text-[#525252] hover:text-[#161616]'
    }`}
  >
    <Camera class="w-5 h-5" />
    <span class="text-[10px] font-mono">Presensi</span>
  </button>

  <!-- Tab 2: Shift -->
  <button
    type="button"
    onclick={() => onSelectTab('shift')}
    class={`flex-1 h-full flex flex-col items-center justify-center gap-1 transition-colors cursor-pointer ${
      activeTab === 'shift' ? 'text-[#0f62fe] font-semibold' : 'text-[#525252] hover:text-[#161616]'
    }`}
  >
    <Calendar class="w-5 h-5" />
    <span class="text-[10px] font-mono">Jadwal Shift</span>
  </button>

  <!-- Tab 3: Kasbon & Payroll -->
  <button
    type="button"
    onclick={() => onSelectTab('finance')}
    class={`flex-1 h-full flex flex-col items-center justify-center gap-1 transition-colors cursor-pointer ${
      activeTab === 'finance' ? 'text-[#0f62fe] font-semibold' : 'text-[#525252] hover:text-[#161616]'
    }`}
  >
    <Wallet class="w-5 h-5" />
    <span class="text-[10px] font-mono">Kasbon &amp; Gaji</span>
  </button>

  <!-- Tab 4: Audit Admin "Wall of Faces" -->
  <button
    type="button"
    onclick={() => onSelectTab('admin')}
    class={`flex-1 h-full flex flex-col items-center justify-center gap-1 transition-colors cursor-pointer relative ${
      activeTab === 'admin' ? 'text-[#0f62fe] font-semibold' : 'text-[#525252] hover:text-[#161616]'
    }`}
  >
    <div class="relative">
      <ShieldCheck class="w-5 h-5" />
      {#if pendingApprovalsCount > 0}
        <span class="absolute -top-1 -right-2 bg-[#da1e28] text-white text-[9px] w-4 h-4 rounded-full flex items-center justify-center font-bold">
          {pendingApprovalsCount}
        </span>
      {/if}
    </div>
    <span class="text-[10px] font-mono">Audit &amp; Owner</span>
  </button>
</nav>
