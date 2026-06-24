import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAssistance } from '../../../composables/useAssistance';
import { AssistanceService } from '../../../services/AssistanceService';
import type { AssistanceSubmissionPayload } from '../../../types/assistance';

vi.mock('../../../services/AssistanceService', () => ({
  AssistanceService: {
    getPrograms: vi.fn(),
    getRegencies: vi.fn(),
    getDistricts: vi.fn(),
    getVillages: vi.fn(),
    submitRegistration: vi.fn(),
    getMySubmissions: vi.fn(),
    updateRegistration: vi.fn(),
    cancelRegistration: vi.fn(),
    getSubmissionDetail: vi.fn(),
    downloadReceipt: vi.fn(),
  },
}));

interface ApiError {
  response?: { data?: { message?: string } };
}

function isApiError(error: unknown): error is ApiError {
  return typeof error === 'object' && error !== null && 'response' in error;
}

describe('useAssistance', () => {
  let composable: ReturnType<typeof useAssistance>;

  beforeEach(() => {
    vi.clearAllMocks();
    composable = useAssistance();
  });

  // ===== HAPPY PATH =====

  it('test_fetchPrograms_memanggil_getPrograms', async () => {
    (AssistanceService.getPrograms as ReturnType<typeof vi.fn>).mockResolvedValue([]);
    await composable.fetchPrograms();
    expect(AssistanceService.getPrograms).toHaveBeenCalled();
  });

  it('test_fetchPrograms_set_programs', async () => {
    const mock = [{ id: '1', title: 'Program A' }];
    (AssistanceService.getPrograms as ReturnType<typeof vi.fn>).mockResolvedValue(mock);
    await composable.fetchPrograms();
    expect(composable.programs.value).toEqual(mock);
  });

  it('test_fetchRegencies_memanggil_getRegencies', async () => {
    (AssistanceService.getRegencies as ReturnType<typeof vi.fn>).mockResolvedValue([]);
    await composable.fetchRegencies();
    expect(AssistanceService.getRegencies).toHaveBeenCalled();
  });

  it('test_submitAssistance_memanggil_submitRegistration', async () => {
    const mockResponse = { data: { registration_number: 'SBN-001', status: 'pending' } };
    (AssistanceService.submitRegistration as ReturnType<typeof vi.fn>).mockResolvedValue(mockResponse);

    const payload: AssistanceSubmissionPayload = {
      program_id: '1', regency_id: '6301', district_id: '6301020', village_id: '6301020001',
      disbursement_method: 'village_cash', dynamicInputs: {}, files: {},
    };

    const result = await composable.submitAssistance(payload);
    expect(result).toEqual(mockResponse);
  });

  // ===== SAD PATH =====

  it('test_fetchPrograms_set_error_jika_gagal', async () => {
    (AssistanceService.getPrograms as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Gagal'));
    try { await composable.fetchPrograms(); } catch { /* expected */ }
    expect(composable.error.value).toBe('Gagal memuat daftar program bantuan.');
  });

  it('test_submitAssistance_set_error_jika_gagal', async () => {
    const error = { response: { data: { message: 'Gagal' } } };
    (AssistanceService.submitRegistration as ReturnType<typeof vi.fn>).mockRejectedValue(error);

    const payload: AssistanceSubmissionPayload = {
      program_id: '1', regency_id: '6301', district_id: '6301020', village_id: '6301020001',
      disbursement_method: 'village_cash', dynamicInputs: {}, files: {},
    };

    try { await composable.submitAssistance(payload); } catch { /* expected */ }
    expect(composable.error.value).toBe('Gagal');
  });

  // ===== NULL/EMPTY =====

  it('test_isLoading_default_false', () => {
    expect(composable.isLoading.value).toBe(false);
  });

  it('test_error_default_null', () => {
    expect(composable.error.value).toBeNull();
  });
});