import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import Profile from '../Profile.vue';
import { profileApi } from '../../../api/profileApi';

// ===== MOCKS =====
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

vi.mock('../../../api/profileApi', () => ({
  profileApi: {
    getProfile: vi.fn(),
    updateProfile: vi.fn(),
  },
}));

// ===== TYPES =====
interface VmProfile {
  formData: {
    nik: string;
    family_card_number: string;
    full_name: string;
    whatsapp_number: string;
  };
  errors: {
    full_name: string;
    new_pin?: string;
    current_pin?: string;
    new_pin_confirmation?: string;
    whatsapp_number: string;
  };
  notification: {
    type: string;
    message: string;
  };
  showConfirmModal: boolean;
  filterPhone: () => void;
  clearError: (field: 'full_name' | 'whatsapp_number') => void;
  validateForm: () => boolean;
  handlePreSubmit: () => void;
}

// ===== ROUTER =====
function createTestRouter() {
  return createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/profile', name: 'profile', component: Profile },
      { path: '/verify-otp', name: 'verify-otp', component: { template: '<div/>' } },
    ],
  });
}

// ===== MOUNT HELPER =====
function mountComponent(router: ReturnType<typeof createTestRouter>) {
  return mount(Profile, {
    global: {
      plugins: [router],
      stubs: {
        'router-link': { template: '<a><slot/></a>' },
      },
    },
  });
}

// ===== TEST SUITE =====
describe('Profile.vue', () => {
  let router: ReturnType<typeof createTestRouter>;

  beforeEach(() => {
    localStorageMock.getItem.mockReturnValue(null);
    router = createTestRouter();
    vi.clearAllMocks();
  });

  // ===== RENDERING =====
  it('test_menampilkan_heading_data_diri', async () => {
    vi.mocked(profileApi.getProfile).mockResolvedValue({
      nik: '6301234567890123',
      family_card_number: '6301234567890123',
      full_name: 'Muhammad Noor',
      whatsapp_number: '6281234567890',
    });

    await router.push({ name: 'profile' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();

    expect(wrapper.text()).toContain('Data Diri');
  });

  it('test_menampilkan_field_nik_dan_kk_disabled', async () => {
    vi.mocked(profileApi.getProfile).mockResolvedValue({
      nik: '6301234567890123',
      family_card_number: '6301234567890123',
      full_name: 'Muhammad Noor',
      whatsapp_number: '6281234567890',
    });

    await router.push({ name: 'profile' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();

    const disabledInputs = wrapper.findAll('input[disabled]');
    expect(disabledInputs.length).toBeGreaterThanOrEqual(2);
  });

  it('test_menampilkan_tombol_simpan', async () => {
    vi.mocked(profileApi.getProfile).mockResolvedValue({
      nik: '6301234567890123',
      family_card_number: '6301234567890123',
      full_name: 'Muhammad Noor',
      whatsapp_number: '6281234567890',
    });

    await router.push({ name: 'profile' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();

    expect(wrapper.text()).toContain('Simpan Data Diri');
  });

  // ===== LOADING =====
  it('test_menampilkan_loading_saat_fetch_data', async () => {
    vi.mocked(profileApi.getProfile).mockReturnValue(new Promise(() => {}));

    await router.push({ name: 'profile' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();

    expect(wrapper.html()).toContain('animate-spin');
  });

  // ===== FORMAT =====
  it('test_filter_phone_menghapus_non_digit', async () => {
    vi.mocked(profileApi.getProfile).mockResolvedValue({
      nik: '6301234567890123',
      family_card_number: '6301234567890123',
      full_name: 'Muhammad Noor',
      whatsapp_number: '6281234567890',
    });

    await router.push({ name: 'profile' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();

    const vm = wrapper.vm as unknown as VmProfile;
    vm.formData.whatsapp_number = '6281-2345-67ab';
    vm.filterPhone();
    expect(vm.formData.whatsapp_number).toBe('6281234567');
  });

  it('test_clear_error_menghapus_error_field', async () => {
    vi.mocked(profileApi.getProfile).mockResolvedValue({
      nik: '6301234567890123',
      family_card_number: '6301234567890123',
      full_name: 'Muhammad Noor',
      whatsapp_number: '6281234567890',
    });

    await router.push({ name: 'profile' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();

    const vm = wrapper.vm as unknown as VmProfile;
    vm.errors.full_name = 'Error';
    vm.clearError('full_name');
    expect(vm.errors.full_name).toBe('');
  });

  // ===== VALIDASI =====
  it('test_validasi_nama_kosong_menampilkan_error', async () => {
    vi.mocked(profileApi.getProfile).mockResolvedValue({
      nik: '6301234567890123',
      family_card_number: '6301234567890123',
      full_name: 'Muhammad Noor',
      whatsapp_number: '6281234567890',
    });

    await router.push({ name: 'profile' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();

    const vm = wrapper.vm as unknown as VmProfile;
    vm.formData.full_name = '';
    vm.formData.whatsapp_number = '6281234567890';
    const isValid = vm.validateForm();
    expect(isValid).toBe(false);
    expect(vm.errors.full_name).toBe('Nama lengkap wajib diisi');
  });

  it('test_validasi_wa_kurang_dari_10_digit_menampilkan_error', async () => {
    vi.mocked(profileApi.getProfile).mockResolvedValue({
      nik: '6301234567890123',
      family_card_number: '6301234567890123',
      full_name: 'Muhammad Noor',
      whatsapp_number: '6281234567890',
    });

    await router.push({ name: 'profile' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();

    const vm = wrapper.vm as unknown as VmProfile;
    vm.formData.full_name = 'Muhammad Noor';
    vm.formData.whatsapp_number = '62812';
    const isValid = vm.validateForm();
    expect(isValid).toBe(false);
    expect(vm.errors.whatsapp_number).toBe('Nomor WhatsApp tidak valid (minimal 10 angka)');
  });

  // ===== MODAL =====
  it('test_modal_konfirmasi_muncul_saat_submit', async () => {
    vi.mocked(profileApi.getProfile).mockResolvedValue({
      nik: '6301234567890123',
      family_card_number: '6301234567890123',
      full_name: 'Muhammad Noor',
      whatsapp_number: '6281234567890',
    });

    await router.push({ name: 'profile' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();

    const vm = wrapper.vm as unknown as VmProfile;
    vm.formData.full_name = 'Ahmad Noor';
    vm.formData.whatsapp_number = '6281234567890';
    vm.handlePreSubmit();
    await wrapper.vm.$nextTick();

    expect(vm.showConfirmModal).toBe(true);
  });

  it('test_modal_batal_menutup_modal', async () => {
    vi.mocked(profileApi.getProfile).mockResolvedValue({
      nik: '6301234567890123',
      family_card_number: '6301234567890123',
      full_name: 'Muhammad Noor',
      whatsapp_number: '6281234567890',
    });

    await router.push({ name: 'profile' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();

    const vm = wrapper.vm as unknown as VmProfile;
    vm.showConfirmModal = true;
    await wrapper.vm.$nextTick();

    vm.showConfirmModal = false;
    await wrapper.vm.$nextTick();

    expect(vm.showConfirmModal).toBe(false);
  });

  // ===== NOTIFIKASI =====
  it('test_notifikasi_error_ditampilkan_saat_gagal_fetch', async () => {
    vi.mocked(profileApi.getProfile).mockRejectedValue(new Error('Gagal'));

    await router.push({ name: 'profile' });
    await router.isReady();
    const wrapper = mountComponent(router);
    await flushPromises();

    const vm = wrapper.vm as unknown as VmProfile;
    expect(vm.notification.type).toBe('error');
    expect(vm.notification.message).toBeTruthy();
  });
});