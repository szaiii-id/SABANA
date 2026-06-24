import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import { ref } from 'vue';
import DisbursementIndex from '../Index.vue';
import { useDisbursement } from '../../../composables/useDisbursement';

vi.mock('../../../composables/useDisbursement', () => ({
  useDisbursement: vi.fn(),
}));

describe('DisbursementIndex.vue', () => {
  let router: any;

  beforeEach(() => {
    vi.clearAllMocks();

    (useDisbursement as any).mockReturnValue({
      submissions: ref([]),
      loading: ref(false),
      error: ref(null),
      successMessage: ref(null),
      fetchList: vi.fn(),
      disburse: vi.fn(),
      bulkDisburse: vi.fn(),
      resetMessages: vi.fn(),
    });

    router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/sabana-center-63/disbursements', name: 'admin.disbursements', component: DisbursementIndex },
      ],
    });
  });

  function mountComponent() {
    return mount(DisbursementIndex, {
      global: {
        plugins: [router],
        stubs: {
          DisbursementHeader: { template: '<div class="header"/>', props: ['total', 'selectedCount', 'filterStatus'] },
          DisbursementCard: { template: '<div class="card"/>', props: ['submission', 'isSelected', 'isSubmitting'] },
          DisburseSingleModal: { template: '<div class="single-modal"/>', props: ['open', 'submission', 'isSubmitting'] },
          DisburseBulkModal: { template: '<div class="bulk-modal"/>', props: ['open', 'count', 'isSubmitting'] },
        },
      },
    });
  }

  // =============================================
  // RENDERING — 3 TEST
  // =============================================

  it('test_menampilkan_heading_penyaluran_bantuan', async () => {
    await router.push({ name: 'admin.disbursements' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.html()).toContain('header');
  });

  it('test_memanggil_fetchList_saat_mount', async () => {
    await router.push({ name: 'admin.disbursements' });
    await router.isReady();
    mountComponent();
    await flushPromises();

    const mock = (useDisbursement as any).mock.results[0].value;
    expect(mock.fetchList).toHaveBeenCalled();
  });

  it('test_menampilkan_pesan_kosong_saat_tidak_ada_data', async () => {
    (useDisbursement as any).mockReturnValue({
      submissions: ref([]),
      loading: ref(false),
      error: ref(null),
      successMessage: ref(null),
      fetchList: vi.fn(),
      disburse: vi.fn(),
      bulkDisburse: vi.fn(),
      resetMessages: vi.fn(),
    });

    await router.push({ name: 'admin.disbursements' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('Tidak Ada Antrean');
  });

  // =============================================
  // LOADING — 1 TEST
  // =============================================

  it('test_menampilkan_loading_skeleton', async () => {
    (useDisbursement as any).mockReturnValue({
      submissions: ref([]),
      loading: ref(true),
      error: ref(null),
      successMessage: ref(null),
      fetchList: vi.fn(),
      disburse: vi.fn(),
      bulkDisburse: vi.fn(),
      resetMessages: vi.fn(),
    });

    await router.push({ name: 'admin.disbursements' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.html()).toContain('animate-pulse');
  });

  // =============================================
  // ERROR — 1 TEST
  // =============================================

  it('test_menampilkan_error_message', async () => {
    (useDisbursement as any).mockReturnValue({
      submissions: ref([]),
      loading: ref(false),
      error: ref('Gagal memuat data.'),
      successMessage: ref(null),
      fetchList: vi.fn(),
      disburse: vi.fn(),
      bulkDisburse: vi.fn(),
      resetMessages: vi.fn(),
    });

    await router.push({ name: 'admin.disbursements' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('Gagal memuat data.');
  });

  // =============================================
  // SUCCESS — 1 TEST
  // =============================================

  it('test_menampilkan_success_message', async () => {
    (useDisbursement as any).mockReturnValue({
      submissions: ref([]),
      loading: ref(false),
      error: ref(null),
      successMessage: ref('Bantuan berhasil disalurkan.'),
      fetchList: vi.fn(),
      disburse: vi.fn(),
      bulkDisburse: vi.fn(),
      resetMessages: vi.fn(),
    });

    await router.push({ name: 'admin.disbursements' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();

    expect(wrapper.text()).toContain('Bantuan berhasil disalurkan.');
  });
});