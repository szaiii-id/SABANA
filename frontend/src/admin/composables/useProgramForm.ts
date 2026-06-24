import { ref, watch } from 'vue';
import type { ProgramData, ProgramPayload } from '../types/program';

const DRAFT_KEY = 'program_draft';
const EDIT_DRAFT_PREFIX = 'program_edit_';
const MAX_BANNER_SIZE = 2 * 1024 * 1024;

export function useProgramForm() {
  const formData = ref<ProgramPayload>({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    status: 'draft',
    quota_total: null,
    benefit_amount: null,
    criteria: {},
    ai_config: {},
    banner: null,
    banner_url: null,
  });

  const validationErrors = ref<Record<string, string>>({});
  const modalMode = ref<'add' | 'edit'>('add');
  const editId = ref('');
  const isModalOpen = ref(false);

  const defaultForm = (): ProgramPayload => ({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    status: 'draft',
    quota_total: null,
    benefit_amount: null,
    criteria: {},
    ai_config: {},
    banner: null,
    banner_url: null,
  });

  const saveDraft = (): void => {
    const key = modalMode.value === 'add' ? DRAFT_KEY : EDIT_DRAFT_PREFIX + editId.value;
    const { banner, ...rest } = formData.value;
    localStorage.setItem(key, JSON.stringify(rest));
  };

  const loadDraft = (): ProgramPayload | null => {
    const key = modalMode.value === 'add' ? DRAFT_KEY : EDIT_DRAFT_PREFIX + editId.value;
    const draft = localStorage.getItem(key);
    if (draft) {
      try {
        const parsed = JSON.parse(draft) as ProgramPayload;
        return { ...parsed, banner: null };
      } catch {
        return null;
      }
    }
    return null;
  };

  const clearDraft = (): void => {
    const key = modalMode.value === 'add' ? DRAFT_KEY : EDIT_DRAFT_PREFIX + editId.value;
    localStorage.removeItem(key);
  };

  const validateForm = (): boolean => {
    validationErrors.value = {};
    let isValid = true;

    if (!formData.value.name.trim() || formData.value.name.trim().length < 3) {
      validationErrors.value.name = 'Nama program minimal 3 karakter.';
      isValid = false;
    }

    if (!formData.value.description.trim() || formData.value.description.trim().length < 10) {
      validationErrors.value.description = 'Deskripsi minimal 10 karakter.';
      isValid = false;
    }

    if (!formData.value.start_date) {
      validationErrors.value.start_date = 'Tanggal mulai wajib diisi.';
      isValid = false;
    }

    if (!formData.value.end_date) {
      validationErrors.value.end_date = 'Tanggal selesai wajib diisi.';
      isValid = false;
    }

    if (
      formData.value.start_date &&
      formData.value.end_date &&
      formData.value.end_date <= formData.value.start_date
    ) {
      validationErrors.value.end_date = 'Tanggal selesai harus setelah tanggal mulai.';
      isValid = false;
    }

    if (
      formData.value.quota_total === null ||
      formData.value.quota_total === undefined ||
      formData.value.quota_total < 1
    ) {
      validationErrors.value.quota_total = 'Kuota total wajib diisi, minimal 1.';
      isValid = false;
    }

    if (
      formData.value.benefit_amount === null ||
      formData.value.benefit_amount === undefined ||
      formData.value.benefit_amount < 0
    ) {
      validationErrors.value.benefit_amount = 'Nilai bantuan wajib diisi.';
      isValid = false;
    }

    if (formData.value.banner instanceof File && formData.value.banner.size > MAX_BANNER_SIZE) {
      validationErrors.value.banner = 'Ukuran banner maksimal 2MB.';
      isValid = false;
    }

    return isValid;
  };

  const openAddModal = (): void => {
    modalMode.value = 'add';
    formData.value = defaultForm();
    validationErrors.value = {};
    isModalOpen.value = true;
  };

  const openEditModal = (program: ProgramData): void => {
    modalMode.value = 'edit';
    editId.value = program.id;
    const draft = loadDraft();
    formData.value = draft
      ? { ...draft, banner: null }
      : {
          name: program.name,
          description: program.description,
          start_date: program.start_date || '',
          end_date: program.end_date || '',
          status:
            program.status === 'active' || program.status === 'draft'
              ? (program.status as 'draft' | 'active')
              : 'draft',
          quota_total: program.quota_total,
          benefit_amount: program.benefit_amount,
          criteria: program.criteria || {},
          ai_config: (program as unknown as Record<string, unknown>).ai_config as Record<string, unknown> || {},
          banner: null,
          banner_url: program.banner_url || null,
        };
    validationErrors.value = {};
    isModalOpen.value = true;
  };

  const closeModal = (): void => {
    isModalOpen.value = false;
  };

  watch(
    formData,
    () => {
      if (isModalOpen.value) saveDraft();
    },
    { deep: true },
  );

  return {
    formData,
    validationErrors,
    modalMode,
    editId,
    isModalOpen,
    validateForm,
    openAddModal,
    openEditModal,
    closeModal,
    clearDraft,
    handleCriteriaUpdate: (criteria: Record<string, unknown>, aiConfig?: Record<string, unknown>): void => {
      formData.value.criteria = criteria;
      if (aiConfig) formData.value.ai_config = aiConfig;
    },
    handleBannerChange: (file: File | null): void => {
      if (file && file.size > MAX_BANNER_SIZE) {
        validationErrors.value.banner = 'Ukuran banner maksimal 2MB.';
        formData.value.banner = null;
        return;
      }
      validationErrors.value.banner = '';
      formData.value.banner = file;
    },
  };
}