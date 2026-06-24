import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import Index from '../Index.vue';
import type { CitizenSearchResult } from '../../../types/citizen-assistance';
import type { AssistanceProgramSchema } from '../../../../types/assistance';
import type { Region } from '../../../composables/useRegion';

const mockSearchCitizen = vi.fn();
const mockSelectCitizen = vi.fn();
const mockFetchPrograms = vi.fn();
const mockSubmitAssistance = vi.fn();
const mockResetForm = vi.fn();
const mockFetchRegencies = vi.fn();
const mockFetchDistricts = vi.fn();
const mockFetchVillages = vi.fn();

let composableState = {
  searchResults: [] as CitizenSearchResult[],
  selectedCitizen: null as CitizenSearchResult | null,
  programs: [] as AssistanceProgramSchema[],
  selectedProgram: null as AssistanceProgramSchema | null,
  isSubmitting: false,
  errorMessage: '',
  successMessage: '',
  isSearching: false,
  hasMoreResults: false,
  isLoadingMore: false,
};

let regionState = {
  regencies: [] as Region[],
  districts: [] as Region[],
  villages: [] as Region[],
};

vi.mock('../../../composables/useCitizenAssistance', () => ({
  useCitizenAssistance: () => ({
    searchResults: composableState.searchResults,
    selectedCitizen: composableState.selectedCitizen,
    programs: composableState.programs,
    selectedProgram: composableState.selectedProgram,
    isSubmitting: composableState.isSubmitting,
    errorMessage: composableState.errorMessage,
    successMessage: composableState.successMessage,
    isSearching: composableState.isSearching,
    hasMoreResults: composableState.hasMoreResults,
    isLoadingMore: composableState.isLoadingMore,
    searchCitizen: mockSearchCitizen,
    loadMoreCitizens: vi.fn(),
    selectCitizen: mockSelectCitizen,
    fetchPrograms: mockFetchPrograms,
    selectProgram: vi.fn(),
    submitAssistance: mockSubmitAssistance,
    resetForm: mockResetForm,
  }),
}));

vi.mock('../../../composables/useRegion', () => ({
  useRegion: () => ({
    regencies: regionState.regencies,
    districts: regionState.districts,
    villages: regionState.villages,
    fetchRegencies: mockFetchRegencies,
    fetchDistricts: mockFetchDistricts,
    fetchVillages: mockFetchVillages,
  }),
}));

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', component: {} as Record<string, never> }],
});

function mountComponent() {
  return mount(Index, {
    global: {
      plugins: [router],
      stubs: {
        CitizenSearchSection: {
          template: '<div>SearchSection</div>',
          name: 'CitizenSearchSection',
        },
        ProgramSelectSection: {
          template: '<div>ProgramSection</div>',
          name: 'ProgramSelectSection',
        },
        SubmissionFormSection: {
          template: '<div>FormSection</div>',
          name: 'SubmissionFormSection',
        },
      },
    },
  });
}

describe('Index.vue', () => {

  beforeEach(() => {
    vi.clearAllMocks();
    composableState = {
      searchResults: [],
      selectedCitizen: null,
      programs: [],
      selectedProgram: null,
      isSubmitting: false,
      errorMessage: '',
      successMessage: '',
      isSearching: false,
      hasMoreResults: false,
      isLoadingMore: false,
    };
    regionState = { regencies: [], districts: [], villages: [] };
  });

  // ===== HAPPY PATH =====

  it('test_merender_judul_halaman', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Ajukan Bantuan');
  });

  it('test_memanggil_fetchPrograms_saat_mount', () => {
    mountComponent();
    expect(mockFetchPrograms).toHaveBeenCalled();
  });

  // ===== SEARCH =====

  it('test_handleSearch_memanggil_searchCitizen', async () => {
    const wrapper = mountComponent();
    await flushPromises();

    const searchSection = wrapper.findComponent({ name: 'CitizenSearchSection' });
    expect(searchSection.exists()).toBe(true);
    await searchSection.vm.$emit('search', 'Joko');

    expect(mockSearchCitizen).toHaveBeenCalledWith('Joko');
  });

  // ===== SELECT CITIZEN =====

  it('test_handleSelectCitizen_memanggil_selectCitizen', async () => {
    const wrapper = mountComponent();
    await flushPromises();

    const searchSection = wrapper.findComponent({ name: 'CitizenSearchSection' });
    expect(searchSection.exists()).toBe(true);
    await searchSection.vm.$emit('select', { id: '1', full_name: 'Joko', nik: '6301' });

    expect(mockSelectCitizen).toHaveBeenCalled();
  });

  // ===== REMOVE CITIZEN =====

  it('test_handleRemoveCitizen_memanggil_resetForm', async () => {
    const wrapper = mountComponent();
    await flushPromises();

    const searchSection = wrapper.findComponent({ name: 'CitizenSearchSection' });
    expect(searchSection.exists()).toBe(true);
    await searchSection.vm.$emit('remove');

    expect(mockResetForm).toHaveBeenCalled();
  });
});