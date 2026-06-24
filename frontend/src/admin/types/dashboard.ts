export interface RegencyStats {
  totalPenerima: number
  totalDanaTersalurkan: number
  programAktif: number
  antreanVerifikasi: number
}

export interface DistrictDistribution {
  name: string
  count: number
  amount: number
}

export interface MonthlyTrend {
  month: string
  amount: number
}

export interface VerificationStatus {
  label: string
  count: number
  color: string
}

export interface TopVillage {
  name: string
  count: number
}

export interface DistrictStats {
  totalPenerima: number
  totalDanaTersalurkan: number
  antreanVerifikasi: number
  pengajuanBulanIni: number
}

export interface VillageDistribution {
  name: string
  count: number
  amount: number
}

export interface VillageStats {
  totalWarga: number
  pengajuanBulanIni: number
  disetujui: number
  disalurkan: number
}

export interface RecentCitizen {
  nik: string
  full_name: string
  created_at: string
}