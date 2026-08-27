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
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
    <div class="space-y-1">
      <div class="flex items-center gap-2">
        <h2 class="text-base sm:text-lg font-bold text-[#17171c]">Role &amp; Hak Akses</h2>
        <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-mono font-semibold bg-[#f4f4f6] text-[#17171c]">
          {roles.length + 1} Role Aktif
        </span>
      </div>
      <p class="text-xs text-[#8e8e93] max-w-xl">
        Atur kewenangan fitur untuk staf outlet. Buat role kustom seperti Head Barista atau Akuntan, dan centang fitur yang dapat diakses.
      </p>
    </div>

    <div class="flex items-center gap-3">
      <div class="relative flex-1 sm:w-60">
        <Search class="w-4 h-4 text-[#8e8e93] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari role..."
          class="w-full pl-10 pr-4 py-2 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-full text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
        />
      </div>

      <button
        type="button"
        onclick={handleOpenCreate}
        class="px-5 py-2.5 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full flex items-center gap-1.5 cursor-pointer shadow-xs transition-all shrink-0"
      >
        <Plus class="w-4 h-4" />
        <span>Buat Role</span>
      </button>
    </div>
  </div>

  <!-- Role Cards Responsive Grid (1 col mobile, 2 col tablet, 3 col desktop) -->
  {#if isLoading}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      {#each Array(3) as _}
        <div class="bg-white border border-[#e5e5ea] rounded-2xl p-6 space-y-4 animate-pulse">
          <div class="h-5 bg-gray-200 rounded-md w-1/2"></div>
          <div class="h-4 bg-gray-100 rounded-md w-3/4"></div>
          <div class="h-12 bg-gray-50 rounded-xl"></div>
        </div>
      {/each}
    </div>
  {:else if filteredRoles.length === 0 && searchQuery}
    <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-12 text-center space-y-3 shadow-2xs">
      <div class="w-12 h-12 rounded-2xl bg-[#f4f4f6] text-[#8e8e93] flex items-center justify-center mx-auto">
        <Shield class="w-6 h-6" />
      </div>
      <div>
        <h3 class="text-sm font-bold text-[#17171c]">Tidak Ada Role Ditemukan</h3>
        <p class="text-xs text-[#8e8e93] mt-1">
          Tidak ada role yang cocok dengan kata kunci "{searchQuery}".
        </p>
      </div>
    </div>
  {:else}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <!-- Special Protected Owner Card -->
      <div class="bg-white border-2 border-[#17171c] rounded-2xl p-5 sm:p-6 flex flex-col justify-between shadow-2xs space-y-4">
        <div class="space-y-3">
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-[#17171c] text-white flex items-center justify-center shrink-0">
                <Lock class="w-4 h-4 text-[#10b981]" />
              </div>
              <div>
                <h3 class="text-sm font-bold text-[#17171c]">Owner (Pemilik Usaha)</h3>
                <span class="text-[10px] font-mono text-[#10b981] font-semibold">Root / Full Access</span>
              </div>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono uppercase bg-[#f4f4f6] text-[#17171c] font-semibold border border-[#e5e5ea]">
              Sistem
            </span>
          </div>

          <p class="text-xs text-[#686873] leading-relaxed">
            Pemegang akun utama bisnis dengan hak akses tidak terbatas ke seluruh modul, laporan keuangan, dan pengaturan workspace.
          </p>

          <div class="pt-3 border-t border-[#f2f2f4] flex items-center justify-between text-xs">
            <div class="flex items-center gap-1.5 text-[#17171c] font-semibold text-[11px]">
              <CheckCircle2 class="w-4 h-4 text-[#10b981]" />
              <span>Semua Izin Aktif (*)</span>
            </div>
            <span class="text-[10.5px] text-[#8e8e93] font-mono">Role Utama</span>
          </div>
        </div>
      </div>

      <!-- Dynamic Custom & System Roles -->
      {#each filteredRoles as role}
        {@const breakdown = getModuleBreakdown(role.permissions)}
        <div class="bg-white border border-[#e5e5ea] hover:border-[#17171c]/40 rounded-2xl p-5 sm:p-6 flex flex-col justify-between shadow-2xs hover:shadow-xs transition-all space-y-4">
          <!-- Role Header -->
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <h3 class="text-sm font-bold text-[#17171c] truncate">{role.name}</h3>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-[11px] font-mono text-[#8e8e93] flex items-center gap-1 bg-[#f8f8fa] border border-[#e5e5ea] px-2.5 py-0.5 rounded-full">
                    <Users class="w-3.5 h-3.5" />
                    {role.members_count} Staf
                  </span>
                </div>
              </div>
            </div>

            <!-- Description -->
            <p class="text-xs text-[#686873] leading-relaxed line-clamp-2">
              {role.description || 'Tidak ada deskripsi tanggung jawab khusus.'}
            </p>

            <!-- Module Permission Badges -->
            <div class="space-y-1.5 pt-2">
              <div class="text-[10.5px] font-mono font-semibold text-[#8e8e93]">
                Hak Akses ({role.permissions.length} Fitur)
              </div>
              <div class="flex flex-wrap gap-1.5">
                {#if breakdown.length > 0}
                  {#each breakdown as mod}
                    <span class="px-2.5 py-1 rounded-lg text-[10.5px] font-medium bg-[#f8f8fa] border border-[#ececee] text-[#17171c]">
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
          <div class="pt-3 border-t border-[#f2f2f4] flex items-center justify-end">
            <div class="flex items-center gap-2">
              <button
                type="button"
                onclick={() => handleOpenEdit(role)}
                class="px-3 py-1.5 text-xs font-semibold bg-[#f8f8fa] hover:bg-[#eeece7] text-[#17171c] border border-[#e5e5ea] rounded-xl flex items-center gap-1.5 cursor-pointer transition-all"
              >
                <Edit2 class="w-3.5 h-3.5" />
                <span>Edit</span>
              </button>

              <button
                type="button"
                onclick={() => handleConfirmDelete(role)}
                class="p-2 text-[#8e8e93] hover:text-[#e5484d] hover:bg-[#fef2f2] border border-[#e5e5ea] hover:border-[#fecaca] rounded-xl transition-all cursor-pointer"
                title="Hapus Role"
              >
                <Trash2 class="w-3.5 h-3.5" />
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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-sm p-6 space-y-4 shadow-xl animate-in fade-in zoom-in-95">
      <div class="w-12 h-12 rounded-2xl bg-[#fef2f2] text-[#e5484d] flex items-center justify-center mx-auto">
        <Trash2 class="w-6 h-6" />
      </div>

      <div class="text-center space-y-1">
        <h3 class="text-base font-bold text-[#17171c]">Hapus Role "{roleToDelete.name}"?</h3>
        <p class="text-xs text-[#8e8e93]">
          Role ini akan dihapus dari workspace. Pastikan tidak ada staf yang masih menggunakan role ini.
        </p>
      </div>

      {#if deleteError}
        <div class="p-3 bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs rounded-xl flex items-center gap-2">
          <AlertCircle class="w-4 h-4 shrink-0" />
          <span>{deleteError}</span>
        </div>
      {/if}

      <div class="flex items-center gap-2 pt-2">
        <button
          type="button"
          onclick={() => (roleToDelete = null)}
          class="flex-1 py-2.5 text-xs font-semibold text-[#686873] hover:bg-[#f4f4f6] rounded-xl cursor-pointer transition-all border border-[#e5e5ea]"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isDeletingRole}
          onclick={executeDeleteRole}
          class="flex-1 py-2.5 text-xs font-semibold bg-[#e5484d] hover:bg-[#dc2626] text-white rounded-xl cursor-pointer transition-all shadow-xs disabled:opacity-50"
        >
          {isDeletingRole ? 'Menghapus...' : 'Hapus'}
        </button>
      </div>
    </div>
  </div>
{/if}
