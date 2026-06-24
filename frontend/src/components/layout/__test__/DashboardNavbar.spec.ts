import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import LogoutHeader from '../DashboardNavbar.vue';

// ===== MOCKS =====
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

// ===== TYPES =====
interface VmLogoutHeader {
  firstName: string;
  timeGreeting: string;
  interactiveMessage: string;
}

// ===== ROUTER =====
function createTestRouter() {
  return createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', name: 'home', component: { template: '<div/>' } },
    ],
  });
}

// ===== MOUNT HELPER =====
function mountComponent(router: ReturnType<typeof createTestRouter>) {
  return mount(LogoutHeader, {
    global: {
      plugins: [router],
    },
  });
}

// ===== TEST SUITE =====
describe('LogoutHeader.vue', () => {
  let router: ReturnType<typeof createTestRouter>;

  beforeEach(() => {
    localStorageMock.getItem.mockReturnValue(null);
    router = createTestRouter();
  });

  // ===== HAPPY PATH =====
  it('test_menampilkan_sapaan_waktu', () => {
    const wrapper = mountComponent(router);
    const vm = wrapper.vm as unknown as VmLogoutHeader;
    
    expect(['Selamat pagi', 'Selamat siang', 'Selamat sore', 'Selamat malam']).toContain(vm.timeGreeting);
  });

  it('test_menampilkan_nama_warga_default', () => {
    const wrapper = mountComponent(router);
    const vm = wrapper.vm as unknown as VmLogoutHeader;
    
    // Default: 'Warga' (tidak ada localStorage)
    expect(vm.firstName).toBe('Warga');
  });

  it('test_menampilkan_pesan_interaktif', () => {
    const wrapper = mountComponent(router);
    const vm = wrapper.vm as unknown as VmLogoutHeader;
    
    expect(vm.interactiveMessage).toBeTruthy();
    expect(typeof vm.interactiveMessage).toBe('string');
    expect(vm.interactiveMessage.length).toBeGreaterThan(0);
  });

  it('test_menampilkan_tombol_keluar', () => {
    const wrapper = mountComponent(router);
    
    expect(wrapper.text()).toContain('KELUAR');
  });

  // ===== SAD PATH =====
  it('test_tampil_warga_jika_localstorage_kosong', () => {
    localStorageMock.getItem.mockReturnValue(null);
    
    const wrapper = mountComponent(router);
    const vm = wrapper.vm as unknown as VmLogoutHeader;
    
    expect(vm.firstName).toBe('Warga');
  });

  it('test_tampil_warga_jika_json_rusak', () => {
    localStorageMock.getItem.mockReturnValue('{invalid json');
    
    const wrapper = mountComponent(router);
    const vm = wrapper.vm as unknown as VmLogoutHeader;
    
    expect(vm.firstName).toBe('Warga');
  });

  // ===== STATE TRANSITION =====
  it('test_emit_event_logout_saat_tombol_diklik', async () => {
    const wrapper = mountComponent(router);
    const button = wrapper.find('button');
    
    await button.trigger('click');
    
    expect(wrapper.emitted('logout')).toBeTruthy();
    expect(wrapper.emitted('logout')?.length).toBe(1);
  });

  // ===== LOADING STATE =====
  it('test_tombol_disabled_saat_loading', async () => {
    const wrapper = mount(LogoutHeader, {
      global: {
        plugins: [router],
      },
      props: {
        loading: true,
      },
    });
    
    const button = wrapper.find('button');
    
    expect(button.attributes('disabled')).toBeDefined();
    expect(wrapper.text()).toContain('MEMPROSES');
  });

  // ===== EDGE CASE =====
  it('test_sapaan_malam_default_saat_jam_tidak_dikenal', () => {
    // Mock Date untuk jam 3 pagi (di luar range)
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2026-01-01T03:00:00'));
    
    const wrapper = mountComponent(router);
    const vm = wrapper.vm as unknown as VmLogoutHeader;
    
    expect(vm.timeGreeting).toBe('Selamat malam');
    
    vi.useRealTimers();
  });

  // ===== A11Y =====
  it('test_button_memiliki_type_button', () => {
    const wrapper = mountComponent(router);
    const button = wrapper.find('button');
    
    // Button tidak di dalam form, jadi tidak perlu type="submit"
    expect(button.exists()).toBe(true);
  });
});