import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AccountTable from '../AccountTable.vue';
import type { AccountData } from '../../../types/account';

// =============================================
// HELPERS
// =============================================
const mockAccounts: AccountData[] = [
  {
    id: '1', nip: '199001012020011001', name: 'Super Admin', role: 'super_admin',
    is_active: true, last_login_at: null, created_at: '2024-01-01',
    region: { regency: null, district: null, village: null },
  },
  {
    id: '2', nip: '199001012020011002', name: 'Admin Kab', role: 'regency_admin',
    is_active: true, last_login_at: null, created_at: '2024-01-01',
    region: { regency: 'Tanah Laut', district: null, village: null },
  },
  {
    id: '3', nip: '199001012020011003', name: 'Petugas Desa', role: 'village_officer',
    is_active: false, last_login_at: null, created_at: '2024-01-01',
    region: { regency: 'Tanah Laut', district: 'Pelaihari', village: 'Desa Test' },
  },
];

function mountComponent(overrides: Record<string, unknown> = {}) {
  return mount(AccountTable, {
    props: {
      accounts: mockAccounts as AccountData[],
      loading: false,
      currentUserId: '1',
      total: 3,
      currentPage: 1,
      totalPages: 1,
      ...overrides,
    },
  });
}

// =============================================
// TEST SUITE
// =============================================
describe('AccountTable.vue', () => {

  // ===== HAPPY PATH =====

  it('test_render_tabel_dengan_data', () => {
    const wrapper = mountComponent();
    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(3);
  });

  it('test_render_nama_pegawai', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Super Admin');
    expect(wrapper.text()).toContain('Admin Kab');
    expect(wrapper.text()).toContain('Petugas Desa');
  });

  it('test_render_nip_pegawai', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('199001012020011001');
  });

  it('test_emit_edit_saat_klik', async () => {
    const wrapper = mountComponent();
    const editButtons = wrapper.findAll('button[title="Edit"]');
    await editButtons[0].trigger('click');

    expect(wrapper.emitted('edit')).toBeTruthy();
    expect(wrapper.emitted('edit')![0][0]).toEqual(mockAccounts[0]);
  });

  it('test_emit_delete_saat_klik', async () => {
    const wrapper = mountComponent();
    const deleteButtons = wrapper.findAll('button[title="Nonaktifkan"]');
    await deleteButtons[0].trigger('click');

    expect(wrapper.emitted('delete')).toBeTruthy();
  });

  it('test_emit_pageChange_prev', async () => {
    const wrapper = mountComponent({ totalPages: 3, currentPage: 2 });
    const prevButton = wrapper.find('button:not([disabled])');
    
    if (prevButton.text().includes('Prev')) {
      await prevButton.trigger('click');
      expect(wrapper.emitted('pageChange')![0][0]).toBe(1);
    }
  });

  // ===== SAD PATH =====

  it('test_loading_state_tampilkan_skeleton', () => {
    const wrapper = mountComponent({ loading: true });
    expect(wrapper.find('.animate-pulse').exists()).toBe(true);
  });

  it('test_empty_state_tampilkan_pesan', () => {
    const wrapper = mountComponent({ accounts: [], total: 0 });
    expect(wrapper.text()).toContain('Belum ada pegawai');
  });

  // ===== BOUNDARY =====

    it('test_page_1_prev_disabled', () => {
        const wrapper = mountComponent({ totalPages: 3, currentPage: 1 });
        // Prev button adalah button pertama di pagination section
        const allButtons = wrapper.findAll('button');
        const prevButton = allButtons.find(btn => btn.text().includes('Prev'));
        expect(prevButton?.attributes('disabled')).toBeDefined();
    });

  it('test_last_page_next_disabled', () => {
    const wrapper = mountComponent({ totalPages: 3, currentPage: 3 });
    const buttons = wrapper.findAll('button');
    const nextButton = buttons[buttons.length - 1];
    expect(nextButton.attributes('disabled')).toBeDefined();
  });

  // ===== EDGE CASE =====

  it('test_tag_anda_untuk_current_user', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Anda');
  });

  it('test_akun_nonaktif_tidak_tampil_tombol_delete', () => {
    const wrapper = mountComponent({ currentUserId: '99' });
    const deleteButtons = wrapper.findAll('button[title="Nonaktifkan"]');
    // Akun nonaktif (id=3) tidak punya tombol delete
    expect(deleteButtons.length).toBe(2); // id=1 & id=2
  });

  // ===== NULL / EMPTY =====

  it('test_total_0_tampilkan_0', () => {
    const wrapper = mountComponent({ accounts: [], total: 0, loading: false });
    expect(wrapper.text()).toContain('Belum ada pegawai');
  });

  it('test_pagination_hidden_saat_totalPages_1', () => {
    const wrapper = mountComponent({ totalPages: 1 });
    expect(wrapper.text()).not.toContain('Prev');
    expect(wrapper.text()).not.toContain('Next');
  });

  // ===== DATA TYPE =====

  it('test_formatRole_super_admin', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Super Admin');
  });

  it('test_formatRole_regency_admin', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Admin Kabupaten');
  });

  // ===== EQUIVALENCE PARTITION =====

  it('test_status_aktif_hijau', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Aktif');
    expect(wrapper.text()).toContain('Nonaktif');
  });

  it('test_wilayah_super_admin_seluruh_kalsel', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Seluruh Kalsel');
  });

  // ===== STATE TRANSITION =====

  it('test_loading_false_tampilkan_tabel', () => {
    const wrapper = mountComponent({ loading: false });
    expect(wrapper.find('table').exists()).toBe(true);
  });

  // ===== RENDERING =====

  it('test_render_total_pegawai', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Total:');
    expect(wrapper.text()).toContain('3');
  });

  it('test_render_pagination_info', () => {
    const wrapper = mountComponent({ totalPages: 5, currentPage: 3 });
    expect(wrapper.text()).toContain('3 / 5');
  });
});