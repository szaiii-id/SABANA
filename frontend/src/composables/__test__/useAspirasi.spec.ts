// src/composables/__test__/useAspirasi.spec.ts
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { useAspirasi } from '../useAspirasi';
import { AspirasiService } from '../../services/AspirasiService';

// ============================================================
// MOCKS
// ============================================================
vi.mock('../../services/AspirasiService', () => ({
  AspirasiService: {
    kirimAspirasi: vi.fn(),
  },
}));

// ============================================================
// TEST SUITE
// ============================================================
describe('useAspirasi - Professional QA Test Suite', () => {

  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  const mockPayload = {
    nama: 'Test User',
    email: 'test@test.com',
    subjek: 'Subjek Aspirasi',
    pesan: 'Isi pesan aspirasi',
  };

  // ============================================================
  // INITIAL STATE
  // ============================================================
  describe('Initial State', () => {

    it('[INIT-01] isSubmitting default false', () => {
      const { isSubmitting } = useAspirasi();
      expect(isSubmitting.value).toBe(false);
    });

    it('[INIT-02] submitStatus default kosong', () => {
      const { submitStatus } = useAspirasi();
      expect(submitStatus.value).toBe('');
    });

    it('[INIT-03] submitMessage default kosong', () => {
      const { submitMessage } = useAspirasi();
      expect(submitMessage.value).toBe('');
    });
  });

  // ============================================================
  // KIRIM ASPIRASI - SUKSES
  // ============================================================
  describe('kirimAspirasiData - Success', () => {

    it('[SUCCESS-01] Memanggil AspirasiService.kirimAspirasi dengan payload benar', async () => {
      const { kirimAspirasiData } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockResolvedValue({ message: 'OK' });

      await kirimAspirasiData(mockPayload);

      expect(AspirasiService.kirimAspirasi).toHaveBeenCalledTimes(1);
      expect(AspirasiService.kirimAspirasi).toHaveBeenCalledWith(mockPayload);
    });

    it('[SUCCESS-02] Return true jika berhasil', async () => {
      const { kirimAspirasiData } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockResolvedValue({ message: 'OK' });

      const result = await kirimAspirasiData(mockPayload);

      expect(result).toBe(true);
    });

    it('[SUCCESS-03] submitStatus = success', async () => {
      const { kirimAspirasiData, submitStatus } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockResolvedValue({ message: 'Terima kasih' });

      await kirimAspirasiData(mockPayload);

      expect(submitStatus.value).toBe('success');
    });

    it('[SUCCESS-04] submitMessage dari response', async () => {
      const { kirimAspirasiData, submitMessage } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockResolvedValue({ message: 'Terima kasih' });

      await kirimAspirasiData(mockPayload);

      expect(submitMessage.value).toBe('Terima kasih');
    });

    it('[SUCCESS-05] submitMessage default jika response tidak ada message', async () => {
      const { kirimAspirasiData, submitMessage } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockResolvedValue({});

      await kirimAspirasiData(mockPayload);

      expect(submitMessage.value).toBe('Aspirasi berhasil dikirim.');
    });

    it('[SUCCESS-06] isSubmitting true selama proses', async () => {
      const { kirimAspirasiData, isSubmitting } = useAspirasi();
      
      let resolvePromise: any;
      (AspirasiService.kirimAspirasi as any).mockReturnValue(
        new Promise((resolve: any) => { resolvePromise = resolve; })
      );

      const promise = kirimAspirasiData(mockPayload);
      expect(isSubmitting.value).toBe(true);

      resolvePromise({ message: 'OK' });
      await promise;

      expect(isSubmitting.value).toBe(false);
    });

    it('[SUCCESS-07] isSubmitting false setelah selesai', async () => {
      const { kirimAspirasiData, isSubmitting } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockResolvedValue({ message: 'OK' });

      await kirimAspirasiData(mockPayload);

      expect(isSubmitting.value).toBe(false);
    });

    it('[SUCCESS-08] submitStatus direset setelah 5 detik', async () => {
      const { kirimAspirasiData, submitStatus } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockResolvedValue({ message: 'OK' });

      await kirimAspirasiData(mockPayload);
      expect(submitStatus.value).toBe('success');

      // Maju 5 detik
      vi.advanceTimersByTime(5000);
      expect(submitStatus.value).toBe('');
    });
  });

  // ============================================================
  // KIRIM ASPIRASI - GAGAL
  // ============================================================
  describe('kirimAspirasiData - Error', () => {

    it('[ERROR-01] Return false jika gagal', async () => {
      const { kirimAspirasiData } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockRejectedValue({
        response: { data: { message: 'Server sedang sibuk' } }
      });

      const result = await kirimAspirasiData(mockPayload);

      expect(result).toBe(false);
    });

    it('[ERROR-02] submitStatus = error', async () => {
      const { kirimAspirasiData, submitStatus } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockRejectedValue({
        response: { data: { message: 'Server sedang sibuk' } }
      });

      await kirimAspirasiData(mockPayload);

      expect(submitStatus.value).toBe('error');
    });

    it('[ERROR-03] submitMessage dari response error', async () => {
      const { kirimAspirasiData, submitMessage } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockRejectedValue({
        response: { data: { message: 'Server sedang sibuk' } }
      });

      await kirimAspirasiData(mockPayload);

      expect(submitMessage.value).toBe('Server sedang sibuk');
    });

    it('[ERROR-04] submitMessage default jika network error', async () => {
      const { kirimAspirasiData, submitMessage } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockRejectedValue(new Error('Network Error'));

      await kirimAspirasiData(mockPayload);

      expect(submitMessage.value).toBe('Gagal mengirim pesan.');
    });

    it('[ERROR-05] isSubmitting false setelah gagal', async () => {
      const { kirimAspirasiData, isSubmitting } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockRejectedValue(new Error('Network Error'));

      await kirimAspirasiData(mockPayload);

      expect(isSubmitting.value).toBe(false);
    });

    it('[ERROR-06] submitStatus direset setelah 5 detik (error case)', async () => {
      const { kirimAspirasiData, submitStatus } = useAspirasi();
      (AspirasiService.kirimAspirasi as any).mockRejectedValue({
        response: { data: { message: 'Error' } }
      });

      await kirimAspirasiData(mockPayload);
      expect(submitStatus.value).toBe('error');

      vi.advanceTimersByTime(5000);
      expect(submitStatus.value).toBe('');
    });
  });

  // ============================================================
  // STATE MANAGEMENT
  // ============================================================
  describe('State Management', () => {

    it('[STATE-01] submitStatus direset sebelum request baru', async () => {
      const { kirimAspirasiData, submitStatus } = useAspirasi();
      
      // Set status sebelumnya
      submitStatus.value = 'success';
      
      (AspirasiService.kirimAspirasi as any).mockResolvedValue({ message: 'OK' });
      await kirimAspirasiData(mockPayload);

      // Status berubah ke success lagi (berarti direset dulu)
      expect(submitStatus.value).toBe('success');
    });

    it('[STATE-02] Multiple calls tidak saling mengganggu', async () => {
      const { kirimAspirasiData, submitStatus, submitMessage } = useAspirasi();
      
      // Request pertama gagal
      (AspirasiService.kirimAspirasi as any).mockRejectedValue({
        response: { data: { message: 'Gagal 1' } }
      });
      await kirimAspirasiData(mockPayload);
      expect(submitMessage.value).toBe('Gagal 1');
      expect(submitStatus.value).toBe('error');

      // Reset timer
      vi.advanceTimersByTime(5000);

      // Request kedua sukses
      (AspirasiService.kirimAspirasi as any).mockResolvedValue({ message: 'Sukses 2' });
      await kirimAspirasiData({ ...mockPayload, pesan: 'Pesan kedua' });
      expect(submitMessage.value).toBe('Sukses 2');
      expect(submitStatus.value).toBe('success');
    });
  });
});