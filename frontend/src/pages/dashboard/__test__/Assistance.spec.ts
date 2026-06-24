import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import Assistance from '../Assistance.vue';
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

describe('Assistance.vue', () => {
  let router: ReturnType<typeof createRouter>;

  beforeEach(() => {
    (useAssistance as ReturnType<typeof vi.fn>).mockReturnValue({
      submitAssistance: vi.fn(),
      fetchPrograms: vi.fn(),
      fetchRegencies: vi.fn(),
      fetchDistricts: vi.fn(),
      fetchVillages: vi.fn(),
      programs: { value: [] },
      regencies: { value: [] },
      districts: { value: [] },
      villages: { value: [] },
      isLoading: { value: false },
    });

    router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/assistance', name: 'assistance', component: Assistance },
        { path: '/history', name: 'history', component: { template: '<div/>' } },
      ],
    });
  });

  function mountComponent() {
    return mount(Assistance, {
      global: {
        plugins: [router],
        stubs: {
          RocketLaunchIcon: { template: '<div/>' },
          CheckBadgeIcon: { template: '<div/>' },
          ArrowPathIcon: { template: '<div/>' },
          AssistanceStep1: { template: '<div>Step1</div>' },
          AssistanceStep2: { template: '<div>Step2</div>' },
          AssistanceStep3: { template: '<div>Step3</div>' },
        },
      },
    });
  }

  // ===== HAPPY PATH =====

  it('test_menampilkan_heading_pilih_program', async () => {
    await router.push({ name: 'assistance' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Pilih Program');
  });

  it('test_menampilkan_step_indicator', async () => {
    await router.push({ name: 'assistance' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect(wrapper.text()).toContain('Langkah');
  });

  // ===== DATA TYPE =====

  it('test_default_step_1', async () => {
    await router.push({ name: 'assistance' });
    await router.isReady();
    const wrapper = mountComponent();
    await flushPromises();
    expect((wrapper.vm as unknown as { currentStep: number }).currentStep).toBe(1);
  });
});