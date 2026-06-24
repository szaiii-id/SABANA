import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import DisburseSingleModal from '../DisburseSingleModal.vue';

describe('DisburseSingleModal.vue', () => {
  const mockSubmission = {
    id: 'sub-1',
    registration_number: 'SBN-001',
    status: 'validated',
    citizen: { id: 'c1', full_name: 'Muhammad Noor', nik: '6301234567890123' },
    program: { id: 'p1', name: 'BLT', benefit_amount: 500000 },
    disbursement_method: 'village_cash',
  };

  function mountComponent(props: any = {}) {
    return mount(DisburseSingleModal, {
      props: {
        open: true,
        submission: mockSubmission,
        isSubmitting: false,
        ...props,
      },
    });
  }

  it('test_menampilkan_heading_konfirmasi', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Konfirmasi Penyaluran');
  });

  it('test_menampilkan_nama_warga', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Muhammad Noor');
  });

  it('test_menampilkan_nama_program', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('BLT');
  });

  it('test_menampilkan_jumlah_bantuan', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Rp');
  });

  it('test_menampilkan_metode_tunai', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Tunai');
  });

  it('test_emit_close_saat_klik_batal', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const batalBtn = Array.from(buttons).find(b => b.text().includes('Batal'));
    if (batalBtn) await batalBtn.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  it('test_emit_confirm_saat_klik_salurkan', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const confirmBtn = Array.from(buttons).find(b => b.text().includes('Salurkan'));
    if (confirmBtn) await confirmBtn.trigger('click');
    expect(wrapper.emitted('confirm')).toBeTruthy();
  });

  it('test_tombol_disabled_saat_submitting', () => {
    const wrapper = mountComponent({ isSubmitting: true });
    const buttons = wrapper.findAll('button');
    const confirmBtn = Array.from(buttons).find(b => b.text().includes('Salurkan'));
    if (confirmBtn) expect(confirmBtn.attributes('disabled')).toBeDefined();
  });

  it('test_modal_tidak_dirender_saat_open_false', () => {
    const wrapper = mountComponent({ open: false });
    expect(wrapper.text()).toBe('');
  });
});