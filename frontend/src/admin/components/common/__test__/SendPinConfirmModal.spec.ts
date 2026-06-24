import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import SendPinConfirmModal from '../SendPinConfirmModal.vue'

describe('SendPinConfirmModal', () => {
  // =============================================
  // RENDER
  // =============================================
  it('merender saat open=true', () => {
    const wrapper = mount(SendPinConfirmModal, {
      props: { open: true, citizenName: 'Joko Widodo', citizenWhatsapp: '6281234567890' },
    })
    expect(wrapper.text()).toContain('Kirim PIN Baru?')
    expect(wrapper.text()).toContain('Joko Widodo')
    expect(wrapper.text()).toContain('6281234567890')
  })

  it('tidak merender saat open=false', () => {
    const wrapper = mount(SendPinConfirmModal, {
      props: { open: false, citizenName: '', citizenWhatsapp: '' },
    })
    expect(wrapper.text()).not.toContain('Kirim PIN Baru?')
  })

  // =============================================
  // AKSI
  // =============================================
  it('meng-emit close saat klik Batal', async () => {
    const wrapper = mount(SendPinConfirmModal, {
      props: { open: true, citizenName: 'Joko', citizenWhatsapp: '62812' },
    })
    await wrapper.findAll('button')[0].trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('meng-emit confirm saat klik Ya, Kirim', async () => {
    const wrapper = mount(SendPinConfirmModal, {
      props: { open: true, citizenName: 'Joko', citizenWhatsapp: '62812' },
    })
    await wrapper.findAll('button')[1].trigger('click')
    expect(wrapper.emitted('confirm')).toBeTruthy()
  })
})