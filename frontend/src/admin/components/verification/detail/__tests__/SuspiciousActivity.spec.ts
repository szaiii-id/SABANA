import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import SuspiciousActivity from '../SuspiciousActivity.vue';

describe('SuspiciousActivity.vue', () => {
  const mockAnomalies = [
    {
      type: 'double_submit',
      severity: 'high',
      message: 'Warga sudah memiliki pengajuan aktif di program ini.',
    },
    {
      type: 'duplicate_nik',
      severity: 'medium',
      message: 'NIK ini sudah digunakan oleh warga lain.',
    },
    {
      type: 'blurry_document',
      severity: 'low',
      message: 'Dokumen ktp tidak terbaca dengan jelas.',
    },
  ];

  function mountComponent(props: any = {}) {
    return mount(SuspiciousActivity, {
      props: {
        anomalies: mockAnomalies,
        ...props,
      },
    });
  }

  // =============================================
  // RENDERING
  // =============================================

  it('test_menampilkan_heading_aktivitas_mencurigakan', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Aktivitas Mencurigakan');
  });

  it('test_menampilkan_jumlah_anomali', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('3 terdeteksi');
  });

  it('test_menampilkan_semua_anomali', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Warga sudah memiliki pengajuan aktif');
    expect(wrapper.text()).toContain('NIK ini sudah digunakan oleh warga lain');
    expect(wrapper.text()).toContain('Dokumen ktp tidak terbaca dengan jelas');
  });

  // =============================================
  // SEVERITY BADGE
  // =============================================

  it('test_menampilkan_badge_tinggi', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Tinggi');
  });

  it('test_menampilkan_badge_sedang', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Sedang');
  });

  it('test_menampilkan_badge_rendah', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Rendah');
  });

  // =============================================
  // EMPTY
  // =============================================

  it('test_tidak_merender_jika_anomalies_kosong', () => {
    const wrapper = mountComponent({ anomalies: [] });
    expect(wrapper.text()).toBe('');
  });

  it('test_tidak_merender_jika_anomalies_null', () => {
    const wrapper = mountComponent({ anomalies: null });
    expect(wrapper.text()).toBe('');
  });

  // =============================================
  // SINGLE ANOMALY
  // =============================================

  it('test_menampilkan_satu_anomali', () => {
    const wrapper = mountComponent({
      anomalies: [mockAnomalies[0]],
    });
    expect(wrapper.text()).toContain('1 terdeteksi');
  });
});