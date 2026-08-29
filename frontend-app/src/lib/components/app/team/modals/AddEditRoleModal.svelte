<script lang="ts">
  import {
    X,
    ShieldCheck,
    AlertTriangle,
    Check,
    Utensils,
    Package,
    Calendar,
    Wallet,
    Users,
    Store,
    Sliders,
  } from 'lucide-svelte';
  import type { WorkspaceRole, PermissionsCatalog, PermissionModule } from '../../../../types/app';
  import { roleService } from '../../../../services/role-service';

  interface Props {
    isOpen: boolean;
    roleToEdit?: WorkspaceRole | null;
    catalog: PermissionsCatalog | null;
    onClose: () => void;
    onSuccess: () => void;
  }

  let { isOpen, roleToEdit = null, catalog, onClose, onSuccess }: Props = $props();

  let isSubmitting = $state(false);
  let errorMessage = $state<string | null>(null);

  let formName = $state('');
  let formDescription = $state('');
  let selectedPermissions = $state<string[]>([]);

  // Icon map for modules
  function getModuleIcon(moduleId: string) {
    switch (moduleId) {
      case 'katalog':
        return Utensils;
      case 'inventaris':
        return Package;
      case 'operasional':
        return Calendar;
      case 'keuangan':
        return Wallet;
      case 'tim':
        return Users;
      case 'pos':
        return Store;
      default:
        return Sliders;
    }
  }

  $effect(() => {
    if (isOpen) {
      errorMessage = null;
      if (roleToEdit) {
        formName = roleToEdit.name;
        formDescription = roleToEdit.description || '';
        selectedPermissions = [...roleToEdit.permissions];
      } else {
        formName = '';
        formDescription = '';
        selectedPermissions = [];
      }
    }
  });

  let allModules = $derived<PermissionModule[]>(catalog ? Object.values(catalog.modules) : []);

  let totalPermissionsCount = $derived(
    allModules.reduce((acc, m) => acc + Object.keys(m.permissions).length, 0)
  );

  function isPermissionChecked(slug: string): boolean {
    return selectedPermissions.includes(slug);
  }

  function togglePermission(slug: string) {
    if (selectedPermissions.includes(slug)) {
      selectedPermissions = selectedPermissions.filter((p) => p !== slug);
    } else {
      selectedPermissions = [...selectedPermissions, slug];
    }
  }

  function toggleModuleAll(mod: PermissionModule) {
    const modPermKeys = Object.keys(mod.permissions);
    const allChecked = modPermKeys.every((k) => selectedPermissions.includes(k));

    if (allChecked) {
      // Uncheck all
      selectedPermissions = selectedPermissions.filter((p) => !modPermKeys.includes(p));
    } else {
      // Check all
      const toAdd = modPermKeys.filter((k) => !selectedPermissions.includes(k));
      selectedPermissions = [...selectedPermissions, ...toAdd];
    }
  }

  function isModuleAllChecked(mod: PermissionModule): boolean {
    const keys = Object.keys(mod.permissions);
    return keys.length > 0 && keys.every((k) => selectedPermissions.includes(k));
  }

  function isModulePartialChecked(mod: PermissionModule): boolean {
    const keys = Object.keys(mod.permissions);
    const checkedCount = keys.filter((k) => selectedPermissions.includes(k)).length;
    return checkedCount > 0 && checkedCount < keys.length;
  }

  async function handleSubmit() {
    if (!formName.trim()) {
      errorMessage = 'Nama role wajib diisi.';
      return;
    }

    if (selectedPermissions.length === 0) {
      errorMessage = 'Pilih minimal satu hak akses fitur untuk role ini.';
      return;
    }

    isSubmitting = true;
    errorMessage = null;

    try {
      if (roleToEdit) {
        await roleService.updateRole(roleToEdit.id, {
          name: formName.trim(),
          description: formDescription.trim(),
          permissions: selectedPermissions,
        });
      } else {
        await roleService.createRole({
          name: formName.trim(),
          description: formDescription.trim(),
          permissions: selectedPermissions,
        });
      }

      onSuccess();
      onClose();
    } catch (err: unknown) {
      errorMessage = err instanceof Error ? err.message : 'Gagal menyimpan role.';
    } finally {
      isSubmitting = false;
    }
  }
</script>

{#if isOpen}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-[#e5e5ea] bg-white shadow-2xl"
    >
      <!-- Modal Header -->
      <div
        class="flex shrink-0 items-center justify-between border-b border-[#f2f2f4] bg-[#fafafc] px-6 py-5"
      >
        <div class="flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-2xl bg-[#17171c] text-white shadow-xs"
          >
            <ShieldCheck class="size-5 text-[#10b981]" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">
              {roleToEdit ? 'Ubah Akses Role' : 'Buat Role Baru'}
            </h3>
            <p class="text-xs text-[#8e8e93]">
              {roleToEdit
                ? `Sesuaikan checklist akses untuk role "${roleToEdit.name}"`
                : 'Tentukan kewenangan fitur untuk role baru'}
            </p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
        >
          <X class="size-5" />
        </button>
      </div>

      <!-- Modal Body (Scrollable) -->
      <div class="flex-1 space-y-6 overflow-y-auto p-5 sm:p-7">
        {#if errorMessage}
          <div
            class="flex items-center gap-2 rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-medium text-[#991b1b]"
          >
            <AlertTriangle class="size-4 shrink-0" />
            <span>{errorMessage}</span>
          </div>
        {/if}

        <!-- Role Basic Info Form -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div class="space-y-1.5">
            <label
              for="role-name-input"
              class="flex items-center gap-1 text-xs font-bold text-[#17171c]"
            >
              Nama Role <span class="text-red-500">*</span>
            </label>
            <input
              id="role-name-input"
              type="text"
              bind:value={formName}
              placeholder="Contoh: Head Barista, Supervisor Shift"
              disabled={roleToEdit?.is_system}
              class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden disabled:bg-[#f5f5f7] disabled:text-[#8e8e93]"
            />
            {#if roleToEdit?.is_system}
              <p class="text-[11px] text-[#8e8e93]">Nama role bawaan sistem dilindungi.</p>
            {/if}
          </div>

          <div class="space-y-1.5">
            <label for="role-desc-input" class="text-xs font-bold text-[#17171c]">
              Deskripsi Role
            </label>
            <input
              id="role-desc-input"
              type="text"
              bind:value={formDescription}
              placeholder="Ringkasan tugas utama role ini"
              class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
        </div>

        <!-- Grouped Permissions Checkbox Matrix -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold tracking-wider text-[#8e8e93] uppercase">
              Checklist Hak Akses Modul ({selectedPermissions.length}/{totalPermissionsCount} Terpilih)
            </h4>
          </div>

          <div class="space-y-4">
            {#each allModules as mod}
              {@const IconComp = getModuleIcon(mod.id)}
              {@const isAll = isModuleAllChecked(mod)}
              {@const isPartial = isModulePartialChecked(mod)}
              <div class="overflow-hidden rounded-2xl border border-[#e5e5ea] bg-white shadow-2xs">
                <!-- Module Header -->
                <div
                  class="flex items-center justify-between border-b border-[#f0f0f4] bg-[#fafafc] px-5 py-3.5"
                >
                  <div class="flex min-w-0 items-center gap-3">
                    <div
                      class="flex size-8 shrink-0 items-center justify-center rounded-xl border border-[#e5e5ea] bg-white"
                    >
                      <IconComp class="size-4 text-[#17171c]" />
                    </div>
                    <div class="min-w-0">
                      <div class="text-xs font-bold text-[#17171c]">{mod.name}</div>
                      <div class="truncate text-[11px] text-[#8e8e93]">{mod.description}</div>
                    </div>
                  </div>

                  <button
                    type="button"
                    onclick={() => toggleModuleAll(mod)}
                    class={`shrink-0 cursor-pointer rounded-full border px-3 py-1 text-xs font-semibold transition-all ${
                      isAll
                        ? 'border-[#a7f3d0] bg-[#ecfdf5] text-[#059669]'
                        : isPartial
                          ? 'border-[#fef3c7] bg-[#fffbeb] text-[#d97706]'
                          : 'border-[#e5e5ea] bg-white text-[#686873] hover:border-[#17171c]'
                    }`}
                  >
                    {isAll ? 'Semua Aktif' : isPartial ? 'Sebagian' : 'Pilih Semua'}
                  </button>
                </div>

                <!-- Permission Items -->
                <div class="divide-y divide-[#f2f2f4] p-1.5">
                  {#each Object.entries(mod.permissions) as [permSlug, permItem]}
                    {@const isChecked = isPermissionChecked(permSlug)}
                    <label
                      class={`flex cursor-pointer items-start gap-3.5 rounded-xl p-3 transition-all select-none ${
                        isChecked ? 'bg-[#fafafc]' : 'hover:bg-[#f9f9fb]'
                      }`}
                    >
                      <!-- Custom Checkbox -->
                      <div class="shrink-0 pt-0.5">
                        <input
                          type="checkbox"
                          checked={isChecked}
                          onchange={() => togglePermission(permSlug)}
                          class="sr-only"
                        />
                        <div
                          class={`flex size-4 items-center justify-center rounded-md transition-all ${
                            isChecked
                              ? 'bg-[#17171c] text-white'
                              : 'border border-[#d1d1d6] bg-white hover:border-[#17171c]'
                          }`}
                        >
                          {#if isChecked}
                            <Check class="size-3 stroke-3" />
                          {/if}
                        </div>
                      </div>

                      <!-- Permission Info -->
                      <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                          <span class="text-xs font-bold text-[#17171c]">{permItem.name}</span>
                          {#if permItem.is_high_risk}
                            <span
                              class="inline-flex items-center gap-1 rounded-full bg-[#fee2e2] px-2 py-0.5 text-[9.5px] font-bold text-[#991b1b] uppercase"
                            >
                              <AlertTriangle class="size-2.5" />
                              Sensitif
                            </span>
                          {/if}
                        </div>
                        <p class="mt-0.5 text-xs leading-relaxed text-[#686873]">
                          {permItem.description}
                        </p>
                      </div>
                    </label>
                  {/each}
                </div>
              </div>
            {/each}
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div
        class="flex shrink-0 items-center justify-between border-t border-[#f2f2f4] bg-[#fafafc] px-6 py-4"
      >
        <div class="font-mono text-xs text-[#8e8e93]">
          <strong class="font-bold text-[#17171c]">{selectedPermissions.length}</strong> izin dipilih
        </div>
        <div class="flex gap-2.5">
          <button
            type="button"
            onclick={onClose}
            class="cursor-pointer rounded-full border border-[#e5e5ea] px-4 py-2.5 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
          >
            Batal
          </button>
          <button
            type="button"
            onclick={handleSubmit}
            disabled={isSubmitting}
            class="flex cursor-pointer items-center gap-1.5 rounded-full bg-[#17171c] px-6 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
          >
            {#if isSubmitting}
              <span>Menyimpan...</span>
            {:else}
              <Check class="size-4" />
              <span>{roleToEdit ? 'Simpan Perubahan' : 'Buat Role'}</span>
            {/if}
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}
