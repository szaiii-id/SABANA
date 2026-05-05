// src/pages/dashboard/__tests__/Report.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import Report from '../Report.vue';
import { reportApi } from '../../../api/reportApi';

describe('Report.vue - Professional QA Test Suite', () => {

  let sendWhatsappSpy: any;
  let sendEmailSpy: any;

  beforeEach(() => {
    vi.clearAllMocks();
    
    sendWhatsappSpy = vi.spyOn(reportApi, 'sendWhatsapp').mockResolvedValue({ message: 'OK' } as any);
    sendEmailSpy = vi.spyOn(reportApi, 'sendEmail').mockResolvedValue({ message: 'OK' } as any);
    
    vi.stubGlobal('localStorage', {
      getItem: vi.fn((key) => {
        if (key === 'user') {
          return JSON.stringify({ nik: '630123456789', full_name: 'AKHMAD WARGA' });
        }
        return null;
      }),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  const mountComponent = () => {
    return mount(Report);
  };

  // ============================================================
  // INISIALISASI
  // ============================================================
  describe('Inisialisasi', () => {

    it('[INIT-01] Load citizen data dari localStorage', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.citizenData.nik).toBe('630123456789');
      expect(vm.citizenData.full_name).toBe('AKHMAD WARGA');
    });

    it('[INIT-02] Tampil dengan NIK dan nama dari localStorage', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('630123456789');
      expect(wrapper.text()).toContain('AKHMAD WARGA');
    });

    it('[INIT-03] Default tab adalah WhatsApp', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.activeTab).toBe('whatsapp');
    });

    it('[INIT-04] Tidak crash jika localStorage user null', async () => {
      (localStorage.getItem as ReturnType<typeof vi.fn>).mockReturnValue(null);

      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.citizenData.nik).toBe('');
      expect(vm.citizenData.full_name).toBe('');
    });
  });

  // ============================================================
  // SWITCH TAB
  // ============================================================
  describe('Switch Tab', () => {

    it('[TAB-01] Pindah ke tab Email', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const buttons = wrapper.findAll('button');
      const emailTabBtn = buttons.find(b => b.text().includes('Email'));
      await emailTabBtn!.trigger('click');
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.activeTab).toBe('email');
    });

    it('[TAB-02] Pindah tab mereset form', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      
      // Isi form di tab WhatsApp
      vm.form.pesan = 'Pesan panjang lebih dari 10 karakter';
      vm.errors.pesan = 'Error sebelumnya';
      vm.notification.message = 'Notif lama';

      // Pindah ke Email
      const buttons = wrapper.findAll('button');
      const emailTabBtn = buttons.find(b => b.text().includes('Email'));
      await emailTabBtn!.trigger('click');
      await flushPromises();

      expect(vm.form.pesan).toBe('');
      expect(vm.form.email).toBe('');
      expect(vm.form.subjek).toBe('');
      expect(vm.errors.pesan).toBe('');
      expect(vm.notification.message).toBe('');
    });

    it('[TAB-03] Kembali ke WhatsApp dari Email', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      // Ke Email dulu
      const buttons = wrapper.findAll('button');
      const emailTabBtn = buttons.find(b => b.text().includes('Email'));
      await emailTabBtn!.trigger('click');
      await flushPromises();

      // Kembali ke WhatsApp
      const waTabBtn = wrapper.findAll('button').find(b => b.text().includes('WhatsApp'));
      await waTabBtn!.trigger('click');
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.activeTab).toBe('whatsapp');
    });
  });

  // ============================================================
  // VALIDASI WHATSAPP
  // ============================================================
  describe('Validasi WhatsApp', () => {

    it('[VAL-WA-01] Error jika pesan kurang dari 10 karakter', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('textarea').setValue('Halo');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(wrapper.text()).toContain('Pesan minimal 10 karakter');
      expect(sendWhatsappSpy).not.toHaveBeenCalled();
    });

    it('[VAL-WA-02] Error jika pesan tepat 9 karakter', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('textarea').setValue('123456789');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(wrapper.text()).toContain('Pesan minimal 10 karakter');
    });

    it('[VAL-WA-03] Sukses jika pesan tepat 10 karakter', async () => {
      sendWhatsappSpy.mockResolvedValue({ message: 'Terkirim' });

      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('textarea').setValue('1234567890');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(sendWhatsappSpy).toHaveBeenCalledWith({
        pesan: '1234567890'
      });
    });
  });

  // ============================================================
  // VALIDASI EMAIL
  // ============================================================
  describe('Validasi Email', () => {

    beforeEach(async () => {
      // Tidak perlu setup khusus
    });

    it('[VAL-EM-01] Error jika email tidak mengandung @', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      // Pindah ke Email
      const buttons = wrapper.findAll('button');
      const emailTabBtn = buttons.find(b => b.text().includes('Email'));
      await emailTabBtn!.trigger('click');
      await flushPromises();

      await wrapper.find('input[type="email"]').setValue('invalid.email');
      await wrapper.find('input[type="text"]').setValue('Subjek yang cukup panjang');
      await wrapper.find('textarea').setValue('Pesan yang cukup panjang untuk validasi');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(wrapper.text()).toContain('Email tidak valid');
      expect(sendEmailSpy).not.toHaveBeenCalled();
    });

    it('[VAL-EM-02] Error jika subjek kurang dari 5 karakter', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const buttons = wrapper.findAll('button');
      const emailTabBtn = buttons.find(b => b.text().includes('Email'));
      await emailTabBtn!.trigger('click');
      await flushPromises();

      await wrapper.find('input[type="email"]').setValue('valid@email.com');
      await wrapper.find('input[type="text"]').setValue('Sub');
      await wrapper.find('textarea').setValue('Pesan yang cukup panjang untuk validasi');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(wrapper.text()).toContain('Subjek minimal 5 karakter');
      expect(sendEmailSpy).not.toHaveBeenCalled();
    });

    it('[VAL-EM-03] Error jika keduanya (email DAN subjek) tidak valid', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const buttons = wrapper.findAll('button');
      const emailTabBtn = buttons.find(b => b.text().includes('Email'));
      await emailTabBtn!.trigger('click');
      await flushPromises();

      await wrapper.find('input[type="email"]').setValue('invalid');
      await wrapper.find('input[type="text"]').setValue('Sub');
      await wrapper.find('textarea').setValue('Pesan yang cukup panjang untuk validasi');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(wrapper.text()).toContain('Email tidak valid');
      expect(wrapper.text()).toContain('Subjek minimal 5 karakter');
    });
  });

  // ============================================================
  // SUBMIT WHATSAPP
  // ============================================================
  describe('Submit WhatsApp', () => {

    it('[SUB-WA-01] Kirim WhatsApp sukses', async () => {
      sendWhatsappSpy.mockResolvedValue({ message: 'Laporan WA berhasil dikirim' });

      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('textarea').setValue('Jalan di desa kami rusak parah, mohon perbaikan.');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(sendWhatsappSpy).toHaveBeenCalledWith({
        pesan: 'Jalan di desa kami rusak parah, mohon perbaikan.'
      });
      expect(wrapper.text()).toContain('Laporan WA berhasil dikirim');
    });

    it('[SUB-WA-02] Reset textarea setelah kirim sukses', async () => {
      sendWhatsappSpy.mockResolvedValue({ message: 'OK' });

      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('textarea').setValue('Pesan panjang lebih dari 10 karakter');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      const vm = wrapper.vm as any;
      expect(vm.form.pesan).toBe('');
    });

    it('[SUB-WA-03] Error network menampilkan pesan default', async () => {
      sendWhatsappSpy.mockRejectedValue(new Error('Network Error'));

      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('textarea').setValue('Pesan panjang lebih dari 10 karakter');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Gagal mengirim laporan.');
    });

    it('[SUB-WA-04] Error 422 menampilkan error validasi server', async () => {
      sendWhatsappSpy.mockRejectedValue({
        response: {
          status: 422,
          data: {
            errors: {
              pesan: ['Pesan mengandung kata tidak pantas']
            }
          }
        }
      });

      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('textarea').setValue('Pesan panjang lebih dari 10 karakter');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Pesan mengandung kata tidak pantas');
    });

    it('[SUB-WA-05] Loading state saat submit', async () => {
      let resolvePromise: any;
      sendWhatsappSpy.mockReturnValue(new Promise(resolve => {
        resolvePromise = resolve;
      }));

      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('textarea').setValue('Pesan panjang lebih dari 10 karakter');
      
      const submitPromise = wrapper.find('form').trigger('submit.prevent');
      await wrapper.vm.$nextTick();

      const vm = wrapper.vm as any;
      expect(vm.isSubmitting).toBe(true);

      resolvePromise({ message: 'OK' });
      await submitPromise;
      await flushPromises();

      expect(vm.isSubmitting).toBe(false);
    });
  });

  // ============================================================
  // SUBMIT EMAIL
  // ============================================================
  describe('Submit Email', () => {

    it('[SUB-EM-01] Kirim Email sukses', async () => {
      sendEmailSpy.mockResolvedValue({ message: 'Laporan Email berhasil dikirim' });

      const wrapper = mountComponent();
      await flushPromises();

      // Pindah ke Email
      const buttons = wrapper.findAll('button');
      const emailTabBtn = buttons.find(b => b.text().includes('Email'));
      await emailTabBtn!.trigger('click');
      await flushPromises();

      await wrapper.find('input[type="email"]').setValue('warga@email.com');
      await wrapper.find('input[type="text"]').setValue('Jalan Rusak Parah');
      await wrapper.find('textarea').setValue('Mohon perbaikan jalan di desa kami yang sudah rusak.');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();

      expect(sendEmailSpy).toHaveBeenCalledWith({
        nama: 'AKHMAD WARGA',
        email: 'warga@email.com',
        subjek: 'Jalan Rusak Parah',
        pesan: 'Mohon perbaikan jalan di desa kami yang sudah rusak.'
      });
      expect(wrapper.text()).toContain('Laporan Email berhasil dikirim');
    });

    it('[SUB-EM-02] Error network Email menampilkan pesan default', async () => {
      sendEmailSpy.mockRejectedValue(new Error('Network Error'));

      const wrapper = mountComponent();
      await flushPromises();

      const buttons = wrapper.findAll('button');
      const emailTabBtn = buttons.find(b => b.text().includes('Email'));
      await emailTabBtn!.trigger('click');
      await flushPromises();

      await wrapper.find('input[type="email"]').setValue('warga@email.com');
      await wrapper.find('input[type="text"]').setValue('Subjek Valid');
      await wrapper.find('textarea').setValue('Pesan yang cukup panjang untuk validasi');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Gagal mengirim laporan.');
    });
  });

  // ============================================================
  // UI ELEMENTS
  // ============================================================
  describe('UI Elements', () => {

    it('[UI-01] Menampilkan "Layanan Aspirasi Warga"', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Layanan');
      expect(wrapper.text()).toContain('Aspirasi Warga');
    });

    it('[UI-02] Menampilkan info privasi', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Privasi Terjamin');
    });

    it('[UI-03] Tombol submit WhatsApp: "Kirim via WhatsApp"', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      expect(wrapper.text()).toContain('Kirim via WhatsApp');
    });

    it('[UI-04] Tombol submit Email: "Kirim via Email"', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const buttons = wrapper.findAll('button');
      const emailTabBtn = buttons.find(b => b.text().includes('Email'));
      await emailTabBtn!.trigger('click');
      await flushPromises();

      expect(wrapper.text()).toContain('Kirim via Email');
    });

    it('[UI-05] clearError hapus error dan notifikasi', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      const vm = wrapper.vm as any;
      vm.errors.pesan = 'Error test';
      vm.notification.message = 'Notif test';

      vm.clearError('pesan');

      expect(vm.errors.pesan).toBe('');
      expect(vm.notification.message).toBe('');
    });
  });

  // ============================================================
  // EDGE CASES
  // ============================================================
  describe('Edge Cases', () => {

    it('[EDGE-01] WhatsApp: Error 422 tanpa errors spesifik', async () => {
      sendWhatsappSpy.mockRejectedValue({
        response: {
          status: 422,
          data: { message: 'Error' }
        }
      });

      const wrapper = mountComponent();
      await flushPromises();

      await wrapper.find('textarea').setValue('Pesan panjang lebih dari 10 karakter');
      await wrapper.find('form').trigger('submit.prevent');
      await flushPromises();
      await wrapper.vm.$nextTick();
      await flushPromises(); // tambahan

      const vm = wrapper.vm as any;
      
      expect(vm.notification.type).toBe('error');
      expect(vm.notification.message).toBeTruthy();
    });

    it('[EDGE-02] Tab Email menampilkan input tambahan', async () => {
      const wrapper = mountComponent();
      await flushPromises();

      // WhatsApp: hanya textarea
      expect(wrapper.findAll('input').length).toBe(0);

      // Pindah ke Email
      const buttons = wrapper.findAll('button');
      const emailTabBtn = buttons.find(b => b.text().includes('Email'));
      await emailTabBtn!.trigger('click');
      await flushPromises();

      // Email: ada input email + subjek
      expect(wrapper.findAll('input[type="email"]').length).toBe(1);
      expect(wrapper.findAll('input[type="text"]').length).toBe(1);
    });
  });
});