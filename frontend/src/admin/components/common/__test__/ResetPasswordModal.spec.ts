import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import ResetPasswordModal from '../ResetPasswordModal.vue';

// =============================================
// MOCK: errorHandler
// =============================================
vi.mock('../../utils/errorHandler', () => ({
  getSafeErrorMessage: (msg: unknown): string => (msg as string) || '',
}));

// =============================================
// HELPERS
// =============================================
function mountComponent(overrides: Record<string, unknown> = {}) {
  return mount(ResetPasswordModal, {
    props: {
      open: true,
      targetName: 'Admin Test',
      targetNip: '199001012020011001',
      submitting: false,
      serverError: '',
      ...overrides,
    },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('ResetPasswordModal.vue', () => {

  // ===== HAPPY PATH =====

  it('test_render_saat_open_true', () => {
    const wrapper = mountComponent();
    expect(wrapper.isVisible()).toBe(true);
  });

  it('test_render_judul_reset_password', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Reset Password');
  });

  it('test_render_target_name', () => {
    const wrapper = mountComponent({ targetName: 'Admin Test' });
    expect(wrapper.text()).toContain('Admin Test');
  });

  it('test_render_target_nip', () => {
    const wrapper = mountComponent({ targetNip: '199001012020011001' });
    expect(wrapper.text()).toContain('199001012020011001');
  });

  it('test_emit_save_dengan_password_valid', async () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { form: { password: string }; handleSubmit: () => void };

    vm.form.password = 'newpassword123';
    await vm.handleSubmit();

    expect(wrapper.emitted('save')).toBeTruthy();
    expect(wrapper.emitted('save')![0][0]).toBe('newpassword123');
  });

  it('test_emit_close_saat_klik_batal', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const batalBtn = buttons.find(btn => btn.text().includes('Batal'));
    await batalBtn?.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  it('test_emit_close_saat_klik_x', async () => {
    const wrapper = mountComponent();
    const closeBtn = wrapper.find('.text-gray-400');
    await closeBtn.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  // ===== SAD PATH =====

  it('test_tidak_render_saat_open_false', () => {
    const wrapper = mountComponent({ open: false });
    expect(wrapper.find('.fixed').exists()).toBe(false);
  });

  it('test_password_kosong_tidak_emit_save', async () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { form: { password: string }; errors: { password: string }; handleSubmit: () => void };

    vm.form.password = '';
    await vm.handleSubmit();

    expect(wrapper.emitted('save')).toBeFalsy();
    expect(vm.errors.password).toBe('Password wajib diisi.');
  });

  it('test_password_kurang_8_karakter_tidak_emit_save', async () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { form: { password: string }; errors: { password: string }; handleSubmit: () => void };

    vm.form.password = '1234567';
    await vm.handleSubmit();

    expect(wrapper.emitted('save')).toBeFalsy();
    expect(vm.errors.password).toBe('Password minimal 8 karakter.');
  });

  it('test_tampilkan_server_error', () => {
    const wrapper = mountComponent({ serverError: 'Gagal mereset password.' });
    expect(wrapper.text()).toContain('Gagal mereset password.');
  });

  // ===== BOUNDARY =====

  it('test_password_tepat_8_karakter_valid', async () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { form: { password: string }; handleSubmit: () => void };

    vm.form.password = '12345678';
    await vm.handleSubmit();

    expect(wrapper.emitted('save')).toBeTruthy();
  });

  it('test_reset_button_disabled_saat_submitting', () => {
    const wrapper = mountComponent({ submitting: true });
    const buttons = wrapper.findAll('button');
    const submitBtn = buttons.find(btn => btn.attributes('type') === 'submit');
    expect(submitBtn?.attributes('disabled')).toBeDefined();
  });

  it('test_spinner_tampil_saat_submitting', () => {
    const wrapper = mountComponent({ submitting: true });
    expect(wrapper.find('.animate-spin').exists()).toBe(true);
  });

  // ===== EDGE CASE =====

  it('test_show_password_toggle', async () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { showPassword: boolean };
    expect(vm.showPassword).toBe(false);

    const toggleBtn = wrapper.find('button[type="button"]');
    await toggleBtn.trigger('click');
    expect(vm.showPassword).toBe(true);
  });

  // ===== NULL / EMPTY =====

  it('test_target_name_kosong', () => {
    const wrapper = mountComponent({ targetName: '' });
    expect(wrapper.find('.text-amber-900').text()).toBe('');
  });

  it('test_server_error_kosong_tidak_tampil', () => {
    const wrapper = mountComponent({ serverError: '' });
    expect(wrapper.find('.text-red-600').exists()).toBe(false);
  });

  // ===== DATA TYPE =====

  it('test_form_password_selalu_string', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { form: { password: string } };
    expect(typeof vm.form.password).toBe('string');
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_validatePassword_kosong', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { form: { password: string }; errors: { password: string }; validatePassword: () => void };

    vm.form.password = '';
    vm.validatePassword();
    expect(vm.errors.password).toBe('Password wajib diisi.');
  });

  it('test_validatePassword_valid', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { form: { password: string }; errors: { password: string }; validatePassword: () => void };

    vm.form.password = 'password123';
    vm.validatePassword();
    expect(vm.errors.password).toBe('');
  });

  // ===== STATE TRANSITION =====

  it('test_form_submit_prevent', async () => {
    const wrapper = mountComponent();
    const form = wrapper.find('form');
    await form.trigger('submit.prevent');
    // Submit dicegah, handleSubmit tetap dipanggil
    expect(wrapper.emitted('save')).toBeFalsy(); // password kosong
  });

  // ===== RENDERING =====

  it('test_render_warning_box', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('.bg-amber-50').exists()).toBe(true);
  });

  it('test_render_password_input', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('input[placeholder="Masukkan password baru"]').exists()).toBe(true);
  });
});