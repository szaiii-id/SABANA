import axios, { type InternalAxiosRequestConfig } from 'axios';

const api = axios.create({
  baseURL: 'http://sabana.id:8000/api/v1/', 
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest' 
  },
  timeout: 60000, 
});

api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = localStorage.getItem('token');
  if (token && config.headers) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (!error.response) {
      error.message = 'Koneksi terputus. Periksa jaringan internet Anda.';
      return Promise.reject(error);
    }

    const status = error.response.status;

    if (status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    } 
    
    else if (status >= 500) {
      error.response.data.message = 'Server SABANA sedang dalam perawatan atau mengalami gangguan. Mohon coba lagi nanti.';
    } 
    
    else if (status === 403) {
      error.response.data.message = 'Anda tidak memiliki akses untuk melakukan tindakan ini.';
    }

    return Promise.reject(error);
  }
);

export default api;