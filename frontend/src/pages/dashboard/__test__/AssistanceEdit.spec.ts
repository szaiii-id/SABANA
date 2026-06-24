import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import AssistanceEdit from '../AssistanceEdit.vue';
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

describe('AssistanceEdit.vue', () => {
  let router: any;

  beforeEach(() => {
    localStorageMock.getItem.mockReturnValue(JSON.stringify({ id: '1', status: 'needs_revision' }));

    (useAssistance as any).mockReturnValue({
      fetchDetail: vi.fn().mockResolvedValue({
        id: '1', registration_number: 'SBN-001', status: 'needs_revision',
        program: { name: 'BLT', criteria: { inputs: [], documents: [] } },
        regency_id: '6301', district_id: '6301020', village_id: '6301020001',
        disbursement_method: 'village_cash', bank_account_number: '',
        revision_items: ['foto_ktp'],
      }),
      fetchPrograms: vi.fn(),
      fetchRegencies: vi.fn(),
      fetchDistricts: vi.fn(),
      fetchVillages: vi.fn(),
      updateAssistance: vi.fn(),
      programs: { value: [] },
      regencies: { value: [] },
      districts: { value: [] },
      villages: { value: [] },
      isLoading: { value: false },
    });

    router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/assistance/edit/:id', name: 'assistance.edit', component: AssistanceEdit },
        { path: '/history', name: 'history', component: { template: '<div/>' } },
      ],
    });
  });

  function mountComponent() {
    return mount(AssistanceEdit, {
      global: {
        plugins: [router],
        stubs: {
          AssistanceStep2: { template: '<div>Step2</div>' },
          AssistanceStep3: { template: '<div>Step3</div>' },
        },
      },
    });
  }

  // =============================================
  // RENDERING
  // =============================================

  it('test_menampilkan_heading_perbaiki_pengajuan', async () => {
    await router.push({ name: 'assistance.edit', params: { id: '1' } });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Perbaiki');
  });

  it('test_menampilkan_mode_revisi', async () => {
    await router.push({ name: 'assistance.edit', params: { id: '1' } });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Mode Revisi');
  });

  it('test_redirect_ke_history_jika_tidak_ada_draft', async () => {
    localStorageMock.getItem.mockReturnValue(null);

    await router.push({ name: 'assistance.edit', params: { id: '1' } });
    await router.isReady();
    mountComponent();
    await flushPromises();

    expect(router.currentRoute.value.name).toBe('history');
  });
});