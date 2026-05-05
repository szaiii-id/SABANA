// src/pages/dashboard/__tests__/History.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import History from '../History.vue';

const { 
  fetchMySubmissionsMock, 
  deleteAssistanceMock, 
  pushMock 
} = vi.hoisted(() => ({
  fetchMySubmissionsMock: vi.fn(),
  deleteAssistanceMock: vi.fn(),
  pushMock: vi.fn()
}));

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock })
}));

vi.mock('../../../composables/useAssistance', () => ({
  useAssistance: () => ({
    fetchMySubmissions: fetchMySubmissionsMock,
    deleteAssistance: deleteAssistanceMock,
    isLoading: ref(false), // ✅ PAKAI ref() ASLI
  })
}));

function createMockSubmission(overrides = {}) {
  return {
    id: 'uuid-001',
    registration_number: 'REG-001',
    status: 'pending',
    program: { name: 'Bantuan Pendidikan' },
    admin_note: null,
    ...overrides,
  };
}

describe('History.vue - Professional QA Test Suite', () => {

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
    return mount(History, {
      global: {
        stubs: {
          ChevronRightIcon: true,
          ExclamationTriangleIcon: true,
          TrashIcon: true,
          ExclamationCircleIcon: true,
          FingerPrintIcon: true,
          InboxStackIcon: true,
          AcademicCapIcon: true,
          HomeModernIcon: true,
          ArrowPathIcon: true,
        }
      }
    });
  };

  // ============================================================
  // FETCH & RENDER DATA
  // ============================================================
  describe('Fetch & Render Data', () => {

    it('[FETCH-01] Memanggil fetchMySubmissions saat mounted', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });
      mountComponent();
      await flushPromises();
      expect(fetchMySubmissionsMock).toHaveBeenCalledTimes(1);
    });

    it('[FETCH-02] Render daftar submissions', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [
          createMockSubmission({ registration_number: 'REG-001', program: { name: 'Bantuan Pendidikan' } }),
          createMockSubmission({ id: 'uuid-002', registration_number: 'REG-002', program: { name: 'Beasiswa Unggul' } }),
        ]
      });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('REG-001');
      expect(wrapper.text()).toContain('Bantuan Pendidikan');
      expect(wrapper.text()).toContain('REG-002');
      expect(wrapper.text()).toContain('Beasiswa Unggul');
    });

    it('[FETCH-03] Handle response tanpa wrapper data (array langsung)', async () => {
      fetchMySubmissionsMock.mockResolvedValue([
        createMockSubmission({ registration_number: 'REG-ARR' })
      ]);

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('REG-ARR');
    });

    it('[FETCH-04] Handle response null/undefined tanpa crash', async () => {
      fetchMySubmissionsMock.mockResolvedValue(null);

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.submissions).toEqual([]);
    });

    it('[FETCH-05] Handle fetch error tanpa crash', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      fetchMySubmissionsMock.mockRejectedValue(new Error('Network Error'));

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.submissions).toEqual([]);
      expect(consoleSpy).toHaveBeenCalled();

      consoleSpy.mockRestore();
    });

    it('[FETCH-06] Menampilkan total berkas dengan benar', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [
          createMockSubmission(),
          createMockSubmission({ id: 'uuid-002', registration_number: 'REG-002' }),
          createMockSubmission({ id: 'uuid-003', registration_number: 'REG-003' }),
        ]
      });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('3');
    });
  });

  // ============================================================
  // LOADING & EMPTY STATES
  // ============================================================
  describe('Loading & Empty States', () => {

    it.skip('[STATE-01] Menampilkan skeleton loading ref harus true', async () => {
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

    it('[STATE-02] Menampilkan empty state jika tidak ada submissions', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      // Cek div empty state ada
      const emptyDiv = wrapper.find('.py-20');
      expect(emptyDiv.exists()).toBe(true);
      expect(wrapper.text()).toContain('Total Berkas0');
    });

    it('[STATE-03] Total berkas = 0 saat empty', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('0');
    });
  });

  // ============================================================
  // STATUS LABELS
  // ============================================================
  describe('Status Labels', () => {

    const statusTests = [
      { status: 'pending', expected: 'Proses Verifikasi' },
      { status: 'needs_revision', expected: 'Perlu Revisi' },
      { status: 'validated', expected: 'Tervalidasi' },
      { status: 'approved', expected: 'Disetujui' },
      { status: 'rejected', expected: 'Ditolak' },
      { status: 'unknown_status', expected: 'unknown_status' },
    ];

    statusTests.forEach(({ status, expected }) => {
      it(`[STATUS] Status "${status}" → "${expected}"`, async () => {
        fetchMySubmissionsMock.mockResolvedValue({
          data: [createMockSubmission({ status })]
        });

        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain(expected);
      });
    });
  });

  // ============================================================
  // CATATAN PERBAIKAN
  // ============================================================
  describe('Catatan Perbaikan', () => {

    it('[NOTE-01] Menampilkan admin_note jika status needs_revision', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [
          createMockSubmission({
            status: 'needs_revision',
            admin_note: 'KTP buram, silakan unggah ulang.'
          })
        ]
      });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Catatan Perbaikan:');
      expect(wrapper.text()).toContain('KTP buram');
    });

    it('[NOTE-02] TIDAK menampilkan admin_note jika status bukan needs_revision', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [
          createMockSubmission({
            status: 'pending',
            admin_note: 'Ini tidak boleh muncul'
          })
        ]
      });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).not.toContain('Catatan Perbaikan:');
    });

    it('[NOTE-03] TIDAK menampilkan catatan jika admin_note null', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [
          createMockSubmission({
            status: 'needs_revision',
            admin_note: null
          })
        ]
      });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).not.toContain('Catatan Perbaikan:');
    });
  });

  // ============================================================
  // NAVIGASI
  // ============================================================
  describe('Navigasi', () => {

    it('[NAV-01] Status pending → edit', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ id: 'uuid-pending', status: 'pending' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const card = wrapper.find('.cursor-pointer');
      await card.trigger('click');

      expect(localStorage.setItem).toHaveBeenCalledWith('SABANA_DRAFT', expect.any(String));
      expect(pushMock).toHaveBeenCalledWith({ name: 'assistance.edit', params: { id: 'uuid-pending' } });
    });

    it('[NAV-02] Status needs_revision → edit', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ id: 'uuid-rev', status: 'needs_revision' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const card = wrapper.find('.cursor-pointer');
      await card.trigger('click');

      expect(pushMock).toHaveBeenCalledWith({ name: 'assistance.edit', params: { id: 'uuid-rev' } });
    });

    it('[NAV-03] Status validated → detail', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ id: 'uuid-val', status: 'validated' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const card = wrapper.find('.cursor-pointer');
      await card.trigger('click');

      expect(pushMock).toHaveBeenCalledWith({ name: 'assistance.detail', params: { id: 'uuid-val' } });
    });

    it('[NAV-04] Status approved → detail', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ id: 'uuid-app', status: 'approved' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const card = wrapper.find('.cursor-pointer');
      await card.trigger('click');

      expect(pushMock).toHaveBeenCalledWith({ name: 'assistance.detail', params: { id: 'uuid-app' } });
    });

    it('[NAV-05] Status rejected → detail', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ id: 'uuid-rej', status: 'rejected' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const card = wrapper.find('.cursor-pointer');
      await card.trigger('click');

      expect(pushMock).toHaveBeenCalledWith({ name: 'assistance.detail', params: { id: 'uuid-rej' } });
    });

    it('[NAV-06] TIDAK simpan localStorage jika non-editable', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ id: 'uuid-val', status: 'validated' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const card = wrapper.find('.cursor-pointer');
      await card.trigger('click');

      expect(localStorage.setItem).not.toHaveBeenCalled();
    });
  });

  // ============================================================
  // DELETE MODAL
  // ============================================================
  describe('Delete Modal', () => {

    it('[DEL-01] Tombol hapus muncul untuk status pending', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ status: 'pending' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const deleteBtn = wrapper.find('.bg-red-50');
      expect(deleteBtn.exists()).toBe(true);
    });

    it('[DEL-02] Tombol hapus muncul untuk status needs_revision', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ status: 'needs_revision' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const deleteBtn = wrapper.find('.bg-red-50');
      expect(deleteBtn.exists()).toBe(true);
    });

    it('[DEL-03] Tombol hapus TIDAK muncul untuk status validated', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ status: 'validated' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const deleteBtn = wrapper.find('.bg-red-50');
      expect(deleteBtn.exists()).toBe(false);
    });

    it('[DEL-04] Membuka modal konfirmasi', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ registration_number: 'REG-DEL', status: 'pending' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.showDeleteModal = true;
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Batalkan Berkas?');
    });

    it('[DEL-05] Menutup modal saat klik Batal', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ status: 'pending' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.showDeleteModal = true;
      await wrapper.vm.$nextTick();

      const buttons = wrapper.findAll('button');
      const cancelBtn = buttons.find(b => b.text() === 'Batal');
      await cancelBtn!.trigger('click');

      expect(vm.showDeleteModal).toBe(false);
    });

    it('[DEL-06] Menutup modal saat klik overlay', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ status: 'pending' })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.showDeleteModal = true;
      await wrapper.vm.$nextTick();

      const overlay = wrapper.find('.backdrop-blur-sm');
      await overlay.trigger('click');

      expect(vm.showDeleteModal).toBe(false);
    });

    it('[DEL-07] Eksekusi delete dan reload data', async () => {
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });
      deleteAssistanceMock.mockResolvedValue({ success: true });

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.selectedRegNumber = 'REG-DEL';
      vm.showDeleteModal = true;
      await wrapper.vm.$nextTick();

      fetchMySubmissionsMock.mockClear();

      const buttons = wrapper.findAll('button');
      const confirmBtn = buttons.find(b => b.text().includes('Ya, Hapus'));
      await confirmBtn!.trigger('click');
      await flushPromises();

      expect(deleteAssistanceMock).toHaveBeenCalledWith('REG-DEL');
      expect(vm.showDeleteModal).toBe(false);
      expect(fetchMySubmissionsMock).toHaveBeenCalled();
    });

    it('[DEL-08] Handle error saat delete gagal', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      
      fetchMySubmissionsMock.mockResolvedValue({ data: [] });
      deleteAssistanceMock.mockRejectedValue(new Error('Delete Failed'));

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.selectedRegNumber = 'REG-DEL';
      vm.showDeleteModal = true;
      await wrapper.vm.$nextTick();

      const buttons = wrapper.findAll('button');
      const confirmBtn = buttons.find(b => b.text().includes('Ya, Hapus'));
      await confirmBtn!.trigger('click');
      await flushPromises();

      expect(consoleSpy).toHaveBeenCalled();
      expect(vm.isDeleting).toBe(false);
      
      consoleSpy.mockRestore();
    });
  });

  // ============================================================
  // PROGRAM ICON
  // ============================================================
  describe('Program Icon', () => {

    it('[ICON-01] Menampilkan program rumah/masjid', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ program: { name: 'Bantuan Renovasi Rumah' } })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Bantuan Renovasi Rumah');
    });

    it('[ICON-02] Menampilkan program beasiswa/pintar', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ program: { name: 'Beasiswa Anak Pintar' } })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Beasiswa Anak Pintar');
    });

    it('[ICON-03] Menampilkan program default', async () => {
      fetchMySubmissionsMock.mockResolvedValue({
        data: [createMockSubmission({ program: { name: 'Bantuan Umum' } })]
      });

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Bantuan Umum');
    });
  });
});