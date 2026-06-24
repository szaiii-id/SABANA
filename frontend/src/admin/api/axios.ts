import axios, { type InternalAxiosRequestConfig } from 'axios';

const adminApi = axios.create({
  baseURL: `${import.meta.env.VITE_API_URL}/api/v1/${import.meta.env.VITE_ADMIN_PORTAL_PREFIX}/`, 
  headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest' 
  },
  timeout: 60000, 
});

adminApi.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = localStorage.getItem('admin_token');
  if (token && config.headers) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type'];
  }
  
  return config;
});

adminApi.interceptors.response.use(
  (response) => response,
  (error) => {
    if (!error.response) {
      error.message = 'Koneksi ke SABANA Center terputus.';
      return Promise.reject(error);
    }

    const status = error.response.status;
    const serverMessage = error.response.data?.message;

    if (status === 401) {
      localStorage.removeItem('admin_token');
      localStorage.removeItem('admin_user');
      
      const adminPrefix = import.meta.env.VITE_ADMIN_PORTAL_PREFIX || 'sabana-center-63';
      const loginPath = `/${adminPrefix}/login`;
      
      if (!window.location.pathname.startsWith(loginPath)) {
        window.location.href = loginPath;
      }
      return Promise.reject(error);
    }

    if (status === 422 && error.response.data?.errors) {
      return Promise.reject(error);
    }

    if (status >= 500) {
      error.response.data.message = 'Server SABANA sedang dalam perawatan. Silakan coba lagi nanti.';
    } else if (status === 403) {
      error.response.data.message = serverMessage || 'Anda tidak memiliki wewenang untuk tindakan ini.';
    } else if (status === 404) {
      error.response.data.message = serverMessage || 'Data tidak ditemukan.';
    } else if (status === 429) {
      error.response.data.message = serverMessage || 'Terlalu banyak permintaan. Silakan coba lagi nanti.';
    } else if (status === 400) {
      error.response.data.message = serverMessage || 'Permintaan tidak valid.';
    }

    return Promise.reject(error);
  }
);

export default adminApi;