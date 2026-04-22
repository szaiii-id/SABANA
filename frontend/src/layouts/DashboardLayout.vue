<template>
  <div class="min-h-screen flex flex-col relative bg-[#fdfaf5]">
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
      <div class="absolute top-[-10%] left-[-10%] w-[70%] h-[70%] bg-[#2D6A4F]/15 rounded-full blur-[120px] animate-blob"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] bg-[#D4A373]/15 rounded-full blur-[120px] animate-blob animation-delay-2000"></div>
    </div>

    <DashboardNavbar :loading="isSubmitting" @logout="isLogoutModalOpen = true" />

    <main class="flex-grow relative z-10 container mx-auto px-6 py-10">
      <router-view />
    </main>

    <Footer class="relative z-10" />

    <LogoutModal 
      :show="isLogoutModalOpen" 
      @close="isLogoutModalOpen = false" 
      @confirm="onConfirmLogout" 
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import DashboardNavbar from '../components/layout/DashboardNavbar.vue';
import LogoutModal from '../components/layout/LogoutModal.vue';
import Footer from '../components/layout/Footer.vue';
import { useAuth } from '../composables/useAuth';
import { useIdleTimeout } from '../composables/useIdleTimeout';

useIdleTimeout();

// State untuk mengontrol modal
const isLogoutModalOpen = ref(false);

// Ambil logic logout dari composable yang sudah kita update tadi
const { isSubmitting, handleLogout } = useAuth();

const onConfirmLogout = async () => {
  isLogoutModalOpen.value = false; // Tutup modal dulu
  await handleLogout();            // Jalankan proses logout (API & Redirect)
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