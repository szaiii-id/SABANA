// =============================================
// useProgramSave.spec.ts
// =============================================

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useProgramSave } from '../useProgramSave';
import ProgramService from '../../services/ProgramService';
import type { ProgramPayload } from '../../types/program';

// =============================================
// MOCK PROGRAM SERVICE
// =============================================
vi.mock('../../services/ProgramService', () => ({
  default: {
    createProgram: vi.fn(),
    updateProgram: vi.fn(),
    uploadBanner: vi.fn(),
  },
}));

// =============================================
// HELPERS
// =============================================
const validPayload: ProgramPayload = {
  name: 'BLT Dana Desa',
  description: 'Bantuan Langsung Tunai',
  start_date: '2026-01-01',
  end_date: '2026-12-31',
  status: 'draft',
  quota_total: null,
  benefit_amount: null,
  criteria: {},
  banner: null,
  banner_url: null,
};

// =============================================
// TEST SUITE
// =============================================
describe('useProgramSave', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===== HAPPY PATH =====

  it('test_handle_save_create_mode', async () => {
    const { handleSave, isSubmitting } = useProgramSave();
    const onSuccess = vi.fn();
    (ProgramService.createProgram as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { id: 'new-uuid' }
    });
    await handleSave(validPayload, 'add', '', onSuccess);
    expect(ProgramService.createProgram).toHaveBeenCalled();
    expect(onSuccess).toHaveBeenCalled();
    expect(isSubmitting.value).toBe(false);
  });

  it('test_handle_save_edit_mode', async () => {
    const { handleSave, isSubmitting } = useProgramSave();
    const onSuccess = vi.fn();
    (ProgramService.updateProgram as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { id: 'existing-uuid' }
    });
    await handleSave(validPayload, 'edit', 'existing-uuid', onSuccess);
    expect(ProgramService.updateProgram).toHaveBeenCalled();
    expect(onSuccess).toHaveBeenCalled();
    expect(isSubmitting.value).toBe(false);
  });

  it('test_handle_save_dengan_banner', async () => {
    const { handleSave } = useProgramSave();
    const onSuccess = vi.fn();
    const file = new File([''], 'banner.jpg', { type: 'image/jpeg' });
    const payloadWithBanner = { ...validPayload, banner: file };
    (ProgramService.createProgram as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { id: 'new-uuid' }
    });
    (ProgramService.uploadBanner as ReturnType<typeof vi.fn>).mockResolvedValue({});
    await handleSave(payloadWithBanner, 'add', '', onSuccess);
    expect(ProgramService.uploadBanner).toHaveBeenCalled();
  });

  // ===== SAD PATH =====

  it('test_handle_save_error_dari_api', async () => {
    const { handleSave, errorMessage } = useProgramSave();
    const onSuccess = vi.fn();
    (ProgramService.createProgram as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { data: { message: 'Gagal membuat program.' } }
    });
    await handleSave(validPayload, 'add', '', onSuccess);
    expect(errorMessage.value).toBe('Gagal membuat program.');
  });

  it('test_handle_save_error_instanceof_error', async () => {
    const { handleSave, errorMessage } = useProgramSave();
    const onSuccess = vi.fn();
    (ProgramService.createProgram as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Network error'));
    await handleSave(validPayload, 'add', '', onSuccess);
    expect(errorMessage.value).toBe('Network error');
  });

  // ===== SUCCESS MESSAGE =====

  it('test_show_success_message', () => {
    const { showSuccess, successMessage, showSuccessMessage } = useProgramSave();
    showSuccessMessage('Program berhasil dibuat.');
    expect(showSuccess.value).toBe(true);
    expect(successMessage.value).toBe('Program berhasil dibuat.');
  });

  // ===== NULL / EMPTY =====

  it('test_initial_state', () => {
    const { isSubmitting, errorMessage, uploadProgress, uploadStep, showSuccess } = useProgramSave();
    expect(isSubmitting.value).toBe(false);
    expect(errorMessage.value).toBe('');
    expect(uploadProgress.value).toBe(0);
    expect(uploadStep.value).toBe('data');
    expect(showSuccess.value).toBe(false);
  });

  // ===== CRITERIA HANDLING =====

  it('test_handle_save_tanpa_criteria_tidak_dikirim', async () => {
    const { handleSave } = useProgramSave();
    const onSuccess = vi.fn();
    const payloadWithoutCriteria = { ...validPayload, criteria: {} };
    (ProgramService.createProgram as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { id: 'new-uuid' }
    });
    await handleSave(payloadWithoutCriteria, 'add', '', onSuccess);
    const callArgs = (ProgramService.createProgram as ReturnType<typeof vi.fn>).mock.calls[0][0];
    expect(callArgs.criteria).toBeUndefined();
  });

  it('test_handle_save_dengan_criteria_dikirim', async () => {
    const { handleSave } = useProgramSave();
    const onSuccess = vi.fn();
    const payloadWithCriteria = {
      ...validPayload,
      criteria: { targets: ['miskin_ekstrem'], documents: ['ktp'] }
    };
    (ProgramService.createProgram as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { id: 'new-uuid' }
    });
    await handleSave(payloadWithCriteria, 'add', '', onSuccess);
    const callArgs = (ProgramService.createProgram as ReturnType<typeof vi.fn>).mock.calls[0][0];
    expect(callArgs.criteria).toBeDefined();
  });
});