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
        await authService.updateBankAccount(
          bankName,
          bankAccountNumber.trim(),
          bankAccountHolder.trim()
        );
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

<div
  class="space-y-6 rounded-2xl border border-[#e5e5ea] bg-white p-5 font-sans shadow-2xs sm:rounded-3xl sm:p-7"
>
  <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-4">
    <div class="space-y-1">
      <h3 class="text-base font-bold text-[#17171c]">Data Profil &amp; Rekening Bank</h3>
      <p class="text-xs text-[#8e8e93]">Informasi akun dan rekening tujuan pencairan payroll</p>
    </div>
    <span
      class="rounded-full bg-[#f4f4f6] px-3 py-1 font-mono text-xs font-semibold text-[#17171c] uppercase"
    >
      {currentUser.role}
    </span>
  </div>

  {#if profileSuccessMsg}
    <div
      class="flex items-center gap-2 rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] p-3.5 text-xs font-semibold text-[#065f46]"
    >
      <Check class="h-4 w-4 shrink-0" />
      <span>{profileSuccessMsg}</span>
    </div>
  {/if}

  {#if profileErrorMsg}
    <div
      class="rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-semibold text-[#991b1b]"
    >
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
        class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
      />
    </div>

    <div class="space-y-1.5">
      <label for="prof-email" class="block font-bold text-[#17171c]">Email Akun</label>
      <input
        id="prof-email"
        type="email"
        value={profileEmail}
        disabled
        class="w-full cursor-not-allowed rounded-xl border border-[#e5e5ea] bg-[#f4f4f6] px-4 py-2.5 font-mono text-[#8e8e93]"
      />
    </div>

    <div class="space-y-4 border-t border-[#f2f2f4] pt-2">
      <div class="space-y-1">
        <h4 class="text-xs font-bold text-[#17171c]">Informasi Rekening Bank Pencairan</h4>
        <p class="text-[11px] text-[#8e8e93]">
          Digunakan untuk ekspor transfer payroll massal (BCA/Mandiri)
        </p>
      </div>

      <div class="space-y-1.5">
        <label for="prof-bank-name" class="block font-bold text-[#17171c]">Nama Bank</label>
        <div class="relative">
          <select
            id="prof-bank-name"
            bind:value={bankName}
            class="w-full cursor-pointer appearance-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 font-mono text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          >
            <option value="BCA">Bank Central Asia (BCA)</option>
            <option value="MANDIRI">Bank Mandiri</option>
            <option value="BRI">Bank Rakyat Indonesia (BRI)</option>
            <option value="BNI">Bank Negara Indonesia (BNI)</option>
            <option value="BSI">Bank Syariah Indonesia (BSI)</option>
            <option value="JAGO">Bank Jago</option>
          </select>
          <ChevronDown
            class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-[#8e8e93]"
          />
        </div>
      </div>

      <div class="space-y-1.5">
        <label for="prof-bank-acc" class="block font-bold text-[#17171c]">Nomor Rekening</label>
        <input
          id="prof-bank-acc"
          type="text"
          bind:value={bankAccountNumber}
          placeholder="Contoh: 1234567890"
          class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
        />
      </div>

      <div class="space-y-1.5">
        <label for="prof-bank-holder" class="block font-bold text-[#17171c]"
          >Nama Pemilik Rekening</label
        >
        <input
          id="prof-bank-holder"
          type="text"
          bind:value={bankAccountHolder}
          placeholder="Nama sesuai buku tabungan"
          class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
        />
      </div>
    </div>
  </div>

  <div class="flex justify-end border-t border-[#f2f2f4] pt-2">
    <button
      type="button"
      onclick={handleSaveProfile}
      disabled={isSavingProfile}
      class="flex cursor-pointer items-center gap-2 rounded-full bg-[#17171c] px-6 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
    >
      {#if isSavingProfile}
        <span>Menyimpan...</span>
      {:else}
        <Save class="h-4 w-4" />
        <span>Simpan Profil</span>
      {/if}
    </button>
  </div>
</div>
