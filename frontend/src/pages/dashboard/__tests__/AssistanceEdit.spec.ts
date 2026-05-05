// src/pages/dashboard/__tests__/AssistanceEdit.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

// 1. Buat mock functions di luar factory
const fetchDetailMock = vi.fn();
const updateAssistanceMock = vi.fn();
const fetchProgramsMock = vi.fn().mockResolvedValue(undefined);
const fetchRegenciesMock = vi.fn().mockResolvedValue(undefined);
const fetchDistrictsMock = vi.fn().mockResolvedValue(undefined);
const fetchVillagesMock = vi.fn().mockResolvedValue(undefined);

// 2. Mock composable menggunakan variabel yang sama
vi.mock('../../../composables/useAssistance', () => ({
  useAssistance: () => ({
    fetchDetail: fetchDetailMock,
    updateAssistance: updateAssistanceMock,
    fetchPrograms: fetchProgramsMock,
    fetchRegencies: fetchRegenciesMock,
    fetchDistricts: fetchDistrictsMock,
    fetchVillages: fetchVillagesMock,
    programs: { value: [] },
    regencies: { value: [] },
    districts: { value: [] },
    villages: { value: [] },
    isLoading: { value: false },
  }),
}));

// 3. Mock axios
vi.mock('axios', () => ({
  default: {
    create: vi.fn(() => ({
      get: vi.fn(),
      post: vi.fn(),
      put: vi.fn(),
      delete: vi.fn(),
      interceptors: {
        request: { use: vi.fn() },
        response: { use: vi.fn() },
      },
    })),
  },
}));

// 4. Mock router
const mockPush = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: mockPush }),
}));

// 5. Import setelah mock
import AssistanceEdit from '../AssistanceEdit.vue';

// 6. Setup localStorage
vi.stubGlobal('localStorage', {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
});

function createMockDetailData() {
  return {
    id: 'uuid-edit',
    program_id: '1',
    registration_number: 'REG-001',
    disbursement_method: 'village_cash',
    bank_account_number: '',
    regency_id: '1',
    district_id: '2',
    village_id: '3',
    submission_data: {},
    evidences: [],
  };
}

describe('AssistanceEdit.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    
    // Reset localStorage
    (localStorage.getItem as ReturnType<typeof vi.fn>).mockReturnValue(
      JSON.stringify({ id: 'uuid-edit' })
    );
  });

  const mountComponent = () => {
    return mount(AssistanceEdit, {
      global: {
        stubs: {
          MapPinIcon: true,
          CreditCardIcon: true,
          IdentificationIcon: true,
          CloudArrowUpIcon: true,
          PhotoIcon: true,
          CheckCircleIcon: true,
          CheckBadgeIcon: true,
          RocketLaunchIcon: true,
          ArrowPathIcon: true,
          AssistanceStep2: true,
          AssistanceStep3: true,
        },
      },
    });
  };

  it('1 - memuat data detail saat mounted', async () => {
    fetchDetailMock.mockResolvedValue(createMockDetailData());

    mountComponent();
    await flushPromises();

    expect(fetchDetailMock).toHaveBeenCalledWith('uuid-edit');
  });

  it('2 - redirect jika tidak ada draft', async () => {
    (localStorage.getItem as ReturnType<typeof vi.fn>).mockReturnValue(null);

    mountComponent();
    await flushPromises();

    expect(mockPush).toHaveBeenCalledWith({ name: 'history' });
  });

  it('3 - menampilkan error jika fetchDetail gagal', async () => {
    // PERBAIKAN: mockRejectedValue -> mockRejectedValue
    fetchDetailMock.mockRejectedValue(new Error('Network error'));

    const wrapper = mountComponent();
    await flushPromises();
    await wrapper.vm.$nextTick();

    const vm = wrapper.vm as any;
    expect(vm.notification.message).toContain('Gagal');
  });

  it('4 - memanggil API update', async () => {
    fetchDetailMock.mockResolvedValue(createMockDetailData());
    updateAssistanceMock.mockResolvedValue({ success: true });

    const wrapper = mountComponent();
    await flushPromises();
    await wrapper.vm.$nextTick();

    const vm = wrapper.vm as any;
    
    vm.isDataReady = true;
    vm.currentStep = 3;
    vm.formData.program_id = '1';
    vm.formData.regency_id = '1';
    vm.formData.district_id = '2';
    vm.formData.village_id = '3';
    
    await wrapper.vm.$nextTick();

    vm.showConfirmModal = true;
    await wrapper.vm.$nextTick();
    await vm.executeUpdate();
    await flushPromises();
    await wrapper.vm.$nextTick();

    expect(updateAssistanceMock).toHaveBeenCalledWith(
      'uuid-edit',
      expect.objectContaining({
        program_id: '1',
        regency_id: '1',
        district_id: '2',
        village_id: '3',
      })
    );
  });

  it('5 - menampilkan error jika update gagal', async () => {
    fetchDetailMock.mockResolvedValue(createMockDetailData());
    updateAssistanceMock.mockRejectedValue({
      response: { data: { message: 'Gagal menyimpan perubahan.' } },
    });

    const wrapper = mountComponent();
    await flushPromises();
    await wrapper.vm.$nextTick();

    const vm = wrapper.vm as any;
    vm.isDataReady = true;
    vm.currentStep = 3;
    vm.formData.program_id = '1';
    vm.formData.regency_id = '1';
    vm.formData.district_id = '2';
    vm.formData.village_id = '3';
    
    await wrapper.vm.$nextTick();

    vm.showConfirmModal = true;
    await wrapper.vm.$nextTick();
    await vm.executeUpdate();
    await flushPromises();
    await wrapper.vm.$nextTick();

    expect(vm.notification.message).toBe('Gagal menyimpan perubahan.');
  });
});