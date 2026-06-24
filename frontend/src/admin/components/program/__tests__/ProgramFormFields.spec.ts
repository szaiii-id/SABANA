// =============================================
// ProgramFormFields.spec.ts
// =============================================

import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ProgramFormFields from '../../../components/program/ProgramFormFields.vue';
import type { ProgramPayload } from '../../../types/program';

// =============================================
// HELPERS
// =============================================
const validForm: ProgramPayload = {
  name: 'BLT Dana Desa',
  description: 'Bantuan Langsung Tunai',
  start_date: '2026-01-01',
  end_date: '2026-12-31',
  status: 'draft',
  quota_total: null,
  benefit_amount: null,
  criteria: {},
  banner: null,
  banner_url: null,
};

function mountComponent(overrides: Record<string, unknown> = {}) {
  return mount(ProgramFormFields, {
    props: {
      form: { ...validForm, ...overrides.form as Partial<ProgramPayload> || {} },
      mode: 'add',
      errors: {},
      existingBannerUrl: null,
      ...overrides,
    },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('ProgramFormFields.vue', () => {

  // ===== RENDERING =====

  it('test_render_name_input', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('input[type="text"]').exists()).toBe(true);
  });

  it('test_render_status_select', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('select').exists()).toBe(true);
  });

  it('test_render_quota_input', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('input[type="number"]').exists()).toBe(true);
  });

  it('test_render_description_textarea', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('textarea').exists()).toBe(true);
  });

  it('test_render_date_inputs', () => {
    const wrapper = mountComponent();
    const dateInputs = wrapper.findAll('input[type="date"]');
    expect(dateInputs.length).toBe(2);
  });

  it('test_render_banner_input', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('input[type="file"]').exists()).toBe(true);
  });

  // ===== V-MODEL =====

  it('test_v_model_name', async () => {
    const wrapper = mountComponent();
    const input = wrapper.find('input[type="text"]');
    await input.setValue('Program Baru');
    expect((input.element as HTMLInputElement).value).toBe('Program Baru');
  });

  // ===== VALIDATION ERRORS =====

  it('test_show_name_error', () => {
    const wrapper = mountComponent({
      errors: { name: 'Nama program wajib diisi.' }
    });
    expect(wrapper.text()).toContain('Nama program wajib diisi.');
  });

  it('test_show_description_error', () => {
    const wrapper = mountComponent({
      errors: { description: 'Deskripsi wajib diisi.' }
    });
    expect(wrapper.text()).toContain('Deskripsi wajib diisi.');
  });

  it('test_show_start_date_error', () => {
    const wrapper = mountComponent({
      errors: { start_date: 'Tanggal mulai wajib diisi.' }
    });
    expect(wrapper.text()).toContain('Tanggal mulai wajib diisi.');
  });

  it('test_show_end_date_error', () => {
    const wrapper = mountComponent({
      errors: { end_date: 'Tanggal selesai harus setelah tanggal mulai.' }
    });
    expect(wrapper.text()).toContain('Tanggal selesai harus setelah tanggal mulai.');
  });

  it('test_error_input_has_red_border', () => {
    const wrapper = mountComponent({
      errors: { name: 'Error' }
    });
    const input = wrapper.find('input[type="text"]');
    expect(input.classes()).toContain('border-[#FCA5A5]');
  });

  it('test_clear_error_on_input', async () => {
    const wrapper = mountComponent({
      errors: { name: 'Error' }
    });
    const input = wrapper.find('input[type="text"]');
    await input.trigger('input');
    expect(wrapper.text()).not.toContain('Error');
  });

  // ===== MODE =====

  it('test_add_mode_form_terisi_default', () => {
    const wrapper = mountComponent({ mode: 'add' });
    const nameInput = wrapper.find('input[type="text"]');
    expect((nameInput.element as HTMLInputElement).value).toBe('BLT Dana Desa');
  });

  it('test_edit_mode_shows_existing_banner', () => {
    const wrapper = mountComponent({
      mode: 'edit',
      existingBannerUrl: 'https://example.com/banner.jpg'
    });
    expect(wrapper.text()).toContain('Banner Saat Ini');
  });

  // ===== LABELS =====

  it('test_render_all_labels', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Nama Program');
    expect(wrapper.text()).toContain('Status');
    expect(wrapper.text()).toContain('Deskripsi');
    expect(wrapper.text()).toContain('Tanggal Mulai');
    expect(wrapper.text()).toContain('Tanggal Selesai');
    expect(wrapper.text()).toContain('Banner Program');
  });

  it('test_render_banner_format_info', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Format JPG/PNG, maksimal 2MB');
  });
});