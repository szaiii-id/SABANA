import { ref } from 'vue';
import AuthService from '../services/AuthService';
import type { ForgotPinPayload, ResetPinPayload } from '../types/auth';

// ===== TYPES =====
interface ApiError {
  response?: {
    data?: {
      message?: string;
    };
  };
}

// ===== COMPOSABLE =====
export function useForgotPin() {
  const isSubmitting = ref(false);
  const isResending = ref(false);
  const errorMessage = ref('');

  const sendOtp = async (payload: ForgotPinPayload) => {
    isSubmitting.value = true;
    isResending.value = true;
    errorMessage.value = '';

    try {
      await AuthService.requestForgotPinOtp(payload);
      return { success: true };
    } catch (error: unknown) {
      const apiError = error as ApiError;
      errorMessage.value = apiError.response?.data?.message || 'Gagal mengirim OTP.';
      return { success: false };
    } finally {
      isSubmitting.value = false;
      isResending.value = false;
    }
  };

  const executeReset = async (payload: ResetPinPayload) => {
    isSubmitting.value = true;
    errorMessage.value = '';

    try {
      await AuthService.resetPin(payload);
      return { success: true };
    } catch (error: unknown) {
      const apiError = error as ApiError;
      errorMessage.value = apiError.response?.data?.message || 'Gagal meriset PIN.';
      return { success: false };
    } finally {
      isSubmitting.value = false;
    }
  };

  return {
    isSubmitting,
    isResending,
    errorMessage,
    sendOtp,
    executeReset,
  };
}