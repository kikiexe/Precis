<script lang="ts">
  import { Camera, Calendar, Wallet, ShieldCheck } from 'lucide-svelte';

  interface Props {
    activeTab: 'presensi' | 'shift' | 'finance' | 'admin';
    pendingApprovalsCount?: number;
    onSelectTab: (tab: 'presensi' | 'shift' | 'finance' | 'admin') => void;
  }

  let { activeTab = 'presensi', pendingApprovalsCount = 0, onSelectTab }: Props = $props();
</script>

<nav class="bg-white/95 backdrop-blur-md border-t border-[#d9d9dd] flex items-center justify-around h-16 shrink-0 fixed bottom-0 left-0 right-0 z-40 select-none max-w-md mx-auto font-sans shadow-none">
  <!-- Tab 1: Presensi / Home -->
  <button
    type="button"
    onclick={() => onSelectTab('presensi')}
    class={`flex-1 h-full flex flex-col items-center justify-center gap-1 transition-all cursor-pointer ${
      activeTab === 'presensi' ? 'text-[#17171c] font-medium' : 'text-[#75758a] hover:text-[#212121]'
    }`}
  >
    <div class={`p-1 rounded-full ${activeTab === 'presensi' ? 'bg-[#eeece7]' : ''}`}>
      <Camera class="w-4 h-4" />
    </div>
    <span class="text-[10px]">Presensi</span>
  </button>

  <!-- Tab 2: Shift -->
  <button
    type="button"
    onclick={() => onSelectTab('shift')}
    class={`flex-1 h-full flex flex-col items-center justify-center gap-1 transition-all cursor-pointer ${
      activeTab === 'shift' ? 'text-[#17171c] font-medium' : 'text-[#75758a] hover:text-[#212121]'
    }`}
  >
    <div class={`p-1 rounded-full ${activeTab === 'shift' ? 'bg-[#eeece7]' : ''}`}>
      <Calendar class="w-4 h-4" />
    </div>
    <span class="text-[10px]">Jadwal Shift</span>
  </button>

  <!-- Tab 3: Kasbon & Payroll -->
  <button
    type="button"
    onclick={() => onSelectTab('finance')}
    class={`flex-1 h-full flex flex-col items-center justify-center gap-1 transition-all cursor-pointer ${
      activeTab === 'finance' ? 'text-[#17171c] font-medium' : 'text-[#75758a] hover:text-[#212121]'
    }`}
  >
    <div class={`p-1 rounded-full ${activeTab === 'finance' ? 'bg-[#eeece7]' : ''}`}>
      <Wallet class="w-4 h-4" />
    </div>
    <span class="text-[10px]">Kasbon &amp; Gaji</span>
  </button>

  <!-- Tab 4: Audit Admin "Wall of Faces" -->
  <button
    type="button"
    onclick={() => onSelectTab('admin')}
    class={`flex-1 h-full flex flex-col items-center justify-center gap-1 transition-all cursor-pointer relative ${
      activeTab === 'admin' ? 'text-[#17171c] font-medium' : 'text-[#75758a] hover:text-[#212121]'
    }`}
  >
    <div class="relative">
      <div class={`p-1 rounded-full ${activeTab === 'admin' ? 'bg-[#eeece7]' : ''}`}>
        <ShieldCheck class="w-4 h-4" />
      </div>
      {#if pendingApprovalsCount > 0}
        <span class="absolute -top-1 -right-1.5 bg-[#ff7759] text-white text-[9px] w-4 h-4 rounded-full flex items-center justify-center font-mono font-medium">
          {pendingApprovalsCount}
        </span>
      {/if}
    </div>
    <span class="text-[10px]">Audit &amp; Owner</span>
  </button>
</nav>
