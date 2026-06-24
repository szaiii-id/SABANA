import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import RegistrationCitizenEditModal from '../RegistrationCitizenEditModal.vue'

const mockCitizen = {
  id: 'uuid-1',
  nik: '6301234567890123',
  full_name: 'Joko Widodo',
  family_card_number: '6301234567890123',
  whatsapp_number: '6281234567890',
}

describe('RegistrationCitizenEditModal', () => {
  it('merender dengan data warga saat open=true', async () => {
    const wrapper = mount(RegistrationCitizenEditModal, {
      props: { open: false, submitting: false, error: '', citizen: null },
    })

    await wrapper.setProps({ open: true, citizen: mockCitizen })
    await nextTick()
    await nextTick()

    const inputs = wrapper.findAll('input')
    expect(inputs[0].element.value).toBe('6301234567890123')
    expect(inputs[1].element.value).toBe('Joko Widodo')
  })

  it('tidak merender saat open=false', () => {
    const wrapper = mount(RegistrationCitizenEditModal, {
      props: { open: false, submitting: false, error: '', citizen: null },
    })
    expect(wrapper.find('h3').exists()).toBe(false)
  })

  it('validasi NIK wajib diisi', async () => {
    const wrapper = mount(RegistrationCitizenEditModal, {
      props: { open: false, submitting: false, error: '', citizen: null },
    })
    await wrapper.setProps({ open: true, citizen: mockCitizen })
    await nextTick()

    const input = wrapper.findAll('input')[0]
    await input.setValue('')
    await input.trigger('blur')
    expect(wrapper.text()).toContain('NIK wajib diisi')
  })

  it('validasi NIK harus 16 digit', async () => {
    const wrapper = mount(RegistrationCitizenEditModal, {
      props: { open: false, submitting: false, error: '', citizen: null },
    })
    await wrapper.setProps({ open: true, citizen: mockCitizen })
    await nextTick()

    const input = wrapper.findAll('input')[0]
    await input.setValue('12345')
    await input.trigger('blur')
    expect(wrapper.text()).toContain('NIK harus 16 digit')
  })

  it('validasi nama lengkap wajib diisi', async () => {
    const wrapper = mount(RegistrationCitizenEditModal, {
      props: { open: false, submitting: false, error: '', citizen: null },
    })
    await wrapper.setProps({ open: true, citizen: mockCitizen })
    await nextTick()

    const input = wrapper.findAll('input')[1]
    await input.setValue('')
    await input.trigger('blur')
    expect(wrapper.text()).toContain('Nama lengkap wajib diisi')
  })

  it('validasi No KK wajib diisi', async () => {
    const wrapper = mount(RegistrationCitizenEditModal, {
      props: { open: false, submitting: false, error: '', citizen: null },
    })
    await wrapper.setProps({ open: true, citizen: mockCitizen })
    await nextTick()

    const input = wrapper.findAll('input')[2]
    await input.setValue('')
    await input.trigger('blur')
    expect(wrapper.text()).toContain('No. KK wajib diisi')
  })

  it('meng-emit submit dengan data yang diubah', async () => {
    const wrapper = mount(RegistrationCitizenEditModal, {
      props: { open: false, submitting: false, error: '', citizen: null },
    })
    await wrapper.setProps({ open: true, citizen: mockCitizen })
    await nextTick()

    await wrapper.findAll('input')[1].setValue('Nama Baru')
    await wrapper.find('form').trigger('submit')

    expect(wrapper.emitted('submit')).toBeTruthy()
  })

  it('menampilkan pesan error', () => {
    const wrapper = mount(RegistrationCitizenEditModal, {
      props: { open: true, submitting: false, error: 'NIK sudah digunakan.', citizen: mockCitizen },
    })
    expect(wrapper.text()).toContain('NIK sudah digunakan.')
  })

  it('meng-emit close saat klik Batal', async () => {
    const wrapper = mount(RegistrationCitizenEditModal, {
      props: { open: false, submitting: false, error: '', citizen: null },
    })
    await wrapper.setProps({ open: true, citizen: mockCitizen })
    await nextTick()

    const btn = wrapper.findAll('button').filter(b => b.text().includes('Batal'))
    await btn[0].trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})