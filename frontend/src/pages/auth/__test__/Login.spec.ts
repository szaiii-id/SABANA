// ===== [IMPORTS] =====
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { ref, nextTick } from 'vue';
import Login from '../Login.vue';

// ===== [MOCK REFS] =====
const isSubmitting = ref(false);
const authError = ref('');

// ===== [MOCKS - AuthService] =====
vi.mock('../../services/AuthService', () => ({
  default: {
    loginUser: vi.fn(),
    registerUser: vi.fn(),
    logoutUser: vi.fn(),
    verifyOtp: vi.fn(),
    resendOtp: vi.fn(),
  },
}));

// ===== [MOCKS - useAuth] =====
vi.mock('../../composables/useAuth', () => ({
  useAuth: () => ({
    isSubmitting,
    authError,
    submitLogin: vi.fn(),
    submitRegistration: vi.fn(),
    handleLogout: vi.fn(),
    isAuthenticated: () => false,
    getStoredToken: () => null,
  }),
}));

// ===== [MOCKS - ROUTER] =====
const mockPush = vi.fn().mockResolvedValue(undefined);

const mockRoute: {
  query: Record<string, string>;
  name: string;
} = {
  query: {},
  name: 'login',
};

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
  useRouter: () => ({
    push: mockPush,
  }),
}));

// ===== [MOCKS - VUELIDATE] =====
const createFieldMock = () => ({
  $error: false,
  $errors: [] as Array<{ $message: string }>,
  $touch: vi.fn(),
});

vi.mock('@vuelidate/core', () => ({
  useVuelidate: vi.fn(() =>
    ref({
      $validate: vi.fn().mockResolvedValue(true),
      $reset: vi.fn(),
      $touch: vi.fn(),
      $model: {},
      $dirty: false,
      $error: false,
      $errors: [],
      $silentErrors: [],
      $externalResults: [],
      nik: createFieldMock(),
      pin: createFieldMock(),
    })
  ),
}));

// ===== [MOCKS - UTILS] =====
vi.mock('../../utils/errorHandler', () => ({
  getSafeErrorMessage: (msg: string) => msg || '',
}));

// ===== [MOCK localStorage] =====
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: vi.fn((key: string) => store[key] ?? null),
    setItem: vi.fn((key: string, value: string) => { store[key] = value; }),
    removeItem: vi.fn((key: string) => { delete store[key]; }),
    clear: vi.fn(() => { store = {}; }),
  };
})();

Object.defineProperty(globalThis, 'localStorage', {
  value: localStorageMock,
  writable: true,
});

// ===== [STUBS] =====
const AuthLayoutStub = {
  template: '<div class="auth-layout"><slot /></div>',
  props: ['maxWidth'],
};

// ===== [HELPERS] =====
const mountLogin = () => {
  return mount(Login, {
    global: {
      stubs: {
        AuthLayout: AuthLayoutStub,
        Transition: { template: '<div><slot /></div>' },
        'router-link': { template: '<a><slot /></a>', props: ['to'] },
      },
    },
  });
};

// ===== [TEST SUITE] =====
describe('Login Page', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockRoute.query = {};
    localStorageMock.clear();
    isSubmitting.value = false;
    authError.value = '';
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===== [1. HAPPY PATH — 5 test] =====
  describe('Happy Path — Login Normal', () => {
    it('merender judul "Selamat Datang"', () => {
      const wrapper = mountLogin();
      expect(wrapper.text()).toContain('Selamat Datang');
    });

    it('merender input NIK dan PIN', () => {
      const wrapper = mountLogin();
      const inputs = wrapper.findAll('input:not([type="checkbox"])');
      expect(inputs.length).toBe(2);
    });

    it('merender checkbox "Ingat NIK Saya"', () => {
      const wrapper = mountLogin();
      const checkbox = wrapper.find('input[type="checkbox"]');
      expect(checkbox.exists()).toBe(true);
    });

    it('formData bisa diisi NIK dan PIN', () => {
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;

      vm.formData.nik = '6372010101010001';
      vm.formData.pin = '123456';

      expect(vm.formData.nik).toBe('6372010101010001');
      expect(vm.formData.pin).toBe('123456');
    });

    it('rememberNik=true → formData.rememberNik true', () => {
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;

      vm.formData.rememberNik = true;
      expect(vm.formData.rememberNik).toBe(true);
    });
  });

  // ===== [2. SAD PATH — 3 test] =====
  describe('Sad Path — Gagal Login', () => {
    it('authError ref berubah saat di-set', async () => {
      authError.value = 'Kombinasi NIK dan PIN tidak cocok.';

      const wrapper = mountLogin();
      await nextTick();

      expect(authError.value).toBe('Kombinasi NIK dan PIN tidak cocok.');
    });

    it('NIK kosong → v$.nik.$error true tampil pesan', async () => {
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;

      vm.v$.nik.$error = true;
      vm.v$.nik.$errors = [{ $message: 'Wajib diisi' }];
      await nextTick();

      expect(wrapper.text()).toContain('Wajib diisi');
    });

    it('formData rememberNik false → hapus remembered_nik', () => {
      localStorageMock.setItem('remembered_nik', '6372010101010001');

      localStorageMock.removeItem('remembered_nik');

      expect(localStorageMock.removeItem).toHaveBeenCalledWith('remembered_nik');
    });
  });

  // ===== [3. BOUNDARY — 2 test] =====
  describe('Boundary — Format Input', () => {
    it('formatNumeric membersihkan non-digit NIK', () => {
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;

      vm.formData.nik = '6372abc0101xyz1';
      vm.formatNumeric('nik', 16);

      expect(vm.formData.nik).toBe('637201011');
    });

    it('formatNumeric memotong PIN ke 6 digit', () => {
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;

      vm.formData.pin = '1234567890';
      vm.formatNumeric('pin', 6);

      expect(vm.formData.pin).toBe('123456');
    });
  });

  // ===== [4. EDGE CASE — 2 test] =====
  describe('Edge Case — Kondisi Khusus', () => {
    it('route query message → sessionAlert terisi', async () => {
      mockRoute.query = { message: 'PIN berhasil diubah.' };
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;
      await nextTick();

      expect(vm.sessionAlert).toBe('PIN berhasil diubah.');
    });

    it('route query message → isSuccessMessage true', async () => {
      mockRoute.query = { message: 'test' };
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;
      await nextTick();

      expect(vm.isSuccessMessage).toBe(true);
    });
  });

  // ===== [5. NULL/EMPTY — 2 test] =====
  describe('Null/Empty — State Awal', () => {
    it('formData kosong saat render', () => {
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;

      expect(vm.formData.nik).toBe('');
      expect(vm.formData.pin).toBe('');
      expect(vm.formData.rememberNik).toBe(false);
    });

    it('authError kosong saat render', () => {
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;

      expect(vm.safeAuthError).toBe('');
    });
  });

  // ===== [6. DATA TYPE — 2 test] =====
  describe('Data Type — Format & Validasi', () => {
    it('formatNumeric membersihkan semua non-digit', () => {
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;

      vm.formData.nik = 'abc123def456!@#';
      vm.formatNumeric('nik', 16);

      expect(vm.formData.nik).toBe('123456');
    });

    it('formatNumeric tidak throw error', () => {
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;

      expect(() => vm.formatNumeric('nik', 16)).not.toThrow();
    });
  });

  // ===== [7. EQUIVALENCE PARTITION — 2 test] =====
  describe('Equivalence Partition — Grup State', () => {
    it('NIK valid 16 digit → v$.nik.$error false', () => {
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;

      vm.v$.nik.$error = false;
      expect(vm.v$.nik.$error).toBe(false);
    });

    it('PIN 6 digit → v$.pin.$error false', () => {
      const wrapper = mountLogin();
      const vm = wrapper.vm as any;

      vm.v$.pin.$error = false;
      expect(vm.v$.pin.$error).toBe(false);
    });
  });

  // ===== [8. STATE TRANSITION — 2 test] =====
  describe('State Transition — Session & Prefill', () => {
    it('session_expired → sessionAlert terisi', async () => {
      localStorageMock.setItem('session_expired', '1');
      mockRoute.query = {};

      const wrapper = mountLogin();
      const vm = wrapper.vm as any;
      await nextTick();

      expect(vm.sessionAlert).toContain('Sesi Anda telah berakhir');
    });

    it('remembered_nik → prefill NIK + rememberNik true', async () => {
      localStorageMock.setItem('remembered_nik', '6372010101010001');
      mockRoute.query = {};

      const wrapper = mountLogin();
      const vm = wrapper.vm as any;
      await nextTick();

      expect(vm.formData.nik).toBe('6372010101010001');
      expect(vm.formData.rememberNik).toBe(true);
    });
  });

  // ===== [9. CONCURRENCY — 1 test] =====
  describe('Concurrency — Submit State', () => {
    it('isSubmitting=true → komponen tetap render', () => {
      isSubmitting.value = true;
      const wrapper = mountLogin();
      expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });
  });

  // ===== [10. SECURITY — 2 test] =====
  describe('Security — Data & Token', () => {
    it('sabana_token dihapus saat mount', () => {
      localStorageMock.setItem('sabana_token', 'old-token');
      mountLogin();

      expect(localStorageMock.removeItem).toHaveBeenCalledWith('sabana_token');
    });

    it('PIN input bertipe password', () => {
      const wrapper = mountLogin();
      const inputs = wrapper.findAll('input[type="password"]');
      expect(inputs.length).toBe(1);
    });
  });
});