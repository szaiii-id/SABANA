// resources/js/tests/composables/useEvaluation.spec.ts

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useEvaluation } from '../../composables/useEvaluation';
import { EvaluationService } from '../../services/EvaluationService';
import type { Evaluation } from '../../types/evaluation';

vi.mock('../../services/EvaluationService');

// ===== HELPERS =====

function validEvaluations(): Evaluation[] {
  return [
    {
      id: 'eval-1',
      status: 'updated',
      status_label: 'Menunggu Verifikasi',
      decision_notes: null,
      triggered_at: '2026-06-01',
      decided_at: null,
      submission: { id: 'sub-1', registration_number: 'SBN-OLD', status: 'evaluation_pending' },
      new_submission: { id: 'sub-2', registration_number: 'SBN-NEW', status: 'validated', smart_score: 75, recommendation: { label: 'Direkomendasikan', color: 'green' } },
      citizen: { id: 'cit-1', full_name: 'Test', nik: '123' },
      program: { id: 'prog-1', name: 'BLT' },
      old_data: null,
      location: { village: 'Desa', district: 'Kec', regency: 'Kab' },
    },
  ];
}

describe('useEvaluation', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===== PILAR 1: FETCH EVALUATIONS =====

  // 1.1 Happy Path
  it('fetchEvaluations berhasil mengisi data evaluations', async () => {
    const mockData = validEvaluations();
    vi.mocked(EvaluationService.getList).mockResolvedValue({ status: 'success', data: mockData });

    const { evaluations, fetchEvaluations, loading, error } = useEvaluation();

    await fetchEvaluations();

    expect(evaluations.value).toEqual(mockData);
    expect(loading.value).toBe(false);
    expect(error.value).toBeNull();
  });

  // 1.2 Sad Path — API error dengan pesan
  it('fetchEvaluations menangani error API dengan pesan', async () => {
    vi.mocked(EvaluationService.getList).mockRejectedValue({ response: { data: { message: 'Gagal memuat data.' } } });

    const { error, fetchEvaluations, evaluations } = useEvaluation();
    await fetchEvaluations();

    expect(error.value).toBe('Gagal memuat data.');
    expect(evaluations.value).toEqual([]);
  });

  // 1.3 Sad Path — API error tanpa response
  it('fetchEvaluations menangani error jaringan', async () => {
    vi.mocked(EvaluationService.getList).mockRejectedValue(new Error('Network Error'));

    const { error, fetchEvaluations } = useEvaluation();
    await fetchEvaluations();

    expect(error.value).toBe('Gagal memuat data evaluasi.');
  });

  // 1.4 Boundary — response.data null
  it('fetchEvaluations handle response.data null', async () => {
    vi.mocked(EvaluationService.getList).mockResolvedValue({ status: 'success', data: null as unknown as Evaluation[] });

    const { evaluations, fetchEvaluations } = useEvaluation();
    await fetchEvaluations();

    expect(evaluations.value).toEqual([]);
  });

  // 1.5 Edge Case — response tidak punya data
  it('fetchEvaluations handle response tanpa data', async () => {
    vi.mocked(EvaluationService.getList).mockResolvedValue({ status: 'success' } as any);

    const { evaluations, fetchEvaluations } = useEvaluation();
    await fetchEvaluations();

    expect(evaluations.value).toEqual([]);
  });

  // 1.6 Data Type — parameter filter
  it('fetchEvaluations menerima parameter filter', async () => {
    vi.mocked(EvaluationService.getList).mockResolvedValue({ status: 'success', data: [] });

    const { fetchEvaluations } = useEvaluation();
    await fetchEvaluations({ status: 'updated' });

    expect(EvaluationService.getList).toHaveBeenCalledWith({ status: 'updated' });
  });

  // 1.7 Loading State — true selama fetch
  it('loading true selama fetchEvaluations', async () => {
    vi.mocked(EvaluationService.getList).mockImplementation(() => new Promise(r => setTimeout(() => r({ status: 'success', data: [] }), 50)));

    const { fetchEvaluations, loading } = useEvaluation();
    const promise = fetchEvaluations();

    expect(loading.value).toBe(true);
    await promise;
    expect(loading.value).toBe(false);
  });

  // ===== PILAR 2: APPROVE =====

  // 2.1 Happy Path
  it('approveEvaluation berhasil', async () => {
    vi.mocked(EvaluationService.approve).mockResolvedValue(undefined);

    const { approveEvaluation, successMessage, loading, error } = useEvaluation();
    const result = await approveEvaluation('eval-1');

    expect(result).toBe(true);
    expect(successMessage.value).toBe('Evaluasi disetujui. Bantuan dilanjutkan.');
    expect(loading.value).toBe(false);
    expect(error.value).toBeNull();
  });

  // 2.2 Sad Path — gagal
  it('approveEvaluation gagal dengan pesan error', async () => {
    vi.mocked(EvaluationService.approve).mockRejectedValue({ response: { data: { message: 'Gagal menyetujui.' } } });

    const { approveEvaluation, error } = useEvaluation();
    const result = await approveEvaluation('eval-1');

    expect(result).toBe(false);
    expect(error.value).toBe('Gagal menyetujui.');
  });

  // ===== PILAR 3: REVOKE =====

  // 3.1 Happy Path
  it('revokeEvaluation berhasil', async () => {
    vi.mocked(EvaluationService.revoke).mockResolvedValue(undefined);

    const { revokeEvaluation, successMessage, loading, error } = useEvaluation();
    const result = await revokeEvaluation('eval-1', 'Alasan penolakan yang valid.');

    expect(result).toBe(true);
    expect(successMessage.value).toBe('Evaluasi ditolak. Bantuan dihentikan.');
    expect(loading.value).toBe(false);
    expect(error.value).toBeNull();
  });

  // 3.2 Sad Path — gagal
  it('revokeEvaluation gagal dengan pesan error', async () => {
    vi.mocked(EvaluationService.revoke).mockRejectedValue({ response: { data: { message: 'Gagal menolak.' } } });

    const { revokeEvaluation, error } = useEvaluation();
    const result = await revokeEvaluation('eval-1', 'Alasan cukup panjang.');

    expect(result).toBe(false);
    expect(error.value).toBe('Gagal menolak.');
  });

  // ===== PILAR 4: RESET =====

  it('reset mengosongkan semua state', () => {
    const { evaluations, loading, error, successMessage, reset } = useEvaluation();

    evaluations.value = validEvaluations();
    loading.value = true;
    error.value = 'err';
    successMessage.value = 'ok';

    reset();

    expect(evaluations.value).toEqual([]);
    expect(loading.value).toBe(false);
    expect(error.value).toBeNull();
    expect(successMessage.value).toBeNull();
  });
});