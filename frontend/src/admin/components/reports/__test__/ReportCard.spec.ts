// ===== [IMPORTS] =====
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ReportCard from '../../../components/reports/ReportCard.vue'

// ===== [HELPERS] =====
function validReport() {
  return {
    id: 'budget-summary',
    title: 'Ringkasan Anggaran Program',
    description: 'Total anggaran, tersalurkan, dan sisa per program',
    endpoint: '/reports/budget-summary',
  }
}

// ===== [TESTS] =====
describe('ReportCard', () => {
  it('test_merender_judul_laporan', () => {
    const wrapper = mount(ReportCard, {
      props: { report: validReport(), isDownloading: false },
    })

    expect(wrapper.text()).toContain('Ringkasan Anggaran Program')
  })

  it('test_merender_deskripsi_laporan', () => {
    const wrapper = mount(ReportCard, {
      props: { report: validReport(), isDownloading: false },
    })

    expect(wrapper.text()).toContain('Total anggaran')
  })

  it('test_tombol_disabled_saat_downloading', () => {
    const wrapper = mount(ReportCard, {
      props: { report: validReport(), isDownloading: true },
    })

    const button = wrapper.find('button')
    expect(button.attributes('disabled')).toBeDefined()
  })

  it('test_emit_event_download_saat_klik', async () => {
    const wrapper = mount(ReportCard, {
      props: { report: validReport(), isDownloading: false },
    })

    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('download')).toBeTruthy()
  })

  it('test_render_dengan_deskripsi_kosong', () => {
    const wrapper = mount(ReportCard, {
      props: { report: { id: 'test', title: 'Test', description: '', endpoint: '/test' }, isDownloading: false },
    })

    expect(wrapper.text()).toContain('Test')
  })
})