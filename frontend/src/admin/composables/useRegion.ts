import { ref } from 'vue';
import adminApi from '../api/axios';

export interface Region {
  id: string;
  name: string;
}

export function useRegion() {
  const regencies = ref<Region[]>([]);
  const districts = ref<Region[]>([]);
  const villages = ref<Region[]>([]);
  const isRegionLoading = ref(false);

  const fetchRegencies = async (): Promise<void> => {
    isRegionLoading.value = true;
    try {
      const response = await adminApi.get('/regions/regencies');
      regencies.value = response.data;
    } catch (err: unknown) {
      console.error(err);
    } finally {
      isRegionLoading.value = false;
    }
  };

  const fetchDistricts = async (regencyId: string): Promise<void> => {
    if (!regencyId) return;
    isRegionLoading.value = true;
    try {
      const response = await adminApi.get('/regions/districts', { params: { regency_id: regencyId } });
      districts.value = response.data;
    } catch (err: unknown) {
      console.error(err);
    } finally {
      isRegionLoading.value = false;
    }
  };

  const fetchVillages = async (districtId: string): Promise<void> => {
    if (!districtId) return;
    isRegionLoading.value = true;
    try {
      const response = await adminApi.get('/regions/villages', { params: { district_id: districtId } });
      villages.value = response.data;
    } catch (err: unknown) {
      console.error(err);
    } finally {
      isRegionLoading.value = false;
    }
  };

  const clearRegions = (): void => {
    districts.value = [];
    villages.value = [];
  };

  return {
    regencies,
    districts,
    villages,
    isRegionLoading,
    fetchRegencies,
    fetchDistricts,
    fetchVillages,
    clearRegions,
  };
}