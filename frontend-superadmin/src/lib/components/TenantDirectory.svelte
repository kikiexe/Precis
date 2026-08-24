<script lang="ts">
  import {
    Building2,
    Search,
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
        (t.name && t.name.toLowerCase().includes(q)) ||
        (t.email && t.email.toLowerCase().includes(q)) ||
        (t.workspaces && t.workspaces.some((w) => w.name.toLowerCase().includes(q) || w.slug.toLowerCase().includes(q)));

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

<div class="space-y-5 font-sans">
  <!-- Clean Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-semibold text-[#17171c] tracking-tight">Direktori Tenant</h1>
      <p class="text-xs text-[#75758a] mt-0.5">
        Kelola akun pemilik bisnis SaaS, pantau masa aktif langganan, dan atur kuota cabang.
      </p>
    </div>

    <button
      type="button"
      onclick={onRefresh}
      disabled={isLoading}
      class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-[#f4f4f4] text-[#17171c] text-xs font-medium border border-[#d9d9dd] rounded-lg transition-all cursor-pointer disabled:opacity-50 self-start sm:self-auto shadow-xs"
    >
      <RefreshCw class={`w-3.5 h-3.5 ${isLoading ? 'animate-spin' : ''}`} />
      <span>Segarkan</span>
    </button>
  </div>

  <!-- Notification Toast -->
  {#if actionMessage}
    <div class="p-3.5 bg-[#edfce9] border border-[#bbf7d0] text-[#003c33] text-xs rounded-xl flex items-center justify-between">
      <div class="flex items-center gap-2">
        <CheckCircle class="w-4 h-4 shrink-0 text-[#16a34a]" />
        <span class="font-medium">{actionMessage}</span>
      </div>
      <button type="button" onclick={() => (actionMessage = null)} class="text-[#003c33] hover:opacity-70">
        <X class="w-4 h-4" />
      </button>
    </div>
  {/if}

  {#if actionError}
    <div class="p-3.5 bg-[#ffefef] border border-[#fecaca] text-[#e5484d] text-xs rounded-xl flex items-center justify-between">
      <div class="flex items-center gap-2">
        <AlertTriangle class="w-4 h-4 shrink-0 text-[#dc2626]" />
        <span class="font-medium">{actionError}</span>
      </div>
      <button type="button" onclick={() => (actionError = null)} class="text-[#e5484d] hover:opacity-70">
        <X class="w-4 h-4" />
      </button>
    </div>
  {/if}

  <!-- Unified Main Container Card -->
  <div class="bg-white border border-[#d9d9dd] rounded-xl shadow-xs overflow-hidden">
    <!-- Toolbar: Filter Pills & Search -->
    <div class="p-3.5 sm:p-4 border-b border-[#e5e5e5] bg-[#fafafa] flex flex-col md:flex-row md:items-center justify-between gap-3">
      <!-- Filter Pills -->
      <div class="flex flex-wrap items-center gap-1">
        <button
          type="button"
          onclick={() => (selectedStatus = 'ALL')}
          class={`px-3 py-1 text-xs font-medium rounded-md transition-all cursor-pointer ${
            selectedStatus === 'ALL'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:text-[#17171c] hover:bg-[#eaeaea]'
          }`}
        >
          Semua ({tenants.length})
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'ACTIVE')}
          class={`px-3 py-1 text-xs font-medium rounded-md transition-all cursor-pointer ${
            selectedStatus === 'ACTIVE'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:text-[#17171c] hover:bg-[#eaeaea]'
          }`}
        >
          Aktif
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'GRACE_PERIOD')}
          class={`px-3 py-1 text-xs font-medium rounded-md transition-all cursor-pointer ${
            selectedStatus === 'GRACE_PERIOD'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:text-[#17171c] hover:bg-[#eaeaea]'
          }`}
        >
          Grace Period
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'SUSPENDED')}
          class={`px-3 py-1 text-xs font-medium rounded-md transition-all cursor-pointer ${
            selectedStatus === 'SUSPENDED'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:text-[#17171c] hover:bg-[#eaeaea]'
          }`}
        >
          Suspended
        </button>
      </div>

      <!-- Search Input -->
      <div class="relative w-full md:w-64">
        <Search class="w-3.5 h-3.5 text-[#93939f] absolute left-3 top-1/2 -translate-y-1/2" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari tenant / workspace..."
          class="w-full pl-8 pr-3 py-1.5 text-xs bg-white border border-[#d9d9dd] rounded-lg focus:outline-hidden focus:border-[#17171c] text-[#17171c] transition-all"
        />
      </div>
    </div>

    <!-- Content State: Loading / Empty / Data -->
    {#if isLoading}
      <div class="py-16 text-center text-[#93939f] space-y-2">
        <RefreshCw class="w-5 h-5 animate-spin mx-auto text-[#17171c]" />
        <p class="text-xs">Memuat data direktori tenant...</p>
      </div>
    {:else if filteredTenants.length === 0}
      <div class="py-16 text-center text-[#93939f] space-y-2">
        <Building2 class="w-8 h-8 mx-auto text-[#93939f] opacity-40" />
        <p class="text-xs font-medium text-[#17171c]">Tidak ada data tenant yang cocok</p>
        <p class="text-[11px] text-[#75758a]">Data tenant terdaftar akan otomatis muncul di sini.</p>
      </div>
    {:else}
      <!-- Desktop Table View -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead class="bg-[#fafafa] border-b border-[#e5e5e5] text-[#75758a] font-medium text-[11px]">
            <tr>
              <th class="py-3 px-4">Pemilik Bisnis (Owner)</th>
              <th class="py-3 px-4">Workspace &amp; Cabang</th>
              <th class="py-3 px-4 text-center">Status Langganan</th>
              <th class="py-3 px-4 text-center">Masa Aktif</th>
              <th class="py-3 px-4 text-center">Kuota Cabang</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#e5e5e5]">
            {#each filteredTenants as tenant (tenant.id)}
              <tr class="hover:bg-[#f9fafb] transition-colors">
                <!-- Owner Info -->
                <td class="py-3.5 px-4">
                  <div class="font-medium text-[#17171c]">{tenant.name}</div>
                  <div class="text-[11px] text-[#75758a] font-mono">{tenant.email}</div>
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
                        <div class="flex items-center gap-1.5">
                          <Store class="w-3.5 h-3.5 text-[#75758a] shrink-0" />
                          <span class="font-medium text-[#17171c]">{ws.name}</span>
                          <span class="text-[10px] px-1.5 py-0.2 bg-[#f1f5ff] text-[#1863dc] font-mono rounded-md">
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
                <td class="py-3.5 px-4 text-center">
                  {#if tenant.subscription_status === 'ACTIVE'}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#edfce9] text-[#003c33] text-[10px] font-semibold rounded-md">
                      ACTIVE
                    </span>
                  {:else if tenant.subscription_status === 'GRACE_PERIOD'}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#fef3c7] text-[#92400e] text-[10px] font-semibold rounded-md">
                      GRACE PERIOD
                    </span>
                  {:else if tenant.subscription_status === 'SUSPENDED'}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#fee2e2] text-[#991b1b] text-[10px] font-semibold rounded-md">
                      SUSPENDED
                    </span>
                  {:else}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#f1f5ff] text-[#1863dc] text-[10px] font-semibold rounded-md">
                      TRIAL
                    </span>
                  {/if}
                </td>

                <!-- Subscription Expiry Info -->
                <td class="py-3.5 px-4 text-center font-mono">
                  {#if tenant.subscription_expires_at}
                    <div class="text-xs text-[#17171c]">
                      {new Date(tenant.subscription_expires_at).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                      })}
                    </div>
                    {#if tenant.days_remaining !== null}
                      <div class={`text-[10px] font-semibold mt-0.5 ${tenant.days_remaining < 7 ? 'text-[#e5484d]' : 'text-[#16a34a]'}`}>
                        {tenant.days_remaining > 0 ? `Sisa ${tenant.days_remaining} hari` : 'Kedaluwarsa'}
                      </div>
                    {/if}
                  {:else}
                    <span class="text-[#93939f] text-[11px]">Lifetime</span>
                  {/if}
                </td>

                <!-- Max Workspaces / Quota -->
                <td class="py-3.5 px-4 text-center font-mono text-xs text-[#17171c]">
                  Maks. {tenant.max_workspaces}
                </td>

                <!-- Action Controls -->
                <td class="py-3.5 px-4 text-right">
                  <div class="inline-flex items-center gap-1.5">
                    <button
                      type="button"
                      onclick={() => openExtendModal(tenant)}
                      class="px-2.5 py-1 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-md transition-all cursor-pointer flex items-center gap-1"
                      title="Perpanjang Masa Aktif"
                    >
                      <PlusCircle class="w-3 h-3" />
                      <span>Perpanjang</span>
                    </button>

                    <button
                      type="button"
                      onclick={() => handleToggleSuspend(tenant)}
                      disabled={isSubmittingAction}
                      class={`px-2.5 py-1 text-xs font-medium rounded-md border transition-all cursor-pointer ${
                        tenant.subscription_status === 'SUSPENDED'
                          ? 'border-[#bbf7d0] bg-[#edfce9] text-[#003c33] hover:bg-[#dcfce7]'
                          : 'border-[#d9d9dd] text-[#616161] hover:bg-[#fee2e2] hover:text-[#991b1b] hover:border-[#fecaca]'
                      }`}
                    >
                      {tenant.subscription_status === 'SUSPENDED' ? 'Aktifkan' : 'Suspend'}
                    </button>
                  </div>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>

      <!-- Mobile Clean Card View -->
      <div class="md:hidden divide-y divide-[#e5e5e5]">
        {#each filteredTenants as tenant (tenant.id)}
          <div class="p-4 space-y-3">
            <!-- Card Header: Name & Status -->
            <div class="flex items-start justify-between gap-2">
              <div>
                <div class="font-medium text-sm text-[#17171c]">{tenant.name}</div>
                <div class="text-xs text-[#75758a] font-mono">{tenant.email}</div>
              </div>

              {#if tenant.subscription_status === 'ACTIVE'}
                <span class="inline-flex items-center px-2 py-0.5 bg-[#edfce9] text-[#003c33] text-[10px] font-semibold rounded-md shrink-0">
                  ACTIVE
                </span>
              {:else if tenant.subscription_status === 'GRACE_PERIOD'}
                <span class="inline-flex items-center px-2 py-0.5 bg-[#fef3c7] text-[#92400e] text-[10px] font-semibold rounded-md shrink-0">
                  GRACE PERIOD
                </span>
              {:else if tenant.subscription_status === 'SUSPENDED'}
                <span class="inline-flex items-center px-2 py-0.5 bg-[#fee2e2] text-[#991b1b] text-[10px] font-semibold rounded-md shrink-0">
                  SUSPENDED
                </span>
              {:else}
                <span class="inline-flex items-center px-2 py-0.5 bg-[#f1f5ff] text-[#1863dc] text-[10px] font-semibold rounded-md shrink-0">
                  TRIAL
                </span>
              {/if}
            </div>

            <!-- Workspace Details -->
            <div class="bg-[#fafafa] p-2.5 rounded-lg border border-[#e5e5e5] space-y-1.5">
              <span class="text-[10px] font-mono uppercase text-[#75758a]">Workspace Bisnis:</span>
              {#if tenant.workspaces && tenant.workspaces.length > 0}
                {#each tenant.workspaces as ws}
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-[#17171c]">{ws.name}</span>
                    <span class="text-[10px] px-1.5 py-0.2 bg-[#f1f5ff] text-[#1863dc] font-mono rounded-md">
                      {ws.branches_count} Cabang
                    </span>
                  </div>
                {/each}
              {:else}
                <div class="text-xs text-[#93939f] italic">Belum ada workspace</div>
              {/if}
            </div>

            <!-- Expiry & Quota Meta -->
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div class="p-2 bg-[#fafafa] rounded-lg border border-[#e5e5e5]">
                <div class="text-[10px] text-[#75758a]">Masa Aktif</div>
                <div class="font-mono font-medium text-[#17171c] mt-0.5">
                  {#if tenant.days_remaining !== null}
                    <span class={tenant.days_remaining < 7 ? 'text-[#e5484d]' : 'text-[#16a34a]'}>
                      {tenant.days_remaining > 0 ? `Sisa ${tenant.days_remaining} hari` : 'Kedaluwarsa'}
                    </span>
                  {:else}
                    Lifetime
                  {/if}
                </div>
              </div>

              <div class="p-2 bg-[#fafafa] rounded-lg border border-[#e5e5e5]">
                <div class="text-[10px] text-[#75758a]">Maksimal Kuota</div>
                <div class="font-mono font-medium text-[#17171c] mt-0.5">
                  {tenant.max_workspaces} Workspace
                </div>
              </div>
            </div>

            <!-- Mobile Action Buttons -->
            <div class="flex items-center gap-2 pt-1">
              <button
                type="button"
                onclick={() => openExtendModal(tenant)}
                class="flex-1 py-2 text-xs font-medium bg-[#17171c] text-white rounded-lg flex items-center justify-center gap-1.5"
              >
                <PlusCircle class="w-3.5 h-3.5" />
                <span>Perpanjang</span>
              </button>

              <button
                type="button"
                onclick={() => handleToggleSuspend(tenant)}
                disabled={isSubmittingAction}
                class={`px-4 py-2 text-xs font-medium rounded-lg border transition-all ${
                  tenant.subscription_status === 'SUSPENDED'
                    ? 'border-[#bbf7d0] bg-[#edfce9] text-[#003c33]'
                    : 'border-[#d9d9dd] text-[#616161] hover:bg-[#fee2e2] hover:text-[#991b1b]'
                }`}
              >
                {tenant.subscription_status === 'SUSPENDED' ? 'Aktifkan' : 'Suspend'}
              </button>
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </div>
</div>

<!-- Modal Perpanjangan Masa Aktif Tenant -->
{#if extendingTenant}
  <div class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-2xl w-full max-w-md p-6 space-y-4 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e5e5e5] pb-3">
        <h3 class="text-sm font-semibold text-[#17171c]">Perpanjang Masa Aktif Tenant</h3>
        <button type="button" onclick={closeExtendModal} class="text-[#93939f] hover:text-[#17171c] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div class="p-3 bg-[#fafafa] rounded-xl border border-[#e5e5e5]">
          <div class="font-medium text-[#17171c]">{extendingTenant.name}</div>
          <div class="text-[11px] text-[#75758a] font-mono">{extendingTenant.email}</div>
          <div class="text-[10px] text-[#75758a] font-mono mt-1">
            Status Saat Ini: <span class="font-semibold text-[#17171c]">{extendingTenant.subscription_status}</span>
          </div>
        </div>

        <div class="space-y-1.5">
          <label for="extension-days-input" class="block font-medium text-[#17171c]">
            Durasi Perpanjangan (Hari)
          </label>
          <div class="grid grid-cols-3 gap-2 mb-2">
            <button
              type="button"
              onclick={() => (extensionDays = 30)}
              class={`py-1.5 text-xs font-mono font-medium rounded-lg border transition-all cursor-pointer ${
                extensionDays === 30 ? 'bg-[#17171c] text-white border-[#17171c]' : 'bg-white text-[#17171c] border-[#d9d9dd] hover:bg-[#f4f4f4]'
              }`}
            >
              +30 Hari
            </button>
            <button
              type="button"
              onclick={() => (extensionDays = 90)}
              class={`py-1.5 text-xs font-mono font-medium rounded-lg border transition-all cursor-pointer ${
                extensionDays === 90 ? 'bg-[#17171c] text-white border-[#17171c]' : 'bg-white text-[#17171c] border-[#d9d9dd] hover:bg-[#f4f4f4]'
              }`}
            >
              +90 Hari
            </button>
            <button
              type="button"
              onclick={() => (extensionDays = 365)}
              class={`py-1.5 text-xs font-mono font-medium rounded-lg border transition-all cursor-pointer ${
                extensionDays === 365 ? 'bg-[#17171c] text-white border-[#17171c]' : 'bg-white text-[#17171c] border-[#d9d9dd] hover:bg-[#f4f4f4]'
              }`}
            >
              +1 Tahun
            </button>
          </div>

          <input
            id="extension-days-input"
            type="number"
            min="1"
            max="3650"
            bind:value={extensionDays}
            class="w-full px-3 py-2 bg-white border border-[#d9d9dd] rounded-lg font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
      </div>

      <div class="pt-2 flex space-x-2.5">
        <button
          type="button"
          onclick={closeExtendModal}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-lg text-[#616161] hover:bg-[#f4f4f4] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleExtendSubmit}
          disabled={isSubmittingAction}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-lg transition-all cursor-pointer disabled:opacity-50"
        >
          {isSubmittingAction ? 'Menyimpan...' : 'Simpan'}
        </button>
      </div>
    </div>
  </div>
{/if}
