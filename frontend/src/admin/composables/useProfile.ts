import { ref, onMounted, onUnmounted } from 'vue';
import { profileService } from '../services/ProfileService';
import type { AdminUser } from '../types/auth';

interface ApiError {
  response?: {
    status: number;
    data?: {
      message?: string;
      errors?: Record<string, string[]>;
    };
  };
}

export function useProfile() {
  const isSubmitting = ref(false);
  const errorMessage = ref('');
  const profile = ref<AdminUser | null>(null);

  const loadProfile = () => {
    const data = localStorage.getItem('admin_user');
    if (data) {
      try {
        profile.value = JSON.parse(data) as AdminUser;
      } catch {
        profile.value = null;
      }
    }
  };

  const handleStorageChange = (e: StorageEvent) => {
    if (e.key === 'admin_user') {
      loadProfile();
    }
  };

  const handleProfileUpdated = () => {
    loadProfile();
  };

  onMounted(() => {
    loadProfile();
    window.addEventListener('storage', handleStorageChange);
    window.addEventListener('admin-profile-updated', handleProfileUpdated);
  });

  onUnmounted(() => {
    window.removeEventListener('storage', handleStorageChange);
    window.removeEventListener('admin-profile-updated', handleProfileUpdated);
  });

  const updateProfile = async (name: string) => {
    isSubmitting.value = true;
    errorMessage.value = '';

    try {
      const response = await profileService.update({ name });
      const admin = response.data?.data || response.data;
      localStorage.setItem('admin_user', JSON.stringify(admin));
      profile.value = admin as AdminUser;
      window.dispatchEvent(new CustomEvent('admin-profile-updated'));
      return { success: true };
    } catch (e: unknown) {
      const err = e as ApiError;
      errorMessage.value = err.response?.data?.message || 'Gagal memperbarui profil.';
      return { success: false };
    } finally {
      isSubmitting.value = false;
    }
  };

  const updatePassword = async (currentPassword: string, newPassword: string) => {
    isSubmitting.value = true;
    errorMessage.value = '';

    try {
      await profileService.updatePassword({ current_password: currentPassword, new_password: newPassword });
      return { success: true };
    } catch (e: unknown) {
      const err = e as ApiError;
      if (err.response?.status === 422) {
        errorMessage.value = err.response.data?.errors?.current_password?.[0] || 'Password saat ini tidak sesuai.';
      } else {
        errorMessage.value = err.response?.data?.message || 'Gagal mengubah password.';
      }
      return { success: false };
    } finally {
      isSubmitting.value = false;
    }
  };

  return { profile, isSubmitting, errorMessage, updateProfile, updatePassword };
}