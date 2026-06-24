import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AccountModal from '../AccountModal.vue';

// =============================================
// HELPERS
// =============================================
function mountComponent(overrides: Record<string, unknown> = {}) {
  return mount(AccountModal, {
    props: {
      open: true,
      mode: 'add',
      submitting: false,
      error: '',
      ...overrides,
    },
    slots: { default: '<div class="form-content">Form Content</div>' },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('AccountModal.vue', () => {

  // ===== HAPPY PATH =====

  it('test_render_saat_open_true', () => {
    const wrapper = mountComponent();
    expect(wrapper.isVisible()).toBe(true);
  });

  it('test_render_judul_add_mode', () => {
    const wrapper = mountComponent({ mode: 'add' });
    expect(wrapper.text()).toContain('Tambah Akun Pegawai');
  });

  it('test_render_judul_edit_mode', () => {
    const wrapper = mountComponent({ mode: 'edit' });
    expect(wrapper.text()).toContain('Edit Akun Pegawai');
  });

  it('test_render_slot_content', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Form Content');
  });

  it('test_emit_close_saat_klik_backdrop', async () => {
    const wrapper = mountComponent();
    const backdrop = wrapper.find('.bg-gray-900\\/40');
    await backdrop.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  it('test_emit_close_saat_klik_tombol_x', async () => {
    const wrapper = mountComponent();
    const closeBtn = wrapper.find('.text-gray-400');
    await closeBtn.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  it('test_emit_save_saat_klik_simpan', async () => {
    const wrapper = mountComponent();
    const saveBtn = wrapper.find('.bg-gradient-to-r');
    await saveBtn.trigger('click');
    expect(wrapper.emitted('save')).toBeTruthy();
  });

  // ===== SAD PATH =====

  it('test_tidak_render_saat_open_false', () => {
    const wrapper = mountComponent({ open: false });
    expect(wrapper.find('.fixed').exists()).toBe(false);
  });

  it('test_tampilkan_error_saat_error_ada', () => {
    const wrapper = mountComponent({ error: 'Terjadi kesalahan' });
    expect(wrapper.text()).toContain('Terjadi kesalahan');
  });

  // ===== BOUNDARY =====

  it('test_simpan_button_disabled_saat_submitting', () => {
    const wrapper = mountComponent({ submitting: true });
    const saveBtn = wrapper.find('.bg-gradient-to-r');
    expect(saveBtn.attributes('disabled')).toBeDefined();
  });

  it('test_simpan_button_text_menyimpan', () => {
    const wrapper = mountComponent({ submitting: true });
    expect(wrapper.text()).toContain('Menyimpan...');
  });

  // ===== EDGE CASE =====

  it('test_emit_save_via_form_submit', async () => {
    const wrapper = mountComponent();
    const form = wrapper.find('form');
    await form.trigger('submit.prevent');
    expect(wrapper.emitted('save')).toBeTruthy();
  });

  it('test_emit_close_via_button_batal', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const batalBtn = buttons.find(btn => btn.text().includes('Batal'));
    await batalBtn?.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  // ===== NULL / EMPTY =====

  it('test_tidak_tampilkan_error_saat_kosong', () => {
    const wrapper = mountComponent({ error: '' });
    expect(wrapper.find('.text-red-600').exists()).toBe(false);
  });

  it('test_slot_kosong_tidak_error', () => {
    const wrapper = mount(AccountModal, {
      props: { open: true, mode: 'add', submitting: false, error: '' },
    });
    expect(wrapper.find('.fixed').exists()).toBe(true);
  });

  // ===== DATA TYPE =====

  it('test_mode_add_string', () => {
    const wrapper = mountComponent({ mode: 'add' });
    expect(wrapper.text()).toContain('Tambah Akun Pegawai');
  });

  it('test_mode_edit_string', () => {
    const wrapper = mountComponent({ mode: 'edit' });
    expect(wrapper.text()).toContain('Edit Akun Pegawai');
  });

  // ===== STATE TRANSITION =====

  it('test_open_false_ke_true_modal_tampil', async () => {
    const wrapper = mountComponent({ open: false });
    expect(wrapper.find('.fixed').exists()).toBe(false);

    await wrapper.setProps({ open: true });
    expect(wrapper.find('.fixed').exists()).toBe(true);
  });

  // ===== RENDERING =====

  it('test_render_button_batal', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Batal');
  });

  it('test_render_button_simpan', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Simpan Akun');
  });

  it('test_render_header_dengan_close_button', () => {
    const wrapper = mountComponent();
    const headerSvg = wrapper.find('.text-gray-400 svg');
    expect(headerSvg.exists()).toBe(true);
  });
});