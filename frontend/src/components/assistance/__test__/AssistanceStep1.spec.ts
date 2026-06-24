import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import AssistanceStep1 from '../AssistanceStep1.vue';
import type { AssistanceProgramSchema } from '../../../types/assistance';

const routerPush = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: routerPush }),
}));

const mockPrograms: AssistanceProgramSchema[] = [
  {
    id: '1', title: 'BLT Dana Desa', slug: 'blt', description: 'Bantuan Langsung Tunai',
    badge: 'Aktif', banner_url: null, start_date: '2024-01-01', end_date: '2024-12-31',
    quota_total: 1000, benefit_amount: 500000, inputs: [], documents: [], has_submitted: false,
  },
  {
    id: '2', title: 'PKH', slug: 'pkh', description: 'Program Keluarga Harapan',
    badge: 'Aktif', banner_url: null, start_date: null, end_date: null,
    quota_total: null, benefit_amount: 300000, inputs: [], documents: [], has_submitted: true,
  },
];

function mountComponent(programs: AssistanceProgramSchema[] = mockPrograms) {
  return mount(AssistanceStep1, {
    props: { programs },
    global: {
      stubs: {
        AlreadySubmittedModal: { template: '<div v-if="open">Modal</div>', props: ['open', 'programTitle'] },
      },
    },
  });
}

describe('AssistanceStep1.vue', () => {

  it('test_menampilkan_heading_pilih_bantuan', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Pilih Bantuan');
  });

  it('test_menampilkan_semua_program', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('BLT Dana Desa');
    expect(wrapper.text()).toContain('PKH');
  });

  it('test_emit_select_saat_klik_program_belum_terdaftar', async () => {
    const wrapper = mountComponent();
    const cards = wrapper.findAll('.group');
    await cards[0].trigger('click');
    expect(wrapper.emitted('select')).toBeTruthy();
  });

  it('test_menampilkan_deskripsi_program', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Bantuan Langsung Tunai');
  });

  it('test_menampilkan_pesan_kosong_saat_tidak_ada_program', () => {
    const wrapper = mountComponent([]);
    expect(wrapper.text()).toContain('Belum Ada Program');
  });

  it('test_menampilkan_badge_sudah_terdaftar', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Sudah Terdaftar');
  });

  it('test_menampilkan_modal_saat_klik_program_sudah_terdaftar', async () => {
    const wrapper = mountComponent();
    const cards = wrapper.findAll('.group');
    await cards[1].trigger('click');
    expect(wrapper.text()).toContain('Modal');
  });

  it('test_emit_refresh_saat_klik_muat_ulang', async () => {
    const wrapper = mountComponent([]);
    const button = wrapper.find('button');
    await button.trigger('click');
    expect(wrapper.emitted('refresh')).toBeTruthy();
  });
});