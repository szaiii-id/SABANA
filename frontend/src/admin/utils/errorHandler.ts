interface ApiErrorResponse {
  status: number;
  data?: {
    message?: string;
    errors?: Record<string, string[]>;
  };
}

interface StructuredError {
  response?: ApiErrorResponse;
}

export function getSafeErrorMessage(error: unknown): string {
  if (!error) return '';
  
  if (typeof error === 'string') return error;
  
  if (error instanceof Error) {
    if (error.message.includes('Network Error')) {
      return 'Gagal terhubung ke server SABANA Center. Periksa koneksi internet Anda.';
    }
    return error.message;
  }
  
  if (typeof error === 'object' && error !== null) {
    const err = error as StructuredError;
    
    if (err.response?.status === 401) {
      return 'Sesi Anda telah berakhir. Silakan login kembali.';
    }
    
    if (err.response?.status === 422) {
      const errors = err.response.data?.errors;
      
      // JANGAN kasih tahu spesifik, gabung jadi satu
      if (errors?.nip || errors?.password) {
        return 'NIP atau Kata Sandi yang Anda masukkan salah.';
      }
      
      return 'Mohon periksa kembali data yang Anda masukkan.';
    }
    
    if (err.response?.status === 429) {
      return 'Terlalu banyak percobaan login. Silakan coba lagi nanti.';
    }
    
    if (err.response?.status === 500) {
      return 'Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.';
    }
    
    return err.response?.data?.message || 'Terjadi kesalahan. Silakan coba lagi.';
  }
  
  return 'Terjadi kesalahan. Silakan coba lagi.';
}