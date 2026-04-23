import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Login from '../Login.vue';
import { useAuth } from '../../../composables/useAuth';

// 1. STUB GLOBAL LOCALSTORAGE (PENTING: Agar onMounted tidak crash)
const locationMock = {
  href: ''
};
vi.stubGlobal('location', locationMock);

const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: vi.fn((key: string) => store[key] || null),
    setItem: vi.fn((key: string, value: string) => { store[key] = value.toString(); }),
    removeItem: vi.fn((key: string) => { delete store[key]; }),
    clear: vi.fn(() => { store = {}; }),
  };
})();

vi.stubGlobal('localStorage', localStorageMock);

// 2. MOCK COMPOSABLE
vi.mock('../../../composables/useAuth', () => ({
  useAuth: vi.fn(),
}));

describe('Login.vue Component', () => {
  let submitLoginMock: any;

  beforeEach(() => {
    // Kita berikan return value default berupa Promise agar tidak crash saat di-await
    submitLoginMock = vi.fn().mockResolvedValue({ success: false }); 

    (useAuth as any).mockReturnValue({
      isSubmitting: { value: false },
      authError: { value: '' },
      submitLogin: submitLoginMock,
    });
    vi.clearAllMocks();
  });

  it('merender input NIK dan PIN', () => {
    const wrapper = mount(Login, {
      global: {
        stubs: ['router-link'] // 3. FIX: Beritahu Vue untuk mengabaikan tag router-link
      }
    });
    expect(wrapper.find('input[type="text"]').exists()).toBe(true);
    expect(wrapper.find('input[type="password"]').exists()).toBe(true);
  });

  it('memanggil submitLogin saat form di-submit', async () => {
    submitLoginMock.mockResolvedValueOnce({ success: true });

    const wrapper = mount(Login, {
      global: { stubs: ['router-link'] }
    });
    
    await wrapper.find('input[type="text"]').setValue('6301012345678901');
    await wrapper.find('input[type="password"]').setValue('123456');
    
    // Gunakan await pada trigger submit
    await wrapper.find('form').trigger('submit.prevent');
    
    // Tambahkan flushPromises untuk memastikan semua async di komponen selesai
    await new Promise(resolve => setTimeout(resolve, 0));

    expect(submitLoginMock).toHaveBeenCalled();
  });

  it('menonaktifkan tombol saat loading', async () => {
    (useAuth as any).mockReturnValue({
      isSubmitting: { value: true },
      authError: { value: '' },
      submitLogin: submitLoginMock,
    });

    const wrapper = mount(Login, {
      global: { stubs: ['router-link'] }
    });
    const button = wrapper.find('button[type="submit"]');

    expect(button.attributes()).toHaveProperty('disabled');
  });

  it('menampilkan pesan error jika authError berisi string', async () => {
    (useAuth as any).mockReturnValue({
      isSubmitting: { value: false },
      authError: { value: 'Kredensial tidak valid' },
      submitLogin: submitLoginMock,
    });

    const wrapper = mount(Login, {
      global: { stubs: ['router-link'] }
    });
    expect(wrapper.text()).toContain('Kredensial tidak valid');
  });
});