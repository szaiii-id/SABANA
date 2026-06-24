import { ref } from 'vue'
import { dashboardApi } from '../api/dashboardApi'

export function useDashboardStats() {
  const regencyId = ref('')
  const districtId = ref('')
  const villageId = ref('')
  const isLoading = ref(false)
  const role = ref('')

  try {
    const admin = JSON.parse(localStorage.getItem('admin_user') || '{}')
    regencyId.value = admin?.regency_id || ''
    districtId.value = admin?.district_id || ''
    villageId.value = admin?.village_id || ''
    role.value = admin?.role || ''
  } catch {
    // ignore
  }

  // Helper: pakai override ID atau fallback ke localStorage
  const rid = (override?: string) => override || regencyId.value
  const did = (override?: string) => override || districtId.value
  const vid = (override?: string) => override || villageId.value

  // ===== REGENCY =====
  const loadRegencyStats = async (overrideRegencyId?: string) => {
    const id = rid(overrideRegencyId)
    if (!id) return null
    isLoading.value = true
    try {
      const { data } = await dashboardApi.getRegencyStats(id)
      return data.data
    } finally {
      isLoading.value = false
    }
  }

  const loadDistrictDistribution = async (filters?: Record<string, string>, overrideRegencyId?: string) => {
    const id = rid(overrideRegencyId)
    if (!id) return []
    isLoading.value = true
    try {
      const { data } = await dashboardApi.getDistrictDistribution(id, filters)
      return data.data
    } finally {
      isLoading.value = false
    }
  }

  const loadMonthlyTrend = async (filters?: Record<string, string>, overrideRegencyId?: string) => {
    const id = rid(overrideRegencyId)
    if (!id) return []
    isLoading.value = true
    try {
      const { data } = await dashboardApi.getMonthlyTrend(id, filters)
      return data.data
    } finally {
      isLoading.value = false
    }
  }

  const loadVerificationStatus = async (overrideRegencyId?: string) => {
    const id = rid(overrideRegencyId)
    if (!id) return []
    isLoading.value = true
    try {
      const { data } = await dashboardApi.getVerificationStatus(id)
      return data.data
    } finally {
      isLoading.value = false
    }
  }

  const loadTopVillages = async (filters?: Record<string, string>, overrideRegencyId?: string) => {
    const id = rid(overrideRegencyId)
    if (!id) return []
    isLoading.value = true
    try {
      const { data } = await dashboardApi.getTopVillages(id, filters)
      return data.data
    } finally {
      isLoading.value = false
    }
  }

  // ===== DISTRICT =====
  const loadDistrictStats = async (overrideDistrictId?: string) => {
    const id = did(overrideDistrictId)
    if (!id) return null
    isLoading.value = true
    try {
      const { data } = await dashboardApi.getDistrictStats(id)
      return data.data
    } finally {
      isLoading.value = false
    }
  }

  const loadVillageDistribution = async (filters?: Record<string, string>, overrideDistrictId?: string) => {
    const id = did(overrideDistrictId)
    if (!id) return []
    isLoading.value = true
    try {
      const { data } = await dashboardApi.getVillageDistribution(id, filters)
      return data.data
    } finally {
      isLoading.value = false
    }
  }

  const loadDistrictMonthlyTrend = async (filters?: Record<string, string>, overrideDistrictId?: string) => {
    const id = did(overrideDistrictId)
    if (!id) return []
    isLoading.value = true
    try {
      const { data } = await dashboardApi.getDistrictMonthlyTrend(id, filters)
      return data.data
    } finally {
      isLoading.value = false
    }
  }

  const loadDistrictVerificationStatus = async (overrideDistrictId?: string) => {
    const id = did(overrideDistrictId)
    if (!id) return []
    isLoading.value = true
    try {
      const { data } = await dashboardApi.getDistrictVerificationStatus(id)
      return data.data
    } finally {
      isLoading.value = false
    }
  }

  // ===== VILLAGE =====
  const loadVillageStats = async (overrideVillageId?: string) => {
    const id = vid(overrideVillageId)
    if (!id) return null
    isLoading.value = true
    try {
      const { data } = await dashboardApi.getVillageStats(id)
      return data.data
    } finally {
      isLoading.value = false
    }
  }

  const loadRecentCitizens = async (overrideVillageId?: string) => {
    const id = vid(overrideVillageId)
    if (!id) return []
    isLoading.value = true
    try {
      const { data } = await dashboardApi.getRecentCitizens(id)
      return data.data
    } finally {
      isLoading.value = false
    }
  }

  return {
    regencyId,
    districtId,
    villageId,
    role,
    isLoading,
    // Regency
    loadRegencyStats,
    loadDistrictDistribution,
    loadMonthlyTrend,
    loadVerificationStatus,
    loadTopVillages,
    // District
    loadDistrictStats,
    loadVillageDistribution,
    loadDistrictMonthlyTrend,
    loadDistrictVerificationStatus,
    // Village
    loadVillageStats,
    loadRecentCitizens,
  }
}