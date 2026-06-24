// =============================================
// TabDataDiri.spec.ts — FULL CODE
// =============================================

import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import TabDataDiri from '../TabDataDiri.vue';
import type { FormInputSchema } from '../../../../types/assistance';

const inputs: FormInputSchema[] = [
  { key: 'pekerjaan', label: 'Pekerjaan', type: 'text' },
  { key: 'penghasilan', label: 'Penghasilan', type: 'currency' },
  { key: 'tanggal_lahir', label: 'Tanggal Lahir', type: 'date' },
  { key: 'catatan', label: 'Catatan', type: 'textarea' },
  { key: 'ipk', label: 'IPK', type: 'decimal' },
];

function mountComponent(props: Record<string, unknown> = {}) {
  return mount(TabDataDiri, {
    props: {
      inputs,
      modelValue: { pekerjaan: '', penghasilan: '', tanggal_lahir: '', catatan: '', ipk: '' },
      errors: {},
      revisionItems: [],
      ...props,
    },
  });
}

describe('TabDataDiri.vue', () => {

  // ===== HAPPY PATH =====

  it('test_menampilkan_semua_input', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Pekerjaan');
    expect(wrapper.text()).toContain('Penghasilan');
  });

  it('test_emit_update_modelValue', async () => {
    const wrapper = mountComponent();
    const input = wrapper.find('input[type="text"]');
    await input.setValue('Petani');
    expect(wrapper.emitted('update:modelValue')).toBeTruthy();
  });

  // ===== DATA TYPE — DATE =====

  it('test_menampilkan_input_date', () => {
    const wrapper = mountComponent();
    const dateInput = wrapper.find('input[type="date"]');
    expect(dateInput.exists()).toBe(true);
  });

  it('test_emit_update_date', async () => {
    const wrapper = mountComponent();
    const dateInput = wrapper.find('input[type="date"]');
    await dateInput.setValue('2024-06-15');
    expect(wrapper.emitted('update:modelValue')).toBeTruthy();
  });

  // ===== DATA TYPE — TEXTAREA =====

  it('test_menampilkan_textarea', () => {
    const wrapper = mountComponent();
    const textarea = wrapper.find('textarea');
    expect(textarea.exists()).toBe(true);
  });

  it('test_emit_update_textarea', async () => {
    const wrapper = mountComponent();
    const textarea = wrapper.find('textarea');
    await textarea.setValue('Catatan penting');
    expect(wrapper.emitted('update:modelValue')).toBeTruthy();
  });

  // ===== DATA TYPE — DECIMAL =====

  it('test_menampilkan_input_decimal', () => {
    const wrapper = mountComponent();
    const decimalInput = wrapper.find('input[type="number"][step="0.01"]');
    expect(decimalInput.exists()).toBe(true);
  });

  // ===== SAD PATH =====

  it('test_menampilkan_error_field', () => {
    const wrapper = mountComponent({ errors: { pekerjaan: 'Pekerjaan wajib diisi' } });
    expect(wrapper.text()).toContain('Pekerjaan wajib diisi');
  });

  // ===== DATA TYPE =====

  it('test_menampilkan_revision_badge', () => {
    const wrapper = mountComponent({ revisionItems: ['pekerjaan'] });
    expect(wrapper.text()).toContain('⚠');
  });

  // ===== ACCESSIBILITY =====

  it('test_input_memiliki_aria_label', () => {
    const wrapper = mountComponent();
    const input = wrapper.find('input[type="text"]');
    expect(input.attributes('aria-label')).toBe('Pekerjaan');
  });

  it('test_error_memiliki_role_alert', () => {
    const wrapper = mountComponent({ errors: { pekerjaan: 'Error' } });
    const error = wrapper.find('[role="alert"]');
    expect(error.exists()).toBe(true);
  });
});