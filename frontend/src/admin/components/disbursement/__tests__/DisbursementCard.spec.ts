import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import DisbursementCard from '../DisbursementCard.vue';

describe('DisbursementCard.vue', () => {
  const mockSubmission = {
    id: 'sub-1',
    registration_number: 'SBN-001',
    status: 'validated',
    citizen: { id: 'c1', full_name: 'Muhammad Noor', nik: '6301234567890123' },
    program: { id: 'p1', name: 'BLT', benefit_amount: 500000 },
    disbursement_method: 'village_cash',
  };

  function mountComponent(props: any = {}) {
    return mount(DisbursementCard, {
      props: {
        submission: mockSubmission,
        isSelected: false,
        isSubmitting: false,
        ...props,
      },
    });
  }

  it('test_menampilkan_nama_warga', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Muhammad Noor');
  });

  it('test_menampilkan_nik', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('6301234567890123');
  });

  it('test_menampilkan_nama_program', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('BLT');
  });

  it('test_menampilkan_nilai_bantuan', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Rp');
  });

  it('test_menampilkan_tombol_salurkan', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Salurkan');
  });

  it('test_emit_toggle_saat_klik_checkbox', async () => {
    const wrapper = mountComponent();
    const checkbox = wrapper.find('input[type="checkbox"]');
    await checkbox.setValue(true);
    expect(wrapper.emitted('toggle')).toBeTruthy();
  });

  it('test_emit_disburse_saat_klik_tombol', async () => {
    const wrapper = mountComponent();
    const button = wrapper.find('button');
    await button.trigger('click');
    expect(wrapper.emitted('disburse')).toBeTruthy();
  });

  it('test_menampilkan_loading_saat_submitting', () => {
    const wrapper = mountComponent({ isSubmitting: true });
    expect(wrapper.text()).toContain('...');
  });

  it('test_checkbox_checked_saat_selected', () => {
    const wrapper = mountComponent({ isSelected: true });
    const checkbox = wrapper.find('input[type="checkbox"]');
    expect((checkbox.element as HTMLInputElement).checked).toBe(true);
  });
});