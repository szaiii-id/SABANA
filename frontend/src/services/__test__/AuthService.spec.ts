import { describe, it, expect, vi, beforeEach } from 'vitest';
import AuthService from '../AuthService';
import { authEndpoint } from '../../api/authApi';

vi.mock('../../api/authApi', () => ({
  authEndpoint: {
    register: vi.fn(),
    login: vi.fn(),
    verifyRegistration: vi.fn(),
    resendRegistrationOtp: vi.fn(),
    logout: vi.fn(),
    forgotPin: vi.fn(),
    resetPin: vi.fn(),
  }
}));

describe('AuthService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('registerUser', async () => {
    const payload = { nik: '123', family_card_number: '123', full_name: 'A', whatsapp_number: '08', pin: '11', pin_confirmation: '11' };
    const mockResponse = { data: { success: true } };
    (authEndpoint.register as any).mockResolvedValue(mockResponse);

    const result = await AuthService.registerUser(payload);

    expect(authEndpoint.register).toHaveBeenCalledWith(payload);
    expect(result).toEqual(mockResponse.data);
  });

  it('loginUser', async () => {
    const payload = { nik: '123', pin: '111' };
    const mockResponse = { data: { token: 'abc' } };
    (authEndpoint.login as any).mockResolvedValue(mockResponse);

    const result = await AuthService.loginUser(payload);

    expect(authEndpoint.login).toHaveBeenCalledWith(payload);
    expect(result).toEqual(mockResponse.data);
  });

  it('verifyOtp', async () => {
    const payload = { nik: '123', whatsapp_number: '08', otp: '123456' };
    const mockResponse = { data: { verified: true } };
    (authEndpoint.verifyRegistration as any).mockResolvedValue(mockResponse);

    const result = await AuthService.verifyOtp(payload);

    expect(authEndpoint.verifyRegistration).toHaveBeenCalledWith(payload);
    expect(result).toEqual(mockResponse.data);
  });

  it('resendOtp', async () => {
    const payload = { nik: '123', whatsapp_number: '08' };
    const mockResponse = { data: { sent: true } };
    (authEndpoint.resendRegistrationOtp as any).mockResolvedValue(mockResponse);

    const result = await AuthService.resendOtp(payload);

    expect(authEndpoint.resendRegistrationOtp).toHaveBeenCalledWith(payload);
    expect(result).toEqual(mockResponse.data);
  });

  it('logoutUser', async () => {
    const mockResponse = { data: { loggedOut: true } };
    (authEndpoint.logout as any).mockResolvedValue(mockResponse);

    const result = await AuthService.logoutUser();

    expect(authEndpoint.logout).toHaveBeenCalled();
    expect(result).toEqual(mockResponse.data);
  });

  it('requestForgotPinOtp', async () => {
    const payload = { nik: '123', whatsapp_number: '08' };
    const mockResponse = { data: { requested: true } };
    (authEndpoint.forgotPin as any).mockResolvedValue(mockResponse);

    const result = await AuthService.requestForgotPinOtp(payload);

    expect(authEndpoint.forgotPin).toHaveBeenCalledWith(payload);
    expect(result).toEqual(mockResponse.data);
  });

  it('resetPin', async () => {
    const payload = { nik: '123', whatsapp_number: '08', otp: '111', new_pin: '222', new_pin_confirmation: '222' };
    const mockResponse = { data: { reset: true } };
    (authEndpoint.resetPin as any).mockResolvedValue(mockResponse);

    const result = await AuthService.resetPin(payload);

    expect(authEndpoint.resetPin).toHaveBeenCalledWith(payload);
    expect(result).toEqual(mockResponse.data);
  });
});