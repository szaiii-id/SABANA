// ===== [IMPORTS] =====
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import RegencyDashboard from '../RegencyDashboard.vue'

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
describe('RegencyDashboard', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
    localStorage.setItem('admin_user', JSON.stringify({ regency_id: '6301' }))
  })

  it('test_merender_judul_dashboard_kabupaten', async () => {
    mockGet.mockResolvedValue({ data: { data: {} } })

    const wrapper = mount(RegencyDashboard)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('Dashboard Kabupaten')
    })
  })

  it('test_menampilkan_loading_saat_pertama_kali', () => {
    mockGet.mockReturnValue(new Promise(() => {}))

    const wrapper = mount(RegencyDashboard)

    expect(wrapper.text()).toContain('Memuat data')
  })

  it('test_merender_stat_cards_dengan_data', async () => {
    let callCount = 0
    mockGet.mockImplementation(() => {
      callCount++
      if (callCount === 1) return Promise.resolve({ data: { data: { totalPenerima: 1234, totalDanaTersalurkan: 740400000, programAktif: 5, antreanVerifikasi: 56 } } })
      return Promise.resolve({ data: { data: [] } })
    })

    const wrapper = mount(RegencyDashboard)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('1234')
    })
  })

  it('test_tetap_render_dengan_data_kosong', async () => {
    let callCount = 0
    mockGet.mockImplementation(() => {
      callCount++
      if (callCount === 1) return Promise.resolve({ data: { data: null } })
      return Promise.resolve({ data: { data: [] } })
    })

    const wrapper = mount(RegencyDashboard)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('0')
    })
  })

  it('test_format_rupiah_menampilkan_benar', async () => {
    let callCount = 0
    mockGet.mockImplementation(() => {
      callCount++
      if (callCount === 1) return Promise.resolve({ data: { data: { totalPenerima: 1, totalDanaTersalurkan: 1000000, programAktif: 1, antreanVerifikasi: 0 } } })
      return Promise.resolve({ data: { data: [] } })
    })

    const wrapper = mount(RegencyDashboard)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('Rp 1 Jt')
    })
  })
})