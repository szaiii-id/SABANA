// src/pages/dashboard/__tests__/Profile.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Profile from '../Profile.vue';

const { getProfileMock, updateProfileMock } = vi.hoisted(() => ({
  getProfileMock: vi.fn(),
  updateProfileMock: vi.fn()
}));

vi.mock('../../../api/profileApi', () => ({
  profileApi: {
    getProfile: getProfileMock,
    updateProfile: updateProfileMock
  }
}));

function createMockProfile(overrides = {}) {
  return {
    nik: '6301234567890123',
    family_card_number: '6301999900012345',
    full_name: 'AKHMAD WARGA',
    whatsapp_number: '081234567890',
    ...overrides,
  };
}

describe('Profile.vue - Professional QA Test Suite', () => {

  beforeEach(() => {
    vi.clearAllMocks();
    vi.stubGlobal('localStorage', {
      getItem: vi.fn(),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
    });
  });

  const mountComponent = () => {
    return mount(Profile);
  };

  // ============================================================
  // FETCH PROFILE
  // ============================================================
  describe('Fetch Profile', () => {

    it('[FETCH-01] Memuat data profil saat mounted', async () => {
      getProfileMock.mockResolvedValue(createMockProfile());

      const wrapper = mountComponent();
      await flushPromises();

      expect(getProfileMock).toHaveBeenCalledTimes(1);

      const nameInput = wrapper.find('input[placeholder="Masukkan nama lengkap"]').element as HTMLInputElement;
      expect(nameInput.value).toBe('AKHMAD WARGA');
    });

    it('[FETCH-02] NIK dan KK disabled (readonly)', async () => {
      getProfileMock.mockResolvedValue(createMockProfile());

      const wrapper = mountComponent();
      await flushPromises();

      const inputs = wrapper.findAll('input');
      
      // NIK input (pertama) - disabled
      expect((inputs[0].element as HTMLInputElement).disabled).toBe(true);
      expect((inputs[0].element as HTMLInputElement).value).toBe('6301234567890123');

      // KK input (kedua) - disabled
      expect((inputs[1].element as HTMLInputElement).disabled).toBe(true);
      expect((inputs[1].element as HTMLInputElement).value).toBe('6301999900012345');
    });

    it('[FETCH-03] Menampilkan error jika fetch gagal', async () => {
      getProfileMock.mockRejectedValue(new Error('Network Error'));

      const wrapper = mountComponent();
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Gagal memuat data profil');
    });

    it('[FETCH-04] Loading state muncul saat fetch', async () => {
      let resolvePromise: any;
      getProfileMock.mockReturnValue(new Promise(resolve => {
        resolvePromise = resolve;
      }));

      const wrapper = mountComponent();
      await wrapper.vm.$nextTick();

      const vm = wrapper.vm as any;
      expect(vm.isLoadingData).toBe(true);

      resolvePromise(createMockProfile());
      await flushPromises();

      expect(vm.isLoadingData).toBe(false);
    });
  });

  // ============================================================
  // VALIDASI FORM
  // ============================================================
  describe('Validasi Form', () => {

    beforeEach(async () => {
      getProfileMock.mockResolvedValue(createMockProfile());
    });

    it('[VAL-01] Error jika nama kosong', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('input[placeholder="Masukkan nama lengkap"]').setValue('');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(wrapper.text()).toContain('Nama lengkap wajib diisi');
      expect(wrapper.text()).not.toContain('Konfirmasi Simpan');
    });

    it('[VAL-02] Error jika nama hanya spasi', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('input[placeholder="Masukkan nama lengkap"]').setValue('   ');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(wrapper.text()).toContain('Nama lengkap wajib diisi');
    });

    it('[VAL-03] Error jika WhatsApp kosong', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const waInput = wrapper.find('input[placeholder*="Contoh"]');
      await waInput.setValue('');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(wrapper.text()).toContain('Nomor WhatsApp tidak valid');
    });

    it('[VAL-04] Error jika WhatsApp kurang dari 10 digit', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const waInput = wrapper.find('input[placeholder*="Contoh"]');
      await waInput.setValue('08123');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(wrapper.text()).toContain('Nomor WhatsApp tidak valid');
    });

    it('[VAL-05] filterPhone menghapus karakter non-digit', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.formData.whatsapp_number = '0812-abc-3456';
      vm.filterPhone();

      expect(vm.formData.whatsapp_number).toBe('08123456');
    });

    it('[VAL-06] clearError menghapus pesan error', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.errors.full_name = 'Error test';
      vm.notification.message = 'Notif test';

      vm.clearError('full_name');

      expect(vm.errors.full_name).toBe('');
      expect(vm.notification.message).toBe('');
    });
  });

  // ============================================================
  // MODAL KONFIRMASI
  // ============================================================
  describe('Modal Konfirmasi', () => {

    beforeEach(async () => {
      getProfileMock.mockResolvedValue(createMockProfile());
    });

    it('[MODAL-01] Modal muncul setelah validasi sukses', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(wrapper.text()).toContain('Konfirmasi Simpan');
      expect(wrapper.text()).toContain('Apakah Anda yakin');
    });

    it('[MODAL-02] Modal tertutup saat klik Batal', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(wrapper.text()).toContain('Konfirmasi Simpan');

      const buttons = wrapper.findAll('button');
      const cancelBtn = buttons.find(b => b.text().includes('Batal'));
      await cancelBtn!.trigger('click');
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.showConfirmModal).toBe(false);
    });

    it('[MODAL-03] Modal tertutup saat klik Ya, Simpan Data', async () => {
      updateProfileMock.mockResolvedValue({ message: 'Berhasil' });

      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      const buttons = wrapper.findAll('button');
      const confirmBtn = buttons.find(b => b.text().includes('Ya, Simpan Data'));
      await confirmBtn!.trigger('click');
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.showConfirmModal).toBe(false);
    });
  });

  // ============================================================
  // SUBMIT
  // ============================================================
  describe('Submit Profile', () => {

    beforeEach(async () => {
      getProfileMock.mockResolvedValue(createMockProfile());
    });

    it('[SUBMIT-01] Submit berhasil menampilkan notifikasi sukses', async () => {
      updateProfileMock.mockResolvedValue({ message: 'Data berhasil diperbarui.' });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.showConfirmModal = true;
      await wrapper.vm.$nextTick();
      await vm.executeSubmit();
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(updateProfileMock).toHaveBeenCalledWith({
        full_name: 'AKHMAD WARGA',
        whatsapp_number: '081234567890'
      });
      expect(vm.notification.type).toBe('success');
      expect(wrapper.text()).toContain('Data berhasil diperbarui.');
    });

    it('[SUBMIT-02] Submit berhasil update localStorage', async () => {
      updateProfileMock.mockResolvedValue({ message: 'OK' });
      
      const mockUserData = JSON.stringify({ full_name: 'Old Name', nik: '123' });
      (localStorage.getItem as ReturnType<typeof vi.fn>).mockReturnValue(mockUserData);

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.formData.full_name = 'NEW NAME';
      vm.showConfirmModal = true;
      await wrapper.vm.$nextTick();
      await vm.executeSubmit();
      await flushPromises();

      expect(localStorage.setItem).toHaveBeenCalledWith(
        'user',
        expect.stringContaining('NEW NAME')
      );
    });

    it('[SUBMIT-03] Error 422 menampilkan error validasi server', async () => {
      updateProfileMock.mockRejectedValue({
        response: {
          status: 422,
          data: {
            message: 'Validation Error',
            errors: {
              full_name: ['Nama sudah digunakan'],
              whatsapp_number: ['Format WhatsApp salah']
            }
          }
        }
      });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.showConfirmModal = true;
      await wrapper.vm.$nextTick();
      await vm.executeSubmit();
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(vm.errors.full_name).toBe('Nama sudah digunakan');
      expect(vm.errors.whatsapp_number).toBe('Format WhatsApp salah');
      expect(vm.notification.message).toContain('Periksa kembali');
    });

    it('[SUBMIT-04] Error 400 menampilkan pesan dari server', async () => {
      updateProfileMock.mockRejectedValue({
        response: {
          status: 400,
          data: { message: 'Permintaan tidak valid' }
        }
      });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.showConfirmModal = true;
      await wrapper.vm.$nextTick();
      await vm.executeSubmit();
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(vm.notification.message).toBe('Permintaan tidak valid');
    });

    it('[SUBMIT-05] Error 401 menampilkan pesan dari server', async () => {
      updateProfileMock.mockRejectedValue({
        response: {
          status: 401,
          data: { message: 'Unauthorized' }
        }
      });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.showConfirmModal = true;
      await wrapper.vm.$nextTick();
      await vm.executeSubmit();
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(vm.notification.message).toBe('Unauthorized');
    });

    it('[SUBMIT-06] Error network menampilkan pesan default', async () => {
      updateProfileMock.mockRejectedValue(new Error('Network Error'));

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.showConfirmModal = true;
      await wrapper.vm.$nextTick();
      await vm.executeSubmit();
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(vm.notification.message).toContain('Terjadi gangguan');
      expect(vm.notification.type).toBe('error');
    });

    it('[SUBMIT-07] Submit menampilkan loading state', async () => {
      let resolvePromise: any;
      updateProfileMock.mockReturnValue(new Promise(resolve => {
        resolvePromise = resolve;
      }));

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.showConfirmModal = true;
      await wrapper.vm.$nextTick();
      
      const submitPromise = vm.executeSubmit();
      await wrapper.vm.$nextTick();

      expect(vm.isSubmitting).toBe(true);

      resolvePromise({ message: 'OK' });
      await submitPromise;
      await flushPromises();

      expect(vm.isSubmitting).toBe(false);
    });
  });

  // ============================================================
  // UI ELEMENTS
  // ============================================================
  describe('UI Elements', () => {

    beforeEach(async () => {
      getProfileMock.mockResolvedValue(createMockProfile());
    });

    it('[UI-01] Menampilkan "Data Diri Penduduk"', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Data Diri');
      expect(wrapper.text()).toContain('Penduduk');
    });

    it('[UI-02] Menampilkan info "Kenapa NIK dikunci?"', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Kenapa NIK dikunci?');
    });

    it('[UI-03] Notification success dengan warna hijau', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.notification.type = 'success';
      vm.notification.message = 'Berhasil!';
      await wrapper.vm.$nextTick();

      const notif = wrapper.find('.bg-green-50');
      expect(notif.exists()).toBe(true);
      expect(wrapper.text()).toContain('Berhasil!');
    });

    it('[UI-04] Notification error dengan warna merah', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.notification.type = 'error';
      vm.notification.message = 'Gagal!';
      await wrapper.vm.$nextTick();

      const notif = wrapper.find('.bg-red-50');
      expect(notif.exists()).toBe(true);
      expect(wrapper.text()).toContain('Gagal!');
    });
  });

  // ============================================================
  // EDGE CASES
  // ============================================================
  describe('Edge Cases', () => {

    it('[EDGE-01] filterPhone handle string kosong', async () => {
      getProfileMock.mockResolvedValue(createMockProfile());
      
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.formData.whatsapp_number = '';
      vm.filterPhone();

      expect(vm.formData.whatsapp_number).toBe('');
    });

    it('[EDGE-02] filterPhone handle hanya non-digit', async () => {
      getProfileMock.mockResolvedValue(createMockProfile());
      
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.formData.whatsapp_number = 'abc-def-ghi';
      vm.filterPhone();

      expect(vm.formData.whatsapp_number).toBe('');
    });

    it('[EDGE-03] Update localStorage tidak crash jika data null', async () => {
      updateProfileMock.mockResolvedValue({ message: 'OK' });
      (localStorage.getItem as ReturnType<typeof vi.fn>).mockReturnValue(null);

      getProfileMock.mockResolvedValue(createMockProfile());
      
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.showConfirmModal = true;
      await wrapper.vm.$nextTick();

      await expect(vm.executeSubmit()).resolves.not.toThrow();
    });

    it('[EDGE-04] Error 422 tanpa errors object masuk ke fallback default', async () => {
      updateProfileMock.mockRejectedValue({
        response: {
          status: 422,
          data: { message: 'Error' }
          // TIDAK ada errors object
        }
      });

      getProfileMock.mockResolvedValue(createMockProfile());
      
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.showConfirmModal = true;
      await wrapper.vm.$nextTick();
      await vm.executeSubmit();
      await flushPromises();
      await wrapper.vm.$nextTick();

      // errors tetap kosong
      expect(vm.errors.full_name).toBe('');
      
      // Karena tidak masuk ke if 422 (karena errors undefined),
      // juga tidak masuk ke if 400/401, maka jatuh ke else default
      expect(vm.notification.message).toContain('Terjadi gangguan');
    });
  });
});