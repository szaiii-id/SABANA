import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import RegistrationCitizenModal from '../RegistrationCitizenModal.vue'

vi.mock('@/composables/useRegistrationCitizen', () => ({
  useRegistrationCitizen: () => ({
    isSubmitting: { value: false },
    errorMessage: { value: '' },
    registerCitizen: vi.fn(),
  }),
}))

describe('RegistrationCitizenModal', () => {
  // =============================================
  // RENDER
  // =============================================
  it('merender modal saat open=true', () => {
    const wrapper = mount(RegistrationCitizenModal, {
      props: { open: true, submitting: false, error: '' },
    })
    expect(wrapper.text()).toContain('Daftarkan Warga Baru')
  })

  it('tidak merender saat open=false', () => {
    const wrapper = mount(RegistrationCitizenModal, {
      props: { open: false, submitting: false, error: '' },
    })
    expect(wrapper.find('h3').exists()).toBe(false)
  })

  // =============================================
  // NIK
  // =============================================
  it('validasi NIK wajib diisi', async () => {
    const wrapper = mount(RegistrationCitizenModal, {
      props: { open: true, submitting: false, error: '' },
    })
    const input = wrapper.findAll('input')[0]
    await input.setValue('')
    await input.trigger('blur')
    expect(wrapper.text()).toContain('NIK wajib diisi')
  })

  it('validasi NIK harus 16 digit', async () => {
    const wrapper = mount(RegistrationCitizenModal, {
      props: { open: true, submitting: false, error: '' },
    })
    const input = wrapper.findAll('input')[0]
    await input.setValue('63012345')
    await input.trigger('blur')
    expect(wrapper.text()).toContain('NIK harus 16 digit')
  })

  it('membersihkan karakter non-digit dari NIK', async () => {
    const wrapper = mount(RegistrationCitizenModal, {
      props: { open: true, submitting: false, error: '' },
    })
    const input = wrapper.findAll('input')[0]
    await input.setValue('6301 ABCD 5678 9012')
    expect((input.element as HTMLInputElement).value).toBe('630156789012')
  })

  // =============================================
  // NAMA LENGKAP
  // =============================================
  it('validasi nama lengkap wajib diisi', async () => {
    const wrapper = mount(RegistrationCitizenModal, {
      props: { open: true, submitting: false, error: '' },
    })
    const input = wrapper.findAll('input')[1]
    await input.setValue('')
    await input.trigger('blur')
    expect(wrapper.text()).toContain('Nama lengkap wajib diisi')
  })

  // =============================================
  // NO KK
  // =============================================
  it('validasi No KK wajib diisi', async () => {
    const wrapper = mount(RegistrationCitizenModal, {
      props: { open: true, submitting: false, error: '' },
    })
    const input = wrapper.findAll('input')[2]
    await input.setValue('')
    await input.trigger('blur')
    expect(wrapper.text()).toContain('No. KK wajib diisi')
  })

  // =============================================
  // WHATSAPP
  // =============================================
  it('validasi WA wajib diisi saat with_pin=true', async () => {
    const wrapper = mount(RegistrationCitizenModal, {
      props: { open: true, submitting: false, error: '' },
    })
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('6301234567890123')
    await inputs[1].setValue('Joko Widodo')
    await inputs[2].setValue('6301234567890123')

    const btn = wrapper.findAll('button').filter(b => b.text().includes('KIRIM PIN'))
    await btn[0].trigger('click')
    expect(wrapper.text()).toContain('No. WA wajib diisi')
  })

  // =============================================
  // SUBMIT
  // =============================================
  it('meng-emit submit dengan with_pin=true', async () => {
    const wrapper = mount(RegistrationCitizenModal, {
      props: { open: true, submitting: false, error: '' },
    })
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('6301234567890123')
    await inputs[1].setValue('Joko Widodo')
    await inputs[2].setValue('6301234567890123')
    await inputs[3].setValue('6281234567890')

    const btn = wrapper.findAll('button').filter(b => b.text().includes('KIRIM PIN'))
    await btn[0].trigger('click')

    expect(wrapper.emitted('submit')![0][0]).toMatchObject({
      nik: '6301234567890123',
      full_name: 'Joko Widodo',
      with_pin: true,
    })
  })

  it('meng-emit submit dengan with_pin=false', async () => {
    const wrapper = mount(RegistrationCitizenModal, {
      props: { open: true, submitting: false, error: '' },
    })
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('6301234567890123')
    await inputs[1].setValue('Joko Widodo')
    await inputs[2].setValue('6301234567890123')
    await inputs[3].setValue('6281234567890')

    const btn = wrapper.findAll('button').filter(
      b => b.text().includes('DAFTARKAN') && !b.text().includes('KIRIM PIN')
    )
    await btn[0].trigger('click')

    expect(wrapper.emitted('submit')![0][0]).toMatchObject({ with_pin: false })
  })

  // =============================================
  // ERROR
  // =============================================
  it('menampilkan pesan error dari server', () => {
    const wrapper = mount(RegistrationCitizenModal, {
      props: { open: true, submitting: false, error: 'NIK sudah terdaftar.' },
    })
    expect(wrapper.text()).toContain('NIK sudah terdaftar.')
  })

  // =============================================
  // CLOSE
  // =============================================
  it('meng-emit close saat tombol close diklik', async () => {
    const wrapper = mount(RegistrationCitizenModal, {
      props: { open: true, submitting: false, error: '' },
    })
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})