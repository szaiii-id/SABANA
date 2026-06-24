import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import LogFilter from '../LogFilter.vue'

describe('LogFilter', () => {
  // =============================================
  // RENDER
  // =============================================
  it('merender semua filter', () => {
    const wrapper = mount(LogFilter)
    expect(wrapper.text()).toContain('Semua Modul')
    expect(wrapper.text()).toContain('Semua Role')
    expect(wrapper.find('input[type="date"]').exists()).toBe(true)
  })

  // =============================================
  // EMIT
  // =============================================
  it('meng-emit filter saat module diubah', async () => {
    const wrapper = mount(LogFilter)
    await wrapper.find('select').setValue('program')
    expect(wrapper.emitted('filter')).toBeTruthy()
    expect(wrapper.emitted('filter')![0][0]).toEqual({ module: 'program' })
  })

  it('meng-emit filter dengan debounce saat nama diketik', async () => {
    vi.useFakeTimers()
    const wrapper = mount(LogFilter)
    
    await wrapper.findAll('input')[0].setValue('Admin')
    vi.advanceTimersByTime(400)

    expect(wrapper.emitted('filter')).toBeTruthy()
    vi.useRealTimers()
  })
})