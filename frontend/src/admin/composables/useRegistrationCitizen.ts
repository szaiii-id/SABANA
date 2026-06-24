import { ref } from 'vue';
import { RegistrationCitizenService } from '../services/RegistrationCitizenService';
import { registrationCitizenApi } from '../api/registrationCitizenApi';
import type { RegisteredCitizen, RegisterCitizenPayload, CitizenListFilters } from '../types/registration-citizen';

// ===== TYPES =====
interface ApiError {
  response?: {
    data?: {
      message?: string;
    };
  };
}

interface RegisterResult {
  citizen?: RegisteredCitizen;
  access_pin?: string;
}

// ===== COMPOSABLE =====
export function useRegistrationCitizen() {
  const citizens = ref<RegisteredCitizen[]>([]);
  const loading = ref(false);
  const isSubmitting = ref(false);
  const errorMessage = ref('');
  const successMessage = ref('');
  const currentPage = ref(1);
  const totalPages = ref(1);
  const total = ref(0);
  const showSuccess = ref(false);

  let requestId = 0;

  // ===== FETCH LIST (PostgreSQL - untuk halaman tanpa search) =====

  const fetchCitizens = async (filters: CitizenListFilters = {}): Promise<void> => {
    const currentRequestId = ++requestId;
    
    loading.value = true;
    
    try {
      const response = await RegistrationCitizenService.getList(filters);
      
      if (currentRequestId === requestId) {
        // Response getList: { data: [...], current_page, total, last_page, per_page }
        citizens.value = response.data || [];
        currentPage.value = response.current_page || 1;
        totalPages.value = response.last_page || 1;
        total.value = response.total || 0;
        errorMessage.value = '';
      }
    } catch (e: unknown) {
      if (currentRequestId === requestId) {
        const apiError = e as ApiError;
        errorMessage.value = apiError.response?.data?.message || 'Gagal memuat data warga.';
        citizens.value = [];
        total.value = 0;
      }
    } finally {
      if (currentRequestId === requestId) {
        loading.value = false;
      }
    }
  };

  // ===== SEARCH (Elasticsearch - untuk search bar) =====

  const searchCitizens = async (query: string, page: number = 1, perPage: number = 15): Promise<void> => {
    const currentRequestId = ++requestId;
    
    loading.value = true;
    
    try {
      const response = await registrationCitizenApi.searchPaginated({
        q: query,
        page,
        per_page: perPage,
      });
      
      if (currentRequestId === requestId) {
        // Response searchPaginated: { status: "success", data: { data: [...], current_page, total, has_more } }
        const responseData = response.data;
        
        // Ambil dari responseData.data (karena ada wrapper status)
        const result = responseData?.data || responseData;
        
        // Pastikan citizens selalu array
        if (result && Array.isArray(result.data)) {
          citizens.value = result.data;
          currentPage.value = result.current_page || page;
          total.value = result.total || 0;
          totalPages.value = Math.ceil((result.total || 0) / perPage);
        } else if (result && Array.isArray(result)) {
          // Fallback jika response langsung array
          citizens.value = result;
          total.value = result.length;
          totalPages.value = 1;
          currentPage.value = 1;
        } else {
          // Format tidak dikenal
          console.warn('Unknown search response format:', responseData);
          citizens.value = [];
          total.value = 0;
          totalPages.value = 1;
        }
        
        errorMessage.value = '';
      }
    } catch (e: unknown) {
      if (currentRequestId === requestId) {
        console.warn('Search failed, using fallback:', e);
        // Fallback ke getList jika search gagal
        await fetchCitizens({ search: query, page, per_page: perPage });
      }
    } finally {
      if (currentRequestId === requestId) {
        loading.value = false;
      }
    }
  };

  // ===== REGISTER =====

  const registerCitizen = async (payload: RegisterCitizenPayload): Promise<RegisterResult | null> => {
    isSubmitting.value = true;
    errorMessage.value = '';
    
    try {
      const response = await RegistrationCitizenService.create(payload);
      successMessage.value = response.message;
      showSuccess.value = true;
      return response.data;
    } catch (e: unknown) {
      const apiError = e as ApiError;
      errorMessage.value = apiError.response?.data?.message || 'Gagal mendaftarkan warga.';
      return null;
    } finally {
      isSubmitting.value = false;
    }
  };

  // ===== RESEND PIN =====

  const resendPin = async (id: string): Promise<{ access_pin?: string } | null> => {
    isSubmitting.value = true;
    errorMessage.value = '';
    
    try {
      const response = await RegistrationCitizenService.resendPin(id);
      successMessage.value = response.message;
      showSuccess.value = true;
      return response.data;
    } catch (e: unknown) {
      const apiError = e as ApiError;
      errorMessage.value = apiError.response?.data?.message || 'Gagal mengirim ulang PIN.';
      return null;
    } finally {
      isSubmitting.value = false;
    }
  };

  // ===== RESET STATE =====

  const resetState = (): void => {
    citizens.value = [];
    loading.value = false;
    isSubmitting.value = false;
    errorMessage.value = '';
    successMessage.value = '';
    currentPage.value = 1;
    totalPages.value = 1;
    total.value = 0;
    showSuccess.value = false;
    requestId = 0;
  };

  return {
    citizens,
    loading,
    isSubmitting,
    errorMessage,
    successMessage,
    currentPage,
    totalPages,
    total,
    showSuccess,
    fetchCitizens,
    searchCitizens,
    registerCitizen,
    resendPin,
    resetState,
  };
}