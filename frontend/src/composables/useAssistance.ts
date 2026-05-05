import { ref } from 'vue';
import { AssistanceService } from '../services/AssistanceService';
import type { AssistanceSubmissionPayload, Region, AssistanceProgramSchema } from '../types/assistance';

export function useAssistance() {
  const isLoading = ref<boolean>(false);
  const error = ref<string | null>(null);
  
  const programs = ref<AssistanceProgramSchema[]>([]);
  const regencies = ref<Region[]>([]);
  const districts = ref<Region[]>([]);
  const villages = ref<Region[]>([]);

  // 1. FUNGSI UNTUK MENARIK RIWAYAT
  const fetchMySubmissions = async () => {
    isLoading.value = true;
    error.value = null;
    try {
      return await AssistanceService.getMySubmissions();
    } catch (err: any) {
      error.value = 'Gagal memuat riwayat pengajuan.';
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const fetchPrograms = async () => {
    try { 
      const response: any = await AssistanceService.getPrograms(); 
      programs.value = response.data ? response.data : response; 
    }
    catch (err) { 
      error.value = 'Gagal memuat daftar program bantuan.'; 
    }
  };

  const fetchRegencies = async () => {
    try { regencies.value = await AssistanceService.getRegencies(); }
    catch (err) { error.value = 'Gagal memuat data kabupaten.'; }
  };

  const fetchDistricts = async (regencyId: string) => {
    try { districts.value = await AssistanceService.getDistricts(regencyId); }
    catch (err) { error.value = 'Gagal memuat data kecamatan.'; }
  };

  const fetchVillages = async (districtId: string) => {
    try { villages.value = await AssistanceService.getVillages(districtId); }
    catch (err) { error.value = 'Gagal memuat data desa.'; }
  };

  const submitAssistance = async (payload: AssistanceSubmissionPayload) => {
    isLoading.value = true;
    error.value = null;
    try {
      return await AssistanceService.submitRegistration(payload);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Terjadi kesalahan saat memproses pengajuan.';
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  // Di dalam useAssistance()
  const updateAssistance = async (id: string, payload: AssistanceSubmissionPayload) => {
      isLoading.value = true;
      error.value = null;
      try {
          return await AssistanceService.updateRegistration(id, payload);
      } catch (err: any) {
          error.value = err.response?.data?.message || 'Gagal memperbarui data.';
          throw err;
      } finally {
          isLoading.value = false;
      }
  };

  const deleteAssistance = async (registrationNumber: string) => {
    isLoading.value = true;
    try {
        const res = await AssistanceService.cancelRegistration(registrationNumber);
        return res;
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Gagal membatalkan pengajuan.';
        throw err;
    } finally {
        isLoading.value = false;
    }
  };

  const fetchDetail = async (id: string) => {
    isLoading.value = true;
    try {
        const response = await AssistanceService.getSubmissionDetail(id);
        // Sesuaikan dengan response Laravel Mas (biasanya ada di .data)
        return response.data || response;
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Gagal memuat detail.';
        throw err;
    } finally {
        isLoading.value = false;
    }
  };

  const downloadPdf = async (id: string, _fileName: string) => {
    isLoading.value = true;
    try {
        const blob = await AssistanceService.downloadReceipt(id);
        
        const fileURL = window.URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }));
        
        window.open(fileURL, '_blank');

        setTimeout(() => {
            window.URL.revokeObjectURL(fileURL);
        }, 1000);

    } catch (err: any) {
        error.value = 'Gagal memproses preview PDF.';
        throw err;
    } finally {
        isLoading.value = false;
    }
};






  return {
    submitAssistance, 
    fetchMySubmissions,
    fetchPrograms, 
    fetchRegencies, 
    fetchDistricts, 
    fetchVillages,
    updateAssistance,
    deleteAssistance,
    fetchDetail,
    downloadPdf,
    programs, 
    regencies, 
    districts, 
    villages,
    isLoading, 
    error,
  };
}