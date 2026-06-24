import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'

vi.mock('../../../api/axios', () => ({
  default: {
    get: vi.fn(),
  },
}))

vi.mock('../../../api/registrationCitizenApi', () => ({
  registrationCitizenApi: {
    resetAndPrintPin: vi.fn(),
  },
}))

import RegistrationCitizenDetail from '../CitizenDetail.vue'
import adminApi from '../../../api/axios'
import { registrationCitizenApi } from '../../../api/registrationCitizenApi'

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', name: 'admin.citizen-registration', component: {} as any }],
})

const mockCitizen = {
  id: 'uuid-1', nik: '6301234567890123', full_name: 'Joko Widodo',
  family_card_number: '6301234567890123', whatsapp_number: '6281234567890',
  last_login_at: '2024-01-01 10:00:00', created_at: '2024-01-01',
}

const mockLogs = [
  { id: 'log-1', admin_name: 'Admin 1', admin_role: 'super_admin', action: 'register_with_pin', created_at: '2024-01-01' },
]

const mockSubmissions = [
  { id: 'sub-1', registration_number: 'SBN-ABC', status: 'completed', program: { name: 'Bantuan Beras' } },
]

describe('RegistrationCitizenDetail', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('menampilkan loading saat data dimuat', () => {
    ;(adminApi.get as any).mockReturnValue(new Promise(() => {}))
    const wrapper = mount(RegistrationCitizenDetail, {
      global: { plugins: [router] },
    })
    expect(wrapper.text()).toContain('Memuat data warga')
  })

  it('menampilkan data warga setelah load', async () => {
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: mockCitizen } })
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: mockLogs } })
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: mockSubmissions } })

    const wrapper = mount(RegistrationCitizenDetail, {
      global: { plugins: [router] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Joko Widodo')
    expect(wrapper.text()).toContain('6301234567890123')
  })

  it('menampilkan data tidak ditemukan', async () => {
    ;(adminApi.get as any).mockRejectedValue(new Error('Not found'))

    const wrapper = mount(RegistrationCitizenDetail, {
      global: { plugins: [router] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Data tidak ditemukan')
  })

  it('menampilkan riwayat pendaftaran', async () => {
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: mockCitizen } })
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: mockLogs } })
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: [] } })

    const wrapper = mount(RegistrationCitizenDetail, {
      global: { plugins: [router] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Riwayat Pendaftaran')
    expect(wrapper.text()).toContain('Admin 1')
  })

  it('menampilkan riwayat pengajuan', async () => {
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: mockCitizen } })
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: [] } })
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: mockSubmissions } })

    const wrapper = mount(RegistrationCitizenDetail, {
      global: { plugins: [router] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Riwayat Pengajuan')
    expect(wrapper.text()).toContain('SBN-ABC')
  })

  it('menampilkan pesan kosong jika belum ada pengajuan', async () => {
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: mockCitizen } })
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: [] } })
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: [] } })

    const wrapper = mount(RegistrationCitizenDetail, {
      global: { plugins: [router] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Belum ada pengajuan')
  })

  it('tidak menampilkan riwayat jika log kosong', async () => {
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: mockCitizen } })
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: [] } })
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: [] } })

    const wrapper = mount(RegistrationCitizenDetail, {
      global: { plugins: [router] },
    })
    await flushPromises()

    expect(wrapper.text()).not.toContain('Riwayat Pendaftaran')
  })

  it('menangani reset dan cetak PIN', async () => {
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: mockCitizen } })
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: [] } })
    ;(adminApi.get as any).mockResolvedValueOnce({ data: { data: [] } })

    const mockBlob = new Blob(['pdf'], { type: 'application/pdf' })
    ;(registrationCitizenApi.resetAndPrintPin as any).mockResolvedValue({ data: mockBlob })

    const wrapper = mount(RegistrationCitizenDetail, {
      global: { plugins: [router] },
    })
    await flushPromises()

    const btn = wrapper.findAll('button').filter(b => b.text().includes('Reset'))
    await btn[0].trigger('click')

    expect(registrationCitizenApi.resetAndPrintPin).toHaveBeenCalledWith('uuid-1')
  })
})