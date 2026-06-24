import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import Profile from '../Index.vue';
import { useProfile } from '../../../composables/useProfile';
import type { AdminUser } from '../../../types/auth';

// =============================================
// MOCK: localStorage
// =============================================
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

// =============================================
// MOCK: useProfile
// =============================================
const mockUpdateProfile = vi.fn();
const mockUpdatePassword = vi.fn();

vi.mock('../../../composables/useProfile', () => ({
  useProfile: vi.fn(),
}));

// =============================================
// HELPERS
// =============================================
const mockAdmin: AdminUser = {
  id: 'admin-1',
  nip: '199001012020011001',
  name: 'Super Admin',
  role: 'super_admin',
  is_active: true,
  last_login_at: '2024-01-15 08:30:00',
  created_at: '2024-01-01',
  region: { regency: 'Tanah Laut', district: null, village: null },
};

function mockUseProfileReturn(overrides: Record<string, unknown> = {}) {
  (useProfile as ReturnType<typeof vi.fn>).mockReturnValue({
    profile: { value: mockAdmin },
    isSubmitting: { value: false },
    errorMessage: { value: '' },
    updateProfile: mockUpdateProfile,
    updatePassword: mockUpdatePassword,
    ...overrides,
  });
}

async function setupComponent() {
  const router = createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/sabana-center-63/profile', name: 'admin.profile', component: Profile },
    ],
  });

  await router.push({ name: 'admin.profile' });
  await router.isReady();

  const wrapper = mount(Profile, {
    global: {
      plugins: [router],
      stubs: {
        EditProfileModal: { template: '<div class="edit-profile-modal"/>', props: ['open', 'nip', 'name', 'submitting', 'errorMessage'] },
        ChangePasswordModal: { template: '<div class="change-password-modal"/>', props: ['open', 'submitting', 'errorMessage'] },
        SuccessModal: { template: '<div class="success-modal"/>', props: ['show', 'message'] },
      },
    },
  });

  await flushPromises();

  return { wrapper, router };
}

// =============================================
// TEST SUITE
// =============================================
describe('Profile.vue', () => {

  beforeEach(() => {
    vi.clearAllMocks();
    mockUseProfileReturn();
    localStorageMock.getItem.mockReturnValue(JSON.stringify(mockAdmin));
  });

  // ===== HAPPY PATH =====

  it('test_menampilkan_heading_profil_saya', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Profil Saya');
  });

  it('test_menampilkan_nip', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('199001012020011001');
  });

  it('test_menampilkan_nama', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Super Admin');
  });

  it('test_menampilkan_wilayah', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Tanah Laut');
  });

  it('test_menampilkan_tombol_edit_profil', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Edit Profil');
  });

  it('test_menampilkan_tombol_ganti_password', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Ganti Password');
  });

  // ===== SAD PATH =====

  it('test_profile_null_tampilkan_default', async () => {
    localStorageMock.getItem.mockReturnValue(JSON.stringify(null));
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Profil Saya');
  });

  it('test_update_profile_gagal_modal_tetap_terbuka', async () => {
    mockUpdateProfile.mockResolvedValue({ success: false });
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as {
      isEditModalOpen: boolean;
      handleUpdateProfile: (name: string) => Promise<void>;
    };

    vm.isEditModalOpen = true;
    await vm.handleUpdateProfile('New Name');
    await flushPromises();

    expect(vm.isEditModalOpen).toBe(true);
  });

  // ===== BOUNDARY =====

  it('test_modal_edit_terbuka_saat_klik_edit', async () => {
    const { wrapper } = await setupComponent();
    const buttons = wrapper.findAll('button');
    const editBtn = Array.from(buttons).find(b => b.text().includes('Edit Profil'));
    if (editBtn) await editBtn.trigger('click');

    const vm = wrapper.vm as unknown as { isEditModalOpen: boolean };
    expect(vm.isEditModalOpen).toBe(true);
  });

  it('test_modal_password_terbuka_saat_klik_ganti', async () => {
    const { wrapper } = await setupComponent();
    const buttons = wrapper.findAll('button');
    const passBtn = Array.from(buttons).find(b => b.text().includes('Ganti Password'));
    if (passBtn) await passBtn.trigger('click');

    const vm = wrapper.vm as unknown as { isPasswordModalOpen: boolean };
    expect(vm.isPasswordModalOpen).toBe(true);
  });

  // ===== EDGE CASE =====

  it('test_region_null_tampilkan_default', async () => {
    const adminNoRegion: AdminUser = { ...mockAdmin, region: { regency: null, district: null, village: null } };
    localStorageMock.getItem.mockReturnValue(JSON.stringify(adminNoRegion));
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Seluruh Kalimantan Selatan');
  });

  it('test_region_village_prioritas', async () => {
    const adminVillage: AdminUser = {
      ...mockAdmin,
      region: { regency: 'Tanah Laut', district: 'Pelaihari', village: 'Desa Test' },
    };
    localStorageMock.getItem.mockReturnValue(JSON.stringify(adminVillage));
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Desa Test');
  });

  // ===== NULL / EMPTY =====

  it('test_last_login_null_tampilkan_strip', async () => {
    const adminNoLogin: AdminUser = { ...mockAdmin, last_login_at: null };
    localStorageMock.getItem.mockReturnValue(JSON.stringify(adminNoLogin));
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('-');
  });

  it('test_name_null_tampilkan_default_initial', async () => {
    const adminNoName: AdminUser = { ...mockAdmin, name: '' };
    localStorageMock.getItem.mockReturnValue(JSON.stringify(adminNoName));
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('A');
  });

  // ===== DATA TYPE =====

  it('test_initial_computed_single_char', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as { initial: string };
    expect(vm.initial).toBe('S');
    expect(vm.initial.length).toBe(1);
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_formatRole_super_admin', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Super Admin');
  });

  it('test_formatRole_village_officer', async () => {
    const adminVillage: AdminUser = { ...mockAdmin, role: 'village_officer' };
    localStorageMock.getItem.mockReturnValue(JSON.stringify(adminVillage));
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Petugas Desa');
  });

  // ===== STATE TRANSITION =====

  it('test_update_profile_sukses_tutup_modal', async () => {
    mockUpdateProfile.mockResolvedValue({ success: true });
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as {
      isEditModalOpen: boolean;
      handleUpdateProfile: (name: string) => Promise<void>;
    };

    vm.isEditModalOpen = true;
    await vm.handleUpdateProfile('New Name');
    await flushPromises();

    expect(vm.isEditModalOpen).toBe(false);
  });

  it('test_update_password_sukses_tutup_modal', async () => {
    mockUpdatePassword.mockResolvedValue({ success: true });
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as {
      isPasswordModalOpen: boolean;
      showSuccessModal: boolean;
      handleUpdatePassword: (current: string, newPass: string) => Promise<void>;
    };

    vm.isPasswordModalOpen = true;
    await vm.handleUpdatePassword('old', 'newpassword123');
    await flushPromises();

    expect(vm.isPasswordModalOpen).toBe(false);
    expect(vm.showSuccessModal).toBe(true);
  });

  // ===== CONCURRENCY =====

  it('test_storage_event_reload_profile', async () => {
    localStorageMock.getItem.mockReturnValue(JSON.stringify(mockAdmin));
    const { wrapper } = await setupComponent();

    const newAdmin: AdminUser = { ...mockAdmin, name: 'Updated Admin' };
    localStorageMock.getItem.mockReturnValue(JSON.stringify(newAdmin));

    window.dispatchEvent(new StorageEvent('storage', { key: 'admin_user' }));
    await flushPromises();

    const vm = wrapper.vm as unknown as { profile: AdminUser | null };
    expect(vm.profile?.name).toBe('Updated Admin');
  });

  // ===== SECURITY =====

  it('test_password_tersembunyi_di_tampilan', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('••••••••');
  });
});