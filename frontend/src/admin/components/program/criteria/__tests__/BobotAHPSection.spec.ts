import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import BobotAHPSection from '../SmartWeightSection.vue';

describe('BobotAHPSection.vue', () => {
  const inputs = [
    { key: 'penghasilan', label: 'Penghasilan', type: 'currency', sifat: 'cost' },
    { key: 'tanggungan', label: 'Tanggungan', type: 'number', sifat: 'benefit' },
    { key: 'nama', label: 'Nama', type: 'text', sifat: 'none' },
  ];

  function mountComponent(props: any = {}) {
    return mount(BobotAHPSection, {
      props: { inputs, existing: {}, ...props },
    });
  }

  it('test_menampilkan_heading_bobot_prioritas', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Bobot Prioritas');
  });

  it('test_menampilkan_benefit_input_saja', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Penghasilan');
    expect(wrapper.text()).toContain('Tanggungan');
  });

  it('test_menampilkan_tombol_reset', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Reset Semua Bobot');
  });
});