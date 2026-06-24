import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import DeleteConfirmModal from '../DeleteConfirmModal.vue';

// =============================================
// HELPERS
// =============================================
function mountComponent(overrides: Record<string, unknown> = {}) {
  return mount(DeleteConfirmModal, {
    props: {
      open: true,
      message: 'Anda akan menonaktifkan akun <strong>Test</strong>',
      submitting: false,
      ...overrides,
    },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('DeleteConfirmModal.vue', () => {

  // ===== HAPPY PATH =====

  it('test_render_saat_open_true', () => {
    const wrapper = mountComponent();
    expect(wrapper.isVisible()).toBe(true);
  });

  it('test_render_judul_konfirmasi', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Konfirmasi Nonaktifkan');
  });

  it('test_render_message_html', () => {
    const wrapper = mountComponent({ message: '<strong>Admin Test</strong>' });
    expect(wrapper.html()).toContain('<strong>Admin Test</strong>');
  });

  it('test_emit_close_saat_klik_batal', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const batalBtn = buttons.find(btn => btn.text().includes('Batal'));
    await batalBtn?.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  it('test_emit_confirm_saat_klik_ya', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const confirmBtn = buttons.find(btn => btn.text().includes('Ya, Nonaktifkan'));
    await confirmBtn?.trigger('click');
    expect(wrapper.emitted('confirm')).toBeTruthy();
  });

  it('test_emit_close_saat_klik_backdrop', async () => {
    const wrapper = mountComponent();
    const backdrop = wrapper.find('.bg-gray-900\\/40');
    await backdrop.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  // ===== SAD PATH =====

  it('test_tidak_render_saat_open_false', () => {
    const wrapper = mountComponent({ open: false });
    expect(wrapper.find('.fixed').exists()).toBe(false);
  });

  // ===== BOUNDARY =====

  it('test_confirm_button_disabled_saat_submitting', () => {
    const wrapper = mountComponent({ submitting: true });
    const buttons = wrapper.findAll('button');
    const confirmBtn = buttons.find(btn => btn.text().includes('Ya, Nonaktifkan'));
    expect(confirmBtn?.attributes('disabled')).toBeDefined();
  });

  it('test_spinner_tampil_saat_submitting', () => {
    const wrapper = mountComponent({ submitting: true });
    expect(wrapper.find('.animate-spin').exists()).toBe(true);
  });

  // ===== EDGE CASE =====

  it('test_message_kosong_tidak_error', () => {
    const wrapper = mountComponent({ message: '' });
    expect(wrapper.find('.fixed').exists()).toBe(true);
  });

  it('test_message_panjang_tetap_render', () => {
    const longMessage = 'A'.repeat(500);
    const wrapper = mountComponent({ message: longMessage });
    expect(wrapper.text()).toContain(longMessage);
  });

  // ===== NULL / EMPTY =====

  it('test_open_false_tidak_render_apa_apa', () => {
    const wrapper = mountComponent({ open: false });
    expect(wrapper.html()).not.toContain('Konfirmasi Nonaktifkan');
  });

  // ===== DATA TYPE =====

  it('test_open_boolean', () => {
    const wrapper = mountComponent({ open: true });
    expect(wrapper.isVisible()).toBe(true);
  });

  it('test_submitting_boolean', () => {
    const wrapper = mountComponent({ submitting: false });
    const buttons = wrapper.findAll('button');
    const confirmBtn = buttons.find(btn => btn.text().includes('Ya, Nonaktifkan'));
    expect(confirmBtn?.attributes('disabled')).toBeUndefined();
  });

  // ===== STATE TRANSITION =====

  it('test_open_false_ke_true_modal_tampil', async () => {
    const wrapper = mountComponent({ open: false });
    expect(wrapper.find('.fixed').exists()).toBe(false);

    await wrapper.setProps({ open: true });
    expect(wrapper.find('.fixed').exists()).toBe(true);
  });

  // ===== RENDERING =====

  it('test_render_warning_icon', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('.bg-red-100').exists()).toBe(true);
    expect(wrapper.find('.text-red-600').exists()).toBe(true);
  });

  it('test_render_button_batal', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Batal');
  });

  it('test_render_button_confirm', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Ya, Nonaktifkan');
  });
});