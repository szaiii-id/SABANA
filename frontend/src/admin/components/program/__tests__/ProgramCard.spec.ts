import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ProgramCard from '../ProgramCard.vue';
import type { ProgramData } from '../../../types/program';

describe('ProgramCard.vue', () => {
  const mockProgram: ProgramData = {
    id: '1',
    name: 'BLT Dana Desa',
    slug: 'blt',
    description: 'Bantuan Langsung Tunai untuk warga',
    criteria: null,
    start_date: '2026-01-01',
    end_date: '2026-12-31',
    quota_total: 1000,
    benefit_amount: 500000,
    banner_url: null,
    status: 'active',
    is_active: true,
    total_anggaran: null,
    created_at: '2026-01-01',
    updated_at: '2026-01-01',
  };

  function mountComponent(program: ProgramData = mockProgram) {
    return mount(ProgramCard, {
      props: { program },
    });
  }

  it('test_menampilkan_nama_program', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('BLT Dana Desa');
  });

  it('test_menampilkan_status_active', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Aktif');
  });

  it('test_menampilkan_status_draft', () => {
    const wrapper = mountComponent({ ...mockProgram, status: 'draft' });
    expect(wrapper.text()).toContain('Draft');
  });

  it('test_menampilkan_status_closed', () => {
    const wrapper = mountComponent({ ...mockProgram, status: 'closed' });
    expect(wrapper.text()).toContain('Tertutup');
  });

  it('test_menampilkan_deskripsi', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Bantuan Langsung Tunai');
  });

  it('test_menampilkan_kuota', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Kuota');
  });

  it('test_menampilkan_nilai_bantuan', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Rp');
  });

  it('test_emit_edit_saat_klik_edit', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const editBtn = Array.from(buttons).find(b => b.text().includes('Edit'));
    if (editBtn) await editBtn.trigger('click');
    expect(wrapper.emitted('edit')).toBeTruthy();
  });

  it('test_emit_close_saat_status_active', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const closeBtn = Array.from(buttons).find(b => b.text().includes('Tutup'));
    if (closeBtn) await closeBtn.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  it('test_tombol_reopen_untuk_status_closed', () => {
    const wrapper = mountComponent({ ...mockProgram, status: 'closed' });
    expect(wrapper.text()).toContain('Buka');
  });
});