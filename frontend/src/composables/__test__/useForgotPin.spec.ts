// src/composables/__test__/useForgotPin.spec.ts
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useForgotPin } from '../useForgotPin';
import AuthService from '../../services/AuthService';

// ============================================================
// MOCKS
// ============================================================
vi.mock('../../services/AuthService', () => ({
  default: {
    requestForgotPinOtp: vi.fn(),
    resetPin: vi.fn(),
  },
}));

// ============================================================
// TEST SUITE
// ============================================================
describe('useForgotPin - Professional QA Test Suite', () => {

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ============================================================
  // INITIAL STATE
  // ============================================================
  describe('Initial State', () => {

    it('[INIT-01] isSubmitting default false', () => {
      const { isSubmitting } = useForgotPin();
      expect(isSubmitting.value).toBe(false);
    });

    it('[INIT-02] isResending default false', () => {
      const { isResending } = useForgotPin();
      expect(isResending.value).toBe(false);
    });

    it('[INIT-03] errorMessage default kosong', () => {
      const { errorMessage } = useForgotPin();
      expect(errorMessage.value).toBe('');
    });
  });

  // ============================================================
  // sendOtp
  // ============================================================
  describe('sendOtp', () => {

    const payload = { nik: '6301234567890123', whatsapp_number: '08123456789' };

    it('[SEND-01] Memanggil AuthService.requestForgotPinOtp dengan payload benar', async () => {
      const { sendOtp } = useForgotPin();
      (AuthService.requestForgotPinOtp as any).mockResolvedValue({});

      await sendOtp(payload);

      expect(AuthService.requestForgotPinOtp).toHaveBeenCalledTimes(1);
      expect(AuthService.requestForgotPinOtp).toHaveBeenCalledWith(payload);
    });

    it('[SEND-02] Return success true jika berhasil', async () => {
      const { sendOtp } = useForgotPin();
      (AuthService.requestForgotPinOtp as any).mockResolvedValue({});

      const result = await sendOtp(payload);

      expect(result.success).toBe(true);
    });

    it('[SEND-03] Return success false jika gagal', async () => {
      const { sendOtp } = useForgotPin();
      (AuthService.requestForgotPinOtp as any).mockRejectedValue({
        response: { data: { message: 'User tidak ditemukan' } }
      });

      const result = await sendOtp({ nik: '000', whatsapp_number: '000' });

      expect(result.success).toBe(false);
    });

    it('[SEND-04] Set errorMessage jika gagal', async () => {
      const { sendOtp, errorMessage } = useForgotPin();
      (AuthService.requestForgotPinOtp as any).mockRejectedValue({
        response: { data: { message: 'User tidak ditemukan' } }
      });

      await sendOtp(payload);

      expect(errorMessage.value).toBe('User tidak ditemukan');
    });

    it('[SEND-05] Error message default jika tidak ada response', async () => {
      const { sendOtp, errorMessage } = useForgotPin();
      (AuthService.requestForgotPinOtp as any).mockRejectedValue(new Error('Network Error'));

      await sendOtp(payload);

      expect(errorMessage.value).toBe('Gagal mengirim OTP.');
    });

    it('[SEND-06] isSubmitting true selama proses', async () => {
      const { sendOtp, isSubmitting } = useForgotPin();
      
      let resolvePromise: any;
      (AuthService.requestForgotPinOtp as any).mockReturnValue(
        new Promise((resolve: any) => { resolvePromise = resolve; })
      );

      const promise = sendOtp(payload);
      expect(isSubmitting.value).toBe(true);

      resolvePromise({});
      await promise;

      expect(isSubmitting.value).toBe(false);
    });

    it('[SEND-07] isSubmitting false setelah selesai (sukses)', async () => {
      const { sendOtp, isSubmitting } = useForgotPin();
      (AuthService.requestForgotPinOtp as any).mockResolvedValue({});

      await sendOtp(payload);

      expect(isSubmitting.value).toBe(false);
    });

    it('[SEND-08] isSubmitting false setelah selesai (gagal)', async () => {
      const { sendOtp, isSubmitting } = useForgotPin();
      (AuthService.requestForgotPinOtp as any).mockRejectedValue(new Error('Error'));

      await sendOtp(payload);

      expect(isSubmitting.value).toBe(false);
    });

    it('[SEND-09] isResending true selama proses', async () => {
      const { sendOtp, isResending } = useForgotPin();
      
      let resolvePromise: any;
      (AuthService.requestForgotPinOtp as any).mockReturnValue(
        new Promise((resolve: any) => { resolvePromise = resolve; })
      );

      const promise = sendOtp(payload);
      expect(isResending.value).toBe(true);

      resolvePromise({});
      await promise;

      expect(isResending.value).toBe(false);
    });

    it('[SEND-10] errorMessage direset sebelum request', async () => {
      const { sendOtp, errorMessage } = useForgotPin();
      
      errorMessage.value = 'Error lama';
      (AuthService.requestForgotPinOtp as any).mockResolvedValue({});

      await sendOtp(payload);

      expect(errorMessage.value).toBe('');
    });
  });

  // ============================================================
  // executeReset
  // ============================================================
  describe('executeReset', () => {

    const payload = {
      nik: '6301234567890123',
      whatsapp_number: '08123456789',
      otp: '123456',
      new_pin: '654321',
      new_pin_confirmation: '654321',
    };

    it('[RESET-01] Memanggil AuthService.resetPin dengan payload benar', async () => {
      const { executeReset } = useForgotPin();
      (AuthService.resetPin as any).mockResolvedValue({});

      await executeReset(payload);

      expect(AuthService.resetPin).toHaveBeenCalledTimes(1);
      expect(AuthService.resetPin).toHaveBeenCalledWith(payload);
    });

    it('[RESET-02] Return success true jika berhasil', async () => {
      const { executeReset } = useForgotPin();
      (AuthService.resetPin as any).mockResolvedValue({});

      const result = await executeReset(payload);

      expect(result.success).toBe(true);
    });

    it('[RESET-03] Return success false jika gagal', async () => {
      const { executeReset } = useForgotPin();
      (AuthService.resetPin as any).mockRejectedValue({
        response: { data: { message: 'OTP expired' } }
      });

      const result = await executeReset(payload);

      expect(result.success).toBe(false);
    });

    it('[RESET-04] Set errorMessage jika gagal', async () => {
      const { executeReset, errorMessage } = useForgotPin();
      (AuthService.resetPin as any).mockRejectedValue({
        response: { data: { message: 'OTP expired' } }
      });

      await executeReset(payload);

      expect(errorMessage.value).toBe('OTP expired');
    });

    it('[RESET-05] Error message default jika network error', async () => {
      const { executeReset, errorMessage } = useForgotPin();
      (AuthService.resetPin as any).mockRejectedValue(new Error('Network Error'));

      await executeReset(payload);

      expect(errorMessage.value).toBe('Gagal meriset PIN.');
    });

    it('[RESET-06] isSubmitting true selama proses', async () => {
      const { executeReset, isSubmitting } = useForgotPin();
      
      let resolvePromise: any;
      (AuthService.resetPin as any).mockReturnValue(
        new Promise((resolve: any) => { resolvePromise = resolve; })
      );

      const promise = executeReset(payload);
      expect(isSubmitting.value).toBe(true);

      resolvePromise({});
      await promise;

      expect(isSubmitting.value).toBe(false);
    });

    it('[RESET-07] isSubmitting false setelah selesai', async () => {
      const { executeReset, isSubmitting } = useForgotPin();
      (AuthService.resetPin as any).mockResolvedValue({});

      await executeReset(payload);

      expect(isSubmitting.value).toBe(false);
    });

    it('[RESET-08] errorMessage direset sebelum request', async () => {
      const { executeReset, errorMessage } = useForgotPin();
      
      errorMessage.value = 'Error lama';
      (AuthService.resetPin as any).mockResolvedValue({});

      await executeReset(payload);

      expect(errorMessage.value).toBe('');
    });
  });
});