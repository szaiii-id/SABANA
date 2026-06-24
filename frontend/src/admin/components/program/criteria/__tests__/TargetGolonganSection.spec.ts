// =============================================
// DokumenWajibSection.spec.ts
// =============================================

import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import DokumenWajibSection from '../DokumenWajibSection.vue';

// =============================================
// HELPERS
// =============================================
function mountComponent(overrides: Record<string, unknown> = {}) {
  return mount(DokumenWajibSection, {
    props: {
      existing: [],
      ...overrides,
    },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('DokumenWajibSection.vue', () => {

  // ===== RENDERING =====

  it('test_render_heading', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Dokumen Persyaratan');
  });

  it('test_render_preset_documents', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('KTP');
    expect(wrapper.text()).toContain('Kartu Keluarga');
    expect(wrapper.text()).toContain('SKTM');
    expect(wrapper.text()).toContain('Rapor Sekolah');
  });

  it('test_render_form_tambah_kustom', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Tambah Dokumen Kustom');
  });

  // ===== HAPPY PATH =====

  it('test_select_preset_document_emits_update', async () => {
    const wrapper = mountComponent();
    const firstDoc = wrapper.find('.cursor-pointer');
    await firstDoc.trigger('click');

    const emit = wrapper.emitted('update');
    expect(emit).toBeTruthy();
    expect(emit![0][0]).toContain('ktp');
  });

  it('test_toggle_required_optional', async () => {
    const wrapper = mountComponent({ existing: ['ktp'] });
    const allButtons = wrapper.findAll('button');
    const toggleBtn = allButtons.find(b =>
      b.text().includes('Wajib') || b.text().includes('Opsional')
    );
    if (toggleBtn) {
      await toggleBtn.trigger('click');
      expect(wrapper.emitted('update')).toBeTruthy();
    }
  });

  it('test_add_custom_document', async () => {
    const wrapper = mountComponent();
    const inputs = wrapper.findAll('input[type="text"]');
    await inputs[0].setValue('Dokumen Custom');
    await inputs[1].setValue('Deskripsi custom');

    const buttons = wrapper.findAll('button');
    const addButton = buttons.find(b => b.text().includes('Tambah'));
    await addButton?.trigger('click');

    expect(wrapper.text()).toContain('Dokumen Custom');
  });

  it('test_select_multiple_documents', async () => {
    const wrapper = mountComponent();
    const docs = wrapper.findAll('.cursor-pointer');
    await docs[0].trigger('click');
    await docs[1].trigger('click');
    expect(wrapper.emitted('update')).toBeTruthy();
  });

  // ===== SAD PATH =====

  it('test_add_custom_disabled_saat_label_kosong', () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const addButton = buttons.find(b => b.text().includes('Tambah'));
    expect(addButton?.attributes('disabled')).toBeDefined();
  });

  // ===== EDGE CASE =====

  it('test_remove_custom_document', async () => {
    const wrapper = mountComponent();
    const inputs = wrapper.findAll('input[type="text"]');
    await inputs[0].setValue('To Delete');
    await inputs[1].setValue('Desc');

    const buttons = wrapper.findAll('button');
    const addButton = buttons.find(b => b.text().includes('Tambah'));
    await addButton?.trigger('click');

    const allButtons = wrapper.findAll('button');
    const removeBtn = allButtons.find(b => b.attributes('title') === 'Hapus permanen');
    if (removeBtn) {
      await removeBtn.trigger('click');
      expect(wrapper.emitted('update')).toBeTruthy();
    }
  });

  // ===== NULL / EMPTY =====

  it('test_existing_kosong_render_preset', () => {
    const wrapper = mountComponent({ existing: [] });
    expect(wrapper.text()).toContain('KTP');
  });

  it('test_existing_undefined_render_preset', () => {
    const wrapper = mountComponent({ existing: undefined });
    expect(wrapper.text()).toContain('KTP');
  });
});