<script lang="ts">
  import { Building2, X, Plus, Sparkles, ArrowRight } from 'lucide-svelte';

  interface Props {
    isOpen: boolean;
    isOnboarding?: boolean;
    onClose: () => void;
    onSubmit: (name: string, branchName: string) => Promise<void>;
  }

  let { isOpen, isOnboarding = false, onClose, onSubmit }: Props = $props();

  let workspaceName = $state('');
  let branchName = $state('Cabang Utama #01');
  let isSubmitting = $state(false);
  let errorMessage = $state<string | null>(null);

  async function handleCreate() {
    if (!workspaceName.trim()) {
      errorMessage = 'Nama workspace / brand bisnis wajib diisi.';
      return;
    }

    isSubmitting = true;
    errorMessage = null;

    try {
      await onSubmit(workspaceName.trim(), branchName.trim() || 'Cabang Utama');
      workspaceName = '';
      branchName = 'Cabang Utama #01';
      onClose();
    } catch (e: unknown) {
      errorMessage = e instanceof Error ? e.message : 'Gagal membuat workspace.';
    } finally {
      isSubmitting = false;
    }
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans select-none">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-lg p-6 sm:p-7 space-y-5 shadow-xl animate-in fade-in zoom-in-95 duration-150">
      <div class="flex items-center justify-between pb-3 border-b border-[#f2f2f4]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#17171c] text-white flex items-center justify-center">
            {#if isOnboarding}
              <Sparkles class="w-5 h-5 text-[#34d399]" />
            {:else}
              <Building2 class="w-5 h-5" />
            {/if}
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">
              {isOnboarding ? 'Selamat Datang! Siapkan Usaha Anda' : 'Buat Workspace Bisnis Baru'}
            </h3>
            <p class="text-xs text-[#8e8e93]">
              {isOnboarding ? 'Mulai trial 14 hari dengan brand & outlet pertama Anda' : 'Tambah brand F&B baru di bawah akun langganan Anda'}
            </p>
          </div>
        </div>
        {#if !isOnboarding}
          <button
            type="button"
            onclick={onClose}
            class="p-2 rounded-xl text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] cursor-pointer transition-all"
          >
            <X class="w-5 h-5" />
          </button>
        {/if}
      </div>

      {#if errorMessage}
        <div class="p-3.5 bg-[#fef2f2] text-[#991b1b] text-xs font-medium rounded-xl border border-[#fecaca]">
          {errorMessage}
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="ws-name" class="block font-bold text-[#17171c]">Nama Workspace / Brand Usaha</label>
          <input
            id="ws-name"
            type="text"
            bind:value={workspaceName}
            placeholder="Contoh: Kopi Senja Artisan"
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
        </div>

        <div class="space-y-1.5">
          <label for="branch-name" class="block font-bold text-[#17171c]">Nama Cabang Outlet Pertama</label>
          <input
            id="branch-name"
            type="text"
            bind:value={branchName}
            placeholder="Contoh: Outlet Sleman #01"
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
          <span class="text-[11px] text-[#8e8e93]">Outlet pertama akan otomatis disiapkan untuk POS kasir dan GPS presensi.</span>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        {#if !isOnboarding}
          <button
            type="button"
            onclick={onClose}
            class="flex-1 py-3 border border-[#e5e5ea] hover:bg-[#f4f4f6] text-[#686873] text-xs font-semibold rounded-full cursor-pointer transition-all"
          >
            Batal
          </button>
        {/if}
        <button
          type="button"
          disabled={isSubmitting || !workspaceName.trim()}
          onclick={handleCreate}
          class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs"
        >
          {#if isSubmitting}
            <span>Membuat Workspace...</span>
          {:else if isOnboarding}
            <span>Mulai Gunakan Précis</span>
            <ArrowRight class="w-4 h-4" />
          {:else}
            <Plus class="w-4 h-4" />
            <span>Buat Workspace</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
