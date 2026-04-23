import { describe, it, expect, vi, beforeEach } from 'vitest';
import api from '../axios';
import axios from 'axios';

vi.mock('axios', async () => {
  const actual = await vi.importActual('axios') as any;
  return {
    default: {
      ...actual,
      create: vi.fn(() => actual.create()),
    }
  };
});

describe('Axios Interceptor', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('mengubah pesan error >= 500 menjadi pesan ramah', async () => {
    // Mock agar interceptor menangkap respon 500
    const mockError = {
      response: {
        status: 500,
        data: { message: 'Original Server Error' }
      }
    };

    // Kita paksa interceptor untuk mengolah error ini
    const interceptor = (api.interceptors.response as any).handlers[0].rejected;
    
    try {
      await interceptor(mockError);
    } catch (error: any) {
      expect(error.response.data.message).toBe('Server SABANA sedang dalam perawatan atau mengalami gangguan. Mohon coba lagi nanti.');
    }
  });

  it('menangani error 403 (Forbidden)', async () => {
    const mockError = {
      response: {
        status: 403,
        data: { message: 'Forbidden' }
      }
    };

    const interceptor = (api.interceptors.response as any).handlers[0].rejected;

    try {
      await interceptor(mockError);
    } catch (error: any) {
      expect(error.response.data.message).toBe('Anda tidak memiliki akses untuk melakukan tindakan ini.');
    }
  });

  it('menangani kegagalan koneksi jaringan (No Response)', async () => {
    const mockError = {
      response: undefined // Simulasikan tidak ada respon dari server
    };

    const interceptor = (api.interceptors.response as any).handlers[0].rejected;

    try {
      await interceptor(mockError);
    } catch (error: any) {
      expect(error.message).toBe('Koneksi terputus. Periksa jaringan internet Anda.');
    }
  });
});