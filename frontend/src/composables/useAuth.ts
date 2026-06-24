import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AuthService from '../services/AuthService';
import type { RegisterPayload, LoginPayload } from '../types/auth';

// ===== TYPES =====
interface RegistrationResult {
  success: boolean;
  phone?: string;
}

interface LoginResult {
  success: boolean;
  require_pin_change?: boolean;
  needsVerification?: boolean;
  wa?: string;
  message?: string;
}

interface ApiError {
  response?: {
    status?: number;
    data?: {
      message?: string;
      errors?: Record<string, string[]>;
    };
  };
  message?: string;
}

// ===== TOKEN SERVICE (Abstraction over localStorage) =====
const TokenService = {
  getToken(): string | null {
    return localStorage.getItem('sabana_token');
  },

  setToken(token: string): void {
    localStorage.setItem('sabana_token', token);
  },

  removeToken(): void {
    localStorage.removeItem('sabana_token');
  },

  // Hapus SEMUA data sesi
  clearSession(): void {
    localStorage.removeItem('sabana_token');
    // Tidak menyimpan citizen data!
  },
};

// ===== CONSTANTS =====
const GENERIC_ERROR_MESSAGE = 'Gagal terhubung ke server SABANA.';
const INVALID_RESPONSE_MESSAGE = 'Gagal membaca struktur data dari server.';
const DEFAULT_CREDENTIAL_ERROR = 'Kombinasi NIK dan PIN tidak cocok.';

// ===== COMPOSABLE =====
export function useAuth() {
  const router = useRouter();
  const isSubmitting = ref<boolean>(false);
  const authError = ref<string>('');

  // ===== HELPER =====
  const getErrorMessage = (error: unknown): string => {
    const apiError = error as ApiError;

    if (apiError?.response?.data?.message) {
      return apiError.response.data.message;
    }

    if (apiError?.message) {
      return apiError.message;
    }

    return GENERIC_ERROR_MESSAGE;
  };

  const logError = (context: string, error: unknown): void => {
    if (import.meta.env.DEV) {
      console.error(`[useAuth] ${context}:`, error);
    }
    // TODO: Integrasi dengan error tracking service (Sentry/Datadog) di production
  };

  // ===== REGISTRATION =====
  const submitRegistration = async (formData: RegisterPayload): Promise<RegistrationResult> => {
    isSubmitting.value = true;
    authError.value = '';

    try {
      await AuthService.registerUser(formData);

      return {
        success: true,
        phone: formData.whatsapp_number,
      };
    } catch (error: unknown) {
      authError.value = getErrorMessage(error);
      logError('Registration failed', error);

      return { success: false };
    } finally {
      isSubmitting.value = false;
    }
  };

  // ===== LOGIN =====
  const submitLogin = async (payload: LoginPayload): Promise<LoginResult> => {
    isSubmitting.value = true;
    authError.value = '';

    try {
      const response = await AuthService.loginUser(payload);

      const citizen = response.data?.citizen;
      const token = response.data?.token;
      const requirePinChange = response.data?.require_pin_change ?? false;

      // Validasi response
      if (!token || !citizen) {
        logError('Invalid response format', response);
        throw new Error(INVALID_RESPONSE_MESSAGE);
      }

      // Bersihkan sesi lama sebelum set baru
      TokenService.clearSession();

      // HANYA simpan token. JANGAN simpan citizen data!
      TokenService.setToken(token);

      return {
        success: true,
        require_pin_change: requirePinChange,
      };
    } catch (error: unknown) {
      const apiError = error as ApiError;

      // Handle 422 Unprocessable Entity (validation/business errors)
      if (apiError?.response?.status === 422) {
        const errors = apiError.response.data?.errors;

        // Akun belum terverifikasi
        if (errors?.is_verified) {
          return {
            success: false,
            needsVerification: true,
            wa: errors.whatsapp_number as unknown as string,
            message: errors.is_verified[0],
          };
        }

        // Kredensial salah
        authError.value = errors?.nik?.[0] || DEFAULT_CREDENTIAL_ERROR;

        return { success: false };
      }

      // Error lainnya (network, server, dll)
      logError('Login failed', error);
      authError.value = getErrorMessage(error);

      return { success: false };
    } finally {
      isSubmitting.value = false;
    }
  };

  // ===== LOGOUT =====
  const handleLogout = async (): Promise<void> => {
    isSubmitting.value = true;

    try {
      await AuthService.logoutUser();
    } catch (error: unknown) {
      // Tetap hapus sesi lokal meski API gagal
      logError('Logout API error (clearing local session)', error);
    } finally {
      // Hapus token — ini satu-satunya yang disimpan
      TokenService.clearSession();

      isSubmitting.value = false;

      // Redirect ke home
      await router.push({ name: 'home' });
    }
  };

  // ===== CHECK AUTH STATUS =====
  const isAuthenticated = (): boolean => {
    return TokenService.getToken() !== null;
  };

  // ===== GET STORED TOKEN =====
  const getStoredToken = (): string | null => {
    return TokenService.getToken();
  };

  return {
    // State
    isSubmitting,
    authError,

    // Methods
    submitRegistration,
    submitLogin,
    handleLogout,
    isAuthenticated,
    getStoredToken,
  };
}