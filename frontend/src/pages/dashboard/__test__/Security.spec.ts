import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import Security from '../Security.vue';
import { securityApi } from '../../../api/securityApi';

// ===== MOCKS =====
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

vi.mock('../../../api/securityApi', () => ({
  securityApi: {
    updatePin: vi.fn(),
  },
}));

// ===== TYPES =====
interface VmSecurity {
  form: {
    current_pin: string;
    new_pin: string;
    new_pin_confirmation: string;
  };
  errors: {
    current_pin: string;
    new_pin: string;
    new_pin_confirmation: string;
  };
  isSubmitting: boolean;
  notification: {
    type: string;
    message: string;
  };
  formatNumeric: (field: 'current_pin' | 'new_pin' | 'new_pin_confirmation') => void;
  validateForm: () => boolean;
}

// ===== ROUTER =====
function createTestRouter() {
  return createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/security', name: 'security', component: Security },
      { path: '/login', name: 'login', component: { template: '<div/>' } },
    ],
  });
}

// ===== MOUNT HELPER =====
function mountComponent(router: ReturnType<typeof createTestRouter>) {
  return mount(Security, {
    global: {
      plugins: [router],
    },
  });
}

// ===== TEST SUITE =====
describe('Security.vue', () => {
  let router: ReturnType<typeof createTestRouter>;

  beforeEach(() => {
    localStorageMock.getItem.mockReturnValue(null);
    router = createTestRouter();
    vi.clearAllMocks();
  });

  // ===== RENDERING =====
  it('test_menampilkan_heading_keamanan_akun', async () => {
    await router.push({ name: 'security' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    expect(wrapper.text()).toContain('Keamanan');
  });

  it('test_menampilkan_input_pin_saat_ini', async () => {
    await router.push({ name: 'security' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    expect(wrapper.text()).toContain('PIN Saat Ini');
  });

  it('test_menampilkan_tombol_perbarui_pin', async () => {
    await router.push({ name: 'security' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    expect(wrapper.text()).toContain('PERBARUI PIN');
  });

  // ===== FORMAT NUMERIC =====
  it('test_format_numeric_batasi_pin_6_digit', async () => {
    await router.push({ name: 'security' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmSecurity;
    vm.form.current_pin = '12345678';
    vm.formatNumeric('current_pin');
    expect(vm.form.current_pin).toBe('123456');
  });

  it('test_format_numeric_hapus_karakter_non_digit', async () => {
    await router.push({ name: 'security' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmSecurity;
    vm.form.new_pin = '12ab56';
    vm.formatNumeric('new_pin');
    expect(vm.form.new_pin).toBe('1256');
  });

  it('test_format_numeric_reset_error_saat_input', async () => {
    await router.push({ name: 'security' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmSecurity;
    vm.errors.current_pin = 'Error';
    vm.notification.message = 'Ada error';
    vm.formatNumeric('current_pin');
    expect(vm.errors.current_pin).toBe('');
    expect(vm.notification.message).toBe('');
  });

  // ===== VALIDASI =====
  it('test_validasi_pin_kurang_dari_6_digit', async () => {
    await router.push({ name: 'security' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmSecurity;
    vm.form.current_pin = '12345';
    vm.form.new_pin = '654321';
    vm.form.new_pin_confirmation = '654321';
    const isValid = vm.validateForm();
    expect(isValid).toBe(false);
    expect(vm.errors.current_pin).toBe('PIN harus 6 digit');
  });

  it('test_validasi_konfirmasi_pin_tidak_sesuai', async () => {
    await router.push({ name: 'security' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmSecurity;
    vm.form.current_pin = '123456';
    vm.form.new_pin = '654321';
    vm.form.new_pin_confirmation = '999999';
    const isValid = vm.validateForm();
    expect(isValid).toBe(false);
    expect(vm.errors.new_pin_confirmation).toBe('Konfirmasi PIN tidak sesuai');
  });

  it('test_validasi_pin_baru_sama_dengan_pin_lama', async () => {
    await router.push({ name: 'security' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmSecurity;
    vm.form.current_pin = '123456';
    vm.form.new_pin = '123456';
    vm.form.new_pin_confirmation = '123456';
    const isValid = vm.validateForm();
    expect(isValid).toBe(false);
    expect(vm.errors.new_pin).toBe('PIN baru tidak boleh sama dengan PIN saat ini');
  });

  // ===== LOADING =====
  it('test_tombol_disabled_saat_submitting', async () => {
    await router.push({ name: 'security' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();
    const vm = wrapper.vm as unknown as VmSecurity;
    vm.isSubmitting = true;
    await wrapper.vm.$nextTick();
    const button = wrapper.find('button[type="submit"]');
    expect(button.attributes('disabled')).toBeDefined();
  });
});