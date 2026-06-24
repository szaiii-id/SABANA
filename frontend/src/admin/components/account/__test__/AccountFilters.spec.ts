import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AccountFilters from '../AccountFilters.vue';

// =============================================
// HELPERS
// =============================================
interface FilterModel {
  search: string;
  role: string;
  is_active: string;
}

const defaultModel: FilterModel = {
  search: '',
  role: '',
  is_active: '',
};

function mountComponent(modelValue: FilterModel = { ...defaultModel }) {
  return mount(AccountFilters, {
    props: { modelValue },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('AccountFilters.vue', () => {

  // ===== HAPPY PATH =====

  it('test_render_search_input', () => {
    const wrapper = mountComponent();
    const input = wrapper.find('input[type="text"]');
    expect(input.exists()).toBe(true);
    expect(input.attributes('placeholder')).toBe('Cari NIP atau Nama...');
  });

  it('test_render_role_select', () => {
    const wrapper = mountComponent();
    const selects = wrapper.findAll('select');
    expect(selects.length).toBe(2);
  });

  it('test_emit_search_input', async () => {
    const wrapper = mountComponent();
    const input = wrapper.find('input[type="text"]');

    await input.setValue('Admin Test');

    expect(wrapper.emitted('update:modelValue')).toBeTruthy();
    const emitted = wrapper.emitted('update:modelValue')![0][0] as FilterModel;
    expect(emitted.search).toBe('Admin Test');
  });

  // ===== SAD PATH =====

  it('test_search_input_kosong_diaccept', async () => {
    const wrapper = mountComponent({ search: 'test', role: '', is_active: '' });
    const input = wrapper.find('input[type="text"]');

    await input.setValue('');

    const emitted = wrapper.emitted('update:modelValue')![0][0] as FilterModel;
    expect(emitted.search).toBe('');
  });

  // ===== BOUNDARY =====

  it('test_role_select_ada_4_option', () => {
    const wrapper = mountComponent();
    const selects = wrapper.findAll('select');
    const roleSelect = selects[0];
    const options = roleSelect.findAll('option');

    expect(options.length).toBe(4); // Semua Level + 3 role
  });

  it('test_status_select_ada_3_option', () => {
    const wrapper = mountComponent();
    const selects = wrapper.findAll('select');
    const statusSelect = selects[1];
    const options = statusSelect.findAll('option');

    expect(options.length).toBe(3); // Semua Status + Aktif + Nonaktif
  });

  // ===== EDGE CASE =====

  it('test_emit_role_change_keep_other_values', async () => {
    const wrapper = mountComponent({ search: 'test', role: '', is_active: '' });
    const selects = wrapper.findAll('select');
    const roleSelect = selects[0];

    await roleSelect.setValue('regency_admin');

    const emitted = wrapper.emitted('update:modelValue')![0][0] as FilterModel;
    expect(emitted.role).toBe('regency_admin');
    expect(emitted.search).toBe('test');
    expect(emitted.is_active).toBe('');
  });

  it('test_emit_status_change_keep_other_values', async () => {
    const wrapper = mountComponent({ search: '', role: '', is_active: '' });
    const selects = wrapper.findAll('select');
    const statusSelect = selects[1];

    await statusSelect.setValue('true');

    const emitted = wrapper.emitted('update:modelValue')![0][0] as FilterModel;
    expect(emitted.is_active).toBe('true');
    expect(emitted.search).toBe('');
    expect(emitted.role).toBe('');
  });

  // ===== NULL / EMPTY =====

  it('test_initial_values_dari_props', () => {
    const wrapper = mountComponent({ search: '', role: '', is_active: '' });
    const vm = wrapper.vm as unknown as { modelValue: FilterModel };

    expect(vm.modelValue.search).toBe('');
    expect(vm.modelValue.role).toBe('');
    expect(vm.modelValue.is_active).toBe('');
  });

  it('test_initial_values_dengan_data', () => {
    const wrapper = mountComponent({ search: 'NIP', role: 'village_officer', is_active: 'true' });
    const vm = wrapper.vm as unknown as { modelValue: FilterModel };

    expect(vm.modelValue.search).toBe('NIP');
    expect(vm.modelValue.role).toBe('village_officer');
    expect(vm.modelValue.is_active).toBe('true');
  });

  // ===== DATA TYPE =====

  it('test_modelValue_has_correct_structure', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { modelValue: FilterModel };

    expect(typeof vm.modelValue.search).toBe('string');
    expect(typeof vm.modelValue.role).toBe('string');
    expect(typeof vm.modelValue.is_active).toBe('string');
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_role_option_regency_admin', () => {
    const wrapper = mountComponent();
    const options = wrapper.findAll('option');
    const roles = options.map(opt => opt.text());

    expect(roles).toContain('Admin Kabupaten');
    expect(roles).toContain('Admin Kecamatan');
    expect(roles).toContain('Petugas Desa');
  });

  it('test_status_option_aktif_nonaktif', () => {
    const wrapper = mountComponent();
    const options = wrapper.findAll('option');
    const statuses = options.map(opt => opt.text());

    expect(statuses).toContain('Aktif');
    expect(statuses).toContain('Nonaktif');
  });

  // ===== STATE TRANSITION =====

  it('test_emit_update_modelValue_saat_search', async () => {
    const wrapper = mountComponent();
    const input = wrapper.find('input[type="text"]');

    await input.setValue('A');
    await input.setValue('Ad');

    // Harus emit 2 kali
    const emitted = wrapper.emitted('update:modelValue');
    expect(emitted?.length).toBe(2);
  });

  // ===== RENDERING =====

  it('test_render_search_icon', () => {
    const wrapper = mountComponent();
    const svg = wrapper.find('svg');
    expect(svg.exists()).toBe(true);
  });

  it('test_render_placeholder_text', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Semua Level');
    expect(wrapper.text()).toContain('Semua Status');
  });
});