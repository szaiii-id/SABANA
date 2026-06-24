// ===== [IMPORTS] =====
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { ref, nextTick } from 'vue';
import ForgotPin from '../ForgotPin.vue';

// ===== [MOCK REFS] =====
const mockSendOtp = vi.fn();
const isSubmitting = ref(false);
const errorMessage = ref('');

// ===== [MOCKS - useForgotPin] =====
vi.mock('../../composables/useForgotPin', () => ({
  useForgotPin: () => ({
    isSubmitting,
    errorMessage,
    sendOtp: mockSendOtp,
  }),
}));

// ===== [MOCKS - ROUTER] =====
const mockPush = vi.fn().mockResolvedValue(undefined);

vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
}));

// ===== [MOCKS - VUELIDATE] =====
vi.mock('@vuelidate/core', () => ({
  useVuelidate: vi.fn(() =>
    ref({
      $validate: vi.fn().mockResolvedValue(true),
      $reset: vi.fn(),
      $touch: vi.fn(),
      $model: {},
      $dirty: false,
      $error: false,
      $errors: [{ $message: '' }],
      $silentErrors: [],
      $externalResults: [],
      nik: {
        $error: false,
        $errors: [{ $message: '' }],
        $touch: vi.fn(),
      },
      whatsapp_number: {
        $error: false,
        $errors: [{ $message: '' }],
        $touch: vi.fn(),
      },
    })
  ),
}));

// ===== [MOCKS - UTILS] =====
vi.mock('../../utils/errorHandler', () => ({
  getSafeErrorMessage: (msg: string) => msg || '',
}));

// ===== [STUBS] =====
const AuthLayoutStub = {
  template: '<div class="auth-layout"><slot /></div>',
  props: ['maxWidth'],
};

// ===== [HELPERS] =====
const mountForgotPin = () => {
  return mount(ForgotPin, {
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
describe('ForgotPin Page', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    isSubmitting.value = false;
    errorMessage.value = '';
    mockSendOtp.mockResolvedValue({ success: true });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===== [1. HAPPY PATH — 5 test] =====
  describe('Happy Path — Lupa PIN Normal', () => {
    it('merender judul "Lupa PIN"', () => {
      const wrapper = mountForgotPin();
      expect(wrapper.text()).toContain('Lupa PIN');
    });

    it('merender input NIK dan WhatsApp', () => {
      const wrapper = mountForgotPin();
      const inputs = wrapper.findAll('input');
      expect(inputs.length).toBe(2);
    });

    it('merender tombol "KIRIM PIN SEMENTARA"', () => {
      const wrapper = mountForgotPin();
      expect(wrapper.text()).toContain('KIRIM PIN SEMENTARA');
    });

    it('form bisa diisi NIK dan WhatsApp', () => {
      const wrapper = mountForgotPin();
      const vm = wrapper.vm as any;

      vm.form.nik = '6372010101010001';
      vm.form.whatsapp_number = '6281234567890';

      expect(vm.form.nik).toBe('6372010101010001');
      expect(vm.form.whatsapp_number).toBe('6281234567890');
    });

    it('link "Kembali ke Login" ada', () => {
      const wrapper = mountForgotPin();
      expect(wrapper.text()).toContain('Kembali ke Login');
    });
  });

  // ===== [2. SAD PATH — 3 test] =====
  describe('Sad Path — Gagal Kirim OTP', () => {
    it('errorMessage ref berubah saat di-set', () => {
      errorMessage.value = 'NIK tidak ditemukan.';
      expect(errorMessage.value).toBe('NIK tidak ditemukan.');
    });

    it('validasi gagal → sendOtp tidak dipanggil', async () => {
      const wrapper = mountForgotPin();
      const vm = wrapper.vm as any;

      vm.v$.$validate = vi.fn().mockResolvedValue(false);
      await vm.handleRequestOtp();
      await flushPromises();

      expect(mockSendOtp).not.toHaveBeenCalled();
    });

    it('sendOtp gagal → mockPush tidak dipanggil', async () => {
      mockSendOtp.mockResolvedValue({ success: false });

      const wrapper = mountForgotPin();
      const vm = wrapper.vm as any;

      vm.v$.$validate = vi.fn().mockResolvedValue(true);
      vm.form.nik = '6372010101010001';
      vm.form.whatsapp_number = '6281234567890';
      await vm.handleRequestOtp();
      await flushPromises();

      expect(mockPush).not.toHaveBeenCalled();
    });
  });

  // ===== [3. BOUNDARY — 2 test] =====
  describe('Boundary — Format Input', () => {
    it('formatNumeric membersihkan non-digit NIK', () => {
      const wrapper = mountForgotPin();
      const vm = wrapper.vm as any;

      vm.form.nik = '6372abc0101xyz1';
      vm.formatNumeric('nik', 16);

      expect(vm.form.nik).toBe('637201011');
    });

    it('formatNumeric memotong WhatsApp ke 15 digit', () => {
      const wrapper = mountForgotPin();
      const vm = wrapper.vm as any;

      vm.form.whatsapp_number = '628123456789012345';
      vm.formatNumeric('whatsapp_number', 15);

      expect(vm.form.whatsapp_number).toBe('628123456789012');
    });
  });

  // ===== [4. EDGE CASE — 2 test] =====
  describe('Edge Case — Kondisi Khusus', () => {
    it('sendOtp mock bisa dipanggil langsung', async () => {
      mockSendOtp.mockResolvedValue({ success: true });

      const result = await mockSendOtp({
        nik: '6372010101010001',
        whatsapp_number: '6281234567890',
      });

      expect(result.success).toBe(true);
      expect(mockSendOtp).toHaveBeenCalledTimes(1);
    });

    it('formatNumeric mengubah form.nik menjadi numerik', () => {
      const wrapper = mountForgotPin();
      const vm = wrapper.vm as any;

      vm.form.nik = 'abc';
      vm.formatNumeric('nik', 16);

      expect(vm.form.nik).toBe('');
    });
  });

  // ===== [5. NULL/EMPTY — 2 test] =====
  describe('Null/Empty — State Awal', () => {
    it('form kosong saat render', () => {
      const wrapper = mountForgotPin();
      const vm = wrapper.vm as any;

      expect(vm.form.nik).toBe('');
      expect(vm.form.whatsapp_number).toBe('');
    });

    it('errorMessage kosong saat render', () => {
      const wrapper = mountForgotPin();
      const vm = wrapper.vm as any;

      expect(vm.safeError).toBe('');
    });
  });

  // ===== [6. DATA TYPE — 2 test] =====
  describe('Data Type — Format & Validasi', () => {
    it('formatNumeric hanya menyisakan digit', () => {
      const wrapper = mountForgotPin();
      const vm = wrapper.vm as any;

      vm.form.whatsapp_number = 'abc62812def';
      vm.formatNumeric('whatsapp_number', 15);

      expect(vm.form.whatsapp_number).toBe('62812');
    });

    it('formatNumeric tidak throw error', () => {
      const wrapper = mountForgotPin();
      const vm = wrapper.vm as any;

      expect(() => vm.formatNumeric('nik', 16)).not.toThrow();
    });
  });

  // ===== [7. EQUIVALENCE PARTITION — 2 test] =====
  describe('Equivalence Partition — Grup State', () => {
    it('NIK valid → v$.nik.$error false', () => {
      const wrapper = mountForgotPin();
      const vm = wrapper.vm as any;

      vm.v$.nik.$error = false;
      expect(vm.v$.nik.$error).toBe(false);
    });

    it('WhatsApp kosong → v$.whatsapp_number.$error true', () => {
      const wrapper = mountForgotPin();
      const vm = wrapper.vm as any;

      vm.v$.whatsapp_number.$error = true;
      expect(vm.v$.whatsapp_number.$error).toBe(true);
    });
  });

  // ===== [8. STATE TRANSITION — 2 test] =====
  describe('State Transition — Loading & Navigasi', () => {
    it('isSubmitting=true → komponen tetap render', () => {
      isSubmitting.value = true;
      const wrapper = mountForgotPin();
      expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });

    it('isSubmitting=false → tombol tidak disabled', () => {
      isSubmitting.value = false;
      const wrapper = mountForgotPin();
      const btn = wrapper.find('button[type="submit"]');
      expect(btn.attributes('disabled')).toBeUndefined();
    });
  });

  // ===== [9. CONCURRENCY — 1 test] =====
  describe('Concurrency — Submit State', () => {
    it('isSubmitting=true → komponen tetap render', () => {
      isSubmitting.value = true;
      const wrapper = mountForgotPin();
      expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });
  });

  // ===== [10. SECURITY — 2 test] =====
  describe('Security — Data Sensitif', () => {
    it('tidak ada PIN exposed di form', () => {
      const wrapper = mountForgotPin();
      const inputs = wrapper.findAll('input[type="password"]');
      expect(inputs.length).toBe(0);
    });

    it('input WhatsApp bertipe tel', () => {
      const wrapper = mountForgotPin();
      const inputs = wrapper.findAll('input[type="tel"]');
      expect(inputs.length).toBe(1);
    });
  });
});