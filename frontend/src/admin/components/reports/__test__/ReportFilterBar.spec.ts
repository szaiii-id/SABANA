// ===== [IMPORTS] =====
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ReportFilterBar from '../ReportFilterBar.vue'

// ===== [HELPERS] =====
function mountComponent(props = {}) {
  return mount(ReportFilterBar, {
    props: {
      filters: { tgl_mulai: '', tgl_akhir: '', program_id: '', wilayah_id: '' },
      programs: [],
      regencies: [],
      districts: [],
      villages: [],
      selectedRegencyId: '',
      selectedDistrictId: '',
      currentRole: 'super_admin',
      hasFilter: false,
      filterLabel: 'Semua Data',
      ...props,
    },
  })
}

// ===== [TESTS] =====
describe('ReportFilterBar', () => {
  it('test_menampilkan_input_tanggal_mulai', () => {
    const wrapper = mountComponent()
    const inputs = wrapper.findAll('input[type="date"]')
    expect(inputs.length).toBeGreaterThanOrEqual(1)
  })

  it('test_menampilkan_dropdown_program', () => {
    const wrapper = mountComponent({
      programs: [{ id: '1', name: 'PKH' }],
    })
    expect(wrapper.text()).toContain('Semua Program')
    expect(wrapper.text()).toContain('PKH')
  })

  it('test_menampilkan_dropdown_kabupaten_untuk_super_admin', () => {
    const wrapper = mountComponent({
      currentRole: 'super_admin',
      regencies: [{ id: '63', name: 'Banjar' }],
    })
    expect(wrapper.text()).toContain('Semua Kabupaten')
  })

  it('test_tidak_menampilkan_dropdown_kabupaten_untuk_village_officer', () => {
    const wrapper = mountComponent({
      currentRole: 'village_officer',
    })
    expect(wrapper.text()).not.toContain('Semua Kabupaten')
  })

  it('test_menampilkan_label_filter_aktif', () => {
    const wrapper = mountComponent({
      hasFilter: true,
      filterLabel: 'Program: PKH',
    })
    expect(wrapper.text()).toContain('Program: PKH')
  })

  it('test_tombol_reset_muncul_saat_ada_filter', () => {
    const wrapper = mountComponent({
      hasFilter: true,
    })
    expect(wrapper.text()).toContain('Reset')
  })
})