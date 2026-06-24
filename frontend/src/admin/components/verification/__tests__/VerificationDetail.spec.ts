import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import VerificationDetail from '../VerificationDetail.vue';
import VerificationService from '../../../services/VerificationService';

vi.mock('../../../services/VerificationService', () => ({
  default: {
    fetchVerificationDetail: vi.fn(),
    approveVerification: vi.fn(),
    rejectVerification: vi.fn(),
    requestRevision: vi.fn(),
    completeVerification: vi.fn(),
    unvalidateVerification: vi.fn(),
  },
}));

vi.mock('../../../../composables/useDisbursement', () => ({
  useDisbursement: () => ({
    disburse: vi.fn(),
    isSubmitting: { value: false },
  }),
}));

describe('VerificationDetail.vue', () => {
  let router: any;

  const mockDetail = {
    id: 'sub-1',
    registration_number: 'SBN-001',
    status: 'pending',
    smart_score: 85.5,
    recommendation: { label: 'Sangat Direkomendasikan', color: 'green' },
    disbursement_method: 'village_cash',
    bank_account_number: null,
    citizen: {
      id: 'c1',
      nik: '6301234567890123',
      full_name: 'Muhammad Noor',
      whatsapp_number: '6281234567890',
    },
    program: {
      id: 'p1',
      name: 'BLT Dana Desa',
      benefit_amount: 500000,
      criteria: {
        inputs: [],
        documents: [],
      },
    },
    submission_data: {
      pekerjaan: 'Petani',
      penghasilan: 1000000,
    },
    evidences: [
      {
        id: 'ev1',
        image_type: 'ktp',
        image_url: 'https://example.com/ktp.jpg',
        ai_result: {
          success: true,
          matches: [{ key: 'nik', label: 'NIK', match_status: 'cocok' }],
          summary: { cocok: 1, total: 1 },
        },
      },
    ],
    wilayah: {
      village: 'Desa Test',
      district: 'Kecamatan Test',
      regency: 'Kabupaten Test',
    },
    verified_by_name: null,
    verified_at: null,
    revision_by_name: null,
    revision_at: null,
    revision_items: [],
    disbursement: null,
    created_at: '2026-01-01 10:00:00',
  };

  beforeEach(() => {
    vi.clearAllMocks();

    (VerificationService.fetchVerificationDetail as any).mockResolvedValue({
      data: mockDetail,
    });

    router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/sabana-center-63/verifications/:id', name: 'admin.verifications.detail', component: VerificationDetail },
        { path: '/sabana-center-63/verifications', name: 'admin.verifications', component: { template: '<div/>' } },
      ],
    });
  });

  function mountComponent() {
    return mount(VerificationDetail, {
      global: {
        plugins: [router],
        stubs: {
          VerificationActions: { template: '<div class="actions"/>', props: ['disabled', 'submitting', 'documents', 'inputs'] },
          SuspiciousActivity: { template: '<div class="suspicious"/>', props: ['anomalies'] },
          EvidenceGallery: { template: '<div class="gallery"/>', props: ['evidences', 'zoomedDocuments'] },
          ConfirmModal: { template: '<div class="confirm-modal"/>', props: ['open', 'title', 'message', 'variant', 'confirmText', 'cancelText'] },
          SuccessModal: { template: '<div class="success-modal"/>', props: ['show', 'message'] },
          DisburseSingleModal: { template: '<div class="disburse-modal"/>', props: ['open', 'submission', 'isSubmitting'] },
        },
      },
    });
  }

  // =============================================
  // RENDERING — 5 TEST
  // =============================================

  it('test_menampilkan_nama_warga', async () => {
    await router.push({ name: 'admin.verifications.detail', params: { id: 'sub-1' } });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('Muhammad Noor');
  });

  it('test_menampilkan_nik', async () => {
    await router.push({ name: 'admin.verifications.detail', params: { id: 'sub-1' } });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('6301234567890123');
  });

  it('test_menampilkan_registration_number', async () => {
    await router.push({ name: 'admin.verifications.detail', params: { id: 'sub-1' } });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('SBN-001');
  });

  it('test_menampilkan_nama_program', async () => {
    await router.push({ name: 'admin.verifications.detail', params: { id: 'sub-1' } });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('BLT Dana Desa');
  });

  it('test_menampilkan_skor_smart', async () => {
    await router.push({ name: 'admin.verifications.detail', params: { id: 'sub-1' } });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('85.5');
  });

  // =============================================
  // STATUS — 3 TEST
  // =============================================

  it('test_menampilkan_status_pending', async () => {
    await router.push({ name: 'admin.verifications.detail', params: { id: 'sub-1' } });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('Pending');
  });

  it('test_menampilkan_status_validated', async () => {
    (VerificationService.fetchVerificationDetail as any).mockResolvedValue({
      data: { ...mockDetail, status: 'validated' },
    });

    await router.push({ name: 'admin.verifications.detail', params: { id: 'sub-1' } });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('Disetujui');
  });

  it('test_menampilkan_status_rejected', async () => {
    (VerificationService.fetchVerificationDetail as any).mockResolvedValue({
      data: { ...mockDetail, status: 'rejected' },
    });

    await router.push({ name: 'admin.verifications.detail', params: { id: 'sub-1' } });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('Ditolak');
  });

  // =============================================
  // LOADING — 1 TEST
  // =============================================

  it('test_menampilkan_loading_saat_fetch', async () => {
    (VerificationService.fetchVerificationDetail as any).mockReturnValue(new Promise(() => {}));

    await router.push({ name: 'admin.verifications.detail', params: { id: 'sub-1' } });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('Memuat detail pengajuan');
  });

  // =============================================
  // LOKASI — 1 TEST
  // =============================================

  it('test_menampilkan_data_lokasi', async () => {
    await router.push({ name: 'admin.verifications.detail', params: { id: 'sub-1' } });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('Desa Test');
    expect(wrapper.text()).toContain('Kecamatan Test');
    expect(wrapper.text()).toContain('Kabupaten Test');
  });
});