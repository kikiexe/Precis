<script lang="ts">
  import { Check, KeyRound } from 'lucide-svelte';
  import { authService } from '../../../services/auth-service';

  let currentPassword = $state('');
  let newPassword = $state('');
  let confirmPassword = $state('');
  let isSavingPassword = $state(false);
  let passwordSuccessMsg = $state<string | null>(null);
  let passwordErrorMsg = $state<string | null>(null);

  async function handleSavePassword() {
    if (!currentPassword) {
      passwordErrorMsg = 'Kata sandi saat ini wajib diisi.';
      return;
    }
    if (!newPassword || newPassword.length < 6) {
      passwordErrorMsg = 'Kata sandi baru minimal 6 karakter.';
      return;
    }
    if (newPassword !== confirmPassword) {
      passwordErrorMsg = 'Konfirmasi kata sandi baru tidak cocok.';
      return;
    }
    isSavingPassword = true;
    passwordSuccessMsg = null;
    passwordErrorMsg = null;
    try {
      await authService.updatePassword(currentPassword, newPassword, confirmPassword);
      passwordSuccessMsg = 'Kata sandi berhasil diperbarui.';
      currentPassword = '';
      newPassword = '';
      confirmPassword = '';
      setTimeout(() => (passwordSuccessMsg = null), 3000);
    } catch (e: unknown) {
      passwordErrorMsg = e instanceof Error ? e.message : 'Gagal memperbarui kata sandi.';
    } finally {
      isSavingPassword = false;
    }
  }
</script>

<div
  class="space-y-6 rounded-2xl border border-[#e5e5ea] bg-white p-5 font-sans shadow-2xs sm:rounded-3xl sm:p-7"
>
  <div class="border-b border-[#f2f2f4] pb-4">
    <h3 class="text-base font-bold text-[#17171c]">Keamanan &amp; Kata Sandi</h3>
    <p class="text-xs text-[#8e8e93]">Ubah kata sandi akun untuk akses portal aman</p>
  </div>

  {#if passwordSuccessMsg}
    <div
      class="flex items-center gap-2 rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] p-3.5 text-xs font-semibold text-[#065f46]"
    >
      <Check class="h-4 w-4 shrink-0" />
      <span>{passwordSuccessMsg}</span>
    </div>
  {/if}

  {#if passwordErrorMsg}
    <div
      class="rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-semibold text-[#991b1b]"
    >
      {passwordErrorMsg}
    </div>
  {/if}

  <div class="space-y-4 text-xs">
    <div class="space-y-1.5">
      <label for="pwd-curr" class="block font-bold text-[#17171c]">Kata Sandi Saat Ini</label>
      <input
        id="pwd-curr"
        type="password"
        bind:value={currentPassword}
        placeholder="••••••••"
        class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
      />
    </div>

    <div class="space-y-1.5">
      <label for="pwd-new" class="block font-bold text-[#17171c]">Kata Sandi Baru</label>
      <input
        id="pwd-new"
        type="password"
        bind:value={newPassword}
        placeholder="Minimal 6 karakter"
        class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
      />
    </div>

    <div class="space-y-1.5">
      <label for="pwd-conf" class="block font-bold text-[#17171c]">Ulangi Kata Sandi Baru</label>
      <input
        id="pwd-conf"
        type="password"
        bind:value={confirmPassword}
        placeholder="Ulangi kata sandi baru"
        class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
      />
    </div>

    <div class="flex justify-end pt-4">
      <button
        type="button"
        onclick={handleSavePassword}
        disabled={isSavingPassword}
        class="flex cursor-pointer items-center gap-2 rounded-full bg-[#17171c] px-6 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
      >
        <KeyRound class="h-4 w-4" />
        <span>{isSavingPassword ? 'Memperbarui...' : 'Perbarui Kata Sandi'}</span>
      </button>
    </div>
  </div>
</div>
