import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import Home from '../Home.vue';
import { useAssistance } from '../../../composables/useAssistance';

vi.mock('../../../composables/useAssistance', () => ({
  useAssistance: vi.fn(),
}));

describe('Home.vue', () => {
  let router: any;

  beforeEach(() => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockResolvedValue({ data: [] }),
      isLoading: { value: false },
    });

    router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/', name: 'dashboard.home', component: Home },
        { path: '/history', name: 'history', component: { template: '<div/>' } },
        { path: '/assistance', name: 'assistance', component: { template: '<div/>' } },
        { path: '/profile', name: 'profile', component: { template: '<div/>' } },
        { path: '/report', name: 'report', component: { template: '<div/>' } },
      ],
    });
  });

  function mountHome() {
    return mount(Home, {
      global: {
        plugins: [router],
        stubs: {
          ShieldCheckIcon: { template: '<div/>' },
          CheckBadgeIcon: { template: '<div/>' },
          UserIcon: { template: '<div/>' },
          ExclamationTriangleIcon: { template: '<div/>' },
        },
      },
    });
  }

  // =============================================
  // RENDERING — 3 TEST
  // =============================================

  it('test_menampilkan_heading_selamat_datang', async () => {
    await router.push({ name: 'dashboard.home' });
    await router.isReady();
    const wrapper = mountHome();
    await flushPromises();
    expect(wrapper.text()).toContain('Selamat Datang');
  });

  it('test_menampilkan_tombol_mulai_daftar_baru_saat_kosong', async () => {
    await router.push({ name: 'dashboard.home' });
    await router.isReady();
    const wrapper = mountHome();
    await flushPromises();
    expect(wrapper.text()).toContain('MULAI DAFTAR BARU');
  });

  it('test_menampilkan_menu_navigasi', async () => {
    await router.push({ name: 'dashboard.home' });
    await router.isReady();
    const wrapper = mountHome();
    await flushPromises();
    expect(wrapper.text()).toContain('Data Saya');
    expect(wrapper.text()).toContain('Lapor Warga');
  });

  // =============================================
  // LOADING STATE — 1 TEST
  // =============================================

  it('test_menampilkan_loading_saat_fetch', async () => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockReturnValue(new Promise(() => {})),
      isLoading: { value: true },
    });

    await router.push({ name: 'dashboard.home' });
    await router.isReady();
    const wrapper = mountHome();
    await flushPromises();
    expect(wrapper.html()).toContain('animate-pulse');
  });

  // =============================================
  // CONDITIONAL — 1 TEST
  // =============================================

  it('test_menampilkan_tombol_buka_riwayat_saat_ada_submission', async () => {
    (useAssistance as any).mockReturnValue({
      fetchMySubmissions: vi.fn().mockResolvedValue({
        data: [{ program: { name: 'BLT' } }],
      }),
      isLoading: { value: false },
    });

    await router.push({ name: 'dashboard.home' });
    await router.isReady();
    const wrapper = mountHome();
    await flushPromises();
    expect(wrapper.text()).toContain('BUKA RIWAYAT BERKAS');
  });
});