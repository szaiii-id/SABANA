import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import VerificationTable from '../VerificationTable.vue';

describe('VerificationTable.vue', () => {
  const mockVerifications = [
    {
      id: '1', registration_number: 'SBN-001', status: 'pending',
      smart_score: 85.5,
      recommendation: { label: 'Sangat Direkomendasikan', color: 'green' },
      citizen: { id: 'c1', nik: '6301234567890123', full_name: 'Muhammad Noor' },
      program: { id: 'p1', name: 'BLT' },
      wilayah: { village: 'Desa A', district: 'Kec A', regency: 'Kab A' },
      created_at: '2026-01-01',
    },
  ];

  function mountComponent(props: any = {}) {
    return mount(VerificationTable, {
      props: {
        showCheckbox: false,
        verifications: [],
        loading: false,
        currentPage: 1,
        totalPages: 1,
        total: 0,
        selectedIds: [],
        ...props,
      },
    });
  }

  it('test_menampilkan_data_warga', () => {
    const wrapper = mountComponent({ verifications: mockVerifications, total: 1 });
    expect(wrapper.text()).toContain('Muhammad Noor');
    expect(wrapper.text()).toContain('6301234567890123');
  });

  it('test_menampilkan_nama_program', () => {
    const wrapper = mountComponent({ verifications: mockVerifications, total: 1 });
    expect(wrapper.text()).toContain('BLT');
  });

  it('test_menampilkan_skor_smart', () => {
    const wrapper = mountComponent({ verifications: mockVerifications, total: 1 });
    expect(wrapper.text()).toContain('85.5');
  });

  it('test_menampilkan_status_pending', () => {
    const wrapper = mountComponent({ verifications: mockVerifications, total: 1 });
    expect(wrapper.text()).toContain('Pending');
  });

  it('test_menampilkan_loading', () => {
    const wrapper = mountComponent({ loading: true });
    expect(wrapper.text()).toContain('Memuat data');
  });

  it('test_menampilkan_pesan_kosong', () => {
    const wrapper = mountComponent({ verifications: [], total: 0 });
    expect(wrapper.text()).toContain('Tidak ada antrean verifikasi');
  });

  it('test_menampilkan_checkbox_saat_showCheckbox_true', () => {
    const wrapper = mountComponent({ showCheckbox: true, verifications: mockVerifications, total: 1 });
    expect(wrapper.find('input[type="checkbox"]').exists()).toBe(true);
  });

  it('test_menampilkan_status_disetujui', () => {
    const verified = [{ ...mockVerifications[0], status: 'validated' }];
    const wrapper = mountComponent({ verifications: verified, total: 1 });
    expect(wrapper.text()).toContain('Disetujui');
  });
});