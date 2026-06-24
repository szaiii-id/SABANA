import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import EvidenceGallery from '../EvidenceGallery.vue';

describe('EvidenceGallery.vue', () => {
  const mockEvidences = [
    {
      id: 'ev1',
      image_type: 'ktp',
      image_url: 'https://example.com/ktp.jpg',
      ai_result: {
        success: true,
        matches: [
          { key: 'nik', label: 'NIK', match_status: 'cocok' },
          { key: 'nama', label: 'Nama', match_status: 'tidak_cocok' },
        ],
        summary: { cocok: 1, total: 2 },
      },
    },
    {
      id: 'ev2',
      image_type: 'kk',
      image_url: 'https://example.com/kk.jpg',
      ai_result: {
        success: true,
        matches: [
          { key: 'nik', label: 'NIK', match_status: 'cocok' },
        ],
        summary: { cocok: 1, total: 1 },
      },
    },
    {
      id: 'ev3',
      image_type: 'foto_rumah',
      image_url: 'https://example.com/rumah.jpg',
      ai_result: null,
    },
  ];

  function mountComponent(props: any = {}) {
    return mount(EvidenceGallery, {
      props: {
        evidences: mockEvidences,
        zoomedDocuments: {},
        ...props,
      },
    });
  }

  // =============================================
  // RENDERING
  // =============================================

  it('test_menampilkan_heading_dokumen_bukti', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Dokumen Bukti');
  });

  it('test_menampilkan_semua_evidence', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Diverifikasi: 0/3');
  });

  // =============================================
  // AI BADGE
  // =============================================

  it('test_menampilkan_badge_cocok_untuk_semua_cocok', () => {
    const wrapper = mountComponent();
    // Evidence ke-2: summary { cocok: 1, total: 1 } → "Cocok"
    expect(wrapper.text()).toContain('Cocok');
  });

  it('test_menampilkan_badge_parsial_untuk_sebagian_cocok', () => {
    const wrapper = mountComponent();
    // Evidence ke-1: summary { cocok: 1, total: 2 } → "1 Cocok"
    expect(wrapper.text()).toContain('1 Cocok');
  });

    it('test_tidak_menampilkan_badge_untuk_ai_null', () => {
        const wrapper = mountComponent();
        // Evidence ke-3 ai_result null → tidak ada badge AI
        const badges = wrapper.findAll('span');
        const hasDiproses = Array.from(badges).some(b => b.text().includes('Diproses'));
        expect(hasDiproses).toBe(false);
    });

  // =============================================
  // AI VALIDASI — HANYA YANG COCOK
  // =============================================

  it('test_menampilkan_ai_validasi_untuk_match_cocok', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('AI Validasi');
    expect(wrapper.text()).toContain('NIK');
  });

  // =============================================
  // ZOOM
  // =============================================

  it('test_emit_zoom_saat_klik_gambar', async () => {
    const wrapper = mountComponent();
    const images = wrapper.findAll('img');
    await images[0].trigger('click');
    expect(wrapper.emitted('zoom')).toBeTruthy();
    expect(wrapper.emitted('zoom')![0]).toEqual(['https://example.com/ktp.jpg', 'ktp']);
  });

  it('test_menampilkan_centang_hijau_setelah_zoom', () => {
    const wrapper = mountComponent({ zoomedDocuments: { ktp: true } });
    // Ada SVG centang
    expect(wrapper.html()).toContain('M5 13l4 4L19 7');
  });

  // =============================================
  // EMPTY
  // =============================================

  it('test_tidak_merender_jika_evidences_kosong', () => {
    const wrapper = mount(EvidenceGallery, {
      props: { evidences: [], zoomedDocuments: {} },
    });
    expect(wrapper.text()).toBe('');
  });
});