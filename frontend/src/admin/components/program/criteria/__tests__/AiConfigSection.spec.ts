// =============================================
// AiConfigSection.spec.ts
// =============================================

import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AiConfigSection from '../AiConfigSection.vue';

// =============================================
// HELPERS
// =============================================
function mountComponent(overrides: Record<string, unknown> = {}) {
  return mount(AiConfigSection, {
    props: {
      documents: ['ktp', 'kk'],
      existing: {},
      ...overrides,
    },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('AiConfigSection.vue', () => {

  // ===== RENDERING =====

  it('test_render_heading', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Konfigurasi AI per Dokumen');
  });

  it('test_render_document_list', () => {
    const wrapper = mountComponent({ documents: ['ktp', 'kk'] });
    expect(wrapper.text()).toContain('KTP');
    expect(wrapper.text()).toContain('Kartu Keluarga');
  });

  it('test_render_ocr_checkbox', () => {
    const wrapper = mountComponent({ documents: ['ktp'] });
    expect(wrapper.text()).toContain('OCR');
  });

  it('test_render_nlp_checkbox', () => {
    const wrapper = mountComponent({ documents: ['ktp'] });
    expect(wrapper.text()).toContain('NLP');
  });

  it('test_render_grid_layout', () => {
    const wrapper = mountComponent({ documents: ['ktp', 'kk'] });
    expect(wrapper.find('.grid').exists()).toBe(true);
  });

  // ===== HAPPY PATH =====

  it('test_default_ocr_enabled_for_ktp', () => {
    const wrapper = mountComponent({ documents: ['ktp'], existing: {} });
    const ocrCheckbox = wrapper.find('input[type="checkbox"]');
    expect((ocrCheckbox.element as HTMLInputElement).checked).toBe(true);
  });

  it('test_default_ocr_disabled_for_foto_rumah', () => {
    const wrapper = mountComponent({ documents: ['foto_rumah_depan'], existing: {} });
    const ocrCheckbox = wrapper.find('input[type="checkbox"]');
    expect((ocrCheckbox.element as HTMLInputElement).checked).toBe(false);
  });

  it('test_default_nlp_enabled_for_ktp', () => {
    const wrapper = mountComponent({ documents: ['ktp'], existing: {} });
    const checkboxes = wrapper.findAll('input[type="checkbox"]');
    const nlpCheckbox = checkboxes[1];
    expect((nlpCheckbox.element as HTMLInputElement).checked).toBe(true);
  });

  it('test_toggle_ocr_emits_update', async () => {
    const wrapper = mountComponent({ documents: ['ktp'], existing: {} });
    const ocrCheckbox = wrapper.find('input[type="checkbox"]');
    await ocrCheckbox.setValue(false);
    expect(wrapper.emitted('update')).toBeTruthy();
  });

  it('test_toggle_nlp_emits_update', async () => {
    const wrapper = mountComponent({ documents: ['ktp'], existing: {} });
    const checkboxes = wrapper.findAll('input[type="checkbox"]');
    const nlpCheckbox = checkboxes[1];
    await nlpCheckbox.setValue(false);
    expect(wrapper.emitted('update')).toBeTruthy();
  });

  // ===== SAD PATH =====

  it('test_empty_documents_shows_message', () => {
    const wrapper = mountComponent({ documents: [] });
    expect(wrapper.text()).toContain('Pilih dokumen terlebih dahulu');
  });

  // ===== EDGE CASE =====

  it('test_custom_document_label', () => {
    const wrapper = mountComponent({
      documents: [{ key: 'custom_doc', label: 'Dokumen Custom', active: true }]
    });
    expect(wrapper.text()).toContain('Dokumen Custom');
  });

  it('test_document_with_active_false_not_rendered', () => {
    const wrapper = mountComponent({
      documents: [{ key: 'hidden_doc', label: 'Hidden', active: false }]
    });
    expect(wrapper.text()).not.toContain('Hidden');
  });

  it('test_mixed_string_and_object_documents', () => {
    const wrapper = mountComponent({
      documents: [
        'ktp',
        { key: 'custom_1', label: 'Custom Doc', active: true }
      ]
    });
    expect(wrapper.text()).toContain('KTP');
    expect(wrapper.text()).toContain('Custom Doc');
  });

  // ===== DATA TYPE =====

  it('test_existing_config_preserved', () => {
    const wrapper = mountComponent({
      documents: ['ktp'],
      existing: { ktp: { ocr: false, nlp_match: false } }
    });
    const checkboxes = wrapper.findAll('input[type="checkbox"]');
    expect((checkboxes[0].element as HTMLInputElement).checked).toBe(false);
    expect((checkboxes[1].element as HTMLInputElement).checked).toBe(false);
  });

  // ===== RENDERING =====

  it('test_render_checkbox_per_document', () => {
    const wrapper = mountComponent({ documents: ['ktp', 'kk'] });
    const checkboxes = wrapper.findAll('input[type="checkbox"]');
    expect(checkboxes.length).toBe(4);
  });
});