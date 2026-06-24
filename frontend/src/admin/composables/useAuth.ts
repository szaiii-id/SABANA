import { ref, onUnmounted } from 'vue';
import AuthService from '../services/AuthService';
import type { LoginPayload, LoginResponse } from '../types/auth';

interface ApiError {
  response?: {
    status: number;
    headers?: Record<string, string>;
    data?: {
      message?: string;
      errors?: Record<string, string[]>;
    };
  };
  message?: string;
}

export function useAuth() {
  const isSubmitting = ref(false);
  const authError = ref('');
  const isRateLimited = ref(false);
  const retryAfter = ref(0);
  let countdownTimer: ReturnType<typeof setInterval> | null = null;
  let refreshTimer: ReturnType<typeof setInterval> | null = null;

  const submitLogin = async (payload: LoginPayload) => {
    isSubmitting.value = true;
    authError.value = '';
    
    try {
      const response: LoginResponse = await AuthService.loginAdmin(payload);
      
      const token = response.data?.token || response.token || '';
      const admin = response.data?.admin || response.admin || null;

      if (!token || !admin) {
        throw new Error('Format data dari server tidak sesuai.');
      }

      localStorage.setItem('admin_token', token);
      localStorage.setItem('admin_user', JSON.stringify(admin));

      startSessionRefresh();

      return { success: true };
    } catch (error: unknown) {
      const err = error as ApiError;

      if (err.response?.status === 429) {
        const retryAfterHeader = err.response.headers?.['retry-after'];
        const seconds = retryAfterHeader ? parseInt(retryAfterHeader) : 300;
        startRateLimit(seconds);
        return { success: false };
      }
      
      authError.value = err.response?.data?.message || err.message || 'Gagal terhubung ke server SABANA Center.';
      return { success: false };
    } finally {
      isSubmitting.value = false;
    }
  };

  const startSessionRefresh = () => {
    if (refreshTimer) clearInterval(refreshTimer);

    refreshTimer = setInterval(async () => {
      const token = localStorage.getItem('admin_token');
      if (!token) {
        clearSessionRefresh();
        return;
      }

      try {
        await AuthService.refreshSession();
      } catch (error: unknown) {
        const err = error as ApiError;

        if (err.response?.status === 401) {
          localStorage.setItem('admin_session_expired', 'true');
          handleLogout();
        }
      }
    }, 30 * 60 * 1000);
  };

  const startRateLimit = (seconds: number) => {
    isRateLimited.value = true;
    retryAfter.value = seconds;
    
    if (countdownTimer) clearInterval(countdownTimer);
    
    countdownTimer = setInterval(() => {
      retryAfter.value--;
      
      if (retryAfter.value <= 0) {
        clearCountdown();
        isRateLimited.value = false;
        authError.value = '';
      } else {
        const minutes = Math.ceil(retryAfter.value / 60);
        authError.value = `Terlalu banyak percobaan. Silakan coba lagi dalam ${minutes} menit.`;
      }
    }, 1000);
  };

  const clearCountdown = () => {
    if (countdownTimer) {
      clearInterval(countdownTimer);
      countdownTimer = null;
    }
  };

  const clearSessionRefresh = () => {
    if (refreshTimer) {
      clearInterval(refreshTimer);
      refreshTimer = null;
    }
  };

  const handleLogout = async () => {
    isSubmitting.value = true;
    try {
      await AuthService.logoutAdmin();
    } catch (error: unknown) {
      console.error('API logout error, clearing local session anyway.');
    } finally {
      localStorage.removeItem('admin_token');
      localStorage.removeItem('admin_user');
      
      clearCountdown();
      clearSessionRefresh();
      
      isSubmitting.value = false;
      const adminPrefix = import.meta.env.VITE_ADMIN_PORTAL_PREFIX || 'sabana-center-63';
      window.location.href = `/${adminPrefix}/login`;
    }
  };

  onUnmounted(() => {
    clearCountdown();
    clearSessionRefresh();
  });

  return {
    isSubmitting,
    authError,
    isRateLimited,
    retryAfter,
    submitLogin,
    handleLogout
  };
}