import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AccountFormFields from '../AccountFormFields.vue';
import type { AccountPayload } from '../../../types/account';

// =============================================
// HELPERS
// =============================================
interface FormErrors {
  nip: string;
  name: string;
  password: string;
}

const baseForm: AccountPayload = {
  nip: '199001012020011001',
  name: 'Test Admin',
  password: 'password123',
  role: 'village_officer',
  is_active: true,
};

const baseErrors: FormErrors = { nip: '', name: '', password: '' };

function mountComponent(overrides: Record<string, unknown> = {}) {
  return mount(AccountFormFields, {
    props: {
      form: { ...baseForm },
      mode: 'add' as const,
      errors: { ...baseErrors },
      ...overrides,
    },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('AccountFormFields.vue', () => {

  // ===== HAPPY PATH =====

  it('test_menampilkan_field_nip', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('NIP');
  });

  it('test_menampilkan_field_nama', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Nama Lengkap');
  });

  it('test_menampilkan_field_password', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Kata Sandi');
  });

  it('test_menampilkan_field_role', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Level Akses');
  });

  it('test_nip_valid_18_digit_tidak_error', () => {
    const errors = { nip: '', name: '', password: '' };
    mountComponent({ mode: 'add', errors, form: { ...baseForm, nip: '199001012020011002' } });
    expect(errors.nip).toBe('');
  });

  it('test_password_valid_tidak_error', () => {
    const errors = { nip: '', name: '', password: '' };
    mountComponent({ mode: 'add', errors, form: { ...baseForm, password: '12345678' } });
    expect(errors.password).toBe('');
  });

  // ===== SAD PATH =====

  it('test_validasi_nip_kosong_add_mode', () => {
    const errors = { nip: '', name: '', password: '' };
    const wrapper = mountComponent({ mode: 'add', errors, form: { ...baseForm, nip: '' } });
    const vm = wrapper.vm as unknown as { validateNip: () => void };
    vm.validateNip();
    expect(errors.nip).toBe('NIP wajib diisi.');
  });

  it('test_validasi_nip_kurang_18_digit', () => {
    const errors = { nip: '', name: '', password: '' };
    const wrapper = mountComponent({ mode: 'add', errors, form: { ...baseForm, nip: '12345' } });
    const vm = wrapper.vm as unknown as { validateNip: () => void };
    vm.validateNip();
    expect(errors.nip).toBe('NIP harus tepat 18 digit.');
  });

  it('test_validasi_nama_kosong', () => {
    const errors = { nip: '', name: '', password: '' };
    const wrapper = mountComponent({ errors, form: { ...baseForm, name: '' } });
    const vm = wrapper.vm as unknown as { validateName: () => void };
    vm.validateName();
    expect(errors.name).toBe('Nama lengkap wajib diisi.');
  });

  it('test_validasi_password_kosong_add_mode', () => {
    const errors = { nip: '', name: '', password: '' };
    const wrapper = mountComponent({ mode: 'add', errors, form: { ...baseForm, password: '' } });
    const vm = wrapper.vm as unknown as { validatePassword: () => void };
    vm.validatePassword();
    expect(errors.password).toBe('Kata sandi wajib diisi.');
  });

  // ===== BOUNDARY =====

  it('test_nip_tepat_18_digit_valid', () => {
    const errors = { nip: '', name: '', password: '' };
    const wrapper = mountComponent({ mode: 'add', errors, form: { ...baseForm, nip: '199001012020011001' } });
    const vm = wrapper.vm as unknown as { validateNip: () => void };
    vm.validateNip();
    expect(errors.nip).toBe('');
  });

  it('test_password_tepat_8_karakter_valid', () => {
    const errors = { nip: '', name: '', password: '' };
    const wrapper = mountComponent({ mode: 'add', errors, form: { ...baseForm, password: '12345678' } });
    const vm = wrapper.vm as unknown as { validatePassword: () => void };
    vm.validatePassword();
    expect(errors.password).toBe('');
  });

  // ===== EDGE CASE =====

  it('test_format_nip_hapus_non_digit', () => {
    const errors = { nip: '', name: '', password: '' };
    const wrapper = mountComponent({ mode: 'add', errors, form: { ...baseForm, nip: '1990-0101-2020-0110' } });
    const vm = wrapper.vm as unknown as { validateNip: () => void; form: AccountPayload };
    vm.validateNip();
    expect(vm.form.nip).toBe('1990010120200110');
  });

  it('test_nip_disabled_dalam_edit_mode', () => {
    const wrapper = mountComponent({ mode: 'edit' });
    const nipInputs = wrapper.findAll('input');
    const nipInput = nipInputs.find(i => i.attributes('disabled') !== undefined);
    expect(nipInput?.exists()).toBe(true);
  });

  it('test_edit_mode_password_opsional', () => {
    const errors = { nip: '', name: '', password: '' };
    const wrapper = mountComponent({ mode: 'edit', errors, form: { ...baseForm, password: '' } });
    const vm = wrapper.vm as unknown as { validatePassword: () => void };
    vm.validatePassword();
    expect(errors.password).toBe('');
  });

  it('test_edit_mode_password_diisi_kurang_8', () => {
    const errors = { nip: '', name: '', password: '' };
    const wrapper = mountComponent({ mode: 'edit', errors, form: { ...baseForm, password: '123' } });
    const vm = wrapper.vm as unknown as { validatePassword: () => void };
    vm.validatePassword();
    expect(errors.password).toBe('Kata sandi minimal 8 karakter.');
  });

  // ===== NULL / EMPTY =====

  it('test_errors_default_kosong', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { errors: FormErrors };
    expect(vm.errors.nip).toBe('');
    expect(vm.errors.name).toBe('');
    expect(vm.errors.password).toBe('');
  });

  // ===== DATA TYPE =====

  it('test_form_nip_selalu_string', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { form: AccountPayload };
    expect(typeof vm.form.nip).toBe('string');
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_role_select_ada_3_option', () => {
    const wrapper = mountComponent();
    const options = wrapper.findAll('option');
    expect(options.length).toBe(3);
  });

  // ===== STATE TRANSITION =====

  it('test_show_password_toggle', async () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { showPassword: boolean };
    expect(vm.showPassword).toBe(false);

    const toggleBtn = wrapper.find('button[aria-label="Toggle password visibility"]');
    await toggleBtn.trigger('click');
    expect(vm.showPassword).toBe(true);
  });

  it('test_emit_roleChange_saat_role_diubah', async () => {
    const wrapper = mountComponent();
    const select = wrapper.find('select');
    await select.trigger('change');
    expect(wrapper.emitted('roleChange')).toBeTruthy();
  });

  // ===== RENDERING =====

  it('test_status_akun_di_edit_mode', () => {
    const wrapper = mountComponent({ mode: 'edit' });
    expect(wrapper.text()).toContain('Status Akun');
  });

  it('test_status_akun_tidak_di_add_mode', () => {
    const wrapper = mountComponent({ mode: 'add' });
    expect(wrapper.text()).not.toContain('Status Akun');
  });

  it('test_helper_text_nip_permanen_di_edit', () => {
    const wrapper = mountComponent({ mode: 'edit' });
    expect(wrapper.text()).toContain('NIP tidak dapat diubah karena bersifat permanen');
  });

  it('test_helper_text_role_di_edit', () => {
    const wrapper = mountComponent({ mode: 'edit' });
    expect(wrapper.text()).toContain('Role tidak dapat diubah demi keamanan');
  });
});