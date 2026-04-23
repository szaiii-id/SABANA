import { describe, it, expect, vi, beforeEach } from 'vitest';
import { AspirasiService } from '../AspirasiService';
import { aspirasiEndpoint } from '../../api/aspirasiApi';

vi.mock('../../api/aspirasiApi', () => ({
  aspirasiEndpoint: {
    kirim: vi.fn(),
  },
}));

describe('AspirasiService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('mengirim payload aspirasi ke endpoint yang benar', async () => {
    const payload = {
      nama: 'Jainudin',
      email: 'jai@uniska.ac.id',
      subjek: 'Infrastruktur',
      pesan: 'Jalan di Tabalong perlu perbaikan.'
    };

    (aspirasiEndpoint.kirim as any).mockResolvedValue({ data: { message: 'Berhasil' } });

    await AspirasiService.kirimAspirasi(payload);

    expect(aspirasiEndpoint.kirim).toHaveBeenCalledWith(payload);
  });
});