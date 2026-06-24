import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useRegion } from '../useRegion';
import adminApi from '../../api/axios';

// =============================================
// MOCK: axios
// =============================================
vi.mock('../../api/axios', () => ({
  default: {
    get: vi.fn(),
  },
}));

// =============================================
// HELPERS
// =============================================
const mockRegencies = [
  { id: '6301', name: 'Tanah Laut' },
  { id: '6302', name: 'Banjar' },
];

const mockDistricts = [
  { id: '6301020', name: 'Pelaihari' },
  { id: '6301021', name: 'Bati-Bati' },
];

const mockVillages = [
  { id: '6301020001', name: 'Desa Test' },
  { id: '6301020002', name: 'Desa Lain' },
];

// =============================================
// TEST SUITE
// =============================================
describe('useRegion', () => {

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===== HAPPY PATH =====

  it('test_fetchRegencies_isi_data', async () => {
    (adminApi.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: mockRegencies });

    const { regencies, fetchRegencies } = useRegion();
    await fetchRegencies();

    expect(regencies.value).toEqual(mockRegencies);
    expect(adminApi.get).toHaveBeenCalledWith('/regions/regencies');
  });

  it('test_fetchDistricts_isi_data', async () => {
    (adminApi.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: mockDistricts });

    const { districts, fetchDistricts } = useRegion();
    await fetchDistricts('6301');

    expect(districts.value).toEqual(mockDistricts);
    expect(adminApi.get).toHaveBeenCalledWith('/regions/districts', { params: { regency_id: '6301' } });
  });

  it('test_fetchVillages_isi_data', async () => {
    (adminApi.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: mockVillages });

    const { villages, fetchVillages } = useRegion();
    await fetchVillages('6301020');

    expect(villages.value).toEqual(mockVillages);
    expect(adminApi.get).toHaveBeenCalledWith('/regions/villages', { params: { district_id: '6301020' } });
  });

  // ===== SAD PATH =====

  it('test_fetchRegencies_gagal_tidak_crash', async () => {
    (adminApi.get as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Network Error'));

    const { regencies, fetchRegencies } = useRegion();
    await fetchRegencies();

    expect(regencies.value).toEqual([]);
  });

  it('test_fetchDistricts_gagal_tidak_crash', async () => {
    (adminApi.get as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Network Error'));

    const { districts, fetchDistricts } = useRegion();
    await fetchDistricts('6301');

    expect(districts.value).toEqual([]);
  });

  it('test_fetchVillages_gagal_tidak_crash', async () => {
    (adminApi.get as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('Network Error'));

    const { villages, fetchVillages } = useRegion();
    await fetchVillages('6301020');

    expect(villages.value).toEqual([]);
  });

  // ===== BOUNDARY =====

  it('test_fetchDistricts_tanpa_id_return_awal', async () => {
    const { districts, fetchDistricts } = useRegion();
    await fetchDistricts('');

    expect(districts.value).toEqual([]);
    expect(adminApi.get).not.toHaveBeenCalled();
  });

  it('test_fetchVillages_tanpa_id_return_awal', async () => {
    const { villages, fetchVillages } = useRegion();
    await fetchVillages('');

    expect(villages.value).toEqual([]);
    expect(adminApi.get).not.toHaveBeenCalled();
  });

  // ===== EDGE CASE =====

  it('test_clearRegions_reset_districts_villages', async () => {
    (adminApi.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: mockDistricts });

    const { districts, villages, fetchDistricts, clearRegions } = useRegion();
    await fetchDistricts('6301');

    expect(districts.value).toEqual(mockDistricts);

    clearRegions();

    expect(districts.value).toEqual([]);
    expect(villages.value).toEqual([]);
  });

  // ===== NULL / EMPTY =====

  it('test_initial_state_kosong', () => {
    const { regencies, districts, villages, isRegionLoading } = useRegion();

    expect(regencies.value).toEqual([]);
    expect(districts.value).toEqual([]);
    expect(villages.value).toEqual([]);
    expect(isRegionLoading.value).toBe(false);
  });

  // ===== DATA TYPE =====

  it('test_fetchRegencies_return_Region_array', async () => {
    (adminApi.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: mockRegencies });

    const { regencies, fetchRegencies } = useRegion();
    await fetchRegencies();

    expect(Array.isArray(regencies.value)).toBe(true);
    expect(regencies.value[0]).toHaveProperty('id');
    expect(regencies.value[0]).toHaveProperty('name');
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_fetchRegencies_data_kosong', async () => {
    (adminApi.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: [] });

    const { regencies, fetchRegencies } = useRegion();
    await fetchRegencies();

    expect(regencies.value).toEqual([]);
  });

  it('test_fetchDistricts_data_kosong', async () => {
    (adminApi.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: [] });

    const { districts, fetchDistricts } = useRegion();
    await fetchDistricts('6301');

    expect(districts.value).toEqual([]);
  });

  // ===== STATE TRANSITION =====

  it('test_isRegionLoading_saat_fetch', async () => {
    let resolvePromise: (value: unknown) => void;
    const pendingPromise = new Promise((resolve) => { resolvePromise = resolve; });
    (adminApi.get as ReturnType<typeof vi.fn>).mockReturnValue(pendingPromise);

    const { isRegionLoading, fetchRegencies } = useRegion();
    const fetchPromise = fetchRegencies();

    expect(isRegionLoading.value).toBe(true);

    resolvePromise!({ data: mockRegencies });
    await fetchPromise;

    expect(isRegionLoading.value).toBe(false);
  });

  // ===== CONCURRENCY =====

  it('test_fetch_parallel_aman', async () => {
    (adminApi.get as ReturnType<typeof vi.fn>)
      .mockResolvedValueOnce({ data: mockRegencies })
      .mockResolvedValueOnce({ data: mockDistricts });

    const { regencies, districts, fetchRegencies, fetchDistricts } = useRegion();

    await Promise.all([
      fetchRegencies(),
      fetchDistricts('6301'),
    ]);

    expect(regencies.value).toEqual(mockRegencies);
    expect(districts.value).toEqual(mockDistricts);
  });

  // ===== SECURITY =====

  it('test_fetchRegencies_401_tidak_crash', async () => {
    (adminApi.get as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { status: 401 },
    });

    const { regencies, fetchRegencies } = useRegion();
    await fetchRegencies();

    expect(regencies.value).toEqual([]);
  });
});