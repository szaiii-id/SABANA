// ===== [IMPORTS] =====
import { describe, it, expect, vi, beforeEach } from 'vitest'

// ===== [MOCKS] =====
const { mockGet, mockPost, mockPut, mockPatch, mockDelete } = vi.hoisted(() => ({
  mockGet: vi.fn(),
  mockPost: vi.fn(),
  mockPut: vi.fn(),
  mockPatch: vi.fn(),
  mockDelete: vi.fn(),
}))

vi.mock('axios', () => ({
  default: {
    create: () => ({
      get: mockGet,
      post: mockPost,
      put: mockPut,
      patch: mockPatch,
      delete: mockDelete,
      interceptors: {
        request: { use: vi.fn() },
        response: { use: vi.fn() },
      },
    }),
  },
}))

// ===== [STORAGE MOCK] =====
const localStorageMock = (() => {
  let store: Record<string, string> = {}
  return {
    getItem: (key: string) => store[key] ?? null,
    setItem: (key: string, value: string) => { store[key] = value },
    removeItem: (key: string) => { delete store[key] },
    clear: () => { store = {} },
  }
})()

Object.defineProperty(globalThis, 'localStorage', { value: localStorageMock })

import { useDashboardStats } from '../useDashboardStats'

// ===== [TESTS] =====
describe('useDashboardStats', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
    mockGet.mockResolvedValue({ data: { data: {} } })
  })

  it('harus membaca regency_id dari localStorage', () => {
    localStorage.setItem('admin_user', JSON.stringify({ regency_id: '6301', role: 'regency_admin' }))
    const { regencyId } = useDashboardStats()
    expect(regencyId.value).toBe('6301')
  })

  it('harus load regency stats dari API', async () => {
    localStorage.setItem('admin_user', JSON.stringify({ regency_id: '6301' }))
    mockGet.mockResolvedValue({ data: { data: { totalPenerima: 100 } } })

    const { loadRegencyStats } = useDashboardStats()
    const result = await loadRegencyStats()

    expect(result).toEqual({ totalPenerima: 100 })
  })

  it('harus load district distribution dari API', async () => {
    localStorage.setItem('admin_user', JSON.stringify({ regency_id: '6301' }))
    mockGet.mockResolvedValue({ data: { data: [{ name: 'Martapura', count: 50 }] } })

    const { loadDistrictDistribution } = useDashboardStats()
    const result = await loadDistrictDistribution()

    expect(result).toHaveLength(1)
    expect(result[0].name).toBe('Martapura')
  })

  it('harus return null jika regency_id tidak ada', async () => {
    localStorage.setItem('admin_user', JSON.stringify({ role: 'super_admin' }))
    const { loadRegencyStats } = useDashboardStats()
    const result = await loadRegencyStats()
    expect(result).toBeNull()
  })

  it('harus load village stats dari API', async () => {
    localStorage.setItem('admin_user', JSON.stringify({ village_id: '6301010001' }))
    mockGet.mockResolvedValue({ data: { data: { totalWarga: 50 } } })

    const { loadVillageStats } = useDashboardStats()
    const result = await loadVillageStats()

    expect(result).toEqual({ totalWarga: 50 })
  })

  it('harus handle localStorage dengan data kosong', () => {
    localStorage.setItem('admin_user', '{}')
    const { regencyId, districtId, villageId } = useDashboardStats()
    expect(regencyId.value).toBe('')
    expect(districtId.value).toBe('')
    expect(villageId.value).toBe('')
  })

  it('harus return empty array jika district distribution kosong', async () => {
    localStorage.setItem('admin_user', JSON.stringify({ regency_id: '6301' }))
    mockGet.mockResolvedValue({ data: { data: [] } })

    const { loadDistrictDistribution } = useDashboardStats()
    const result = await loadDistrictDistribution()

    expect(result).toEqual([])
  })

  it('harus return array dari loadTopVillages', async () => {
    localStorage.setItem('admin_user', JSON.stringify({ regency_id: '6301' }))
    mockGet.mockResolvedValue({ data: { data: [] } })

    const { loadTopVillages } = useDashboardStats()
    const result = await loadTopVillages()

    expect(Array.isArray(result)).toBe(true)
  })

  it('harus load district stats untuk district_admin', async () => {
    localStorage.setItem('admin_user', JSON.stringify({ district_id: '6301010', role: 'district_admin' }))
    mockGet.mockResolvedValue({ data: { data: { totalPenerima: 30 } } })

    const { loadDistrictStats } = useDashboardStats()
    const result = await loadDistrictStats()

    expect(result).toEqual({ totalPenerima: 30 })
  })

  it('harus merefleksikan perubahan localStorage', () => {
    localStorage.setItem('admin_user', JSON.stringify({ role: 'regency_admin', regency_id: '6301' }))
    const { role } = useDashboardStats()
    expect(role.value).toBe('regency_admin')

    localStorage.setItem('admin_user', JSON.stringify({ role: 'super_admin' }))
    const { role: role2 } = useDashboardStats()
    expect(role2.value).toBe('super_admin')
  })

  it('harus menangani multiple concurrent API calls', async () => {
    localStorage.setItem('admin_user', JSON.stringify({ regency_id: '6301' }))
    mockGet.mockResolvedValue({ data: { data: [] } })

    const { loadRegencyStats, loadVerificationStatus, loadTopVillages } = useDashboardStats()
    const results = await Promise.all([
      loadRegencyStats(),
      loadVerificationStatus(),
      loadTopVillages(),
    ])

    expect(Array.isArray(results[0])).toBe(true)
  })

  it('harus handle localStorage yang corrupt dengan aman', () => {
    localStorage.setItem('admin_user', '{invalid-json')
    const { regencyId, role } = useDashboardStats()
    expect(regencyId.value).toBe('')
    expect(role.value).toBe('')
  })
})