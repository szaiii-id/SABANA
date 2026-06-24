import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import CitizenSearchSection from '../CitizenSearchSection.vue';
import type { CitizenSearchResult } from '../../../types/citizen-assistance';

const mockResult: CitizenSearchResult = {
  id: '1',
  full_name: 'Joko Widodo',
  nik: '6301234567890123',
  family_card_number: '6301',
  whatsapp_number: '62812',
};

function mountComponent(props: Record<string, unknown> = {}) {
  return mount(CitizenSearchSection, {
    props: {
      query: '',
      results: [],
      selected: null,
      isSearching: false,
      hasSearched: false,
      hasMore: false,
      isLoadingMore: false,
      ...props,
    },
  });
}

describe('CitizenSearchSection.vue', () => {

  // ===== HAPPY PATH =====

  it('test_merender_input_pencarian', () => {
    const wrapper = mountComponent();
    expect(wrapper.find('input').exists()).toBe(true);
  });

  it('test_menampilkan_hasil_pencarian', () => {
    const wrapper = mountComponent({
      query: 'Joko',
      results: [mockResult],
      hasSearched: true,
    });
    expect(wrapper.text()).toContain('Joko Widodo');
  });

  it('test_menampilkan_warga_terpilih', () => {
    const wrapper = mountComponent({
      selected: mockResult,
    });
    expect(wrapper.text()).toContain('Ganti');
  });

  // ===== EMIT =====

  it('test_meng_emit_search_saat_input_berubah', async () => {
    const wrapper = mountComponent();
    await wrapper.find('input').setValue('Joko');
    expect(wrapper.emitted('search')?.[0][0]).toBe('Joko');
  });

  it('test_meng_emit_select_saat_klik_hasil', async () => {
    const wrapper = mountComponent({
      query: 'Joko',
      results: [mockResult],
      hasSearched: true,
    });
    await wrapper.find('.cursor-pointer').trigger('click');
    expect(wrapper.emitted('select')).toBeTruthy();
  });

  // ===== SAD PATH =====

  it('test_menampilkan_empty_state_saat_tidak_ditemukan', () => {
    const wrapper = mountComponent({
      query: 'xxxxx',
      results: [],
      hasSearched: true,
    });
    expect(wrapper.text()).toContain('Warga tidak ditemukan');
  });
});