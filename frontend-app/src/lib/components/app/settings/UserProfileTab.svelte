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

<div class="bg-white border border-[#d9d9dd] rounded-[24px] p-6 space-y-5 font-sans">
  <div class="flex items-center justify-between border-b border-[#f2f2f2] pb-3">
    <div>
      <h2 class="text-base font-medium text-[#212121]">Data Profil &amp; Rekening Bank</h2>
      <p class="text-xs text-[#75758a]">Informasi akun dan rekening tujuan pencairan payroll</p>
    </div>
    <span class="px-2.5 py-0.5 rounded-full bg-[#eeece7] text-[#17171c] text-[10px] font-mono font-medium uppercase">
      {currentUser.role}
    </span>
  </div>

  {#if profileSuccessMsg}
    <div class="p-3 bg-[#edfce9] border border-[#bbf7d0] rounded-xl text-xs font-medium text-[#003c33] flex items-center gap-2">
      <Check class="w-4 h-4 shrink-0" />
      <span>{profileSuccessMsg}</span>
    </div>
  {/if}

  {#if profileErrorMsg}
    <div class="p-3 bg-[#ffefef] border border-[#fecaca] rounded-xl text-xs font-medium text-[#e5484d]">
      {profileErrorMsg}
    </div>
  {/if}

  <div class="space-y-4 text-xs">
    <div class="space-y-1.5">
      <label for="prof-name" class="block font-medium text-[#212121]">Nama Lengkap</label>
      <input
        id="prof-name"
        type="text"
        bind:value={profileName}
        class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl text-[#212121] focus:border-[#17171c] focus:outline-hidden"
      />
    </div>

    <div class="space-y-1.5">
      <label for="prof-email" class="block font-medium text-[#212121]">Email Akun</label>
      <input
        id="prof-email"
        type="email"
        value={profileEmail}
        disabled
        class="w-full px-3.5 py-2.5 bg-[#eeece7]/60 border border-[#d9d9dd] rounded-xl text-[#75758a] font-mono cursor-not-allowed"
      />
    </div>

    <div class="pt-2 border-t border-[#f2f2f2] space-y-3">
      <div class="font-medium text-[#212121]">Rekening Bank Pencairan</div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="space-y-1.5">
          <label for="prof-bank" class="block text-[11px] text-[#75758a]">Nama Bank</label>
          <div class="relative">
            <select
              id="prof-bank"
              bind:value={bankName}
              class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              <option value="BCA">Bank BCA</option>
              <option value="MANDIRI">Bank Mandiri</option>
              <option value="BRI">Bank BRI</option>
              <option value="BNI">Bank BNI</option>
              <option value="BSI">Bank Syariah Indonesia</option>
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>

        <div class="space-y-1.5">
          <label for="prof-acc-num" class="block text-[11px] text-[#75758a]">Nomor Rekening</label>
          <input
            id="prof-acc-num"
            type="text"
            bind:value={bankAccountNumber}
            placeholder="8830192831"
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
      </div>

      <div class="space-y-1.5">
        <label for="prof-acc-holder" class="block text-[11px] text-[#75758a]">Nama Pemilik Rekening</label>
        <input
          id="prof-acc-holder"
          type="text"
          bind:value={bankAccountHolder}
          placeholder="Sesuai buku tabungan"
          class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl text-[#212121] focus:border-[#17171c] focus:outline-hidden"
        />
      </div>
    </div>

    <div class="pt-3 flex gap-2">
      <button
        type="button"
        onclick={handleSaveProfile}
        disabled={isSavingProfile}
        class="flex-1 py-2.5 bg-[#17171c] hover:bg-black text-white font-medium rounded-full transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5"
      >
        <Save class="w-3.5 h-3.5" />
        <span>Simpan Profil &amp; Rekening</span>
      </button>
    </div>
  </div>
</div>
