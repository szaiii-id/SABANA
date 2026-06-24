import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import SubmissionFormSection from '../SubmissionFormSection.vue';
import type { AssistanceProgramSchema, AssistanceSubmissionPayload } from '../../../../types/assistance';
import type { Region } from '../../../composables/useRegion';

const mockProgram: AssistanceProgramSchema = {
  id: '1', title: 'Bantuan Beras', slug: 'bantuan-beras',
  description: '', badge: 'Aktif',
  banner_url: null, start_date: null, end_date: null,
  quota_total: null, benefit_amount: null,
  inputs: [{ key: 'nama_ibu', label: 'Nama Ibu', type: 'text' }],
  documents: [{ key: 'ktp', label: 'KTP', required: true }],
  has_submitted: false,
};

const mockFormData: AssistanceSubmissionPayload = {
  program_id: '1', regency_id: '', district_id: '', village_id: '',
  disbursement_method: 'village_cash', bank_account_number: '',
  dynamicInputs: {}, files: {} as Record<string, File>,
};

const mockRegencies: Region[] = [];
const mockDistricts: Region[] = [];
const mockVillages: Region[] = [];

function mountComponent(props: Record<string, unknown> = {}) {
  return mount(SubmissionFormSection, {
    props: {
      formData: mockFormData,
      selectedProgram: mockProgram,
      regencies: mockRegencies,
      districts: mockDistricts,
      villages: mockVillages,
      isSubmitting: false,
      error: '',
      success: '',
      registrationNumber: '',
      ...props,
    },
    global: {
      stubs: {
        AssistanceStep2: { template: '<div>Step2</div>', name: 'AssistanceStep2' },
        AssistanceStep3: { template: '<div>Step3</div>', name: 'AssistanceStep3' },
      },
    },
  });
}

describe('SubmissionFormSection.vue', () => {

  // ===== HAPPY PATH =====

  it('test_menampilkan_step_indicator', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Data');
    expect(wrapper.text()).toContain('Berkas');
  });

  it('test_menampilkan_form_step_2', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Step2');
  });

  // ===== SAD PATH =====

  it('test_menampilkan_pesan_error', () => {
    const wrapper = mountComponent({ error: 'Gagal mengajukan.' });
    expect(wrapper.text()).toContain('Gagal mengajukan.');
  });

  // ===== NULL/EMPTY =====

  it('test_menampilkan_loading_overlay_saat_submitting', () => {
    const wrapper = mountComponent({ isSubmitting: true });
    expect(wrapper.text()).toContain('MENGUNGGAH BERKAS');
  });

  // ===== STATE TRANSITION =====

  it('test_menampilkan_success_state', () => {
    const wrapper = mountComponent({ success: 'Berhasil!', registrationNumber: 'SBN-ABC' });
    expect(wrapper.text()).toContain('Berhasil!');
    expect(wrapper.text()).toContain('SBN-ABC');
  });

  // ===== EMIT =====

  it('test_meng_emit_regencyChange', async () => {
    const wrapper = mountComponent();
    const step2 = wrapper.findComponent({ name: 'AssistanceStep2' });
    expect(step2.exists()).toBe(true);
    await step2.vm.$emit('regencyChange');
    expect(wrapper.emitted('regencyChange')).toBeTruthy();
  });

  it('test_meng_emit_submit', async () => {
    const wrapper = mountComponent();
    const vm = wrapper.vm as unknown as { currentStep: number };
    vm.currentStep = 3;
    await wrapper.vm.$nextTick();

    const step3 = wrapper.findComponent({ name: 'AssistanceStep3' });
    expect(step3.exists()).toBe(true);
    await step3.vm.$emit('submit');
    expect(wrapper.emitted('submit')).toBeTruthy();
  });
});