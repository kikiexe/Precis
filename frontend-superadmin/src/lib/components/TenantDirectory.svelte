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

<div class="space-y-6">
  <!-- Top Bar Title -->
  <div class="bg-white p-4 border border-[#e0e0e0] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-[#161616] tracking-tight">Direktori Tenant & Manajemen Workspace</h1>
      <p class="text-xs text-[#525252] mt-0.5">
        Kelola seluruh pemilik akun bisnis SaaS, pantau status langganan, dan atur kuota workspace/cabang.
      </p>
    </div>

    <button
      type="button"
      onclick={onRefresh}
      disabled={isLoading}
      class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-[#f4f4f4] hover:bg-[#e0e0e0] text-[#161616] text-xs font-medium border border-[#e0e0e0] transition-colors disabled:opacity-50"
    >
      <RefreshCw class={`w-3.5 h-3.5 ${isLoading ? 'animate-spin' : ''}`} />
      <span>Segarkan Data</span>
    </button>
  </div>

  {#if actionMessage}
    <div class="p-3 bg-[#defbe6] border border-[#24a148] text-[#24a148] text-xs font-medium flex items-center space-x-2">
      <CheckCircle class="w-4 h-4 shrink-0" />
      <span>{actionMessage}</span>
    </div>
  {/if}

  {#if actionError}
    <div class="p-3 bg-[#ffebee] border border-[#da1e28] text-[#da1e28] text-xs font-medium flex items-center space-x-2">
      <AlertTriangle class="w-4 h-4 shrink-0" />
      <span>{actionError}</span>
    </div>
  {/if}

  <!-- Filter & Search Bar -->
  <div class="bg-white p-4 border border-[#e0e0e0] space-y-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
      <!-- Status Tabs -->
      <div class="flex flex-wrap gap-1 bg-[#f4f4f4] p-1 border border-[#e0e0e0]">
        <button
          type="button"
          onclick={() => (selectedStatus = 'ALL')}
          class={`px-3 py-1.5 text-xs font-medium transition-colors ${
            selectedStatus === 'ALL' ? 'bg-[#161616] text-white shadow-sm' : 'text-[#525252] hover:text-[#161616]'
          }`}
        >
          Semua ({tenants.length})
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'ACTIVE')}
          class={`px-3 py-1.5 text-xs font-medium transition-colors ${
            selectedStatus === 'ACTIVE' ? 'bg-[#161616] text-white shadow-sm' : 'text-[#525252] hover:text-[#161616]'
          }`}
        >
          Aktif
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'GRACE_PERIOD')}
          class={`px-3 py-1.5 text-xs font-medium transition-colors ${
            selectedStatus === 'GRACE_PERIOD' ? 'bg-[#161616] text-white shadow-sm' : 'text-[#525252] hover:text-[#161616]'
          }`}
        >
          Grace Period
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'SUSPENDED')}
          class={`px-3 py-1.5 text-xs font-medium transition-colors ${
            selectedStatus === 'SUSPENDED' ? 'bg-[#161616] text-white shadow-sm' : 'text-[#525252] hover:text-[#161616]'
          }`}
        >
          Suspended
        </button>
      </div>

      <!-- Search Box -->
      <div class="relative w-full md:w-72">
        <Search class="w-3.5 h-3.5 text-[#8c8c8c] absolute left-3 top-1/2 -translate-y-1/2" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari tenant / email / kedai..."
          class="w-full pl-9 pr-3 py-1.5 text-xs bg-[#f4f4f4] border border-[#e0e0e0] focus:outline-none focus:border-[#0f62fe] focus:bg-white text-[#161616]"
        />
      </div>
    </div>
  </div>

  <!-- Tenants Table -->
  <div class="bg-white border border-[#e0e0e0] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-[#f4f4f4] border-b border-[#e0e0e0] text-[#525252] font-semibold uppercase tracking-wider">
          <tr>
            <th class="py-3 px-4">Pemilik Akun (Owner)</th>
            <th class="py-3 px-4">Workspace & Cabang</th>
            <th class="py-3 px-4">Status Langganan</th>
            <th class="py-3 px-4">Masa Aktif</th>
            <th class="py-3 px-4">Kuota Outlet</th>
            <th class="py-3 px-4 text-right">Kontrol Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#f4f4f4]">
          {#if isLoading}
            <tr>
              <td colspan="6" class="py-12 text-center text-[#8c8c8c]">
                <RefreshCw class="w-5 h-5 animate-spin mx-auto mb-2 text-[#0f62fe]" />
                <span>Memuat data direktori tenant...</span>
              </td>
            </tr>
          {:else if filteredTenants.length === 0}
            <tr>
              <td colspan="6" class="py-12 text-center text-[#8c8c8c]">
                <Building2 class="w-6 h-6 mx-auto mb-2 text-[#c6c6c6]" />
                <span>Tidak ada data tenant yang cocok.</span>
              </td>
            </tr>
          {:else}
            {#each filteredTenants as tenant (tenant.id)}
              <tr class="hover:bg-[#fbfbfb] transition-colors">
                <!-- Owner Info -->
                <td class="py-3 px-4">
                  <div class="font-bold text-[#161616]">{tenant.name}</div>
                  <div class="font-mono text-[#525252] text-[11px]">{tenant.email}</div>
                  <div class="text-[10px] text-[#8c8c8c] mt-0.5">
                    Daftar: {new Date(tenant.created_at).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'short',
                      year: 'numeric',
                    })}
                  </div>
                </td>

                <!-- Workspaces & Branches -->
                <td class="py-3 px-4">
                  {#if tenant.workspaces && tenant.workspaces.length > 0}
                    <div class="space-y-1">
                      {#each tenant.workspaces as ws}
                        <div class="flex items-center space-x-1.5">
                          <Store class="w-3 h-3 text-[#0f62fe] shrink-0" />
                          <span class="font-medium text-[#161616]">{ws.name}</span>
                          <span class="text-[10px] px-1.5 py-0.2 bg-[#edf5ff] text-[#0f62fe] font-mono">
                            {ws.branches_count} cabang
                          </span>
                        </div>
                      {/each}
                    </div>
                  {:else}
                    <span class="text-[#8c8c8c] italic text-[11px]">Belum membuat workspace</span>
                  {/if}
                </td>

                <!-- Status Badge -->
                <td class="py-3 px-4">
                  {#if tenant.subscription_status === 'ACTIVE'}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#defbe6] text-[#24a148] text-[11px] font-semibold border border-[#6fdc8c]">
                      ACTIVE
                    </span>
                  {:else if tenant.subscription_status === 'GRACE_PERIOD'}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#fdf2d0] text-[#b28600] text-[11px] font-semibold border border-[#f1c21b]">
                      GRACE PERIOD
                    </span>
                  {:else if tenant.subscription_status === 'SUSPENDED'}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#ffebee] text-[#da1e28] text-[11px] font-semibold border border-[#da1e28]">
                      SUSPENDED
                    </span>
                  {:else}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#edf5ff] text-[#0f62fe] text-[11px] font-semibold border border-[#d0e2ff]">
                      TRIAL
                    </span>
                  {/if}
                </td>

                <!-- Expiry Date & Days Remaining -->
                <td class="py-3 px-4">
                  {#if tenant.subscription_expires_at}
                    <div class="font-mono text-[#161616]">
                      {new Date(tenant.subscription_expires_at).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                      })}
                    </div>
                    {#if tenant.days_remaining !== null}
                      <div class="text-[10px] mt-0.5 font-mono">
                        {#if tenant.days_remaining > 5}
                          <span class="text-[#24a148] font-semibold">Sisa {tenant.days_remaining} hari</span>
                        {:else if tenant.days_remaining >= 0}
                          <span class="text-[#b28600] font-bold">H-{tenant.days_remaining} Kedaluwarsa!</span>
                        {:else}
                          <span class="text-[#da1e28] font-bold">Lewat {-tenant.days_remaining} hari</span>
                        {/if}
                      </div>
                    {/if}
                  {:else}
                    <span class="text-[#8c8c8c] italic text-[11px]">Tanpa Batas / Belum Diatur</span>
                  {/if}
                </td>

                <!-- Max Workspaces -->
                <td class="py-3 px-4 font-mono font-semibold text-[#161616]">
                  {tenant.workspaces.length} / {tenant.max_workspaces} Outlet
                </td>

                <!-- Actions -->
                <td class="py-3 px-4 text-right space-x-1.5">
                  <button
                    type="button"
                    onclick={() => openExtendModal(tenant)}
                    class="inline-flex items-center space-x-1 px-2.5 py-1 bg-[#f4f4f4] hover:bg-[#e0e0e0] text-[#161616] border border-[#e0e0e0] text-xs font-medium transition-colors"
                  >
                    <PlusCircle class="w-3.5 h-3.5 text-[#0f62fe]" />
                    <span>Perpanjang</span>
                  </button>

                  <button
                    type="button"
                    onclick={() => handleToggleSuspend(tenant)}
                    class={`inline-flex items-center space-x-1 px-2.5 py-1 text-xs font-medium transition-colors ${
                      tenant.subscription_status === 'SUSPENDED'
                        ? 'bg-[#24a148] hover:bg-[#1e833a] text-white'
                        : 'bg-[#ffebee] hover:bg-[#da1e28] text-[#da1e28] hover:text-white border border-[#da1e28]'
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
  <div class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white border border-[#161616] w-full max-w-md shadow-2xl">
      <!-- Header -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-[#e0e0e0] bg-[#161616] text-white">
        <div class="flex items-center space-x-2">
          <Calendar class="w-4 h-4 text-[#0f62fe]" />
          <h2 class="text-sm font-bold">Perpanjang Masa Aktif Langganan</h2>
        </div>
        <button type="button" onclick={closeExtendModal} class="text-[#8c8c8c] hover:text-white">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-5 space-y-4 text-xs">
        <div>
          <span class="text-[11px] text-[#525252] uppercase font-semibold">Tenant:</span>
          <div class="font-bold text-[#161616] text-sm mt-0.5">{extendingTenant.name}</div>
          <div class="font-mono text-[#525252] text-[11px]">{extendingTenant.email}</div>
        </div>

        <div>
          <label for="extension-days" class="block font-semibold uppercase text-[#161616] text-[11px] mb-1.5">
            Pilih Durasi Tambahan:
          </label>
          <div class="grid grid-cols-3 gap-2">
            <button
              type="button"
              onclick={() => (extensionDays = 30)}
              class={`py-2 px-3 text-xs font-mono font-bold border transition-colors ${
                extensionDays === 30
                  ? 'bg-[#0f62fe] text-white border-[#0f62fe]'
                  : 'bg-[#f4f4f4] text-[#161616] border-[#e0e0e0] hover:bg-[#e0e0e0]'
              }`}
            >
              +30 Hari (1 Bln)
            </button>

            <button
              type="button"
              onclick={() => (extensionDays = 90)}
              class={`py-2 px-3 text-xs font-mono font-bold border transition-colors ${
                extensionDays === 90
                  ? 'bg-[#0f62fe] text-white border-[#0f62fe]'
                  : 'bg-[#f4f4f4] text-[#161616] border-[#e0e0e0] hover:bg-[#e0e0e0]'
              }`}
            >
              +90 Hari (3 Bln)
            </button>

            <button
              type="button"
              onclick={() => (extensionDays = 365)}
              class={`py-2 px-3 text-xs font-mono font-bold border transition-colors ${
                extensionDays === 365
                  ? 'bg-[#0f62fe] text-white border-[#0f62fe]'
                  : 'bg-[#f4f4f4] text-[#161616] border-[#e0e0e0] hover:bg-[#e0e0e0]'
              }`}
            >
              +365 Hari (1 Thn)
            </button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-5 py-3 border-t border-[#e0e0e0] bg-[#f4f4f4] flex items-center justify-between">
        <button
          type="button"
          onclick={closeExtendModal}
          class="px-4 py-2 bg-white hover:bg-[#e0e0e0] text-[#161616] text-xs font-medium border border-[#e0e0e0]"
        >
          Batal
        </button>

        <button
          type="button"
          onclick={handleExtendSubmit}
          disabled={isSubmittingAction}
          class="px-4 py-2 bg-[#0f62fe] hover:bg-[#0050e6] text-white text-xs font-bold transition-colors inline-flex items-center space-x-1.5 disabled:opacity-50"
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
