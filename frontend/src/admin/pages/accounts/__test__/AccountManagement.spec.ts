import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import AccountManagement from '../Index.vue';
import AccountService from '../../../services/AccountService';

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
// MOCK: AccountService
// =============================================
vi.mock('../../../services/AccountService', () => ({
  default: {
    fetchAccounts: vi.fn(),
    createAccount: vi.fn(),
    updateAccount: vi.fn(),
    deleteAccount: vi.fn(),
    activateAccount: vi.fn(),
    resetPassword: vi.fn(),
  },
}));

// =============================================
// MOCK: useRegion
// =============================================
vi.mock('../../../composables/useRegion', () => ({
  useRegion: () => ({
    regencies: { value: [] },
    districts: { value: [] },
    villages: { value: [] },
    isRegionLoading: { value: false },
    fetchRegencies: vi.fn(),
    fetchDistricts: vi.fn(),
    fetchVillages: vi.fn(),
    clearRegions: vi.fn(),
  }),
}));

// =============================================
// HELPERS
// =============================================
interface AccountManagementInstance {
  accounts: Array<Record<string, unknown>>;
  loading: boolean;
  isSubmitting: boolean;
  errorMessage: string;
  isModalOpen: boolean;
  modalMode: 'add' | 'edit';
  editId: string;
  isDeleteModalOpen: boolean;
  deleteTarget: Record<string, unknown> | null;
  deleteMessage: string;
  isResetModalOpen: boolean;
  resetTarget: Record<string, unknown> | null;
  resetError: string;
  showSuccessModal: boolean;
  successModalMessage: string;
  validationErrors: Record<string, string>;
  pagination: { currentPage: number; total: number; totalPages: number; perPage: number };
  formData: Record<string, unknown>;
  filters: { search: string; role: string; is_active: string };
  currentUser: Record<string, unknown> | null;
  fetchData: () => Promise<void>;
  changePage: (page: number) => void;
  openAddModal: () => void;
  openEditModal: (account: Record<string, unknown>) => void;
  closeModal: () => void;
  validateForm: () => boolean;
  confirmDelete: (account: Record<string, unknown>) => void;
  executeDelete: () => Promise<void>;
  handleActivate: (account: Record<string, unknown>) => Promise<void>;
  openResetModal: (account: Record<string, unknown>) => void;
  handleResetPassword: (password: string) => Promise<void>;
  handleSave: () => Promise<void>;
  debounceSearch: () => void;
  resetRegions: () => void;
  handleRegencyChange: (val: string) => Promise<void>;
  handleDistrictChange: (val: string) => Promise<void>;
  showSuccessModalMessage: (message: string) => void;
  saveData: () => Promise<void>;
}

const mockAccount = {
  id: '2',
  nip: '199001012020011002',
  name: 'Admin Kab',
  role: 'regency_admin',
  is_active: true,
  regency_id: '6301',
  district_id: '',
  village_id: '',
  region: { regency: 'Tanah Laut', district: null, village: null },
};

async function setupComponent() {
  const router = createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/sabana-center-63/accounts', name: 'admin.accounts', component: AccountManagement },
    ],
  });

  await router.push({ name: 'admin.accounts' });
  await router.isReady();

  const wrapper = mount(AccountManagement, {
    global: {
      plugins: [router],
      stubs: {
        AccountFilters: { template: '<div class="filters"/>', props: ['modelValue'] },
        AccountTable: { template: '<div class="table"/>', props: ['accounts', 'loading', 'currentUserId', 'total', 'currentPage', 'totalPages'] },
        AccountModal: { template: '<div class="modal"><slot/></div>', props: ['open', 'mode', 'submitting', 'error'] },
        AccountFormFields: { template: '<div class="form-fields"/>', props: ['form', 'mode', 'errors'] },
        RegionSection: { template: '<div class="region"/>', props: ['regencyId', 'districtId', 'villageId', 'regencies', 'districts', 'villages', 'showDistrict', 'showVillage'] },
        DeleteConfirmModal: { template: '<div class="delete-modal"/>', props: ['open', 'message', 'submitting'] },
        ResetPasswordModal: { template: '<div class="reset-modal"/>', props: ['open', 'targetName', 'targetNip', 'submitting', 'serverError'] },
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
describe('AccountManagement.vue', () => {

  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.getItem.mockReturnValue(JSON.stringify({
      id: 'admin-1', nip: '199001012020011001', name: 'Super Admin',
      role: 'super_admin', is_active: true,
    }));

    (AccountService.fetchAccounts as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: [],
      current_page: 1, total: 0, last_page: 1, per_page: 15,
    });
  });

  afterEach(() => {
    vi.clearAllTimers();
  });

  // ===== HAPPY PATH =====

  it('test_menampilkan_heading_manajemen_akun', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Manajemen Akun');
  });

  it('test_menampilkan_tombol_tambah_pegawai', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Tambah Pegawai');
  });

  it('test_memanggil_fetchAccounts_saat_mount', async () => {
    await setupComponent();
    expect(AccountService.fetchAccounts).toHaveBeenCalled();
  });

  it('test_modal_terbuka_saat_klik_tambah', async () => {
    const { wrapper } = await setupComponent();
    const addBtn = wrapper.find('button');
    await addBtn.trigger('click');

    const vm = wrapper.vm as unknown as AccountManagementInstance;
    expect(vm.isModalOpen).toBe(true);
    expect(vm.modalMode).toBe('add');
  });

  it('test_create_account_sukses', async () => {
    (AccountService.createAccount as ReturnType<typeof vi.fn>).mockResolvedValue({});
    (AccountService.fetchAccounts as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: [mockAccount], current_page: 1, total: 1, last_page: 1, per_page: 15,
    });

    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.modalMode = 'add';
    vm.formData = { nip: '199001012020011002', name: 'Admin Baru', password: 'password123', role: 'village_officer', regency_id: '6301', district_id: '6301020', village_id: '6301020001', is_active: true };
    await vm.saveData();
    await flushPromises();

    expect(AccountService.createAccount).toHaveBeenCalled();
  });

  it('test_delete_account_sukses', async () => {
    (AccountService.deleteAccount as ReturnType<typeof vi.fn>).mockResolvedValue({});
    (AccountService.fetchAccounts as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: [], current_page: 1, total: 0, last_page: 1, per_page: 15,
    });

    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.deleteTarget = mockAccount;
    await vm.executeDelete();
    await flushPromises();

    expect(AccountService.deleteAccount).toHaveBeenCalledWith('2');
    expect(vm.isDeleteModalOpen).toBe(false);
  });

  // ===== SAD PATH =====

  it('test_fetch_gagal_tampilkan_error', async () => {
    (AccountService.fetchAccounts as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Network Error'));
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    expect(vm.errorMessage).toBe('Gagal memuat data akun. Silakan coba lagi.');
  });

  it('test_create_account_gagal_403', async () => {
    (AccountService.createAccount as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { status: 403, data: { message: 'Anda tidak memiliki wewenang.' } },
    });

    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.modalMode = 'add';
    vm.formData = { nip: '199001012020011002', name: 'Test', password: 'password123', role: 'district_admin', regency_id: '6301', district_id: '6301020', village_id: '', is_active: true };
    await vm.saveData();
    await flushPromises();

    expect(vm.errorMessage).toBe('Anda tidak memiliki wewenang.');
  });

it('test_delete_account_gagal_404', async () => {
    (AccountService.deleteAccount as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { status: 404 },
    });

    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.deleteTarget = mockAccount;
    await vm.executeDelete();
    await flushPromises();

    expect(vm.errorMessage).toBe('Gagal menonaktifkan akun.'); // ← fallback, bukan 404
});

  // ===== BOUNDARY =====

  it('test_validasi_nip_tepat_18_digit_lolos', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.modalMode = 'add';
    vm.formData = { nip: '199001012020011002', name: 'Test', password: 'password123', role: 'village_officer', regency_id: '6301', district_id: '6301020', village_id: '6301020001' };
    const isValid = vm.validateForm();

    expect(isValid).toBe(true);
  });

  it('test_validasi_password_tepat_8_karakter_lolos', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.modalMode = 'add';
    vm.formData = { nip: '199001012020011002', name: 'Test', password: '12345678', role: 'village_officer', regency_id: '6301', district_id: '6301020', village_id: '6301020001' };
    const isValid = vm.validateForm();

    expect(isValid).toBe(true);
  });

  // ===== EDGE CASE =====

  it('test_open_edit_modal_isi_form', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.openEditModal(mockAccount);
    await flushPromises();

    expect(vm.modalMode).toBe('edit');
    expect(vm.formData.name).toBe('Admin Kab');
    expect(vm.isModalOpen).toBe(true);
  });

  it('test_close_modal_reset_errors', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.validationErrors = { nip: 'Error', name: '', password: '' };
    vm.closeModal();

    expect(vm.validationErrors.nip).toBe('');
    expect(vm.isModalOpen).toBe(false);
  });

  // ===== NULL / EMPTY =====

  it('test_validasi_nip_kosong', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.modalMode = 'add';
    vm.formData.nip = '';
    const isValid = vm.validateForm();

    expect(isValid).toBe(false);
    expect(vm.validationErrors.nip).toBe('NIP wajib diisi.');
  });

  it('test_validasi_name_kosong', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.formData.name = '';
    const isValid = vm.validateForm();

    expect(isValid).toBe(false);
    expect(vm.validationErrors.name).toBe('Nama lengkap wajib diisi.');
  });

  it('test_empty_accounts_list', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    expect(vm.accounts.length).toBe(0);
  });

  // ===== DATA TYPE =====

  it('test_formData_role_default_string', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    expect(typeof vm.formData.role).toBe('string');
    expect(vm.formData.role).toBe('village_officer');
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_validasi_nip_kurang_18_digit', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.modalMode = 'add';
    vm.formData.nip = '12345';
    const isValid = vm.validateForm();

    expect(isValid).toBe(false);
    expect(vm.validationErrors.nip).toBe('NIP harus 18 digit.');
  });

  it('test_validasi_password_kurang_8_karakter', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.modalMode = 'add';
    vm.formData.password = '1234567';
    const isValid = vm.validateForm();

    expect(isValid).toBe(false);
    expect(vm.validationErrors.password).toBe('Kata sandi minimal 8 karakter.');
  });

  // ===== STATE TRANSITION =====

  it('test_confirm_delete_buka_modal', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    vm.confirmDelete(mockAccount);

    expect(vm.isDeleteModalOpen).toBe(true);
    expect(vm.deleteTarget).toEqual(mockAccount);
  });

  it('test_handleActivate_panggil_API', async () => {
    (AccountService.activateAccount as ReturnType<typeof vi.fn>).mockResolvedValue({});
    (AccountService.fetchAccounts as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: [], current_page: 1, total: 0, last_page: 1, per_page: 15,
    });

    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    await vm.handleActivate(mockAccount);
    await flushPromises();

    expect(AccountService.activateAccount).toHaveBeenCalledWith('2');
  });

  // ===== CONCURRENCY =====

  it('test_debounce_search_cancel_previous', async () => {
    vi.useFakeTimers();
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;
    const clearTimeoutSpy = vi.spyOn(window, 'clearTimeout');

    vm.debounceSearch();
    vm.debounceSearch();

    expect(clearTimeoutSpy).toHaveBeenCalled();
    vi.useRealTimers();
  });

  // ===== SECURITY =====

  it('test_current_user_dari_localStorage', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as AccountManagementInstance;

    expect(vm.currentUser).not.toBeNull();
    expect(vm.currentUser?.role).toBe('super_admin');
  });

  it('test_handleStorageChange_trigger_fetch', async () => {
      const { wrapper } = await setupComponent();
      const vm = wrapper.vm as unknown as AccountManagementInstance;

      // Reset mock & spy
      (AccountService.fetchAccounts as ReturnType<typeof vi.fn>).mockClear();
      
      window.dispatchEvent(new StorageEvent('storage', { key: 'admin_token' }));
      await flushPromises();

      expect(AccountService.fetchAccounts).toHaveBeenCalled();
  });
});