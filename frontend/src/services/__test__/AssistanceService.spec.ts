// src/services/__tests__/AssistanceService.spec.ts
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { AssistanceService } from '../AssistanceService';

// Mock semua API functions
vi.mock('../../api/assistanceApi', () => ({
  submitAssistanceApi: vi.fn(),
  getRegenciesApi: vi.fn(),
  getDistrictsApi: vi.fn(),
  getVillagesApi: vi.fn(),
  getProgramsApi: vi.fn(),
  getMySubmissionsApi: vi.fn(),
  updateAssistanceApi: vi.fn(),
  deleteAssistanceApi: vi.fn(),
  getSubmissionDetailApi: vi.fn(),
  downloadAssistancePdfApi: vi.fn(),
}));

// Import mock functions untuk digunakan di test
import {
  getProgramsApi,
  getRegenciesApi,
  getDistrictsApi,
  getVillagesApi,
  submitAssistanceApi,
  updateAssistanceApi,
  getMySubmissionsApi,
  deleteAssistanceApi,
  getSubmissionDetailApi,
  downloadAssistancePdfApi,
} from '../../api/assistanceApi';

describe('AssistanceService - Professional QA Test Suite', () => {

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ============================================================
  // GET PROGRAMS
  // ============================================================
  describe('getPrograms', () => {

    it('[PROG-01] Map raw API data to schema with default fallbacks', async () => {
      const rawApiData = [
        {
          id: '1',
          title: 'Program A',
          // No badge, iconSvg, inputs, files → use defaults
        },
        {
          id: '2',
          title: 'Program B',
          badge: 'Khusus Disabilitas',
          iconSvg: '<svg>custom</svg>',
          inputs: [{ name: 'ktp', type: 'text' }],
          files: [{ name: 'surat', type: 'pdf' }],
        },
      ];

      (getProgramsApi as any).mockResolvedValue(rawApiData);

      const result = await AssistanceService.getPrograms();

      expect(getProgramsApi).toHaveBeenCalledTimes(1);
      expect(result).toHaveLength(2);

      // Item 1: Default fallbacks
      expect(result[0].id).toBe('1');
      expect(result[0].title).toBe('Program A');
      expect(result[0].badge).toBe('Bantuan Aktif');
      expect(result[0].iconSvg).toContain('M2.25 12l8.954');
      expect(result[0].inputs).toEqual([]);
      expect(result[0].files).toEqual([]);

      // Item 2: Explicit data
      expect(result[1].badge).toBe('Khusus Disabilitas');
      expect(result[1].iconSvg).toBe('<svg>custom</svg>');
      expect(result[1].inputs).toHaveLength(1);
      expect(result[1].files).toHaveLength(1);
    });

    it('[PROG-02] Handle empty array response', async () => {
      (getProgramsApi as any).mockResolvedValue([]);

      const result = await AssistanceService.getPrograms();

      expect(result).toEqual([]);
    });
  });

  // ============================================================
  // REGION HANDLERS
  // ============================================================
  describe('Region Handlers', () => {

    it('[REG-01] getRegencies calls API and returns data', async () => {
      const mockData = [{ id: '6301', name: 'Tanah Laut' }];
      (getRegenciesApi as any).mockResolvedValue(mockData);

      const result = await AssistanceService.getRegencies();

      expect(getRegenciesApi).toHaveBeenCalledTimes(1);
      expect(result).toEqual(mockData);
    });

    it('[REG-02] getDistricts calls API with regencyId', async () => {
      const mockData = [{ id: '630101', name: 'Bajuin' }];
      (getDistrictsApi as any).mockResolvedValue(mockData);

      const result = await AssistanceService.getDistricts('6301');

      expect(getDistrictsApi).toHaveBeenCalledWith('6301');
      expect(result).toEqual(mockData);
    });

    it('[REG-03] getVillages calls API with districtId', async () => {
      const mockData = [{ id: '6301012001', name: 'Tirta Jaya' }];
      (getVillagesApi as any).mockResolvedValue(mockData);

      const result = await AssistanceService.getVillages('630101');

      expect(getVillagesApi).toHaveBeenCalledWith('630101');
      expect(result).toEqual(mockData);
    });
  });

  // ============================================================
  // SUBMIT REGISTRATION
  // ============================================================
  describe('submitRegistration', () => {

    it('[SUB-01] Appends all fields to FormData for bpd_transfer', async () => {
      const mockFile = new File(['dummy'], 'ktp.jpg', { type: 'image/jpeg' });
      const payload = {
        program_id: 'prog-123',
        regency_id: '6301',
        district_id: '630101',
        village_id: '6301012001',
        disbursement_method: 'bpd_transfer' as const,
        bank_account_number: '1234567890',
        dynamicInputs: { pekerjaan: 'Petani' },
        files: { ktp_file: mockFile },
      };

      (submitAssistanceApi as any).mockResolvedValue({ data: { success: true } });

      const result = await AssistanceService.submitRegistration(payload as any);

      expect(submitAssistanceApi).toHaveBeenCalledTimes(1);

      const formDataArg = (submitAssistanceApi as any).mock.calls[0][0] as FormData;

      expect(formDataArg.get('program_id')).toBe('prog-123');
      expect(formDataArg.get('disbursement_method')).toBe('bpd_transfer');
      expect(formDataArg.get('bank_account_number')).toBe('1234567890');
      expect(formDataArg.get('pekerjaan')).toBe('Petani');
      expect(formDataArg.get('ktp_file')).toBeInstanceOf(File);

      expect(result).toEqual({ data: { success: true } });
    });

    it('[SUB-02] Does NOT append bank_account_number for village_cash', async () => {
      const payload = {
        program_id: 'prog-123',
        regency_id: '6301',
        district_id: '630101',
        village_id: '6301012001',
        disbursement_method: 'village_cash' as const,
        bank_account_number: '1234567890',
        dynamicInputs: {},
        files: {},
      };

      (submitAssistanceApi as any).mockResolvedValue({});

      await AssistanceService.submitRegistration(payload as any);

      const formDataArg = (submitAssistanceApi as any).mock.calls[0][0] as FormData;

      expect(formDataArg.get('disbursement_method')).toBe('village_cash');
      expect(formDataArg.has('bank_account_number')).toBe(false);
    });

    it('[SUB-03] Handles empty dynamicInputs and files', async () => {
      const payload = {
        program_id: 'prog-123',
        regency_id: '6301',
        district_id: '630101',
        village_id: '6301012001',
        disbursement_method: 'village_cash' as const,
        bank_account_number: '',
        dynamicInputs: {},
        files: {},
      };

      (submitAssistanceApi as any).mockResolvedValue({});

      await AssistanceService.submitRegistration(payload as any);

      expect(submitAssistanceApi).toHaveBeenCalledTimes(1);
    });
  });

  // ============================================================
  // UPDATE REGISTRATION
  // ============================================================
  describe('updateRegistration', () => {

    it('[UPD-01] Appends fields and filters non-File objects', async () => {
      const mockFile = new File(['dummy'], 'update.jpg', { type: 'image/jpeg' });

      const payload = {
        program_id: 'ignored',
        regency_id: '6302',
        district_id: '630201',
        village_id: '6302012001',
        disbursement_method: 'village_cash' as const,
        bank_account_number: '',
        dynamicInputs: { alasan: 'Revisi' },
        files: {
          new_file: mockFile,
          existing_file: 'https://cloudinary.com/image.jpg' as unknown as File,
        },
      };

      (updateAssistanceApi as any).mockResolvedValue({ success: true });

      await AssistanceService.updateRegistration('sub-123', payload as any);

      expect(updateAssistanceApi).toHaveBeenCalledTimes(1);

      const [idArg, formDataArg] = (updateAssistanceApi as any).mock.calls[0];

      expect(idArg).toBe('sub-123');
      expect((formDataArg as FormData).get('regency_id')).toBe('6302');
      expect((formDataArg as FormData).get('alasan')).toBe('Revisi');
      expect((formDataArg as FormData).get('new_file')).toBeInstanceOf(File);
      expect((formDataArg as FormData).has('existing_file')).toBe(false);
    });

    it('[UPD-02] Appends bank_account_number if present', async () => {
      const payload = {
        regency_id: '6302',
        district_id: '630201',
        village_id: '6302012001',
        disbursement_method: 'bpd_transfer' as const,
        bank_account_number: '999888777',
        dynamicInputs: {},
        files: {},
      };

      (updateAssistanceApi as any).mockResolvedValue({});

      await AssistanceService.updateRegistration('sub-456', payload as any);

      const [, formDataArg] = (updateAssistanceApi as any).mock.calls[0];

      expect((formDataArg as FormData).get('bank_account_number')).toBe('999888777');
    });

    it('[UPD-03] Does NOT append program_id', async () => {
      const payload = {
        program_id: 'should-be-ignored',
        regency_id: '6302',
        district_id: '630201',
        village_id: '6302012001',
        disbursement_method: 'village_cash' as const,
        bank_account_number: '',
        dynamicInputs: {},
        files: {},
      };

      (updateAssistanceApi as any).mockResolvedValue({});

      await AssistanceService.updateRegistration('sub-789', payload as any);

      const [, formDataArg] = (updateAssistanceApi as any).mock.calls[0];

      expect((formDataArg as FormData).has('program_id')).toBe(false);
    });
  });

  // ============================================================
  // SUBMISSION HANDLERS
  // ============================================================
  describe('Submission Handlers', () => {

    it('[HIST-01] getMySubmissions calls API', async () => {
      const mockResponse = [{ id: '1', status: 'pending' }];
      (getMySubmissionsApi as any).mockResolvedValue(mockResponse);

      const result = await AssistanceService.getMySubmissions();

      expect(getMySubmissionsApi).toHaveBeenCalledTimes(1);
      expect(result).toEqual(mockResponse);
    });

    it('[HIST-02] cancelRegistration calls API with registrationNumber', async () => {
      (deleteAssistanceApi as any).mockResolvedValue({ success: true });

      const result = await AssistanceService.cancelRegistration('REG-001');

      expect(deleteAssistanceApi).toHaveBeenCalledWith('REG-001');
      expect(result).toEqual({ success: true });
    });

    it('[HIST-03] getSubmissionDetail calls API with id', async () => {
      const mockResponse = { data: { id: 'uuid-1', status: 'validated' } };
      (getSubmissionDetailApi as any).mockResolvedValue(mockResponse);

      const result = await AssistanceService.getSubmissionDetail('uuid-1');

      expect(getSubmissionDetailApi).toHaveBeenCalledWith('uuid-1');
      expect(result).toEqual(mockResponse);
    });

    it('[HIST-04] downloadReceipt calls API and returns Blob', async () => {
      const mockBlob = new Blob(['pdf-content'], { type: 'application/pdf' });
      (downloadAssistancePdfApi as any).mockResolvedValue(mockBlob);

      const result = await AssistanceService.downloadReceipt('uuid-1');

      expect(downloadAssistancePdfApi).toHaveBeenCalledWith('uuid-1');
      expect(result).toBeInstanceOf(Blob);
      expect(result).toEqual(mockBlob);
    });
  });

  // ============================================================
  // EDGE CASES
  // ============================================================
  describe('Edge Cases', () => {

    it('[EDGE-01] getPrograms handles null/undefined fields gracefully', async () => {
      const rawApiData = [
        {
          id: '1',
          title: null,
          badge: null,
          iconSvg: null,
          inputs: null,
          files: null,
        },
      ];

      (getProgramsApi as any).mockResolvedValue(rawApiData);

      const result = await AssistanceService.getPrograms();

      expect(result[0].title).toBeNull();
      expect(result[0].badge).toBe('Bantuan Aktif');
      expect(result[0].iconSvg).toContain('M2.25');
      expect(result[0].inputs).toEqual([]);
      expect(result[0].files).toEqual([]);
    });

    it('[EDGE-02] submitRegistration with many dynamicInputs', async () => {
      const payload = {
        program_id: 'prog-123',
        regency_id: '6301',
        district_id: '630101',
        village_id: '6301012001',
        disbursement_method: 'village_cash' as const,
        bank_account_number: '',
        dynamicInputs: {
          field1: 'value1',
          field2: 'value2',
          field3: 'value3',
        },
        files: {},
      };

      (submitAssistanceApi as any).mockResolvedValue({});

      await AssistanceService.submitRegistration(payload as any);

      const formDataArg = (submitAssistanceApi as any).mock.calls[0][0] as FormData;

      expect(formDataArg.get('field1')).toBe('value1');
      expect(formDataArg.get('field2')).toBe('value2');
      expect(formDataArg.get('field3')).toBe('value3');
    });
  });
});