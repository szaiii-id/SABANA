import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { mount } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import EvaluationIndex from '../Index.vue';
import { useEvaluation } from '../../../composables/useEvaluation';

vi.mock('../../../composables/useEvaluation');

// ===== MOCK LOCALSTORAGE =====
const localStorageMock = {
  getItem: vi.fn().mockReturnValue(JSON.stringify({ role: 'super_admin' })),
  setItem: vi.fn(),
  removeItem: vi.fn(),
};
Object.defineProperty(globalThis, 'localStorage', { value: localStorageMock });

// ===== HELPERS =====
function validEvaluations() {
  return [
    {
      id: 'eval-1',
      status: 'updated',
      status_label: 'Menunggu Verifikasi',
      decision_notes: null,
      triggered_at: '2026-06-01',
      decided_at: null,
      submission: { id: 'sub-1', registration_number: 'SBN-OLD', status: 'evaluation_pending' },
      new_submission: { id: 'sub-2', registration_number: 'SBN-NEW', status: 'validated', smart_score: 75, recommendation: { label: 'Direkomendasikan', color: 'green' } },
      citizen: { id: 'cit-1', full_name: 'Test', nik: '123' },
      program: { id: 'prog-1', name: 'BLT' },
      old_data: null,
      location: { village: 'Desa', district: 'Kec', regency: 'Kab' },
    },
  ];
}

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', component: {} as any }],
});

function mockUseEvaluation(overrides: Record<string, unknown> = {}) {
  vi.mocked(useEvaluation).mockReturnValue({
    evaluations: ref([]),
    loading: ref(false),
    error: ref(null),
    successMessage: ref(null),
    fetchEvaluations: vi.fn(),
    approveEvaluation: vi.fn().mockResolvedValue(true),
    revokeEvaluation: vi.fn().mockResolvedValue(true),
    reset: vi.fn(),
    ...overrides,
  } as any);
}

describe('EvaluationIndex.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===== HAPPY PATH =====
  it('menampilkan daftar evaluasi', async () => {
    mockUseEvaluation({ evaluations: ref(validEvaluations()) });

    const wrapper = mount(EvaluationIndex, {
      global: {
        plugins: [router],
        stubs: { EvaluationHeader: true, ApproveEvaluationModal: true, RevokeEvaluationModal: true },
      },
    });

    await wrapper.vm.$nextTick();
    await new Promise(r => setTimeout(r, 100));

    expect(wrapper.findComponent({ name: 'EvaluationCard' }).exists()).toBe(true);
  });

  // ===== SAD PATH — EMPTY =====
  it('menampilkan pesan kosong ketika tidak ada evaluasi', async () => {
    mockUseEvaluation();

    const wrapper = mount(EvaluationIndex, {
      global: {
        plugins: [router],
        stubs: { EvaluationHeader: true, ApproveEvaluationModal: true, RevokeEvaluationModal: true },
      },
    });

    await wrapper.vm.$nextTick();
    await new Promise(r => setTimeout(r, 100));

    expect(wrapper.text()).toContain('Belum Ada Evaluasi');
  });

  // ===== LOADING =====
  it('menampilkan skeleton saat loading', () => {
    mockUseEvaluation({ loading: ref(true) });

    const wrapper = mount(EvaluationIndex, {
      global: {
        plugins: [router],
        stubs: { EvaluationHeader: true, ApproveEvaluationModal: true, RevokeEvaluationModal: true },
      },
    });

    expect(wrapper.findAll('.animate-pulse').length).toBeGreaterThan(0);
  });

  // ===== ERROR =====
  it('menampilkan pesan error', () => {
    mockUseEvaluation({ error: ref('Gagal memuat data evaluasi.') });

    const wrapper = mount(EvaluationIndex, {
      global: {
        plugins: [router],
        stubs: { EvaluationHeader: true, ApproveEvaluationModal: true, RevokeEvaluationModal: true },
      },
    });

    expect(wrapper.text()).toContain('Gagal memuat data evaluasi.');
  });

  // ===== SUCCESS MESSAGE =====
  it('menampilkan pesan sukses', () => {
    mockUseEvaluation({
      evaluations: ref(validEvaluations()),
      successMessage: ref('Evaluasi disetujui.'),
    });

    const wrapper = mount(EvaluationIndex, {
      global: {
        plugins: [router],
        stubs: { EvaluationHeader: true, ApproveEvaluationModal: true, RevokeEvaluationModal: true },
      },
    });

    expect(wrapper.text()).toContain('Evaluasi disetujui.');
  });

  // ===== MOUNTED =====
  it('memanggil fetchEvaluations saat mounted', () => {
    const fetchMock = vi.fn();
    mockUseEvaluation({ fetchEvaluations: fetchMock });

    mount(EvaluationIndex, {
      global: {
        plugins: [router],
        stubs: { EvaluationHeader: true, ApproveEvaluationModal: true, RevokeEvaluationModal: true },
      },
    });

    expect(fetchMock).toHaveBeenCalled();
  });

  // ===== NULL / EMPTY — error null =====
  it('tidak menampilkan error saat null', () => {
    mockUseEvaluation({ evaluations: ref(validEvaluations()), error: ref(null) });

    const wrapper = mount(EvaluationIndex, {
      global: {
        plugins: [router],
        stubs: { EvaluationHeader: true, ApproveEvaluationModal: true, RevokeEvaluationModal: true },
      },
    });

    expect(wrapper.text()).not.toContain('Gagal');
  });
});