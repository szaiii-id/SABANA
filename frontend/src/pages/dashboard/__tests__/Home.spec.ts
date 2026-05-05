// src/pages/dashboard/__tests__/Home.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Home from '../Home.vue';

const { pushMock, fetchMySubmissionsMock } = vi.hoisted(() => ({
  pushMock: vi.fn(),
  fetchMySubmissionsMock: vi.fn()
}));

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock })
}));

vi.mock('../../../composables/useAssistance', () => ({
  useAssistance: () => ({
    fetchMySubmissions: fetchMySubmissionsMock,
    isLoading: { value: false }
  })
}));

function createMockSubmission(overrides = {}) {
  return {
    id: 'uuid-001',
    registration_number: 'REG-001',
    status: 'pending',
    program: { name: 'Bantuan Pendidikan' },
    ...overrides,
  };
}

describe('Home.vue - Professional QA Test Suite', () => {

  beforeEach(() => {
    vi.clearAllMocks();
  });

  const mountComponent = () => {
    return mount(Home, {
      global: {
        stubs: {
          InboxStackIcon: true,
          CheckBadgeIcon: true,
          UserIcon: true,
          ExclamationTriangleIcon: true,
          ShieldCheckIcon: true,
          ArrowRightIcon: true,
        }
      }
    });
  };

  // ============================================================
  // INISIALISASI & FETCH DATA
  // ============================================================
  describe('Inisialisasi & Fetch Data', () => {

    it('[INIT-01] Memanggil fetchMySubmissions saat mounted', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      mountComponent();
      await flushPromises();

      expect(fetchMySubmissionsMock).toHaveBeenCalledTimes(1);
    });

    it('[INIT-02] Handle response dengan wrapper data', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ registration_number: 'REG-001' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.submissions).toHaveLength(1);
      expect(vm.submissions[0].registration_number).toBe('REG-001');
    });

    it('[INIT-03] Handle response array langsung (tanpa wrapper)', async () => {
      fetchMySubmissionsMock.mockResolvedValue([
        createMockSubmission({ registration_number: 'REG-DIRECT' })
      ]);

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.submissions).toHaveLength(1);
      expect(vm.submissions[0].registration_number).toBe('REG-DIRECT');
    });

    it('[INIT-04] Handle response null/undefined tanpa crash', async () => {
      fetchMySubmissionsMock.mockResolvedValue(null);

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.submissions).toEqual([]);
    });

    it('[INIT-05] Handle fetch error tanpa crash', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      fetchMySubmissionsMock.mockRejectedValue(new Error('Network Error'));

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.submissions).toEqual([]);
      expect(consoleSpy).toHaveBeenCalled();

      consoleSpy.mockRestore();
    });
  });

  // ============================================================
  // EMPTY STATE (0 Submissions)
  // ============================================================
  describe('Empty State (0 Submissions)', () => {

    it('[EMPTY-01] Menampilkan tombol daftar baru saat 0 submission', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      // Tombol MULAI DAFTAR BARU muncul
      expect(wrapper.text()).toContain('MULAI DAFTAR BARU');
      // Tombol BUKA RIWAYAT BERKAS tidak muncul
      expect(wrapper.text()).not.toContain('BUKA RIWAYAT BERKAS');
    });

    it('[EMPTY-02] Klik tombol navigasi ke assistance', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      const btn = wrapper.find('button');
      await btn.trigger('click');

      expect(pushMock).toHaveBeenCalledWith({ name: 'assistance' });
    });

    it('[EMPTY-03] Tidak menampilkan info "Terakhir"', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).not.toContain('Terakhir:');
    });

    it('[EMPTY-04] Indikator status muncul saat 0 submission', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      const html = wrapper.html();
      expect(html).toContain('animate-pulse');
      expect(html).toContain('shadow-lg');
    });
  });

  // ============================================================
  // MEMILIKI SUBMISSIONS
  // ============================================================
  describe('Memiliki Submissions', () => {

    it('[HAS-01] Tombol BUKA RIWAYAT BERKAS muncul saat ada submission', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [
          createMockSubmission(),
          createMockSubmission({ id: 'uuid-002', registration_number: 'REG-002' }),
          createMockSubmission({ id: 'uuid-003', registration_number: 'REG-003' }),
        ]
      });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('BUKA RIWAYAT BERKAS');
      expect(wrapper.text()).not.toContain('MULAI DAFTAR BARU');
    });

    it('[HAS-02] Single submission tetap tombol riwayat', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission()]
      });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('BUKA RIWAYAT BERKAS');
    });

    it('[HAS-03] Info "Terakhir" muncul dengan nama program', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [
          createMockSubmission({ program: { name: 'Beasiswa Unggul' } }),
        ]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      
      expect(vm.hasSubmissions).toBe(true);
      expect(vm.latestSubmission.program.name).toBe('Beasiswa Unggul');
      
      expect(wrapper.text()).toContain('BUKA RIWAYAT BERKAS');
    });

    it('[HAS-04] Klik tombol navigasi ke history', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission()]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const btn = wrapper.find('button');
      await btn.trigger('click');

      expect(pushMock).toHaveBeenCalledWith({ name: 'history' });
    });

    it('[HAS-05] Indikator status muncul saat ada submission', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission()]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const html = wrapper.html();
      expect(html).toContain('animate-pulse');
      expect(html).toContain('shadow-lg');
    });

    it('[HAS-06] latestSubmission computed return submission pertama', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [
          createMockSubmission({ registration_number: 'REG-FIRST', program: { name: 'Bantuan Pertama' } }),
          createMockSubmission({ id: 'uuid-002', registration_number: 'REG-SECOND' }),
        ]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      
      expect(vm.latestSubmission).not.toBeNull();
      expect(vm.latestSubmission.registration_number).toBe('REG-FIRST');
      expect(vm.latestSubmission.program.name).toBe('Bantuan Pertama');
    });
  });

  // ============================================================
  // LOADING STATE
  // ============================================================
  describe('Loading State', () => {

    it('[LOAD-01] Menampilkan skeleton saat loading', async () => {
      let resolvePromise: any;
      fetchMySubmissionsMock.mockReturnValue(new Promise(resolve => {
        resolvePromise = resolve;
      }));

      const wrapper = mountComponent();
      await wrapper.vm.$nextTick();

      const skeletons = wrapper.findAll('.animate-pulse');
      expect(skeletons.length).toBeGreaterThan(0);

      resolvePromise({ data: [] });
      await flushPromises();
    });

    it('[LOAD-02] Indikator tidak muncul saat loading', async () => {
      let resolvePromise: any;
      fetchMySubmissionsMock.mockReturnValue(new Promise(resolve => {
        resolvePromise = resolve;
      }));

      const wrapper = mountComponent();
      await wrapper.vm.$nextTick();

      // v-if="!isLoading" → indikator tidak muncul
      const indicators = wrapper.findAll('.rounded-full');
      expect(indicators.length).toBe(0);

      resolvePromise({ data: [] });
      await flushPromises();
    });
  });

  // ============================================================
  // NAVIGASI CARD
  // ============================================================
  describe('Navigasi Card', () => {

    it('[NAV-01] Card "Data Saya" navigasi ke profile', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      const cards = wrapper.findAll('.cursor-pointer');
      await cards[0].trigger('click');

      expect(pushMock).toHaveBeenCalledWith({ name: 'profile' });
    });

    it('[NAV-02] Card "Lapor Warga" navigasi ke report', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      const cards = wrapper.findAll('.cursor-pointer');
      await cards[1].trigger('click');

      expect(pushMock).toHaveBeenCalledWith({ name: 'report' });
    });

    it('[NAV-03] Card "Data Saya" menampilkan teks yang benar', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Data Saya');
      expect(wrapper.text()).toContain('Identitas & Berkas');
    });

    it('[NAV-04] Card "Lapor Warga" menampilkan teks yang benar', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Lapor Warga');
      expect(wrapper.text()).toContain('Aspirasi & Keluhan');
    });
  });

  // ============================================================
  // UI ELEMENTS
  // ============================================================
  describe('UI Elements', () => {

    it('[UI-01] Menampilkan header "Portal Warga Banua"', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Portal Warga Banua');
    });

    it('[UI-02] Menampilkan "Selamat Datang"', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Selamat Datang');
    });

    it('[UI-03] Menampilkan teks SABANA', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('SABANA');
    });

    it('[UI-04] Menampilkan "Status Pengajuan"', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Status Pengajuan');
    });
  });

  // ============================================================
  // EDGE CASES
  // ============================================================
  describe('Edge Cases', () => {

    it('[EDGE-01] latestSubmission return null jika array kosong', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      
      // Gunakan computed
      expect(vm.latestSubmission).toBeNull();
      expect(vm.hasSubmissions).toBe(false);
    });

    it('[EDGE-02] Submission tanpa program tidak crash', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [
          createMockSubmission({ program: null })
        ]
      });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('BUKA RIWAYAT BERKAS');
    });

    it('[EDGE-03] latestSubmission handle program null', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [
          createMockSubmission({ registration_number: 'REG-NOPROG', program: null })
        ]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      
      // Gunakan computed
      expect(vm.latestSubmission).not.toBeNull();
      expect(vm.latestSubmission.registration_number).toBe('REG-NOPROG');
      expect(vm.latestSubmission.program).toBeNull();
      expect(vm.hasSubmissions).toBe(true);
    });
  });

  // ============================================================
  // RESPONSE FORMAT VARIATIONS
  // ============================================================
  describe('Response Format Variations', () => {

    it('[RESP-01] Response dengan structure { data: [...] }', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission()]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.submissions).toHaveLength(1);
    });

    it('[RESP-02] Response array langsung', async () => {
      fetchMySubmissionsMock.mockResolvedValue([
        createMockSubmission()
      ]);

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.submissions).toHaveLength(1);
    });

    it('[RESP-03] Response undefined → fallback ke []', async () => {
      fetchMySubmissionsMock.mockResolvedValue(undefined);

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.submissions).toEqual([]);
    });
  });
});