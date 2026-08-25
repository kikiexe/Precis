<script lang="ts">
  import {
    Plus,
    Printer,
    CreditCard,
    User as UserIcon,
    KeyRound,
  } from 'lucide-svelte';
  import type { CashAdvance, PayrollSlipData, User } from '../../../types/app';
  import { authService } from '../../../services/auth-service';
  import { formatRupiah } from '../../../utils/formatters';

  interface Props {
    currentUser: User;
    cashAdvances: CashAdvance[];
    payrollSlip: PayrollSlipData | null;
    onOpenKasbonModal: () => void;
  }

  let {
    currentUser,
    cashAdvances = [],
    payrollSlip = null,
    onOpenKasbonModal,
  }: Props = $props();

  let totalActiveKasbon = $derived(
    cashAdvances.filter((k) => k.status === 'APPROVED').reduce((sum, k) => sum + k.amount, 0)
  );

  // Bank Account State
  let bankName = $state('BCA');
  let bankAccountNumber = $state('');
  let bankAccountHolder = $state('');
  let isSavingBank = $state(false);
  let bankSuccessMsg = $state<string | null>(null);
  let bankErrorMsg = $state<string | null>(null);

  // Profile & Password State
  let profileName = $state('');
  let isSavingProfile = $state(false);
  let profileSuccessMsg = $state<string | null>(null);
  let profileErrorMsg = $state<string | null>(null);

  let currentPassword = $state('');
  let newPassword = $state('');
  let confirmPassword = $state('');
  let isSavingPassword = $state(false);
  let passwordSuccessMsg = $state<string | null>(null);
  let passwordErrorMsg = $state<string | null>(null);

  $effect(() => {
    if (currentUser) {
      bankName = currentUser.bank_name || 'BCA';
      bankAccountNumber = currentUser.bank_account_number || '';
      bankAccountHolder = currentUser.bank_account_holder || currentUser.name || '';
      profileName = currentUser.name || '';
    }
  });

  async function handleSaveBankAccount() {
    if (!bankAccountNumber.trim() || !bankAccountHolder.trim()) {
      bankErrorMsg = 'Nomor rekening dan nama pemilik rekening wajib diisi.';
      return;
    }
    isSavingBank = true;
    bankSuccessMsg = null;
    bankErrorMsg = null;
    try {
      await authService.updateBankAccount(bankName, bankAccountNumber.trim(), bankAccountHolder.trim());
      currentUser.bank_name = bankName;
      currentUser.bank_account_number = bankAccountNumber.trim();
      currentUser.bank_account_holder = bankAccountHolder.trim();
      bankSuccessMsg = 'Data rekening bank berhasil disimpan.';
    } catch (e: unknown) {
      bankErrorMsg = e instanceof Error ? e.message : 'Gagal menyimpan rekening bank.';
    } finally {
      isSavingBank = false;
    }
  }

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
      profileSuccessMsg = 'Nama profil berhasil diperbarui.';
    } catch (e: unknown) {
      profileErrorMsg = e instanceof Error ? e.message : 'Gagal memperbarui profil.';
    } finally {
      isSavingProfile = false;
    }
  }

  async function handleSavePassword() {
    if (!currentPassword) {
      passwordErrorMsg = 'Masukkan kata sandi saat ini.';
      return;
    }
    if (newPassword.length < 8) {
      passwordErrorMsg = 'Kata sandi baru minimal 8 karakter.';
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
    } catch (e: unknown) {
      passwordErrorMsg = e instanceof Error ? e.message : 'Gagal memperbarui kata sandi.';
    } finally {
      isSavingPassword = false;
    }
  }
</script>

<div class="space-y-3 sm:space-y-4 max-w-4xl mx-auto font-sans pb-6">
  <div class="flex items-center justify-between gap-3 pb-1">
    <div>
      <h2 class="text-sm sm:text-base font-medium text-[#212121]">Kasbon &amp; Slip Gaji Digital</h2>
      <p class="text-[11px] text-[#75758a]">Pengajuan pinjaman darurat &amp; rincian gaji berjalan</p>
    </div>
    <button
      type="button"
      onclick={onOpenKasbonModal}
      class="bg-[#17171c] hover:bg-black text-white px-3 py-1.5 text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer transition-all shrink-0"
    >
      <Plus class="w-3.5 h-3.5" />
      <span>Ajukan Kasbon</span>
    </button>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 sm:gap-4">
    <!-- Status Kasbon Aktif & Riwayat -->
    <div class="lg:col-span-6 space-y-3">
      <!-- Total Kasbon Card -->
      <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 flex items-center justify-between">
        <div>
          <div class="text-[11px] text-[#75758a]">Total Kasbon Belum Lunas</div>
          <div class={`text-xl font-medium font-mono mt-0.5 ${totalActiveKasbon > 0 ? 'text-[#e5484d]' : 'text-[#00875a]'}`}>
            {formatRupiah(totalActiveKasbon)}
          </div>
          <div class="text-[10px] text-[#93939f] mt-0.5">
            {totalActiveKasbon > 0 ? 'Dipotong otomatis saat payroll' : 'Tidak ada pinjaman kasbon aktif'}
          </div>
        </div>
      </div>

      <!-- Riwayat Kasbon -->
      <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 space-y-2.5">
        <div class="flex items-center justify-between border-b border-[#f2f2f2] pb-2">
          <h3 class="text-xs font-medium text-[#212121]">Riwayat Permohonan Kasbon</h3>
          <span class="text-[10px] font-mono text-[#75758a]">{cashAdvances.length} Pengajuan</span>
        </div>

        {#if cashAdvances.length === 0}
          <div class="py-6 text-center text-xs text-[#75758a]">Belum ada riwayat kasbon.</div>
        {:else}
          <div class="divide-y divide-[#f2f2f2]">
            {#each cashAdvances as k}
              <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                <div>
                  <div class="font-mono font-medium text-[#17171c]">{formatRupiah(k.amount)}</div>
                  <div class="text-[10px] text-[#75758a] mt-0.5">{k.purpose || 'Pinjaman'} • {k.created_at || k.request_date || '-'}</div>
                </div>
                <span class={`text-[9px] font-mono font-medium px-2 py-0.5 rounded-full ${
                  k.status === 'APPROVED' ? 'bg-[#edfce9] text-[#00875a]' : k.status === 'PENDING' ? 'bg-[#eeece7] text-[#616161]' : 'bg-[#ffefef] text-[#e5484d]'
                }`}>
                  {k.status}
                </span>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    </div>

    <!-- Slip Gaji Digital Preview -->
    <div class="lg:col-span-6 space-y-3">
      <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 space-y-3">
        <div class="flex items-center justify-between border-b border-[#f2f2f2] pb-2.5">
          <div>
            <h3 class="text-xs sm:text-sm font-medium text-[#212121]">Slip Gaji Periode Berjalan</h3>
            <div class="text-[10px] font-mono text-[#75758a]">{payrollSlip?.period_start || ''} s/d {payrollSlip?.period_end || ''}</div>
          </div>
          <button
            type="button"
            onclick={() => window.print()}
            class="p-1.5 border border-[#d9d9dd] rounded-xl hover:bg-[#eeece7] text-[#616161] hover:text-[#212121] cursor-pointer"
            title="Cetak Slip Gaji"
          >
            <Printer class="w-4 h-4" />
          </button>
        </div>

        {#if payrollSlip}
          <div class="space-y-2 text-xs">
            <div class="flex justify-between py-1 border-b border-[#f2f2f2]">
              <span class="text-[#616161]">Gaji Pokok</span>
              <span class="font-mono text-[#212121]">{formatRupiah(payrollSlip.base_salary)}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-[#f2f2f2]">
              <span class="text-[#616161]">Upah Lembur ({Math.round((payrollSlip.total_overtime_minutes || 0) / 60)} Jam)</span>
              <span class="font-mono text-[#00875a]">+{formatRupiah(payrollSlip.overtime_pay)}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-[#f2f2f2]">
              <span class="text-[#616161]">Denda Terlambat ({payrollSlip.total_late_minutes || 0} Mnt)</span>
              <span class="font-mono text-[#e5484d]">-{formatRupiah(payrollSlip.late_penalty)}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-[#f2f2f2]">
              <span class="text-[#616161]">Potongan Kasbon</span>
              <span class="font-mono text-[#e5484d]">-{formatRupiah(payrollSlip.cash_advance_deduction)}</span>
            </div>
            <div class="flex justify-between py-2 text-xs sm:text-sm font-medium bg-[#fbfbfb] border border-[#d9d9dd]/60 px-3 rounded-xl">
              <span class="text-[#17171c]">Take Home Pay (Gaji Bersih)</span>
              <span class="font-mono text-[#00875a]">{formatRupiah(payrollSlip.net_salary)}</span>
            </div>
          </div>
        {:else}
          <div class="py-6 text-center text-xs text-[#75758a]">Data slip gaji belum tersedia.</div>
        {/if}
      </div>
    </div>
  </div>

  <!-- Rekening Bank Pencairan Payroll Staf -->
  <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 sm:p-6 space-y-4">
    <div class="flex items-center justify-between border-b border-[#f2f2f2] pb-3">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-full bg-[#17171c] text-white flex items-center justify-center">
          <CreditCard class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-xs sm:text-sm font-medium text-[#212121]">Rekening Bank Pencairan Gaji</h3>
          <p class="text-[11px] text-[#75758a]">Data rekening bank pribadi untuk transfer otomatis payroll dari Owner</p>
        </div>
      </div>
      {#if currentUser.bank_account_number}
        <span class="text-[10px] font-mono text-[#00875a] bg-[#edfce9] px-2.5 py-1 rounded-full font-medium">
          Tersimpan
        </span>
      {:else}
        <span class="text-[10px] font-mono text-[#e5484d] bg-[#ffefef] px-2.5 py-1 rounded-full font-medium">
          Belum Diisi
        </span>
      {/if}
    </div>

    {#if bankSuccessMsg}
      <div class="p-3 bg-[#edfce9] border border-[#00875a]/20 text-[#003c33] text-xs rounded-xl">{bankSuccessMsg}</div>
    {/if}
    {#if bankErrorMsg}
      <div class="p-3 bg-[#ffefef] border border-[#e5484d]/20 text-[#e5484d] text-xs rounded-xl">{bankErrorMsg}</div>
    {/if}

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="space-y-1">
        <label for="staff-bank-name" class="text-[11px] font-medium text-[#212121]">Nama Bank</label>
        <select
          id="staff-bank-name"
          bind:value={bankName}
          class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#17171c] focus:outline-hidden focus:border-[#17171c]"
        >
          <option value="BCA">BCA (Bank Central Asia)</option>
          <option value="Mandiri">Bank Mandiri</option>
          <option value="BRI">BRI (Bank Rakyat Indonesia)</option>
          <option value="BNI">BNI (Bank Negara Indonesia)</option>
          <option value="BSI">BSI (Bank Syariah Indonesia)</option>
          <option value="CIMB">CIMB Niaga</option>
          <option value="Permata">Bank Permata</option>
          <option value="SeaBank">SeaBank</option>
          <option value="Jago">Bank Jago</option>
          <option value="BCA Digital (Blu)">BCA Digital (Blu)</option>
        </select>
      </div>

      <div class="space-y-1">
        <label for="staff-bank-number" class="text-[11px] font-medium text-[#212121]">Nomor Rekening</label>
        <input
          id="staff-bank-number"
          type="text"
          bind:value={bankAccountNumber}
          placeholder="Contoh: 1234567890"
          class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs font-mono text-[#17171c] focus:outline-hidden focus:border-[#17171c]"
        />
      </div>

      <div class="space-y-1">
        <label for="staff-bank-holder" class="text-[11px] font-medium text-[#212121]">Atas Nama Rekening</label>
        <input
          id="staff-bank-holder"
          type="text"
          bind:value={bankAccountHolder}
          placeholder="Nama Lengkap Pemilik Rekening"
          class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#17171c] focus:outline-hidden focus:border-[#17171c]"
        />
      </div>
    </div>

    <div class="flex justify-end pt-1">
      <button
        type="button"
        onclick={handleSaveBankAccount}
        disabled={isSavingBank}
        class="px-4 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50"
      >
        {isSavingBank ? 'Menyimpan...' : 'Simpan Rekening Bank'}
      </button>
    </div>
  </div>

  <!-- Profil Personal & Ganti Password -->
  <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 sm:p-6 space-y-4">
    <div class="flex items-center justify-between border-b border-[#f2f2f2] pb-3">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-full bg-[#17171c] text-white flex items-center justify-center">
          <UserIcon class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-xs sm:text-sm font-medium text-[#212121]">Profil &amp; Keamanan Akun</h3>
          <p class="text-[11px] text-[#75758a]">Kelola nama profil personal dan pembaruan kata sandi login</p>
        </div>
      </div>
      <span class="text-[10px] font-mono text-[#75758a] bg-[#eeece7] px-2.5 py-1 rounded-full font-medium">
        {currentUser.email}
      </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Form Edit Nama Profil -->
      <div class="space-y-3">
        <div class="text-xs font-medium text-[#212121] flex items-center gap-1.5">
          <UserIcon class="w-3.5 h-3.5 text-[#75758a]" />
          <span>Data Personal</span>
        </div>

        {#if profileSuccessMsg}
          <div class="p-2.5 bg-[#edfce9] border border-[#00875a]/20 text-[#003c33] text-xs rounded-xl">{profileSuccessMsg}</div>
        {/if}
        {#if profileErrorMsg}
          <div class="p-2.5 bg-[#ffefef] border border-[#e5484d]/20 text-[#e5484d] text-xs rounded-xl">{profileErrorMsg}</div>
        {/if}

        <div class="space-y-1">
          <label for="profile-email-ro" class="text-[11px] font-medium text-[#212121]">Alamat Email (Akun)</label>
          <input
            id="profile-email-ro"
            type="email"
            value={currentUser.email}
            disabled
            class="w-full bg-[#eeece7]/50 border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs font-mono text-[#75758a] cursor-not-allowed"
          />
        </div>

        <div class="space-y-1">
          <label for="profile-name-input" class="text-[11px] font-medium text-[#212121]">Nama Lengkap</label>
          <input
            id="profile-name-input"
            type="text"
            bind:value={profileName}
            placeholder="Nama Lengkap Anda"
            class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#17171c] focus:outline-hidden focus:border-[#17171c]"
          />
        </div>

        <button
          type="button"
          onclick={handleSaveProfile}
          disabled={isSavingProfile}
          class="px-4 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50"
        >
          {isSavingProfile ? 'Menyimpan...' : 'Simpan Nama Profil'}
        </button>
      </div>

      <!-- Form Ganti Password -->
      <div class="space-y-3">
        <div class="text-xs font-medium text-[#212121] flex items-center gap-1.5">
          <KeyRound class="w-3.5 h-3.5 text-[#75758a]" />
          <span>Ganti Kata Sandi</span>
        </div>

        {#if passwordSuccessMsg}
          <div class="p-2.5 bg-[#edfce9] border border-[#00875a]/20 text-[#003c33] text-xs rounded-xl">{passwordSuccessMsg}</div>
        {/if}
        {#if passwordErrorMsg}
          <div class="p-2.5 bg-[#ffefef] border border-[#e5484d]/20 text-[#e5484d] text-xs rounded-xl">{passwordErrorMsg}</div>
        {/if}

        <div class="space-y-1">
          <label for="pwd-current" class="text-[11px] font-medium text-[#212121]">Kata Sandi Saat Ini</label>
          <input
            id="pwd-current"
            type="password"
            bind:value={currentPassword}
            placeholder="••••••••"
            class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#17171c] focus:outline-hidden focus:border-[#17171c]"
          />
        </div>

        <div class="space-y-1">
          <label for="pwd-new" class="text-[11px] font-medium text-[#212121]">Kata Sandi Baru</label>
          <input
            id="pwd-new"
            type="password"
            bind:value={newPassword}
            placeholder="Minimal 8 karakter"
            class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#17171c] focus:outline-hidden focus:border-[#17171c]"
          />
        </div>

        <div class="space-y-1">
          <label for="pwd-confirm" class="text-[11px] font-medium text-[#212121]">Konfirmasi Kata Sandi Baru</label>
          <input
            id="pwd-confirm"
            type="password"
            bind:value={confirmPassword}
            placeholder="Ulangi kata sandi baru"
            class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#17171c] focus:outline-hidden focus:border-[#17171c]"
          />
        </div>

        <button
          type="button"
          onclick={handleSavePassword}
          disabled={isSavingPassword}
          class="px-4 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50"
        >
          {isSavingPassword ? 'Memperbarui...' : 'Perbarui Kata Sandi'}
        </button>
      </div>
    </div>
  </div>
</div>
