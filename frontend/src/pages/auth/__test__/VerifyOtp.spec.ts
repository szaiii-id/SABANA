// ===== [IMPORTS] =====
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { nextTick } from 'vue';
import VerifyOtp from '../VerifyOtp.vue';
import AuthService from '../../../services/AuthService';

// ===== [MOCKS - ROUTER] =====
const mockPush = vi.fn().mockResolvedValue(undefined);
const mockReplace = vi.fn().mockResolvedValue(undefined);

const mockRoute: Record<string, any> = {
  query: { nik: '6372010101010001', wa: '6281234567890' },
  name: 'verify-otp',
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

// ===== [MOCKS - errorHandler] =====
vi.mock('../../utils/errorHandler', () => ({
  getSafeErrorMessage: (msg: string) => msg || '',
}));

// ===== [MOCK setInterval & setTimeout] =====
vi.useFakeTimers();

// ===== [HELPERS] =====
const mountVerifyOtp = () => {
  return mount(VerifyOtp, {
    global: {
      stubs: {
        SuccessModal: SuccessModalStub,
        Transition: { template: '<div><slot /></div>' },
      },
    },
  });
};

// ===== [TEST SUITE] =====
describe('VerifyOtp Page', () => {
  let verifyOtpSpy: ReturnType<typeof vi.fn>;
  let resendOtpSpy: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    vi.clearAllMocks();
    mockRoute.query = { nik: '6372010101010001', wa: '6281234567890' };

    // Spy langsung ke method AuthService
    verifyOtpSpy = vi.fn().mockResolvedValue({ status: 'success' });
    resendOtpSpy = vi.fn().mockResolvedValue({ status: 'success' });

    vi.spyOn(AuthService, 'verifyOtp').mockImplementation(verifyOtpSpy as any);
    vi.spyOn(AuthService, 'resendOtp').mockImplementation(resendOtpSpy as any);
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.clearAllTimers();
  });

  // ===== [1. HAPPY PATH — 5 test] =====
  describe('Happy Path — Verifikasi Normal', () => {
    it('merender judul "Verifikasi OTP"', () => {
      const wrapper = mountVerifyOtp();
      expect(wrapper.text()).toContain('Verifikasi OTP');
    });

    it('merender 6 input OTP', () => {
      const wrapper = mountVerifyOtp();
      const inputs = wrapper.findAll('input');
      expect(inputs.length).toBe(6);
    });

    it('menampilkan nomor WA tersamarkan (****)', () => {
      const wrapper = mountVerifyOtp();
      expect(wrapper.text()).toContain('****');
      expect(wrapper.text()).not.toContain('6281234567890');
    });

    it('OTP lengkap 6 digit → tombol submit aktif', async () => {
      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;
      vm.otpArray = ['1', '2', '3', '4', '5', '6'];
      await nextTick();

      const btn = wrapper.find('button[type="submit"]');
      expect(btn.attributes('disabled')).toBeUndefined();
    });

    it('verifyOtpCode sukses → showSuccessModal = true', async () => {
      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      vm.otpArray = ['1', '2', '3', '4', '5', '6'];
      vm.isLoading = false;
      vm.errorMessage = '';

      await vm.verifyOtpCode();
      await flushPromises();
      await nextTick();

      expect(verifyOtpSpy).toHaveBeenCalledWith({
        nik: '6372010101010001',
        whatsapp_number: '6281234567890',
        otp: '123456',
      });
      expect(vm.showSuccessModal).toBe(true);
    });
  });

  // ===== [2. SAD PATH — 3 test] =====
  describe('Sad Path — Gagal Verifikasi', () => {
    it('OTP salah → errorMessage terisi', async () => {
      verifyOtpSpy.mockRejectedValue({
        response: { data: { message: 'Kode OTP salah.' } },
      });

      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      vm.otpArray = ['9', '9', '9', '9', '9', '9'];
      await vm.verifyOtpCode();
      await flushPromises();
      await nextTick();

      expect(vm.errorMessage).toBe('Kode OTP salah.');
    });

    it('OTP salah → otpArray di-reset', async () => {
      verifyOtpSpy.mockRejectedValue({
        response: { data: { message: 'Kode OTP salah.' } },
      });

      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      vm.otpArray = ['1', '2', '3', '4', '5', '6'];
      await vm.verifyOtpCode();
      await flushPromises();
      await nextTick();

      expect(vm.otpArray).toEqual(['', '', '', '', '', '']);
    });

    it('resendOtp gagal → errorMessage terisi', async () => {
      resendOtpSpy.mockRejectedValue({
        response: { data: { message: 'Gagal mengirim ulang kode OTP.' } },
      });

      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      vm.timer = 0;
      vm.isResending = false;
      vm.errorMessage = '';

      await vm.resendOtp();
      await flushPromises();
      await nextTick();

      expect(vm.errorMessage).toBe('Gagal mengirim ulang kode OTP.');
    });
  });

  // ===== [3. BOUNDARY — 2 test] =====
  describe('Boundary — Batasan Input', () => {
    it('handleOtpInput membersihkan non-digit', () => {
      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      vm.handleOtpInput(0, { target: { value: 'a1b2' } });
      expect(vm.otpArray[0]).toBe('12');
    });

    it('paste 6 digit → isi OTP array', () => {
      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      vm.handlePaste({
        preventDefault: vi.fn(),
        clipboardData: { getData: () => '123456' },
      });

      expect(vm.otpArray).toEqual(['1', '2', '3', '4', '5', '6']);
    });
  });

  // ===== [4. EDGE CASE — 2 test] =====
  describe('Edge Case — Kondisi Khusus', () => {
    it('NIK kosong → redirect ke register', async () => {
      mockRoute.query = { nik: '', wa: '6281234567890' };
      mountVerifyOtp();
      await flushPromises();

      expect(mockReplace).toHaveBeenCalledWith({ name: 'register' });
    });

    it('nomor WA pendek (< 8 digit) tidak di-mask', async () => {
      mockRoute.query = { nik: '6372010101010001', wa: '62812' };
      const wrapper = mountVerifyOtp();
      await flushPromises();

      expect(wrapper.text()).toContain('62812');
      expect(wrapper.text()).not.toContain('****');
    });
  });

  // ===== [5. NULL/EMPTY — 2 test] =====
  describe('Null/Empty — State Awal', () => {
    it('OTP kosong → tombol submit disabled', () => {
      const wrapper = mountVerifyOtp();
      const btn = wrapper.find('button[type="submit"]');
      expect(btn.attributes('disabled')).toBeDefined();
    });

    it('errorMessage kosong → tidak ada pesan error', () => {
      const wrapper = mountVerifyOtp();
      expect(wrapper.text()).not.toContain('Kode OTP salah');
    });
  });

  // ===== [6. DATA TYPE — 2 test] =====
  describe('Data Type — Format Input', () => {
    it('handleOtpInput membersihkan karakter non-digit', () => {
      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      vm.handleOtpInput(0, { target: { value: 'a' } });
      expect(vm.otpArray[0]).toBe('');
    });

    it('handleOtpKeydown Backspace tidak error', () => {
      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      vm.otpArray = ['', '', '', '', '', ''];

      expect(() => {
        vm.handleOtpKeydown(1, { key: 'Backspace' });
      }).not.toThrow();
    });
  });

  // ===== [7. EQUIVALENCE PARTITION — 2 test] =====
  describe('Equivalence Partition — Grup State', () => {
    it('timer awal = 60 → tombol resend disabled', () => {
      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      expect(vm.timer).toBe(60);

      const buttons = wrapper.findAll('button[type="button"]');
      const resendBtn = buttons[buttons.length - 1];
      expect(resendBtn.attributes('disabled')).toBeDefined();
    });

    it('timer = 0 → tombol resend aktif', async () => {
      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      vm.timer = 0;
      await nextTick();

      const buttons = wrapper.findAll('button[type="button"]');
      const resendBtn = buttons[buttons.length - 1];
      expect(resendBtn.attributes('disabled')).toBeUndefined();
    });
  });

  // ===== [8. STATE TRANSITION — 2 test] =====
  describe('State Transition — Perubahan State', () => {
    it('resendOtp sukses → successMessage terisi', async () => {
      resendOtpSpy.mockResolvedValue({ status: 'success' });

      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      vm.timer = 0;
      vm.isResending = false;
      vm.successMessage = '';

      await vm.resendOtp();
      await flushPromises();
      await nextTick();

      expect(vm.successMessage).toBeTruthy();
      expect(wrapper.text()).toContain('Kode OTP baru telah dikirim');
    });

    it('handleSuccessConfirm → redirect ke login', () => {
      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      vm.showSuccessModal = true;
      vm.handleSuccessConfirm();

      expect(vm.showSuccessModal).toBe(false);
      expect(mockPush).toHaveBeenCalledWith({ name: 'login' });
    });
  });

  // ===== [9. CONCURRENCY — 1 test] =====
  describe('Concurrency — Race Condition', () => {
    it('isLoading=true saat verifyOtpCode dipanggil', async () => {
      verifyOtpSpy.mockImplementation(() => new Promise(r => setTimeout(r, 5000)));

      const wrapper = mountVerifyOtp();
      const vm = wrapper.vm as any;

      vm.otpArray = ['1', '2', '3', '4', '5', '6'];

      const promise = vm.verifyOtpCode();
      await nextTick();

      expect(vm.isLoading).toBe(true);

      vi.advanceTimersByTime(5000);
      await promise;

      expect(vm.isLoading).toBe(false);
    });
  });

  // ===== [10. SECURITY — 2 test] =====
  describe('Security — Data Sensitif', () => {
    it('nomor WA di-mask dengan ****', () => {
      const wrapper = mountVerifyOtp();
      expect(wrapper.text()).toContain('****');
      expect(wrapper.text()).not.toContain('6281234567890');
    });

    it('changePhoneNumber → redirect ke register?edit=true', async () => {
      const wrapper = mountVerifyOtp();
      const buttons = wrapper.findAll('button[type="button"]');
      const changeWaBtn = buttons[0];

      await changeWaBtn.trigger('click');

      expect(mockPush).toHaveBeenCalledWith({
        name: 'register',
        query: { nik: '6372010101010001', wa: '6281234567890', edit: 'true' },
      });
    });
  });
});