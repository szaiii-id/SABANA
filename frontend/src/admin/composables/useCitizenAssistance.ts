import { ref } from 'vue';
import { CitizenAssistanceService } from '../services/CitizenAssistanceService';
import type { CitizenSearchResult, CitizenAssistancePayload } from '../types/citizen-assistance';
import type { AssistanceProgramSchema } from '../../types/assistance';

interface ApiError {
  response?: {
    data?: {
      message?: string;
    };
  };
}

function isApiError(error: unknown): error is ApiError {
  return typeof error === 'object' && error !== null && 'response' in error;
}

export function useCitizenAssistance() {
  const searchResults = ref<CitizenSearchResult[]>([]);
  const selectedCitizen = ref<CitizenSearchResult | null>(null);
  const programs = ref<AssistanceProgramSchema[]>([]);
  const selectedProgram = ref<AssistanceProgramSchema | null>(null);
  const isSubmitting = ref(false);
  const errorMessage = ref('');
  const successMessage = ref('');
  const isSearching = ref(false);
  const hasMoreResults = ref(false);
  const isLoadingMore = ref(false);
  const currentSearchPage = ref(1);
  const currentSearchQuery = ref('');

  let searchTimeout: ReturnType<typeof setTimeout> | null = null;

  const searchCitizen = (query: string) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    
    // ✅ Ubah minimal 1 karakter
    if (!query || query.length < 1) {
      searchResults.value = [];
      hasMoreResults.value = false;
      currentSearchPage.value = 1;
      currentSearchQuery.value = '';
      return;
    }
    
    currentSearchQuery.value = query;
    isSearching.value = true;
    currentSearchPage.value = 1;

    searchTimeout = setTimeout(async () => {
      try {
        const result = await CitizenAssistanceService.searchCitizenPaginated(query, 1);
        searchResults.value = result.data || [];
        hasMoreResults.value = result.has_more ?? false;
      } catch (err: unknown) {
        console.error('Search failed', err);
        searchResults.value = [];
        hasMoreResults.value = false;
      } finally {
        isSearching.value = false;
      }
    }, 500);
  };

  const loadMoreCitizens = async () => {
    if (!hasMoreResults.value || isLoadingMore.value || !currentSearchQuery.value) return;

    isLoadingMore.value = true;
    const nextPage = currentSearchPage.value + 1;

    try {
      const result = await CitizenAssistanceService.searchCitizenPaginated(currentSearchQuery.value, nextPage);
      searchResults.value = [...searchResults.value, ...(result.data || [])];
      hasMoreResults.value = result.has_more ?? false;
      currentSearchPage.value = nextPage;
    } catch (err: unknown) {
      console.error('Load more failed', err);
    } finally {
      isLoadingMore.value = false;
    }
  };

  const selectCitizen = (citizen: CitizenSearchResult) => {
    selectedCitizen.value = citizen;
    searchResults.value = [];
    hasMoreResults.value = false;
    currentSearchPage.value = 1;
    currentSearchQuery.value = '';
    fetchPrograms();
  };

  const fetchPrograms = async () => {
    try {
      programs.value = await CitizenAssistanceService.getPrograms(
        selectedCitizen.value?.id
      );
    } catch (err: unknown) {
      console.error('Failed to fetch programs', err);
    }
  };

  const selectProgram = (program: AssistanceProgramSchema) => {
    selectedProgram.value = program;
  };

  const submitAssistance = async (payload: CitizenAssistancePayload) => {
    isSubmitting.value = true;
    errorMessage.value = '';
    try {
      const response = await CitizenAssistanceService.submitAssistance(payload);
      successMessage.value = response.message;
      return response;
    } catch (err: unknown) {
      if (isApiError(err)) {
        errorMessage.value = err.response?.data?.message || 'Gagal mengajukan bantuan.';
      } else {
        errorMessage.value = 'Gagal mengajukan bantuan.';
      }
      return null;
    } finally {
      isSubmitting.value = false;
    }
  };

  const resetForm = () => {
    if (searchTimeout) {
      clearTimeout(searchTimeout);
      searchTimeout = null;
    }
    selectedCitizen.value = null;
    selectedProgram.value = null;
    searchResults.value = [];
    successMessage.value = '';
    errorMessage.value = '';
    isSearching.value = false;
    hasMoreResults.value = false;
    currentSearchPage.value = 1;
    currentSearchQuery.value = '';
  };

  return {
    searchResults,
    selectedCitizen,
    programs,
    selectedProgram,
    isSubmitting,
    errorMessage,
    successMessage,
    isSearching,
    hasMoreResults,
    isLoadingMore,
    searchCitizen,
    loadMoreCitizens,
    selectCitizen,
    fetchPrograms,
    selectProgram,
    submitAssistance,
    resetForm,
  };
}