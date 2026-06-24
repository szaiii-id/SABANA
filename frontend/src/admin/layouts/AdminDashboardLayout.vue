<template>
  <div class="min-h-screen bg-[#FAF6F0] relative overflow-hidden font-sans">
    
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
      <div class="absolute top-[-5%] right-[-5%] w-[50%] h-[60%] bg-[#1B4332]/5 rounded-full blur-[150px] animate-blob"></div>
      <div class="absolute bottom-[-10%] left-[-5%] w-[45%] h-[55%] bg-[#D4A373]/8 rounded-full blur-[130px] animate-blob animation-delay-2000"></div>
      <div class="absolute top-[40%] left-[30%] w-[40%] h-[40%] bg-[#2D6A4F]/4 rounded-full blur-[100px] animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative z-10 min-h-screen p-2 md:p-3 flex flex-col gap-3">
      
      <!-- Top Row: Logo + Navbar -->
      <div class="grid grid-cols-[260px_1fr] gap-3 flex-shrink-0">
        <router-link :to="{ name: 'admin.dashboard' }" class="flex items-center justify-center">
          <div class="text-center leading-none">
            <h1 class="text-3xl font-black italic tracking-tight text-[#1B4332]">SABANA</h1>
            <span class="block text-[10px] font-bold not-italic tracking-[0.2em] text-[#D4A373] mt-1 uppercase">Center</span>
            <div class="mt-2 w-10 h-1 bg-gradient-to-r from-[#1B4332] to-[#D4A373] rounded-full mx-auto"></div>
          </div>
        </router-link>
        <AdminNavbar :loading="isSubmitting" @logout="isLogoutModalOpen = true" />
      </div>

      <!-- Bottom Row: Sidebar + Content -->
      <div class="grid grid-cols-[260px_1fr] gap-3 flex-1 min-h-0">
        <div class="flex-shrink-0">
          <AdminSidebar />
        </div>
        <main class="flex flex-col gap-3 min-h-0">
          <div class="flex-1 bg-white/95 backdrop-blur-xl border border-[#E8D5C4] rounded-[2rem] shadow-[0_20px_50px_rgba(27,67,50,0.05)] p-2 overflow-y-auto">
            <router-view />
          </div>
          <AdminFooter class="flex-shrink-0" />
        </main>
      </div>

    </div>

    <IdleWarningToast :isWarning="isWarning" :remainingSeconds="remainingSeconds" />
    <LogoutModal :show="isLogoutModalOpen" @close="isLogoutModalOpen = false" @confirm="onConfirmLogout" />

  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import AdminNavbar from '../components/layout/AdminNavbar.vue';
import AdminSidebar from '../components/layout/AdminSidebar.vue';
import AdminFooter from '../components/layout/AdminFooter.vue';
import LogoutModal from '../components/layout/LogoutModal.vue';
import IdleWarningToast from '../components/layout/IdleWarningToast.vue';
import { useAuth } from '../composables/useAuth';
import { useIdleTimeout } from '../composables/useIdleTimeout';

const { isSubmitting, handleLogout } = useAuth();
const isLogoutModalOpen = ref(false);

const { isWarning, remainingSeconds } = useIdleTimeout(async () => {
  await handleLogout();
}, {
  timeoutMinutes: 30,
  enableWarning: true,
  debug: import.meta.env.DEV
});

const onConfirmLogout = async () => {
  isLogoutModalOpen.value = false;
  isSubmitting.value = true;
  try {
    await handleLogout();
  } catch (error) {
    console.error('Logout failed:', error);
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.08); }
  66% { transform: translate(-20px, 20px) scale(0.95); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob { animation: blob 18s infinite alternate cubic-bezier(0.4, 0, 0.2, 1); }
.animation-delay-2000 { animation-delay: 2s; }
.animation-delay-4000 { animation-delay: 4s; }
</style>