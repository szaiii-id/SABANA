export interface SpkProgram {
  id: string
  name: string
  description: string | null
  quota_total: number | null
  criteria_count: number
  submissions_count: number
  status: string
  banner_url: string | null
}

export interface SpkCriteria {
  key: string
  label: string
  type: string
  sifat: 'benefit' | 'cost'
  weight: number
  ideal_value: number
  options?: Array<{ value: string; score: number }>
}

export interface SpkDecisionRow {
  id: string
  nik: string
  name: string
  status: string
  [key: string]: unknown
}

export interface SpkNormalizedRow {
  id: string
  name: string
  status: string
  [key: string]: number | string
}

export interface SpkWeightedRow {
  id: string
  name: string
  status: string
  total: number
  [key: string]: number | string
}

export interface SpkRankingRow {
  rank: number
  id: string
  name: string
  total: number
  status: string
  recommendation: {
    label: string
    color: 'green' | 'yellow' | 'orange' | 'red' | 'gray'
  }
}

export interface SpkSummary {
  total: number
  quota: number
  highest: number
  lowest: number
  average: number
  eligible: number
}

export interface SpkData {
  program: {
    id: string
    name: string
    quota_total: number | null
  }
  criteria: SpkCriteria[]
  decision_matrix: SpkDecisionRow[]
  normalized_matrix: SpkNormalizedRow[]
  weighted_matrix: SpkWeightedRow[]
  ranking: SpkRankingRow[]
  summary: SpkSummary
  total_alternatives: number
}

export interface SpkFilters {
  regency_id: string
  district_id: string
  village_id: string
  status: string
}