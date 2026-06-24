import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useVerification } from '../../composables/useVerification';
import VerificationService from '../../services/VerificationService';
import type { VerificationData, VerificationFilters } from '../../types/verification';

vi.mock('../../services/VerificationService', () => ({
  default: {
    fetchVerifications: vi.fn(),
    fetchVerificationDetail: vi.fn(),
    approveVerification: vi.fn(),
    rejectVerification: vi.fn(),
    requestRevision: vi.fn(),
    completeVerification: vi.fn(),
    unvalidateVerification: vi.fn(),
    bulkCompleteVerification: vi.fn(),
  },
}));

describe('useVerification', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===== HAPPY PATH =====
  it('fetchVerifications mengisi data', async () => {
    (VerificationService.fetchVerifications as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: [{ id: '1', status: 'pending' }] as VerificationData[],
      current_page: 1,
      total: 1,
      last_page: 1,
    });

    const { verifications, fetchVerifications } = useVerification();
    await fetchVerifications({} as VerificationFilters);

    expect(verifications.value).toHaveLength(1);
  });

  it('approve mengembalikan true jika sukses', async () => {
    (VerificationService.approveVerification as ReturnType<typeof vi.fn>).mockResolvedValue({});

    const { approve } = useVerification();
    const result = await approve('id-1');

    expect(result).toBe(true);
  });

  // ===== SAD PATH =====
  it('fetchVerifications menangani error', async () => {
    (VerificationService.fetchVerifications as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Network error'));

    const { errorMessage, fetchVerifications } = useVerification();
    await fetchVerifications({} as VerificationFilters);

    expect(errorMessage.value).toContain('Gagal');
  });

  it('reject mengembalikan false jika gagal', async () => {
    (VerificationService.rejectVerification as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { data: { message: 'Error' } },
    });

    const { reject, errorMessage } = useVerification();
    const result = await reject('id-1', 'Alasan');

    expect(result).toBe(false);
    expect(errorMessage.value).toBe('Error');
  });

  // ===== LOADING STATE =====
  it('loading true saat fetch', () => {
    const { loading, fetchVerifications } = useVerification();
    fetchVerifications({} as VerificationFilters);
    expect(loading.value).toBe(true);
  });

  it('submitting true saat approve', () => {
    (VerificationService.approveVerification as ReturnType<typeof vi.fn>).mockResolvedValue({});

    const { submitting, approve } = useVerification();
    approve('id-1');
    expect(submitting.value).toBe(true);
  });
});