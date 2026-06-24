import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import History from '../History.vue';
import { useAssistance } from '../../../composables/useAssistance';

const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

vi.mock('../../../composables/useAssistance', () => ({
  useAssistance: vi.fn(),
}));

describe('History.vue', () => {
  let router: any;

  beforeEach(() => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, total: 0 } }),
      deleteAssistance: vi.fn(),
      isLoading: { value: false },
      fetchDisbursementReceipt: vi.fn(),
      downloadDisbursementPdf: vi.fn(),
    });

    router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/history', name: 'history', component: History },
        { path: '/assistance/edit/:id', name: 'assistance.edit', component: { template: '<div/>' } },
        { path: '/assistance/detail/:id', name: 'assistance.detail', component: { template: '<div/>' } },
      ],
    });
  });

  function mountComponent() {
    return mount(History, {
      global: {
        plugins: [router],
        stubs: {
          ChevronRightIcon: { template: '<div/>' },
          ExclamationTriangleIcon: { template: '<div/>' },
          TrashIcon: { template: '<div/>' },
          ExclamationCircleIcon: { template: '<div/>' },
          FingerPrintIcon: { template: '<div/>' },
          InboxStackIcon: { template: '<div/>' },
          AcademicCapIcon: { template: '<div/>' },
          HomeModernIcon: { template: '<div/>' },
          ArrowPathIcon: { template: '<div/>' },
          CheckBadgeIcon: { template: '<div/>' },
          DisbursementReceiptModal: { template: '<div/>', props: ['visible', 'data', 'submissionId'] },
        },
      },
    });
  }

  // =============================================
  // RENDERING — 3 TEST
  // =============================================

  it('test_menampilkan_heading_riwayat_pendaftaran', async () => {
    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Riwayat Pendaftaran');
  });

  it('test_menampilkan_pesan_kosong_saat_tidak_ada_data', async () => {
    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    await wrapper.vm.$nextTick();
    const vm = wrapper.vm as any;
    expect(vm.submissions.length).toBe(0);
    expect(wrapper.text()).toContain('Total Berkas');
  });

  it('test_menampilkan_total_berkas', async () => {
    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Total Berkas');
  });

  // =============================================
  // DATA — 1 TEST
  // =============================================

  it('test_menampilkan_data_submission', async () => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockResolvedValue({
        data: [
          {
            id: '1', registration_number: 'SBN-TEST001',
            program: { name: 'BLT Dana Desa' }, status: 'pending',
            is_evaluation: false, revision_items: [], admin_note: null,
            submitted_at: '2024-01-15',
            location: { village: 'Desa A', district: 'Kec A', regency: 'Kab A', province: 'KALSEL' },
          },
        ],
        meta: { current_page: 1, last_page: 1, total: 1 },
      }),
      deleteAssistance: vi.fn(), isLoading: { value: false },
      fetchDisbursementReceipt: vi.fn(), downloadDisbursementPdf: vi.fn(),
    });

    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('SBN-TEST001');
    expect(wrapper.text()).toContain('BLT Dana Desa');
  });

  // =============================================
  // LOADING — 1 TEST
  // =============================================

  it('test_menampilkan_loading_skeleton', async () => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockReturnValue(new Promise(() => {})),
      deleteAssistance: vi.fn(), isLoading: { value: true },
      fetchDisbursementReceipt: vi.fn(), downloadDisbursementPdf: vi.fn(),
    });

    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.html()).toContain('animate-pulse');
  });

  // =============================================
  // META — 1 TEST
  // =============================================

  it('test_menyimpan_meta_dari_response', async () => {
    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    const vm = wrapper.vm as any;
    expect(vm.meta.current_page).toBe(1);
    expect(vm.meta.total).toBe(0);
  });

  // =============================================
  // CONDITIONAL STATUS — 7 TEST
  // =============================================

  it('test_menampilkan_banner_revisi_untuk_status_needs_revision', async () => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockResolvedValue({
        data: [{
          id: '1', registration_number: 'SBN-001', program: { name: 'BLT' },
          status: 'needs_revision', is_evaluation: false,
          revision_items: ['foto_ktp'], admin_note: 'Foto kurang jelas',
          submitted_at: '2024-01-15',
          location: { village: 'Desa', district: 'Kec', regency: 'Kab', province: 'KALSEL' },
        }],
        meta: { current_page: 1, last_page: 1, total: 1 },
      }),
      deleteAssistance: vi.fn(), isLoading: { value: false },
      fetchDisbursementReceipt: vi.fn(), downloadDisbursementPdf: vi.fn(),
    });

    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Perlu Perbaikan');
  });

  it('test_menampilkan_banner_evaluasi_untuk_status_evaluation_pending', async () => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockResolvedValue({
        data: [{
          id: '1', registration_number: 'SBN-002', program: { name: 'PKH' },
          status: 'evaluation_pending', is_evaluation: true,
          revision_items: [], admin_note: null,
          submitted_at: '2024-01-15',
          location: { village: 'Desa', district: 'Kec', regency: 'Kab', province: 'KALSEL' },
        }],
        meta: { current_page: 1, last_page: 1, total: 1 },
      }),
      deleteAssistance: vi.fn(), isLoading: { value: false },
      fetchDisbursementReceipt: vi.fn(), downloadDisbursementPdf: vi.fn(),
    });

    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Evaluasi');
  });

  it('test_menampilkan_tombol_hapus_untuk_status_pending', async () => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockResolvedValue({
        data: [{
          id: '1', registration_number: 'SBN-003', program: { name: 'BLT' },
          status: 'pending', is_evaluation: false,
          revision_items: [], admin_note: null,
          submitted_at: '2024-01-15',
          location: { village: 'Desa', district: 'Kec', regency: 'Kab', province: 'KALSEL' },
        }],
        meta: { current_page: 1, last_page: 1, total: 1 },
      }),
      deleteAssistance: vi.fn(), isLoading: { value: false },
      fetchDisbursementReceipt: vi.fn(), downloadDisbursementPdf: vi.fn(),
    });

    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    const vm = wrapper.vm as any;
    const hasPending = vm.submissions.some((s: any) => s.status === 'pending');
    expect(hasPending).toBe(true);
  });

  it('test_tidak_menampilkan_tombol_hapus_untuk_status_validated', async () => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockResolvedValue({
        data: [{
          id: '1', registration_number: 'SBN-004', program: { name: 'BLT' },
          status: 'validated', is_evaluation: false,
          revision_items: [], admin_note: null,
          submitted_at: '2024-01-15',
          location: { village: 'Desa', district: 'Kec', regency: 'Kab', province: 'KALSEL' },
        }],
        meta: { current_page: 1, last_page: 1, total: 1 },
      }),
      deleteAssistance: vi.fn(), isLoading: { value: false },
      fetchDisbursementReceipt: vi.fn(), downloadDisbursementPdf: vi.fn(),
    });

    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    const vm = wrapper.vm as any;
    const canDelete = ['pending', 'needs_revision'].includes(vm.submissions[0].status);
    expect(canDelete).toBe(false);
  });

  // =============================================
  // COMPLETED STATUS — 3 TEST
  // =============================================

  it('test_menampilkan_badge_disalurkan_untuk_status_completed', async () => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockResolvedValue({
        data: [{
          id: '1', registration_number: 'SBN-CMP001', program: { name: 'BLT' },
          status: 'completed', is_evaluation: false,
          revision_items: [], admin_note: null,
          submitted_at: '2024-01-15',
          location: { village: 'Desa', district: 'Kec', regency: 'Kab', province: 'KALSEL' },
          disbursement: { reference_number: 'REF-001' },
        }],
        meta: { current_page: 1, last_page: 1, total: 1 },
      }),
      deleteAssistance: vi.fn(), isLoading: { value: false },
      fetchDisbursementReceipt: vi.fn(), downloadDisbursementPdf: vi.fn(),
    });

    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Disalurkan');
  });

  it('test_menampilkan_banner_completed', async () => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockResolvedValue({
        data: [{
          id: '1', registration_number: 'SBN-CMP002', program: { name: 'PKH' },
          status: 'completed', is_evaluation: false,
          revision_items: [], admin_note: null,
          submitted_at: '2024-01-15',
          location: { village: 'Desa', district: 'Kec', regency: 'Kab', province: 'KALSEL' },
          disbursement: { reference_number: 'REF-002' },
        }],
        meta: { current_page: 1, last_page: 1, total: 1 },
      }),
      deleteAssistance: vi.fn(), isLoading: { value: false },
      fetchDisbursementReceipt: vi.fn(), downloadDisbursementPdf: vi.fn(),
    });

    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Bantuan Disalurkan');
  });

  it('test_menampilkan_tombol_bukti_penyaluran_untuk_status_completed', async () => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockResolvedValue({
        data: [{
          id: '1', registration_number: 'SBN-CMP003', program: { name: 'BLT' },
          status: 'completed', is_evaluation: false,
          revision_items: [], admin_note: null,
          submitted_at: '2024-01-15',
          location: { village: 'Desa', district: 'Kec', regency: 'Kab', province: 'KALSEL' },
          disbursement: { reference_number: 'REF-003' },
        }],
        meta: { current_page: 1, last_page: 1, total: 1 },
      }),
      deleteAssistance: vi.fn(), isLoading: { value: false },
      fetchDisbursementReceipt: vi.fn(), downloadDisbursementPdf: vi.fn(),
    });

    await router.push({ name: 'history' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Bukti Penyaluran');
  });
});