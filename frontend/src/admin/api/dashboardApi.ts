import adminApi from './axios'

export const dashboardApi = {
  // ===== REGENCY =====
  getRegencyStats: (regencyId: string) =>
    adminApi.get<{ data: import('../types/dashboard').RegencyStats }>(`dashboard/regency/${regencyId}/stats`),

  getDistrictDistribution: (regencyId: string, params?: Record<string, string>) =>
    adminApi.get<{ data: import('../types/dashboard').DistrictDistribution[] }>(`dashboard/regency/${regencyId}/districts`, { params }),

  getMonthlyTrend: (regencyId: string, params?: Record<string, string>) =>
    adminApi.get<{ data: import('../types/dashboard').MonthlyTrend[] }>(`dashboard/regency/${regencyId}/trend`, { params }),

  getVerificationStatus: (regencyId: string) =>
    adminApi.get<{ data: import('../types/dashboard').VerificationStatus[] }>(`dashboard/regency/${regencyId}/verification-status`),

  getTopVillages: (regencyId: string, params?: Record<string, string>) =>
    adminApi.get<{ data: import('../types/dashboard').TopVillage[] }>(`dashboard/regency/${regencyId}/top-villages`, { params }),

  // ===== DISTRICT =====
  getDistrictStats: (districtId: string) =>
    adminApi.get<{ data: import('../types/dashboard').DistrictStats }>(`dashboard/district/${districtId}/stats`),

  getVillageDistribution: (districtId: string, params?: Record<string, string>) =>
    adminApi.get<{ data: import('../types/dashboard').VillageDistribution[] }>(`dashboard/district/${districtId}/villages`, { params }),

  getDistrictMonthlyTrend: (districtId: string, params?: Record<string, string>) =>
    adminApi.get<{ data: import('../types/dashboard').MonthlyTrend[] }>(`dashboard/district/${districtId}/trend`, { params }),

  getDistrictVerificationStatus: (districtId: string) =>
    adminApi.get<{ data: import('../types/dashboard').VerificationStatus[] }>(`dashboard/district/${districtId}/verification-status`),

  // ===== VILLAGE =====
  getVillageStats: (villageId: string) =>
    adminApi.get<{ data: import('../types/dashboard').VillageStats }>(`dashboard/village/${villageId}/stats`),

  getRecentCitizens: (villageId: string) =>
    adminApi.get<{ data: import('../types/dashboard').RecentCitizen[] }>(`dashboard/village/${villageId}/recent-citizens`),
}