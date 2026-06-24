// ===== [IMPORTS] =====
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import VillageDashboard from '../VillageDashboard.vue'

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
describe('VillageDashboard', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
    localStorage.setItem('admin_user', JSON.stringify({ village_id: '6301010001' }))
  })

  it('test_merender_judul_dashboard_desa', async () => {
    mockGet.mockResolvedValue({ data: { data: {} } })

    const wrapper = mount(VillageDashboard)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('Dashboard Desa')
    })
  })

  it('test_menampilkan_loading_saat_pertama_kali', () => {
    mockGet.mockReturnValue(new Promise(() => {}))

    const wrapper = mount(VillageDashboard)

    expect(wrapper.text()).toContain('Memuat data')
  })

  it('test_merender_stat_cards_dengan_data', async () => {
    let callCount = 0
    mockGet.mockImplementation(() => {
      callCount++
      if (callCount === 1) return Promise.resolve({ data: { data: { totalWarga: 245, pengajuanBulanIni: 12, disetujui: 198, disalurkan: 185 } } })
      return Promise.resolve({ data: { data: [] } })
    })

    const wrapper = mount(VillageDashboard)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('245')
    })
  })

  it('test_tetap_render_dengan_data_kosong', async () => {
    let callCount = 0
    mockGet.mockImplementation(() => {
      callCount++
      if (callCount === 1) return Promise.resolve({ data: { data: null } })
      return Promise.resolve({ data: { data: [] } })
    })

    const wrapper = mount(VillageDashboard)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('0')
    })
  })

  it('test_menampilkan_pesan_kosong_jika_tidak_ada_warga', async () => {
    mockGet.mockResolvedValue({ data: { data: [] } })

    const wrapper = mount(VillageDashboard)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('Belum ada warga terdaftar')
    })
  })
})