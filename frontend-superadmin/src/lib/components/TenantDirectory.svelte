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
    onUpdateStatus: (
      tenantId: string,
      status: 'ACTIVE' | 'SUSPENDED' | 'GRACE_PERIOD' | 'TRIAL'
    ) => Promise<void>;
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
        (t.workspaces &&
          t.workspaces.some(
            (w) => w.name.toLowerCase().includes(q) || w.slug.toLowerCase().includes(q)
          ));

      return matchStatus && matchQuery;
    })
  );

  async function handleToggleSuspend(tenant: TenantRecord) {
    const newStatus = tenant.subscription_status === 'SUSPENDED' ? 'ACTIVE' : 'SUSPENDED';
    const actionLabel =
      newStatus === 'SUSPENDED' ? 'menonaktifkan (suspend)' : 'mengaktifkan kembali';

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
  <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
    <div>
      <h1 class="text-xl font-semibold tracking-tight text-[#17171c]">Direktori Tenant</h1>
      <p class="mt-0.5 text-xs text-[#75758a]">
        Kelola akun pemilik bisnis SaaS, pantau masa aktif langganan, dan atur kuota cabang.
      </p>
    </div>

    <button
      type="button"
      onclick={onRefresh}
      disabled={isLoading}
      class="inline-flex cursor-pointer items-center gap-1.5 self-start rounded-lg border border-[#d9d9dd] bg-white px-3.5 py-2 text-xs font-medium text-[#17171c] shadow-xs transition-all hover:bg-[#f4f4f4] disabled:opacity-50 sm:self-auto"
    >
      <RefreshCw class={`h-3.5 w-3.5 ${isLoading ? 'animate-spin' : ''}`} />
      <span>Segarkan</span>
    </button>
  </div>

  <!-- Notification Toast -->
  {#if actionMessage}
    <div
      class="flex items-center justify-between rounded-xl border border-[#bbf7d0] bg-[#edfce9] p-3.5 text-xs text-[#003c33]"
    >
      <div class="flex items-center gap-2">
        <CheckCircle class="h-4 w-4 shrink-0 text-[#16a34a]" />
        <span class="font-medium">{actionMessage}</span>
      </div>
      <button
        type="button"
        onclick={() => (actionMessage = null)}
        class="text-[#003c33] hover:opacity-70"
      >
        <X class="h-4 w-4" />
      </button>
    </div>
  {/if}

  {#if actionError}
    <div
      class="flex items-center justify-between rounded-xl border border-[#fecaca] bg-[#ffefef] p-3.5 text-xs text-[#e5484d]"
    >
      <div class="flex items-center gap-2">
        <AlertTriangle class="h-4 w-4 shrink-0 text-[#dc2626]" />
        <span class="font-medium">{actionError}</span>
      </div>
      <button
        type="button"
        onclick={() => (actionError = null)}
        class="text-[#e5484d] hover:opacity-70"
      >
        <X class="h-4 w-4" />
      </button>
    </div>
  {/if}

  <!-- Unified Main Container Card -->
  <div class="overflow-hidden rounded-xl border border-[#d9d9dd] bg-white shadow-xs">
    <!-- Toolbar: Filter Pills & Search -->
    <div
      class="flex flex-col justify-between gap-3 border-b border-[#e5e5e5] bg-[#fafafa] p-3.5 sm:p-4 md:flex-row md:items-center"
    >
      <!-- Filter Pills -->
      <div class="flex flex-wrap items-center gap-1">
        <button
          type="button"
          onclick={() => (selectedStatus = 'ALL')}
          class={`cursor-pointer rounded-md px-3 py-1 text-xs font-medium transition-all ${
            selectedStatus === 'ALL'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:bg-[#eaeaea] hover:text-[#17171c]'
          }`}
        >
          Semua ({tenants.length})
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'ACTIVE')}
          class={`cursor-pointer rounded-md px-3 py-1 text-xs font-medium transition-all ${
            selectedStatus === 'ACTIVE'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:bg-[#eaeaea] hover:text-[#17171c]'
          }`}
        >
          Aktif
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'GRACE_PERIOD')}
          class={`cursor-pointer rounded-md px-3 py-1 text-xs font-medium transition-all ${
            selectedStatus === 'GRACE_PERIOD'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:bg-[#eaeaea] hover:text-[#17171c]'
          }`}
        >
          Grace Period
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'SUSPENDED')}
          class={`cursor-pointer rounded-md px-3 py-1 text-xs font-medium transition-all ${
            selectedStatus === 'SUSPENDED'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:bg-[#eaeaea] hover:text-[#17171c]'
          }`}
        >
          Suspended
        </button>
      </div>

      <!-- Search Input -->
      <div class="relative w-full md:w-64">
        <Search class="absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-[#93939f]" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari tenant / workspace..."
          class="w-full rounded-lg border border-[#d9d9dd] bg-white py-1.5 pr-3 pl-8 text-xs text-[#17171c] transition-all focus:border-[#17171c] focus:outline-hidden"
        />
      </div>
    </div>

    <!-- Content State: Loading / Empty / Data -->
    {#if isLoading}
      <div class="space-y-2 py-16 text-center text-[#93939f]">
        <RefreshCw class="mx-auto h-5 w-5 animate-spin text-[#17171c]" />
        <p class="text-xs">Memuat data direktori tenant...</p>
      </div>
    {:else if filteredTenants.length === 0}
      <div class="space-y-2 py-16 text-center text-[#93939f]">
        <Building2 class="mx-auto h-8 w-8 text-[#93939f] opacity-40" />
        <p class="text-xs font-medium text-[#17171c]">Tidak ada data tenant yang cocok</p>
        <p class="text-[11px] text-[#75758a]">
          Data tenant terdaftar akan otomatis muncul di sini.
        </p>
      </div>
    {:else}
      <!-- Desktop Table View -->
      <div class="hidden overflow-x-auto md:block">
        <table class="w-full border-collapse text-left text-xs">
          <thead
            class="border-b border-[#e5e5e5] bg-[#fafafa] text-[11px] font-medium text-[#75758a]"
          >
            <tr>
              <th class="px-4 py-3">Pemilik Bisnis (Owner)</th>
              <th class="px-4 py-3">Workspace &amp; Cabang</th>
              <th class="px-4 py-3 text-center">Status Langganan</th>
              <th class="px-4 py-3 text-center">Masa Aktif</th>
              <th class="px-4 py-3 text-center">Kuota Cabang</th>
              <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#e5e5e5]">
            {#each filteredTenants as tenant (tenant.id)}
              <tr class="transition-colors hover:bg-[#f9fafb]">
                <!-- Owner Info -->
                <td class="px-4 py-3.5">
                  <div class="font-medium text-[#17171c]">{tenant.name}</div>
                  <div class="font-mono text-[11px] text-[#75758a]">{tenant.email}</div>
                  <div class="mt-0.5 text-[10px] text-[#93939f]">
                    Daftar: {new Date(tenant.created_at).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'short',
                      year: 'numeric',
                    })}
                  </div>
                </td>

                <!-- Workspaces & Branches -->
                <td class="px-4 py-3.5">
                  {#if tenant.workspaces && tenant.workspaces.length > 0}
                    <div class="space-y-1">
                      {#each tenant.workspaces as ws}
                        <div class="flex items-center gap-1.5">
                          <Store class="h-3.5 w-3.5 shrink-0 text-[#75758a]" />
                          <span class="font-medium text-[#17171c]">{ws.name}</span>
                          <span
                            class="py-0.2 rounded-md bg-[#f1f5ff] px-1.5 font-mono text-[10px] text-[#1863dc]"
                          >
                            {ws.branches_count} cabang
                          </span>
                        </div>
                      {/each}
                    </div>
                  {:else}
                    <span class="text-[11px] text-[#93939f] italic">Belum membuat workspace</span>
                  {/if}
                </td>

                <!-- Status Badge -->
                <td class="px-4 py-3.5 text-center">
                  {#if tenant.subscription_status === 'ACTIVE'}
                    <span
                      class="inline-flex items-center rounded-md bg-[#edfce9] px-2 py-0.5 text-[10px] font-semibold text-[#003c33]"
                    >
                      ACTIVE
                    </span>
                  {:else if tenant.subscription_status === 'GRACE_PERIOD'}
                    <span
                      class="inline-flex items-center rounded-md bg-[#fef3c7] px-2 py-0.5 text-[10px] font-semibold text-[#92400e]"
                    >
                      GRACE PERIOD
                    </span>
                  {:else if tenant.subscription_status === 'SUSPENDED'}
                    <span
                      class="inline-flex items-center rounded-md bg-[#fee2e2] px-2 py-0.5 text-[10px] font-semibold text-[#991b1b]"
                    >
                      SUSPENDED
                    </span>
                  {:else}
                    <span
                      class="inline-flex items-center rounded-md bg-[#f1f5ff] px-2 py-0.5 text-[10px] font-semibold text-[#1863dc]"
                    >
                      TRIAL
                    </span>
                  {/if}
                </td>

                <!-- Subscription Expiry Info -->
                <td class="px-4 py-3.5 text-center font-mono">
                  {#if tenant.subscription_expires_at}
                    <div class="text-xs text-[#17171c]">
                      {new Date(tenant.subscription_expires_at).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                      })}
                    </div>
                    {#if tenant.days_remaining !== null}
                      <div
                        class={`mt-0.5 text-[10px] font-semibold ${tenant.days_remaining < 7 ? 'text-[#e5484d]' : 'text-[#16a34a]'}`}
                      >
                        {tenant.days_remaining > 0
                          ? `Sisa ${tenant.days_remaining} hari`
                          : 'Kedaluwarsa'}
                      </div>
                    {/if}
                  {:else}
                    <span class="text-[11px] text-[#93939f]">Lifetime</span>
                  {/if}
                </td>

                <!-- Max Workspaces / Quota -->
                <td class="px-4 py-3.5 text-center font-mono text-xs text-[#17171c]">
                  Maks. {tenant.max_workspaces}
                </td>

                <!-- Action Controls -->
                <td class="px-4 py-3.5 text-right">
                  <div class="inline-flex items-center gap-1.5">
                    <button
                      type="button"
                      onclick={() => openExtendModal(tenant)}
                      class="flex cursor-pointer items-center gap-1 rounded-md bg-[#17171c] px-2.5 py-1 text-xs font-medium text-white transition-all hover:bg-black"
                      title="Perpanjang Masa Aktif"
                    >
                      <PlusCircle class="h-3 w-3" />
                      <span>Perpanjang</span>
                    </button>

                    <button
                      type="button"
                      onclick={() => handleToggleSuspend(tenant)}
                      disabled={isSubmittingAction}
                      class={`cursor-pointer rounded-md border px-2.5 py-1 text-xs font-medium transition-all ${
                        tenant.subscription_status === 'SUSPENDED'
                          ? 'border-[#bbf7d0] bg-[#edfce9] text-[#003c33] hover:bg-[#dcfce7]'
                          : 'border-[#d9d9dd] text-[#616161] hover:border-[#fecaca] hover:bg-[#fee2e2] hover:text-[#991b1b]'
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
      <div class="divide-y divide-[#e5e5e5] md:hidden">
        {#each filteredTenants as tenant (tenant.id)}
          <div class="space-y-3 p-4">
            <!-- Card Header: Name & Status -->
            <div class="flex items-start justify-between gap-2">
              <div>
                <div class="text-sm font-medium text-[#17171c]">{tenant.name}</div>
                <div class="font-mono text-xs text-[#75758a]">{tenant.email}</div>
              </div>

              {#if tenant.subscription_status === 'ACTIVE'}
                <span
                  class="inline-flex shrink-0 items-center rounded-md bg-[#edfce9] px-2 py-0.5 text-[10px] font-semibold text-[#003c33]"
                >
                  ACTIVE
                </span>
              {:else if tenant.subscription_status === 'GRACE_PERIOD'}
                <span
                  class="inline-flex shrink-0 items-center rounded-md bg-[#fef3c7] px-2 py-0.5 text-[10px] font-semibold text-[#92400e]"
                >
                  GRACE PERIOD
                </span>
              {:else if tenant.subscription_status === 'SUSPENDED'}
                <span
                  class="inline-flex shrink-0 items-center rounded-md bg-[#fee2e2] px-2 py-0.5 text-[10px] font-semibold text-[#991b1b]"
                >
                  SUSPENDED
                </span>
              {:else}
                <span
                  class="inline-flex shrink-0 items-center rounded-md bg-[#f1f5ff] px-2 py-0.5 text-[10px] font-semibold text-[#1863dc]"
                >
                  TRIAL
                </span>
              {/if}
            </div>

            <!-- Workspace Details -->
            <div class="space-y-1.5 rounded-lg border border-[#e5e5e5] bg-[#fafafa] p-2.5">
              <span class="font-mono text-[10px] text-[#75758a] uppercase">Workspace Bisnis:</span>
              {#if tenant.workspaces && tenant.workspaces.length > 0}
                {#each tenant.workspaces as ws}
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-[#17171c]">{ws.name}</span>
                    <span
                      class="py-0.2 rounded-md bg-[#f1f5ff] px-1.5 font-mono text-[10px] text-[#1863dc]"
                    >
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
              <div class="rounded-lg border border-[#e5e5e5] bg-[#fafafa] p-2">
                <div class="text-[10px] text-[#75758a]">Masa Aktif</div>
                <div class="mt-0.5 font-mono font-medium text-[#17171c]">
                  {#if tenant.days_remaining !== null}
                    <span class={tenant.days_remaining < 7 ? 'text-[#e5484d]' : 'text-[#16a34a]'}>
                      {tenant.days_remaining > 0
                        ? `Sisa ${tenant.days_remaining} hari`
                        : 'Kedaluwarsa'}
                    </span>
                  {:else}
                    Lifetime
                  {/if}
                </div>
              </div>

              <div class="rounded-lg border border-[#e5e5e5] bg-[#fafafa] p-2">
                <div class="text-[10px] text-[#75758a]">Maksimal Kuota</div>
                <div class="mt-0.5 font-mono font-medium text-[#17171c]">
                  {tenant.max_workspaces} Workspace
                </div>
              </div>
            </div>

            <!-- Mobile Action Buttons -->
            <div class="flex items-center gap-2 pt-1">
              <button
                type="button"
                onclick={() => openExtendModal(tenant)}
                class="flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-[#17171c] py-2 text-xs font-medium text-white"
              >
                <PlusCircle class="h-3.5 w-3.5" />
                <span>Perpanjang</span>
              </button>

              <button
                type="button"
                onclick={() => handleToggleSuspend(tenant)}
                disabled={isSubmittingAction}
                class={`rounded-lg border px-4 py-2 text-xs font-medium transition-all ${
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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-md space-y-4 rounded-2xl border border-[#d9d9dd] bg-white p-6 shadow-xl"
    >
      <div class="flex items-center justify-between border-b border-[#e5e5e5] pb-3">
        <h3 class="text-sm font-semibold text-[#17171c]">Perpanjang Masa Aktif Tenant</h3>
        <button
          type="button"
          onclick={closeExtendModal}
          class="cursor-pointer text-[#93939f] hover:text-[#17171c]"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div class="rounded-xl border border-[#e5e5e5] bg-[#fafafa] p-3">
          <div class="font-medium text-[#17171c]">{extendingTenant.name}</div>
          <div class="font-mono text-[11px] text-[#75758a]">{extendingTenant.email}</div>
          <div class="mt-1 font-mono text-[10px] text-[#75758a]">
            Status Saat Ini: <span class="font-semibold text-[#17171c]"
              >{extendingTenant.subscription_status}</span
            >
          </div>
        </div>

        <div class="space-y-1.5">
          <label for="extension-days-input" class="block font-medium text-[#17171c]">
            Durasi Perpanjangan (Hari)
          </label>
          <div class="mb-2 grid grid-cols-3 gap-2">
            <button
              type="button"
              onclick={() => (extensionDays = 30)}
              class={`cursor-pointer rounded-lg border py-1.5 font-mono text-xs font-medium transition-all ${
                extensionDays === 30
                  ? 'border-[#17171c] bg-[#17171c] text-white'
                  : 'border-[#d9d9dd] bg-white text-[#17171c] hover:bg-[#f4f4f4]'
              }`}
            >
              +30 Hari
            </button>
            <button
              type="button"
              onclick={() => (extensionDays = 90)}
              class={`cursor-pointer rounded-lg border py-1.5 font-mono text-xs font-medium transition-all ${
                extensionDays === 90
                  ? 'border-[#17171c] bg-[#17171c] text-white'
                  : 'border-[#d9d9dd] bg-white text-[#17171c] hover:bg-[#f4f4f4]'
              }`}
            >
              +90 Hari
            </button>
            <button
              type="button"
              onclick={() => (extensionDays = 365)}
              class={`cursor-pointer rounded-lg border py-1.5 font-mono text-xs font-medium transition-all ${
                extensionDays === 365
                  ? 'border-[#17171c] bg-[#17171c] text-white'
                  : 'border-[#d9d9dd] bg-white text-[#17171c] hover:bg-[#f4f4f4]'
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
            class="w-full rounded-lg border border-[#d9d9dd] bg-white px-3 py-2 font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
      </div>

      <div class="flex space-x-2.5 pt-2">
        <button
          type="button"
          onclick={closeExtendModal}
          class="flex-1 cursor-pointer rounded-lg border border-[#d9d9dd] py-2 text-xs font-medium text-[#616161] hover:bg-[#f4f4f4]"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleExtendSubmit}
          disabled={isSubmittingAction}
          class="flex-1 cursor-pointer rounded-lg bg-[#17171c] py-2 text-xs font-medium text-white transition-all hover:bg-black disabled:opacity-50"
        >
          {isSubmittingAction ? 'Menyimpan...' : 'Simpan'}
        </button>
      </div>
    </div>
  </div>
{/if}
