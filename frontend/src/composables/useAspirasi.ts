import { ref } from 'vue';
import { AspirasiService } from '../services/AspirasiService';
import type { AspirasiPayload } from '../types/aspirasi';

export function useAspirasi() {
  const isSubmitting = ref(false);
  const submitStatus = ref<'success' | 'error' | ''>('');
  const submitMessage = ref('');

  const kirimAspirasiData = async (formData: AspirasiPayload) => {
    isSubmitting.value = true;
    submitStatus.value = '';
    
    try {
      const result = await AspirasiService.kirimAspirasi(formData);
      submitStatus.value = 'success';
      submitMessage.value = result.message || 'Aspirasi berhasil dikirim.';
      return true; // Berhasil
    } catch (error: any) {
      submitStatus.value = 'error';
      submitMessage.value = error.response?.data?.message || 'Gagal mengirim pesan.';
      return false; // Gagal
    } finally {
      isSubmitting.value = false;
      setTimeout(() => { submitStatus.value = ''; }, 5000);
    }
  };

  return {
    isSubmitting,
    submitStatus,
    submitMessage,
    kirimAspirasiData
  };
}