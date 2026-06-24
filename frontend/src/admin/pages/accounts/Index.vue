<template>
  <div class="flex flex-col h-full space-y-6">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h2 class="text-3xl font-black text-[#1B4332] tracking-tight">Manajemen Akun</h2>
        <p class="text-[#6B705C] font-medium mt-1 text-sm">Kelola akses pegawai dan hierarki wilayah SABANA Center.</p>
      </div>
      <button @click="openAddModal" class="flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] text-white font-bold rounded-2xl shadow-lg shadow-[#1B4332]/20 hover:shadow-xl hover:scale-[1.02] transition-all duration-300">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Tambah Pegawai
      </button>
    </div>

    <AccountFilters v-model="filters" @update:model-value="debounceSearch" />

    <AccountTable 
      :accounts="accounts" 
      :loading="loading" 
      :currentUserId="currentUser?.id" 
      :total="pagination.total"
      :currentPage="pagination.currentPage"
      :totalPages="pagination.totalPages"
      @edit="openEditModal" 
      @delete="confirmDelete"
      @activate="handleActivate"
      @resetPassword="openResetModal"
      @pageChange="changePage"
    />

    <AccountModal :open="isModalOpen" :mode="modalMode" :submitting="isSubmitting" :error="errorMessage" @close="closeModal" @save="handleSave">
      <AccountFormFields :form="formData" :mode="modalMode" :errors="validationErrors" @roleChange="resetRegions" />
      <RegionSection 
        v-if="formData.role !== 'super_admin'" 
        v-model:regencyId="formData.regency_id" 
        v-model:districtId="formData.district_id" 
        v-model:villageId="formData.village_id" 
        :regencies="regencies" 
        :districts="districts" 
        :villages="villages" 
        :showDistrict="['district_admin','village_officer'].includes(formData.role)" 
        :showVillage="formData.role === 'village_officer'" 
        @update:regencyId="handleRegencyChange" 
        @update:districtId="handleDistrictChange" 
      />
    </AccountModal>

    <DeleteConfirmModal 
      :open="isDeleteModalOpen" 
      :message="deleteMessage" 
      :submitting="isSubmitting" 
      @close="isDeleteModalOpen = false" 
      @confirm="executeDelete" 
    />

    <ResetPasswordModal 
      :open="isResetModalOpen"
      :targetName="resetTarget?.name || ''"
      :targetNip="resetTarget?.nip || ''"
      :submitting="isSubmitting"
      :serverError="resetError"
      @close="isResetModalOpen = false"
      @save="handleResetPassword"
    />

    <SuccessModal 
      :show="showSuccessModal" 
      :message="successModalMessage" 
      @close="showSuccessModal = false" 
    />

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import AccountService from '../../services/AccountService';
import { useRegion } from '../../composables/useRegion';
import AccountFilters from '../../components/account/AccountFilters.vue';
import AccountTable from '../../components/account/AccountTable.vue';
import AccountModal from '../../components/account/AccountModal.vue';
import AccountFormFields from '../../components/account/AccountFormFields.vue';
import RegionSection from '../../components/account/RegionSection.vue';
import DeleteConfirmModal from '../../components/common/DeleteConfirmModal.vue';
import ResetPasswordModal from '../../components/common/ResetPasswordModal.vue';
import SuccessModal from '../../components/common/SuccessModal.vue';
import type { AccountData, AccountPayload } from '../../types/account';

const accounts = ref<AccountData[]>([]);
const loading = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref('');
const resetError = ref('');
const isModalOpen = ref(false);
const modalMode = ref<'add' | 'edit'>('add');
const editId = ref('');

const isDeleteModalOpen = ref(false);
const deleteTarget = ref<AccountData | null>(null);
const deleteMessage = ref('');

const isResetModalOpen = ref(false);
const resetTarget = ref<AccountData | null>(null);

const showSuccessModal = ref(false);
const successModalMessage = ref('');

const validationErrors = ref<Record<string, string>>({ nip: '', name: '', password: '' });

const pagination = ref({
  currentPage: 1,
  total: 0,
  totalPages: 1,
  perPage: 15,
});

const { regencies, districts, villages, fetchRegencies, fetchDistricts, fetchVillages, clearRegions } = useRegion();

const currentUser = computed(() => { 
  const d = localStorage.getItem('admin_user'); 
  return d ? JSON.parse(d) : null; 
});

const filters = ref({ search: '', role: '', is_active: '' });
const formData = ref<AccountPayload>({ 
  nip: '', 
  name: '', 
  password: '', 
  role: 'village_officer', 
  regency_id: '', 
  district_id: '', 
  village_id: '', 
  is_active: true 
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const debounceSearch = () => { 
  if (searchTimeout) clearTimeout(searchTimeout); 
  pagination.value.currentPage = 1;
  searchTimeout = setTimeout(fetchData, 500); 
};

const fetchData = async () => { 
  loading.value = true; 
  try { 
    const apiFilters = {
      search: filters.value.search,
      role: filters.value.role,
      is_active: filters.value.is_active === '' 
        ? undefined 
        : filters.value.is_active === 'true',
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage,
    };
    
    const r = await AccountService.fetchAccounts(apiFilters);
    
    accounts.value = r.data || [];
    pagination.value = {
      currentPage: r.current_page || 1,
      total: r.total || 0,
      totalPages: r.last_page || 1,
      perPage: r.per_page || 15,
    };
  } catch (e) { 
    console.error('Failed to fetch accounts:', e); 
    errorMessage.value = 'Gagal memuat data akun. Silakan coba lagi.';
  } finally { 
    loading.value = false; 
  } 
};

const changePage = (page: number) => {
  pagination.value.currentPage = page;
  fetchData();
};

const handleStorageChange = (event: StorageEvent) => {
  if (event.key === 'admin_user' || event.key === 'admin_token') {
    fetchData();
  }
};

onMounted(() => { 
  fetchData(); 
  fetchRegencies();
  window.addEventListener('storage', handleStorageChange);
});

onUnmounted(() => {
  if (searchTimeout) clearTimeout(searchTimeout);
  window.removeEventListener('storage', handleStorageChange);
});

const handleRegencyChange = async (val: string) => { 
  formData.value.district_id = ''; 
  formData.value.village_id = ''; 
  if (val) await fetchDistricts(val); 
};

const handleDistrictChange = async (val: string) => { 
  formData.value.village_id = ''; 
  if (val) await fetchVillages(val); 
};

const resetRegions = () => { 
  formData.value.regency_id = ''; 
  formData.value.district_id = ''; 
  formData.value.village_id = ''; 
  clearRegions(); 
};

const openAddModal = () => { 
  modalMode.value = 'add'; 
  formData.value = { 
    nip: '', 
    name: '', 
    password: '', 
    role: 'village_officer', 
    is_active: true, 
    regency_id: '', 
    district_id: '', 
    village_id: '' 
  }; 
  validationErrors.value = { nip: '', name: '', password: '' };
  errorMessage.value = ''; 
  clearRegions();
  isModalOpen.value = true; 
};

const openEditModal = async (account: AccountData) => {
  modalMode.value = 'edit'; 
  editId.value = account.id;
  formData.value = { 
    nip: account.nip, 
    name: account.name, 
    password: '', 
    role: account.role, 
    is_active: account.is_active, 
    regency_id: account.regency_id || '', 
    district_id: account.district_id || '', 
    village_id: account.village_id || '' 
  };
  validationErrors.value = { nip: '', name: '', password: '' };
  errorMessage.value = ''; 
  isModalOpen.value = true;
  
  if (formData.value.regency_id) { 
    await fetchDistricts(formData.value.regency_id); 
    if (formData.value.district_id) await fetchVillages(formData.value.district_id); 
  }
};

const openResetModal = (account: AccountData) => {
  resetTarget.value = account;
  resetError.value = '';
  isResetModalOpen.value = true;
};

const handleResetPassword = async (password: string) => {
  if (!resetTarget.value) return;
  isSubmitting.value = true;
  resetError.value = '';
  try {
    await AccountService.resetPassword(resetTarget.value.id, password);
    isResetModalOpen.value = false;
    resetTarget.value = null;
    showSuccessModalMessage('Password berhasil direset.');
  } catch (e: any) {
    resetError.value = e.response?.data?.message || 'Gagal mereset password.';
  } finally {
    isSubmitting.value = false;
  }
};

const handleActivate = async (account: AccountData) => {
  isSubmitting.value = true;
  errorMessage.value = '';
  try {
    await AccountService.activateAccount(account.id);
    showSuccessModalMessage('Akun berhasil diaktifkan.');
    await fetchData();
  } catch (e: any) {
    errorMessage.value = e.response?.data?.message || 'Gagal mengaktifkan akun.';
  } finally {
    isSubmitting.value = false;
  }
};

const showSuccessModalMessage = (message: string) => {
  successModalMessage.value = message;
  showSuccessModal.value = true;
};

const closeModal = () => { 
  isModalOpen.value = false; 
  validationErrors.value = { nip: '', name: '', password: '' };
  errorMessage.value = '';
};

const validateForm = (): boolean => {
  validationErrors.value = { nip: '', name: '', password: '' };
  errorMessage.value = '';
  let isValid = true;

  if (modalMode.value === 'add') {
    if (!formData.value.nip) {
      validationErrors.value.nip = 'NIP wajib diisi.';
      isValid = false;
    } else if (formData.value.nip.length !== 18) {
      validationErrors.value.nip = 'NIP harus 18 digit.';
      isValid = false;
    }
  }

  if (!formData.value.name?.trim()) {
    validationErrors.value.name = 'Nama lengkap wajib diisi.';
    isValid = false;
  }

  if (modalMode.value === 'add') {
    if (!formData.value.password) {
      validationErrors.value.password = 'Kata sandi wajib diisi.';
      isValid = false;
    } else if (formData.value.password.length < 8) {
      validationErrors.value.password = 'Kata sandi minimal 8 karakter.';
      isValid = false;
    }
  } else {
    if (formData.value.password && formData.value.password.length > 0 && formData.value.password.length < 8) {
      validationErrors.value.password = 'Kata sandi minimal 8 karakter.';
      isValid = false;
    }
  }

  if (formData.value.role !== 'super_admin' && !formData.value.regency_id) {
    errorMessage.value = 'Kabupaten/Kota wajib dipilih.';
    isValid = false;
  }
  if (['district_admin', 'village_officer'].includes(formData.value.role) && !formData.value.district_id) {
    errorMessage.value = 'Kecamatan wajib dipilih.';
    isValid = false;
  }
  if (formData.value.role === 'village_officer' && !formData.value.village_id) {
    errorMessage.value = 'Desa/Kelurahan wajib dipilih.';
    isValid = false;
  }

  return isValid;
};

const saveData = async () => {
  errorMessage.value = '';
  isSubmitting.value = true;

  try {
    const payload: AccountPayload = { ...formData.value };

    if (!payload.password) delete payload.password;
    if (!payload.regency_id) delete payload.regency_id;
    if (!payload.district_id) delete payload.district_id;
    if (!payload.village_id) delete payload.village_id;

    if (modalMode.value === 'add') {
      delete payload.is_active;
    }

    if (payload.role === 'super_admin') {
      delete payload.regency_id;
      delete payload.district_id;
      delete payload.village_id;
    }
    if (payload.role === 'regency_admin') {
      delete payload.district_id;
      delete payload.village_id;
    }
    if (payload.role === 'district_admin') {
      delete payload.village_id;
    }

    if (modalMode.value === 'add') {
      await AccountService.createAccount(payload);
      showSuccessModalMessage('Akun pegawai berhasil dibuat.');
    } else {
      await AccountService.updateAccount(editId.value, payload);
      showSuccessModalMessage('Data akun berhasil diperbarui.');
    }

    closeModal();
    await fetchData();
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response.data?.errors;
      
      if (errors?.nip) validationErrors.value.nip = errors.nip[0];
      if (errors?.name) validationErrors.value.name = errors.name[0];
      if (errors?.password) validationErrors.value.password = errors.password[0];
      if (errors?.role) errorMessage.value = errors.role[0];
      if (errors?.regency_id) errorMessage.value = errors.regency_id[0];
      if (errors?.district_id) errorMessage.value = errors.district_id[0];
      if (errors?.village_id) errorMessage.value = errors.village_id[0];
      
      if (!errorMessage.value && !validationErrors.value.nip && !validationErrors.value.name && !validationErrors.value.password) {
        errorMessage.value = e.response.data?.message || 'Terjadi kesalahan validasi.';
      }
    } else if (e.response?.status === 403) {
      errorMessage.value = e.response.data?.message || 'Anda tidak memiliki wewenang untuk melakukan aksi ini.';
    } else if (e.response?.status === 404) {
      errorMessage.value = 'Data akun tidak ditemukan.';
    } else {
      errorMessage.value = e.response?.data?.message || 'Terjadi kesalahan pada server. Silakan coba lagi.';
    }
  } finally {
    isSubmitting.value = false;
  }
};

const handleSave = async () => {
  if (!validateForm()) return;
  await saveData();
};

const confirmDelete = (account: AccountData) => {
  deleteTarget.value = account;
  deleteMessage.value = `Anda akan menonaktifkan akun <strong>${account.name}</strong> (${account.nip}). Akun tidak dapat login sampai diaktifkan kembali.`;
  isDeleteModalOpen.value = true;
};

const executeDelete = async () => {
  if (!deleteTarget.value) return;
  isSubmitting.value = true;
  
  try {
    await AccountService.deleteAccount(deleteTarget.value.id);
    isDeleteModalOpen.value = false;
    deleteTarget.value = null;
    showSuccessModalMessage('Akun berhasil dinonaktifkan.');
    await fetchData();
  } catch (e: any) {
    isDeleteModalOpen.value = false;
    errorMessage.value = e.response?.data?.message || 'Gagal menonaktifkan akun.';
    deleteTarget.value = null;
  } finally {
    isSubmitting.value = false;
  }
};
</script>