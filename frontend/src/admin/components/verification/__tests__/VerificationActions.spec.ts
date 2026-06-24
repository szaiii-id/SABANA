import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import VerificationActions from '../VerificationActions.vue';

describe('VerificationActions.vue', () => {
  function mountComponent(props: any = {}) {
    return mount(VerificationActions, {
      props: {
        disabled: false,
        submitting: false,
        ...props,
      },
    });
  }

  it('test_menampilkan_tombol_setujui', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Setujui Pengajuan');
  });

  it('test_menampilkan_tombol_tolak', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Tolak');
  });

  it('test_menampilkan_tombol_revisi', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Revisi');
  });

  it('test_tombol_setujui_disabled_saat_disabled_true', () => {
    const wrapper = mountComponent({ disabled: true });
    expect(wrapper.text()).toContain('Periksa semua dokumen terlebih dahulu');
  });

  it('test_emit_approve_saat_klik_setujui', async () => {
    const wrapper = mountComponent();
    const approveBtn = wrapper.find('button');
    await approveBtn.trigger('click');
    expect(wrapper.emitted('approve')).toBeTruthy();
  });

  it('test_form_tolak_muncul_saat_klik_tolak', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const rejectBtn = Array.from(buttons).find(b => b.text().includes('Tolak'));
    if (rejectBtn) await rejectBtn.trigger('click');

    const vm = wrapper.vm as any;
    expect(vm.showRejectForm).toBe(true);
  });

  it('test_form_revisi_muncul_saat_klik_revisi', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    const revisionBtn = Array.from(buttons).find(b => b.text().includes('Revisi'));
    if (revisionBtn) await revisionBtn.trigger('click');

    const vm = wrapper.vm as any;
    expect(vm.showRevisionForm).toBe(true);
  });
});