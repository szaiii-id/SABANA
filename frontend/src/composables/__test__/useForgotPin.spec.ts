import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useForgotPin } from '../useForgotPin';
import AuthService from '../../services/AuthService';

vi.mock('../../services/AuthService', () => ({
  default: {
    requestForgotPinOtp: vi.fn(),
    resetPin: vi.fn(),
  },
}));

describe('useForgotPin', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('sendOtp success', async () => {
    const { sendOtp, isSubmitting } = useForgotPin();
    const payload = { nik: '123', whatsapp_number: '081' };
    (AuthService.requestForgotPinOtp as any).mockResolvedValue({});

    const result = await sendOtp(payload);

    expect(AuthService.requestForgotPinOtp).toHaveBeenCalledWith(payload);
    expect(result.success).toBe(true);
    expect(isSubmitting.value).toBe(false);
  });

  it('sendOtp fails', async () => {
    const { sendOtp, errorMessage } = useForgotPin();
    (AuthService.requestForgotPinOtp as any).mockRejectedValue({
      response: { data: { message: 'User tidak ditemukan' } }
    });

    const result = await sendOtp({ nik: '000', whatsapp_number: '000' });

    expect(result.success).toBe(false);
    expect(errorMessage.value).toBe('User tidak ditemukan');
  });

  it('executeReset success', async () => {
    const { executeReset } = useForgotPin();
    const payload = { 
        nik: '123', whatsapp_number: '081', otp: '111', 
        new_pin: '123456', new_pin_confirmation: '123456' 
    };
    (AuthService.resetPin as any).mockResolvedValue({});

    const result = await executeReset(payload);

    expect(AuthService.resetPin).toHaveBeenCalledWith(payload);
    expect(result.success).toBe(true);
  });
});