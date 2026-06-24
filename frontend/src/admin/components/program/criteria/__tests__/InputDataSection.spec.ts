// =============================================
// InputDataSection.spec.ts — FINAL
// =============================================

import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import InputDataSection from '../InputDataSection.vue';

function mountComponent(overrides: Record<string, unknown> = {}) {
  return mount(InputDataSection, {
    props: {
      existing: [],
      ...overrides,
    },
  });
}

describe('InputDataSection.vue', () => {

  // ===== RENDERING =====

  it('test_render_heading', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Form Input Data Warga');
  });

  it('test_render_tambah_field_form', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Tambah Field');
  });

  it('test_render_type_select_options', () => {
    const wrapper = mountComponent();
    const select = wrapper.find('select');
    expect(select.text()).toContain('Text');
    expect(select.text()).toContain('Number');
    expect(select.text()).toContain('Currency (Rp)');
    expect(select.text()).toContain('Select');
    expect(select.text()).toContain('Date');
    expect(select.text()).toContain('Text Area');
  });

  it('test_render_required_checkbox', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('input[type="checkbox"]').exists()).toBe(true);
  });

  // ===== HAPPY PATH =====

  it('test_add_text_input', async () => {
    const wrapper = mountComponent();
    const inputs = wrapper.findAll('input[type="text"]');
    await inputs[0].setValue('Nama Lengkap');

    const buttons = wrapper.findAll('button');
    const addButton = buttons.find(b => b.text().includes('Tambah'));
    await addButton?.trigger('click');

    expect(wrapper.text()).toContain('Nama Lengkap');
  });

  it('test_add_number_input_with_benefit', async () => {
    const wrapper = mountComponent();
    const inputs = wrapper.findAll('input[type="text"]');
    await inputs[0].setValue('Penghasilan');

    const selects = wrapper.findAll('select');
    await selects[0].setValue('number');

    const buttons = wrapper.findAll('button');
    const addButton = buttons.find(b => b.text().includes('Tambah'));
    await addButton?.trigger('click');

    expect(wrapper.text()).toContain('Penghasilan');
    expect(wrapper.text()).toContain('Benefit');
  });

  it('test_add_currency_input_with_cost', async () => {
    const wrapper = mountComponent();
    const inputs = wrapper.findAll('input[type="text"]');
    await inputs[0].setValue('Pengeluaran');

    const selects = wrapper.findAll('select');
    await selects[0].setValue('currency');

    const buttons = wrapper.findAll('button');
    const addButton = buttons.find(b => b.text().includes('Tambah'));
    await addButton?.trigger('click');

    expect(wrapper.text()).toContain('Pengeluaran');
    expect(wrapper.text()).toContain('Cost');
  });

  it('test_add_select_input', async () => {
    const wrapper = mountComponent();
    const inputs = wrapper.findAll('input[type="text"]');
    await inputs[0].setValue('Pendidikan');

    const selects = wrapper.findAll('select');
    await selects[0].setValue('select');

    const buttons = wrapper.findAll('button');
    const addButton = buttons.find(b => b.text().includes('Tambah'));
    await addButton?.trigger('click');

    expect(wrapper.text()).toContain('Pendidikan');
  });

  // ===== SAD PATH =====

  it('test_add_disabled_saat_label_kosong', () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const addButton = buttons.find(b => b.text().includes('Tambah'));
    expect(addButton?.attributes('disabled')).toBeDefined();
  });

  // ===== NULL / EMPTY =====

  it('test_existing_kosong_tampil_empty_state', () => {
    const wrapper = mountComponent({ existing: [] });
    expect(wrapper.text()).toContain('Belum ada field input');
  });

  it('test_existing_undefined_tampil_empty_state', () => {
    const wrapper = mountComponent({ existing: undefined });
    expect(wrapper.text()).toContain('Belum ada field input');
  });
});