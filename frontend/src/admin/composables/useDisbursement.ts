import { ref } from 'vue';
import { DisbursementService } from '../services/DisbursementService';
import type { DisbursementSubmission } from '../types/disbursement';

export function useDisbursement() {
  const submissions = ref<DisbursementSubmission[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const successMessage = ref<string | null>(null);

  const fetchList = async (params?: Record<string, string>) => {
    loading.value = true;
    error.value = null;
    try {
      submissions.value = await DisbursementService.getList(params);
    } catch (e: unknown) {
      const apiError = e as { response?: { data?: { message?: string } } };
      error.value = apiError.response?.data?.message || 'Gagal memuat data.';
    } finally {
      loading.value = false;
    }
  };

  const disburse = async (submissionId: string, notes?: string): Promise<boolean> => {
    loading.value = true;
    error.value = null;
    try {
      await DisbursementService.disburse(submissionId, notes);
      successMessage.value = 'Bantuan berhasil disalurkan.';
      return true;
    } catch (e: unknown) {
      const apiError = e as { response?: { data?: { message?: string } } };
      error.value = apiError.response?.data?.message || 'Gagal menyalurkan.';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const bulkDisburse = async (submissionIds: string[], notes?: string): Promise<boolean> => {
    loading.value = true;
    error.value = null;
    try {
      const result = await DisbursementService.bulkDisburse(submissionIds, notes);
      successMessage.value = result.message;
      return true;
    } catch (e: unknown) {
      const apiError = e as { response?: { data?: { message?: string } } };
      error.value = apiError.response?.data?.message || 'Gagal menyalurkan.';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const resetMessages = () => {
    error.value = null;
    successMessage.value = null;
  };

  return {
    submissions,
    loading,
    error,
    successMessage,
    fetchList,
    disburse,
    bulkDisburse,
    resetMessages,
  };
}