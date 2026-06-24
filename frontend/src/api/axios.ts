import axios, {
  type InternalAxiosRequestConfig,
  type AxiosError,
  type AxiosResponse,
} from 'axios';

// ===== CONSTANTS =====
const TOKEN_KEY = 'sabana_token';

const GENERIC_ERROR_MESSAGES: Record<number, string> = {
  400: 'Permintaan tidak valid.',
  403: 'Anda tidak memiliki akses untuk melakukan tindakan ini.',
  404: 'Data tidak ditemukan.',
  429: 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
  500: 'Server SABANA sedang dalam perawatan atau mengalami gangguan. Mohon coba lagi nanti.',
};

const NETWORK_ERROR_MESSAGE = 'Koneksi terputus. Periksa jaringan internet Anda.';

// ===== TYPES =====
interface ApiErrorResponse {
  message?: string;
  errors?: Record<string, string[]>;
  [key: string]: unknown;
}

interface NetworkError extends Error {
  isNetworkError: boolean;
  originalError: AxiosError;
}

// ===== TOKEN SERVICE =====
const TokenService = {
  getToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
  },

  removeToken(): void {
    localStorage.removeItem(TOKEN_KEY);
  },
};

// ===== CREATE AXIOS INSTANCE =====
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1/',
  headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  timeout: 30000,
});

// ===== REQUEST INTERCEPTOR =====
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig): InternalAxiosRequestConfig => {
    const token = TokenService.getToken();

    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    if (config.data instanceof FormData && config.headers) {
      delete config.headers['Content-Type'];
    }

    return config;
  },
  (error: AxiosError): Promise<never> => {
    return Promise.reject(error);
  },
);

// ===== RESPONSE INTERCEPTOR =====
api.interceptors.response.use(
  (response: AxiosResponse): AxiosResponse => {
    return response;
  },
  (error: AxiosError<ApiErrorResponse>): Promise<never> => {
    // Network error (tidak ada response)
    if (!error.response) {
      const networkError = new Error(NETWORK_ERROR_MESSAGE) as NetworkError;
      networkError.isNetworkError = true;
      networkError.originalError = error;

      return Promise.reject(networkError);
    }

    const status = error.response.status;
    const responseData = error.response.data;
    const serverMessage = responseData?.message;

    // 401 Unauthorized → hapus token & redirect login
    if (status === 401) {
      TokenService.removeToken();

      const currentPath = window.location.pathname;
      if (currentPath !== '/login') {
        window.location.href = '/login';
      }

      return Promise.reject(error);
    }

    // 422 Validation Error → biarkan komponen yang handle
    if (status === 422 && responseData?.errors) {
      return Promise.reject(error);
    }

    // Set pesan error yang user-friendly
    if (!error.response.data) {
      error.response.data = {} as ApiErrorResponse;
    }

    if (status >= 500) {
      error.response.data.message = GENERIC_ERROR_MESSAGES[500];
    } else if (GENERIC_ERROR_MESSAGES[status]) {
      error.response.data.message = serverMessage || GENERIC_ERROR_MESSAGES[status];
    }

    // Log error untuk debugging
    if (import.meta.env.DEV) {
      console.error('[SABANA API Error]', {
        status,
        url: error.config?.url,
        message: error.response.data?.message,
        data: error.response.data,
      });
    }

    return Promise.reject(error);
  },
);

export default api;
export type { ApiErrorResponse, NetworkError };