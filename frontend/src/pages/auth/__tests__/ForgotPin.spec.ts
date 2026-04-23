import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import ForgotPin from '../ForgotPin.vue';
import { useForgotPin } from '../../../composables/useForgotPin';

const pushMock = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock }),
  routerLink: { template: '<a><slot /></a>' }
}));

vi.mock('../../../composables/useForgotPin', () => ({
  useForgotPin: vi.fn(),
}));

describe('ForgotPin.vue', () => {
  let sendOtpMock: any;

  beforeEach(() => {
    sendOtpMock = vi.fn();
    (useForgotPin as any).mockReturnValue({
      isSubmitting: { value: false },
      errorMessage: { value: '' },
      sendOtp: sendOtpMock,
    });
    vi.clearAllMocks();
  });

  it('menampilkan error validasi jika NIK tidak 16 digit', async () => {
    const wrapper = mount(ForgotPin, {
      global: { stubs: ['router-link'] }
    });

    const nikInput = wrapper.find('input[type="text"]');
    await nikInput.setValue('123');
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    expect(wrapper.text()).toContain('Harus 16 digit');
    expect(sendOtpMock).not.toHaveBeenCalled();
  });

  it('memanggil sendOtp dan redirect jika form valid', async () => {
    sendOtpMock.mockResolvedValue({ success: true });
    const wrapper = mount(ForgotPin, {
      global: { stubs: ['router-link'] }
    });

    await wrapper.findAll('input')[0].setValue('1234567890123456');
    await wrapper.findAll('input')[1].setValue('08123456789');
    
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    expect(sendOtpMock).toHaveBeenCalled();
    expect(pushMock).toHaveBeenCalledWith(expect.objectContaining({ name: 'reset-pin' }));
  });
});