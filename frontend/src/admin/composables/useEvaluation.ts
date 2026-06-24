import { ref } from 'vue';
import { EvaluationService } from '../services/EvaluationService';
import type { Evaluation } from '../types/evaluation';

export function useEvaluation() {
  const evaluations = ref<Evaluation[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const successMessage = ref<string | null>(null);

  /**
   * Fetch daftar evaluasi.
   */
  const fetchEvaluations = async (params?: Record<string, string>) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await EvaluationService.getList(params);
      evaluations.value = response.data || [];
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat data evaluasi.';
      evaluations.value = [];
    } finally {
      loading.value = false;
    }
  };

  /**
   * Approve evaluasi.
   */
  const approveEvaluation = async (id: string): Promise<boolean> => {
    loading.value = true;
    error.value = null;

    try {
      await EvaluationService.approve(id);
      successMessage.value = 'Evaluasi disetujui. Bantuan dilanjutkan.';
      return true;
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal menyetujui evaluasi.';
      return false;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Revoke evaluasi.
   */
  const revokeEvaluation = async (id: string, notes: string): Promise<boolean> => {
    loading.value = true;
    error.value = null;

    try {
      await EvaluationService.revoke(id, notes);
      successMessage.value = 'Evaluasi ditolak. Bantuan dihentikan.';
      return true;
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal menolak evaluasi.';
      return false;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Reset state.
   */
  const reset = () => {
    evaluations.value = [];
    loading.value = false;
    error.value = null;
    successMessage.value = null;
  };

  return {
    evaluations,
    loading,
    error,
    successMessage,
    fetchEvaluations,
    approveEvaluation,
    revokeEvaluation,
    reset,
  };
}