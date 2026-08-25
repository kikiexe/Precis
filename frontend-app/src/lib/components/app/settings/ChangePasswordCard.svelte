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

<div class="bg-white border border-[#d9d9dd] rounded-[24px] p-6 space-y-5 font-sans">
  <div class="border-b border-[#f2f2f2] pb-3">
    <h2 class="text-base font-medium text-[#212121]">Keamanan &amp; Kata Sandi</h2>
    <p class="text-xs text-[#75758a]">Ubah kata sandi akun untuk akses portal aman</p>
  </div>

  {#if passwordSuccessMsg}
    <div class="p-3 bg-[#edfce9] border border-[#bbf7d0] rounded-xl text-xs font-medium text-[#003c33] flex items-center gap-2">
      <Check class="w-4 h-4 shrink-0" />
      <span>{passwordSuccessMsg}</span>
    </div>
  {/if}

  {#if passwordErrorMsg}
    <div class="p-3 bg-[#ffefef] border border-[#fecaca] rounded-xl text-xs font-medium text-[#e5484d]">
      {passwordErrorMsg}
    </div>
  {/if}

  <div class="space-y-4 text-xs">
    <div class="space-y-1.5">
      <label for="pwd-curr" class="block font-medium text-[#212121]">Kata Sandi Saat Ini</label>
      <input
        id="pwd-curr"
        type="password"
        bind:value={currentPassword}
        placeholder="••••••••"
        class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl text-[#212121] focus:border-[#17171c] focus:outline-hidden"
      />
    </div>

    <div class="space-y-1.5">
      <label for="pwd-new" class="block font-medium text-[#212121]">Kata Sandi Baru</label>
      <input
        id="pwd-new"
        type="password"
        bind:value={newPassword}
        placeholder="Minimal 6 karakter"
        class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl text-[#212121] focus:border-[#17171c] focus:outline-hidden"
      />
    </div>

    <div class="space-y-1.5">
      <label for="pwd-conf" class="block font-medium text-[#212121]">Ulangi Kata Sandi Baru</label>
      <input
        id="pwd-conf"
        type="password"
        bind:value={confirmPassword}
        placeholder="Ulangi kata sandi baru"
        class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl text-[#212121] focus:border-[#17171c] focus:outline-hidden"
      />
    </div>

    <div class="pt-4">
      <button
        type="button"
        onclick={handleSavePassword}
        disabled={isSavingPassword}
        class="w-full py-2.5 bg-white hover:bg-[#eeece7] border border-[#d9d9dd] text-[#17171c] font-medium rounded-full transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5"
      >
        <KeyRound class="w-3.5 h-3.5" />
        <span>Perbarui Kata Sandi</span>
      </button>
    </div>
  </div>
</div>
