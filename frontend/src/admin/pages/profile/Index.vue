<template>
  <div class="flex flex-col h-full">
    <div class="mb-8">
      <h2 class="text-3xl font-black text-[#1B4332] tracking-tight">Profil Saya</h2>
      <p class="text-[#6B705C] font-medium mt-1 text-sm">Kelola informasi akun dan keamanan Anda.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <div class="bg-white border border-[#E8D5C4] rounded-3xl p-8 shadow-sm">
        <div class="flex items-center gap-4 mb-6">
          <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] flex items-center justify-center text-white font-black text-2xl shadow-lg shadow-[#1B4332]/20">
            {{ initial }}
          </div>
          <div>
            <h3 class="text-xl font-black text-[#1B4332]">{{ profile?.name }}</h3>
            <p class="text-sm text-[#D4A373] font-bold uppercase tracking-wider">{{ formatRole(profile?.role) }}</p>
          </div>
        </div>

        <div class="space-y-4">
          <div class="flex justify-between items-center py-3 border-b border-[#E8D5C4]">
            <span class="text-xs font-bold text-[#6B705C] uppercase tracking-wider">NIP</span>
            <span class="text-sm font-bold text-[#1B4332]">{{ profile?.nip }}</span>
          </div>
          <div class="flex justify-between items-center py-3 border-b border-[#E8D5C4]">
            <span class="text-xs font-bold text-[#6B705C] uppercase tracking-wider">Nama</span>
            <span class="text-sm font-bold text-[#1B4332]">{{ profile?.name }}</span>
          </div>
          <div class="flex justify-between items-center py-3 border-b border-[#E8D5C4]">
            <span class="text-xs font-bold text-[#6B705C] uppercase tracking-wider">Level Akses</span>
            <span class="text-sm font-bold text-[#1B4332]">{{ formatRole(profile?.role) }}</span>
          </div>
          <div class="flex justify-between items-center py-3">
            <span class="text-xs font-bold text-[#6B705C] uppercase tracking-wider">Wilayah</span>
            <span class="text-sm font-bold text-[#1B4332] text-right">
              {{ wilayah || 'Seluruh Kalimantan Selatan' }}
            </span>
          </div>
        </div>

        <button @click="isEditModalOpen = true" class="mt-6 w-full px-5 py-3 bg-[#FAF6F0] border border-[#E8D5C4] text-[#1B4332] font-bold rounded-xl hover:bg-[#1B4332] hover:text-white transition-all duration-300">
          Edit Profil
        </button>
      </div>

      <div class="bg-white border border-[#E8D5C4] rounded-3xl p-8 shadow-sm">
        <h3 class="text-xl font-black text-[#1B4332] mb-4">Keamanan</h3>
        <p class="text-sm text-[#6B705C] mb-6">Ganti password secara berkala untuk menjaga keamanan akun Anda.</p>
        
        <div class="flex items-center justify-between py-3 border-b border-[#E8D5C4]">
          <span class="text-xs font-bold text-[#6B705C] uppercase tracking-wider">Password</span>
          <span class="text-sm font-bold text-[#1B4332]">••••••••</span>
        </div>
        <div class="flex items-center justify-between py-3">
          <span class="text-xs font-bold text-[#6B705C] uppercase tracking-wider">Login Terakhir</span>
          <span class="text-sm font-bold text-[#1B4332]">{{ profile?.last_login_at || '-' }}</span>
        </div>

        <button @click="isPasswordModalOpen = true" class="mt-6 w-full px-5 py-3 bg-[#FFF3E0] border border-[#FFB74D] text-[#E65100] font-bold rounded-xl hover:bg-[#FFB74D] hover:text-white transition-all duration-300">
          Ganti Password
        </button>
      </div>

    </div>

    <EditProfileModal :open="isEditModalOpen" :nip="profile?.nip || ''" :name="profile?.name || ''" :submitting="isSubmitting" :errorMessage="errorMessage" @close="isEditModalOpen = false" @save="handleUpdateProfile" />
    
    <ChangePasswordModal :open="isPasswordModalOpen" :submitting="isSubmitting" :errorMessage="errorMessage" @close="isPasswordModalOpen = false" @save="handleUpdatePassword" />

    <SuccessModal :show="showSuccessModal" :message="successModalMessage" @close="showSuccessModal = false" />

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import EditProfileModal from '../../components/profile/EditProfileModal.vue';
import ChangePasswordModal from '../../components/profile/ChangePasswordModal.vue';
import SuccessModal from '../../components/common/SuccessModal.vue';
import { useProfile } from '../../composables/useProfile';
import type { AdminUser } from '../../types/auth';

const { isSubmitting, errorMessage, updateProfile, updatePassword } = useProfile();

const profile = ref<AdminUser | null>(null);
const isEditModalOpen = ref(false);
const isPasswordModalOpen = ref(false);
const showSuccessModal = ref(false);
const successModalMessage = ref('');

const initial = computed(() => profile.value?.name?.charAt(0).toUpperCase() || 'A');

const wilayah = computed(() => {
  const r = profile.value?.region;
  if (!r) return null;
  return r.village || r.district || r.regency || null;
});

const formatRole = (role?: string) => {
  const map: Record<string, string> = {
    super_admin: 'Super Admin',
    regency_admin: 'Admin Kabupaten',
    district_admin: 'Admin Kecamatan',
    village_officer: 'Petugas Desa'
  };
  return map[role || ''] || role || '';
};

const loadProfile = () => {
  const data = localStorage.getItem('admin_user');
  if (data) profile.value = JSON.parse(data);
};

const handleStorageChange = (e: StorageEvent) => {
  if (e.key === 'admin_user') {
    loadProfile();
  }
};

onMounted(() => {
  loadProfile();
  window.addEventListener('storage', handleStorageChange);
});

onUnmounted(() => {
  window.removeEventListener('storage', handleStorageChange);
});

const handleUpdateProfile = async (name: string) => {
  const result = await updateProfile(name);
  if (result.success) {
    isEditModalOpen.value = false;
    loadProfile();
    showSuccessMessage('Profil berhasil diperbarui.');
  }
};

const handleUpdatePassword = async (currentPassword: string, newPassword: string) => {
  const result = await updatePassword(currentPassword, newPassword);
  if (result.success) {
    isPasswordModalOpen.value = false;
    showSuccessMessage('Password berhasil diubah.');
  }
};

const showSuccessMessage = (message: string) => {
  successModalMessage.value = message;
  showSuccessModal.value = true;
};
</script>