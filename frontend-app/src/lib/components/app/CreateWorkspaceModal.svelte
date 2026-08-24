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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans select-none">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-md p-6 space-y-4 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-2xl bg-[#17171c] text-white flex items-center justify-center">
            {#if isOnboarding}
              <Sparkles class="w-4 h-4 text-[#edfce9]" />
            {:else}
              <Building2 class="w-4 h-4" />
            {/if}
          </div>
          <div>
            <h3 class="text-base font-semibold text-[#212121]">
              {isOnboarding ? 'Selamat Datang! Siapkan Usaha Anda' : 'Buat Workspace Bisnis Baru'}
            </h3>
            <p class="text-[11px] text-[#75758a]">
              {isOnboarding ? 'Mulai trial 14 hari dengan membuat brand & outlet pertama Anda' : 'Tambah brand F&B baru di bawah akun langganan Anda'}
            </p>
          </div>
        </div>
        {#if !isOnboarding}
          <button
            type="button"
            onclick={onClose}
            class="p-1.5 rounded-full hover:bg-[#eeece7] text-[#616161] hover:text-[#212121] cursor-pointer transition-colors"
          >
            <X class="w-4 h-4" />
          </button>
        {/if}
      </div>

      {#if errorMessage}
        <div class="p-3 bg-[#ffefef] text-[#e5484d] text-xs rounded-[12px] border border-[#d9d9dd]">
          {errorMessage}
        </div>
      {/if}

      <div class="space-y-3.5 text-xs">
        <div class="space-y-1">
          <label for="ws-name" class="block font-medium text-[#212121]">Nama Workspace / Brand Usaha</label>
          <input
            id="ws-name"
            type="text"
            bind:value={workspaceName}
            placeholder="Contoh: Kopi Senja Artisan"
            class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="branch-name" class="block font-medium text-[#212121]">Nama Cabang Outlet Pertama</label>
          <input
            id="branch-name"
            type="text"
            bind:value={branchName}
            placeholder="Contoh: Outlet Sleman #01"
            class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
          <span class="text-[10px] text-[#75758a]">Outlet pertama akan otomatis disiapkan untuk POS kasir dan GPS presensi.</span>
        </div>
      </div>

      <div class="pt-2 space-y-2">
        <button
          type="button"
          onclick={handleCreate}
          disabled={isSubmitting}
          class="w-full py-3 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5 shadow-none"
        >
          {#if isSubmitting}
            <span>Menyiapkan Workspace...</span>
          {:else if isOnboarding}
            <span>Mulai Kelola Bisnis (Trial 14 Hari)</span>
            <ArrowRight class="w-4 h-4" />
          {:else}
            <Plus class="w-3.5 h-3.5" />
            <span>Buat Workspace Baru</span>
          {/if}
        </button>

        {#if isOnboarding}
          <button
            type="button"
            onclick={onClose}
            class="w-full py-2 text-[11px] text-[#616161] hover:text-[#17171c] transition-colors cursor-pointer text-center"
          >
            Lewati langkah ini jika Anda mendaftar sebagai staf/karyawan yang menunggu undangan.
          </button>
        {:else}
          <button
            type="button"
            onclick={onClose}
            class="w-full py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
          >
            Batal
          </button>
        {/if}
      </div>
    </div>
  </div>
{/if}
