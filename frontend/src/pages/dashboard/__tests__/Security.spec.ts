// src/pages/dashboard/__tests__/Security.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Security from '../Security.vue';

const { updatePinMock } = vi.hoisted(() => ({
  updatePinMock: vi.fn()
}));

vi.mock('../../../api/securityApi', () => ({
  securityApi: {
    updatePin: updatePinMock
  }
}));

vi.mock('../../../utils/errorHandler', () => ({
  getSafeErrorMessage: vi.fn((msg: string) => msg)
}));

describe('Security.vue - Professional QA Test Suite', () => {

  beforeEach(() => {
    vi.clearAllMocks();
    updatePinMock.mockResolvedValue({ message: 'PIN berhasil diperbarui.' });
  });

  const mountComponent = () => {
    return mount(Security);
  };

  const fillForm = async (wrapper: any, current: string, newPin: string, confirm: string) => {
    await wrapper.find('#input-current-pin').setValue(current);
    await wrapper.find('#input-new-pin').setValue(newPin);
    await wrapper.find('#input-confirm-pin').setValue(confirm);
  };

  // ============================================================
  // RENDER & INISIALISASI
  // ============================================================
  describe('Render & Inisialisasi', () => {

    it('[RENDER-01] Menampilkan "Keamanan Akun"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('Keamanan');
      expect(wrapper.text()).toContain('Akun');
    });

    it('[RENDER-02] Semua input field tersedia', () => {
      const wrapper = mountComponent();
      expect(wrapper.find('#input-current-pin').exists()).toBe(true);
      expect(wrapper.find('#input-new-pin').exists()).toBe(true);
      expect(wrapper.find('#input-confirm-pin').exists()).toBe(true);
    });

    it('[RENDER-03] Tombol submit ada', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('PERBARUI PIN');
    });

    it('[RENDER-04] Form dalam keadaan kosong saat mount', () => {
      const wrapper = mountComponent();
      expect((wrapper.find('#input-current-pin').element as HTMLInputElement).value).toBe('');
      expect((wrapper.find('#input-new-pin').element as HTMLInputElement).value).toBe('');
      expect((wrapper.find('#input-confirm-pin').element as HTMLInputElement).value).toBe('');
    });
  });

  // ============================================================
  // FORMAT NUMERIC
  // ============================================================
  describe('formatNumeric', () => {

    it('[FMT-01] Hanya menerima digit (menghapus huruf)', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.form.current_pin = 'abc123def';
      vm.formatNumeric('current_pin');
      expect(vm.form.current_pin).toBe('123');
    });

    it('[FMT-02] Maksimal 6 karakter', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.form.new_pin = '1234567890';
      vm.formatNumeric('new_pin');
      expect(vm.form.new_pin).toBe('123456');
    });

    it('[FMT-03] Menghapus karakter spesial', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.form.new_pin_confirmation = '12-34-56';
      vm.formatNumeric('new_pin_confirmation');
      expect(vm.form.new_pin_confirmation).toBe('123456');
    });

    it('[FMT-04] Clear error saat format ulang', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.errors.current_pin = 'Error';
      vm.notification.message = 'Notif';
      vm.form.current_pin = '123';
      vm.formatNumeric('current_pin');
      expect(vm.errors.current_pin).toBe('');
      expect(vm.notification.message).toBe('');
    });
  });

  // ============================================================
  // VALIDASI FORM
  // ============================================================
  describe('Validasi Form', () => {

    it('[VAL-01] Error jika PIN saat ini kurang dari 6 digit', async () => {
      const wrapper = mountComponent();
      await fillForm(wrapper, '123', '654321', '654321');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(wrapper.text()).toContain('PIN harus 6 digit');
      expect(updatePinMock).not.toHaveBeenCalled();
    });

    it('[VAL-02] Error jika PIN baru kurang dari 6 digit', async () => {
      const wrapper = mountComponent();
      await fillForm(wrapper, '123456', '654', '654321');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(wrapper.text()).toContain('PIN harus 6 digit');
      expect(updatePinMock).not.toHaveBeenCalled();
    });

    it('[VAL-03] Error jika konfirmasi PIN tidak cocok', async () => {
      const wrapper = mountComponent();
      await fillForm(wrapper, '123456', '654321', '111111');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(wrapper.text()).toContain('Konfirmasi PIN tidak sesuai');
      expect(updatePinMock).not.toHaveBeenCalled();
    });

    it('[VAL-04] Error jika PIN baru sama dengan PIN saat ini', async () => {
      const wrapper = mountComponent();
      await fillForm(wrapper, '123456', '123456', '123456');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(wrapper.text()).toContain('PIN baru tidak boleh sama dengan PIN saat ini');
      expect(updatePinMock).not.toHaveBeenCalled();
    });

    it('[VAL-05] Multiple errors muncul bersamaan', async () => {
      const wrapper = mountComponent();
      await fillForm(wrapper, '12', '34', '56');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      const errorCount = wrapper.findAll('.text-red-500').length;
      expect(errorCount).toBeGreaterThanOrEqual(2);
    });
  });

  // ============================================================
  // SUBMIT SUKSES
  // ============================================================
  describe('Submit Sukses', () => {

    it('[SUBMIT-01] Berhasil update PIN', async () => {
      updatePinMock.mockResolvedValue({ message: 'PIN berhasil diperbarui.' });
      const wrapper = mountComponent();
      await fillForm(wrapper, '123456', '654321', '654321');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(updatePinMock).toHaveBeenCalledWith({
        current_pin: '123456',
        new_pin: '654321',
        new_pin_confirmation: '654321'
      });
    });

    it('[SUBMIT-02] Menampilkan notifikasi sukses', async () => {
      updatePinMock.mockResolvedValue({ message: 'PIN berhasil diperbarui.' });
      const wrapper = mountComponent();
      await fillForm(wrapper, '123456', '654321', '654321');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(wrapper.text()).toContain('PIN berhasil diperbarui.');
      expect(wrapper.find('.bg-green-50').exists()).toBe(true);
    });

    it('[SUBMIT-03] Reset form setelah sukses', async () => {
      updatePinMock.mockResolvedValue({ message: 'OK' });
      const wrapper = mountComponent();
      await fillForm(wrapper, '123456', '654321', '654321');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect((wrapper.find('#input-current-pin').element as HTMLInputElement).value).toBe('');
      expect((wrapper.find('#input-new-pin').element as HTMLInputElement).value).toBe('');
      expect((wrapper.find('#input-confirm-pin').element as HTMLInputElement).value).toBe('');
    });
  });

  // ============================================================
  // ERROR HANDLING
  // ============================================================
  describe('Error Handling', () => {

    it('[ERR-01] Error 400: PIN saat ini tidak valid', async () => {
      updatePinMock.mockRejectedValue({
        response: { status: 400, data: { message: 'PIN saat ini tidak valid.' } }
      });
      const wrapper = mountComponent();
      await fillForm(wrapper, '111111', '222222', '222222');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();
      expect(wrapper.text()).toContain('PIN saat ini tidak valid.');
      expect(wrapper.find('.bg-red-50').exists()).toBe(true);
    });

    it('[ERR-02] Error 401: Unauthorized', async () => {
      updatePinMock.mockRejectedValue({
        response: { status: 401, data: { message: 'Sesi telah berakhir.' } }
      });
      const wrapper = mountComponent();
      await fillForm(wrapper, '111111', '222222', '222222');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();
      expect(wrapper.text()).toContain('Sesi telah berakhir');
    });

    it('[ERR-03] Error 422: Validasi server', async () => {
      updatePinMock.mockRejectedValue({
        response: {
          status: 422,
          data: {
            errors: {
              current_pin: ['PIN tidak valid'],
              new_pin: ['PIN baru terlalu lemah']
            }
          }
        }
      });
      const wrapper = mountComponent();
      await fillForm(wrapper, '111111', '222222', '222222');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();
      expect(wrapper.text()).toContain('PIN tidak valid');
      expect(wrapper.text()).toContain('PIN baru terlalu lemah');
    });

    it('[ERR-04] Error 422 tanpa errors object', async () => {
      updatePinMock.mockRejectedValue({
        response: { status: 422, data: { message: 'Error' } }
      });
      const wrapper = mountComponent();
      await fillForm(wrapper, '111111', '222222', '222222');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();
      await flushPromises(); // tambahan

      const vm = wrapper.vm as any;
      expect(vm.notification.type).toBe('error');
      expect(vm.notification.message).toBeTruthy();
    });

    it('[ERR-05] Error network', async () => {
      updatePinMock.mockRejectedValue(new Error('Network Error'));
      const wrapper = mountComponent();
      await fillForm(wrapper, '111111', '222222', '222222');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();
      expect(wrapper.text()).toContain('Terjadi kesalahan sistem.');
    });
  });

  // ============================================================
  // LOADING STATE
  // ============================================================
  describe('Loading State', () => {

    it('[LOAD-01] Loading state saat submit', async () => {
      let resolvePromise: any;
      updatePinMock.mockImplementation(() => new Promise(resolve => {
        resolvePromise = resolve;
      }));

      const wrapper = mountComponent();
      await fillForm(wrapper, '123456', '654321', '654321');
      
      wrapper.find('form').trigger('submit.prevent');
      await wrapper.vm.$nextTick();

      const vm = wrapper.vm as any;
      const submitBtn = wrapper.find('button[type="submit"]');
      
      expect(vm.isSubmitting).toBe(true);
      expect((submitBtn.element as HTMLButtonElement).disabled).toBe(true);

      // Resolve
      resolvePromise({ message: 'OK' });
      await flushPromises();
      await wrapper.vm.$nextTick();
      await flushPromises();

      expect(vm.isSubmitting).toBe(false);
      expect((submitBtn.element as HTMLButtonElement).disabled).toBe(false);
    });
  });

  // ============================================================
  // UI ELEMENTS
  // ============================================================
  describe('UI Elements', () => {

    it('[UI-01] Notifikasi sukses hijau', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.notification.type = 'success';
      vm.notification.message = 'Berhasil!';
      await wrapper.vm.$nextTick();
      expect(wrapper.find('.bg-green-50').exists()).toBe(true);
    });

    it('[UI-02] Notifikasi error merah', async () => {
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      vm.notification.type = 'error';
      vm.notification.message = 'Gagal!';
      await wrapper.vm.$nextTick();
      expect(wrapper.find('.bg-red-50').exists()).toBe(true);
    });

    it('[UI-03] Input type password', () => {
      const wrapper = mountComponent();
      expect(wrapper.findAll('input[type="password"]').length).toBe(3);
    });
  });
});