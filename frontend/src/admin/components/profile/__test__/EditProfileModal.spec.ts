import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import EditProfileModal from '../EditProfileModal.vue';

// =============================================
// HELPERS
// =============================================
interface EditProfileModalInstance {
  form: { name: string };
  errors: { name: string };
  validateName: () => void;
  handleSubmit: () => void;
}

interface EditProfileModalProps {
  open: boolean;
  nip: string;
  name: string;
  submitting: boolean;
  errorMessage: string;
}

function mountComponent(overrides: Partial<EditProfileModalProps> = {}) {
  return mount(EditProfileModal, {
    props: {
      open: true,
      nip: '199001012020011001',
      name: 'Admin Test',
      submitting: false,
      errorMessage: '',
      ...overrides,
    },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('EditProfileModal.vue', () => {

  // ===== HAPPY PATH =====

  it('test_menampilkan_nip_readonly', () => {
    const wrapper = mountComponent();
    const input = wrapper.find('input[disabled]');
    expect((input.element as HTMLInputElement).value).toBe('199001012020011001');
  });

  it('test_menerima_props_name', () => {
    const wrapper = mountComponent({ name: 'Admin Test' });
    expect(wrapper.props('name')).toBe('Admin Test');
  });

  it('test_emit_save_saat_valid', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as EditProfileModalInstance;
    vm.form.name = 'New Name';
    vm.handleSubmit();
    expect(wrapper.emitted('save')).toBeTruthy();
    expect(wrapper.emitted('save')![0]).toEqual(['New Name']);
  });

  it('test_emit_close_saat_klik_batal', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const closeBtn = Array.from(buttons).find(b => b.text().includes('Batal'));
    if (closeBtn) await closeBtn.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  // ===== SAD PATH =====

  it('test_validasi_nama_kosong', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as EditProfileModalInstance;
    vm.form.name = '';
    vm.handleSubmit();
    expect(vm.errors.name).toBe('Nama wajib diisi.');
  });

  it('test_menampilkan_error_message', () => {
    const wrapper = mountComponent({ errorMessage: 'Gagal menyimpan' });
    expect(wrapper.text()).toContain('Gagal menyimpan');
  });

  // ===== BOUNDARY =====

  it('test_nip_disabled', () => {
    const wrapper = mountComponent();
    const disabledInput = wrapper.find('input[disabled]');
    expect(disabledInput.exists()).toBe(true);
  });

  it('test_watch_open_reset_form', async () => {
      const wrapper = mountComponent({ open: false, name: 'Old Name' });
      await wrapper.setProps({ open: true, name: 'New Name' });
      
      const vm = wrapper.vm as unknown as EditProfileModalInstance;
      expect(vm.form.name).toBe('New Name');
  });

  // ===== EDGE CASE =====

  it('test_trim_nama_saat_save', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as EditProfileModalInstance;
    vm.form.name = '   Trimmed   ';
    vm.handleSubmit();
    expect(wrapper.emitted('save')![0]).toEqual(['Trimmed']);
  });

  it('test_tidak_render_saat_open_false', () => {
    const wrapper = mountComponent({ open: false });
    expect(wrapper.find('.fixed').exists()).toBe(false);
  });

  // ===== NULL / EMPTY =====

  it('test_nip_kosong_tidak_error', () => {
    const wrapper = mountComponent({ nip: '' });
    expect(wrapper.find('input[disabled]').exists()).toBe(true);
  });

  it('test_error_message_kosong_tidak_tampil', () => {
    const wrapper = mountComponent({ errorMessage: '' });
    expect(wrapper.find('.text-red-600').exists()).toBe(false);
  });

  // ===== DATA TYPE =====

  it('test_nip_selalu_string', () => {
    const wrapper = mountComponent({ nip: '199001012020011001' });
    const input = wrapper.find('input[disabled]');
    expect(typeof (input.element as HTMLInputElement).value).toBe('string');
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_validateName_nama_spasi_saja', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as EditProfileModalInstance;
    vm.form.name = '   ';
    vm.validateName();
    expect(vm.errors.name).toBe('Nama wajib diisi.');
  });

  it('test_validateName_nama_valid', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as EditProfileModalInstance;
    vm.form.name = 'Valid Name';
    vm.validateName();
    expect(vm.errors.name).toBe('');
  });

  // ===== STATE TRANSITION =====

  it('test_tombol_disabled_saat_submitting', () => {
    const wrapper = mountComponent({ submitting: true });
    const submitBtn = wrapper.find('button[type="submit"]');
    expect(submitBtn.attributes('disabled')).toBeDefined();
    expect(submitBtn.text()).toContain('Menyimpan');
  });

  it('test_watch_open_reset_form', async () => {
    const wrapper = mountComponent({ open: true, name: 'Old Name' });
    await wrapper.setProps({ open: false });
    await wrapper.setProps({ open: true, name: 'New Name' });
    
    const vm = wrapper.vm as unknown as EditProfileModalInstance;
    expect(vm.form.name).toBe('New Name');
  });

  // ===== RENDERING =====

  it('test_render_judul_edit_profil', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Edit Profil');
  });

  it('test_render_label_nip', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('NIP');
  });

  it('test_render_label_nama', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Nama Lengkap');
  });
});