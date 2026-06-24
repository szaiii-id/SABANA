import { ref } from 'vue';
import ProgramService from '../services/ProgramService';
import type { ProgramPayload } from '../types/program';

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

export function useProgramSave() {
  const isSubmitting = ref(false);
  const errorMessage = ref('');
  const uploadProgress = ref(0);
  const uploadStep = ref<'data' | 'banner' | 'done'>('data');
  const showSuccess = ref(false);
  const successMessage = ref('');

  const handleSave = async (
    payload: ProgramPayload,
    mode: 'add' | 'edit',
    editId: string,
    onSuccess: () => void
  ) => {
    errorMessage.value = '';
    isSubmitting.value = true;
    uploadProgress.value = 0;
    uploadStep.value = 'data';

    try {
      const { banner, criteria, ai_config, ...rest } = payload;
      const hasBanner = banner instanceof File;

      const data: ProgramPayload = {
        ...rest,
        ...(criteria && Object.keys(criteria).length > 0 ? { criteria } : {}),
        ...(ai_config && Object.keys(ai_config).length > 0 ? { ai_config } : {}),
      };

      uploadProgress.value = 10;

      // ✅ Step 1: Create/Update program (tanpa banner)
      let result: { data?: { id?: string } } | undefined;
      if (mode === 'add') {
        result = await ProgramService.createProgram(data);
      } else {
        result = await ProgramService.updateProgram(editId, data);
      }

      uploadProgress.value = 60;
      const programId = result?.data?.id || editId;

      // ✅ Step 2: Upload banner (async, tidak blocking)
      if (hasBanner && programId) {
        uploadStep.value = 'banner';

        try {
          await ProgramService.uploadBanner(programId, banner as File, (percent: number) => {
            uploadProgress.value = 60 + Math.round(percent * 0.35);
          });
        } catch {
          // Banner gagal — jangan batalkan program yang sudah terbuat
          uploadProgress.value = 100;
          uploadStep.value = 'done';
          await new Promise(r => setTimeout(r, 500));

          isSubmitting.value = false;
          showSuccessMessage(
            mode === 'add'
              ? 'Program berhasil dibuat, tetapi banner gagal diupload. Anda bisa upload ulang nanti.'
              : 'Program berhasil diperbarui, tetapi banner gagal diupload. Anda bisa upload ulang nanti.'
          );
          onSuccess();
          return;
        }
      }

      // ✅ Step 3: Selesai
      uploadProgress.value = 100;
      uploadStep.value = 'done';
      await new Promise(r => setTimeout(r, 500));

      isSubmitting.value = false;
      showSuccessMessage(mode === 'add' ? 'Program berhasil dibuat.' : 'Program berhasil diperbarui.');
      onSuccess();

    } catch (e: unknown) {
      isSubmitting.value = false;
      if (isApiError(e)) {
        errorMessage.value = e.response?.data?.message || 'Terjadi kesalahan.';
      } else if (e instanceof Error) {
        errorMessage.value = e.message;
      } else {
        errorMessage.value = 'Terjadi kesalahan.';
      }
    }
  };

  const showSuccessMessage = (message: string) => {
    successMessage.value = message;
    showSuccess.value = true;
  };

  return {
    isSubmitting,
    errorMessage,
    uploadProgress,
    uploadStep,
    showSuccess,
    successMessage,
    handleSave,
    showSuccessMessage,
  };
}