// src/pages/dashboard/__tests__/AssistanceDetail.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import AssistanceDetail from '../AssistanceDetail.vue';

const { fetchDetailMock, downloadPdfMock, pushMock, backMock } = vi.hoisted(() => ({
  fetchDetailMock: vi.fn(),
  downloadPdfMock: vi.fn(),
  pushMock: vi.fn(),
  backMock: vi.fn()
}));

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock, back: backMock }),
  useRoute: () => ({ params: { id: 'test-uuid-123' } })
}));

vi.mock('../../../composables/useAssistance', () => ({
  useAssistance: () => ({
    fetchDetail: fetchDetailMock,
    downloadPdf: downloadPdfMock,
    isLoading: { value: false, __v_isRef: true }
  })
}));

function createMockSubmission(overrides = {}) {
  return {
    id: 'test-uuid-123',
    registration_number: 'REG-999',
    status: 'pending',
    program: { name: 'Program Bantuan Sosial' },
    regency: { name: 'Kabupaten Tapin' },
    district: { name: 'Kecamatan Tapin' },
    village: { name: 'Desa Banua' },
    disbursement_method: 'village_cash',
    bank_account_number: '',
    citizen: { full_name: 'John Doe', nik: '1234567890123456' },
    submission_data: {},
    evidences: [],
    ...overrides,
  };
}

describe('AssistanceDetail.vue - Professional QA Test Suite', () => {

  beforeEach(() => {
    vi.clearAllMocks();
  });

  const mountComponent = () => {
    return mount(AssistanceDetail, {
      global: {
        stubs: {
          ArrowLeftIcon: true,
          PhotoIcon: true,
          IdentificationIcon: true,
          CreditCardIcon: true,
          ArrowDownTrayIcon: true,
          CheckBadgeIcon: true,
          MapPinIcon: true
        }
      }
    });
  };

  // ============================================================
  // INITIAL LOADING
  // ============================================================
  describe('Inisialisasi & Fetch Data', () => {
    
    it('[INIT-01] Fetch detail dengan ID dari route params', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission());

      const wrapper = mountComponent();
      await flushPromises();

      expect(fetchDetailMock).toHaveBeenCalledWith('test-uuid-123');
      expect(wrapper.text()).toContain('REG-999');
    });

    it('[INIT-02] Redirect ke history jika fetch gagal', async () => {
      fetchDetailMock.mockRejectedValue(new Error('Not Found'));

      mountComponent();
      await flushPromises();

      expect(pushMock).toHaveBeenCalledWith({ name: 'history' });
    });

    it('[INIT-03] Tidak crash jika fetch return null', async () => {
      fetchDetailMock.mockResolvedValue(null);

      const wrapper = mountComponent();
      await flushPromises();
      await wrapper.vm.$nextTick();

      // Komponen tidak crash, konten tidak dirender
      expect(wrapper.find('.max-w-4xl').exists()).toBe(false);
    });

    it('[INIT-04] Menampilkan data citizen dengan benar', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({
        citizen: { full_name: 'Budi Santoso', nik: '6309123456789012' }
      }));

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Budi Santoso');
      expect(wrapper.text()).toContain('6309123456789012');
    });

    it('[INIT-05] Menampilkan fallback jika citizen null', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({
        citizen: null
      }));

      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('---');
    });
  });

  // ============================================================
  // STATUS DISPLAY
  // ============================================================
  describe('Status Display', () => {
    
    it('[STATUS-01] Menampilkan status pending', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ status: 'pending' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text().toLowerCase()).toContain('pending');
    });

    it('[STATUS-02] Menampilkan status validated', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ status: 'validated' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text().toLowerCase()).toContain('validated');
    });

    it('[STATUS-03] Menampilkan status needs_revision dengan format rapi', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ status: 'needs_revision' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      // String(status).replace('_', ' ') → "needs revision"
      expect(wrapper.text().toLowerCase()).toContain('needs revision');
    });

    it('[STATUS-04] Menampilkan status rejected', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ status: 'rejected' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text().toLowerCase()).toContain('rejected');
    });

    it('[STATUS-05] Menampilkan status approved', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ status: 'approved' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text().toLowerCase()).toContain('approved');
    });
  });

  // ============================================================
  // DOWNLOAD BUTTON
  // ============================================================
  describe('Download Button', () => {
    
    it('[DL-01] Tombol download muncul untuk status validated', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ status: 'validated' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text().toLowerCase()).toContain('unduh bukti sah');
    });

    it('[DL-02] Tombol download muncul untuk status approved', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ status: 'approved' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text().toLowerCase()).toContain('unduh bukti sah');
    });

    it('[DL-03] Tombol download muncul untuk status completed', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ status: 'completed' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text().toLowerCase()).toContain('unduh bukti sah');
    });

    it('[DL-04] Tombol download TIDAK muncul untuk status pending', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ status: 'pending' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text().toLowerCase()).not.toContain('unduh bukti sah');
    });

    it('[DL-05] Tombol download TIDAK muncul untuk status needs_revision', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ status: 'needs_revision' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text().toLowerCase()).not.toContain('unduh bukti sah');
    });

    it('[DL-06] Tombol download TIDAK muncul untuk status rejected', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ status: 'rejected' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text().toLowerCase()).not.toContain('unduh bukti sah');
    });

    it('[DL-07] Memanggil downloadPdf dengan parameter benar', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({
        id: 'uuid-download',
        registration_number: 'REG-001',
        status: 'validated'
      }));
      
      const wrapper = mountComponent();
      await flushPromises();

      const buttons = wrapper.findAll('button');
      const btnDownload = buttons.find(b => b.text().toLowerCase().includes('unduh bukti sah'));
      
      expect(btnDownload).toBeDefined();
      await btnDownload!.trigger('click');
      await flushPromises();

      expect(downloadPdfMock).toHaveBeenCalledWith('uuid-download', 'SABANA_REG-001');
    });

    it('[DL-08] Tidak crash jika download dipanggil saat submission null', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({
        status: 'validated'
      }));
      
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.submission = null; // Simulasi null
      await wrapper.vm.$nextTick();

      // Seharusnya tidak throw error
      expect(() => vm.handleDownload()).not.toThrow();
      expect(downloadPdfMock).not.toHaveBeenCalled();
    });
  });

  // ============================================================
  // DISBURSEMENT & LOCATION
  // ============================================================
  describe('Penyaluran & Lokasi', () => {
    
    it('[DISP-01] Format village_cash menjadi "Tunai (Balai Desa)"', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ disbursement_method: 'village_cash' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Tunai (Balai Desa)');
    });

    it('[DISP-02] Format bpd_transfer menjadi "Transfer Bank (BPD)"', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ disbursement_method: 'bpd_transfer' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Transfer Bank (BPD)');
    });

    it('[DISP-03] Menampilkan nomor rekening jika ada', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({
        disbursement_method: 'bpd_transfer',
        bank_account_number: '123-456-7890'
      }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('123-456-7890');
    });

    it('[DISP-04] Tidak menampilkan nomor rekening jika kosong', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ bank_account_number: '' }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).not.toContain('Nomor Rekening');
    });

    it('[LOC-01] Menampilkan lokasi banua lengkap', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({
        village: { name: 'Desa Sungai Paring' },
        district: { name: 'Martapura' },
        regency: { name: 'Kabupaten Banjar' }
      }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Desa Sungai Paring');
      expect(wrapper.text()).toContain('Martapura');
      expect(wrapper.text()).toContain('Kabupaten Banjar');
    });

    it('[LOC-02] Fallback jika lokasi null', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({
        village: null,
        district: null,
        regency: null
      }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Memuat Desa...');
      expect(wrapper.text()).toContain('Memuat Kecamatan...');
      expect(wrapper.text()).toContain('Kabupaten');
    });
  });

  // ============================================================
  // DYNAMIC DATA & EVIDENCES
  // ============================================================
  describe('Submission Data & Evidences', () => {
    
    it('[DATA-01] Menampilkan submission_data dinamis', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({
        submission_data: {
          school_name: 'SMA Negeri 1',
          nisn: '1234567890',
          _method: 'hidden_value'
        }
      }));
      
      const wrapper = mountComponent();
      await flushPromises();

      // Data yang boleh ditampilkan
      expect(wrapper.text()).toContain('SMA Negeri 1');
      expect(wrapper.text()).toContain('1234567890');
      
      // Hidden key tidak ditampilkan
      expect(wrapper.text()).not.toContain('hidden_value');
    });

    it('[DATA-02] Tidak crash jika submission_data null', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ submission_data: null }));
      
      const wrapper = mountComponent();
      await flushPromises();

      // Komponen tetap render tanpa error
      expect(wrapper.find('.max-w-4xl').exists()).toBe(true);
    });

    it('[DATA-03] Tidak crash jika submission_data kosong', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ submission_data: {} }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.find('.max-w-4xl').exists()).toBe(true);
    });

    it('[EV-01] Menampilkan evidence images', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({
        evidences: [
          { id: '1', image_type: 'ktp', image_url: 'https://example.com/ktp.jpg' },
          { id: '2', image_type: 'kk', image_url: 'https://example.com/kk.jpg' }
        ]
      }));
      
      const wrapper = mountComponent();
      await flushPromises();

      const images = wrapper.findAll('img');
      expect(images.length).toBe(2);
      expect(images[0].attributes('src')).toBe('https://example.com/ktp.jpg');
      expect(images[1].attributes('src')).toBe('https://example.com/kk.jpg');
    });

    it('[EV-02] Tidak crash jika evidences kosong', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ evidences: [] }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.find('.max-w-4xl').exists()).toBe(true);
    });

    it('[EV-03] Tidak crash jika evidences null', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({ evidences: null }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.find('.max-w-4xl').exists()).toBe(true);
    });
  });

  // ============================================================
  // NAVIGATION
  // ============================================================
  describe('Navigasi', () => {
    
    it('[NAV-01] Tombol kembali memanggil router.back()', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission());
      
      const wrapper = mountComponent();
      await flushPromises();

      const buttons = wrapper.findAll('button');
      const btnBack = buttons.find(b => b.text().toLowerCase().includes('kembali ke riwayat'));
      
      expect(btnBack).toBeDefined();
      await btnBack!.trigger('click');
      
      expect(backMock).toHaveBeenCalled();
    });
  });

  // ============================================================
  // PROGRAM NAME FALLBACK
  // ============================================================
  describe('Program Name', () => {
    
    it('[PROG-01] Menampilkan program.name', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({
        program: { name: 'Bantuan Pendidikan', title: 'Judul Program' }
      }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Bantuan Pendidikan');
    });

    it('[PROG-02] Fallback ke program.title jika name tidak ada', async () => {
      fetchDetailMock.mockResolvedValue(createMockSubmission({
        program: { title: 'Judul Program' }
      }));
      
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Judul Program');
    });
  });
});