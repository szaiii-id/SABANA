import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ProgramSelectSection from '../ProgramSelectSection.vue';
import type { AssistanceProgramSchema } from '../../../../types/assistance';

const mockProgram: AssistanceProgramSchema = {
  id: '1', title: 'Bantuan Beras', slug: 'bantuan-beras',
  description: 'Bantuan beras untuk warga', badge: 'Aktif',
  banner_url: null, start_date: null, end_date: null,
  quota_total: 100, benefit_amount: 500000,
  inputs: [], documents: [], has_submitted: false,
};

function mountComponent(props: Record<string, unknown> = {}) {
  return mount(ProgramSelectSection, {
    props: {
      citizenName: 'Joko',
      programs: [],
      selectedId: null,
      loading: false,
      ...props,
    },
  });
}

describe('ProgramSelectSection.vue', () => {

  // ===== HAPPY PATH =====

  it('test_menampilkan_daftar_program', () => {
    const wrapper = mountComponent({ programs: [mockProgram] });
    expect(wrapper.text()).toContain('Bantuan Beras');
  });

  it('test_meng_emit_select_saat_klik_program', async () => {
    const wrapper = mountComponent({ programs: [mockProgram] });
    await wrapper.find('.cursor-pointer').trigger('click');
    expect(wrapper.emitted('select')).toBeTruthy();
  });

  it('test_menampilkan_nama_warga', () => {
    const wrapper = mountComponent({ citizenName: 'Joko', programs: [mockProgram] });
    expect(wrapper.text()).toContain('Joko');
  });

  // ===== SAD PATH =====

  it('test_menampilkan_loading_skeleton', () => {
    const wrapper = mountComponent({ loading: true });
    expect(wrapper.find('.animate-pulse').exists()).toBe(true);
  });

  it('test_menampilkan_empty_state', () => {
    const wrapper = mountComponent({ programs: [] });
    expect(wrapper.text()).toContain('Tidak ada program aktif');
  });

  // ===== DATA TYPE =====

  it('test_menampilkan_benefit_amount', () => {
    const wrapper = mountComponent({ programs: [mockProgram] });
    expect(wrapper.text()).toContain('Rp');
  });

  it('test_menampilkan_kuota', () => {
    const wrapper = mountComponent({ programs: [mockProgram] });
    expect(wrapper.text()).toContain('kuota');
  });
});