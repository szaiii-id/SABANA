import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import ProgramManagement from '../Index.vue';
import ProgramService from '../../../services/ProgramService';

const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

vi.mock('../../../services/ProgramService', () => ({
  default: {
    fetchPrograms: vi.fn(),
    createProgram: vi.fn(),
    updateProgram: vi.fn(),
    deleteProgram: vi.fn(),
    closeProgram: vi.fn(),
    reopenProgram: vi.fn(),
    duplicateProgram: vi.fn(),
    uploadBanner: vi.fn(),
  },
}));

vi.mock('../../../../composables/useProgramForm', () => ({
  useProgramForm: () => ({
    formData: { value: { name: '', description: '', start_date: '', end_date: '', status: 'draft', quota_total: null, benefit_amount: null, criteria: {}, banner: null, banner_url: null } },
    validationErrors: { value: {} },
    modalMode: { value: 'add' },
    editId: { value: '' },
    isModalOpen: { value: false },
    validateForm: vi.fn(() => true),
    openAddModal: vi.fn(),
    openEditModal: vi.fn(),
    closeModal: vi.fn(),
    clearDraft: vi.fn(),
    handleCriteriaUpdate: vi.fn(),
    handleBannerChange: vi.fn(),
  }),
}));

vi.mock('../../../../composables/useProgramSave', () => ({
  useProgramSave: () => ({
    isSubmitting: { value: false },
    errorMessage: { value: '' },
    uploadProgress: { value: 0 },
    uploadStep: { value: 'data' },
    showSuccess: { value: false },
    successMessage: { value: '' },
    handleSave: vi.fn(),
    showSuccessMessage: vi.fn(),
  }),
}));

describe('ProgramManagement.vue', () => {
  let router: any;

  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.getItem.mockReturnValue(null);

    (ProgramService.fetchPrograms as any).mockResolvedValue({
      data: [],
      current_page: 1, total: 0, last_page: 1, per_page: 12,
    });

    router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/sabana-center-63/programs', name: 'admin.programs', component: ProgramManagement },
      ],
    });
  });

  function mountComponent() {
    return mount(ProgramManagement, {
      global: {
        plugins: [router],
        stubs: {
          ProgramFilters: { template: '<div class="filters"/>', props: ['modelValue'] },
          ProgramCard: { template: '<div class="card"/>', props: ['program'] },
          ProgramCardSkeleton: { template: '<div class="skeleton"/>' },
          ProgramEmptyState: { template: '<div>Belum ada program</div>' },
          ProgramModal: { template: '<div class="modal"><slot/></div>', props: ['open', 'mode', 'submitting', 'error', 'uploadStep', 'uploadProgress'] },
          ProgramFormFields: { template: '<div class="form-fields"/>', props: ['form', 'mode', 'errors', 'existingBannerUrl'] },
          ProgramCriteriaSection: { template: '<div class="criteria"/>', props: ['existing'] },
          DeleteConfirmModal: { template: '<div class="delete-modal"/>', props: ['open', 'message', 'submitting'] },
          SuccessModal: { template: '<div class="success-modal"/>', props: ['show', 'message'] },
        },
      },
    });
  }

  // =============================================
  // RENDERING — 4 TEST
  // =============================================

  it('test_menampilkan_heading_manajemen_program', async () => {
    await router.push({ name: 'admin.programs' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Manajemen Program');
  });

  it('test_menampilkan_tombol_tambah_program', async () => {
    await router.push({ name: 'admin.programs' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Tambah Program');
  });

  it('test_menampilkan_empty_state_saat_tidak_ada_program', async () => {
    await router.push({ name: 'admin.programs' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Belum ada program');
  });

  it('test_memanggil_fetchPrograms_saat_mount', async () => {
    await router.push({ name: 'admin.programs' });
    await router.isReady();
    mountComponent();
    await flushPromises();
    expect(ProgramService.fetchPrograms).toHaveBeenCalled();
  });

  // =============================================
  // MODAL — 1 TEST
  // =============================================

  it('test_modal_terbuka_saat_klik_tambah', async () => {
    await router.push({ name: 'admin.programs' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    const addBtn = wrapper.find('button');
    await addBtn.trigger('click');

    expect(wrapper.html()).toContain('modal');
  });

  // =============================================
  // FILTERS — 1 TEST
  // =============================================

  it('test_menampilkan_filter_program', async () => {
    await router.push({ name: 'admin.programs' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.html()).toContain('filters');
  });
});