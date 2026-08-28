<script lang="ts">
  import {
    Plus,
    Users,
    Edit2,
    Trash2,
    Search,
    Shield,
    CheckCircle2,
    Lock,
    AlertCircle,
  } from 'lucide-svelte';
  import type { WorkspaceRole, PermissionsCatalog } from '../../../types/app';
  import { roleService } from '../../../services/role-service';
  import AddEditRoleModal from './modals/AddEditRoleModal.svelte';

  interface Props {
    roles: WorkspaceRole[];
    catalog: PermissionsCatalog | null;
    isLoading?: boolean;
    onRefresh: () => Promise<void>;
  }

  let { roles = [], catalog, isLoading = false, onRefresh }: Props = $props();

  let searchQuery = $state('');
  let isAddEditModalOpen = $state(false);
  let selectedRoleForEdit = $state<WorkspaceRole | null>(null);

  // Deletion modal state
  let roleToDelete = $state<WorkspaceRole | null>(null);
  let isDeletingRole = $state(false);
  let deleteError = $state<string | null>(null);

  let filteredRoles = $derived(
    roles.filter((r) => {
      const q = searchQuery.toLowerCase().trim();
      if (!q) return true;
      return (
        r.name.toLowerCase().includes(q) ||
        (r.description && r.description.toLowerCase().includes(q))
      );
    })
  );

  function handleOpenCreate() {
    selectedRoleForEdit = null;
    isAddEditModalOpen = true;
  }

  function handleOpenEdit(role: WorkspaceRole) {
    selectedRoleForEdit = role;
    isAddEditModalOpen = true;
  }

  function handleConfirmDelete(role: WorkspaceRole) {
    deleteError = null;
    roleToDelete = role;
  }

  async function executeDeleteRole() {
    if (!roleToDelete) return;
    isDeletingRole = true;
    deleteError = null;

    try {
      await roleService.deleteRole(roleToDelete.id);
      roleToDelete = null;
      await onRefresh();
    } catch (err: unknown) {
      deleteError = err instanceof Error ? err.message : 'Gagal menghapus role kustom.';
    } finally {
      isDeletingRole = false;
    }
  }

  // Get breakdown tags per module for a role
  function getModuleBreakdown(rolePerms: string[]): { name: string; count: number }[] {
    if (!catalog) return [];
    const result: { name: string; count: number }[] = [];

    for (const mod of Object.values(catalog.modules)) {
      const modKeys = Object.keys(mod.permissions);
      const count = modKeys.filter((k) => rolePerms.includes(k)).length;
      if (count > 0) {
        result.push({ name: mod.name, count });
      }
    }
    return result;
  }
</script>

<div class="space-y-6 font-sans">
  <!-- Action Bar & Overview -->
  <div
    class="flex flex-col justify-between gap-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:flex-row sm:items-center sm:rounded-3xl sm:p-6"
  >
    <div class="space-y-1">
      <div class="flex items-center gap-2">
        <h2 class="text-base font-bold text-[#17171c] sm:text-lg">Role &amp; Hak Akses</h2>
        <span
          class="rounded-full bg-[#f4f4f6] px-2.5 py-0.5 font-mono text-[10.5px] font-semibold text-[#17171c]"
        >
          {roles.length + 1} Role Aktif
        </span>
      </div>
      <p class="max-w-xl text-xs text-[#8e8e93]">
        Atur kewenangan fitur untuk staf outlet. Buat role kustom seperti Head Barista atau Akuntan,
        dan centang fitur yang dapat diakses.
      </p>
    </div>

    <div class="flex items-center gap-3">
      <div class="relative flex-1 sm:w-60">
        <Search
          class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#8e8e93]"
        />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari role..."
          class="w-full rounded-full border border-[#e5e5ea] bg-[#f8f8fa] py-2 pr-4 pl-10 text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
        />
      </div>

      <button
        type="button"
        onclick={handleOpenCreate}
        class="flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full bg-[#17171c] px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
      >
        <Plus class="h-4 w-4" />
        <span>Buat Role</span>
      </button>
    </div>
  </div>

  <!-- Role Cards Responsive Grid (1 col mobile, 2 col tablet, 3 col desktop) -->
  {#if isLoading}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
      {#each Array(3) as _}
        <div class="animate-pulse space-y-4 rounded-2xl border border-[#e5e5ea] bg-white p-6">
          <div class="h-5 w-1/2 rounded-md bg-gray-200"></div>
          <div class="h-4 w-3/4 rounded-md bg-gray-100"></div>
          <div class="h-12 rounded-xl bg-gray-50"></div>
        </div>
      {/each}
    </div>
  {:else if filteredRoles.length === 0 && searchQuery}
    <div
      class="space-y-3 rounded-2xl border border-[#e5e5ea] bg-white p-12 text-center shadow-2xs sm:rounded-3xl"
    >
      <div
        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f4f4f6] text-[#8e8e93]"
      >
        <Shield class="h-6 w-6" />
      </div>
      <div>
        <h3 class="text-sm font-bold text-[#17171c]">Tidak Ada Role Ditemukan</h3>
        <p class="mt-1 text-xs text-[#8e8e93]">
          Tidak ada role yang cocok dengan kata kunci "{searchQuery}".
        </p>
      </div>
    </div>
  {:else}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
      <!-- Special Protected Owner Card -->
      <div
        class="flex flex-col justify-between space-y-4 rounded-2xl border-2 border-[#17171c] bg-white p-5 shadow-2xs sm:p-6"
      >
        <div class="space-y-3">
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-3">
              <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#17171c] text-white"
              >
                <Lock class="h-4 w-4 text-[#10b981]" />
              </div>
              <div>
                <h3 class="text-sm font-bold text-[#17171c]">Owner (Pemilik Usaha)</h3>
                <span class="font-mono text-[10px] font-semibold text-[#10b981]"
                  >Root / Full Access</span
                >
              </div>
            </div>
            <span
              class="rounded-full border border-[#e5e5ea] bg-[#f4f4f6] px-2.5 py-0.5 font-mono text-[10px] font-semibold text-[#17171c] uppercase"
            >
              Sistem
            </span>
          </div>

          <p class="text-xs leading-relaxed text-[#686873]">
            Pemegang akun utama bisnis dengan hak akses tidak terbatas ke seluruh modul, laporan
            keuangan, dan pengaturan workspace.
          </p>

          <div class="flex items-center justify-between border-t border-[#f2f2f4] pt-3 text-xs">
            <div class="flex items-center gap-1.5 text-[11px] font-semibold text-[#17171c]">
              <CheckCircle2 class="h-4 w-4 text-[#10b981]" />
              <span>Semua Izin Aktif (*)</span>
            </div>
            <span class="font-mono text-[10.5px] text-[#8e8e93]">Role Utama</span>
          </div>
        </div>
      </div>

      <!-- Dynamic Custom & System Roles -->
      {#each filteredRoles as role}
        {@const breakdown = getModuleBreakdown(role.permissions)}
        <div
          class="flex flex-col justify-between space-y-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs transition-all hover:border-[#17171c]/40 hover:shadow-xs sm:p-6"
        >
          <!-- Role Header -->
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <h3 class="truncate text-sm font-bold text-[#17171c]">{role.name}</h3>
                <div class="mt-1 flex items-center gap-2">
                  <span
                    class="flex items-center gap-1 rounded-full border border-[#e5e5ea] bg-[#f8f8fa] px-2.5 py-0.5 font-mono text-[11px] text-[#8e8e93]"
                  >
                    <Users class="h-3.5 w-3.5" />
                    {role.members_count} Staf
                  </span>
                </div>
              </div>
            </div>

            <!-- Description -->
            <p class="line-clamp-2 text-xs leading-relaxed text-[#686873]">
              {role.description || 'Tidak ada deskripsi tanggung jawab khusus.'}
            </p>

            <!-- Module Permission Badges -->
            <div class="space-y-1.5 pt-2">
              <div class="font-mono text-[10.5px] font-semibold text-[#8e8e93]">
                Hak Akses ({role.permissions.length} Fitur)
              </div>
              <div class="flex flex-wrap gap-1.5">
                {#if breakdown.length > 0}
                  {#each breakdown as mod}
                    <span
                      class="rounded-lg border border-[#ececee] bg-[#f8f8fa] px-2.5 py-1 text-[10.5px] font-medium text-[#17171c]"
                    >
                      {mod.name} ({mod.count})
                    </span>
                  {/each}
                {:else}
                  <span class="text-xs text-[#8e8e93] italic">Belum ada izin modul</span>
                {/if}
              </div>
            </div>
          </div>

          <!-- Actions Footer -->
          <div class="flex items-center justify-end border-t border-[#f2f2f4] pt-3">
            <div class="flex items-center gap-2">
              <button
                type="button"
                onclick={() => handleOpenEdit(role)}
                class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-3 py-1.5 text-xs font-semibold text-[#17171c] transition-all hover:bg-[#eeece7]"
              >
                <Edit2 class="h-3.5 w-3.5" />
                <span>Edit</span>
              </button>

              <button
                type="button"
                onclick={() => handleConfirmDelete(role)}
                class="cursor-pointer rounded-xl border border-[#e5e5ea] p-2 text-[#8e8e93] transition-all hover:border-[#fecaca] hover:bg-[#fef2f2] hover:text-[#e5484d]"
                title="Hapus Role"
              >
                <Trash2 class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<!-- Modal: Add or Edit Role -->
<AddEditRoleModal
  isOpen={isAddEditModalOpen}
  roleToEdit={selectedRoleForEdit}
  {catalog}
  onClose={() => (isAddEditModalOpen = false)}
  onSuccess={async () => {
    await onRefresh();
  }}
/>

<!-- Delete Role Confirmation Modal -->
{#if roleToDelete}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-sm space-y-4 rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl"
    >
      <div
        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#fef2f2] text-[#e5484d]"
      >
        <Trash2 class="h-6 w-6" />
      </div>

      <div class="space-y-1 text-center">
        <h3 class="text-base font-bold text-[#17171c]">Hapus Role "{roleToDelete.name}"?</h3>
        <p class="text-xs text-[#8e8e93]">
          Role ini akan dihapus dari workspace. Pastikan tidak ada staf yang masih menggunakan role
          ini.
        </p>
      </div>

      {#if deleteError}
        <div
          class="flex items-center gap-2 rounded-xl border border-[#fecaca] bg-[#fef2f2] p-3 text-xs text-[#991b1b]"
        >
          <AlertCircle class="h-4 w-4 shrink-0" />
          <span>{deleteError}</span>
        </div>
      {/if}

      <div class="flex items-center gap-2 pt-2">
        <button
          type="button"
          onclick={() => (roleToDelete = null)}
          class="flex-1 cursor-pointer rounded-xl border border-[#e5e5ea] py-2.5 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isDeletingRole}
          onclick={executeDeleteRole}
          class="flex-1 cursor-pointer rounded-xl bg-[#e5484d] py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-[#dc2626] disabled:opacity-50"
        >
          {isDeletingRole ? 'Menghapus...' : 'Hapus'}
        </button>
      </div>
    </div>
  </div>
{/if}
