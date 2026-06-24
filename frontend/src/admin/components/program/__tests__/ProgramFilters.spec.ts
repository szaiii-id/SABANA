import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ProgramFilters from '../ProgramFilters.vue';

describe('ProgramFilters.vue', () => {
  function mountComponent(modelValue = { search: '', status: '' }) {
    return mount(ProgramFilters, {
      props: { modelValue },
    });
  }

  it('test_menampilkan_input_search', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('input').exists()).toBe(true);
  });

  it('test_menampilkan_select_status', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('select').exists()).toBe(true);
  });

  it('test_menampilkan_opsi_semua_status', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Semua Status');
  });

  it('test_menampilkan_opsi_draft', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Draft');
  });

  it('test_menampilkan_opsi_active', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Aktif');
  });

  it('test_menampilkan_opsi_closed', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Tertutup');
  });
});