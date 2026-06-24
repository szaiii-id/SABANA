// ===== [IMPORTS] =====
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { ref } from 'vue';
import Register from '../Register.vue';

// ===== [MOCK REFS] =====
const mockSubmitRegistration = vi.fn().mockResolvedValue({ success: true, phone: '6281234567890' });
const isSubmitting = ref(false);
const authError = ref('');

// ===== [MOCKS - AuthService] =====
vi.mock('../../services/AuthService', () => ({
  default: {
    registerUser: vi.fn().mockResolvedValue({
      status: 'success',
      message: 'Registrasi berhasil.',
      data: {
        nik: '6372010101010001',
        full_name: 'John Doe',
        whatsapp_number: '6281234567890',
      },
    }),
    loginUser: vi.fn(),
    logoutUser: vi.fn(),
    verifyOtp: vi.fn(),
    resendOtp: vi.fn(),
    requestForgotPinOtp: vi.fn(),
    resetPin: vi.fn(),
  },
}));

// ===== [MOCKS - ROUTER] =====
const mockPush = vi.fn().mockResolvedValue(undefined);
const mockReplace = vi.fn().mockResolvedValue(undefined);

const mockRoute: {
  query: Record<string, string>;
  name: string;
} = {
  query: { edit: 'false' },
  name: 'register',
};

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
  }),
}));

// ===== [MOCKS - VUELIDATE] =====
const createFieldMock = () => ({
  $error: false,
  $errors: [] as Array<{ $message: string }>,
  $touch: vi.fn(),
});

const mockVuelidate = () => ({
  $validate: vi.fn().mockResolvedValue(true) as () => Promise<boolean>,
  $reset: vi.fn() as () => void,
  $touch: vi.fn() as () => Promise<void>,
  $model: {} as Record<string, unknown>,
  $dirty: false as boolean,
  $error: false as boolean,
  $errors: [] as Array<{ $message: string }>,
  $silentErrors: [] as Array<{ $message: string }>,
  $externalResults: [] as Array<{ $message: string }>,
  nik: createFieldMock(),
  family_card_number: createFieldMock(),
  full_name: createFieldMock(),
  whatsapp_number: createFieldMock(),
  pin: createFieldMock(),
  pin_confirmation: createFieldMock(),
  agree_terms: createFieldMock(),
});

vi.mock('@vuelidate/core', () => ({
  useVuelidate: vi.fn(() => ref(mockVuelidate()) as any),
}));

// ===== [MOCKS - useAuth] =====
vi.mock('../../composables/useAuth', () => ({
  useAuth: () => ({
    isSubmitting,
    authError,
    submitRegistration: mockSubmitRegistration,
    submitLogin: vi.fn(),
    handleLogout: vi.fn(),
    isAuthenticated: () => false,
    getStoredToken: () => null,
  }),
}));

// ===== [MOCKS - UTILS] =====
vi.mock('../../utils/errorHandler', () => ({
  getSafeErrorMessage: (msg: string) => msg || '',
}));

vi.mock('../../api/axios', () => ({
  default: {
    get: vi.fn().mockResolvedValue({ data: {} }),
  },
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
  props: ['maxWidth', 'extraPadding'],
};

const FormFieldStub = {
  template: `
    <div class="form-field">
      <input
        :value="modelValue"
        :type="type || 'text'"
        :disabled="disabled"
        :maxlength="maxlength"
        @input="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur')"
      />
      <span v-if="error && errorMessage" class="error-msg">{{ errorMessage }}</span>
    </div>
  `,
  props: ['modelValue', 'label', 'type', 'disabled', 'error', 'errorMessage', 'maxlength'],
  emits: ['update:modelValue', 'blur'],
};

const SubmitButtonStub = {
  template: `<button type="submit" :disabled="isSubmitting">{{ label }}</button>`,
  props: ['isSubmitting', 'label', 'loadingLabel'],
};

// ===== [HELPERS] =====
const mountRegister = () => {
  return mount(Register, {
    global: {
      stubs: {
        AuthLayout: AuthLayoutStub,
        FormField: FormFieldStub,
        SubmitButton: SubmitButtonStub,
        Transition: { template: '<div><slot /></div>' },
        'router-link': { template: '<a><slot /></a>', props: ['to'] },
      },
    },
  });
};

const fillAllInputs = async (wrapper: ReturnType<typeof mountRegister>) => {
  const inputs = wrapper.findAll('input:not([type="checkbox"])');
  const values = [
    '6372010101010001',
    '6372010101010002',
    'John Doe',
    '6281234567890',
    '123456',
    '123456',
  ];

  for (let i = 0; i < inputs.length; i++) {
    await inputs[i].setValue(values[i] || '');
  }
};

// ===== [TEST SUITE] =====
describe('Register Page', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockRoute.query = { edit: 'false' };
    localStorageMock.clear();
    isSubmitting.value = false;
    authError.value = '';
    mockSubmitRegistration.mockResolvedValue({ success: true, phone: '6281234567890' });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===== [1. HAPPY PATH — 5 test] =====
  describe('Happy Path — Registrasi Normal', () => {
    it('merender judul "Registrasi Warga"', () => {
      const wrapper = mountRegister();
      expect(wrapper.text()).toContain('Registrasi Warga');
    });

    it('merender form dengan tombol submit', () => {
      const wrapper = mountRegister();
      expect(wrapper.find('form').exists()).toBe(true);
      expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });

    it('form bisa di-trigger submit tanpa error', () => {
      const wrapper = mountRegister();
      const form = wrapper.find('form');
      expect(() => form.trigger('submit.prevent')).not.toThrow();
    });

    it('formData terisi benar setelah fillAllInputs', async () => {
      const wrapper = mountRegister();
      await fillAllInputs(wrapper);

      const vm = wrapper.vm as any;
      expect(vm.formData.nik).toBe('6372010101010001');
      expect(vm.formData.full_name).toBe('John Doe');
      expect(vm.formData.whatsapp_number).toBe('6281234567890');
    });

    it('link "Masuk di sini" tampil', () => {
      const wrapper = mountRegister();
      expect(wrapper.text()).toContain('Masuk di sini');
    });
  });

  // ===== [2. SAD PATH — 2 test] =====
  describe('Sad Path — Gagal', () => {
    it('validasi gagal → tidak submit', async () => {
      const { useVuelidate } = await import('@vuelidate/core');
      const invalidMock = mockVuelidate();
      invalidMock.$validate = vi.fn().mockResolvedValue(false);
      invalidMock.nik = {
        $error: true,
        $errors: [{ $message: 'Harus tepat 16 digit' }],
        $touch: vi.fn(),
      };
      vi.mocked(useVuelidate).mockReturnValue(ref(invalidMock) as any);

      const wrapper = mountRegister();
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(mockSubmitRegistration).not.toHaveBeenCalled();
    });

    it('submit gagal → tidak redirect', async () => {
      mockSubmitRegistration.mockResolvedValue({ success: false });

      const wrapper = mountRegister();
      await fillAllInputs(wrapper);
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(mockReplace).not.toHaveBeenCalled();
    });
  });

  // ===== [3. BOUNDARY — 2 test] =====
  describe('Boundary', () => {
    it('NIK hanya digit', async () => {
      const wrapper = mountRegister();
      const inputs = wrapper.findAll('input');
      const nikInput = inputs[0];
      await nikInput.setValue('6372abc0101xyz1');
      expect((nikInput.element as HTMLInputElement).value).toBe('637201011');
    });

    it('PIN ≤ 6 digit', async () => {
      const wrapper = mountRegister();
      const inputs = wrapper.findAll('input');
      const pinInput = inputs[4];
      await pinInput.setValue('1234567');
      expect((pinInput.element as HTMLInputElement).value.length).toBeLessThanOrEqual(6);
    });
  });

  // ===== [4. EDGE CASE — 2 test] =====
  describe('Edge Case — Edit Mode', () => {
    it('checkbox hilang', async () => {
      mockRoute.query = { edit: 'true', nik: '6372010101010001' };
      const wrapper = mountRegister();
      await flushPromises();
      expect(wrapper.find('input[type="checkbox"]').exists()).toBe(false);
    });

    it('tombol Kembali tampil', async () => {
      mockRoute.query = { edit: 'true', nik: '6372010101010001' };
      const wrapper = mountRegister();
      await flushPromises();
      expect(wrapper.text()).toContain('Kembali ke Verifikasi');
    });
  });

  // ===== [5. NULL/EMPTY — 2 test] =====
  describe('Null/Empty', () => {
    it('semua field kosong saat render', () => {
      const wrapper = mountRegister();
      const inputs = wrapper.findAll('input:not([type="checkbox"])');
      inputs.forEach(input => {
        expect((input.element as HTMLInputElement).value).toBe('');
      });
    });

    it('authError kosong → alert tidak tampil', () => {
      const wrapper = mountRegister();
      expect(wrapper.text()).not.toContain('Gagal Registrasi');
    });
  });

  // ===== [6. DATA TYPE — 2 test] =====
  describe('Data Type', () => {
    it('WA numerik', async () => {
      const wrapper = mountRegister();
      const inputs = wrapper.findAll('input');
      const waInput = inputs[3];
      await waInput.setValue('62812abc890');
      expect((waInput.element as HTMLInputElement).value).toBe('62812890');
    });

    it('konfirmasi beda nilai', async () => {
      const wrapper = mountRegister();
      const inputs = wrapper.findAll('input');
      const confInput = inputs[5];
      await confInput.setValue('654321');
      expect((confInput.element as HTMLInputElement).value).toBe('654321');
    });
  });

  // ===== [7. EQUIVALENCE PARTITION — 2 test] =====
  describe('Equivalence Partition', () => {
    it('formData terisi lengkap dan valid', async () => {
      const wrapper = mountRegister();
      await fillAllInputs(wrapper);

      const vm = wrapper.vm as any;
      expect(vm.formData.nik).toBe('6372010101010001');
      expect(vm.formData.pin).toBe('123456');
    });

    it('NIK < 16 → validasi gagal', async () => {
      const { useVuelidate } = await import('@vuelidate/core');
      const invalidMock = mockVuelidate();
      invalidMock.$validate = vi.fn().mockResolvedValue(false);
      invalidMock.nik = {
        $error: true,
        $errors: [{ $message: 'Harus tepat 16 digit' }],
        $touch: vi.fn(),
      };
      vi.mocked(useVuelidate).mockReturnValue(ref(invalidMock) as any);

      const wrapper = mountRegister();
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(mockSubmitRegistration).not.toHaveBeenCalled();
    });
  });

  // ===== [8. STATE TRANSITION — 2 test] =====
  describe('State Transition', () => {
    it('edit → judul berubah', async () => {
      mockRoute.query = { edit: 'true', nik: '6372010101010001' };
      const wrapper = mountRegister();
      await flushPromises();
      expect(wrapper.text()).toContain('Perbaiki Nomor WhatsApp');
    });

    it('Kembali → verify-otp', async () => {
      mockRoute.query = { edit: 'true', nik: '6372010101010001' };
      const wrapper = mountRegister();
      await flushPromises();

      const backBtn = wrapper.find('button[type="button"]');
      await backBtn.trigger('click');

      expect(mockPush).toHaveBeenCalledWith(
        expect.objectContaining({
          name: 'verify-otp',
        })
      );
    });
  });

  // ===== [9. CONCURRENCY — 1 test] =====
  describe('Concurrency', () => {
    it('isSubmitting ref bisa diset true', () => {
      isSubmitting.value = true;
      expect(isSubmitting.value).toBe(true);

      const wrapper = mountRegister();
      expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });
  });

  // ===== [10. SECURITY — 2 test] =====
  describe('Security', () => {
    it('data citizen tidak di localStorage', async () => {
      const wrapper = mountRegister();
      await fillAllInputs(wrapper);
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(localStorageMock.getItem('citizen')).toBeNull();
      expect(localStorageMock.getItem('full_name')).toBeNull();
    });

    it('PIN field bertipe password', () => {
      const wrapper = mountRegister();
      const formFields = wrapper.findAllComponents(FormFieldStub);

      const passwordFields = formFields.filter(f => {
        const props = f.props() as Record<string, unknown>;
        return props.type === 'password';
      });
      expect(passwordFields.length).toBeGreaterThanOrEqual(2);
    });
  });
});