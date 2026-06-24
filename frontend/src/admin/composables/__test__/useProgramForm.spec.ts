// =============================================
// useProgramForm.spec.ts — FINAL
// =============================================

import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useProgramForm } from '../useProgramForm';

// =============================================
// MOCK LOCAL STORAGE
// =============================================
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: vi.fn((key: string) => store[key] || null),
    setItem: vi.fn((key: string, value: string) => { store[key] = value; }),
    removeItem: vi.fn((key: string) => { delete store[key]; }),
    clear: vi.fn(() => { store = {}; }),
  };
})();

Object.defineProperty(window, 'localStorage', { value: localStorageMock });

// =============================================
// TEST SUITE
// =============================================
describe('useProgramForm', () => {
  beforeEach(() => {
    localStorageMock.clear();
    vi.clearAllMocks();
  });

  // ===== MODAL MODE =====

  it('test_open_add_modal_mengatur_mode_add', () => {
    const { modalMode, isModalOpen, openAddModal } = useProgramForm();
    openAddModal();
    expect(modalMode.value).toBe('add');
    expect(isModalOpen.value).toBe(true);
  });

  it('test_open_edit_modal_mengatur_mode_edit', () => {
    const { modalMode, editId, isModalOpen, openEditModal } = useProgramForm();
    openEditModal({
      id: 'test-uuid',
      name: 'Test Program',
      description: 'Description',
      start_date: '2026-01-01',
      end_date: '2026-12-31',
      status: 'active',
      quota_total: 100,
      benefit_amount: 500000,
      criteria: {},
      banner_url: null,
    });
    expect(modalMode.value).toBe('edit');
    expect(editId.value).toBe('test-uuid');
    expect(isModalOpen.value).toBe(true);
  });

  it('test_close_modal_menutup_modal', () => {
    const { isModalOpen, openAddModal, closeModal } = useProgramForm();
    openAddModal();
    expect(isModalOpen.value).toBe(true);
    closeModal();
    expect(isModalOpen.value).toBe(false);
  });

  // ===== FORM VALIDATION =====

  it('test_validate_form_name_kosong', () => {
    const { formData, validationErrors, validateForm } = useProgramForm();
    formData.value.name = '';
    expect(validateForm()).toBe(false);
    expect(validationErrors.value.name).toBeDefined();
  });

  it('test_validate_form_description_kosong', () => {
    const { formData, validationErrors, validateForm } = useProgramForm();
    formData.value.description = '';
    expect(validateForm()).toBe(false);
    expect(validationErrors.value.description).toBeDefined();
  });

  it('test_validate_form_start_date_kosong', () => {
    const { formData, validationErrors, validateForm } = useProgramForm();
    formData.value.start_date = '';
    expect(validateForm()).toBe(false);
    expect(validationErrors.value.start_date).toBeDefined();
  });

  it('test_validate_form_end_date_kosong', () => {
    const { formData, validationErrors, validateForm } = useProgramForm();
    formData.value.end_date = '';
    expect(validateForm()).toBe(false);
    expect(validationErrors.value.end_date).toBeDefined();
  });

  it('test_validate_form_end_date_sebelum_start_date', () => {
    const { formData, validationErrors, validateForm } = useProgramForm();
    formData.value.start_date = '2026-12-31';
    formData.value.end_date = '2026-01-01';
    expect(validateForm()).toBe(false);
    expect(validationErrors.value.end_date).toBeDefined();
  });

  it('test_validate_form_semua_valid', () => {
    const { formData, validateForm } = useProgramForm();
    formData.value.name = 'Program Valid';
    formData.value.description = 'Deskripsi lengkap';
    formData.value.start_date = '2026-01-01';
    formData.value.end_date = '2026-12-31';
    expect(validateForm()).toBe(true);
  });

  // ===== LOCAL STORAGE — SAVE DRAFT =====

  it('test_save_draft_add_mode_memanggil_setItem', async () => {
    const { formData, openAddModal } = useProgramForm();
    openAddModal();
    
    // Trigger watch deep dengan mengubah formData setelah modal terbuka
    formData.value.name = 'Test Draft';
    
    // Tunggu microtask untuk watch trigger
    await new Promise(resolve => setTimeout(resolve, 50));
    
    expect(localStorageMock.setItem).toHaveBeenCalled();
  });

  it('test_save_draft_edit_mode_memanggil_setItem', async () => {
    const { formData, openEditModal } = useProgramForm();
    openEditModal({
      id: 'edit-uuid',
      name: 'Test',
      description: 'Desc',
      start_date: '2026-01-01',
      end_date: '2026-12-31',
      status: 'active',
      quota_total: null,
      benefit_amount: null,
      criteria: {},
      banner_url: null,
    });
    
    formData.value.name = 'Edited Draft';
    await new Promise(resolve => setTimeout(resolve, 50));
    
    expect(localStorageMock.setItem).toHaveBeenCalled();
  });

  // ===== LOCAL STORAGE — CLEAR DRAFT =====

  it('test_clear_draft_add_mode', () => {
    const { openAddModal, clearDraft } = useProgramForm();
    openAddModal();
    clearDraft();
    expect(localStorageMock.removeItem).toHaveBeenCalledWith('program_draft');
  });

  it('test_clear_draft_edit_mode', () => {
    const { openEditModal, clearDraft } = useProgramForm();
    openEditModal({
      id: 'edit-uuid',
      name: 'Test',
      description: 'Desc',
      start_date: '2026-01-01',
      end_date: '2026-12-31',
      status: 'draft',
      quota_total: null,
      benefit_amount: null,
      criteria: {},
      banner_url: null,
    });
    clearDraft();
    expect(localStorageMock.removeItem).toHaveBeenCalledWith('program_edit_edit-uuid');
  });

  // ===== CRITERIA =====

  it('test_handle_criteria_update', () => {
    const { formData, handleCriteriaUpdate } = useProgramForm();
    const criteria = { targets: ['miskin_ekstrem'], documents: ['ktp'] };
    const aiConfig = { ktp: { ocr: true } };
    handleCriteriaUpdate(criteria, aiConfig);
    expect(formData.value.criteria).toEqual(criteria);
    expect(formData.value.ai_config).toEqual(aiConfig);
  });

  it('test_handle_criteria_update_tanpa_ai_config', () => {
    const { formData, handleCriteriaUpdate } = useProgramForm();
    const criteria = { targets: ['miskin'] };
    handleCriteriaUpdate(criteria);
    expect(formData.value.criteria).toEqual(criteria);
  });

  // ===== BANNER =====

  it('test_handle_banner_change', () => {
    const { formData, handleBannerChange } = useProgramForm();
    const file = new File([''], 'banner.jpg', { type: 'image/jpeg' });
    handleBannerChange(file);
    expect(formData.value.banner).toBe(file);
  });

  it('test_handle_banner_change_null', () => {
    const { formData, handleBannerChange } = useProgramForm();
    formData.value.banner = new File([''], 'banner.jpg');
    handleBannerChange(null);
    expect(formData.value.banner).toBeNull();
  });

  // ===== NULL / EMPTY =====

  it('test_form_data_default_values', () => {
    const { formData } = useProgramForm();
    expect(formData.value.name).toBe('');
    expect(formData.value.description).toBe('');
    expect(formData.value.status).toBe('draft');
    expect(formData.value.quota_total).toBeNull();
    expect(formData.value.benefit_amount).toBeNull();
  });

  // ===== STATE TRANSITION =====

  it('test_open_add_then_close_then_open_edit', () => {
    const { modalMode, editId, openAddModal, closeModal, openEditModal } = useProgramForm();
    openAddModal();
    expect(modalMode.value).toBe('add');
    closeModal();
    openEditModal({
      id: 'new-uuid',
      name: 'Test',
      description: 'Desc',
      start_date: '2026-01-01',
      end_date: '2026-12-31',
      status: 'draft',
      quota_total: null,
      benefit_amount: null,
      criteria: {},
      banner_url: null,
    });
    expect(modalMode.value).toBe('edit');
    expect(editId.value).toBe('new-uuid');
  });
});