import { describe, it, expect } from 'vitest';
import { getSafeErrorMessage } from '../../utils/errorHandler';

describe('getSafeErrorMessage', () => {

  // =============================================
  // FALSY INPUTS — 4 TEST
  // =============================================

  it('test_return_empty_string_for_null', () => {
    expect(getSafeErrorMessage(null)).toBe('');
  });

  it('test_return_empty_string_for_undefined', () => {
    expect(getSafeErrorMessage(undefined)).toBe('');
  });

  it('test_return_empty_string_for_empty_string', () => {
    expect(getSafeErrorMessage('')).toBe('');
  });

  it('test_return_empty_string_for_whitespace_only', () => {
    expect(getSafeErrorMessage('   ')).toBe('');
  });

  // =============================================
  // BUSINESS ERRORS — HARUS DITAMPILKAN — 5 TEST
  // =============================================

  it('test_menampilkan_pesan_nik_sudah_terdaftar', () => {
    const message = 'NIK ini sudah terdaftar. Gunakan fitur Lupa PIN jika Anda pemilik akun.';
    expect(getSafeErrorMessage(message)).toBe(message);
  });

  it('test_menampilkan_pesan_kode_verifikasi_masih_berlaku', () => {
    const message = 'Kode verifikasi masih berlaku. Cek WhatsApp Anda atau tunggu 300 detik untuk kirim ulang.';
    expect(getSafeErrorMessage(message)).toBe(message);
  });

  it('test_menampilkan_pesan_data_tidak_ditemukan', () => {
    expect(getSafeErrorMessage('Data pendaftar tidak ditemukan.')).toBe('Data pendaftar tidak ditemukan.');
  });

  it('test_menampilkan_pesan_akun_sudah_terverifikasi', () => {
    expect(getSafeErrorMessage('Akun ini sudah terverifikasi. Tidak dapat mengubah data.'))
      .toBe('Akun ini sudah terverifikasi. Tidak dapat mengubah data.');
  });

  it('test_menampilkan_pesan_error_pendek', () => {
    expect(getSafeErrorMessage('Gagal terhubung ke server SABANA.'))
      .toBe('Gagal terhubung ke server SABANA.');
  });

  // =============================================
  // TECHNICAL ERRORS — HARUS DISEMBUNYIKAN — 10 TEST
  // =============================================

  const GENERIC_MESSAGE = 'Layanan sedang sibuk atau terjadi gangguan sistem. Silakan coba beberapa saat lagi.';

  it('test_menyembunyikan_error_sql', () => {
    expect(getSafeErrorMessage('SQLSTATE[HY000]: Connection refused')).toBe(GENERIC_MESSAGE);
  });

  it('test_menyembunyikan_error_exception', () => {
    expect(getSafeErrorMessage('Uncaught Exception: Database connection failed')).toBe(GENERIC_MESSAGE);
  });

  it('test_menyembunyikan_error_typeerror', () => {
    expect(getSafeErrorMessage('TypeError: Cannot read property of undefined')).toBe(GENERIC_MESSAGE);
  });

  it('test_menyembunyikan_error_undefined', () => {
    expect(getSafeErrorMessage('Cannot read properties of undefined')).toBe(GENERIC_MESSAGE);
  });

  it('test_menyembunyikan_error_stack_trace', () => {
    expect(getSafeErrorMessage('Error: Something wrong\nStack trace: #0 /var/www/html/app/Services/CitizenService.php:45')).toBe(GENERIC_MESSAGE);
  });

  it('test_menyembunyikan_error_syntax_error', () => {
    expect(getSafeErrorMessage('Syntax error in SQL query')).toBe(GENERIC_MESSAGE);
  });

  it('test_menyembunyikan_error_internal_server_error', () => {
    expect(getSafeErrorMessage('Internal Server Error: 500')).toBe(GENERIC_MESSAGE);
  });

  it('test_menyembunyikan_error_could_not_connect', () => {
    expect(getSafeErrorMessage('Could not connect to database server')).toBe(GENERIC_MESSAGE);
  });

  it('test_menyembunyikan_error_connection_refused', () => {
    expect(getSafeErrorMessage('Connection refused on port 3306')).toBe(GENERIC_MESSAGE);
  });

  it('test_menyembunyikan_error_timed_out', () => {
    expect(getSafeErrorMessage('Connection timed out after 30000ms')).toBe(GENERIC_MESSAGE);
  });

  // =============================================
  // EDGE CASES — 2 TEST
  // =============================================

  it('test_trim_spasi_di_akhir_pesan_error_bisnis', () => {
    expect(getSafeErrorMessage('  Data tidak ditemukan.  ')).toBe('Data tidak ditemukan.');
  });

  it('test_case_insensitive_untuk_keyword_teknis', () => {
    expect(getSafeErrorMessage('SqlState: HY000')).toBe(GENERIC_MESSAGE);
  });
});