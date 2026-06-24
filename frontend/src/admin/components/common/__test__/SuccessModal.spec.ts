import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import SuccessModal from '../SuccessModal.vue';

// =============================================
// HELPERS
// =============================================
function mountComponent(overrides: Record<string, unknown> = {}) {
  return mount(SuccessModal, {
    props: {
      show: true,
      message: 'Operasi berhasil.',
      ...overrides,
    },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('SuccessModal.vue', () => {

  // ===== HAPPY PATH =====

  it('test_render_saat_show_true', () => {
    const wrapper = mountComponent();
    expect(wrapper.isVisible()).toBe(true);
  });

  it('test_render_judul_berhasil', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Berhasil!');
  });

  it('test_render_message', () => {
    const wrapper = mountComponent({ message: 'Akun berhasil dibuat.' });
    expect(wrapper.text()).toContain('Akun berhasil dibuat.');
  });

  it('test_emit_close_saat_klik_tutup', async () => {
    const wrapper = mountComponent();
    const button = wrapper.find('button');
    await button.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  it('test_emit_close_saat_klik_backdrop', async () => {
    const wrapper = mountComponent();
    const backdrop = wrapper.find('.bg-gray-900\\/40');
    await backdrop.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  // ===== SAD PATH =====

  it('test_tidak_render_saat_show_false', () => {
    const wrapper = mountComponent({ show: false });
    expect(wrapper.find('.fixed').exists()).toBe(false);
  });

  // ===== BOUNDARY =====

  it('test_message_kosong_tidak_error', () => {
    const wrapper = mountComponent({ message: '' });
    expect(wrapper.find('.fixed').exists()).toBe(true);
  });

  it('test_message_panjang_tetap_render', () => {
    const longMessage = 'A'.repeat(300);
    const wrapper = mountComponent({ message: longMessage });
    expect(wrapper.text()).toContain(longMessage);
  });

  // ===== EDGE CASE =====

  it('test_render_check_icon', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('.bg-green-100').exists()).toBe(true);
    expect(wrapper.find('.text-green-600').exists()).toBe(true);
  });

  // ===== NULL / EMPTY =====

  it('test_show_false_tidak_render_apa_apa', () => {
    const wrapper = mountComponent({ show: false });
    expect(wrapper.html()).not.toContain('Berhasil!');
  });

  // ===== DATA TYPE =====

  it('test_show_boolean', () => {
    const wrapper = mountComponent({ show: true });
    expect(wrapper.isVisible()).toBe(true);
  });

  it('test_message_string', () => {
    const wrapper = mountComponent({ message: 'Test message' });
    expect(wrapper.text()).toContain('Test message');
  });

  // ===== STATE TRANSITION =====

  it('test_show_false_ke_true_modal_tampil', async () => {
    const wrapper = mountComponent({ show: false });
    expect(wrapper.find('.fixed').exists()).toBe(false);

    await wrapper.setProps({ show: true });
    expect(wrapper.find('.fixed').exists()).toBe(true);
  });

  // ===== RENDERING =====

  it('test_render_button_tutup', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Tutup');
  });

  it('test_render_success_icon', () => {
    const wrapper = mountComponent();
    const svg = wrapper.find('.text-green-600');
    expect(svg.exists()).toBe(true);
  });
});