import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ProgramModal from '../ProgramModal.vue';

describe('ProgramModal.vue', () => {
  function mountComponent(props: any = {}) {
    return mount(ProgramModal, {
      props: {
        open: true,
        mode: 'add',
        submitting: false,
        error: '',
        uploadStep: 'data',
        uploadProgress: 0,
        ...props,
      },
      global: {
        stubs: {
          UploadProgress: { template: '<div class="upload-progress"/>' },
        },
      },
    });
  }

  // =============================================
  // RENDERING
  // =============================================

  it('test_menampilkan_heading_tambah_program', () => {
    const wrapper = mountComponent({ mode: 'add' });
    expect(wrapper.text()).toContain('Tambah Program');
  });

  it('test_menampilkan_heading_edit_program', () => {
    const wrapper = mountComponent({ mode: 'edit' });
    expect(wrapper.text()).toContain('Edit Program');
  });

  it('test_menampilkan_tombol_batal', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Batal');
  });

  it('test_menampilkan_tombol_simpan', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Simpan Program');
  });

  // =============================================
  // MODE
  // =============================================

  it('test_mode_add_menampilkan_teks_tambah', () => {
    const wrapper = mountComponent({ mode: 'add' });
    expect(wrapper.text()).toContain('Tambah Program');
  });

  it('test_mode_edit_menampilkan_teks_edit', () => {
    const wrapper = mountComponent({ mode: 'edit' });
    expect(wrapper.text()).toContain('Edit Program');
  });

  // =============================================
  // ERROR
  // =============================================

  it('test_menampilkan_error_message', () => {
    const wrapper = mountComponent({ error: 'Gagal menyimpan' });
    expect(wrapper.text()).toContain('Gagal menyimpan');
  });

  // =============================================
  // SUBMITTING
  // =============================================

  it('test_menampilkan_upload_progress_saat_submitting', () => {
    const wrapper = mountComponent({ submitting: true, uploadProgress: 50 });
    expect(wrapper.html()).toContain('upload-progress');
  });

  it('test_tombol_disabled_saat_submitting', () => {
    const wrapper = mountComponent({ submitting: true });
    const submitBtn = wrapper.find('button:not([type="button"])');
    expect(submitBtn.attributes('disabled')).toBeDefined();
  });

  it('test_tombol_menampilkan_menyimpan_saat_submitting', () => {
    const wrapper = mountComponent({ submitting: true });
    expect(wrapper.text()).toContain('Menyimpan...');
  });

  // =============================================
  // EMIT
  // =============================================

  it('test_emit_close_saat_klik_batal', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const closeBtn = Array.from(buttons).find(b => b.text().includes('Batal'));
    if (closeBtn) await closeBtn.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  it('test_emit_save_saat_klik_simpan', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const saveBtn = Array.from(buttons).find(b => b.text().includes('Simpan Program'));
    if (saveBtn) await saveBtn.trigger('click');
    expect(wrapper.emitted('save')).toBeTruthy();
  });

  // =============================================
  // OPEN/CLOSE
  // =============================================

  it('test_modal_tidak_dirender_saat_open_false', () => {
    const wrapper = mountComponent({ open: false });
    expect(wrapper.text()).toBe('');
  });
});