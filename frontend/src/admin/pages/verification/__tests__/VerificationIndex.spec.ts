import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import VerificationIndex from '../Index.vue';
import VerificationService from '../../../services/VerificationService';

const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

vi.mock('../../../services/VerificationService', () => ({
  default: {
    fetchVerifications: vi.fn(),
    approveVerification: vi.fn(),
    rejectVerification: vi.fn(),
    requestRevision: vi.fn(),
    completeVerification: vi.fn(),
    unvalidateVerification: vi.fn(),
    bulkCompleteVerification: vi.fn(),
  },
}));

describe('VerificationIndex.vue', () => {
  let router: ReturnType<typeof createRouter>;

  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.getItem.mockReturnValue(JSON.stringify({
      id: 'admin-1', name: 'Admin', role: 'super_admin',
    }));

    vi.mocked(VerificationService.fetchVerifications).mockResolvedValue({
      data: [],
      current_page: 1, total: 0, last_page: 1, per_page: 15,
    });

    router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/sabana-center-63/verifications', name: 'admin.verifications', component: VerificationIndex },
        { path: '/sabana-center-63/verifications/:id', name: 'admin.verifications.detail', component: { template: '<div/>' } },
      ],
    });
  });

  function mountComponent() {
    return mount(VerificationIndex, {
      global: {
        plugins: [router],
        stubs: {
          VerificationFilters: { template: '<div class="filters"/>', props: ['modelValue'] },
          VerificationTable: { template: '<div class="table"/>', props: ['showCheckbox', 'verifications', 'loading', 'currentPage', 'totalPages', 'total', 'selectedIds'] },
          ConfirmModal: { template: '<div class="confirm-modal"/>', props: ['open', 'title', 'message', 'variant', 'confirmText', 'cancelText'] },
          SuccessModal: { template: '<div class="success-modal"/>', props: ['show', 'message'] },
        },
      },
    });
  }

  // =============================================
  // RENDERING — 3 TEST
  // =============================================

  it('test_menampilkan_heading_antrean_verifikasi', async () => {
    await router.push({ name: 'admin.verifications' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Antrean Verifikasi');
  });

  it('test_menampilkan_total_antrean', async () => {
    await router.push({ name: 'admin.verifications' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Total Pengajuan');
  });

  it('test_memanggil_fetchVerifications_saat_mount', async () => {
    await router.push({ name: 'admin.verifications' });
    await router.isReady();
    mountComponent();
    await flushPromises();
    expect(VerificationService.fetchVerifications).toHaveBeenCalled();
  });

  // =============================================
  // FILTER — 1 TEST
  // =============================================

  it('test_menampilkan_filter', async () => {
    await router.push({ name: 'admin.verifications' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.html()).toContain('filters');
  });

  // =============================================
  // EMPTY STATE — 1 TEST
  // =============================================

  it('test_tidak_ada_data_saat_kosong', async () => {
    await router.push({ name: 'admin.verifications' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    const vm = wrapper.vm as unknown as { verifications: unknown[] };
    expect(vm.verifications.length).toBe(0);
  });
});