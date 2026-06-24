// ===== [IMPORTS] =====
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import DistrictDashboard from '../DistrictDashboard.vue'

// ===== [MOCKS] =====
const { mockGet } = vi.hoisted(() => ({
  mockGet: vi.fn(),
}))

vi.mock('axios', () => ({
  default: {
    create: () => ({
      get: mockGet,
      post: vi.fn(),
      put: vi.fn(),
      patch: vi.fn(),
      delete: vi.fn(),
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

// ===== [TESTS] =====
describe('DistrictDashboard', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
    localStorage.setItem('admin_user', JSON.stringify({ district_id: '6301010' }))
  })

  it('test_merender_judul_dashboard_kecamatan', async () => {
    mockGet.mockResolvedValue({ data: { data: {} } })

    const wrapper = mount(DistrictDashboard)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('Dashboard Kecamatan')
    })
  })

  it('test_menampilkan_loading_saat_pertama_kali', () => {
    mockGet.mockReturnValue(new Promise(() => {}))

    const wrapper = mount(DistrictDashboard)

    expect(wrapper.text()).toContain('Memuat data')
  })

  it('test_merender_stat_cards_dengan_data', async () => {
    let callCount = 0
    mockGet.mockImplementation(() => {
      callCount++
      if (callCount === 1) return Promise.resolve({ data: { data: { totalPenerima: 450, totalDanaTersalurkan: 270000000, antreanVerifikasi: 23, pengajuanBulanIni: 15 } } })
      return Promise.resolve({ data: { data: [] } })
    })

    const wrapper = mount(DistrictDashboard)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('450')
    })
  })

  it('test_tetap_render_dengan_data_kosong', async () => {
    let callCount = 0
    mockGet.mockImplementation(() => {
      callCount++
      if (callCount === 1) return Promise.resolve({ data: { data: null } })
      return Promise.resolve({ data: { data: [] } })
    })

    const wrapper = mount(DistrictDashboard)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('0')
    })
  })

  it('test_format_rupiah_menampilkan_benar', async () => {
    let callCount = 0
    mockGet.mockImplementation(() => {
      callCount++
      if (callCount === 1) return Promise.resolve({ data: { data: { totalPenerima: 1, totalDanaTersalurkan: 50000000, antreanVerifikasi: 0, pengajuanBulanIni: 0 } } })
      return Promise.resolve({ data: { data: [] } })
    })

    const wrapper = mount(DistrictDashboard)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('Rp 50 Jt')
    })
  })
})