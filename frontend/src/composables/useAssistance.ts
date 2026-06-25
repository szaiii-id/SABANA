import { ref } from 'vue';
import { AssistanceService } from '../services/AssistanceService';
import type { AssistanceSubmissionPayload, AssistanceProgramSchema, Region } from '../types/assistance';

interface ApiError {
  response?: {
    data?: {
      message?: string;
    };
  };
}

function isApiError(error: unknown): error is ApiError {
  return typeof error === 'object' && error !== null && 'response' in error;
}

export function useAssistance() {
  const isLoading = ref<boolean>(false);
  const error = ref<string | null>(null);

  const programs = ref<AssistanceProgramSchema[]>([]);
  const regencies = ref<Region[]>([]);
  const districts = ref<Region[]>([]);
  const villages = ref<Region[]>([]);

  const fetchMySubmissions = async () => {
    isLoading.value = true;
    error.value = null;
    try {
      return await AssistanceService.getMySubmissions();
    } catch (err: unknown) {
      if (isApiError(err)) {
        error.value = err.response?.data?.message || 'Gagal memuat riwayat pengajuan.';
      } else {
        error.value = 'Gagal memuat riwayat pengajuan.';
      }
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const fetchPrograms = async () => {
    try {
      programs.value = await AssistanceService.getPrograms();
    } catch (err: unknown) {
      error.value = 'Gagal memuat daftar program bantuan.';
    }
  };

  const fetchRegencies = async () => {
    try {
      regencies.value = await AssistanceService.getRegencies();
    } catch (err: unknown) {
      error.value = 'Gagal memuat data kabupaten.';
    }
  };

  const fetchDistricts = async (regencyId: string) => {
    try {
      districts.value = await AssistanceService.getDistricts(regencyId);
    } catch (err: unknown) {
      error.value = 'Gagal memuat data kecamatan.';
    }
  };

  const fetchVillages = async (districtId: string) => {
    try {
      villages.value = await AssistanceService.getVillages(districtId);
    } catch (err: unknown) {
      error.value = 'Gagal memuat data desa.';
    }
  };

  const submitAssistance = async (payload: AssistanceSubmissionPayload, idempotencyKey?: string) => {
    isLoading.value = true;
    error.value = null;
    try {
      return await AssistanceService.submitRegistration(payload, idempotencyKey);
    } catch (err: unknown) {
      if (isApiError(err)) {
        error.value = err.response?.data?.message || 'Terjadi kesalahan saat memproses pengajuan.';
      } else {
        error.value = 'Terjadi kesalahan saat memproses pengajuan.';
      }
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const updateAssistance = async (id: string, payload: AssistanceSubmissionPayload) => {
    isLoading.value = true;
    error.value = null;
    try {
      return await AssistanceService.updateRegistration(id, payload);
    } catch (err: unknown) {
      if (isApiError(err)) {
        error.value = err.response?.data?.message || 'Gagal memperbarui data.';
      } else {
        error.value = 'Gagal memperbarui data.';
      }
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const deleteAssistance = async (registrationNumber: string) => {
    isLoading.value = true;
    try {
      return await AssistanceService.cancelRegistration(registrationNumber);
    } catch (err: unknown) {
      if (isApiError(err)) {
        error.value = err.response?.data?.message || 'Gagal membatalkan pengajuan.';
      } else {
        error.value = 'Gagal membatalkan pengajuan.';
      }
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const fetchDetail = async (id: string) => {
    isLoading.value = true;
    try {
      const response = await AssistanceService.getSubmissionDetail(id);
      return response.data || response;
    } catch (err: unknown) {
      if (isApiError(err)) {
        error.value = err.response?.data?.message || 'Gagal memuat detail.';
      } else {
        error.value = 'Gagal memuat detail.';
      }
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const downloadPdf = async (id: string) => {
    isLoading.value = true;
    try {
      const blob = await AssistanceService.downloadReceipt(id);
      const fileURL = window.URL.createObjectURL(blob);
      window.open(fileURL, '_blank');
      setTimeout(() => window.URL.revokeObjectURL(fileURL), 1000);
    } catch (err: unknown) {
      error.value = 'Gagal memproses preview PDF.';
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const fetchDisbursementReceipt = async (submissionId: string) => {
    isLoading.value = true;
    error.value = null;
    try {
      return await AssistanceService.getDisbursementReceipt(submissionId);
    } catch (err: unknown) {
      error.value = 'Gagal memuat bukti penyaluran.';
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const downloadDisbursementPdf = async (submissionId: string) => {
    isLoading.value = true;
    try {
      const blob = await AssistanceService.downloadDisbursementReceiptPdf(submissionId);
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `Bukti_Penyaluran_${submissionId}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (err: unknown) {
      error.value = 'Gagal mengunduh PDF.';
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
    fetchDisbursementReceipt,
    downloadDisbursementPdf,
    programs,
    regencies,
    districts,
    villages,
    isLoading,
    error,
  };
}