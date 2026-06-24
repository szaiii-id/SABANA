import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import TabPenyaluran from '../TabPenyaluran.vue';

function mountComponent(props: Record<string, unknown> = {}) {
  return mount(TabPenyaluran, {
    props: {
      modelValue: 'village_cash',
      ...props,
    },
  });
}

describe('TabPenyaluran.vue', () => {

  // ===== HAPPY PATH =====

  it('test_menampilkan_opsi_tunai', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Tunai');
  });

  it('test_menampilkan_opsi_transfer', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Transfer');
  });

  // ===== BOUNDARY =====

  it('test_menampilkan_input_rekening_saat_transfer', () => {
    const wrapper = mountComponent({ modelValue: 'bpd_transfer' });
    expect(wrapper.find('input[type="text"]').exists()).toBe(true);
  });

  // ===== SAD PATH =====

  it('test_menampilkan_error_rekening', () => {
    const wrapper = mountComponent({ 
      modelValue: 'bpd_transfer', 
      bankAccountError: 'Nomor rekening wajib diisi' 
    });
    expect(wrapper.text()).toContain('Nomor rekening wajib diisi');
  });

  // ===== ACCESSIBILITY =====

  it('test_radio_memiliki_aria_label', () => {
    const wrapper = mountComponent();
    const radio = wrapper.find('input[type="radio"][aria-label="Tunai melalui balai desa"]');
    expect(radio.exists()).toBe(true);
  });

  it('test_radiogroup_memiliki_role', () => {
    const wrapper = mountComponent();
    const group = wrapper.find('[role="radiogroup"]');
    expect(group.exists()).toBe(true);
  });

  it('test_error_memiliki_role_alert', () => {
    const wrapper = mountComponent({ 
      modelValue: 'bpd_transfer', 
      bankAccountError: 'Error' 
    });
    const error = wrapper.find('[role="alert"]');
    expect(error.exists()).toBe(true);
  });
});