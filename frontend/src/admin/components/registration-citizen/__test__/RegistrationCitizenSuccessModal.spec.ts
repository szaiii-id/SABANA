import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import RegistrationCitizenSuccessModal from '../RegistrationCitizenSuccessModal.vue'

const mockCitizen = {
  nik: '6301234567890123',
  full_name: 'Joko Widodo',
  family_card_number: '6301234567890123',
  whatsapp_number: '6281234567890',
}

describe('RegistrationCitizenSuccessModal', () => {
  // =============================================
  // RENDER
  // =============================================
  it('merender saat open=true', () => {
    const wrapper = mount(RegistrationCitizenSuccessModal, {
      props: { open: true, citizen: mockCitizen, accessPin: '123456' },
    })
    expect(wrapper.text()).toContain('Warga Berhasil Didaftarkan')
  })

  it('tidak merender saat open=false', () => {
    const wrapper = mount(RegistrationCitizenSuccessModal, {
      props: { open: false, citizen: null, accessPin: '' },
    })
    expect(wrapper.find('h3').exists()).toBe(false)
  })

  // =============================================
  // DATA
  // =============================================
  it('menampilkan NIK dan nama warga', () => {
    const wrapper = mount(RegistrationCitizenSuccessModal, {
      props: { open: true, citizen: mockCitizen, accessPin: '987654' },
    })
    expect(wrapper.text()).toContain('6301234567890123')
    expect(wrapper.text()).toContain('Joko Widodo')
  })

  // =============================================
  // NULL / EMPTY
  // =============================================
  it('menangani whatsapp null', () => {
    const wrapper = mount(RegistrationCitizenSuccessModal, {
      props: { open: true, citizen: { ...mockCitizen, whatsapp_number: null }, accessPin: '123456' },
    })
    expect(wrapper.text()).toContain('Warga Berhasil Didaftarkan')
  })

  // =============================================
  // CLOSE
  // =============================================
  it('meng-emit close saat tombol diklik', async () => {
    const wrapper = mount(RegistrationCitizenSuccessModal, {
      props: { open: true, citizen: mockCitizen, accessPin: '123456' },
    })
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})