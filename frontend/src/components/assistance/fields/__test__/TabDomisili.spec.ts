import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import TabDomisili from '../TabDomisili.vue';
import type { Region } from '../../../../types/assistance';

const regencies: Region[] = [{ id: '6301', name: 'Tanah Laut' }];
const districts: Region[] = [{ id: '6301020', name: 'Pelaihari' }];
const villages: Region[] = [{ id: '6301020001', name: 'Desa Test' }];

function mountComponent(props: Record<string, unknown> = {}) {
  return mount(TabDomisili, {
    props: {
      regencyId: '', districtId: '', villageId: '',
      regencies, districts, villages,
      regencyError: '', districtError: '', villageError: '',
      ...props,
    },
  });
}

describe('TabDomisili.vue', () => {

  // ===== HAPPY PATH =====

  it('test_menampilkan_select_kabupaten', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Tanah Laut');
  });

  it('test_emit_update_regencyId', async () => {
    const wrapper = mountComponent();
    const select = wrapper.find('select');
    await select.setValue('6301');
    expect(wrapper.emitted('update:regencyId')).toBeTruthy();
  });

  // ===== SAD PATH =====

  it('test_menampilkan_error_regency', () => {
    const wrapper = mountComponent({ regencyError: 'Kabupaten wajib dipilih' });
    expect(wrapper.text()).toContain('Kabupaten wajib dipilih');
  });

  // ===== DATA TYPE =====

  it('test_menampilkan_select_kecamatan', () => {
    const wrapper = mountComponent({ districtId: '6301020', districts });
    expect(wrapper.text()).toContain('Pelaihari');
  });

  it('test_menampilkan_select_desa', () => {
    const wrapper = mountComponent({ villageId: '6301020001', villages });
    expect(wrapper.text()).toContain('Desa Test');
  });
});