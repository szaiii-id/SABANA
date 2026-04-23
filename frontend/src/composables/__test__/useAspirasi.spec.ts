import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAspirasi } from '../useAspirasi';
import { AspirasiService } from '../../services/AspirasiService';

vi.mock('../../services/AspirasiService', () => ({
  AspirasiService: {
    kirimAspirasi: vi.fn(), // Pastikan diinisialisasi sebagai mock function
  },
}));

describe('useAspirasi Composable', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
  });

  it('mengubah status menjadi success saat pengiriman berhasil', async () => {
    const { kirimAspirasiData, submitStatus, submitMessage } = useAspirasi();
    
    // Sekarang mockResolvedValue akan berfungsi karena sudah jadi vi.fn()
    (AspirasiService.kirimAspirasi as any).mockResolvedValue({ message: 'Terima kasih' });

    const result = await kirimAspirasiData({
      nama: 'Test', email: 't@t.com', subjek: 'S', pesan: 'P'
    });

    expect(result).toBe(true);
    expect(submitStatus.value).toBe('success');
    expect(submitMessage.value).toBe('Terima kasih');
  });

  it('menangani error dari server dengan pesan yang ramah', async () => {
    const { kirimAspirasiData, submitStatus, submitMessage } = useAspirasi();
    
    (AspirasiService.kirimAspirasi as any).mockRejectedValue({
      response: { data: { message: 'Server sedang sibuk' } }
    });

    const result = await kirimAspirasiData({
      nama: 'Test', email: 't@t.com', subjek: 'S', pesan: 'P'
    });

    expect(result).toBe(false);
    expect(submitStatus.value).toBe('error');
    expect(submitMessage.value).toBe('Server sedang sibuk');
  });
});