import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import VerifyOtp from '../VerifyOtp.vue';
import { useAuth } from '../../../composables/useAuth';
import AuthService from '../../../services/AuthService';

const pushMock = vi.fn();
const replaceMock = vi.fn();

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock, replace: replaceMock }),
  useRoute: () => ({ query: { nik: '6301012345678901', wa: '08123456789' } })
}));

vi.mock('../../../composables/useAuth', () => ({
  useAuth: vi.fn(),
}));

vi.mock('../../../services/AuthService', () => ({
  default: {
    verifyOtp: vi.fn(),
    resendOtp: vi.fn(),
  },
}));

const locationMock = { href: '' };
vi.stubGlobal('location', locationMock);

describe('VerifyOtp.vue Component', () => {
  beforeEach(() => {
    (useAuth as any).mockReturnValue({
      isSubmitting: { value: false },
      authError: { value: '' },
    });
    vi.clearAllMocks();
  });

  const mountOptions = {
    global: { 
      stubs: ['router-link', 'SuccessModal'] 
    }
  };

  it('merender 6 kotak input OTP', () => {
    const wrapper = mount(VerifyOtp, mountOptions);
    const inputs = wrapper.findAll('input');
    expect(inputs.length).toBe(6);
  });

  it('memanggil AuthService.verifyOtp saat 6 digit terisi (via Watcher)', async () => {
    (AuthService.verifyOtp as any).mockResolvedValue({ success: true });
    const wrapper = mount(VerifyOtp, mountOptions);

    const inputs = wrapper.findAll('input');
    
    // Isi ke-6 input satu per satu untuk mentrigger watcher otpArray
    await inputs[0].setValue('1');
    await inputs[1].setValue('2');
    await inputs[2].setValue('3');
    await inputs[3].setValue('4');
    await inputs[4].setValue('5');
    await inputs[5].setValue('6');

    await flushPromises();

    expect(AuthService.verifyOtp).toHaveBeenCalledWith({
      nik: '6301012345678901',
      whatsapp_number: '08123456789',
      otp: '123456'
    });
  });

  it('menampilkan pesan error dari safeErrorMessage saat OTP salah', async () => {
    (AuthService.verifyOtp as any).mockRejectedValue({
      response: { 
        status: 422,
        data: { errors: { otp: ['Kode OTP tidak valid.'] } } 
      }
    });

    const wrapper = mount(VerifyOtp, mountOptions);
    const inputs = wrapper.findAll('input');
    
    // Isi 6 digit
    for(let i=0; i<6; i++) {
      await inputs[i].setValue('0');
    }

    await flushPromises();
    
    // Karena Mas pakai safeErrorMessage (computed), pastikan teksnya muncul
    expect(wrapper.text()).toContain('Kode OTP tidak valid.');
  });

  it('memanggil resendOtp saat tombol kirim ulang diklik', async () => {
    (AuthService.resendOtp as any).mockResolvedValue({ success: true });
    const wrapper = mount(VerifyOtp, mountOptions);

    // Di komponen Mas, tombol resend di-disabled kalau timer > 0
    // Kita "paksa" ubah nilai timer agar tombol bisa diklik
    (wrapper.vm as any).timer = 0;
    await wrapper.vm.$nextTick();

    const resendBtn = wrapper.findAll('button').find(b => b.text().includes('KIRIM ULANG'));
    if (resendBtn) {
      await resendBtn.trigger('click');
      expect(AuthService.resendOtp).toHaveBeenCalled();
    }
  });
});