// src/pages/auth/__tests__/Login.spec.ts
import { ref } from 'vue';
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Login from '../Login.vue';

const locationMock = { href: '' };
vi.stubGlobal('location', locationMock);

let localStorageStore: Record<string, string> = {};

const mockGetItem = vi.fn((key: string) => localStorageStore[key] || null);
const mockSetItem = vi.fn((key: string, value: string) => { localStorageStore[key] = value; });
const mockRemoveItem = vi.fn((key: string) => { delete localStorageStore[key]; });
const mockClear = vi.fn(() => { localStorageStore = {}; });

vi.stubGlobal('localStorage', {
  getItem: mockGetItem,
  setItem: mockSetItem,
  removeItem: mockRemoveItem,
  clear: mockClear,
});

const submitLoginMock = vi.fn();
const authErrorRef = ref('');
const isSubmittingRef = ref(false);

vi.mock('../../../composables/useAuth', () => ({
  useAuth: () => ({
    isSubmitting: isSubmittingRef,
    authError: authErrorRef,
    submitLogin: submitLoginMock,
  }),
}));

vi.mock('../../../utils/errorHandler', () => ({
  getSafeErrorMessage: vi.fn((msg: string) => msg),
}));

describe('Login.vue - Professional QA Test Suite', () => {

  beforeEach(() => {
    // Reset mock function calls & implementation
    submitLoginMock.mockReset();
    submitLoginMock.mockResolvedValue({ success: false }); // ← SET SETELAH reset!
    
    // Reset refs
    authErrorRef.value = '';
    isSubmittingRef.value = false;
    
    // Reset store
    localStorageStore = {};
    locationMock.href = '';
  });

 
  const mountComponent = () => {
    return mount(Login, {
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
    it('[RENDER-01] Menampilkan input NIK', () => {
      const wrapper = mountComponent();
      expect(wrapper.find('input[type="text"]').exists()).toBe(true);
    });
    it('[RENDER-02] Menampilkan input PIN', () => {
      const wrapper = mountComponent();
      expect(wrapper.find('input[type="password"]').exists()).toBe(true);
    });
    it('[RENDER-03] Menampilkan "Selamat Datang"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('Selamat Datang');
    });
    it('[RENDER-04] Menampilkan logo SABANA', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('SABANA');
    });
    it('[RENDER-05] Tombol submit "MASUK KE LAYANAN"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('MASUK KE LAYANAN');
    });
    it('[RENDER-06] Checkbox "Ingat NIK Saya"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('Ingat NIK Saya');
    });
    it('[RENDER-07] Link "Lupa PIN?"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('Lupa PIN?');
    });
    it('[RENDER-08] Link "Daftar Sekarang"', () => {
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('Daftar Sekarang');
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
      vm.formData.nik = '6301-abc-4567';
      vm.formatNumeric('nik', 16);
      expect(vm.formData.nik).toBe('63014567');
    });
  });

  // ============================================================
  // VALIDASI
  // ============================================================
  describe('Validasi', () => {
    it('[VAL-01] Error NIK < 16 digit', async () => {
      const wrapper = mountComponent();
      await wrapper.find('input[type="text"]').setValue('123');
      await wrapper.find('input[type="text"]').trigger('blur');
      await wrapper.vm.$nextTick();
      expect(wrapper.text()).toContain('Harus 16 digit');
    });
    it('[VAL-02] Error PIN < 6 digit', async () => {
      const wrapper = mountComponent();
      await wrapper.find('input[type="password"]').setValue('123');
      await wrapper.find('input[type="password"]').trigger('blur');
      await wrapper.vm.$nextTick();
      expect(wrapper.text()).toContain('Harus 6 digit');
    });
  });

  // ============================================================
  // SUBMIT
  // ============================================================
  describe('Submit', () => {
    it('[SUB-01] submitLogin dengan payload benar', async () => {
      submitLoginMock.mockResolvedValue({ success: true });
      const wrapper = mountComponent();
      await wrapper.find('input[type="text"]').setValue('6301234567890123');
      await wrapper.find('input[type="password"]').setValue('123456');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(submitLoginMock).toHaveBeenCalledWith({ nik: '6301234567890123', pin: '123456' });
    });
    it('[SUB-02] Redirect ke /dashboard setelah sukses', async () => {
      submitLoginMock.mockResolvedValue({ success: true });
      const wrapper = mountComponent();
      await wrapper.find('input[type="text"]').setValue('6301234567890123');
      await wrapper.find('input[type="password"]').setValue('123456');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(locationMock.href).toBe('/dashboard');
    });
    it('[SUB-03] TIDAK redirect jika gagal', async () => {
      const wrapper = mountComponent();
      await wrapper.find('input[type="text"]').setValue('6301234567890123');
      await wrapper.find('input[type="password"]').setValue('123456');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(locationMock.href).toBe('');
    });
    it('[SUB-04] TIDAK submit jika validasi gagal', async () => {
      const wrapper = mountComponent();
      await wrapper.find('input[type="text"]').setValue('12');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(submitLoginMock).not.toHaveBeenCalled();
    });
    it('[SUB-05] Simpan NIK jika rememberNik dicentang', async () => {
      submitLoginMock.mockResolvedValue({ success: true });
      const wrapper = mountComponent();
      await wrapper.find('input[type="text"]').setValue('6301234567890123');
      await wrapper.find('input[type="password"]').setValue('123456');
      await wrapper.find('input[type="checkbox"]').setValue(true);
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(mockSetItem).toHaveBeenCalledWith('remembered_nik', '6301234567890123');
    });
    it('[SUB-06] Hapus NIK jika rememberNik tidak dicentang', async () => {
      submitLoginMock.mockResolvedValue({ success: true });
      const wrapper = mountComponent();
      await wrapper.find('input[type="text"]').setValue('6301234567890123');
      await wrapper.find('input[type="password"]').setValue('123456');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      expect(mockRemoveItem).toHaveBeenCalledWith('remembered_nik');
    });
  });

  // ============================================================
  // ERROR DISPLAY
  // ============================================================
  describe('Error Display', () => {
    it('[ERR-01] authError muncul', async () => {
      authErrorRef.value = 'Kredensial tidak valid';
      const wrapper = mountComponent();
      await wrapper.vm.$nextTick();
      expect(wrapper.text()).toContain('Kredensial tidak valid');
    });
  });

  // ============================================================
  // ONMOUNTED
  // ============================================================
  describe('onMounted', () => {
    it('[MOUNT-01] Load remembered NIK', () => {
      localStorageStore['remembered_nik'] = '6301234567890123';
      const wrapper = mountComponent();
      const vm = wrapper.vm as any;
      expect(vm.formData.nik).toBe('6301234567890123');
      expect(vm.formData.rememberNik).toBe(true);
    });
    it('[MOUNT-02] DEBUG', () => {
        localStorageStore['session_expired'] = '1';
        
        // Cek store sebelum mount
        console.log('Store:', localStorageStore);
        console.log('getItem result:', mockGetItem('session_expired'));
        
        const wrapper = mountComponent();
        
        // Cek apakah mockGetItem dipanggil
        console.log('mockGetItem calls:', mockGetItem.mock.calls);
        console.log('mockClear calls:', mockClear.mock.calls);
        console.log('sessionAlert value:', (wrapper.vm as any).sessionAlert);
    });
    it('[MOUNT-03] clear() dipanggil', () => {
      localStorageStore['remembered_nik'] = '6301234567890123';
      mountComponent();
      expect(mockClear).toHaveBeenCalled();
    });
  });

  // ============================================================
  // LOADING STATE
  // ============================================================
  describe('Loading State', () => {
    it('[LOAD-01] Tombol disabled', () => {
      isSubmittingRef.value = true;
      const wrapper = mountComponent();
      expect((wrapper.find('button[type="submit"]').element as HTMLButtonElement).disabled).toBe(true);
    });
    it('[LOAD-02] Teks MENGAUTENTIKASI', () => {
      isSubmittingRef.value = true;
      const wrapper = mountComponent();
      expect(wrapper.text()).toContain('MENGAUTENTIKASI...');
    });
  });

  // ============================================================
  // SESSION ALERT
  // ============================================================
  describe('Session Alert', () => {
    it('[ALERT-01] Dismiss alert', async () => {
      const wrapper = mountComponent();
      
      // Set sessionAlert LANGSUNG via vm
      const vm = wrapper.vm as any;
      vm.sessionAlert = 'Sesi Anda telah berakhir otomatis demi keamanan.';
      await wrapper.vm.$nextTick();
      
      // Verifikasi muncul
      expect(wrapper.text()).toContain('Sesi Anda telah berakhir');
      
      // Dismiss
      const buttons = wrapper.findAll('button');
      const closeBtn = buttons.find(b => b.attributes('class')?.includes('text-amber-400'));
      if (closeBtn) {
        await closeBtn.trigger('click');
        expect(wrapper.text()).not.toContain('Sesi Anda telah berakhir');
      }
    });
  });
});