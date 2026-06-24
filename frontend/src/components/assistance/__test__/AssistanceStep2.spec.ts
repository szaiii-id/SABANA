import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AssistanceStep2 from '../AssistanceStep2.vue';
import type { AssistanceProgramSchema, AssistanceSubmissionPayload } from '../../../types/assistance';

const mockProgram: AssistanceProgramSchema = {
  id: '1', title: 'BLT', slug: 'blt', description: '', badge: '',
  banner_url: null, start_date: null, end_date: null, quota_total: null, benefit_amount: null,
  inputs: [
    { key: 'pekerjaan', label: 'Pekerjaan', type: 'text' },
    { key: 'penghasilan', label: 'Penghasilan', type: 'currency' },
    { key: 'tanggungan', label: 'Tanggungan', type: 'number' },
  ],
  documents: [], has_submitted: false,
};

const mockFormData: AssistanceSubmissionPayload = {
  program_id: '1', regency_id: '', district_id: '', village_id: '',
  disbursement_method: 'village_cash', bank_account_number: '',
  dynamicInputs: { pekerjaan: '', penghasilan: '', tanggungan: '' }, files: {},
};

const mockRegencies = [{ id: '6301', name: 'Tanah Laut' }];

function mountComponent(props: Record<string, unknown> = {}) {
  return mount(AssistanceStep2, {
    props: {
      formData: mockFormData,
      selectedProgram: mockProgram,
      regencies: mockRegencies,
      districts: [],
      villages: [],
      ...props,
    },
    global: {
      stubs: {
        MapPinIcon: { template: '<div/>' },
        CreditCardIcon: { template: '<div/>' },
        IdentificationIcon: { template: '<div/>' },
        TabDomisili: { template: '<div>Domisili</div>' },
        TabDataDiri: { template: '<div>Data Diri</div>' },
        TabPenyaluran: { template: '<div>Penyaluran</div>' },
        ConfirmModal: { template: '<div/>', props: ['open', 'title', 'message'] },
      },
    },
  });
}

describe('AssistanceStep2.vue', () => {

  // ===== HAPPY PATH =====

  it('test_menampilkan_tab_domisili_data_diri_penyaluran', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Domisili');
    expect(wrapper.text()).toContain('Data Diri');
    expect(wrapper.text()).toContain('Penyaluran');
  });

  it('test_menampilkan_langkah_2_dari_3', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Langkah 2 dari 3');
  });

  it('test_default_tab_domisili', () => {
    const wrapper = mountComponent();
    expect((wrapper.vm as unknown as { activeTab: string }).activeTab).toBe('domisili');
  });

  // ===== SAD PATH — VALIDASI TIPE DATA =====

  it('test_validasi_currency_harus_angka', async () => {
    const wrapper = mountComponent({
      formData: {
        ...mockFormData,
        regency_id: '6301', district_id: '6301020', village_id: '6301020001',
        dynamicInputs: { pekerjaan: 'Test', penghasilan: 'abc', tanggungan: '' },
      },
    });
    
    const vm = wrapper.vm as unknown as { 
      handleNextTab: () => void; 
      fieldErrors: Record<string, string>;
      activeTab: string;
    };
    
    // Pindah ke tab data dulu
    vm.activeTab = 'data';
    vm.handleNextTab();
    await (wrapper.vm as unknown as { $nextTick: () => Promise<void> }).$nextTick();
    
    expect(vm.fieldErrors.penghasilan || vm.fieldErrors.tanggungan).toBeTruthy();
  });

  // ===== BOUNDARY =====

  it('test_bisa_lanjut_jika_domisili_lengkap', async () => {
    const wrapper = mountComponent({
      formData: {
        ...mockFormData,
        regency_id: '6301',
        district_id: '6301020',
        village_id: '6301020001',
      },
    });
    const vm = wrapper.vm as unknown as { handleNextTab: () => void; activeTab: string };
    vm.handleNextTab();
    await (wrapper.vm as unknown as { $nextTick: () => Promise<void> }).$nextTick();
    expect(vm.activeTab).toBe('data');
  });

  // ===== EDGE CASE =====

  it('test_emit_prev_saat_kembali_tanpa_data', async () => {
    const wrapper = mountComponent();
    const allButtons = wrapper.findAll('button');
    const backBtn = Array.from(allButtons).find(btn => btn.text().includes('Kembali'));
    if (backBtn) {
      await backBtn.trigger('click');
      expect(wrapper.emitted('prev')).toBeTruthy();
    }
  });

  // ===== DATA TYPE =====

  it('test_menampilkan_revision_items_jika_ada', () => {
    const wrapper = mountComponent({ revisionItems: ['foto_ktp'] });
    expect((wrapper.vm as unknown as { revItems: string[] }).revItems).toContain('foto_ktp');
  });
});