import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAuth } from '../useAuth';
import AuthService from '../../services/AuthService';
import { useRouter } from 'vue-router';

const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: vi.fn((key: string) => store[key] || null),
    setItem: vi.fn((key: string, value: string) => {
      store[key] = value.toString();
    }),
    removeItem: vi.fn((key: string) => {
      delete store[key];
    }),
    clear: vi.fn(() => {
      store = {};
    }),
  };
})();
vi.stubGlobal('localStorage', localStorageMock);

const pushMock = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: pushMock,
  }),
}));

vi.mock('../../services/AuthService', () => ({
  default: {
    registerUser: vi.fn(),
    loginUser: vi.fn(),
    logoutUser: vi.fn(),
  },
}));

describe('useAuth', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  it('submitRegistration success', async () => {
    const { submitRegistration, isSubmitting, authError } = useAuth();
    const payload = { nik: '123', family_card_number: '123', full_name: 'A', whatsapp_number: '081234', pin: '11', pin_confirmation: '11' };
    
    (AuthService.registerUser as any).mockResolvedValue({});

    const result = await submitRegistration(payload);

    expect(AuthService.registerUser).toHaveBeenCalledWith(payload);
    expect(result).toEqual({ success: true, phone: '081234' });
    expect(isSubmitting.value).toBe(false);
    expect(authError.value).toBe('');
  });

  it('submitRegistration fails', async () => {
    const { submitRegistration, authError } = useAuth();
    const payload = { nik: '123', family_card_number: '123', full_name: 'A', whatsapp_number: '081234', pin: '11', pin_confirmation: '11' };
    
    (AuthService.registerUser as any).mockRejectedValue({
      response: { data: { message: 'Register Error' } }
    });

    const result = await submitRegistration(payload);

    expect(result).toEqual({ success: false });
    expect(authError.value).toBe('Register Error');
  });

  it('submitLogin success', async () => {
    const { submitLogin, isSubmitting } = useAuth();
    const payload = { nik: '123', pin: '111' };
    
    (AuthService.loginUser as any).mockResolvedValue({
      data: {
        token: 'test-token',
        user: { id: 1, name: 'User' }
      }
    });

    const result = await submitLogin(payload);

    expect(result).toEqual({ success: true, mustChangePin: false });
    expect(localStorage.getItem('token')).toBe('test-token');
    expect(localStorage.getItem('user')).toBe(JSON.stringify({ id: 1, name: 'User' }));
    expect(isSubmitting.value).toBe(false);
  });

  it('submitLogin fails unverified account 422', async () => {
    const { submitLogin } = useAuth();
    const payload = { nik: '123', pin: '111' };
    
    (AuthService.loginUser as any).mockRejectedValue({
      response: {
        status: 422,
        data: {
          errors: {
            is_verified: ['Akun belum aktif'],
            whatsapp_number: '081234'
          }
        }
      }
    });

    const result = await submitLogin(payload);

    expect(result).toEqual({
      success: false,
      needsVerification: true,
      wa: '081234',
      message: 'Akun belum aktif'
    });
  });

  it('submitLogin fails wrong credentials 422', async () => {
    const { submitLogin, authError } = useAuth();
    const payload = { nik: '123', pin: '111' };
    
    (AuthService.loginUser as any).mockRejectedValue({
      response: {
        status: 422,
        data: {
          errors: {
            nik: ['Kombinasi salah']
          }
        }
      }
    });

    const result = await submitLogin(payload);

    expect(result).toEqual({ success: false });
    expect(authError.value).toBe('Kombinasi salah');
  });

  it('handleLogout success', async () => {
    const { handleLogout, isSubmitting } = useAuth();
    
    localStorage.setItem('token', 'abc');
    localStorage.setItem('user', '{}');
    
    (AuthService.logoutUser as any).mockResolvedValue({});

    await handleLogout();

    expect(AuthService.logoutUser).toHaveBeenCalled();
    expect(localStorage.getItem('token')).toBeNull();
    expect(localStorage.getItem('user')).toBeNull();
    expect(pushMock).toHaveBeenCalledWith({ name: 'home' });
    expect(isSubmitting.value).toBe(false);
  });

  it('handleLogout clears storage even if api fails', async () => {
    const { handleLogout } = useAuth();
    
    localStorage.setItem('token', 'abc');
    
    (AuthService.logoutUser as any).mockRejectedValue(new Error('Network Error'));

    await handleLogout();

    expect(localStorage.getItem('token')).toBeNull();
    expect(pushMock).toHaveBeenCalledWith({ name: 'home' });
  });
});