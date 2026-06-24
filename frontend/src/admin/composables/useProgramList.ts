import { ref, onMounted, onUnmounted } from 'vue';
import ProgramService from '../services/ProgramService';
import type { ProgramData } from '../types/program';

export function useProgramList() {
  const programs = ref<ProgramData[]>([]);
  const loading = ref(false);
  const filters = ref({ search: '', status: '' });
  const currentPage = ref(1);
  const totalPages = ref(1);
  let searchTimeout: ReturnType<typeof setTimeout> | null = null;

  const fetchData = async () => {
    loading.value = true;
    try {
      const r = await ProgramService.fetchPrograms({ ...filters.value, page: currentPage.value, per_page: 12 });
      programs.value = r.data || [];
      currentPage.value = r.current_page || 1;
      totalPages.value = r.last_page || 1;
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
  };

  const debounceSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    currentPage.value = 1;
    searchTimeout = setTimeout(fetchData, 500);
  };

  const changePage = (page: number) => { currentPage.value = page; fetchData(); };

  onMounted(() => { fetchData(); });
  onUnmounted(() => { if (searchTimeout) clearTimeout(searchTimeout); });

  return { programs, loading, filters, currentPage, totalPages, fetchData, debounceSearch, changePage };
}