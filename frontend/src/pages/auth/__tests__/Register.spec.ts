import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Register from '../Register.vue';
import { useAuth } from '../../../composables/useAuth';

// MOCK ROUTER
const pushMock = vi.fn();
const replaceMock = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock, replace: replaceMock })
}));

vi.mock('../../../composables/useAuth', () => ({
  useAuth: vi.fn(),
}));

describe('Register.vue Component', () => {
  let submitRegistrationMock: any;

  beforeEach(() => {
    submitRegistrationMock = vi.fn().mockResolvedValue({ success: true, phone: '08123456789' });
    (useAuth as any).mockReturnValue({
      isSubmitting: { value: false },
      authError: { value: '' },
      submitRegistration: submitRegistrationMock,
    });
    vi.clearAllMocks();
  });

  it('merender semua input pendaftaran', () => {
    const wrapper = mount(Register, {
      global: {
        stubs: ['router-link', 'SuccessModal'],
      }
    });
    
    // Gunakan find dengan tipe input atau id jika ada, atau selector yang lebih umum
    expect(wrapper.find('input').exists()).toBe(true);
    expect(wrapper.findAll('input').length).toBeGreaterThanOrEqual(5);
  });

  it('menampilkan pesan error jika PIN tidak cocok', async () => {
    const wrapper = mount(Register, {
      global: { stubs: ['router-link', 'SuccessModal'] }
    });

    const pinInputs = wrapper.findAll('input[type="password"]');
    await pinInputs[0].setValue('123456');
    await pinInputs[1].setValue('654321');
    await pinInputs[1].trigger('blur');

    // Berdasarkan output terminal Mas, teks yang muncul adalah "PIN tidak cocok"
    expect(wrapper.text()).toContain('PIN tidak cocok');
  });

  it('memanggil submitRegistration saat data valid', async () => {
    const wrapper = mount(Register, {
      global: { stubs: ['router-link', 'SuccessModal'] }
    });

    const inputs = wrapper.findAll('input');
    await inputs[0].setValue('6301012345678901');
    await inputs[1].setValue('6301012345678901');
    await inputs[2].setValue('Akhmad Jainudin');
    await inputs[3].setValue('08123456789');
    await inputs[4].setValue('123456');
    await inputs[5].setValue('123456');

    // 2. Gunakan trigger dan tunggu async selesai
    await wrapper.find('form').trigger('submit.prevent');
    
    // Memberikan waktu untuk proses async/await di dalam onSubmit
    await new Promise(resolve => setTimeout(resolve, 0));

    expect(submitRegistrationMock).toHaveBeenCalled();
    // Opsional: cek apakah redirect ke verify-otp terpanggil
    expect(replaceMock).toHaveBeenCalled();
  });
});