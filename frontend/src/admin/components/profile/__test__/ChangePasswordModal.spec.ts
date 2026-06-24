import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ChangePasswordModal from '../ChangePasswordModal.vue';

// =============================================
// HELPERS
// =============================================
interface ChangePasswordModalInstance {
  form: { current_password: string; new_password: string };
  errors: { current_password: string; new_password: string };
  showCurrent: boolean;
  showNew: boolean;
  validateCurrentPassword: () => void;
  validateNewPassword: () => void;
  handleSubmit: () => void;
}

interface ChangePasswordModalProps {
  open: boolean;
  submitting: boolean;
  errorMessage: string;
}

function mountComponent(overrides: Partial<ChangePasswordModalProps> = {}) {
  return mount(ChangePasswordModal, {
    props: {
      open: true,
      submitting: false,
      errorMessage: '',
      ...overrides,
    },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('ChangePasswordModal.vue', () => {

  // ===== HAPPY PATH =====

  it('test_menampilkan_heading_ganti_password', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Ganti Password');
  });

  it('test_menampilkan_input_password_saat_ini', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Password Saat Ini');
  });

  it('test_menampilkan_input_password_baru', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Password Baru');
  });

  it('test_emit_save_saat_valid', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as ChangePasswordModalInstance;
    vm.form.current_password = 'oldpassword';
    vm.form.new_password = 'newpassword123';
    vm.handleSubmit();
    expect(wrapper.emitted('save')).toBeTruthy();
    expect(wrapper.emitted('save')![0]).toEqual(['oldpassword', 'newpassword123']);
  });

  it('test_emit_close_saat_klik_batal', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const closeBtn = Array.from(buttons).find(b => b.text().includes('Batal'));
    if (closeBtn) await closeBtn.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  // ===== SAD PATH =====

  it('test_validasi_password_saat_ini_kosong', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as ChangePasswordModalInstance;
    vm.form.current_password = '';
    vm.handleSubmit();
    expect(vm.errors.current_password).toBe('Password saat ini wajib diisi.');
  });

  it('test_validasi_password_baru_kosong', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as ChangePasswordModalInstance;
    vm.form.current_password = 'oldpass';
    vm.form.new_password = '';
    vm.handleSubmit();
    expect(vm.errors.new_password).toBe('Password baru wajib diisi.');
  });

  it('test_menampilkan_error_message', () => {
    const wrapper = mountComponent({ errorMessage: 'Password salah' });
    expect(wrapper.text()).toContain('Password salah');
  });

  // ===== BOUNDARY =====

  it('test_validasi_password_baru_min_8', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as ChangePasswordModalInstance;
    vm.form.current_password = 'oldpass';
    vm.form.new_password = '1234567';
    vm.handleSubmit();
    expect(vm.errors.new_password).toBe('Password minimal 8 karakter.');
  });

  it('test_password_baru_tepat_8_karakter_valid', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as ChangePasswordModalInstance;
    vm.form.current_password = 'oldpass';
    vm.form.new_password = '12345678';
    vm.handleSubmit();
    expect(wrapper.emitted('save')).toBeTruthy();
  });

  // ===== EDGE CASE =====

  it('test_validasi_password_baru_sama_dengan_lama', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as ChangePasswordModalInstance;
    vm.form.current_password = 'password123';
    vm.form.new_password = 'password123';
    vm.handleSubmit();
    expect(vm.errors.new_password).toBe('Password baru tidak boleh sama dengan saat ini.');
  });

  it('test_tidak_render_saat_open_false', () => {
    const wrapper = mountComponent({ open: false });
    expect(wrapper.find('.fixed').exists()).toBe(false);
  });

  // ===== NULL / EMPTY =====

  it('test_form_kosong_saat_mount', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as ChangePasswordModalInstance;
    expect(vm.form.current_password).toBe('');
    expect(vm.form.new_password).toBe('');
  });

  it('test_error_message_kosong_tidak_tampil', () => {
    const wrapper = mountComponent({ errorMessage: '' });
    expect(wrapper.find('.text-red-600').exists()).toBe(false);
  });

  // ===== DATA TYPE =====

  it('test_form_values_string', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as ChangePasswordModalInstance;
    expect(typeof vm.form.current_password).toBe('string');
    expect(typeof vm.form.new_password).toBe('string');
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_validateCurrentPassword_kosong', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as ChangePasswordModalInstance;
    vm.form.current_password = '';
    vm.validateCurrentPassword();
    expect(vm.errors.current_password).toBe('Password saat ini wajib diisi.');
  });

  it('test_validateCurrentPassword_terisi', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as ChangePasswordModalInstance;
    vm.form.current_password = 'password';
    vm.validateCurrentPassword();
    expect(vm.errors.current_password).toBe('');
  });

  // ===== STATE TRANSITION =====

  it('test_tombol_disabled_saat_submitting', () => {
    const wrapper = mountComponent({ submitting: true });
    const submitBtn = wrapper.find('button[type="submit"]');
    expect(submitBtn.attributes('disabled')).toBeDefined();
    expect(submitBtn.text()).toContain('Mengubah');
  });

  it('test_show_password_toggle', async () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as ChangePasswordModalInstance;
    expect(vm.showCurrent).toBe(false);

    const toggleButtons = wrapper.findAll('button[type="button"]');
    if (toggleButtons.length > 0) {
      await toggleButtons[0].trigger('click');
      expect(vm.showCurrent).toBe(true);
    }
  });

  // ===== RENDERING =====

  it('test_render_button_batal', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Batal');
  });

  it('test_render_button_ubah_password', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Ubah Password');
  });
});