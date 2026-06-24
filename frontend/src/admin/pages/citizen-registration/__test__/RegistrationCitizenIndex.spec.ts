import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import RegistrationCitizenIndex from '../Index.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', name: 'admin.citizen-registration', component: {} as any }],
})

describe('RegistrationCitizenIndex', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('merender judul halaman', async () => {
    const wrapper = mount(RegistrationCitizenIndex, {
      global: { plugins: [router] },
    })
    await flushPromises()
    expect(wrapper.text()).toContain('Pendaftaran Warga')
  })

  it('membuka modal daftar saat tombol diklik', async () => {
    const wrapper = mount(RegistrationCitizenIndex, {
      global: { plugins: [router] },
    })
    await flushPromises()

    const btn = wrapper.findAll('button').filter(b => b.text().includes('Daftarkan Warga'))
    await btn[0].trigger('click')
    await wrapper.vm.$nextTick()

    expect(wrapper.html()).toContain('Daftarkan Warga Baru')
  })
})