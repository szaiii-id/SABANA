import { describe, it, expect, vi, beforeEach } from 'vitest';
import AccountService from '../AccountService';
import { accountEndpoint } from '../../api/accountApi';

// =============================================
// MOCK: accountApi
// =============================================
vi.mock('../../api/accountApi', () => ({
  accountEndpoint: {
    getAll: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
    activate: vi.fn(),
    resetPassword: vi.fn(),
  },
}));

// =============================================
// HELPERS
// =============================================
const validPayload = {
  nip: '199001012020011001',
  name: 'Admin Test',
  password: 'password123',
  role: 'village_officer' as const,
  village_id: '6301020001',
};

const mockResponse = {
  data: { status: 'success', message: 'Berhasil', data: { id: '1' } },
};

// =============================================
// TEST SUITE
// =============================================
describe('AccountService', () => {

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===== HAPPY PATH =====

  it('test_fetchAccounts_berhasil_return_data', async () => {
    (accountEndpoint.getAll as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    const result = await AccountService.fetchAccounts();

    expect(result).toEqual(mockResponse.data);
    expect(accountEndpoint.getAll).toHaveBeenCalled();
  });

  it('test_createAccount_berhasil_return_data', async () => {
    (accountEndpoint.create as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    const result = await AccountService.createAccount(validPayload);

    expect(result).toEqual(mockResponse.data);
    expect(accountEndpoint.create).toHaveBeenCalledWith(validPayload);
  });

  it('test_updateAccount_berhasil_return_data', async () => {
    (accountEndpoint.update as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    const result = await AccountService.updateAccount('1', { name: 'Updated' });

    expect(result).toEqual(mockResponse.data);
    expect(accountEndpoint.update).toHaveBeenCalledWith('1', { name: 'Updated' });
  });

  it('test_deleteAccount_berhasil_return_data', async () => {
    (accountEndpoint.delete as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    const result = await AccountService.deleteAccount('1');

    expect(result).toEqual(mockResponse.data);
    expect(accountEndpoint.delete).toHaveBeenCalledWith('1');
  });

  it('test_activateAccount_berhasil_return_data', async () => {
    (accountEndpoint.activate as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    const result = await AccountService.activateAccount('1');

    expect(result).toEqual(mockResponse.data);
    expect(accountEndpoint.activate).toHaveBeenCalledWith('1');
  });

  it('test_resetPassword_berhasil_return_data', async () => {
    (accountEndpoint.resetPassword as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    const result = await AccountService.resetPassword('1', 'newpassword123');

    expect(result).toEqual(mockResponse.data);
    expect(accountEndpoint.resetPassword).toHaveBeenCalledWith('1', 'newpassword123');
  });

  // ===== SAD PATH =====

  it('test_fetchAccounts_gagal_throw_error', async () => {
    (accountEndpoint.getAll as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Network Error'));

    await expect(AccountService.fetchAccounts()).rejects.toThrow('Network Error');
  });

  it('test_createAccount_gagal_throw_error', async () => {
    (accountEndpoint.create as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Validation Error'));

    await expect(AccountService.createAccount(validPayload)).rejects.toThrow('Validation Error');
  });

  it('test_deleteAccount_gagal_throw_error', async () => {
    (accountEndpoint.delete as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Not Found'));

    await expect(AccountService.deleteAccount('999')).rejects.toThrow('Not Found');
  });

  it('test_resetPassword_gagal_throw_error', async () => {
    (accountEndpoint.resetPassword as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Unauthorized'));

    await expect(AccountService.resetPassword('1', 'pass')).rejects.toThrow('Unauthorized');
  });

  // ===== BOUNDARY =====

  it('test_fetchAccounts_dengan_filter', async () => {
    const filters = { search: 'Admin', role: 'village_officer', page: 1, per_page: 10 };
    (accountEndpoint.getAll as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    await AccountService.fetchAccounts(filters);

    expect(accountEndpoint.getAll).toHaveBeenCalledWith(filters);
  });

  it('test_updateAccount_partial_payload', async () => {
    (accountEndpoint.update as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    await AccountService.updateAccount('1', { name: 'New Name' });

    expect(accountEndpoint.update).toHaveBeenCalledWith('1', { name: 'New Name' });
  });

  // ===== EDGE CASE =====

  it('test_fetchAccounts_tanpa_filter', async () => {
    (accountEndpoint.getAll as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    await AccountService.fetchAccounts();

    expect(accountEndpoint.getAll).toHaveBeenCalledWith(undefined);
  });

  // ===== NULL / EMPTY =====

  it('test_fetchAccounts_response_null', async () => {
    (accountEndpoint.getAll as ReturnType<typeof vi.fn>).mockResolvedValue({ data: null });

    const result = await AccountService.fetchAccounts();

    expect(result).toBeNull();
  });

  it('test_createAccount_response_null', async () => {
    (accountEndpoint.create as ReturnType<typeof vi.fn>).mockResolvedValue({ data: null });

    const result = await AccountService.createAccount(validPayload);

    expect(result).toBeNull();
  });

  // ===== DATA TYPE =====

  it('test_createAccount_payload_type_AccountPayload', async () => {
    (accountEndpoint.create as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    await AccountService.createAccount(validPayload);

    const calledWith = (accountEndpoint.create as ReturnType<typeof vi.fn>).mock.calls[0][0];
    expect(typeof calledWith.nip).toBe('string');
    expect(typeof calledWith.name).toBe('string');
    expect(typeof calledWith.role).toBe('string');
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_resetPassword_password_8_char', async () => {
    (accountEndpoint.resetPassword as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    await AccountService.resetPassword('1', '12345678');

    expect(accountEndpoint.resetPassword).toHaveBeenCalledWith('1', '12345678');
  });

  it('test_resetPassword_password_kosong', async () => {
    (accountEndpoint.resetPassword as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Validation Error'));

    await expect(AccountService.resetPassword('1', '')).rejects.toThrow('Validation Error');
  });

  // ===== CONCURRENCY =====

  it('test_parallel_calls', async () => {
    (accountEndpoint.getAll as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    const [r1, r2] = await Promise.all([
      AccountService.fetchAccounts(),
      AccountService.fetchAccounts(),
    ]);

    expect(accountEndpoint.getAll).toHaveBeenCalledTimes(2);
    expect(r1).toEqual(mockResponse.data);
    expect(r2).toEqual(mockResponse.data);
  });

  // ===== SECURITY =====

  it('test_activateAccount_401_throw_error', async () => {
    (accountEndpoint.activate as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { status: 401, data: { message: 'Unauthorized' } },
    });

    await expect(AccountService.activateAccount('1')).rejects.toEqual({
      response: { status: 401, data: { message: 'Unauthorized' } },
    });
  });
});