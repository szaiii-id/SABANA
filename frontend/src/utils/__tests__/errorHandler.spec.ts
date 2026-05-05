// src/utils/__tests__/errorHandler.spec.ts
import { describe, it, expect } from 'vitest';
import { getSafeErrorMessage } from '../errorHandler';

// ============================================================
// TEST SUITE
// ============================================================
describe('getSafeErrorMessage - Professional QA Test Suite', () => {

  // ============================================================
  // NULL / UNDEFINED / EMPTY
  // ============================================================
  describe('Empty & Invalid Input', () => {

    it('[EMPTY-01] Return empty string untuk null', () => {
      expect(getSafeErrorMessage(null)).toBe('');
    });

    it('[EMPTY-02] Return empty string untuk undefined', () => {
      expect(getSafeErrorMessage(undefined)).toBe('');
    });

    it('[EMPTY-03] Return empty string untuk string kosong', () => {
      expect(getSafeErrorMessage('')).toBe('');
    });

    it('[EMPTY-04] ⚠️ Whitespace tidak di-trim (potential improvement)', () => {
    // Fungsi saat ini: whitespace dianggap valid
    // Improvement: sebaiknya di-trim() dulu
    expect(getSafeErrorMessage('   ')).toBe('   ');
    });
  });

  // ============================================================
  // SQL-RELATED ERRORS (Harus di-mask)
  // ============================================================
  describe('SQL Error Filtering', () => {

    it('[SQL-01] Mask pesan mengandung "sql"', () => {
      const result = getSafeErrorMessage('SQL syntax error near SELECT');
      expect(result).toContain('Layanan sedang sibuk');
    });

    it('[SQL-02] Mask pesan mengandung "SQL" uppercase', () => {
      const result = getSafeErrorMessage('SQLSTATE[23000]: Integrity constraint');
      expect(result).toContain('Layanan sedang sibuk');
    });

    it('[SQL-03] Mask pesan mengandung "mysql"', () => {
      const result = getSafeErrorMessage('MySQL server has gone away');
      expect(result).toContain('Layanan sedang sibuk');
    });
  });

  // ============================================================
  // EXCEPTION ERRORS (Harus di-mask)
  // ============================================================
  describe('Exception Error Filtering', () => {

    it('[EXC-01] Mask pesan mengandung "exception"', () => {
      const result = getSafeErrorMessage('NullPointerException occurred');
      expect(result).toContain('Layanan sedang sibuk');
    });

    it('[EXC-02] Mask pesan mengandung "Exception"', () => {
      const result = getSafeErrorMessage('Internal Server Exception');
      expect(result).toContain('Layanan sedang sibuk');
    });
  });

  // ============================================================
  // TYPEERROR (Harus di-mask)
  // ============================================================
  describe('TypeError Filtering', () => {

    it('[TYPE-01] Mask pesan mengandung "typeerror"', () => {
      const result = getSafeErrorMessage('TypeError: cannot read property of null');
      expect(result).toContain('Layanan sedang sibuk');
    });

    it('[TYPE-02] Mask pesan mengandung "TypeError"', () => {
      const result = getSafeErrorMessage('Uncaught TypeError: undefined is not a function');
      expect(result).toContain('Layanan sedang sibuk');
    });
  });

  // ============================================================
  // SERVER ERROR (Harus di-mask)
  // ============================================================
  describe('Server Error Filtering', () => {

    it('[SRV-01] Mask pesan mengandung "server error"', () => {
      const result = getSafeErrorMessage('Internal server error 500');
      expect(result).toContain('Layanan sedang sibuk');
    });

    it('[SRV-02] Mask pesan mengandung "Server Error"', () => {
      const result = getSafeErrorMessage('500 Server Error');
      expect(result).toContain('Layanan sedang sibuk');
    });
  });

  // ============================================================
  // UNDEFINED (Harus di-mask)
  // ============================================================
  describe('Undefined Error Filtering', () => {

    it('[UND-01] Mask pesan mengandung "undefined"', () => {
      const result = getSafeErrorMessage('Error: undefined is not an object');
      expect(result).toContain('Layanan sedang sibuk');
    });

    it('[UND-02] Mask pesan mengandung "UNDEFINED"', () => {
      const result = getSafeErrorMessage('UNDEFINED VARIABLE');
      expect(result).toContain('Layanan sedang sibuk');
    });
  });

  // ============================================================
  // PESAN TERLALU PANJANG (Harus di-mask)
  // ============================================================
  describe('Long Message Filtering', () => {

    it('[LONG-01] Mask pesan > 80 karakter', () => {
      const longMessage = 'A'.repeat(81);
      const result = getSafeErrorMessage(longMessage);
      expect(result).toContain('Layanan sedang sibuk');
    });

    it('[LONG-02] Mask pesan tepat 81 karakter', () => {
      const longMessage = 'B'.repeat(81);
      const result = getSafeErrorMessage(longMessage);
      expect(result).toContain('Layanan sedang sibuk');
    });

    it('[LONG-03] TIDAK mask pesan tepat 80 karakter', () => {
      const message = 'C'.repeat(80);
      const result = getSafeErrorMessage(message);
      expect(result).toBe(message);
    });

    it('[LONG-04] TIDAK mask pesan < 80 karakter', () => {
      const message = 'Pesan pendek';
      const result = getSafeErrorMessage(message);
      expect(result).toBe(message);
    });
  });

  // ============================================================
  // PESAN VALID (Tidak di-mask)
  // ============================================================
  describe('Valid Messages (Not Masked)', () => {

    it('[VALID-01] Pesan normal tidak berubah', () => {
      const message = 'NIK sudah terdaftar dalam sistem.';
      expect(getSafeErrorMessage(message)).toBe(message);
    });

    it('[VALID-02] Pesan validasi tidak berubah', () => {
      const message = 'PIN tidak sesuai, silakan coba lagi.';
      expect(getSafeErrorMessage(message)).toBe(message);
    });

    it('[VALID-03] Pesan server pendek tidak berubah', () => {
      const message = 'Koneksi terputus, periksa jaringan.';
      expect(getSafeErrorMessage(message)).toBe(message);
    });

    it('[VALID-04] Pesan dengan angka tidak berubah', () => {
      const message = 'Nomor WhatsApp 08123456789 tidak valid.';
      expect(getSafeErrorMessage(message)).toBe(message);
    });
  });

  // ============================================================
  // CASE INSENSITIVE
  // ============================================================
  describe('Case Insensitive Matching', () => {

    it('[CASE-01] "SQL" tertangkap', () => {
      expect(getSafeErrorMessage('SQL Error')).toContain('Layanan sedang sibuk');
    });

    it('[CASE-02] "Sql" tertangkap', () => {
      expect(getSafeErrorMessage('Sql Error')).toContain('Layanan sedang sibuk');
    });

    it('[CASE-03] "sql" tertangkap', () => {
      expect(getSafeErrorMessage('sql error')).toContain('Layanan sedang sibuk');
    });
  });

  // ============================================================
  // EDGE CASES
  // ============================================================
  describe('Edge Cases', () => {

    it('[EDGE-01] Pesan dengan multiple keywords', () => {
      const message = 'SQL Exception TypeError: undefined server error';
      expect(getSafeErrorMessage(message)).toContain('Layanan sedang sibuk');
    });

    it('[EDGE-02] Pesan "sql" di tengah kata (tidak ikut ke-mask? atau iya?)', () => {
      // Kata "pasal" mengandung "sql"? Tidak, karena cek: pasal.includes('sql') = false
      const message = 'Data pasal tidak ditemukan';
      expect(getSafeErrorMessage(message)).toBe(message);
    });

    it('[EDGE-03] Pesan "exception" di tengah kata (tidak)', () => {
      const message = 'Data exception tidak ditemukan';
      expect(getSafeErrorMessage(message)).toContain('Layanan sedang sibuk');
    });
  });
});