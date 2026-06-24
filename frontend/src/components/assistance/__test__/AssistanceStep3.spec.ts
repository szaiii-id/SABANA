// =============================================
// AssistanceStep3.spec.ts — FULL CODE
// =============================================

import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AssistanceStep3 from '../AssistanceStep3.vue';
import type { AssistanceProgramSchema, AssistanceSubmissionPayload, FormDocumentSchema } from '../../../types/assistance';

const mockDocuments: FormDocumentSchema[] = [
  { key: 'ktp', label: 'KTP', required: true },
  { key: 'kk', label: 'Kartu Keluarga', required: true },
  { key: 'foto_rumah', label: 'Foto Rumah (Opsional)', required: false },
];

const mockProgram: AssistanceProgramSchema = {
  id: '1', title: 'BLT', slug: 'blt', description: '', badge: '',
  banner_url: null, start_date: null, end_date: null, quota_total: null, benefit_amount: null,
  inputs: [], documents: mockDocuments,
  has_submitted: false,
};

const mockFormData: AssistanceSubmissionPayload = {
  program_id: '1', regency_id: '6301', district_id: '6301020', village_id: '6301020001',
  disbursement_method: 'village_cash', bank_account_number: '',
  dynamicInputs: {}, files: {} as Record<string, File>,
};

function mountComponent(props: Record<string, unknown> = {}) {
  return mount(AssistanceStep3, {
    props: {
      formData: mockFormData,
      selectedProgram: mockProgram,
      isLoading: false,
      ...props,
    },
    global: {
      stubs: {
        CloudArrowUpIcon: { template: '<div/>' },
        PhotoIcon: { template: '<div/>' },
        CheckCircleIcon: { template: '<div/>' },
      },
    },
  });
}

describe('AssistanceStep3.vue', () => {

  // ===== HAPPY PATH =====

  it('test_menampilkan_heading_verifikasi_berkas', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Verifikasi Berkas');
  });

  it('test_menampilkan_semua_dokumen', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('KTP');
    expect(wrapper.text()).toContain('Kartu Keluarga');
  });

  it('test_menampilkan_tombol_kirim_pengajuan', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('KIRIM PENGAJUAN');
  });

  it('test_menampilkan_tombol_kembali', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Kembali');
  });

  // ===== SAD PATH =====

  it('test_validasi_gagal_jika_dokumen_wajib_kosong', async () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { validateAndSubmit: () => void; fieldErrors: Record<string, string> };
    vm.validateAndSubmit();
    await (wrapper.vm as unknown as { $nextTick: () => Promise<void> }).$nextTick();
    expect(vm.fieldErrors.ktp).toBeTruthy();
    expect(vm.fieldErrors.kk).toBeTruthy();
  });

  // ===== BOUNDARY — UPLOAD > 2MB =====

  it('test_validasi_gagal_jika_file_lebih_dari_2mb', async () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { 
      handleFileUpload: (e: Event, key: string) => void; 
      fieldErrors: Record<string, string>;
    };
    
    const file = new File(['x'.repeat(3 * 1024 * 1024)], 'large.jpg', { type: 'image/jpeg' });
    const event = { target: { files: [file], value: '' } } as unknown as Event;
    
    vm.handleFileUpload(event, 'ktp');
    await (wrapper.vm as unknown as { $nextTick: () => Promise<void> }).$nextTick();
    
    expect(vm.fieldErrors.ktp).toBeTruthy();
    expect(vm.fieldErrors.ktp).toContain('2MB');
  });

  // ===== EDGE CASE =====

  it('test_menampilkan_label_opsional', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Boleh Kosong');
  });

  it('test_emit_prev_saat_klik_kembali', async () => {
    const wrapper = mountComponent();
    const buttons = wrapper.findAll('button');
    await buttons[0].trigger('click');
    expect(wrapper.emitted('prev')).toBeTruthy();
  });

  // ===== NULL/EMPTY =====

  it('test_menampilkan_loading_saat_isLoading_true', () => {
    const wrapper = mountComponent({ isLoading: true });
    expect(wrapper.text()).toContain('MENGIRIM');
  });

  // ===== DATA TYPE =====

  it('test_menampilkan_wajib_upload_ulang_untuk_revision_items', () => {
    const wrapper = mountComponent({ revisionItems: ['ktp'] });
    expect(wrapper.text()).toContain('Wajib Upload Ulang');
  });
});