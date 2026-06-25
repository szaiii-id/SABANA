import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAssistance } from '../useAssistance';
import { AssistanceService } from '../../services/AssistanceService';
import type { AssistanceSubmissionPayload } from '../../types/assistance';

vi.mock('../../services/AssistanceService', () => ({
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

describe('useAssistance', () => {
  let composable: ReturnType<typeof useAssistance>;

  beforeEach(() => {
    vi.clearAllMocks();
    composable = useAssistance();
  });

  // =============================================
  // INITIAL STATE — 6 TEST
  // =============================================

  it('test_isLoading_default_false', () => {
    expect(composable.isLoading.value).toBe(false);
  });

  it('test_error_default_null', () => {
    expect(composable.error.value).toBeNull();
  });

  it('test_programs_default_empty', () => {
    expect(composable.programs.value).toEqual([]);
  });

  it('test_regencies_default_empty', () => {
    expect(composable.regencies.value).toEqual([]);
  });

  it('test_districts_default_empty', () => {
    expect(composable.districts.value).toEqual([]);
  });

  it('test_villages_default_empty', () => {
    expect(composable.villages.value).toEqual([]);
  });

  // =============================================
  // FETCH PROGRAMS — 4 TEST
  // =============================================

  it('test_fetchPrograms_memanggil_getPrograms', async () => {
    (AssistanceService.getPrograms as any).mockResolvedValue([]);
    await composable.fetchPrograms();
    expect(AssistanceService.getPrograms).toHaveBeenCalled();
  });

  it('test_fetchPrograms_set_programs_dari_array_langsung', async () => {
    const mockData = [{ id: '1', title: 'Program A' }];
    (AssistanceService.getPrograms as any).mockResolvedValue(mockData);
    await composable.fetchPrograms();
    expect(composable.programs.value).toEqual(mockData);
  });

  it('test_fetchPrograms_set_programs_dari_response_data', async () => {
    const mockData = [{ id: '1', title: 'Program A' }];
    (AssistanceService.getPrograms as any).mockResolvedValue(mockData);
    await composable.fetchPrograms();
    expect(composable.programs.value).toEqual(mockData);
  });

  it('test_fetchPrograms_set_error_jika_gagal', async () => {
    (AssistanceService.getPrograms as any).mockRejectedValue(new Error('Gagal'));
    try { await composable.fetchPrograms(); } catch (e) {}
    expect(composable.error.value).toBe('Gagal memuat daftar program bantuan.');
  });

  // =============================================
  // FETCH REGENCIES — 3 TEST
  // =============================================

  it('test_fetchRegencies_memanggil_getRegencies', async () => {
    (AssistanceService.getRegencies as any).mockResolvedValue([]);
    await composable.fetchRegencies();
    expect(AssistanceService.getRegencies).toHaveBeenCalled();
  });

  it('test_fetchRegencies_set_regencies', async () => {
    const mock = [{ id: '6301', name: 'Tanah Laut' }];
    (AssistanceService.getRegencies as any).mockResolvedValue(mock);
    await composable.fetchRegencies();
    expect(composable.regencies.value).toEqual(mock);
  });

  it('test_fetchRegencies_set_error_jika_gagal', async () => {
    (AssistanceService.getRegencies as any).mockRejectedValue(new Error('Gagal'));
    try { await composable.fetchRegencies(); } catch (e) {}
    expect(composable.error.value).toBe('Gagal memuat data kabupaten.');
  });

  // =============================================
  // FETCH DISTRICTS — 3 TEST
  // =============================================

  it('test_fetchDistricts_memanggil_getDistricts', async () => {
    (AssistanceService.getDistricts as any).mockResolvedValue([]);
    await composable.fetchDistricts('6301');
    expect(AssistanceService.getDistricts).toHaveBeenCalledWith('6301');
  });

  it('test_fetchDistricts_set_districts', async () => {
    const mock = [{ id: '6301020', name: 'Pelaihari' }];
    (AssistanceService.getDistricts as any).mockResolvedValue(mock);
    await composable.fetchDistricts('6301');
    expect(composable.districts.value).toEqual(mock);
  });

  it('test_fetchDistricts_set_error_jika_gagal', async () => {
    (AssistanceService.getDistricts as any).mockRejectedValue(new Error('Gagal'));
    try { await composable.fetchDistricts('6301'); } catch (e) {}
    expect(composable.error.value).toBe('Gagal memuat data kecamatan.');
  });

  // =============================================
  // FETCH VILLAGES — 3 TEST
  // =============================================

  it('test_fetchVillages_memanggil_getVillages', async () => {
    (AssistanceService.getVillages as any).mockResolvedValue([]);
    await composable.fetchVillages('6301020');
    expect(AssistanceService.getVillages).toHaveBeenCalledWith('6301020');
  });

  it('test_fetchVillages_set_villages', async () => {
    const mock = [{ id: '6301020001', name: 'Desa Test' }];
    (AssistanceService.getVillages as any).mockResolvedValue(mock);
    await composable.fetchVillages('6301020');
    expect(composable.villages.value).toEqual(mock);
  });

  it('test_fetchVillages_set_error_jika_gagal', async () => {
    (AssistanceService.getVillages as any).mockRejectedValue(new Error('Gagal'));
    try { await composable.fetchVillages('6301020'); } catch (e) {}
    expect(composable.error.value).toBe('Gagal memuat data desa.');
  });

  // =============================================
  // SUBMIT ASSISTANCE — 5 TEST
  // =============================================

  const mockPayload: AssistanceSubmissionPayload = {
    program_id: '1', regency_id: '6301', district_id: '6301020', village_id: '6301020001',
    disbursement_method: 'village_cash', dynamicInputs: {}, files: {},
  };

  it('test_submitAssistance_memanggil_submitRegistration', async () => {
    (AssistanceService.submitRegistration as any).mockResolvedValue({ data: { registration_number: 'SBN-001' } });
    await composable.submitAssistance(mockPayload);
    expect(AssistanceService.submitRegistration).toHaveBeenCalledWith(mockPayload, undefined);
  });

  it('test_submitAssistance_return_response_jika_sukses', async () => {
    const mockResponse = { data: { registration_number: 'SBN-001', status: 'pending' } };
    (AssistanceService.submitRegistration as any).mockResolvedValue(mockResponse);
    const result = await composable.submitAssistance(mockPayload);
    expect(result).toEqual(mockResponse);
  });

  it('test_submitAssistance_isLoading_true_selama_proses', () => {
    (AssistanceService.submitRegistration as any).mockReturnValue(new Promise(() => {}));
    composable.submitAssistance(mockPayload);
    expect(composable.isLoading.value).toBe(true);
  });

  it('test_submitAssistance_set_error_jika_gagal', async () => {
    (AssistanceService.submitRegistration as any).mockRejectedValue({ response: { data: { message: 'Gagal' } } });
    try { await composable.submitAssistance(mockPayload); } catch (e) {}
    expect(composable.error.value).toBe('Gagal');
  });

  it('test_submitAssistance_error_default_jika_network_error', async () => {
    (AssistanceService.submitRegistration as any).mockRejectedValue(new Error('Network'));
    try { await composable.submitAssistance(mockPayload); } catch (e) {}
    expect(composable.error.value).toBe('Terjadi kesalahan saat memproses pengajuan.');
  });

  // =============================================
  // FETCH MY SUBMISSIONS — 3 TEST
  // =============================================

  it('test_fetchMySubmissions_memanggil_getMySubmissions', async () => {
    (AssistanceService.getMySubmissions as any).mockResolvedValue({ data: [] });
    await composable.fetchMySubmissions();
    expect(AssistanceService.getMySubmissions).toHaveBeenCalled();
  });

  it('test_fetchMySubmissions_isLoading_true_selama_proses', () => {
    (AssistanceService.getMySubmissions as any).mockReturnValue(new Promise(() => {}));
    composable.fetchMySubmissions();
    expect(composable.isLoading.value).toBe(true);
  });

  it('test_fetchMySubmissions_set_error_jika_gagal', async () => {
    (AssistanceService.getMySubmissions as any).mockRejectedValue(new Error('Gagal'));
    try { await composable.fetchMySubmissions(); } catch (e) {}
    expect(composable.error.value).toBe('Gagal memuat riwayat pengajuan.');
  });

  // =============================================
  // UPDATE ASSISTANCE — 2 TEST
  // =============================================

  it('test_updateAssistance_memanggil_updateRegistration', async () => {
    (AssistanceService.updateRegistration as any).mockResolvedValue({});
    await composable.updateAssistance('1', mockPayload);
    expect(AssistanceService.updateRegistration).toHaveBeenCalledWith('1', mockPayload);
  });

  it('test_updateAssistance_set_error_jika_gagal', async () => {
    (AssistanceService.updateRegistration as any).mockRejectedValue({ response: { data: { message: 'Gagal' } } });
    try { await composable.updateAssistance('1', mockPayload); } catch (e) {}
    expect(composable.error.value).toBe('Gagal');
  });

  // =============================================
  // DELETE ASSISTANCE — 2 TEST
  // =============================================

  it('test_deleteAssistance_memanggil_cancelRegistration', async () => {
    (AssistanceService.cancelRegistration as any).mockResolvedValue({});
    await composable.deleteAssistance('SBN-001');
    expect(AssistanceService.cancelRegistration).toHaveBeenCalledWith('SBN-001');
  });

  it('test_deleteAssistance_set_error_jika_gagal', async () => {
    (AssistanceService.cancelRegistration as any).mockRejectedValue({ response: { data: { message: 'Gagal' } } });
    try { await composable.deleteAssistance('SBN-001'); } catch (e) {}
    expect(composable.error.value).toBe('Gagal');
  });

  // =============================================
  // FETCH DETAIL — 4 TEST
  // =============================================

  it('test_fetchDetail_memanggil_getSubmissionDetail', async () => {
    (AssistanceService.getSubmissionDetail as any).mockResolvedValue({ data: { id: '1' } });
    await composable.fetchDetail('1');
    expect(AssistanceService.getSubmissionDetail).toHaveBeenCalledWith('1');
  });

  it('test_fetchDetail_return_data_jika_ada_wrapper', async () => {
    const mock = { data: { id: '1' } };
    (AssistanceService.getSubmissionDetail as any).mockResolvedValue(mock);
    const result = await composable.fetchDetail('1');
    expect(result).toEqual({ id: '1' });
  });

  it('test_fetchDetail_return_langsung_jika_tidak_ada_wrapper', async () => {
    const mock = { id: '1' };
    (AssistanceService.getSubmissionDetail as any).mockResolvedValue(mock);
    const result = await composable.fetchDetail('1');
    expect(result).toEqual(mock);
  });

  it('test_fetchDetail_set_error_jika_gagal', async () => {
    (AssistanceService.getSubmissionDetail as any).mockRejectedValue(new Error('Gagal'));
    try { await composable.fetchDetail('1'); } catch (e) {}
    expect(composable.error.value).toBe('Gagal memuat detail.');
  });

  // =============================================
  // DOWNLOAD PDF — 3 TEST
  // =============================================

  it('test_downloadPdf_memanggil_downloadReceipt', async () => {
    (AssistanceService.downloadReceipt as any).mockResolvedValue(new Blob());
    await composable.downloadPdf('1', 'test.pdf');
    expect(AssistanceService.downloadReceipt).toHaveBeenCalledWith('1');
  });

  it('test_downloadPdf_membuka_pdf_di_tab_baru', async () => {
    const mockBlob = new Blob();
    (AssistanceService.downloadReceipt as any).mockResolvedValue(mockBlob);
    const openSpy = vi.spyOn(window, 'open').mockReturnValue(null);
    await composable.downloadPdf('1', 'test.pdf');
    expect(openSpy).toHaveBeenCalled();
    openSpy.mockRestore();
  });

  it('test_downloadPdf_set_error_jika_gagal', async () => {
    (AssistanceService.downloadReceipt as any).mockRejectedValue(new Error('Gagal'));
    try { await composable.downloadPdf('1', 'test.pdf'); } catch (e) {}
    expect(composable.error.value).toBe('Gagal memproses preview PDF.');
  });
});