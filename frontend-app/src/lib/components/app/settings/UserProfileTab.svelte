<script lang="ts">
  import { Check, Save, ChevronDown } from 'lucide-svelte';
  import type { User } from '../../../types/app';
  import { authService } from '../../../services/auth-service';

  interface Props {
    currentUser: User;
  }

  let { currentUser }: Props = $props();

  let profileName = $state('');
  let profileEmail = $state('');
  let bankName = $state('BCA');
  let bankAccountNumber = $state('');
  let bankAccountHolder = $state('');
  let isSavingProfile = $state(false);
  let profileSuccessMsg = $state<string | null>(null);
  let profileErrorMsg = $state<string | null>(null);

  $effect(() => {
    profileName = currentUser.name || '';
    profileEmail = currentUser.email || '';
    bankName = currentUser.bank_name || 'BCA';
    bankAccountNumber = currentUser.bank_account_number || '';
    bankAccountHolder = currentUser.bank_account_holder || currentUser.name || '';
  });

  async function handleSaveProfile() {
    if (!profileName.trim()) {
      profileErrorMsg = 'Nama lengkap tidak boleh kosong.';
      return;
    }
    isSavingProfile = true;
    profileSuccessMsg = null;
    profileErrorMsg = null;
    try {
      await authService.updateProfile(profileName.trim());
      currentUser.name = profileName.trim();

      if (bankAccountNumber.trim()) {
        await authService.updateBankAccount(bankName, bankAccountNumber.trim(), bankAccountHolder.trim());
        currentUser.bank_name = bankName;
        currentUser.bank_account_number = bankAccountNumber.trim();
        currentUser.bank_account_holder = bankAccountHolder.trim();
      }

      profileSuccessMsg = 'Profil dan rekening berhasil disimpan.';
      setTimeout(() => (profileSuccessMsg = null), 3000);
    } catch (e: unknown) {
      profileErrorMsg = e instanceof Error ? e.message : 'Gagal memperbarui profil.';
    } finally {
      isSavingProfile = false;
    }
  }
</script>

<div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-7 space-y-6 shadow-2xs font-sans">
  <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-4">
    <div class="space-y-1">
      <h3 class="text-base font-bold text-[#17171c]">Data Profil &amp; Rekening Bank</h3>
      <p class="text-xs text-[#8e8e93]">Informasi akun dan rekening tujuan pencairan payroll</p>
    </div>
    <span class="px-3 py-1 rounded-full bg-[#f4f4f6] text-[#17171c] text-xs font-mono font-semibold uppercase">
      {currentUser.role}
    </span>
  </div>

  {#if profileSuccessMsg}
    <div class="p-3.5 bg-[#ecfdf5] border border-[#a7f3d0] rounded-2xl text-xs font-semibold text-[#065f46] flex items-center gap-2">
      <Check class="w-4 h-4 shrink-0" />
      <span>{profileSuccessMsg}</span>
    </div>
  {/if}

  {#if profileErrorMsg}
    <div class="p-3.5 bg-[#fef2f2] border border-[#fecaca] rounded-2xl text-xs font-semibold text-[#991b1b]">
      {profileErrorMsg}
    </div>
  {/if}

  <div class="space-y-4 text-xs">
    <div class="space-y-1.5">
      <label for="prof-name" class="block font-bold text-[#17171c]">Nama Lengkap</label>
      <input
        id="prof-name"
        type="text"
        bind:value={profileName}
        class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
      />
    </div>

    <div class="space-y-1.5">
      <label for="prof-email" class="block font-bold text-[#17171c]">Email Akun</label>
      <input
        id="prof-email"
        type="email"
        value={profileEmail}
        disabled
        class="w-full px-4 py-2.5 bg-[#f4f4f6] border border-[#e5e5ea] rounded-xl text-[#8e8e93] font-mono cursor-not-allowed"
      />
    </div>

    <div class="pt-2 border-t border-[#f2f2f4] space-y-4">
      <div class="space-y-1">
        <h4 class="font-bold text-xs text-[#17171c]">Informasi Rekening Bank Pencairan</h4>
        <p class="text-[11px] text-[#8e8e93]">Digunakan untuk ekspor transfer payroll massal (BCA/Mandiri)</p>
      </div>

      <div class="space-y-1.5">
        <label for="prof-bank-name" class="block font-bold text-[#17171c]">Nama Bank</label>
        <div class="relative">
          <select
            id="prof-bank-name"
            bind:value={bankName}
            class="appearance-none w-full px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
          >
            <option value="BCA">Bank Central Asia (BCA)</option>
            <option value="MANDIRI">Bank Mandiri</option>
            <option value="BRI">Bank Rakyat Indonesia (BRI)</option>
            <option value="BNI">Bank Negara Indonesia (BNI)</option>
            <option value="BSI">Bank Syariah Indonesia (BSI)</option>
            <option value="JAGO">Bank Jago</option>
          </select>
          <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>
      </div>

      <div class="space-y-1.5">
        <label for="prof-bank-acc" class="block font-bold text-[#17171c]">Nomor Rekening</label>
        <input
          id="prof-bank-acc"
          type="text"
          bind:value={bankAccountNumber}
          placeholder="Contoh: 1234567890"
          class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
        />
      </div>

      <div class="space-y-1.5">
        <label for="prof-bank-holder" class="block font-bold text-[#17171c]">Nama Pemilik Rekening</label>
        <input
          id="prof-bank-holder"
          type="text"
          bind:value={bankAccountHolder}
          placeholder="Nama sesuai buku tabungan"
          class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
        />
      </div>
    </div>
  </div>

  <div class="pt-2 border-t border-[#f2f2f4] flex justify-end">
    <button
      type="button"
      onclick={handleSaveProfile}
      disabled={isSavingProfile}
      class="px-6 py-2.5 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all disabled:opacity-50 flex items-center gap-2 shadow-xs"
    >
      {#if isSavingProfile}
        <span>Menyimpan...</span>
      {:else}
        <Save class="w-4 h-4" />
        <span>Simpan Profil</span>
      {/if}
    </button>
  </div>
</div>
