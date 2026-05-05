// src/composables/__test__/useAuth.spec.ts
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAuth } from '../useAuth';
import AuthService from '../../services/AuthService';

// ============================================================
// LOCALSTORAGE MOCK
// ============================================================
let store: Record<string, string> = {};

const localStorageMock = {
  getItem: vi.fn((key: string) => store[key] || null),
  setItem: vi.fn((key: string, value: string) => { store[key] = value; }),
  removeItem: vi.fn((key: string) => { delete store[key]; }),
  clear: vi.fn(() => { store = {}; }),
};
vi.stubGlobal('localStorage', localStorageMock);

// ============================================================
// MOCKS
// ============================================================
const pushMock = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock }),
}));

vi.mock('../../services/AuthService', () => ({
  default: {
    registerUser: vi.fn(),
    loginUser: vi.fn(),
    logoutUser: vi.fn(),
  },
}));

// ============================================================
// TEST SUITE
// ============================================================
describe('useAuth - Professional QA Test Suite', () => {

  beforeEach(() => {
    vi.clearAllMocks();
    store = {}; // ✅ Reset store manual
  });

  // ============================================================
  // INITIAL STATE
  // ============================================================
  describe('Initial State', () => {

    it('[INIT-01] isSubmitting default false', () => {
      const { isSubmitting } = useAuth();
      expect(isSubmitting.value).toBe(false);
    });

    it('[INIT-02] authError default kosong', () => {
      const { authError } = useAuth();
      expect(authError.value).toBe('');
    });
  });

  // ============================================================
  // submitRegistration
  // ============================================================
  describe('submitRegistration', () => {

    const payload = {
      nik: '6301234567890123',
      family_card_number: '6301234567890123',
      full_name: 'John Doe',
      whatsapp_number: '08123456789',
      pin: '123456',
      pin_confirmation: '123456',
    };

    it('[REG-01] Memanggil AuthService.registerUser', async () => {
      const { submitRegistration } = useAuth();
      (AuthService.registerUser as any).mockResolvedValue({});

      await submitRegistration(payload);

      expect(AuthService.registerUser).toHaveBeenCalledWith(payload);
    });

    it('[REG-02] Return success true dan phone', async () => {
      const { submitRegistration } = useAuth();
      (AuthService.registerUser as any).mockResolvedValue({});

      const result = await submitRegistration(payload);

      expect(result).toEqual({ success: true, phone: '08123456789' });
    });

    it('[REG-03] isSubmitting false setelah sukses', async () => {
      const { submitRegistration, isSubmitting } = useAuth();
      (AuthService.registerUser as any).mockResolvedValue({});

      await submitRegistration(payload);

      expect(isSubmitting.value).toBe(false);
    });

    it('[REG-04] authError kosong setelah sukses', async () => {
      const { submitRegistration, authError } = useAuth();
      (AuthService.registerUser as any).mockResolvedValue({});

      await submitRegistration(payload);

      expect(authError.value).toBe('');
    });

    it('[REG-05] Return success false jika gagal', async () => {
      const { submitRegistration } = useAuth();
      (AuthService.registerUser as any).mockRejectedValue({
        response: { data: { message: 'Register Error' } }
      });

      const result = await submitRegistration(payload);

      expect(result).toEqual({ success: false });
    });

    it('[REG-06] authError terisi jika gagal', async () => {
      const { submitRegistration, authError } = useAuth();
      (AuthService.registerUser as any).mockRejectedValue({
        response: { data: { message: 'Register Error' } }
      });

      await submitRegistration(payload);

      expect(authError.value).toBe('Register Error');
    });

    it('[REG-07] authError default jika network error', async () => {
      const { submitRegistration, authError } = useAuth();
      (AuthService.registerUser as any).mockRejectedValue(new Error('Network Error'));

      await submitRegistration(payload);

      expect(authError.value).toBe('Gagal terhubung ke server SABANA.');
    });
  });

  // ============================================================
  // submitLogin
  // ============================================================
  describe('submitLogin', () => {

    const payload = { nik: '6301234567890123', pin: '123456' };

    it('[LOGIN-01] Return success dan simpan token + user', async () => {
      const { submitLogin } = useAuth();
      (AuthService.loginUser as any).mockResolvedValue({
        data: {
          token: 'test-token',
          user: { id: 1, name: 'John' },
        },
      });

      const result = await submitLogin(payload);

      expect(result).toEqual({ success: true, mustChangePin: false });
      expect(localStorage.getItem('token')).toBe('test-token');
      expect(localStorage.getItem('user')).toBe(JSON.stringify({ id: 1, name: 'John' }));
    });

    it('[LOGIN-02] Handle response tanpa wrapper data', async () => {
      const { submitLogin } = useAuth();
      (AuthService.loginUser as any).mockResolvedValue({
        token: 'direct-token',
        user: { id: 2, name: 'Jane' },
      });

      await submitLogin(payload);

      expect(localStorage.getItem('token')).toBe('direct-token');
      expect(localStorage.getItem('user')).toBe(JSON.stringify({ id: 2, name: 'Jane' }));
    });

    it('[LOGIN-03] Throw error jika token tidak ada', async () => {
      const { submitLogin, authError } = useAuth();
      (AuthService.loginUser as any).mockResolvedValue({
        data: { user: { id: 1 } }, // Tidak ada token
      });

      await submitLogin(payload);

      expect(authError.value).toContain('Gagal');
    });

    it('[LOGIN-04] Error 422: Akun belum terverifikasi', async () => {
      const { submitLogin } = useAuth();
      (AuthService.loginUser as any).mockRejectedValue({
        response: {
          status: 422,
          data: {
            errors: {
              is_verified: ['Akun belum aktif'],
              whatsapp_number: '08123456789',
            },
          },
        },
      });

      const result = await submitLogin(payload);

      expect(result).toEqual({
        success: false,
        needsVerification: true,
        wa: '08123456789',
        message: 'Akun belum aktif',
      });
    });

    it('[LOGIN-05] Error 422: Kredensial salah', async () => {
      const { submitLogin, authError } = useAuth();
      (AuthService.loginUser as any).mockRejectedValue({
        response: {
          status: 422,
          data: {
            errors: { nik: ['Kombinasi NIK dan PIN tidak cocok.'] },
          },
        },
      });

      const result = await submitLogin(payload);

      expect(result).toEqual({ success: false });
      expect(authError.value).toBe('Kombinasi NIK dan PIN tidak cocok.');
    });

    it('[LOGIN-06] Error 422 default message', async () => {
      const { submitLogin, authError } = useAuth();
      (AuthService.loginUser as any).mockRejectedValue({
        response: { status: 422, data: { errors: {} } },
      });

      await submitLogin(payload);

      expect(authError.value).toBe('Kombinasi NIK dan PIN tidak cocok.');
    });

    it('[LOGIN-07] isSubmitting false setelah selesai', async () => {
      const { submitLogin, isSubmitting } = useAuth();
      (AuthService.loginUser as any).mockResolvedValue({
        data: { token: 'x', user: { id: 1 } },
      });

      await submitLogin(payload);

      expect(isSubmitting.value).toBe(false);
    });
  });

  // ============================================================
  // handleLogout
  // ============================================================
  describe('handleLogout', () => {

    it('[LOGOUT-01] Memanggil AuthService.logoutUser', async () => {
      const { handleLogout } = useAuth();
      (AuthService.logoutUser as any).mockResolvedValue({});

      await handleLogout();

      expect(AuthService.logoutUser).toHaveBeenCalledTimes(1);
    });

    it('[LOGOUT-02] Hapus token dan user dari localStorage', async () => {
      const { handleLogout } = useAuth();
      
      store['token'] = 'abc';
      store['user'] = JSON.stringify({ id: 1 });

      (AuthService.logoutUser as any).mockResolvedValue({});

      await handleLogout();

      expect(store['token']).toBeUndefined();
      expect(store['user']).toBeUndefined();
    });

    it('[LOGOUT-03] Redirect ke home setelah logout', async () => {
      const { handleLogout } = useAuth();
      (AuthService.logoutUser as any).mockResolvedValue({});

      await handleLogout();

      expect(pushMock).toHaveBeenCalledWith({ name: 'home' });
    });

    it('[LOGOUT-04] Tetap hapus localStorage meski API gagal', async () => {
      const { handleLogout } = useAuth();
      
      store['token'] = 'abc';
      (AuthService.logoutUser as any).mockRejectedValue(new Error('Network Error'));

      await handleLogout();

      expect(store['token']).toBeUndefined();
      expect(pushMock).toHaveBeenCalledWith({ name: 'home' });
    });

    it('[LOGOUT-05] isSubmitting false setelah logout', async () => {
      const { handleLogout, isSubmitting } = useAuth();
      (AuthService.logoutUser as any).mockResolvedValue({});

      await handleLogout();

      expect(isSubmitting.value).toBe(false);
    });
  });
});