import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import VerificationFilters from '../VerificationFilters.vue';

describe('VerificationFilters.vue', () => {
  function mountComponent(modelValue = { search: '', status: '' }) {
    return mount(VerificationFilters, {
      props: { modelValue },
    });
  }

  it('test_menampilkan_input_search', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('input[type="text"]').exists()).toBe(true);
  });

  it('test_menampilkan_select_status', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('select').exists()).toBe(true);
  });

  it('test_menampilkan_opsi_semua_status', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Semua Status');
  });

  it('test_menampilkan_opsi_pending', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Pending');
  });

  it('test_menampilkan_opsi_disetujui', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Disetujui');
  });

  it('test_menampilkan_opsi_ditolak', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Ditolak');
  });

  it('test_menampilkan_opsi_perlu_revisi', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Perlu Revisi');
  });

  it('test_menampilkan_opsi_selesai', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Selesai');
  });
});