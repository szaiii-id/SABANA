import { ref } from 'vue';
import AuthService from '../services/AuthService';
import type { ForgotPinPayload, ResetPinPayload } from '../types/auth';

export function useForgotPin() {
  const isSubmitting = ref(false);
  const isResending = ref(false); // State khusus untuk kirim ulang OTP
  const errorMessage = ref('');

  // Fungsi Kirim OTP (Lupa PIN Awal) & Bisa dipakai Resend
  const sendOtp = async (payload: ForgotPinPayload) => {
    isSubmitting.value = true;
    isResending.value = true;
    errorMessage.value = '';
    try {
      await AuthService.requestForgotPinOtp(payload);
      return { success: true };
    } catch (error: any) {
      errorMessage.value = error.response?.data?.message || 'Gagal mengirim OTP.';
      return { success: false };
    } finally {
      isSubmitting.value = false;
      isResending.value = false;
    }
  };

  // Fungsi Reset PIN Final (Kirim 4 data ke BE)
  const executeReset = async (payload: ResetPinPayload) => {
    isSubmitting.value = true;
    errorMessage.value = '';
    try {
      await AuthService.resetPin(payload);
      return { success: true };
    } catch (error: any) {
      // Menangkap error 422 dari BE (Misal: OTP Expired)
      errorMessage.value = error.response?.data?.message || 'Gagal meriset PIN.';
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
    executeReset 
  };
}