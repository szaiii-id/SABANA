import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { ref } from 'vue';
import { useAuth } from '../useAuth';
import AuthService from '../../services/AuthService';
import type { LoginPayload } from '../../types/auth';

// =============================================
// MOCK: AuthService
// =============================================
vi.mock('../../services/AuthService', () => ({
  default: {
    loginAdmin: vi.fn(),
    logoutAdmin: vi.fn(),
    refreshSession: vi.fn(),
  },
}));

// =============================================
// MOCK: localStorage
// =============================================
const storage: Record<string, string> = {};
const localStorageMock = {
  getItem: vi.fn((key: string) => storage[key] ?? null),
  setItem: vi.fn((key: string, value: string) => { storage[key] = value; }),
  removeItem: vi.fn((key: string) => { delete storage[key]; }),
  clear: vi.fn(() => { Object.keys(storage).forEach(k => delete storage[k]); }),
};
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

// =============================================
// MOCK: window.location
// =============================================
const locationMock = { href: '' };
Object.defineProperty(window, 'location', { value: locationMock, writable: true });

// =============================================
// MOCK: setInterval / clearInterval
// =============================================
vi.useFakeTimers();

// =============================================
// TEST SUITE
// =============================================
describe('useAuth', () => {

  beforeEach(() => {
    vi.clearAllMocks();
    vi.clearAllTimers();
    Object.keys(storage).forEach(k => delete storage[k]);
    localStorageMock.getItem.mockClear();
    localStorageMock.setItem.mockClear();
    localStorageMock.removeItem.mockClear();
    locationMock.href = '';
  });

  afterEach(() => {
    vi.clearAllTimers();
  });

  // ===== HAPPY PATH =====

  it('test_submit_login_berhasil_return_success_true', async () => {
    const mockToken = 'test-token-123';
    const mockAdmin = { id: '1', nip: '199001012020011001', name: 'Admin', role: 'super_admin' };
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { token: mockToken, admin: mockAdmin },
    });

    const { submitLogin } = useAuth();
    const result = await submitLogin({ nip: '199001012020011001', password: 'password123' });

    expect(result.success).toBe(true);
    expect(localStorageMock.setItem).toHaveBeenCalledWith('admin_token', mockToken);
  });

  it('test_submit_login_berhasil_update_isSubmitting', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { token: 'token', admin: { id: '1' } },
    });

    const { submitLogin, isSubmitting } = useAuth();
    const promise = submitLogin({ nip: '199001012020011001', password: 'password123' });

    expect(isSubmitting.value).toBe(true);
    await promise;
    expect(isSubmitting.value).toBe(false);
  });

  // ===== SAD PATH =====

  it('test_submit_login_gagal_return_success_false', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { status: 422, data: { message: 'Kredensial tidak cocok.' } },
    });

    const { submitLogin } = useAuth();
    const result = await submitLogin({ nip: '199001012020011001', password: 'wrong' });

    expect(result.success).toBe(false);
  });

  it('test_submit_login_gagal_set_authError', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { status: 422, data: { message: 'Kredensial tidak cocok.' } },
    });

    const { submitLogin, authError } = useAuth();
    await submitLogin({ nip: '199001012020011001', password: 'wrong' });

    expect(authError.value).toBe('Kredensial tidak cocok.');
  });

  it('test_submit_login_network_error', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockRejectedValue({
      message: 'Network Error',
    });

    const { submitLogin, authError } = useAuth();
    await submitLogin({ nip: '199001012020011001', password: 'password123' });

    expect(authError.value).toBe('Network Error');
  });

  // ===== BOUNDARY =====

  it('test_submit_login_token_kosong_throw_error', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { token: '', admin: null },
    });

    const { submitLogin, authError } = useAuth();
    await submitLogin({ nip: '199001012020011001', password: 'password123' });

    expect(authError.value).toBe('Format data dari server tidak sesuai.');
  });

  it('test_submit_login_admin_null_throw_error', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { token: 'token', admin: null },
    });

    const { submitLogin, authError } = useAuth();
    await submitLogin({ nip: '199001012020011001', password: 'password123' });

    expect(authError.value).toBe('Format data dari server tidak sesuai.');
  });

  // ===== EDGE CASE =====

  it('test_submit_login_rate_limited_429', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: {
        status: 429,
        headers: { 'retry-after': '60' },
      },
    });

    const { submitLogin, isRateLimited } = useAuth();
    const result = await submitLogin({ nip: '199001012020011001', password: 'wrong' });

    expect(result.success).toBe(false);
    expect(isRateLimited.value).toBe(true);
  });

  it('test_submit_login_rate_limited_countdown', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: {
        status: 429,
        headers: { 'retry-after': '120' },
      },
    });

    const { submitLogin, isRateLimited, retryAfter } = useAuth();
    await submitLogin({ nip: '199001012020011001', password: 'wrong' });

    expect(isRateLimited.value).toBe(true);
    expect(retryAfter.value).toBe(120);
  });

  // ===== NULL / EMPTY =====

  it('test_authError_kosong_saat_pertama_load', () => {
    const { authError } = useAuth();
    expect(authError.value).toBe('');
  });

  // ===== DATA TYPE =====

  it('test_submitLogin_terima_LoginPayload_type', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { token: 'token', admin: { id: '1' } },
    });

    const { submitLogin } = useAuth();
    const payload: LoginPayload = { nip: '199001012020011001', password: 'password123' };
    await submitLogin(payload);

    expect(AuthService.loginAdmin).toHaveBeenCalledWith(payload);
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_submit_login_nip_valid_18_digit', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { token: 'token', admin: { id: '1' } },
    });

    const { submitLogin } = useAuth();
    const result = await submitLogin({ nip: '199001012020011001', password: 'password123' });

    expect(result.success).toBe(true);
  });

  it('test_submit_login_nip_invalid', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { status: 422, data: { message: 'Kredensial tidak cocok.' } },
    });

    const { submitLogin } = useAuth();
    const result = await submitLogin({ nip: '12345', password: 'password123' });

    expect(result.success).toBe(false);
  });

  // ===== STATE TRANSITION =====

  it('test_handleLogout_hapus_token_dari_localStorage', async () => {
    (AuthService.logoutAdmin as ReturnType<typeof vi.fn>).mockResolvedValue({});
    storage['admin_token'] = 'token';

    const { handleLogout } = useAuth();
    await handleLogout();

    expect(localStorageMock.removeItem).toHaveBeenCalledWith('admin_token');
    expect(localStorageMock.removeItem).toHaveBeenCalledWith('admin_user');
  });

  it('test_handleLogout_redirect_ke_login', async () => {
    (AuthService.logoutAdmin as ReturnType<typeof vi.fn>).mockResolvedValue({});

    const { handleLogout } = useAuth();
    await handleLogout();

    expect(locationMock.href).toContain('/login');
  });

  it('test_handleLogout_gagal_api_tetap_hapus_local', async () => {
    (AuthService.logoutAdmin as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Network Error'));

    const { handleLogout } = useAuth();
    await handleLogout();

    expect(localStorageMock.removeItem).toHaveBeenCalledWith('admin_token');
  });

  // ===== CONCURRENCY =====

  it('test_rate_limited_selesai_setelah_countdown', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { status: 429, headers: { 'retry-after': '1' } },
    });

    const { submitLogin, isRateLimited, authError } = useAuth();
    await submitLogin({ nip: '199001012020011001', password: 'wrong' });

    expect(isRateLimited.value).toBe(true);

    vi.advanceTimersByTime(2000);

    expect(isRateLimited.value).toBe(false);
    expect(authError.value).toBe('');
  });

  // ===== SECURITY =====

  it('test_session_refresh_setiap_30_menit', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { token: 'token', admin: { id: '1' } },
    });
    (AuthService.refreshSession as ReturnType<typeof vi.fn>).mockResolvedValue({});
    storage['admin_token'] = 'token';

    const { submitLogin } = useAuth();
    await submitLogin({ nip: '199001012020011001', password: 'password123' });

    vi.advanceTimersByTime(30 * 60 * 1000);

    expect(AuthService.refreshSession).toHaveBeenCalled();
  });

  it('test_session_refresh_401_trigger_logout', async () => {
    (AuthService.loginAdmin as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { token: 'token', admin: { id: '1' } },
    });
    (AuthService.refreshSession as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { status: 401 },
    });
    (AuthService.logoutAdmin as ReturnType<typeof vi.fn>).mockResolvedValue({});
    storage['admin_token'] = 'token';

    const { submitLogin } = useAuth();
    await submitLogin({ nip: '199001012020011001', password: 'password123' });

    vi.advanceTimersByTime(30 * 60 * 1000);
    await vi.runAllTimersAsync();

    expect(localStorageMock.setItem).toHaveBeenCalledWith('admin_session_expired', 'true');
  });
});