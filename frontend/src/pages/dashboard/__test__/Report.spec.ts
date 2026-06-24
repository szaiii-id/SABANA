import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import Report from '../Report.vue';
import { reportApi } from '../../../api/reportApi';

// ===== MOCKS =====
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

vi.mock('../../../api/reportApi', () => ({
  reportApi: {
    sendWhatsapp: vi.fn(),
    sendEmail: vi.fn(),
  },
}));

vi.mock('../../../api/axios', () => ({
  default: {
    get: vi.fn(),
  },
}));

// ===== TYPES =====
interface VmReport {
  activeTab: 'whatsapp' | 'email';
  isSubmitting: boolean;
  citizenData: {
    nik: string;
    full_name: string;
  };
  form: {
    email: string;
    subjek: string;
    pesan: string;
  };
  errors: {
    email: string;
    subjek: string;
    pesan: string;
  };
  validate: () => boolean;
}

// ===== ROUTER =====
function createTestRouter() {
  return createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/report', name: 'report', component: Report },
    ],
  });
}

// ===== MOUNT HELPER =====
function mountComponent(router: ReturnType<typeof createTestRouter>) {
  return mount(Report, {
    global: { plugins: [router] },
  });
}

// ===== TEST SUITE =====
describe('Report.vue', () => {
  let router: ReturnType<typeof createTestRouter>;

  beforeEach(() => {
    localStorageMock.getItem.mockReturnValue(null);
    router = createTestRouter();
    vi.clearAllMocks();
  });

  // ===== RENDERING =====
  it('test_menampilkan_heading_layanan_aspirasi', async () => {
    await router.push({ name: 'report' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    expect(wrapper.text()).toContain('Aspirasi');
  });

  it('test_menampilkan_tab_whatsapp_dan_email', async () => {
    await router.push({ name: 'report' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    expect(wrapper.text()).toContain('WhatsApp');
    expect(wrapper.text()).toContain('Email');
  });

  it('test_default_tab_whatsapp', async () => {
    await router.push({ name: 'report' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmReport;
    expect(vm.activeTab).toBe('whatsapp');
  });

  // ===== VALIDASI =====
  it('test_validasi_pesan_kurang_dari_10_karakter', async () => {
    await router.push({ name: 'report' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmReport;
    vm.form.pesan = 'Pendek';
    const isValid = vm.validate();
    expect(isValid).toBe(false);
    expect(vm.errors.pesan).toBe('Pesan minimal 10 karakter');
  });

  it('test_validasi_email_tidak_valid', async () => {
    await router.push({ name: 'report' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmReport;
    vm.activeTab = 'email';
    vm.form.email = 'bukanemail';
    vm.form.subjek = 'Subjek Laporan';
    vm.form.pesan = 'Pesan laporan yang cukup panjang';
    const isValid = vm.validate();
    expect(isValid).toBe(false);
    expect(vm.errors.email).toBe('Email tidak valid');
  });

  it('test_validasi_berhasil_saat_semua_valid', async () => {
    await router.push({ name: 'report' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmReport;
    vm.activeTab = 'email';
    vm.form.email = 'test@email.com';
    vm.form.subjek = 'Subjek Laporan';
    vm.form.pesan = 'Pesan laporan yang cukup panjang';
    const isValid = vm.validate();
    expect(isValid).toBe(true);
  });

  // ===== TAB SWITCH =====
  it('test_switch_tab_reset_form', async () => {
    await router.push({ name: 'report' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmReport;
    vm.form.pesan = 'Test pesan';
    vm.activeTab = 'email';
    await wrapper.vm.$nextTick();
    expect(vm.form.pesan).toBe('');
  });

  // ===== LOADING =====
  it('test_tombol_disabled_saat_submitting', async () => {
    await router.push({ name: 'report' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmReport;
    vm.isSubmitting = true;
    await wrapper.vm.$nextTick();
    const button = wrapper.find('button[type="submit"]');
    expect(button.attributes('disabled')).toBeDefined();
  });
});