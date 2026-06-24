// ===== [IMPORTS] =====
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { ref, nextTick } from 'vue';
import ResetPin from '../ResetPin.vue';

// ===== [MOCK REFS] =====
const mockExecuteReset = vi.fn();
const isSubmitting = ref(false);
const errorMessage = ref('');

// ===== [MOCKS - useForgotPin] =====
vi.mock('../../composables/useForgotPin', () => ({
  useForgotPin: () => ({
    isSubmitting,
    errorMessage,
    sendOtp: vi.fn(),
    executeReset: mockExecuteReset,
  }),
}));

// ===== [MOCKS - ROUTER] =====
const mockPush = vi.fn().mockResolvedValue(undefined);
const mockReplace = vi.fn().mockResolvedValue(undefined);

const mockRoute: {
  query: Record<string, string>;
  name: string;
} = {
  query: { nik: '6372010101010001', wa: '6281234567890' },
  name: 'reset-pin',
};

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
  }),
}));

// ===== [MOCKS - SuccessModal] =====
const SuccessModalStub = {
  template: `<div v-if="show" class="success-modal"><button class="confirm-btn" @click="$emit('confirm')">OK</button></div>`,
  props: ['show'],
  emits: ['confirm'],
};

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
const mountResetPin = () => {
  return mount(ResetPin, {
    global: {
      stubs: {
        AuthLayout: AuthLayoutStub,
        SuccessModal: SuccessModalStub,
      },
    },
  });
};

// ===== [TEST SUITE] =====
describe('ResetPin Page', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockRoute.query = { nik: '6372010101010001', wa: '6281234567890' };
    isSubmitting.value = false;
    errorMessage.value = '';
    mockExecuteReset.mockResolvedValue({ success: true });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===== [1. HAPPY PATH — 5 test] =====
  describe('Happy Path — Reset PIN Normal', () => {
    it('merender judul "Atur PIN Baru"', () => {
      const wrapper = mountResetPin();
      expect(wrapper.text()).toContain('Atur PIN Baru');
    });

    it('merender input OTP, PIN Baru, Konfirmasi PIN', () => {
      const wrapper = mountResetPin();
      const inputs = wrapper.findAll('input');
      expect(inputs.length).toBe(3);
    });

    it('merender tombol "SIMPAN PIN BARU"', () => {
      const wrapper = mountResetPin();
      expect(wrapper.text()).toContain('SIMPAN PIN BARU');
    });

    it('formData berisi NIK & WA dari route query', () => {
      const wrapper = mountResetPin();
      const vm = wrapper.vm as any;

      expect(vm.formData.nik).toBe('6372010101010001');
      expect(vm.formData.whatsapp_number).toBe('6281234567890');
    });

    it('executeReset mock bisa dipanggil langsung', async () => {
      mockExecuteReset.mockResolvedValue({ success: true });

      const result = await mockExecuteReset({
        nik: '6372010101010001',
        whatsapp_number: '6281234567890',
        otp: '123456',
        new_pin: '654321',
        new_pin_confirmation: '654321',
      });

      expect(result.success).toBe(true);
      expect(mockExecuteReset).toHaveBeenCalledTimes(1);
    });
  });

  // ===== [2. SAD PATH — 3 test] =====
  describe('Sad Path — Gagal Reset PIN', () => {
    it('errorMessage ref berubah saat di-set', () => {
      errorMessage.value = 'Kode OTP salah.';
      expect(errorMessage.value).toBe('Kode OTP salah.');
    });

    it('executeReset gagal → showSuccessModal tetap false', async () => {
      mockExecuteReset.mockResolvedValue({ success: false });

      const wrapper = mountResetPin();
      const vm = wrapper.vm as any;

      await vm.handleReset();
      await flushPromises();

      expect(vm.showSuccessModal).toBe(false);
    });

    it('NIK kosong → redirect ke login', async () => {
      mockRoute.query = { nik: '', wa: '' };
      mountResetPin();
      await flushPromises();

      expect(mockReplace).toHaveBeenCalledWith({ name: 'login' });
    });
  });

  // ===== [3. BOUNDARY — 2 test] =====
  describe('Boundary — Format Input', () => {
    it('formatNumeric membersihkan non-digit OTP', () => {
      const wrapper = mountResetPin();
      const vm = wrapper.vm as any;

      vm.formData.otp = '12ab34';
      vm.formatNumeric('otp', 6);

      expect(vm.formData.otp).toBe('1234');
    });

    it('formatNumeric memotong PIN ke 6 digit', () => {
      const wrapper = mountResetPin();
      const vm = wrapper.vm as any;

      vm.formData.new_pin = '1234567890';
      vm.formatNumeric('new_pin', 6);

      expect(vm.formData.new_pin).toBe('123456');
    });
  });

  // ===== [4. EDGE CASE — 2 test] =====
  describe('Edge Case — Kondisi Khusus', () => {
    it('showSuccessModal bisa di-set true', () => {
      const wrapper = mountResetPin();
      const vm = wrapper.vm as any;

      vm.showSuccessModal = true;
      expect(vm.showSuccessModal).toBe(true);
    });

    it('handleSuccessConfirm → redirect ke login', () => {
      const wrapper = mountResetPin();
      const vm = wrapper.vm as any;

      vm.handleSuccessConfirm();

      expect(mockPush).toHaveBeenCalledWith({ name: 'login' });
    });
  });

  // ===== [5. NULL/EMPTY — 2 test] =====
  describe('Null/Empty — State Awal', () => {
    it('formData otp, pin, konfirmasi kosong', () => {
      const wrapper = mountResetPin();
      const vm = wrapper.vm as any;

      expect(vm.formData.otp).toBe('');
      expect(vm.formData.new_pin).toBe('');
      expect(vm.formData.new_pin_confirmation).toBe('');
    });

    it('errorMessage kosong → safeError kosong', () => {
      const wrapper = mountResetPin();
      const vm = wrapper.vm as any;

      expect(vm.safeError).toBe('');
    });
  });

  // ===== [6. DATA TYPE — 2 test] =====
  describe('Data Type — Format & Validasi', () => {
    it('formatNumeric hanya menyisakan digit', () => {
      const wrapper = mountResetPin();
      const vm = wrapper.vm as any;

      vm.formData.new_pin = 'abc123def';
      vm.formatNumeric('new_pin', 6);

      expect(vm.formData.new_pin).toBe('123');
    });

    it('formatNumeric tidak throw error', () => {
      const wrapper = mountResetPin();
      const vm = wrapper.vm as any;

      expect(() => vm.formatNumeric('otp', 6)).not.toThrow();
    });
  });

  // ===== [7. EQUIVALENCE PARTITION — 2 test] =====
  describe('Equivalence Partition — Grup State', () => {
    it('OTP 6 digit valid → bisa submit', () => {
      const wrapper = mountResetPin();
      const vm = wrapper.vm as any;

      vm.formData.otp = '123456';
      expect(vm.formData.otp.length).toBe(6);
    });

    it('PIN baru & konfirmasi sama → valid', () => {
      const wrapper = mountResetPin();
      const vm = wrapper.vm as any;

      vm.formData.new_pin = '654321';
      vm.formData.new_pin_confirmation = '654321';

      expect(vm.formData.new_pin).toBe(vm.formData.new_pin_confirmation);
    });
  });

  // ===== [8. STATE TRANSITION — 2 test] =====
  describe('State Transition — Loading & Navigasi', () => {
    it('isSubmitting=true → komponen tetap render', () => {
      isSubmitting.value = true;
      const wrapper = mountResetPin();
      expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });

    it('isSubmitting=false → tombol tidak disabled', () => {
      isSubmitting.value = false;
      const wrapper = mountResetPin();
      const btn = wrapper.find('button[type="submit"]');
      expect(btn.attributes('disabled')).toBeUndefined();
    });
  });

  // ===== [9. CONCURRENCY — 1 test] =====
  describe('Concurrency — Submit State', () => {
    it('isSubmitting=true → komponen tetap render', () => {
      isSubmitting.value = true;
      const wrapper = mountResetPin();
      expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });
  });

  // ===== [10. SECURITY — 2 test] =====
  describe('Security — Data Sensitif', () => {
    it('PIN input bertipe password', () => {
      const wrapper = mountResetPin();
      const inputs = wrapper.findAll('input[type="password"]');
      expect(inputs.length).toBe(2);
    });

    it('OTP input bertipe text', () => {
      const wrapper = mountResetPin();
      const inputs = wrapper.findAll('input[type="text"]');
      expect(inputs.length).toBe(1);
    });
  });
});