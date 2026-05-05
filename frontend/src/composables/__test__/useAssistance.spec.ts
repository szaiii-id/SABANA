// src/composables/__test__/useAssistance.spec.ts
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAssistance } from '../useAssistance';
import { AssistanceService } from '../../services/AssistanceService';

// ============================================================
// MOCKS
// ============================================================
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

// Mock window.URL
vi.stubGlobal('URL', {
  createObjectURL: vi.fn(() => 'blob:mock-url'),
  revokeObjectURL: vi.fn(),
});

// Mock window.open
const windowOpenMock = vi.fn();
vi.stubGlobal('open', windowOpenMock);

// ============================================================
// TEST SUITE
// ============================================================
describe('useAssistance - Professional QA Test Suite', () => {

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ============================================================
  // INITIAL STATE
  // ============================================================
  describe('Initial State', () => {

    it('[INIT-01] isLoading default false', () => {
      const { isLoading } = useAssistance();
      expect(isLoading.value).toBe(false);
    });

    it('[INIT-02] error default null', () => {
      const { error } = useAssistance();
      expect(error.value).toBeNull();
    });

    it('[INIT-03] programs default empty array', () => {
      const { programs } = useAssistance();
      expect(programs.value).toEqual([]);
    });

    it('[INIT-04] regencies default empty array', () => {
      const { regencies } = useAssistance();
      expect(regencies.value).toEqual([]);
    });

    it('[INIT-05] districts default empty array', () => {
      const { districts } = useAssistance();
      expect(districts.value).toEqual([]);
    });

    it('[INIT-06] villages default empty array', () => {
      const { villages } = useAssistance();
      expect(villages.value).toEqual([]);
    });
  });

  // ============================================================
  // fetchPrograms
  // ============================================================
  describe('fetchPrograms', () => {

    it('[PROG-01] Memanggil AssistanceService.getPrograms', async () => {
      const { fetchPrograms } = useAssistance();
      (AssistanceService.getPrograms as any).mockResolvedValue([{ id: '1' }]);

      await fetchPrograms();

      expect(AssistanceService.getPrograms).toHaveBeenCalledTimes(1);
    });

    it('[PROG-02] Set programs dengan response langsung (array)', async () => {
      const { fetchPrograms, programs } = useAssistance();
      const mockData = [{ id: '1', title: 'Program A' }];
      (AssistanceService.getPrograms as any).mockResolvedValue(mockData);

      await fetchPrograms();

      expect(programs.value).toEqual(mockData);
    });

    it('[PROG-03] Set programs dengan response.data wrapper', async () => {
      const { fetchPrograms, programs } = useAssistance();
      const mockData = [{ id: '1', title: 'Program A' }];
      (AssistanceService.getPrograms as any).mockResolvedValue({ data: mockData });

      await fetchPrograms();

      expect(programs.value).toEqual(mockData);
    });

    it('[PROG-04] Set error jika gagal', async () => {
      const { fetchPrograms, error } = useAssistance();
      (AssistanceService.getPrograms as any).mockRejectedValue(new Error('Network Error'));

      await fetchPrograms();

      expect(error.value).toBe('Gagal memuat daftar program bantuan.');
    });
  });

  // ============================================================
  // fetchRegencies
  // ============================================================
  describe('fetchRegencies', () => {

    it('[REG-01] Memanggil AssistanceService.getRegencies', async () => {
      const { fetchRegencies } = useAssistance();
      (AssistanceService.getRegencies as any).mockResolvedValue([{ id: '1', name: 'Banjar' }]);

      await fetchRegencies();

      expect(AssistanceService.getRegencies).toHaveBeenCalledTimes(1);
    });

    it('[REG-02] Set regencies dengan response', async () => {
      const { fetchRegencies, regencies } = useAssistance();
      const mockData = [{ id: '1', name: 'Banjar' }];
      (AssistanceService.getRegencies as any).mockResolvedValue(mockData);

      await fetchRegencies();

      expect(regencies.value).toEqual(mockData);
    });

    it('[REG-03] Set error jika gagal', async () => {
      const { fetchRegencies, error } = useAssistance();
      (AssistanceService.getRegencies as any).mockRejectedValue(new Error('Error'));

      await fetchRegencies();

      expect(error.value).toBe('Gagal memuat data kabupaten.');
    });
  });

  // ============================================================
  // fetchDistricts
  // ============================================================
  describe('fetchDistricts', () => {

    it('[DIST-01] Memanggil AssistanceService.getDistricts dengan regencyId', async () => {
      const { fetchDistricts } = useAssistance();
      (AssistanceService.getDistricts as any).mockResolvedValue([]);

      await fetchDistricts('6301');

      expect(AssistanceService.getDistricts).toHaveBeenCalledWith('6301');
    });

    it('[DIST-02] Set districts dengan response', async () => {
      const { fetchDistricts, districts } = useAssistance();
      const mockData = [{ id: '630101', name: 'Martapura' }];
      (AssistanceService.getDistricts as any).mockResolvedValue(mockData);

      await fetchDistricts('6301');

      expect(districts.value).toEqual(mockData);
    });

    it('[DIST-03] Set error jika gagal', async () => {
      const { fetchDistricts, error } = useAssistance();
      (AssistanceService.getDistricts as any).mockRejectedValue(new Error('Error'));

      await fetchDistricts('6301');

      expect(error.value).toBe('Gagal memuat data kecamatan.');
    });
  });

  // ============================================================
  // fetchVillages
  // ============================================================
  describe('fetchVillages', () => {

    it('[VILL-01] Memanggil AssistanceService.getVillages dengan districtId', async () => {
      const { fetchVillages } = useAssistance();
      (AssistanceService.getVillages as any).mockResolvedValue([]);

      await fetchVillages('630101');

      expect(AssistanceService.getVillages).toHaveBeenCalledWith('630101');
    });

    it('[VILL-02] Set villages dengan response', async () => {
      const { fetchVillages, villages } = useAssistance();
      const mockData = [{ id: '6301012001', name: 'Sungai Paring' }];
      (AssistanceService.getVillages as any).mockResolvedValue(mockData);

      await fetchVillages('630101');

      expect(villages.value).toEqual(mockData);
    });

    it('[VILL-03] Set error jika gagal', async () => {
      const { fetchVillages, error } = useAssistance();
      (AssistanceService.getVillages as any).mockRejectedValue(new Error('Error'));

      await fetchVillages('630101');

      expect(error.value).toBe('Gagal memuat data desa.');
    });
  });

  // ============================================================
  // submitAssistance
  // ============================================================
  describe('submitAssistance', () => {

    const mockPayload: any = {
      program_id: '1',
      regency_id: '6301',
      district_id: '630101',
      village_id: '6301012001',
      disbursement_method: 'village_cash',
      bank_account_number: '',
      dynamicInputs: {},
      files: {},
    };

    it('[SUB-01] Memanggil AssistanceService.submitRegistration', async () => {
      const { submitAssistance } = useAssistance();
      (AssistanceService.submitRegistration as any).mockResolvedValue({ data: {} });

      await submitAssistance(mockPayload);

      expect(AssistanceService.submitRegistration).toHaveBeenCalledWith(mockPayload);
    });

    it('[SUB-02] Return response jika sukses', async () => {
      const { submitAssistance } = useAssistance();
      const mockResponse = { data: { registration_number: 'REG-001' } };
      (AssistanceService.submitRegistration as any).mockResolvedValue(mockResponse);

      const result = await submitAssistance(mockPayload);

      expect(result).toEqual(mockResponse);
    });

    it('[SUB-03] isLoading true selama proses', async () => {
      const { submitAssistance, isLoading } = useAssistance();
      let resolve: any;
      (AssistanceService.submitRegistration as any).mockReturnValue(new Promise((r: any) => { resolve = r; }));

      const promise = submitAssistance(mockPayload);
      expect(isLoading.value).toBe(true);

      resolve({ data: {} });
      await promise;
      expect(isLoading.value).toBe(false);
    });

    it('[SUB-04] Set error jika gagal', async () => {
      const { submitAssistance, error } = useAssistance();
      (AssistanceService.submitRegistration as any).mockRejectedValue({
        response: { data: { message: 'Data tidak valid' } }
      });

      await expect(submitAssistance(mockPayload)).rejects.toThrow();
      expect(error.value).toBe('Data tidak valid');
    });

    it('[SUB-05] error default jika network error', async () => {
      const { submitAssistance, error } = useAssistance();
      (AssistanceService.submitRegistration as any).mockRejectedValue(new Error('Network'));

      await expect(submitAssistance(mockPayload)).rejects.toThrow();
      expect(error.value).toBe('Terjadi kesalahan saat memproses pengajuan.');
    });
  });

  // ============================================================
  // fetchMySubmissions
  // ============================================================
  describe('fetchMySubmissions', () => {

    it('[HIST-01] Memanggil AssistanceService.getMySubmissions', async () => {
      const { fetchMySubmissions } = useAssistance();
      (AssistanceService.getMySubmissions as any).mockResolvedValue({ data: [] });

      await fetchMySubmissions();

      expect(AssistanceService.getMySubmissions).toHaveBeenCalledTimes(1);
    });

    it('[HIST-02] isLoading true selama proses', async () => {
      const { fetchMySubmissions, isLoading } = useAssistance();
      let resolve: any;
      (AssistanceService.getMySubmissions as any).mockReturnValue(new Promise((r: any) => { resolve = r; }));

      const promise = fetchMySubmissions();
      expect(isLoading.value).toBe(true);

      resolve({ data: [] });
      await promise;
      expect(isLoading.value).toBe(false);
    });

    it('[HIST-03] Error jika gagal', async () => {
      const { fetchMySubmissions, error } = useAssistance();
      (AssistanceService.getMySubmissions as any).mockRejectedValue(new Error('Error'));

      await expect(fetchMySubmissions()).rejects.toThrow();
      expect(error.value).toBe('Gagal memuat riwayat pengajuan.');
    });
  });

  // ============================================================
  // updateAssistance
  // ============================================================
  describe('updateAssistance', () => {

    const mockPayload: any = {
      regency_id: '6301',
      district_id: '630101',
      village_id: '6301012001',
      disbursement_method: 'village_cash',
      bank_account_number: '',
      dynamicInputs: {},
      files: {},
    };

    it('[UPD-01] Memanggil AssistanceService.updateRegistration', async () => {
      const { updateAssistance } = useAssistance();
      (AssistanceService.updateRegistration as any).mockResolvedValue({});

      await updateAssistance('uuid-123', mockPayload);

      expect(AssistanceService.updateRegistration).toHaveBeenCalledWith('uuid-123', mockPayload);
    });

    it('[UPD-02] Error handling', async () => {
      const { updateAssistance, error } = useAssistance();
      (AssistanceService.updateRegistration as any).mockRejectedValue({
        response: { data: { message: 'Gagal update' } }
      });

      await expect(updateAssistance('uuid-123', mockPayload)).rejects.toThrow();
      expect(error.value).toBe('Gagal update');
    });
  });

  // ============================================================
  // deleteAssistance
  // ============================================================
  describe('deleteAssistance', () => {

    it('[DEL-01] Memanggil AssistanceService.cancelRegistration', async () => {
      const { deleteAssistance } = useAssistance();
      (AssistanceService.cancelRegistration as any).mockResolvedValue({ success: true });

      await deleteAssistance('REG-001');

      expect(AssistanceService.cancelRegistration).toHaveBeenCalledWith('REG-001');
    });

    it('[DEL-02] Error handling', async () => {
      const { deleteAssistance, error } = useAssistance();
      (AssistanceService.cancelRegistration as any).mockRejectedValue({
        response: { data: { message: 'Gagal hapus' } }
      });

      await expect(deleteAssistance('REG-001')).rejects.toThrow();
      expect(error.value).toBe('Gagal hapus');
    });
  });

  // ============================================================
  // fetchDetail
  // ============================================================
  describe('fetchDetail', () => {

    it('[DET-01] Memanggil AssistanceService.getSubmissionDetail', async () => {
      const { fetchDetail } = useAssistance();
      (AssistanceService.getSubmissionDetail as any).mockResolvedValue({
        data: { id: 'uuid-1', status: 'validated' }
      });

      await fetchDetail('uuid-1');

      expect(AssistanceService.getSubmissionDetail).toHaveBeenCalledWith('uuid-1');
    });

    it('[DET-02] Return response.data jika ada wrapper', async () => {
      const { fetchDetail } = useAssistance();
      const mockData = { id: 'uuid-1', status: 'validated' };
      (AssistanceService.getSubmissionDetail as any).mockResolvedValue({ data: mockData });

      const result = await fetchDetail('uuid-1');

      expect(result).toEqual(mockData);
    });

    it('[DET-03] Return response langsung jika tidak ada wrapper', async () => {
      const { fetchDetail } = useAssistance();
      const mockData = { id: 'uuid-1', status: 'validated' };
      (AssistanceService.getSubmissionDetail as any).mockResolvedValue(mockData);

      const result = await fetchDetail('uuid-1');

      expect(result).toEqual(mockData);
    });

    it('[DET-04] Error handling', async () => {
      const { fetchDetail, error } = useAssistance();
      (AssistanceService.getSubmissionDetail as any).mockRejectedValue({
        response: { data: { message: 'Not found' } }
      });

      await expect(fetchDetail('uuid-1')).rejects.toThrow();
      expect(error.value).toBe('Not found');
    });
  });

  // ============================================================
  // downloadPdf
  // ============================================================
  describe('downloadPdf', () => {

    it('[PDF-01] Memanggil AssistanceService.downloadReceipt', async () => {
      const { downloadPdf } = useAssistance();
      const mockBlob = new Blob(['pdf'], { type: 'application/pdf' });
      (AssistanceService.downloadReceipt as any).mockResolvedValue(mockBlob);

      await downloadPdf('uuid-1', 'SABANA_REG-001');

      expect(AssistanceService.downloadReceipt).toHaveBeenCalledWith('uuid-1');
    });

    it('[PDF-02] Membuka PDF di tab baru', async () => {
      const { downloadPdf } = useAssistance();
      const mockBlob = new Blob(['pdf'], { type: 'application/pdf' });
      (AssistanceService.downloadReceipt as any).mockResolvedValue(mockBlob);

      await downloadPdf('uuid-1', 'SABANA_REG-001');

      expect(URL.createObjectURL).toHaveBeenCalled();
      expect(windowOpenMock).toHaveBeenCalledWith('blob:mock-url', '_blank');
    });

    it('[PDF-03] Error handling', async () => {
      const { downloadPdf, error } = useAssistance();
      (AssistanceService.downloadReceipt as any).mockRejectedValue(new Error('Error'));

      await expect(downloadPdf('uuid-1', 'test')).rejects.toThrow();
      expect(error.value).toBe('Gagal memproses preview PDF.');
    });
  });
});