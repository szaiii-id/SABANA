import { describe, it, expect } from 'vitest';
import { getSafeErrorMessage } from '../errorHandler';

// =============================================
// TEST SUITE
// =============================================
describe('errorHandler', () => {

  // ===== HAPPY PATH =====

  it('test_return_empty_string_ketika_error_null', () => {
    const result = getSafeErrorMessage(null);
    expect(result).toBe('');
  });

  it('test_return_empty_string_ketika_error_undefined', () => {
    const result = getSafeErrorMessage(undefined);
    expect(result).toBe('');
  });

  it('test_return_empty_string_ketika_error_falsy', () => {
    const result = getSafeErrorMessage('');
    expect(result).toBe('');
  });

  // ===== SAD PATH =====

  it('test_return_string_langsung_ketika_error_string', () => {
    const result = getSafeErrorMessage('Terjadi kesalahan');
    expect(result).toBe('Terjadi kesalahan');
  });

  it('test_return_message_ketika_Error_instance', () => {
    const error = new Error('Something went wrong');
    const result = getSafeErrorMessage(error);
    expect(result).toBe('Something went wrong');
  });

  it('test_return_network_error_message', () => {
    const error = new Error('Network Error');
    const result = getSafeErrorMessage(error);
    expect(result).toBe('Gagal terhubung ke server SABANA Center. Periksa koneksi internet Anda.');
  });

  // ===== BOUNDARY =====

  it('test_return_string_empty_ketika_error_string_kosong', () => {
    const result = getSafeErrorMessage('');
    expect(result).toBe('');
  });

  it('test_return_pesan_422_ketika_nip_atau_password_salah', () => {
    const error = {
      response: {
        status: 422,
        data: {
          errors: {
            nip: ['NIP wajib diisi.'],
          },
        },
      },
    };
    const result = getSafeErrorMessage(error);
    expect(result).toBe('NIP atau Kata Sandi yang Anda masukkan salah.');
  });

  it('test_return_pesan_422_generik_ketika_field_lain', () => {
    const error = {
      response: {
        status: 422,
        data: {
          errors: {
            name: ['Nama wajib diisi.'],
          },
        },
      },
    };
    const result = getSafeErrorMessage(error);
    expect(result).toBe('Mohon periksa kembali data yang Anda masukkan.');
  });

  // ===== EDGE CASE =====

  it('test_return_pesan_401_ketika_unauthorized', () => {
    const error = {
      response: {
        status: 401,
      },
    };
    const result = getSafeErrorMessage(error);
    expect(result).toBe('Sesi Anda telah berakhir. Silakan login kembali.');
  });

  it('test_return_pesan_429_ketika_rate_limited', () => {
    const error = {
      response: {
        status: 429,
      },
    };
    const result = getSafeErrorMessage(error);
    expect(result).toBe('Terlalu banyak percobaan login. Silakan coba lagi nanti.');
  });

  it('test_return_pesan_500_ketika_server_error', () => {
    const error = {
      response: {
        status: 500,
      },
    };
    const result = getSafeErrorMessage(error);
    expect(result).toBe('Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.');
  });

  // ===== NULL / EMPTY =====

  it('test_return_fallback_ketika_object_tanpa_response', () => {
    const error = {};
    const result = getSafeErrorMessage(error);
    expect(result).toBe('Terjadi kesalahan. Silakan coba lagi.');
  });

  it('test_return_fallback_ketika_unknown_type', () => {
    const error = 12345;
    const result = getSafeErrorMessage(error);
    expect(result).toBe('Terjadi kesalahan. Silakan coba lagi.');
  });

  // ===== DATA TYPE =====

  it('test_handle_boolean_error', () => {
    const result = getSafeErrorMessage(true);
    expect(result).toBe('Terjadi kesalahan. Silakan coba lagi.');
  });

  it('test_handle_symbol_error', () => {
    const result = getSafeErrorMessage(Symbol('test'));
    expect(result).toBe('Terjadi kesalahan. Silakan coba lagi.');
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_return_message_dari_response_data_message', () => {
    const error = {
      response: {
        status: 400,
        data: {
          message: 'Permintaan tidak valid.',
        },
      },
    };
    const result = getSafeErrorMessage(error);
    expect(result).toBe('Permintaan tidak valid.');
  });

  it('test_return_message_dari_response_403', () => {
    const error = {
      response: {
        status: 403,
        data: {
          message: 'Akses ditolak.',
        },
      },
    };
    const result = getSafeErrorMessage(error);
    expect(result).toBe('Akses ditolak.');
  });

  // ===== SECURITY =====

  it('test_tidak_bocorkan_stack_trace', () => {
    const error = {
      response: {
        status: 500,
        data: {
          message: 'SQLSTATE[42S22]: Column not found',
          trace: 'at /app/...',
        },
      },
    };
    const result = getSafeErrorMessage(error);
    expect(result).toBe('Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.');
    expect(result).not.toContain('SQLSTATE');
    expect(result).not.toContain('trace');
  });

  it('test_tidak_bocorkan_detail_422_selain_nip_password', () => {
    const error = {
      response: {
        status: 422,
        data: {
          errors: {
            email: ['Email sudah terdaftar.'],
            phone: ['Nomor telepon tidak valid.'],
          },
        },
      },
    };
    const result = getSafeErrorMessage(error);
    expect(result).toBe('Mohon periksa kembali data yang Anda masukkan.');
    expect(result).not.toContain('Email');
    expect(result).not.toContain('telepon');
  });
});