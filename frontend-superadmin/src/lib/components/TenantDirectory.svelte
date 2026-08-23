<script lang="ts">
  import {
    Building2,
    Search,
    ShieldAlert,
    ShieldCheck,
    Calendar,
    AlertTriangle,
    RefreshCw,
    PlusCircle,
    Store,
    X,
    CheckCircle,
  } from 'lucide-svelte';
  import type { TenantRecord } from '../types/superadmin';

  interface Props {
    tenants: TenantRecord[];
    isLoading: boolean;
    onUpdateStatus: (tenantId: string, status: 'ACTIVE' | 'SUSPENDED' | 'GRACE_PERIOD' | 'TRIAL') => Promise<void>;
    onExtendSubscription: (tenantId: string, days: number) => Promise<void>;
    onRefresh: () => void;
  }

  let { tenants, isLoading, onUpdateStatus, onExtendSubscription, onRefresh }: Props = $props();

  let selectedStatus = $state<'ALL' | 'ACTIVE' | 'GRACE_PERIOD' | 'SUSPENDED' | 'TRIAL'>('ALL');
  let searchQuery = $state('');

  let extendingTenant = $state<TenantRecord | null>(null);
  let extensionDays = $state(30);
  let isSubmittingAction = $state(false);
  let actionMessage = $state<string | null>(null);
  let actionError = $state<string | null>(null);

  let filteredTenants = $derived(
    tenants.filter((t) => {
      const matchStatus = selectedStatus === 'ALL' || t.subscription_status === selectedStatus;
      const q = searchQuery.toLowerCase();
      const matchQuery =
        !searchQuery ||
        t.name.toLowerCase().includes(q) ||
        t.email.toLowerCase().includes(q) ||
        t.workspaces.some((w) => w.name.toLowerCase().includes(q) || w.slug.toLowerCase().includes(q));

      return matchStatus && matchQuery;
    })
  );

  async function handleToggleSuspend(tenant: TenantRecord) {
    const newStatus = tenant.subscription_status === 'SUSPENDED' ? 'ACTIVE' : 'SUSPENDED';
    const actionLabel = newStatus === 'SUSPENDED' ? 'menonaktifkan (suspend)' : 'mengaktifkan kembali';

    if (!confirm(`Apakah Anda yakin ingin ${actionLabel} tenant "${tenant.name}"?`)) {
      return;
    }

    isSubmittingAction = true;
    actionMessage = null;
    actionError = null;

    try {
      await onUpdateStatus(tenant.id, newStatus);
      actionMessage = `Status tenant "${tenant.name}" berhasil diubah menjadi ${newStatus}.`;
    } catch (err: unknown) {
      if (err instanceof Error) {
        actionError = err.message;
      } else {
        actionError = 'Gagal memperbarui status tenant.';
      }
    } finally {
      isSubmittingAction = false;
    }
  }

  function openExtendModal(tenant: TenantRecord) {
    extendingTenant = tenant;
    extensionDays = 30;
    actionMessage = null;
    actionError = null;
  }

  function closeExtendModal() {
    extendingTenant = null;
  }

  async function handleExtendSubmit() {
    if (!extendingTenant) return;

    isSubmittingAction = true;
    actionMessage = null;
    actionError = null;

    try {
      await onExtendSubscription(extendingTenant.id, extensionDays);
      actionMessage = `Masa aktif tenant "${extendingTenant.name}" berhasil diperpanjang ${extensionDays} hari.`;
      setTimeout(() => {
        closeExtendModal();
      }, 1200);
    } catch (err: unknown) {
      if (err instanceof Error) {
        actionError = err.message;
      } else {
        actionError = 'Gagal memperpanjang masa aktif tenant.';
      }
    } finally {
      isSubmittingAction = false;
    }
  }
</script>

<div class="space-y-6 font-sans">
  <!-- Top Bar Title -->
  <div class="bg-white p-6 rounded-[22px] border border-[#d9d9dd] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-none">
    <div>
      <h1 class="text-xl font-medium text-[#212121] tracking-tight">Direktori Tenant &amp; Manajemen Workspace</h1>
      <p class="text-xs text-[#616161] mt-0.5 font-normal">
        Kelola seluruh pemilik akun bisnis SaaS, pantau status langganan, dan atur kuota workspace/cabang.
      </p>
    </div>

    <button
      type="button"
      onclick={onRefresh}
      disabled={isLoading}
      class="inline-flex items-center space-x-1.5 px-4 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7] text-[#212121] text-xs font-medium border border-[#d9d9dd] rounded-full transition-all cursor-pointer disabled:opacity-50 self-start sm:self-auto"
    >
      <RefreshCw class={`w-3.5 h-3.5 ${isLoading ? 'animate-spin' : ''}`} />
      <span>Segarkan Data</span>
    </button>
  </div>

  {#if actionMessage}
    <div class="p-3.5 bg-[#edfce9] border border-[#edfce9] text-[#003c33] text-xs font-medium flex items-center space-x-2 rounded-[12px]">
      <CheckCircle class="w-4 h-4 shrink-0" />
      <span>{actionMessage}</span>
    </div>
  {/if}

  {#if actionError}
    <div class="p-3.5 bg-[#ffad9b]/15 border border-[#ffad9b] text-[#b30000] text-xs font-medium flex items-center space-x-2 rounded-[12px]">
      <AlertTriangle class="w-4 h-4 shrink-0" />
      <span>{actionError}</span>
    </div>
  {/if}

  <!-- Filter & Search Bar -->
  <div class="bg-white p-4 sm:p-5 rounded-[22px] border border-[#d9d9dd] space-y-4 shadow-none">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
      <!-- Status Tabs -->
      <div class="flex flex-wrap gap-1 bg-[#eeece7]/60 p-1 rounded-full border border-[#d9d9dd]">
        <button
          type="button"
          onclick={() => (selectedStatus = 'ALL')}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer ${
            selectedStatus === 'ALL' ? 'bg-[#17171c] text-white shadow-none' : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          Semua ({tenants.length})
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'ACTIVE')}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer ${
            selectedStatus === 'ACTIVE' ? 'bg-[#17171c] text-white shadow-none' : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          Aktif
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'GRACE_PERIOD')}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer ${
            selectedStatus === 'GRACE_PERIOD' ? 'bg-[#17171c] text-white shadow-none' : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          Grace Period
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'SUSPENDED')}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer ${
            selectedStatus === 'SUSPENDED' ? 'bg-[#17171c] text-white shadow-none' : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          Suspended
        </button>
      </div>

      <!-- Search Box -->
      <div class="relative w-full md:w-72">
        <Search class="w-3.5 h-3.5 text-[#93939f] absolute left-3.5 top-1/2 -translate-y-1/2" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari tenant / email / kedai..."
          class="w-full pl-10 pr-3.5 py-2 text-xs bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full focus:outline-hidden focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 text-[#212121] transition-all"
        />
      </div>
    </div>
  </div>

  <!-- Tenants Table -->
  <div class="bg-white border border-[#d9d9dd] rounded-[22px] overflow-hidden shadow-none">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs border-collapse">
        <thead class="bg-[#eeece7]/50 border-b border-[#d9d9dd] text-[#616161] font-mono text-[11px]">
          <tr>
            <th class="py-3.5 px-4 font-medium">Pemilik Akun (Owner)</th>
            <th class="py-3.5 px-4 font-medium">Workspace &amp; Cabang</th>
            <th class="py-3.5 px-4 font-medium">Status Langganan</th>
            <th class="py-3.5 px-4 font-medium">Masa Aktif</th>
            <th class="py-3.5 px-4 font-medium">Kuota Outlet</th>
            <th class="py-3.5 px-4 text-right font-medium">Kontrol Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#d9d9dd]/60">
          {#if isLoading}
            <tr>
              <td colspan="6" class="py-12 text-center text-[#93939f]">
                <RefreshCw class="w-5 h-5 animate-spin mx-auto mb-2 text-[#1863dc]" />
                <span>Memuat data direktori tenant...</span>
              </td>
            </tr>
          {:else if filteredTenants.length === 0}
            <tr>
              <td colspan="6" class="py-12 text-center text-[#93939f]">
                <Building2 class="w-6 h-6 mx-auto mb-2 text-[#93939f] opacity-50" />
                <span>Tidak ada data tenant yang cocok.</span>
              </td>
            </tr>
          {:else}
            {#each filteredTenants as tenant (tenant.id)}
              <tr class="hover:bg-[#eeece7]/20 transition-colors">
                <!-- Owner Info -->
                <td class="py-3.5 px-4">
                  <div class="font-medium text-[#212121]">{tenant.name}</div>
                  <div class="font-mono text-[#75758a] text-[11px]">{tenant.email}</div>
                  <div class="text-[10px] text-[#93939f] mt-0.5">
                    Daftar: {new Date(tenant.created_at).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'short',
                      year: 'numeric',
                    })}
                  </div>
                </td>

                <!-- Workspaces & Branches -->
                <td class="py-3.5 px-4">
                  {#if tenant.workspaces && tenant.workspaces.length > 0}
                    <div class="space-y-1">
                      {#each tenant.workspaces as ws}
                        <div class="flex items-center space-x-1.5">
                          <Store class="w-3.5 h-3.5 text-[#1863dc] shrink-0" />
                          <span class="font-medium text-[#212121]">{ws.name}</span>
                          <span class="text-[10px] px-2 py-0.2 bg-[#f1f5ff] text-[#1863dc] font-mono rounded-full font-medium">
                            {ws.branches_count} cabang
                          </span>
                        </div>
                      {/each}
                    </div>
                  {:else}
                    <span class="text-[#93939f] italic text-[11px]">Belum membuat workspace</span>
                  {/if}
                </td>

                <!-- Status Badge -->
                <td class="py-3.5 px-4">
                  {#if tenant.subscription_status === 'ACTIVE'}
                    <span class="inline-flex items-center px-2.5 py-0.5 bg-[#edfce9] text-[#003c33] text-[11px] font-medium rounded-full">
                      ACTIVE
                    </span>
                  {:else if tenant.subscription_status === 'GRACE_PERIOD'}
                    <span class="inline-flex items-center px-2.5 py-0.5 bg-[#eeece7] text-[#616161] text-[11px] font-medium rounded-full">
                      GRACE PERIOD
                    </span>
                  {:else if tenant.subscription_status === 'SUSPENDED'}
                    <span class="inline-flex items-center px-2.5 py-0.5 bg-[#ffad9b]/20 text-[#b30000] text-[11px] font-medium rounded-full">
                      SUSPENDED
                    </span>
                  {:else}
                    <span class="inline-flex items-center px-2.5 py-0.5 bg-[#f1f5ff] text-[#1863dc] text-[11px] font-medium rounded-full">
                      TRIAL
                    </span>
                  {/if}
                </td>

                <!-- Expiry Date & Days Remaining -->
                <td class="py-3.5 px-4">
                  {#if tenant.subscription_expires_at}
                    <div class="font-mono text-[#212121]">
                      {new Date(tenant.subscription_expires_at).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                      })}
                    </div>
                    {#if tenant.days_remaining !== null}
                      <div class="text-[10px] mt-0.5 font-mono">
                        {#if tenant.days_remaining > 5}
                          <span class="text-[#003c33] font-medium">Sisa {tenant.days_remaining} hari</span>
                        {:else if tenant.days_remaining >= 0}
                          <span class="text-[#ff7759] font-medium">H-{tenant.days_remaining} Kedaluwarsa!</span>
                        {:else}
                          <span class="text-[#b30000] font-medium">Lewat {-tenant.days_remaining} hari</span>
                        {/if}
                      </div>
                    {/if}
                  {:else}
                    <span class="text-[#93939f] italic text-[11px]">Tanpa Batas / Belum Diatur</span>
                  {/if}
                </td>

                <!-- Max Workspaces -->
                <td class="py-3.5 px-4 font-mono font-medium text-[#212121]">
                  {tenant.workspaces.length} / {tenant.max_workspaces} Outlet
                </td>

                <!-- Actions -->
                <td class="py-3.5 px-4 text-right space-x-1.5">
                  <button
                    type="button"
                    onclick={() => openExtendModal(tenant)}
                    class="inline-flex items-center space-x-1 px-3 py-1 bg-white hover:bg-[#eeece7] text-[#212121] border border-[#d9d9dd] rounded-full text-xs font-medium transition-all cursor-pointer"
                  >
                    <PlusCircle class="w-3.5 h-3.5 text-[#1863dc]" />
                    <span>Perpanjang</span>
                  </button>

                  <button
                    type="button"
                    onclick={() => handleToggleSuspend(tenant)}
                    class={`inline-flex items-center space-x-1 px-3 py-1 text-xs font-medium rounded-full transition-all cursor-pointer ${
                      tenant.subscription_status === 'SUSPENDED'
                        ? 'bg-[#003c33] hover:bg-[#002822] text-white'
                        : 'bg-white hover:bg-[#ffad9b]/15 text-[#b30000] border border-[#d9d9dd]'
                    }`}
                  >
                    {#if tenant.subscription_status === 'SUSPENDED'}
                      <ShieldCheck class="w-3.5 h-3.5" />
                      <span>Buka Kunci</span>
                    {:else}
                      <ShieldAlert class="w-3.5 h-3.5" />
                      <span>Suspend</span>
                    {/if}
                  </button>
                </td>
              </tr>
            {/each}
          {/if}
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Perpanjang Masa Aktif Manual -->
{#if extendingTenant}
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] w-full max-w-md shadow-none overflow-hidden animate-in fade-in zoom-in-95">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-[#d9d9dd] bg-[#17171c] text-white">
        <div class="flex items-center space-x-2">
          <Calendar class="w-4 h-4 text-[#edfce9]" />
          <h2 class="text-sm font-medium">Perpanjang Masa Aktif Langganan</h2>
        </div>
        <button type="button" onclick={closeExtendModal} class="text-[#93939f] hover:text-white cursor-pointer p-1">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-4 text-xs">
        <div>
          <span class="text-[11px] text-[#75758a] uppercase font-medium">Tenant:</span>
          <div class="font-medium text-[#212121] text-sm mt-0.5">{extendingTenant.name}</div>
          <div class="font-mono text-[#75758a] text-[11px]">{extendingTenant.email}</div>
        </div>

        <div>
          <label for="extension-days" class="block font-medium text-[#212121] text-[11px] mb-2">
            Pilih Durasi Tambahan:
          </label>
          <div class="grid grid-cols-3 gap-2">
            <button
              type="button"
              onclick={() => (extensionDays = 30)}
              class={`py-2 px-3 text-xs font-mono rounded-full border transition-all cursor-pointer ${
                extensionDays === 30
                  ? 'bg-[#17171c] text-white border-[#17171c] font-medium shadow-none'
                  : 'bg-white text-[#616161] border-[#d9d9dd] hover:bg-[#eeece7]'
              }`}
            >
              +30 Hari
            </button>

            <button
              type="button"
              onclick={() => (extensionDays = 90)}
              class={`py-2 px-3 text-xs font-mono rounded-full border transition-all cursor-pointer ${
                extensionDays === 90
                  ? 'bg-[#17171c] text-white border-[#17171c] font-medium shadow-none'
                  : 'bg-white text-[#616161] border-[#d9d9dd] hover:bg-[#eeece7]'
              }`}
            >
              +90 Hari
            </button>

            <button
              type="button"
              onclick={() => (extensionDays = 365)}
              class={`py-2 px-3 text-xs font-mono rounded-full border transition-all cursor-pointer ${
                extensionDays === 365
                  ? 'bg-[#17171c] text-white border-[#17171c] font-medium shadow-none'
                  : 'bg-white text-[#616161] border-[#d9d9dd] hover:bg-[#eeece7]'
              }`}
            >
              +365 Hari
            </button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-[#d9d9dd] bg-[#eeece7]/30 flex items-center justify-between">
        <button
          type="button"
          onclick={closeExtendModal}
          class="px-4 py-2 bg-white hover:bg-[#eeece7] text-[#616161] text-xs font-medium border border-[#d9d9dd] rounded-full cursor-pointer transition-all"
        >
          Batal
        </button>

        <button
          type="button"
          onclick={handleExtendSubmit}
          disabled={isSubmittingAction}
          class="px-5 py-2 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full transition-all inline-flex items-center space-x-1.5 cursor-pointer disabled:opacity-50 shadow-none"
        >
          {#if isSubmittingAction}
            <RefreshCw class="w-3.5 h-3.5 animate-spin" />
            <span>Menyimpan...</span>
          {:else}
            <CheckCircle class="w-3.5 h-3.5" />
            <span>Perpanjang {extensionDays} Hari</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
