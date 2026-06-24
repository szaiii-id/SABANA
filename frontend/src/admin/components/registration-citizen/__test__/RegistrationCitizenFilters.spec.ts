import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import RegistrationCitizenFilters from '../RegistrationCitizenFilters.vue'

describe('RegistrationCitizenFilters', () => {
  // =============================================
  // RENDER
  // =============================================
  it('merender input dengan placeholder yang benar', () => {
    const wrapper = mount(RegistrationCitizenFilters, {
      props: { modelValue: { search: '' } },
    })
    expect(wrapper.find('input').attributes('placeholder')).toBe('Cari NIK atau Nama...')
  })

  // =============================================
  // V-MODEL / EMIT
  // =============================================
  it('meng-emit nilai saat user mengetik', async () => {
    const wrapper = mount(RegistrationCitizenFilters, {
      props: { modelValue: { search: '' } },
    })
    await wrapper.find('input').setValue('Joko')
    expect(wrapper.emitted('update:modelValue')![0][0]).toEqual({ search: 'Joko' })
  })

  it('menampilkan nilai search dari prop', () => {
    const wrapper = mount(RegistrationCitizenFilters, {
      props: { modelValue: { search: '630123' } },
    })
    expect((wrapper.find('input').element as HTMLInputElement).value).toBe('630123')
  })

  // =============================================
  // EDGE CASE
  // =============================================
  it('menangani input kosong', () => {
    const wrapper = mount(RegistrationCitizenFilters, {
      props: { modelValue: { search: '' } },
    })
    expect((wrapper.find('input').element as HTMLInputElement).value).toBe('')
  })

  it('menangani input dengan spasi', async () => {
    const wrapper = mount(RegistrationCitizenFilters, {
      props: { modelValue: { search: '' } },
    })
    await wrapper.find('input').setValue('  Joko  ')
    expect(wrapper.emitted('update:modelValue')![0][0]).toEqual({ search: '  Joko  ' })
  })
})