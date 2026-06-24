import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import DisburseBulkModal from '../DisburseBulkModal.vue';

describe('DisburseBulkModal.vue', () => {
  function mountComponent(props: any = {}) {
    return mount(DisburseBulkModal, {
      props: {
        open: true,
        count: 5,
        isSubmitting: false,
        ...props,
      },
    });
  }

  it('test_menampilkan_heading_penyaluran_massal', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Konfirmasi Penyaluran Massal');
  });

  it('test_menampilkan_jumlah', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('5');
  });

  it('test_emit_close_saat_klik_batal', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const batalBtn = Array.from(buttons).find(b => b.text().includes('Batal'));
    if (batalBtn) await batalBtn.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  it('test_emit_confirm_saat_klik_salurkan_semua', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const confirmBtn = Array.from(buttons).find(b => b.text().includes('Salurkan Semua'));
    if (confirmBtn) await confirmBtn.trigger('click');
    expect(wrapper.emitted('confirm')).toBeTruthy();
  });

  it('test_tombol_disabled_saat_submitting', () => {
    const wrapper = mountComponent({ isSubmitting: true });
    const buttons = wrapper.findAll('button');
    const confirmBtn = Array.from(buttons).find(b => b.text().includes('Salurkan Semua'));
    if (confirmBtn) expect(confirmBtn.attributes('disabled')).toBeDefined();
  });

  it('test_modal_tidak_dirender_saat_open_false', () => {
    const wrapper = mountComponent({ open: false });
    expect(wrapper.text()).toBe('');
  });
});