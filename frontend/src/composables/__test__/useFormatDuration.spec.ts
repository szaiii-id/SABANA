// ===== [IMPORTS] =====
import { describe, it, expect } from 'vitest';
import { formatDuration } from '../useFormatDuration';

// ===== [TEST SUITE] =====
describe('useFormatDuration', () => {
  // ===== [1. HAPPY PATH — 3 test] =====
  describe('Happy Path — Format Normal', () => {
    it('format 0 detik → "0 detik"', () => {
      expect(formatDuration(0)).toBe('0 detik');
    });

    it('format 30 detik → "30 detik"', () => {
      expect(formatDuration(30)).toBe('30 detik');
    });

    it('format 120 detik → "2 menit"', () => {
      expect(formatDuration(120)).toBe('2 menit');
    });
  });

  // ===== [2. SAD PATH — 1 test] =====
  describe('Sad Path — Input Negatif', () => {
    it('format negatif tetap return string (tidak crash)', () => {
      expect(formatDuration(-5)).toBe('-5 detik');
    });
  });

  // ===== [3. BOUNDARY — 3 test] =====
  describe('Boundary — Batas Menit', () => {
    it('59 detik → "59 detik"', () => {
      expect(formatDuration(59)).toBe('59 detik');
    });

    it('60 detik → "1 menit" (fix: >= 60)', () => {
      expect(formatDuration(60)).toBe('1 menit');
    });

    it('61 detik → "1 menit" (fix: Math.round)', () => {
      expect(formatDuration(61)).toBe('1 menit');
    });
  });

  // ===== [4. EDGE CASE — 2 test] =====
  describe('Edge Case — Nilai Ekstrem', () => {
    it('format 3599 detik → "60 menit"', () => {
      expect(formatDuration(3599)).toBe('60 menit');
    });

    it('format 3600 detik → "60 menit"', () => {
      expect(formatDuration(3600)).toBe('60 menit');
    });
  });

  // ===== [5. NULL/EMPTY — 1 test] =====
  describe('Null/Empty — NaN', () => {
    it('format NaN → "NaN detik" (tidak crash)', () => {
      expect(formatDuration(NaN)).toBe('NaN detik');
    });
  });

  // ===== [6. DATA TYPE — 1 test] =====
  describe('Data Type — Return String', () => {
    it('selalu return string', () => {
      expect(typeof formatDuration(10)).toBe('string');
      expect(typeof formatDuration(120)).toBe('string');
    });
  });

  // ===== [7. EQUIVALENCE PARTITION — 2 test] =====
  describe('Equivalence Partition — Detik vs Menit', () => {
    it('1-59 detik → format detik', () => {
      expect(formatDuration(1)).toBe('1 detik');
      expect(formatDuration(30)).toBe('30 detik');
      expect(formatDuration(59)).toBe('59 detik');
    });

    it('60+ detik → format menit', () => {
      expect(formatDuration(60)).toBe('1 menit');
      expect(formatDuration(90)).toBe('2 menit');
      expect(formatDuration(600)).toBe('10 menit');
    });
  });

  // ===== [8. STATE TRANSITION — Tidak relevan] =====
  // ===== [9. CONCURRENCY — Tidak relevan] =====
  // ===== [10. SECURITY — 1 test] =====
  describe('Security — Input Injection', () => {
    it('input string tidak crash', () => {
      expect(() => formatDuration('abc' as any)).not.toThrow();
    });
  });
});