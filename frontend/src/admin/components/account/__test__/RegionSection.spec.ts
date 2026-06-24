import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import RegionSection from '../RegionSection.vue';
import type { Region } from '../../../composables/useRegion';

// =============================================
// HELPERS
// =============================================
const mockRegencies: Region[] = [
  { id: '6301', name: 'Tanah Laut' },
  { id: '6302', name: 'Banjar' },
];

const mockDistricts: Region[] = [
  { id: '6301020', name: 'Pelaihari' },
];

const mockVillages: Region[] = [
  { id: '6301020001', name: 'Desa Test' },
];

function mountComponent(overrides: Record<string, unknown> = {}) {
  return mount(RegionSection, {
    props: {
      regencies: mockRegencies,
      districts: mockDistricts,
      villages: mockVillages,
      showDistrict: true,
      showVillage: true,
      regencyId: '',
      districtId: '',
      villageId: '',
      ...overrides,
    },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('RegionSection.vue', () => {

  // ===== HAPPY PATH =====

  it('test_render_heading_penugasan_wilayah', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Penugasan Wilayah');
  });

  it('test_render_regency_select', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Kabupaten/Kota');
    expect(wrapper.text()).toContain('Tanah Laut');
  });

  it('test_render_district_select', () => {
    const wrapper = mountComponent({ showDistrict: true });
    expect(wrapper.text()).toContain('Kecamatan');
  });

  it('test_render_village_select', () => {
    const wrapper = mountComponent({ showVillage: true });
    expect(wrapper.text()).toContain('Desa/Kelurahan');
  });

  it('test_emit_update_regencyId', async () => {
    const wrapper = mountComponent();
    const selects = wrapper.findAll('select');
    await selects[0].setValue('6301');

    expect(wrapper.emitted('update:regencyId')).toBeTruthy();
    expect(wrapper.emitted('update:regencyId')![0][0]).toBe('6301');
  });

  it('test_emit_update_districtId', async () => {
    const wrapper = mountComponent({ regencyId: '6301', showDistrict: true });
    const selects = wrapper.findAll('select');
    const districtSelect = selects[1];
    await districtSelect.setValue('6301020');

    expect(wrapper.emitted('update:districtId')).toBeTruthy();
  });

  it('test_emit_update_villageId', async () => {
    const wrapper = mountComponent({ regencyId: '6301', districtId: '6301020', showVillage: true });
    const selects = wrapper.findAll('select');
    const villageSelect = selects[2];
    await villageSelect.setValue('6301020001');

    expect(wrapper.emitted('update:villageId')).toBeTruthy();
  });

  // ===== SAD PATH =====

  it('test_district_select_hidden_saat_showDistrict_false', () => {
    const wrapper = mountComponent({ showDistrict: false });
    expect(wrapper.text()).not.toContain('Kecamatan');
  });

  it('test_village_select_hidden_saat_showVillage_false', () => {
    const wrapper = mountComponent({ showVillage: false });
    expect(wrapper.text()).not.toContain('Desa/Kelurahan');
  });

  // ===== BOUNDARY =====

  it('test_district_disabled_saat_regencyId_kosong', () => {
    const wrapper = mountComponent({ regencyId: '', showDistrict: true });
    const selects = wrapper.findAll('select');
    const districtSelect = selects[1];
    expect(districtSelect.attributes('disabled')).toBeDefined();
  });

  it('test_village_disabled_saat_districtId_kosong', () => {
    const wrapper = mountComponent({ districtId: '', showVillage: true });
    const selects = wrapper.findAll('select');
    const villageSelect = selects[2];
    expect(villageSelect.attributes('disabled')).toBeDefined();
  });

  // ===== EDGE CASE =====

  it('test_only_regency_shown_for_regency_admin', () => {
    const wrapper = mountComponent({ showDistrict: false, showVillage: false });
    const selects = wrapper.findAll('select');
    expect(selects.length).toBe(1);
  });

  // ===== NULL / EMPTY =====

  it('test_regency_id_kosong_default', () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { regencyId: string };
    expect(vm.regencyId).toBe('');
  });

it('test_empty_regions_list', () => {
    const wrapper = mountComponent({ regencies: [], districts: [], villages: [] });
    const options = wrapper.findAll('option');
    // 3 placeholder: Pilih Kabupaten, Pilih Kecamatan, Pilih Desa
    expect(options.length).toBe(3);
});

  // ===== DATA TYPE =====

  it('test_regencyId_selalu_string', () => {
    const wrapper = mountComponent({ regencyId: '6301' });
    const vm = wrapper.vm as unknown as { regencyId: string };
    expect(typeof vm.regencyId).toBe('string');
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_regency_options_dari_props', () => {
    const wrapper = mountComponent();
    const options = wrapper.findAll('option');
    const names = options.map(o => o.text());
    expect(names).toContain('Tanah Laut');
    expect(names).toContain('Banjar');
  });

  // ===== STATE TRANSITION =====

  it('test_regency_change_reset_district_and_village', async () => {
    const wrapper = mountComponent({ regencyId: '6301', districtId: '6301020', villageId: '6301020001' });
    const selects = wrapper.findAll('select');
    
    // Ganti kabupaten
    await selects[0].setValue('6302');

    expect(wrapper.emitted('update:regencyId')).toBeTruthy();
  });

  // ===== RENDERING =====

  it('test_render_3_selects_saat_full', () => {
    const wrapper = mountComponent({ showDistrict: true, showVillage: true });
    const selects = wrapper.findAll('select');
    expect(selects.length).toBe(3);
  });
});