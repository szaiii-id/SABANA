// src/api/__tests__/axios.spec.ts
import { describe, it, expect, vi, beforeEach } from 'vitest';
import api from '../axios';

// ============================================================
// MOCK LOCALSTORAGE
// ============================================================
let store: Record<string, string> = {};

vi.stubGlobal('localStorage', {
  getItem: vi.fn((key: string) => store[key] || null),
  setItem: vi.fn((key: string, value: string) => { store[key] = value; }),
  removeItem: vi.fn((key: string) => { delete store[key]; }),
  clear: vi.fn(() => { store = {}; }),
});

// Mock window.location
const locationMock = { href: '' };
vi.stubGlobal('location', locationMock);

// ============================================================
// TEST SUITE
// ============================================================
describe('Axios Instance - Professional QA Test Suite', () => {

  beforeEach(() => {
    vi.clearAllMocks();
    store = {};
    locationMock.href = '';
  });

  // ============================================================
  // CONFIGURATION
  // ============================================================
  describe('Configuration', () => {

    it('[CONFIG-01] BaseURL benar', () => {
      expect(api.defaults.baseURL).toBe('http://sabana.id:8000/api/v1/');
    });

    it('[CONFIG-02] Timeout 60000ms', () => {
      expect(api.defaults.timeout).toBe(60000);
    });

    it('[CONFIG-03] Accept header application/json', () => {
      expect(api.defaults.headers['Accept']).toBe('application/json');
    });

    it('[CONFIG-04] Content-Type application/json', () => {
      expect(api.defaults.headers['Content-Type']).toBe('application/json');
    });

    it('[CONFIG-05] X-Requested-With XMLHttpRequest', () => {
      expect(api.defaults.headers['X-Requested-With']).toBe('XMLHttpRequest');
    });
  });

  // ============================================================
  // REQUEST INTERCEPTOR
  // ============================================================
  describe('Request Interceptor', () => {

    it('[REQ-01] Menambahkan Authorization header jika token ada', async () => {
      const requestInterceptor = (api.interceptors.request as any).handlers[0].fulfilled;
      
      store['token'] = 'test-token-123';
      
      const config = { headers: {} };
      const result = requestInterceptor(config);

      expect(result.headers.Authorization).toBe('Bearer test-token-123');
    });

    it('[REQ-02] Tidak menambahkan Authorization jika token tidak ada', async () => {
      const requestInterceptor = (api.interceptors.request as any).handlers[0].fulfilled;
      
      const config = { headers: {} };
      const result = requestInterceptor(config);

      expect(result.headers.Authorization).toBeUndefined();
    });

    it('[REQ-03] Tidak crash jika config.headers null/undefined', async () => {
      const requestInterceptor = (api.interceptors.request as any).handlers[0].fulfilled;
      
      store['token'] = 'token-123';
      
      const config = {};
      const result = requestInterceptor(config);

      // Tidak throw error
      expect(result).toBeDefined();
    });
  });

  // ============================================================
  // RESPONSE INTERCEPTOR - SUCCESS
  // ============================================================
  describe('Response Interceptor - Success', () => {

    it('[RES-01] Response sukses diteruskan tanpa perubahan', async () => {
      const responseInterceptor = (api.interceptors.response as any).handlers[0].fulfilled;
      
      const response = { data: { message: 'OK' }, status: 200 };
      const result = responseInterceptor(response);

      expect(result).toEqual(response);
    });
  });

  // ============================================================
  // RESPONSE INTERCEPTOR - ERROR 401
  // ============================================================
  describe('Response Interceptor - Error 401', () => {

    const getRejectedHandler = () => (api.interceptors.response as any).handlers[0].rejected;

    it('[401-01] Hapus token & user dari localStorage', async () => {
      store['token'] = 'old-token';
      store['user'] = JSON.stringify({ id: 1 });

      const error = {
        response: { status: 401, data: { message: 'Unauthorized' } }
      };

      try {
        await getRejectedHandler()(error);
      } catch (e) {}

      expect(store['token']).toBeUndefined();
      expect(store['user']).toBeUndefined();
    });

    it('[401-02] Redirect ke /login', async () => {
      const error = {
        response: { status: 401, data: { message: 'Unauthorized' } }
      };

      try {
        await getRejectedHandler()(error);
      } catch (e) {}

      expect(locationMock.href).toBe('/login');
    });

    it('[401-03] Promise.reject dengan error asli', async () => {
      const error = {
        response: { status: 401, data: { message: 'Unauthorized' } }
      };

      try {
        await getRejectedHandler()(error);
        expect(true).toBe(false); // Harusnya throw
      } catch (e: any) {
        expect(e.response.status).toBe(401);
      }
    });
  });

  // ============================================================
  // RESPONSE INTERCEPTOR - ERROR >= 500
  // ============================================================
  describe('Response Interceptor - Error 500+', () => {

    const getRejectedHandler = () => (api.interceptors.response as any).handlers[0].rejected;

    it('[500-01] Override message untuk status 500', async () => {
      const error = {
        response: { status: 500, data: { message: 'Original Error' } }
      };

      try {
        await getRejectedHandler()(error);
      } catch (e: any) {
        expect(e.response.data.message).toContain('Server SABANA sedang dalam perawatan');
      }
    });

    it('[500-02] Override message untuk status 502', async () => {
      const error = {
        response: { status: 502, data: { message: 'Bad Gateway' } }
      };

      try {
        await getRejectedHandler()(error);
      } catch (e: any) {
        expect(e.response.data.message).toContain('Server SABANA sedang dalam perawatan');
      }
    });

    it('[500-03] Override message untuk status 503', async () => {
      const error = {
        response: { status: 503, data: { message: 'Service Unavailable' } }
      };

      try {
        await getRejectedHandler()(error);
      } catch (e: any) {
        expect(e.response.data.message).toContain('Server SABANA sedang dalam perawatan');
      }
    });
  });

  // ============================================================
  // RESPONSE INTERCEPTOR - ERROR 403
  // ============================================================
  describe('Response Interceptor - Error 403', () => {

    const getRejectedHandler = () => (api.interceptors.response as any).handlers[0].rejected;

    it('[403-01] Override message untuk status 403', async () => {
      const error = {
        response: { status: 403, data: { message: 'Forbidden' } }
      };

      try {
        await getRejectedHandler()(error);
      } catch (e: any) {
        expect(e.response.data.message).toBe('Anda tidak memiliki akses untuk melakukan tindakan ini.');
      }
    });
  });

  // ============================================================
  // RESPONSE INTERCEPTOR - NO RESPONSE (NETWORK ERROR)
  // ============================================================
  describe('Response Interceptor - Network Error', () => {

    const getRejectedHandler = () => (api.interceptors.response as any).handlers[0].rejected;

    it('[NET-01] Set message untuk network error (no response)', async () => {
      const error = {
        response: undefined
      };

      try {
        await getRejectedHandler()(error);
      } catch (e: any) {
        expect(e.message).toBe('Koneksi terputus. Periksa jaringan internet Anda.');
      }
    });

    it('[NET-02] Network error tetap Promise.reject', async () => {
      const error = { response: undefined };

      try {
        await getRejectedHandler()(error);
        expect(true).toBe(false);
      } catch (e: any) {
        expect(e.message).toBeDefined();
      }
    });
  });

  // ============================================================
  // RESPONSE INTERCEPTOR - OTHER ERRORS (DITERUSKAN)
  // ============================================================
  describe('Response Interceptor - Other Errors', () => {

    const getRejectedHandler = () => (api.interceptors.response as any).handlers[0].rejected;

    it('[OTHER-01] Error 404 diteruskan tanpa perubahan', async () => {
      const error = {
        response: { status: 404, data: { message: 'Not Found' } }
      };

      try {
        await getRejectedHandler()(error);
      } catch (e: any) {
        expect(e.response.status).toBe(404);
        expect(e.response.data.message).toBe('Not Found');
      }
    });

    it('[OTHER-02] Error 422 diteruskan tanpa perubahan', async () => {
      const error = {
        response: { status: 422, data: { message: 'Validation Error' } }
      };

      try {
        await getRejectedHandler()(error);
      } catch (e: any) {
        expect(e.response.status).toBe(422);
        expect(e.response.data.message).toBe('Validation Error');
      }
    });

    it('[OTHER-03] Error 400 diteruskan tanpa perubahan', async () => {
      const error = {
        response: { status: 400, data: { message: 'Bad Request' } }
      };

      try {
        await getRejectedHandler()(error);
      } catch (e: any) {
        expect(e.response.status).toBe(400);
        expect(e.response.data.message).toBe('Bad Request');
      }
    });

    it('[OTHER-04] Error 429 diteruskan tanpa perubahan', async () => {
      const error = {
        response: { status: 429, data: { message: 'Too Many Requests' } }
      };

      try {
        await getRejectedHandler()(error);
      } catch (e: any) {
        expect(e.response.status).toBe(429);
        expect(e.response.data.message).toBe('Too Many Requests');
      }
    });
  });
});