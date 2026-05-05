// src/pages/dashboard/__tests__/Assistance.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { reactive, ref } from 'vue';
import Assistance from '../Assistance.vue';
import AssistanceStep1 from '../../../components/assistance/AssistanceStep1.vue';
import AssistanceStep2 from '../../../components/assistance/AssistanceStep2.vue';
import AssistanceStep3 from '../../../components/assistance/AssistanceStep3.vue';

const { 
  fetchProgramsMock, 
  fetchRegenciesMock, 
  fetchDistrictsMock, 
  fetchVillagesMock, 
  submitAssistanceMock 
} = vi.hoisted(() => ({
  fetchProgramsMock: vi.fn(),
  fetchRegenciesMock: vi.fn(),
  fetchDistrictsMock: vi.fn(),
  fetchVillagesMock: vi.fn(),
  submitAssistanceMock: vi.fn()
}));

vi.mock('../../../composables/useAssistance', () => ({
  useAssistance: () => ({
    fetchPrograms: fetchProgramsMock,
    fetchRegencies: fetchRegenciesMock,
    fetchDistricts: fetchDistrictsMock,
    fetchVillages: fetchVillagesMock,
    submitAssistance: submitAssistanceMock,
    programs: reactive([
      { 
        id: '1', title: 'Bantuan Pendidikan', badge: 'Aktif', 
        iconSvg: '<svg></svg>', 
        inputs: [
          { key: 'school_name', label: 'Nama Sekolah', type: 'text' },
          { key: 'nisn', label: 'NISN', type: 'text' }
        ], 
        files: [
          { key: 'ktp', label: 'KTP' },
          { key: 'kk', label: 'Kartu Keluarga' }
        ] 
      },
      { 
        id: '2', title: 'Bantuan Kesehatan', badge: 'Baru', 
        iconSvg: '<svg></svg>', 
        inputs: [
          { key: 'hospital_name', label: 'Nama Rumah Sakit', type: 'text' }
        ], 
        files: [
          { key: 'medical_record', label: 'Rekam Medis' }
        ] 
      }
    ]),
    regencies: reactive([{ id: '1', name: 'Kabupaten Banjar' }]),
    districts: reactive([{ id: '1', name: 'Martapura' }]),
    villages: reactive([{ id: '1', name: 'Sungai Paring' }]),
    isLoading: ref(false)
  })
}));

const mockPush = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: mockPush })
}));

describe('Assistance.vue - Professional QA Test Suite', () => {
  
  beforeEach(() => {
    vi.clearAllMocks();
    vi.stubGlobal('URL', { createObjectURL: vi.fn(() => 'blob:mock-url'), revokeObjectURL: vi.fn() });
    vi.stubGlobal('localStorage', { getItem: vi.fn(), setItem: vi.fn(), removeItem: vi.fn(), clear: vi.fn() });
    fetchProgramsMock.mockResolvedValue(undefined);
    fetchRegenciesMock.mockResolvedValue(undefined);
    fetchDistrictsMock.mockResolvedValue(undefined);
    fetchVillagesMock.mockResolvedValue(undefined);
    submitAssistanceMock.mockResolvedValue({ data: { registration_number: 'REG-2024-001' } });
  });

  const mountComponent = () => {
    return mount(Assistance, {
      global: {
        stubs: {
          CheckBadgeIcon: true,
          RocketLaunchIcon: true,
          ArrowPathIcon: true
        }
      }
    });
  };

   // ============================================================
  // INISIALISASI
  // ============================================================
  describe('Inisialisasi & Loading Data', () => {
    it('[INIT-01] Memanggil fetchPrograms dan fetchRegencies saat mounted', async () => {
      mountComponent();
      await flushPromises();
      expect(fetchProgramsMock).toHaveBeenCalledTimes(1);
      expect(fetchRegenciesMock).toHaveBeenCalledTimes(1);
    });

    it('[INIT-02] Menampilkan Step 1 setelah data berhasil dimuat', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      expect(wrapper.findComponent(AssistanceStep1).exists()).toBe(true);
    });

    it('[INIT-03] Menampilkan error jika fetchPrograms gagal', async () => {
      fetchProgramsMock.mockRejectedValue(new Error('Network Error'));
      
      const wrapper = mountComponent();
      await flushPromises();
      await wrapper.vm.$nextTick();
      
      const vm = wrapper.vm as any;
      
      // Sekarang error handling sudah ada, user dapat feedback
      expect(vm.notification.message).toContain('Gagal memuat data');
      expect(vm.notification.type).toBe('error');
    });
  });

  // ============================================================
  // FLOW NAVIGASI
  // ============================================================
  describe('Flow Navigasi Utama', () => {
    it('[FLOW-01] Step 1 → Step 2', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      await wrapper.findComponent(AssistanceStep1).vm.$emit('select', { 
        id: '1', title: 'Bantuan Pendidikan', badge: '', iconSvg: '', inputs: [], files: [] 
      });
      await flushPromises();
      expect(wrapper.findComponent(AssistanceStep2).exists()).toBe(true);
    });

    it('[FLOW-02] Step 2 → Step 3', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      await wrapper.findComponent(AssistanceStep1).vm.$emit('select', { 
        id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] 
      });
      await flushPromises();
      await wrapper.findComponent(AssistanceStep2).vm.$emit('next');
      await flushPromises();
      expect(wrapper.findComponent(AssistanceStep3).exists()).toBe(true);
    });

    it('[FLOW-03] Step 2 → Step 1 (prev)', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      await wrapper.findComponent(AssistanceStep1).vm.$emit('select', { 
        id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] 
      });
      await flushPromises();
      await wrapper.findComponent(AssistanceStep2).vm.$emit('prev');
      await flushPromises();
      expect(wrapper.findComponent(AssistanceStep1).exists()).toBe(true);
    });

    it('[FLOW-04] Step 3 → Step 2 (prev)', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      await wrapper.findComponent(AssistanceStep1).vm.$emit('select', { 
        id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] 
      });
      await flushPromises();
      await wrapper.findComponent(AssistanceStep2).vm.$emit('next');
      await flushPromises();
      await wrapper.findComponent(AssistanceStep3).vm.$emit('prev');
      await flushPromises();
      expect(wrapper.findComponent(AssistanceStep2).exists()).toBe(true);
    });
  });

  // ============================================================
  // MANAJEMEN FORM
  // ============================================================
  describe('Manajemen Data Form', () => {
    it('[FORM-01] Reset dynamicInputs dan files saat pilih program', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      const vm = wrapper.vm as any;
      vm.formData.dynamicInputs = { old_key: 'old_value' };
      vm.formData.files = { old_file: new File([], 'test.txt') };
      await wrapper.findComponent(AssistanceStep1).vm.$emit('select', { 
        id: '2', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] 
      });
      await flushPromises();
      expect(vm.formData.dynamicInputs).toEqual({});
      expect(vm.formData.files).toEqual({});
    });

    it('[FORM-02] handleRegencyChange mereset district & village', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.formData.district_id = 'old';
      vm.formData.village_id = 'old';
      await vm.handleRegencyChange();
      expect(vm.formData.district_id).toBe('');
      expect(vm.formData.village_id).toBe('');
    });

    it('[FORM-03] handleRegencyChange memanggil fetchDistricts', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.formData.regency_id = '1';
      await vm.handleRegencyChange();
      expect(fetchDistrictsMock).toHaveBeenCalledWith('1');
    });

    it('[FORM-04] handleRegencyChange TIDAK memanggil fetchDistricts jika kosong', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.formData.regency_id = '';
      await vm.handleRegencyChange();
      expect(fetchDistrictsMock).not.toHaveBeenCalled();
    });

    it('[FORM-05] handleDistrictChange mereset village', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.formData.village_id = 'old';
      await vm.handleDistrictChange();
      expect(vm.formData.village_id).toBe('');
    });

    it('[FORM-06] handleDistrictChange memanggil fetchVillages', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.formData.district_id = '1';
      await vm.handleDistrictChange();
      expect(fetchVillagesMock).toHaveBeenCalledWith('1');
    });
  });

  // ============================================================
  // SUBMISSION
  // ============================================================
  describe('Proses Submit', () => {
    it('[SUBMIT-01] Memanggil submitAssistance dengan formData yang benar', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      const vm = wrapper.vm as any;
      
      // Set formData manual (bypass flow normal untuk kontrol penuh)
      vm.formData.program_id = '1';
      vm.formData.regency_id = '1';
      vm.formData.district_id = '1';
      vm.formData.village_id = '1';
      vm.formData.dynamicInputs = { school_name: 'SMA 1', nisn: '12345' };
      vm.selectedProgram = { id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] };
      vm.currentStep = 3;
      
      await wrapper.vm.$nextTick();
      await vm.handleSubmit();
      await flushPromises();

      expect(submitAssistanceMock).toHaveBeenCalledTimes(1);
      const callArgs = submitAssistanceMock.mock.calls[0][0];
      expect(callArgs.program_id).toBe('1');
      expect(callArgs.dynamicInputs).toEqual({ school_name: 'SMA 1', nisn: '12345' });
    });

    it('[SUBMIT-02] Menampilkan halaman sukses', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      const vm = wrapper.vm as any;
      vm.currentStep = 3;
      vm.selectedProgram = { id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] };
      await wrapper.vm.$nextTick();
      await vm.handleSubmit();
      await flushPromises();
      expect(vm.isSuccess).toBe(true);
      expect(vm.registrationNumber).toBe('REG-2024-001');
      expect(wrapper.text()).toContain('BERHASIL TERKIRIM!');
    });

    it('[SUBMIT-03] Menampilkan error jika submit gagal', async () => {
      submitAssistanceMock.mockRejectedValue({ response: { data: { message: 'Data tidak valid' } } });
      const wrapper = mountComponent();
      await flushPromises();
      const vm = wrapper.vm as any;
      vm.currentStep = 3;
      vm.selectedProgram = { id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] };
      await wrapper.vm.$nextTick();
      await vm.handleSubmit();
      await flushPromises();
      await wrapper.vm.$nextTick();
      expect(vm.notification.message).toBe('Data tidak valid');
    });

    it('[SUBMIT-04] Menampilkan error default', async () => {
      submitAssistanceMock.mockRejectedValue(new Error('Network Error'));
      const wrapper = mountComponent();
      await flushPromises();
      const vm = wrapper.vm as any;
      vm.currentStep = 3;
      vm.selectedProgram = { id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] };
      await wrapper.vm.$nextTick();
      await vm.handleSubmit();
      await flushPromises();
      await wrapper.vm.$nextTick();
      expect(vm.notification.message).toBe('Gagal mengirim pengajuan.');
    });

    it('[SUBMIT-05] Menghapus localStorage draft setelah submit sukses', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      const vm = wrapper.vm as any;
      vm.currentStep = 3;
      vm.selectedProgram = { id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] };
      await wrapper.vm.$nextTick();
      await vm.handleSubmit();
      await flushPromises();
      expect(localStorage.removeItem).toHaveBeenCalledWith('SABANA_DRAFT');
    });
  });

  // ============================================================
  // NOTIFICATION
  // ============================================================
  describe('Notifikasi & Error', () => {
    it('[NOTIF-01] Menampilkan error dari Step 2', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      await wrapper.findComponent(AssistanceStep1).vm.$emit('select', { 
        id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] 
      });
      await flushPromises();
      await wrapper.findComponent(AssistanceStep2).vm.$emit('errorMsg', 'Field wajib diisi!');
      await flushPromises();
      expect(wrapper.text()).toContain('Field wajib diisi!');
    });

    it('[NOTIF-02] Menampilkan error dari Step 3', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      await wrapper.findComponent(AssistanceStep1).vm.$emit('select', { 
        id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] 
      });
      await flushPromises();
      await wrapper.findComponent(AssistanceStep2).vm.$emit('next');
      await flushPromises();
      await wrapper.findComponent(AssistanceStep3).vm.$emit('errorMsg', 'Berkas wajib!');
      await flushPromises();
      expect(wrapper.text()).toContain('Berkas wajib!');
    });

    it('[NOTIF-03] Notifikasi reset saat pilih program baru', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      const vm = wrapper.vm as any;
      vm.notification.message = 'Error lama';
      await wrapper.findComponent(AssistanceStep1).vm.$emit('select', { 
        id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] 
      });
      await flushPromises();
      expect(vm.notification.message).toBe('');
    });
  });

  // ============================================================
  // LOCALSTORAGE
  // ============================================================
  describe('LocalStorage Draft', () => {
    it('[DRAFT-01] Menyimpan draft saat formData berubah', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      const vm = wrapper.vm as any;
      vm.formData.program_id = '1';
      await wrapper.vm.$nextTick();
      await flushPromises();
      expect(localStorage.setItem).toHaveBeenCalledWith(
        'SABANA_DRAFT',
        expect.stringContaining('"program_id":"1"')
      );
    });
  });

  // ============================================================
  // COMPUTED
  // ============================================================
  describe('Computed Properties', () => {
    it('[COMP-01] displayHeaderTitle = "Pilih Program" saat Step 1', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      expect(vm.displayHeaderTitle).toBe('Pilih Program');
    });

    it('[COMP-02] displayHeaderTitle = nama program saat Step 2+', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      const vm = wrapper.vm as any;
      vm.currentStep = 2;
      vm.selectedProgram = { id: '1', title: 'Bantuan Pendidikan' };
      await wrapper.vm.$nextTick();
      expect(vm.displayHeaderTitle).toBe('Bantuan Pendidikan');
    });

    it('[COMP-03] displayHeaderTitle = "Data Pengajuan" jika null', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.selectedProgram = null;
      vm.currentStep = 2;
      expect(vm.displayHeaderTitle).toBe('Data Pengajuan');
    });
  });

  // ============================================================
  // EDGE CASES
  // ============================================================
  describe('Edge Cases & Robustness', () => {
    it('[EDGE-01] Tidak crash saat programs kosong', () => {
      expect(() => mountComponent()).not.toThrow();
    });

    it('[EDGE-02] Step 2 tidak dirender jika selectedProgram null', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      const vm = wrapper.vm as any;
      vm.selectedProgram = null;
      vm.currentStep = 2;
      await wrapper.vm.$nextTick();
      expect(wrapper.findComponent(AssistanceStep2).exists()).toBe(false);
    });

    it('[EDGE-03] Step 3 tidak dirender jika selectedProgram null', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      const vm = wrapper.vm as any;
      vm.selectedProgram = null;
      vm.currentStep = 3;
      await wrapper.vm.$nextTick();
      expect(wrapper.findComponent(AssistanceStep3).exists()).toBe(false);
    });

    it('[EDGE-04] getFilePreview mengembalikan URL', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      const result = vm.getFilePreview(new File(['test'], 'test.png'));
      expect(result).toBe('blob:mock-url');
    });

    it('[EDGE-05] Processing timer 2 detik', async () => {
      vi.useFakeTimers();
      const wrapper = mountComponent();
      await flushPromises();
      const vm = wrapper.vm as any;
      vm.isLoading = { value: true };
      vm.currentStep = 3;
      vm.selectedProgram = { id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] };
      
      submitAssistanceMock.mockImplementation(() => new Promise(r => setTimeout(r, 5000)));
      
      vi.advanceTimersByTime(2500);
      await flushPromises();
      
      expect(vm.isProcessingLong).toBe(true);
      vi.useRealTimers();
    });
  });

  // ============================================================
  // E2E
  // ============================================================
  describe('Integration: End-to-End Flow', () => {
    it('[E2E-01] Full flow: Step 1 → Step 2 → Step 3 → Submit → Success', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      
      // Step 1
      await wrapper.findComponent(AssistanceStep1).vm.$emit('select', { 
        id: '1', title: 'Bantuan Pendidikan', badge: '', iconSvg: '', inputs: [], files: [] 
      });
      await flushPromises();
      
      // Step 2 → Step 3
      await wrapper.findComponent(AssistanceStep2).vm.$emit('next');
      await flushPromises();
      
      // Submit
      const vm = wrapper.vm as any;
      await vm.handleSubmit();
      await flushPromises();
      
      expect(vm.isSuccess).toBe(true);
      expect(wrapper.text()).toContain('BERHASIL TERKIRIM!');
    });

    it('[E2E-02] Navigasi bolak-balik', async () => {
      const wrapper = mountComponent();
      await flushPromises();
      
      await wrapper.findComponent(AssistanceStep1).vm.$emit('select', { 
        id: '1', title: 'Test', badge: '', iconSvg: '', inputs: [], files: [] 
      });
      await flushPromises();
      
      await wrapper.findComponent(AssistanceStep2).vm.$emit('next');
      await flushPromises();
      
      await wrapper.findComponent(AssistanceStep3).vm.$emit('prev');
      await flushPromises();
      
      await wrapper.findComponent(AssistanceStep2).vm.$emit('prev');
      await flushPromises();
      
      expect(wrapper.findComponent(AssistanceStep1).exists()).toBe(true);
    });
  });
});