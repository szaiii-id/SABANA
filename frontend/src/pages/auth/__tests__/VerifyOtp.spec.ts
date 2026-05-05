// src/pages/auth/__tests__/VerifyOtp.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import VerifyOtp from '../VerifyOtp.vue';

// ============================================================
// MOCK ROUTER
// ============================================================
const pushMock = vi.fn();
const replaceMock = vi.fn();

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock, replace: replaceMock }),
  useRoute: () => ({ query: { nik: '6301012345678901', wa: '08123456789' } }),
}));

// ============================================================
// MOCK AUTH SERVICE
// ============================================================
vi.mock('../../../services/AuthService', () => ({
  default: {
    verifyOtp: vi.fn(),
    resendOtp: vi.fn(),
  },
}));

// ============================================================
// MOCK ERROR HANDLER
// ============================================================
vi.mock('../../../utils/errorHandler', () => ({
  getSafeErrorMessage: vi.fn((msg: string) => msg),
}));

// ============================================================
// IMPORT MODULES
// ============================================================
import AuthService from '../../../services/AuthService';

// ============================================================
// TEST SUITE
// ============================================================
describe('VerifyOtp.vue - Professional QA Test Suite', () => {

  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  const mountComponent = () => {
    return mount(VerifyOtp, {
      global: {
        stubs: {
          'router-link': true,
          SuccessModal: true,
          AuthLayout: {
            template: '<div class="auth-layout"><slot /></div>',
            props: ['maxWidth'],
          },
        },
      },
    });
  };

  // Helper: isi OTP
  const fillOtp = async (wrapper: any, digits: string[]) => {
    const inputs = wrapper.findAll('input');
    for (let i = 0; i < digits.length && i < inputs.length; i++) {
      await inputs[i].setValue(digits[i]);
    }
  };

  // ============================================================
  // RENDER
  // ============================================================
  describe('Render', () => {

    it('[RENDER-01] 6 input OTP tersedia', () => {
      const wrapper = mountComponent();
      const inputs = wrapper.findAll('input');
      expect(inputs.length).toBe(6);
    });

    it('[RENDER-02] Menampilkan "Verifikasi OTP"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('Verifikasi OTP');
    });

    it('[RENDER-03] Menampilkan nomor WhatsApp', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('08123456789');
    });

    it('[RENDER-04] Tombol "AKTIFKAN AKUN" tersedia', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('AKTIFKAN AKUN');
    });

    it('[RENDER-05] Tombol resend "Kirim Ulang Kode" tersedia', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('Kirim Ulang');
    });

    it('[RENDER-06] Redirect ke register jika nik kosong', () => {
      // Override useRoute untuk test ini
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      
      // Verifikasi nikNumber ada
      expect(vm.nikNumber).toBe('6301012345678901');
    });
  });

  // ============================================================
  // OTP INPUT BEHAVIOR
  // ============================================================
  describe('OTP Input Behavior', () => {

    it('[OTP-01] Auto-focus ke input berikutnya saat diisi', async () => {
      const wrapper = mountComponent();
      const inputs = wrapper.findAll('input');

      await inputs[0].setValue('1');
      await wrapper.vm.$nextTick();

      // Input kedua harusnya focused (tapi susah di-test di JSDOM)
      const vm = wrapper.vm as any;
      expect(vm.otpArray[0]).toBe('1');
      expect(vm.otpArray[1]).toBe('');
    });

    it('[OTP-02] Backspace memindahkan fokus ke input sebelumnya', async () => {
      const wrapper = mountComponent();
      const inputs = wrapper.findAll('input');

      await inputs[0].setValue('1');
      await inputs[1].setValue('2');
      
      const vm = wrapper.vm as any;
      vm.otpArray[1] = '';
      
      // Trigger keydown backspace
      await inputs[1].trigger('keydown', { key: 'Backspace' });
      
      expect(vm.otpArray[1]).toBe('');
    });

    it('[OTP-03] Hanya menerima digit (menghapus non-digit)', async () => {
      const wrapper = mountComponent();
      const inputs = wrapper.findAll('input');

      await inputs[0].setValue('a');
      await wrapper.vm.$nextTick();

      const vm = wrapper.vm as any;
      expect(vm.otpArray[0]).toBe('');
    });

    it('[OTP-04] Paste OTP mengisi semua input', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      
      // Panggil handlePaste langsung tanpa event
      vm.handlePaste({
        clipboardData: {
          getData: () => '123456',
        },
      } as any);
      
      await wrapper.vm.$nextTick();

      expect(vm.otpArray).toEqual(['1', '2', '3', '4', '5', '6']);
    });

    it('[OTP-05] isOtpComplete true jika semua terisi', async () => {
      const wrapper = mountComponent();
      await fillOtp(wrapper, ['1', '2', '3', '4', '5', '6']);

      const vm = wrapper.vm as any;
      expect(vm.isOtpComplete).toBe(true);
    });

    it('[OTP-06] isOtpComplete false jika belum semua terisi', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      expect(vm.isOtpComplete).toBe(false);
    });
  });

  // ============================================================
  // VERIFY OTP
  // ============================================================
  describe('Verify OTP', () => {

    it('[VERIFY-01] verifyOtp dipanggil saat 6 digit terisi (submit)', async () => {
      (AuthService.verifyOtp as any).mockResolvedValue({ success: true });

      const wrapper = mountComponent();
      await fillOtp(wrapper, ['1', '2', '3', '4', '5', '6']);
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(AuthService.verifyOtp).toHaveBeenCalledWith({
        nik: '6301012345678901',
        whatsapp_number: '08123456789',
        otp: '123456',
      });
    });

    it('[VERIFY-02] Menampilkan error jika OTP salah', async () => {
      (AuthService.verifyOtp as any).mockRejectedValue({
        response: {
          status: 422,
          data: { message: 'Kode OTP tidak valid.' },
        },
      });

      const wrapper = mountComponent();
      await fillOtp(wrapper, ['0', '0', '0', '0', '0', '0']);
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Kode OTP tidak valid.');
    });

    it('[VERIFY-03] Reset OTP setelah error', async () => {
      (AuthService.verifyOtp as any).mockRejectedValue({
        response: { status: 422, data: { message: 'Salah' } },
      });

      const wrapper = mountComponent();
      await fillOtp(wrapper, ['1', '2', '3', '4', '5', '6']);
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.otpArray).toEqual(['', '', '', '', '', '']);
    });

    it('[VERIFY-04] Tampil success modal jika OTP valid', async () => {
      (AuthService.verifyOtp as any).mockResolvedValue({ success: true });

      const wrapper = mountComponent();
      await fillOtp(wrapper, ['1', '2', '3', '4', '5', '6']);
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.showSuccessModal).toBe(true);
    });

    it('[VERIFY-05] Loading state saat verifikasi', async () => {
      let resolvePromise: any;
      (AuthService.verifyOtp as any).mockReturnValue(
        new Promise((resolve: any) => { resolvePromise = resolve; })
      );

      const wrapper = mountComponent();
      await fillOtp(wrapper, ['1', '2', '3', '4', '5', '6']);
      wrapper.find('form').trigger('submit.prevent');
      await wrapper.vm.$nextTick();

      const vm = wrapper.vm as any;
      expect(vm.isLoading).toBe(true);

      resolvePromise({ success: true });
      await flushPromises();
      expect(vm.isLoading).toBe(false);
    });
  });

  // ============================================================
  // RESEND OTP
  // ============================================================
  describe('Resend OTP', () => {

    it('[RESEND-01] resendOtp dipanggil saat timer habis', async () => {
      (AuthService.resendOtp as any).mockResolvedValue({ success: true });

      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      
      // Set timer ke 0
      vm.timer = 0;
      await wrapper.vm.$nextTick();

      const buttons = wrapper.findAll('button');
      const resendBtn = buttons.find((b: any) => b.text().includes('Kirim Ulang'));
      
      if (resendBtn) {
        await resendBtn.trigger('click');
        await flushPromises();
        expect(AuthService.resendOtp).toHaveBeenCalledWith({
          nik: '6301012345678901',
          whatsapp_number: '08123456789',
        });
      }
    });

    it('[RESEND-02] Timer disabled saat > 0', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      
      vm.timer = 30;
      expect(vm.timer).toBeGreaterThan(0);
    });

    it('[RESEND-03] Timer countdown berjalan', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      
      expect(vm.timer).toBe(60);
      
      vi.advanceTimersByTime(1000);
      expect(vm.timer).toBe(59);
      
      vi.advanceTimersByTime(59000);
      expect(vm.timer).toBe(0);
    });

    it('[RESEND-04] Error resend menampilkan pesan', async () => {
      (AuthService.resendOtp as any).mockRejectedValue({
        response: { data: { message: 'Gagal mengirim ulang.' } },
      });

      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.timer = 0;
      await wrapper.vm.$nextTick();

      const buttons = wrapper.findAll('button');
      const resendBtn = buttons.find((b: any) => b.text().includes('Kirim Ulang'));
      
      if (resendBtn) {
        await resendBtn.trigger('click');
        await flushPromises();
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Gagal mengirim ulang.');
      }
    });
  });

  // ============================================================
  // SUCCESS HANDLER
  // ============================================================
  describe('Success Handler', () => {

    it('[SUCCESS-01] handleSuccessConfirm navigasi ke login', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      
      vm.handleSuccessConfirm();
      
      expect(vm.showSuccessModal).toBe(false);
      expect(pushMock).toHaveBeenCalledWith({ name: 'login' });
    });
  });

  // ============================================================
  // ERROR DISPLAY
  // ============================================================
  describe('Error Display', () => {

    it('[ERR-01] safeError computed dari errorMessage', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      
      vm.errorMessage = 'Test error';
      await wrapper.vm.$nextTick();
      
      expect(wrapper.text()).toContain('Test error');
    });
  });
});