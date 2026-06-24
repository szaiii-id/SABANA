import { describe, it, expect, vi, beforeEach } from 'vitest';
import AuthService from '../AuthService';
import { adminAuthEndpoint } from '../../api/authApi';
import type { LoginPayload } from '../../types/auth';

// =============================================
// MOCK: authApi
// =============================================
vi.mock('../../api/authApi', () => ({
  adminAuthEndpoint: {
    login: vi.fn(),
    logout: vi.fn(),
    ping: vi.fn(),
  },
}));

// =============================================
// HELPERS
// =============================================
const validPayload: LoginPayload = {
  nip: '199001012020011001',
  password: 'password123',
};

const mockResponse = {
  data: {
    status: 'success',
    message: 'Login berhasil.',
    data: {
      admin: { id: '1', nip: '199001012020011001', name: 'Admin', role: 'super_admin', is_active: true },
      token: 'test-token-123',
    },
  },
};

// =============================================
// TEST SUITE
// =============================================
describe('AuthService', () => {

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===== HAPPY PATH =====

  it('test_loginAdmin_berhasil_return_response_data', async () => {
    (adminAuthEndpoint.login as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    const result = await AuthService.loginAdmin(validPayload);

    expect(result).toEqual(mockResponse.data);
    expect(adminAuthEndpoint.login).toHaveBeenCalledWith(validPayload);
  });

  it('test_logoutAdmin_berhasil_return_response_data', async () => {
    const logoutResponse = { data: { status: 'success', message: 'Logout berhasil.' } };
    (adminAuthEndpoint.logout as ReturnType<typeof vi.fn>).mockResolvedValue(logoutResponse);

    const result = await AuthService.logoutAdmin();

    expect(result).toEqual(logoutResponse.data);
    expect(adminAuthEndpoint.logout).toHaveBeenCalled();
  });

  it('test_refreshSession_berhasil_return_response_data', async () => {
    const pingResponse = { data: { id: '1', name: 'Admin' } };
    (adminAuthEndpoint.ping as ReturnType<typeof vi.fn>).mockResolvedValue(pingResponse);

    const result = await AuthService.refreshSession();

    expect(result).toEqual(pingResponse.data);
    expect(adminAuthEndpoint.ping).toHaveBeenCalled();
  });

  // ===== SAD PATH =====

  it('test_loginAdmin_gagal_throw_error', async () => {
    const error = new Error('Network Error');
    (adminAuthEndpoint.login as ReturnType<typeof vi.fn>).mockRejectedValue(error);

    await expect(AuthService.loginAdmin(validPayload)).rejects.toThrow('Network Error');
  });

  it('test_logoutAdmin_gagal_throw_error', async () => {
    (adminAuthEndpoint.logout as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Unauthorized'));

    await expect(AuthService.logoutAdmin()).rejects.toThrow('Unauthorized');
  });

  it('test_refreshSession_gagal_throw_error', async () => {
    (adminAuthEndpoint.ping as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Session expired'));

    await expect(AuthService.refreshSession()).rejects.toThrow('Session expired');
  });

  // ===== BOUNDARY =====

  it('test_loginAdmin_dengan_nip_18_digit', async () => {
    (adminAuthEndpoint.login as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    await AuthService.loginAdmin({ nip: '199001012020011001', password: 'password123' });

    expect(adminAuthEndpoint.login).toHaveBeenCalledWith({
      nip: '199001012020011001',
      password: 'password123',
    });
  });

  it('test_loginAdmin_dengan_password_min_8_char', async () => {
    (adminAuthEndpoint.login as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    await AuthService.loginAdmin({ nip: '199001012020011001', password: '12345678' });

    expect(adminAuthEndpoint.login).toHaveBeenCalledWith({
      nip: '199001012020011001',
      password: '12345678',
    });
  });

  // ===== EDGE CASE =====

  it('test_loginAdmin_dengan_nip_ada_spasi', async () => {
    (adminAuthEndpoint.login as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    await AuthService.loginAdmin({ nip: '1990 0101 2020 0110 01', password: 'password123' });

    expect(adminAuthEndpoint.login).toHaveBeenCalledWith({
      nip: '1990 0101 2020 0110 01',
      password: 'password123',
    });
  });

  it('test_logoutAdmin_dipanggil_tanpa_token', async () => {
    (adminAuthEndpoint.logout as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Unauthorized'));

    await expect(AuthService.logoutAdmin()).rejects.toThrow();
  });

  // ===== NULL / EMPTY =====

  it('test_loginAdmin_response_data_kosong', async () => {
    (adminAuthEndpoint.login as ReturnType<typeof vi.fn>).mockResolvedValue({ data: null });

    const result = await AuthService.loginAdmin(validPayload);

    expect(result).toBeNull();
  });

  it('test_refreshSession_response_null', async () => {
    (adminAuthEndpoint.ping as ReturnType<typeof vi.fn>).mockResolvedValue({ data: null });

    const result = await AuthService.refreshSession();

    expect(result).toBeNull();
  });

  // ===== DATA TYPE =====

  it('test_loginAdmin_parameter_type_LoginPayload', async () => {
    (adminAuthEndpoint.login as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    const payload: LoginPayload = { nip: '199001012020011001', password: 'password123' };
    await AuthService.loginAdmin(payload);

    expect(adminAuthEndpoint.login).toHaveBeenCalledWith(payload);
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_loginAdmin_nip_valid_18_digit_sukses', async () => {
    (adminAuthEndpoint.login as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    const result = await AuthService.loginAdmin({ nip: '199001012020011001', password: 'password123' });

    expect(result).toEqual(mockResponse.data);
  });

  it('test_loginAdmin_nip_kurang_dari_18_digit', async () => {
    (adminAuthEndpoint.login as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { status: 422, data: { message: 'NIP harus 18 digit.' } },
    });

    await expect(
      AuthService.loginAdmin({ nip: '12345', password: 'password123' })
    ).rejects.toEqual({
      response: { status: 422, data: { message: 'NIP harus 18 digit.' } },
    });
  });

  // ===== CONCURRENCY =====

  it('test_loginAdmin_parallel_calls', async () => {
    (adminAuthEndpoint.login as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    const [result1, result2] = await Promise.all([
      AuthService.loginAdmin(validPayload),
      AuthService.loginAdmin(validPayload),
    ]);

    expect(adminAuthEndpoint.login).toHaveBeenCalledTimes(2);
    expect(result1).toEqual(mockResponse.data);
    expect(result2).toEqual(mockResponse.data);
  });

  // ===== SECURITY =====

  it('test_loginAdmin_401_throw_error', async () => {
    const error = {
      response: { status: 401, data: { message: 'Unauthorized' } },
    };
    (adminAuthEndpoint.login as ReturnType<typeof vi.fn>).mockRejectedValue(error);

    await expect(AuthService.loginAdmin(validPayload)).rejects.toEqual(error);
  });

  it('test_refreshSession_401_throw_error', async () => {
    (adminAuthEndpoint.ping as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { status: 401 },
    });

    await expect(AuthService.refreshSession()).rejects.toEqual({
      response: { status: 401 },
    });
  });
});