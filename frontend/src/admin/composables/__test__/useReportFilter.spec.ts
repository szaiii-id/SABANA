// ===== [IMPORTS] =====
import { describe, it, expect, vi, beforeEach } from 'vitest'

// ===== [MOCKS] =====
const mockFetchActivePrograms = vi.fn()
const mockGetRegions = vi.fn()

vi.mock('../../services/adminReportService', () => ({
  adminReportService: {
    fetchActivePrograms: (...args: any[]) => mockFetchActivePrograms(...args),
  },
}))

vi.mock('../../services/api/axios', () => ({
  default: {
    get: (...args: any[]) => mockGetRegions(...args),
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

import { useReportFilter } from '../../composables/useReportFilter'

// ===== [TESTS] =====
describe('useReportFilter', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
  })

  // 1. HAPPY PATH
  it('harus menginisialisasi filter dengan nilai default kosong', () => {
    const { filters } = useReportFilter()

    expect(filters.value).toEqual({
      tgl_mulai: '',
      tgl_akhir: '',
      program_id: '',
      wilayah_id: '',
    })
  })

  it('harus memuat daftar program dari service', async () => {
    mockFetchActivePrograms.mockResolvedValue([
      { id: 'uuid-1', name: 'PKH' },
      { id: 'uuid-2', name: 'BLT' },
    ])

    const { programs, loadPrograms } = useReportFilter()
    await loadPrograms()

    expect(programs.value).toHaveLength(2)
    expect(programs.value[0].name).toBe('PKH')
  })

  it('harus mendeteksi filter aktif', () => {
    const { filters, hasFilter } = useReportFilter()

    filters.value.program_id = 'uuid-1'
    expect(hasFilter.value).toBe(true)
  })

  // 2. SAD PATH
  it('harus mengembalikan array kosong jika fetchPrograms gagal', async () => {
    mockFetchActivePrograms.mockRejectedValue(new Error('Network error'))

    const { programs, loadPrograms } = useReportFilter()
    await loadPrograms()

    expect(programs.value).toEqual([])
  })

  // 3. BOUNDARY
  it('harus menghasilkan label filter untuk semua filter terisi', () => {
    const { filters, filterLabel, programs } = useReportFilter()

    programs.value = [
      { id: 'uuid-1', name: 'PKH' },
      { id: 'uuid-2', name: 'BLT' },
    ]
    filters.value = {
      tgl_mulai: '2026-01-01',
      tgl_akhir: '2026-12-31',
      program_id: 'uuid-1',
      wilayah_id: '',
    }

    expect(filterLabel.value).toContain('2026-01-01')
    expect(filterLabel.value).toContain('PKH')
  })

  // 4. EDGE CASE
  it('harus mereset filter ke nilai default', () => {
    const { filters, resetFilters } = useReportFilter()

    filters.value.program_id = 'uuid-1'
    filters.value.tgl_mulai = '2026-01-01'
    resetFilters()

    expect(filters.value).toEqual({
      tgl_mulai: '',
      tgl_akhir: '',
      program_id: '',
      wilayah_id: '',
    })
  })

  // 5. NULL/EMPTY
  it('harus mengembalikan "Semua Data" jika tidak ada filter', () => {
    const { filterLabel } = useReportFilter()

    expect(filterLabel.value).toBe('Semua Data')
  })

  // 6. DATA TYPE
  it('harus mengembalikan string untuk filterLabel', () => {
    const { filterLabel } = useReportFilter()

    expect(typeof filterLabel.value).toBe('string')
  })

  // 7. EQUIVALENCE PARTITION
  it('harus menghasilkan label yang berbeda untuk kombinasi filter berbeda', () => {
    const { filters, filterLabel, programs } = useReportFilter()
    programs.value = [
      { id: 'uuid-1', name: 'PKH' },
    ]

    filters.value = { tgl_mulai: '2026-01-01', tgl_akhir: '', program_id: '', wilayah_id: '' }
    expect(filterLabel.value).toContain('Dari 2026-01-01')

    filters.value = { tgl_mulai: '', tgl_akhir: '', program_id: 'uuid-1', wilayah_id: '' }
    expect(filterLabel.value).toContain('PKH')
  })

  // 8. STATE TRANSITION
  it('harus merefleksikan perubahan filter secara real-time', () => {
    const { filters, hasFilter } = useReportFilter()

    expect(hasFilter.value).toBe(false)
    filters.value.program_id = 'uuid-1'
    expect(hasFilter.value).toBe(true)
    filters.value.program_id = ''
    expect(hasFilter.value).toBe(false)
  })

  // 9. CONCURRENCY
  it('harus menangani loadPrograms dipanggil dua kali berturut-turut', async () => {
    mockFetchActivePrograms.mockResolvedValue([
      { id: 'uuid-1', name: 'PKH' },
    ])

    const { loadPrograms, programs } = useReportFilter()
    await Promise.all([loadPrograms(), loadPrograms()])

    expect(programs.value).toHaveLength(1)
  })

  // 10. SECURITY
  it('harus membaca role admin dari localStorage dengan aman', () => {
    localStorage.setItem('admin_user', JSON.stringify({ role: 'regency_admin', regency_id: '6301' }))

    const { currentRole } = useReportFilter()

    expect(currentRole.value).toBe('regency_admin')
  })
})