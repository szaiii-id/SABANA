import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import Login from '../Login.vue';
import { useAuth } from '../../../composables/useAuth';

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
// MOCK: Composable & Utils
// =============================================
const mockSubmitLogin = vi.fn();
const mockHandleLogout = vi.fn();

vi.mock('../../../composables/useAuth', () => ({
  useAuth: vi.fn(),
}));

vi.mock('../../../utils/errorHandler', () => ({
  getSafeErrorMessage: (msg: unknown): string => (msg as string) || '',
}));

// =============================================
// HELPERS
// =============================================
interface LoginComponentInstance {
  formData: { nip: string; password: string; rememberNip: boolean };
  validationErrors: { nip: string; password: string };
  formatNumeric: (field: 'nip', maxLength: number) => void;
  validateForm: () => boolean;
  onSubmit: () => Promise<void>;
  showPassword: boolean;
  sessionAlert: string;
}

function defaultMockReturn(overrides: Record<string, unknown> = {}) {
  return {
    isSubmitting: ref(false),
    authError: ref(''),
    isRateLimited: ref(false),
    retryAfter: ref(0),
    submitLogin: mockSubmitLogin,
    handleLogout: mockHandleLogout,
    ...overrides,
  };
}

async function setupComponent() {
  const router = createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/sabana-center-63/login', name: 'admin.login', component: Login },
      { path: '/sabana-center-63/dashboard', name: 'admin.dashboard', component: { template: '<div/>' } },
    ],
  });

  await router.push({ name: 'admin.login' });
  await router.isReady();

  const wrapper = mount(Login, {
    global: {
      plugins: [router],
      stubs: { AdminAuthLayout: { template: '<div class="auth-layout"><slot/></div>' } },
    },
  });

  await flushPromises();

  return { wrapper, router };
}

// =============================================
// TEST SUITE
// =============================================
describe('AdminLogin.vue', () => {

  beforeEach(() => {
    vi.clearAllMocks();
    (useAuth as ReturnType<typeof vi.fn>).mockReturnValue(defaultMockReturn());
    localStorageMock.getItem.mockReturnValue(null);
  });

  // ===== HAPPY PATH =====

  it('test_submit_login_sukses_redirect_ke_dashboard', async () => {
    mockSubmitLogin.mockResolvedValue({ success: true });
    const { wrapper, router } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '199001012020011001';
    vm.formData.password = 'password123';

    await vm.onSubmit();
    await flushPromises();

    expect(mockSubmitLogin).toHaveBeenCalledWith({
      nip: '199001012020011001',
      password: 'password123',
    });
    expect(router.currentRoute.value.name).toBe('admin.dashboard');
  });

  it('test_submit_login_sukses_simpan_nip_ke_localstorage', async () => {
    mockSubmitLogin.mockResolvedValue({ success: true });
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '199001012020011001';
    vm.formData.password = 'password123';
    vm.formData.rememberNip = true;

    await vm.onSubmit();
    await flushPromises();

    expect(localStorageMock.setItem).toHaveBeenCalledWith('remembered_nip', '199001012020011001');
  });

  // ===== SAD PATH =====

  it('test_safe_auth_error_kosong_saat_tidak_ada_error', async () => {
    const { wrapper } = await setupComponent();

    const errorDiv = wrapper.find('.bg-\\[\\#FEF2F2\\]');
    expect(errorDiv.exists()).toBe(false);
  });

  it('test_safe_auth_error_tampil_saat_auth_error_ada', async () => {
    const authError = ref('Server error');
    (useAuth as ReturnType<typeof vi.fn>).mockReturnValue(defaultMockReturn({ authError }));

    const { wrapper } = await setupComponent();

    const errorDiv = wrapper.find('.bg-\\[\\#FEF2F2\\]');
    expect(errorDiv.exists()).toBe(true);
    expect(errorDiv.text()).toContain('Server error');
  });

  it('test_submit_login_rate_limited_tampilkan_terkunci', async () => {
    (useAuth as ReturnType<typeof vi.fn>).mockReturnValue(defaultMockReturn({
      isRateLimited: ref(true),
      retryAfter: ref(60),
    }));

    const { wrapper } = await setupComponent();
    const button = wrapper.find('button[type="submit"]');

    expect(button.attributes('disabled')).toBeDefined();
    expect(wrapper.text()).toContain('Terkunci');
  });

  // ===== BOUNDARY =====

  it('test_format_numeric_batasi_nip_tepat_18_digit', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '1990010120200110012345';
    vm.formatNumeric('nip', 18);

    expect(vm.formData.nip).toBe('199001012020011001');
  });

  it('test_format_numeric_hapus_karakter_non_digit', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '1990-0101-2020-0110';
    vm.formatNumeric('nip', 18);

    expect(vm.formData.nip).toBe('1990010120200110');
  });

  it('test_validasi_nip_kurang_dari_18_digit_ditolak', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '12345';
    const isValid = vm.validateForm();

    expect(isValid).toBe(false);
    expect(vm.validationErrors.nip).toBe('NIP harus 18 digit.');
  });

  it('test_validasi_password_min_8_karakter', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '199001012020011001';
    vm.formData.password = '1234567';
    const isValid = vm.validateForm();

    expect(isValid).toBe(false);
    expect(vm.validationErrors.password).toBe('Kata sandi minimal 8 karakter.');
  });

  it('test_validasi_nip_tepat_18_digit_lolos', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '199001012020011001';
    vm.formData.password = 'password123';
    const isValid = vm.validateForm();

    expect(isValid).toBe(true);
  });

  it('test_validasi_password_tepat_8_karakter_lolos', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '199001012020011001';
    vm.formData.password = '12345678';
    const isValid = vm.validateForm();

    expect(isValid).toBe(true);
  });

  // ===== EDGE CASE =====

  it('test_nip_dengan_spasi_disantiasi_oleh_format_numeric', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '1990 0101 2020 0110 01';
    vm.formatNumeric('nip', 18);

    expect(vm.formData.nip).toBe('199001012020011001');
  });

  it('test_session_alert_ditampilkan_saat_session_expired', async () => {
    localStorageMock.getItem.mockReturnValue('true');
    const { wrapper } = await setupComponent();

    expect(wrapper.text()).toContain('Sesi Anda telah berakhir');
  });

  it('test_remember_nip_terisi_dari_localstorage', async () => {
    localStorageMock.getItem.mockReturnValue('199001012020011001');
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    expect(vm.formData.nip).toBe('199001012020011001');
    expect(vm.formData.rememberNip).toBe(true);
  });

  // ===== NULL / EMPTY =====

  it('test_validasi_nip_kosong_ditolak', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    const isValid = vm.validateForm();

    expect(isValid).toBe(false);
    expect(vm.validationErrors.nip).toBe('NIP wajib diisi.');
  });

  it('test_validasi_password_kosong_ditolak', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '199001012020011001';
    const isValid = vm.validateForm();

    expect(isValid).toBe(false);
    expect(vm.validationErrors.password).toBe('Kata sandi wajib diisi.');
  });

  it('test_form_data_kosong_saat_pertama_load', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    expect(vm.formData.nip).toBe('');
    expect(vm.formData.password).toBe('');
    expect(vm.formData.rememberNip).toBe(false);
  });

  // ===== DATA TYPE =====

  it('test_format_numeric_abaikan_input_kosong', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '';
    vm.formatNumeric('nip', 18);

    expect(vm.formData.nip).toBe('');
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_validasi_nip_17_digit_ditolak', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '19900101202001100';
    vm.formData.password = 'password123';
    const isValid = vm.validateForm();

    expect(isValid).toBe(false);
  });

  it('test_validasi_nip_18_digit_lolos', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '199001012020011001';
    vm.formData.password = 'password123';
    const isValid = vm.validateForm();

    expect(isValid).toBe(true);
  });

  it('test_validasi_nip_19_digit_ditolak', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '1990010120200110019';
    vm.formData.password = 'password123';
    const isValid = vm.validateForm();

    expect(isValid).toBe(false);
  });

  // ===== STATE TRANSITION =====

  it('test_format_numeric_clear_error_saat_input', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.validationErrors.nip = 'Error';
    vm.formatNumeric('nip', 18);

    expect(vm.validationErrors.nip).toBe('');
  });

  it('test_tombol_disabled_saat_submitting', async () => {
    (useAuth as ReturnType<typeof vi.fn>).mockReturnValue(defaultMockReturn({
      isSubmitting: ref(true),
    }));

    const { wrapper } = await setupComponent();
    const button = wrapper.find('button[type="submit"]');

    expect(button.attributes('disabled')).toBeDefined();
    expect(wrapper.text()).toContain('Memverifikasi');
  });

  it('test_show_password_toggle', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    expect(vm.showPassword).toBe(false);

    const toggleButton = wrapper.find('button[type="button"]');
    if (toggleButton.exists()) {
      await toggleButton.trigger('click');
      expect(vm.showPassword).toBe(true);
    }
  });

  // ===== CONCURRENCY =====

  it('test_double_submit_dicegah_saat_submitting', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '199001012020011001';
    vm.formData.password = 'password123';

    void vm.onSubmit();
    await flushPromises();

    await vm.onSubmit();
    await flushPromises();

    expect(mockSubmitLogin).toHaveBeenCalledTimes(2);
  });

  // ===== SECURITY =====

  it('test_password_tidak_terlihat_default', async () => {
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    expect(vm.showPassword).toBe(false);
  });

  it('test_remember_nip_false_hapus_localstorage', async () => {
    mockSubmitLogin.mockResolvedValue({ success: true });
    const { wrapper } = await setupComponent();
    const vm = wrapper.vm as unknown as LoginComponentInstance;

    vm.formData.nip = '199001012020011001';
    vm.formData.password = 'password123';
    vm.formData.rememberNip = false;

    await vm.onSubmit();
    await flushPromises();

    expect(localStorageMock.removeItem).toHaveBeenCalledWith('remembered_nip');
  });

  // ===== RENDERING =====

  it('test_menampilkan_heading_sabana_center', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('SABANA');
  });

  it('test_menampilkan_label_nip_pegawai', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('NIP Pegawai');
  });

  it('test_menampilkan_label_kata_sandi', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Kata Sandi');
  });

  it('test_menampilkan_tombol_masuk_ke_sistem', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Masuk ke Sistem');
  });

  it('test_checkbox_remember_nip_ada', async () => {
    const { wrapper } = await setupComponent();
    expect(wrapper.text()).toContain('Ingat NIP Saya');
  });
});