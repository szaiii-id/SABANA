import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import DashboardNavbar from '../DashboardNavbar.vue';

describe('DashboardNavbar.vue', () => {
  it('mengirimkan event logout saat tombol Keluar diklik', async () => {
    const wrapper = mount(DashboardNavbar, {
      global: {
        stubs: ['router-link']
      }
    });

    // Sesuaikan dengan teks asli di template Mas: "Keluar"
    const logoutBtn = wrapper.find('button');
    expect(logoutBtn.text()).toContain('Keluar');

    await logoutBtn.trigger('click');

    // Memastikan event 'logout' terpancar keluar
    expect(wrapper.emitted()).toHaveProperty('logout');
    expect(wrapper.emitted('logout')).toHaveLength(1);
  });
});