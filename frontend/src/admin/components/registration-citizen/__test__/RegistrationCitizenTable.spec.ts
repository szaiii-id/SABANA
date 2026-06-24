import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import RegistrationCitizenTable from '../RegistrationCitizenTable.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'admin.citizen-registration', component: {} as any },
    { path: '/detail/:id', name: 'admin.citizen-registration.detail', component: {} as any },
  ],
})

const mockCitizens = [
  {
    id: 'uuid-1',
    nik: '6301234567890123',
    full_name: 'Joko Widodo',
    family_card_number: '6301234567890123',
    whatsapp_number: '6281234567890',
    is_verified: true,
    last_login_at: '2024-01-01 10:00:00',
    created_at: '2024-01-01',
  },
  {
    id: 'uuid-2',
    nik: '6301234567890456',
    full_name: 'Budi Santoso',
    family_card_number: '6301234567890456',
    whatsapp_number: null,
    is_verified: false,
    last_login_at: null,
    created_at: '2024-02-01',
  },
]

describe('RegistrationCitizenTable', () => {
  // =============================================
  // RENDER DATA
  // =============================================
  it('menampilkan nama dan NIK warga', async () => {
    const wrapper = mount(RegistrationCitizenTable, {
      props: { citizens: mockCitizens, loading: false, total: 2, currentPage: 1, totalPages: 1 },
      global: { plugins: [router] },
    })
    await router.isReady()
    expect(wrapper.text()).toContain('Joko Widodo')
    expect(wrapper.text()).toContain('6301234567890123')
    expect(wrapper.text()).toContain('Budi Santoso')
  })

  it('menampilkan total warga', async () => {
    const wrapper = mount(RegistrationCitizenTable, {
      props: { citizens: mockCitizens, loading: false, total: 2, currentPage: 1, totalPages: 1 },
      global: { plugins: [router] },
    })
    await router.isReady()
    expect(wrapper.text()).toContain('2')
  })

  // =============================================
  // LOADING STATE
  // =============================================
  it('menampilkan skeleton saat loading', () => {
    const wrapper = mount(RegistrationCitizenTable, {
      props: { citizens: [], loading: true, total: 0, currentPage: 1, totalPages: 1 },
      global: { plugins: [router] },
    })
    expect(wrapper.find('.animate-pulse').exists()).toBe(true)
  })

  // =============================================
  // EMPTY STATE
  // =============================================
  it('menampilkan pesan kosong saat tidak ada data', () => {
    const wrapper = mount(RegistrationCitizenTable, {
      props: { citizens: [], loading: false, total: 0, currentPage: 1, totalPages: 1 },
      global: { plugins: [router] },
    })
    expect(wrapper.text()).toContain('Belum ada warga terdaftar')
  })

  // =============================================
  // AKSI EDIT
  // =============================================
  it('meng-emit event edit saat tombol edit diklik', async () => {
    const wrapper = mount(RegistrationCitizenTable, {
      props: { citizens: mockCitizens, loading: false, total: 2, currentPage: 1, totalPages: 1 },
      global: { plugins: [router] },
    })
    await router.isReady()
    const editBtn = wrapper.findAll('button').filter(b => b.text().includes('Edit'))
    await editBtn[0].trigger('click')
    expect(wrapper.emitted('edit')![0][0]).toEqual(mockCitizens[0])
  })

  // =============================================
  // KIRIM PIN
  // =============================================
  it('menampilkan modal konfirmasi saat klik Kirim PIN', async () => {
    const wrapper = mount(RegistrationCitizenTable, {
      props: { citizens: mockCitizens, loading: false, total: 2, currentPage: 1, totalPages: 1 },
      global: { plugins: [router] },
    })
    await router.isReady()
    const btn = wrapper.findAll('button').filter(b => b.text().includes('Kirim PIN'))
    await btn[0].trigger('click')
    expect(wrapper.text()).toContain('Kirim PIN Baru?')
  })

  it('tidak menampilkan tombol Kirim PIN jika WA kosong', async () => {
    const tanpaWA = [{ ...mockCitizens[0], whatsapp_number: null }]
    const wrapper = mount(RegistrationCitizenTable, {
      props: { citizens: tanpaWA, loading: false, total: 1, currentPage: 1, totalPages: 1 },
      global: { plugins: [router] },
    })
    await router.isReady()
    const btn = wrapper.findAll('button').filter(b => b.text().includes('Kirim PIN'))
    expect(btn.length).toBe(0)
  })

  // =============================================
  // PAGINATION
  // =============================================
  it('meng-emit pageChange saat klik Next', async () => {
    const wrapper = mount(RegistrationCitizenTable, {
      props: { citizens: mockCitizens, loading: false, total: 10, currentPage: 1, totalPages: 2 },
      global: { plugins: [router] },
    })
    await router.isReady()
    const nextBtn = wrapper.findAll('button').filter(b => b.text().includes('Next'))
    await nextBtn[0].trigger('click')
    expect(wrapper.emitted('pageChange')![0][0]).toBe(2)
  })

  it('tombol Prev disabled di halaman pertama', async () => {
    const wrapper = mount(RegistrationCitizenTable, {
      props: { citizens: mockCitizens, loading: false, total: 10, currentPage: 1, totalPages: 2 },
      global: { plugins: [router] },
    })
    await router.isReady()
    const prevBtn = wrapper.findAll('button').filter(b => b.text().includes('Prev'))
    expect(prevBtn[0].attributes('disabled')).toBeDefined()
  })
})