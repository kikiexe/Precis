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

<div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-7 space-y-6 shadow-2xs font-sans">
  <div class="border-b border-[#f2f2f4] pb-4">
    <h3 class="text-base font-bold text-[#17171c]">Keamanan &amp; Kata Sandi</h3>
    <p class="text-xs text-[#8e8e93]">Ubah kata sandi akun untuk akses portal aman</p>
  </div>

  {#if passwordSuccessMsg}
    <div class="p-3.5 bg-[#ecfdf5] border border-[#a7f3d0] rounded-2xl text-xs font-semibold text-[#065f46] flex items-center gap-2">
      <Check class="w-4 h-4 shrink-0" />
      <span>{passwordSuccessMsg}</span>
    </div>
  {/if}

  {#if passwordErrorMsg}
    <div class="p-3.5 bg-[#fef2f2] border border-[#fecaca] rounded-2xl text-xs font-semibold text-[#991b1b]">
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
        class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
      />
    </div>

    <div class="space-y-1.5">
      <label for="pwd-new" class="block font-bold text-[#17171c]">Kata Sandi Baru</label>
      <input
        id="pwd-new"
        type="password"
        bind:value={newPassword}
        placeholder="Minimal 6 karakter"
        class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
      />
    </div>

    <div class="space-y-1.5">
      <label for="pwd-conf" class="block font-bold text-[#17171c]">Ulangi Kata Sandi Baru</label>
      <input
        id="pwd-conf"
        type="password"
        bind:value={confirmPassword}
        placeholder="Ulangi kata sandi baru"
        class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
      />
    </div>

    <div class="pt-4 flex justify-end">
      <button
        type="button"
        onclick={handleSavePassword}
        disabled={isSavingPassword}
        class="px-6 py-2.5 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all disabled:opacity-50 flex items-center gap-2 shadow-xs"
      >
        <KeyRound class="w-4 h-4" />
        <span>{isSavingPassword ? 'Memperbarui...' : 'Perbarui Kata Sandi'}</span>
      </button>
    </div>
  </div>
</div>
