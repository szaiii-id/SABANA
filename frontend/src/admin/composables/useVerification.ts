import { ref } from 'vue';
import VerificationService from '../services/VerificationService';
import type { VerificationData, VerificationDetail, VerificationFilters } from '../types/verification';

export function useVerification() {
  const verifications = ref<VerificationData[]>([]);
  const currentDetail = ref<VerificationDetail | null>(null);
  const loading = ref(false);
  const submitting = ref(false);
  const errorMessage = ref('');
  const successMessage = ref('');
  const currentPage = ref(1);
  const totalPages = ref(1);
  const total = ref(0);
  const showSuccess = ref(false);

  const fetchVerifications = async (filters: VerificationFilters = {}) => {
    loading.value = true;
    try {
      const r = await VerificationService.fetchVerifications(filters);
      verifications.value = r.data || [];
      currentPage.value = r.current_page || 1;
      totalPages.value = r.last_page || 1;
      total.value = r.total || 0;
    } catch (e) {
      errorMessage.value = 'Gagal mengambil data verifikasi.';
    } finally {
      loading.value = false;
    }
  };

  const fetchDetail = async (id: string) => {
    loading.value = true;
    try {
      const r = await VerificationService.fetchVerificationDetail(id);
      currentDetail.value = r.data;
      return r.data;
    } catch (e) {
      errorMessage.value = 'Gagal mengambil detail pengajuan.';
      return null;
    } finally {
      loading.value = false;
    }
  };

  const approve = async (id: string) => {
    submitting.value = true;
    try {
      await VerificationService.approveVerification(id);
      showSuccessMessage('Pengajuan berhasil disetujui.');
      return true;
    } catch (e: any) {
      errorMessage.value = e.response?.data?.message || 'Gagal menyetujui.';
      return false;
    } finally {
      submitting.value = false;
    }
  };

  const reject = async (id: string, notes: string) => {
    submitting.value = true;
    try {
      await VerificationService.rejectVerification(id, notes);
      showSuccessMessage('Pengajuan berhasil ditolak.');
      return true;
    } catch (e: any) {
      errorMessage.value = e.response?.data?.message || 'Gagal menolak.';
      return false;
    } finally {
      submitting.value = false;
    }
  };

  const requestRevision = async (id: string, notes: string, revisionItems?: string[]) => {
    submitting.value = true;
    try {
      await VerificationService.requestRevision(id, notes, revisionItems);
      showSuccessMessage('Permintaan perbaikan dikirim.');
      return true;
    } catch (e: any) {
      errorMessage.value = e.response?.data?.message || 'Gagal mengirim perbaikan.';
      return false;
    } finally {
      submitting.value = false;
    }
  };

  const complete = async (id: string) => {
    submitting.value = true;
    try {
      await VerificationService.completeVerification(id);
      showSuccessMessage('Pengajuan berhasil ditandai selesai.');
      return true;
    } catch (e: any) {
      errorMessage.value = e.response?.data?.message || 'Gagal menandai selesai.';
      return false;
    } finally {
      submitting.value = false;
    }
  };

  const unvalidate = async (id: string, notes: string) => {
    submitting.value = true;
    try {
      await VerificationService.unvalidateVerification(id, notes);
      showSuccessMessage('Persetujuan berhasil dibatalkan.');
      return true;
    } catch (e: any) {
      errorMessage.value = e.response?.data?.message || 'Gagal membatalkan persetujuan.';
      return false;
    } finally {
      submitting.value = false;
    }
  };

  const showSuccessMessage = (message: string) => {
    successMessage.value = message;
    showSuccess.value = true;
  };

  const bulkComplete = async (ids: string[]) => {
      submitting.value = true;
      try {
          const r = await VerificationService.bulkCompleteVerification(ids);
          showSuccessMessage(r.message || `${ids.length} pengajuan berhasil ditandai selesai.`);
          return true;
      } catch (e: any) {
          errorMessage.value = e.response?.data?.message || 'Gagal memproses.';
          return false;
      } finally {
          submitting.value = false;
      }
  };

  return {
    verifications, currentDetail, loading, submitting, errorMessage,
    successMessage, currentPage, totalPages, total, showSuccess,
    fetchVerifications, fetchDetail, approve, reject, requestRevision,
    complete, unvalidate, showSuccessMessage, bulkComplete
  };
}