// src/pages/auth/__tests__/Register.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Register from '../Register.vue';
import { useAuth } from '../../../composables/useAuth';
import { ref } from 'vue';

// MOCK ROUTER
const pushMock = vi.fn();
const replaceMock = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock, replace: replaceMock })
}));

// MOCK useAuth
vi.mock('../../../composables/useAuth', () => ({
  useAuth: vi.fn(),
}));

// MOCK getSafeErrorMessage
vi.mock('../../../utils/errorHandler', () => ({
  getSafeErrorMessage: vi.fn((msg: string) => msg),
}));

describe('Register.vue - Professional QA Test Suite', () => {
  let submitRegistrationMock: any;

  beforeEach(() => {
    submitRegistrationMock = vi.fn().mockResolvedValue({ success: true });
    (useAuth as any).mockReturnValue({
      isSubmitting: ref(false),  
      authError: ref(''),         
      submitRegistration: submitRegistrationMock,
    });
    vi.clearAllMocks();
  });

  const mountComponent = () => {
    return mount(Register, {
      global: {
        stubs: {
          'router-link': { template: '<a><slot /></a>' },
          // ✅ AuthLayout stub yang merender slot
          AuthLayout: {
            template: '<div class="auth-layout"><slot /></div>',
            props: ['maxWidth', 'extraPadding']
          },
        }
      }
    });
  };

  const fillValidForm = async (wrapper: any) => {
    const inputs = wrapper.findAll('input:not([type="checkbox"])');
    await inputs[0].setValue('6301234567890123');
    await inputs[1].setValue('6301234567890123');
    await inputs[2].setValue('JOHN DOE');
    await inputs[3].setValue('081234567890');
    await inputs[4].setValue('123456');
    await inputs[5].setValue('123456');
    
    const checkbox = wrapper.find('input[type="checkbox"]');
    if (checkbox.exists()) {
      await checkbox.setValue(true);
    }
  };

  // ============================================================
  // RENDER
  // ============================================================
  describe('Render', () => {

    it('[RENDER-01] Menampilkan judul "Registrasi Warga"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('Registrasi Warga');
    });

    it('[RENDER-02] Menampilkan logo SABANA', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('SABANA');
    });

    it('[RENDER-03] Semua input field tersedia', () => {
      const wrapper = mountComponent();
      const inputs = wrapper.findAll('input:not([type="checkbox"])');
      expect(inputs.length).toBe(6);
    });

    it('[RENDER-04] Checkbox terms tersedia', () => {
      const wrapper = mountComponent();
      expect(wrapper.find('input[type="checkbox"]').exists()).toBe(true);
    });

    it('[RENDER-05] Tombol submit "DAFTAR SEKARANG"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('DAFTAR SEKARANG');
    });

    it('[RENDER-06] Link "Masuk di sini"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('Masuk di sini');
    });
  });

  // ============================================================
  // FORMAT NUMERIC
  // ============================================================
  describe('formatNumeric', () => {

    it('[FMT-01] NIK maksimal 16 digit', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.formData.nik = '12345678901234567890';
      vm.formatNumeric('nik', 16);
      expect(vm.formData.nik).toBe('1234567890123456');
    });

    it('[FMT-02] PIN maksimal 6 digit', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.formData.pin = '1234567890';
      vm.formatNumeric('pin', 6);
      expect(vm.formData.pin).toBe('123456');
    });

    it('[FMT-03] Menghapus karakter non-digit', () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.formData.whatsapp_number = '0812-abc-3456';
      vm.formatNumeric('whatsapp_number', 15);
      expect(vm.formData.whatsapp_number).toBe('08123456');
    });
  });

  // ============================================================
  // VALIDASI
  // ============================================================
  describe('Validasi Form', () => {

    it('[VAL-01] Error jika PIN tidak cocok', async () => {
      const wrapper = mountComponent();
      
      const pinInputs = wrapper.findAll('input[type="password"]');
      await pinInputs[0].setValue('123456');
      await pinInputs[1].setValue('654321');
      await pinInputs[1].trigger('blur');
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('PIN tidak cocok');
    });

    it('[VAL-02] Error jika NIK kurang dari 16 digit', async () => {
      const wrapper = mountComponent();
      
      const inputs = wrapper.findAll('input:not([type="checkbox"])');
      await inputs[0].setValue('123');
      await inputs[0].trigger('blur');
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Harus 16 digit');
    });

    it('[VAL-03] Error jika checkbox tidak dicentang (submit)', async () => {
      const wrapper = mountComponent();
      
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Anda harus menyetujui');
    });

    it('[VAL-04] Submit form valid memanggil API', async () => {
      const wrapper = mountComponent();
      await fillValidForm(wrapper);
      
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(submitRegistrationMock).toHaveBeenCalledTimes(1);
    });

    it('[VAL-05] Redirect ke verify-otp setelah sukses', async () => {
      const wrapper = mountComponent();
      await fillValidForm(wrapper);
      
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(replaceMock).toHaveBeenCalledWith({
        name: 'verify-otp',
        query: expect.objectContaining({
          nik: '6301234567890123',
          wa: '081234567890'
        })
      });
    });
  });

  // ============================================================
  // ERROR DISPLAY
  // ============================================================
  describe('Error Display', () => {

    it('[ERR-01] Menampilkan authError jika ada', async () => {
      (useAuth as any).mockReturnValue({
        isSubmitting: { value: false },
        authError: { value: 'NIK sudah terdaftar.' },
        submitRegistration: submitRegistrationMock,
      });

      const wrapper = mountComponent();
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('NIK sudah terdaftar.');
    });
  });
});