// ===== [IMPORTS] =====
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { ref } from 'vue';
import { useAuth } from '../useAuth';
import AuthService from '../../services/AuthService';
import type { RegisterPayload, LoginPayload, LoginData } from '../../types/auth';

// ===== [MOCKS] =====
vi.mock('../../services/AuthService', () => ({
  default: {
    registerUser: vi.fn(),
    loginUser: vi.fn(),
    logoutUser: vi.fn(),
    verifyOtp: vi.fn(),
    resendOtp: vi.fn(),
    requestForgotPinOtp: vi.fn(),
    resetPin: vi.fn(),
  },
}));

const mockPush = vi.fn().mockResolvedValue(undefined);
const mockReplace = vi.fn().mockResolvedValue(undefined);

vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
  }),
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

// ===== [HELPER - MOCK DATA FACTORY] =====
const validData = {
  registerResponse: () => ({
    status: 'success',
    message: 'Registrasi berhasil. Silakan verifikasi OTP.',
    data: {
      nik: '6372010101010001',
      family_card_number: '6372010101010002',
      full_name: 'John Doe',
      whatsapp_number: '6281234567890',
      is_verified: false,
      last_login: null,
    },
  }),

  loginResponse: (overrides?: Partial<LoginData>) => ({
    status: 'success',
    message: 'Login berhasil.',
    data: {
      citizen: {
        nik: '6372010101010001',
        family_card_number: '6372010101010002',
        full_name: 'John Doe',
        whatsapp_number: '6281234567890',
        is_verified: true,
        last_login: '2025-01-15T10:30:00.000Z',
      },
      token: 'jwt-token-abc-xyz-secure',
      require_pin_change: false,
      ...overrides,
    },
  }),

  error422: (errors: Record<string, string[]>) => ({
    response: {
      status: 422,
      data: {
        status: 'error',
        message: 'Validasi gagal.',
        errors,
      },
    },
  }),

  networkError: () => {
    const error = new Error('Koneksi terputus. Periksa jaringan internet Anda.') as any;
    error.isNetworkError = true;
    error.originalError = new Error('Network Error');
    return error;
  },
};

const validRegisterPayload = (): RegisterPayload => ({
  nik: '6372010101010001',
  family_card_number: '6372010101010002',
  full_name: 'John Doe',
  whatsapp_number: '6281234567890',
  pin: '123456',
  pin_confirmation: '123456',
});

const validLoginPayload = (): LoginPayload => ({
  nik: '6372010101010001',
  pin: '123456',
});

// ===== [TEST SUITE] =====
describe('useAuth Composable', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.clear();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===== [1. HAPPY PATH — 5 test] =====
  describe('Happy Path — Registrasi & Login Sukses', () => {
    it('submitRegistration: berhasil dengan data valid, mengembalikan phone', async () => {
      vi.mocked(AuthService.registerUser).mockResolvedValue(validData.registerResponse());
      const { submitRegistration, authError } = useAuth();

      const result = await submitRegistration(validRegisterPayload());

      expect(result.success).toBe(true);
      expect(result.phone).toBe('6281234567890');
      expect(authError.value).toBe('');
    });

    it('submitLogin: sukses login, token tersimpan di localStorage', async () => {
      vi.mocked(AuthService.loginUser).mockResolvedValue(validData.loginResponse());
      const { submitLogin } = useAuth();

      const result = await submitLogin(validLoginPayload());

      expect(result.success).toBe(true);
      expect(result.require_pin_change).toBe(false);
      expect(localStorageMock.setItem).toHaveBeenCalledWith('sabana_token', 'jwt-token-abc-xyz-secure');
    });

    it('isAuthenticated: return true saat token tersimpan', () => {
      localStorageMock.setItem('sabana_token', 'existing-token');
      const { isAuthenticated } = useAuth();
      expect(isAuthenticated()).toBe(true);
    });

    it('getStoredToken: mengembalikan token yang tersimpan', () => {
      localStorageMock.setItem('sabana_token', 'my-secret-token');
      const { getStoredToken } = useAuth();
      expect(getStoredToken()).toBe('my-secret-token');
    });

    it('handleLogout: menghapus token, redirect ke home', async () => {
      localStorageMock.setItem('sabana_token', 'logout-token');
      vi.mocked(AuthService.logoutUser).mockResolvedValue({
        status: 'success',
        message: 'Logout berhasil.',
      });

      const { handleLogout } = useAuth();
      await handleLogout();

      expect(localStorageMock.removeItem).toHaveBeenCalledWith('sabana_token');
      expect(mockPush).toHaveBeenCalledWith({ name: 'home' });
    });
  });

  // ===== [2. SAD PATH — 3 test] =====
  describe('Sad Path — Error Handling', () => {
    it('submitRegistration: gagal → authError terisi', async () => {
      vi.mocked(AuthService.registerUser).mockRejectedValue(
        validData.error422({ nik: ['NIK sudah terdaftar di sistem.'] })
      );

      const { submitRegistration, authError } = useAuth();
      const result = await submitRegistration(validRegisterPayload());

      expect(result.success).toBe(false);
      expect(authError.value).toBe('Validasi gagal.');
    });

    it('submitLogin: 422 belum verifikasi → needsVerification=true', async () => {
      vi.mocked(AuthService.loginUser).mockRejectedValue(
        validData.error422({
          is_verified: ['Akun Anda belum diverifikasi. Silakan cek WA.'],
          whatsapp_number: ['6281234567890'],
        })
      );

      const { submitLogin } = useAuth();
      const result = await submitLogin(validLoginPayload());

      expect(result.success).toBe(false);
      expect(result.needsVerification).toBe(true);
      expect(result.message).toBe('Akun Anda belum diverifikasi. Silakan cek WA.');
    });

    it('submitLogin: 422 kredensial salah → authError terisi', async () => {
      vi.mocked(AuthService.loginUser).mockRejectedValue(
        validData.error422({ nik: ['Kombinasi NIK dan PIN tidak cocok.'] })
      );

      const { submitLogin, authError } = useAuth();
      const result = await submitLogin(validLoginPayload());

      expect(result.success).toBe(false);
      expect(authError.value).toBe('Kombinasi NIK dan PIN tidak cocok.');
    });
  });

  // ===== [3. BOUNDARY — 2 test] =====
  describe('Boundary — Batasan State', () => {
    it('isSubmitting: true selama proses registrasi berlangsung', async () => {
      vi.mocked(AuthService.registerUser).mockImplementation(
        () => new Promise(resolve => setTimeout(() => resolve(validData.registerResponse()), 100))
      );

      const { submitRegistration, isSubmitting } = useAuth();
      const promise = submitRegistration(validRegisterPayload());

      expect(isSubmitting.value).toBe(true);
      await promise;
      expect(isSubmitting.value).toBe(false);
    });

    it('isSubmitting: tetap false jika error immediate (network)', async () => {
      vi.mocked(AuthService.loginUser).mockRejectedValue(validData.networkError());

      const { submitLogin, isSubmitting } = useAuth();
      await submitLogin(validLoginPayload());

      expect(isSubmitting.value).toBe(false);
    });
  });

  // ===== [4. EDGE CASE — 1 test] =====
  describe('Edge Case — Response Tidak Lengkap', () => {
    it('submitLogin: response tanpa token & citizen → throw error', async () => {
      vi.mocked(AuthService.loginUser).mockResolvedValue({
        status: 'success',
        message: 'OK',
        data: null as any,
      });

      const { submitLogin, authError } = useAuth();
      const result = await submitLogin(validLoginPayload());

      expect(result.success).toBe(false);
      expect(authError.value).toBeTruthy();
    });
  });

  // ===== [5. NULL/EMPTY — 2 test] =====
  describe('Null/Empty — State Awal', () => {
    it('isAuthenticated: false saat localStorage kosong', () => {
      const { isAuthenticated } = useAuth();
      expect(isAuthenticated()).toBe(false);
    });

    it('getStoredToken: null saat tidak ada token', () => {
      const { getStoredToken } = useAuth();
      expect(getStoredToken()).toBeNull();
    });
  });

  // ===== [6. DATA TYPE — 1 test] =====
  describe('Data Type — Response Tidak Terduga', () => {
    it('submitRegistration: error bukan object → authError generik', async () => {
      vi.mocked(AuthService.registerUser).mockRejectedValue('String error mentah');

      const { submitRegistration, authError } = useAuth();
      await submitRegistration(validRegisterPayload());

      expect(authError.value).toBeTruthy();
      expect(authError.value).not.toBe('');
    });
  });

  // ===== [7. EQUIVALENCE PARTITION — 2 test] =====
  describe('Equivalence Partition — Grup Response', () => {
    it('submitLogin: require_pin_change = true', async () => {
      vi.mocked(AuthService.loginUser).mockResolvedValue(
        validData.loginResponse({ require_pin_change: true })
      );

      const { submitLogin } = useAuth();
      const result = await submitLogin(validLoginPayload());

      expect(result.require_pin_change).toBe(true);
    });

    it('submitLogin: require_pin_change = false (default)', async () => {
      vi.mocked(AuthService.loginUser).mockResolvedValue(validData.loginResponse());

      const { submitLogin } = useAuth();
      const result = await submitLogin(validLoginPayload());

      expect(result.require_pin_change).toBe(false);
    });
  });

  // ===== [8. STATE TRANSITION — 2 test] =====
  describe('State Transition — Perubahan State', () => {
    it('authError: di-reset sebelum submit kedua', async () => {
      vi.mocked(AuthService.registerUser)
        .mockRejectedValueOnce(validData.error422({ nik: ['Error pertama'] }))
        .mockResolvedValueOnce(validData.registerResponse());

      const { submitRegistration, authError } = useAuth();

      await submitRegistration(validRegisterPayload());
      expect(authError.value).toBe('Validasi gagal.');

      await submitRegistration(validRegisterPayload());
      expect(authError.value).toBe('');
    });

    it('Token: token lama dihapus saat login baru', async () => {
      localStorageMock.setItem('sabana_token', 'old-token-123');

      vi.mocked(AuthService.loginUser).mockResolvedValue(
        validData.loginResponse({ token: 'new-token-456' } as any)
      );

      const { submitLogin } = useAuth();
      await submitLogin(validLoginPayload());

      expect(localStorageMock.setItem).toHaveBeenCalledWith('sabana_token', 'new-token-456');
    });
  });

  // ===== [9. CONCURRENCY — 1 test] =====
  describe('Concurrency — Race Condition', () => {
    it('submit ganda tidak membuat isSubmitting stuck', async () => {
      vi.mocked(AuthService.registerUser)
        .mockImplementationOnce(() => new Promise(r => setTimeout(() => r(validData.registerResponse()), 50)))
        .mockResolvedValueOnce(validData.registerResponse());

      const { submitRegistration, isSubmitting } = useAuth();

      const p1 = submitRegistration(validRegisterPayload());
      const p2 = submitRegistration(validRegisterPayload());

      await Promise.all([p1, p2]);
      expect(isSubmitting.value).toBe(false);
    });
  });

  // ===== [10. SECURITY — 1 test] =====
  describe('Security — XSS & Data Leak', () => {
    it('hanya token yang disimpan, bukan data citizen', async () => {
      vi.mocked(AuthService.loginUser).mockResolvedValue(
        validData.loginResponse({
          citizen: {
            nik: '6372010101010001',
            full_name: 'John Doe',
            whatsapp_number: '6281234567890',
            family_card_number: '6372010101010002',
            is_verified: true,
            last_login: '2025-01-15T10:30:00.000Z',
          },
        } as any)
      );

      const { submitLogin } = useAuth();
      await submitLogin(validLoginPayload());

      expect(localStorageMock.setItem).toHaveBeenCalledWith('sabana_token', 'jwt-token-abc-xyz-secure');
      // Pastikan tidak ada data citizen yang bocor
      const allCalls = localStorageMock.setItem.mock.calls.flat() as string[];
      expect(allCalls.join(' ')).not.toContain('John Doe');
      expect(allCalls.join(' ')).not.toContain('6281234567890');
    });
  });
});