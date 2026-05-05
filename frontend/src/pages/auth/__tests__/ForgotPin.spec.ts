// src/pages/auth/__tests__/ForgotPin.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import ForgotPin from '../ForgotPin.vue';
import { useForgotPin } from '../../../composables/useForgotPin';
import { ref } from 'vue';


// ============================================================
// MOCKS
// ============================================================
const pushMock = vi.fn();

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock }),
}));

vi.mock('../../../composables/useForgotPin');

vi.mock('../../../utils/errorHandler', () => ({
  getSafeErrorMessage: vi.fn((msg: string) => msg),
}));

// ============================================================
// TEST SUITE
// ============================================================
describe('ForgotPin.vue - Professional QA Test Suite', () => {
  let sendOtpMock: any;


  beforeEach(() => {
    vi.clearAllMocks();
    sendOtpMock = vi.fn();
    (useForgotPin as any).mockReturnValue({
      isSubmitting: ref(false),  // ✅ Pakai ref() asli!
      errorMessage: ref(''),
      sendOtp: sendOtpMock,
    });
  });

  const mountComponent = () => {
    return mount(ForgotPin, {
      global: {
        stubs: {
          'router-link': { template: '<a><slot /></a>' },
          AuthLayout: {
            template: '<div class="auth-layout"><slot /></div>',
            props: ['maxWidth'],
          },
        },
      },
    });
  };

  // ============================================================
  // RENDER
  // ============================================================
  describe('Render', () => {
    
    it('[RENDER-01] Menampilkan "Lupa PIN"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('Lupa PIN');
    });

    it('[RENDER-02] Menampilkan logo SABANA', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('SABANA');
    });

    it('[RENDER-03] Input NIK tersedia', () => {
      const wrapper = mountComponent();
      expect(wrapper.find('input[type="text"]').exists()).toBe(true);
    });

    it('[RENDER-04] Input WhatsApp tersedia', () => {
      const wrapper = mountComponent();
      expect(wrapper.find('input[type="tel"]').exists()).toBe(true);
    });

    it('[RENDER-05] Tombol "KIRIM PIN SEMENTARA"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('KIRIM PIN SEMENTARA');
    });

    it('[RENDER-06] Link "Kembali ke Login"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('Kembali ke Login');
    });
  });

  // ============================================================
  // FORMAT NUMERIC
  // ============================================================
  describe('formatNumeric', () => {

    it('[FMT-01] NIK maksimal 16 digit', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.form.nik = '12345678901234567890';
      vm.formatNumeric('nik', 16);
      expect(vm.form.nik).toBe('1234567890123456');
    });

    it('[FMT-02] WhatsApp maksimal 15 digit', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.form.whatsapp_number = '081234567890123456';
      vm.formatNumeric('whatsapp_number', 15);
      expect(vm.form.whatsapp_number).toBe('081234567890123');
    });

    it('[FMT-03] Menghapus karakter non-digit', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.form.nik = '6301-abc-4567';
      vm.formatNumeric('nik', 16);
      expect(vm.form.nik).toBe('63014567');
    });
  });

  // ============================================================
  // VALIDASI
  // ============================================================
  describe('Validasi', () => {

    it('[VAL-01] Error jika NIK < 16 digit', async () => {
      const wrapper = mountComponent();
      await wrapper.find('input[type="text"]').setValue('123');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(wrapper.text()).toContain('Harus 16 digit');
      expect(sendOtpMock).not.toHaveBeenCalled();
    });

    it('[VAL-02] Error jika WhatsApp kosong', async () => {
      const wrapper = mountComponent();
      await wrapper.find('input[type="text"]').setValue('1234567890123456');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(wrapper.text()).toContain('No WhatsApp wajib diisi');
      expect(sendOtpMock).not.toHaveBeenCalled();
    });

    it('[VAL-03] Error jika NIK kosong', async () => {
      const wrapper = mountComponent();
      await wrapper.find('input[type="tel"]').setValue('08123456789');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(wrapper.text()).toContain('NIK wajib diisi');
      expect(sendOtpMock).not.toHaveBeenCalled();
    });
  });

  // ============================================================
  // SUBMIT
  // ============================================================
  describe('Submit', () => {

    it('[SUB-01] sendOtp dipanggil dengan payload benar', async () => {
      sendOtpMock.mockResolvedValue({ success: true });
      const wrapper = mountComponent();
      
      await wrapper.find('input[type="text"]').setValue('6301234567890123');
      await wrapper.find('input[type="tel"]').setValue('08123456789');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(sendOtpMock).toHaveBeenCalledWith({
        nik: '6301234567890123',
        whatsapp_number: '08123456789',
      });
    });

    it('[SUB-02] Redirect ke reset-pin setelah sukses', async () => {
      sendOtpMock.mockResolvedValue({ success: true });
      const wrapper = mountComponent();
      
      await wrapper.find('input[type="text"]').setValue('6301234567890123');
      await wrapper.find('input[type="tel"]').setValue('08123456789');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(pushMock).toHaveBeenCalledWith({
        name: 'reset-pin',
        query: { nik: '6301234567890123', wa: '08123456789' },
      });
    });

    it('[SUB-03] TIDAK redirect jika gagal', async () => {
      sendOtpMock.mockResolvedValue({ success: false });
      const wrapper = mountComponent();
      
      await wrapper.find('input[type="text"]').setValue('6301234567890123');
      await wrapper.find('input[type="tel"]').setValue('08123456789');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(pushMock).not.toHaveBeenCalled();
    });

    it('[SUB-04] Tombol disabled saat submitting', async () => {
      (useForgotPin as any).mockReturnValue({
        isSubmitting: { value: true },
        errorMessage: { value: '' },
        sendOtp: sendOtpMock,
      });

      const wrapper = mountComponent();
      expect((wrapper.find('button[type="submit"]').element as HTMLButtonElement).disabled).toBe(true);
      expect(wrapper.text()).toContain('MENGIRIM...');
    });
  });

  // ============================================================
  // ERROR DISPLAY
  // ============================================================
  describe('Error Display', () => {

    it('[ERR-01] Menampilkan errorMessage', async () => {
      (useForgotPin as any).mockReturnValue({
        isSubmitting: { value: false },
        errorMessage: { value: 'NIK tidak ditemukan.' },
        sendOtp: sendOtpMock,
      });

      const wrapper = mountComponent();
      await wrapper.vm.$nextTick();
      expect(wrapper.text()).toContain('NIK tidak ditemukan.');
    });
  });
});