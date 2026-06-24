// ===== [IMPORTS] =====
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import StatCard from '../../../components/dashboard/StatCard.vue'

// ===== [TESTS] =====
describe('StatCard', () => {
  it('test_merender_title', () => {
    const wrapper = mount(StatCard, {
      props: { title: 'Total Penerima', value: 1234, subtitle: 'Penerima bantuan', icon: 'users', variant: 'primary' },
    })

    expect(wrapper.text()).toContain('Total Penerima')
  })

  it('test_merender_value', () => {
    const wrapper = mount(StatCard, {
      props: { title: 'Total', value: 999, subtitle: 'test', icon: 'users', variant: 'white' },
    })

    expect(wrapper.text()).toContain('999')
  })

  it('test_merender_subtitle', () => {
    const wrapper = mount(StatCard, {
      props: { title: 'Total', value: 999, subtitle: 'Penerima bantuan', icon: 'users', variant: 'primary' },
    })

    expect(wrapper.text()).toContain('Penerima bantuan')
  })

  it('test_variant_primary_class', () => {
    const wrapper = mount(StatCard, {
      props: { title: 'T', value: 1, subtitle: 'S', icon: 'users', variant: 'primary' },
    })

    expect(wrapper.find('.bg-gradient-to-br').exists()).toBe(true)
  })

  it('test_variant_white_class', () => {
    const wrapper = mount(StatCard, {
      props: { title: 'T', value: 1, subtitle: 'S', icon: 'users', variant: 'white' },
    })

    expect(wrapper.find('.bg-white').exists()).toBe(true)
  })
})