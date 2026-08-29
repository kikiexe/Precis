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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-lg space-y-5 rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl duration-150 sm:p-7"
    >
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-2xl bg-[#17171c] text-white"
          >
            {#if isOnboarding}
              <Sparkles class="size-5 text-[#34d399]" />
            {:else}
              <Building2 class="size-5" />
            {/if}
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">
              {isOnboarding ? 'Selamat Datang! Siapkan Usaha Anda' : 'Buat Workspace Bisnis Baru'}
            </h3>
            <p class="text-xs text-[#8e8e93]">
              {isOnboarding
                ? 'Mulai trial 14 hari dengan brand & outlet pertama Anda'
                : 'Tambah brand F&B baru di bawah akun langganan Anda'}
            </p>
          </div>
        </div>
        {#if !isOnboarding}
          <button
            type="button"
            onclick={onClose}
            class="cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
          >
            <X class="size-5" />
          </button>
        {/if}
      </div>

      {#if errorMessage}
        <div
          class="rounded-xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-medium text-[#991b1b]"
        >
          {errorMessage}
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="ws-name" class="block font-bold text-[#17171c]"
            >Nama Workspace / Brand Usaha</label
          >
          <input
            id="ws-name"
            type="text"
            bind:value={workspaceName}
            placeholder="Contoh: Kopi Senja Artisan"
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1.5">
          <label for="branch-name" class="block font-bold text-[#17171c]"
            >Nama Cabang Outlet Pertama</label
          >
          <input
            id="branch-name"
            type="text"
            bind:value={branchName}
            placeholder="Contoh: Outlet Sleman #01"
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
          <span class="text-[11px] text-[#8e8e93]"
            >Outlet pertama akan otomatis disiapkan untuk POS kasir dan GPS presensi.</span
          >
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        {#if !isOnboarding}
          <button
            type="button"
            onclick={onClose}
            class="flex-1 cursor-pointer rounded-full border border-[#e5e5ea] py-3 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
          >
            Batal
          </button>
        {/if}
        <button
          type="button"
          disabled={isSubmitting || !workspaceName.trim()}
          onclick={handleCreate}
          class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
        >
          {#if isSubmitting}
            <span>Membuat Workspace...</span>
          {:else if isOnboarding}
            <span>Mulai Gunakan Précis</span>
            <ArrowRight class="size-4" />
          {:else}
            <Plus class="size-4" />
            <span>Buat Workspace</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
