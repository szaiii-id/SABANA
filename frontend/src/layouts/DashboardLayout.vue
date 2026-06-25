<template>
  <div class="min-h-screen bg-[#fdfaf5] relative overflow-hidden font-sans">
    
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
      <div class="absolute top-[-10%] left-[-10%] w-[70%] h-[70%] bg-[#2D6A4F]/10 rounded-full blur-[120px] animate-blob"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] bg-[#D4A373]/10 rounded-full blur-[120px] animate-blob animation-delay-2000"></div>
    </div>

    <div class="relative z-10 grid grid-cols-[260px_1fr] grid-rows-[auto_1fr] min-h-screen p-6 gap-x-6 gap-y-4">
      
      <div class="flex items-center justify-center py-2">
        <router-link to="/dashboard" class="text-3xl font-[1000] italic tracking-tighter text-[#2D6A4F] text-center">
          SABANA
        </router-link>
      </div>

      <DashboardNavbar 
        ref="navbarRef"
        :loading="isSubmitting" 
        @logout="isLogoutModalOpen = true" 
      />

      <DashboardSidebar />

      <main class="flex flex-col gap-4">
        <div class="flex-grow bg-white/90 backdrop-blur-2xl border border-white rounded-[2.5rem] shadow-[0_20px_50px_rgba(45,106,79,0.08)] p-8 overflow-y-auto">
          <router-view />
        </div>
        
        <DashboardFooter />
      </main>

    </div>

    <IdleWarningToast :isWarning="isWarning" :remainingSeconds="remainingSeconds" />
    
    <LogoutModal 
      :show="isLogoutModalOpen" 
      @close="isLogoutModalOpen = false" 
      @confirm="onConfirmLogout" 
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, provide } from 'vue';
import DashboardNavbar from '../components/layout/DashboardNavbar.vue';
import DashboardSidebar from '../components/layout/DashboardSidebar.vue';
import DashboardFooter from '../components/layout/DashboardFooter.vue';
import LogoutModal from '../components/layout/LogoutModal.vue';
import IdleWarningToast from '../components/layout/IdleWarningToast.vue';
import { useAuth } from '../composables/useAuth';
import { useIdleTimeout } from '../composables/useIdleTimeout';
import api from '../api/axios';

// ===== COMPOSABLES =====
const isLogoutModalOpen = ref(false);
const { isSubmitting, handleLogout } = useAuth();
const navbarRef = ref<InstanceType<typeof DashboardNavbar>>();

// Idle timeout 5 menit + warning 60 detik
const { isWarning, remainingSeconds } = useIdleTimeout(async () => {
  await handleLogout();
}, {
  timeoutMinutes: 5,
  enableWarning: true,
  debug: import.meta.env.DEV,
});

// ===== FETCH PROFILE =====
onMounted(async () => {
  try {
    const response = await api.get('/citizen/profile');
    const fullName = response.data?.data?.full_name || 'Warga';
    navbarRef.value?.setFirstName(fullName.split(' ')[0]);
  } catch {
    // Biarkan default 'Warga'
  }
});

// ===== PROVIDE: Update nama di header =====
const updateFirstName = (name: string): void => {
  navbarRef.value?.setFirstName(name.split(' ')[0]);
};
provide('updateFirstName', updateFirstName);

// ===== LOGOUT =====
const onConfirmLogout = async (): Promise<void> => {
  isLogoutModalOpen.value = false;
  await handleLogout();
};
</script>

<style scoped>
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob { animation: blob 15s infinite alternate ease-in-out; }
.animation-delay-2000 { animation-delay: 2s; }
</style>