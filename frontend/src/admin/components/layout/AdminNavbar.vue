<template>
  <header class="bg-white/95 backdrop-blur-xl border border-[#E8D5C4] rounded-[3rem] shadow-[0_10px_30px_rgba(27,67,50,0.04)] px-8 py-4 flex justify-between items-center">
    
    <div class="flex items-center gap-3">
      <div class="w-1 h-8 bg-gradient-to-b from-[#1B4332] to-[#D4A373] rounded-full"></div>
      <div>
        <p class="text-[10px] font-black text-[#D4A373] uppercase tracking-[0.2em]">Workspace</p>
        <p class="text-sm font-bold text-[#1B4332]">{{ currentRouteName }}</p>
      </div>
    </div>

    <div class="flex items-center gap-6">
      <div class="text-right">
        <p class="text-sm font-bold text-[#1B4332] leading-tight">{{ adminInfo.name }}</p>
        <p class="text-[10px] font-bold text-[#D4A373] uppercase tracking-wider">{{ adminInfo.roleLabel }}</p>
      </div>
      
      <div class="relative">
        <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] flex items-center justify-center text-white font-black text-lg shadow-lg shadow-[#1B4332]/20 border-2 border-white">
          {{ adminInfo.initial }}
        </div>
        <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-green-500 border-2 border-white rounded-full"></div>
      </div>

      <div class="w-px h-10 bg-gradient-to-b from-transparent via-[#E8D5C4] to-transparent"></div>

      <button 
        @click="$emit('logout')" 
        :disabled="loading"
        class="flex items-center gap-3 px-5 py-2.5 text-sm font-bold text-[#6B705C] bg-[#FAF6F0] rounded-2xl hover:bg-red-50 hover:text-red-600 transition-all duration-300 disabled:opacity-50 border border-[#E8D5C4] hover:border-red-200 group"
      >
        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        {{ loading ? 'Keluar...' : 'Keluar' }}
      </button>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute } from 'vue-router';

defineProps<{
  loading?: boolean;
}>();

defineEmits(['logout']);

const route = useRoute();

const adminInfo = ref<{ name: string; initial: string; roleLabel: string }>({
  name: 'Administrator',
  initial: 'A',
  roleLabel: 'Pegawai',
});

const parseAdminInfo = () => {
  try {
    const rawData = localStorage.getItem('admin_user');
    const data = rawData ? JSON.parse(rawData) : null;
    
    const name = data?.name || 'Administrator';
    const role = data?.role || 'village_officer';
    
    const roleMap: Record<string, string> = {
      'super_admin': 'Super Admin',
      'regency_admin': 'Admin Kabupaten',
      'district_admin': 'Admin Kecamatan',
      'village_officer': 'Petugas Desa'
    };
    
    adminInfo.value = {
      name,
      initial: name.charAt(0).toUpperCase(),
      roleLabel: roleMap[role] || 'Pegawai',
    };
  } catch {
    adminInfo.value = { name: 'Administrator', initial: 'A', roleLabel: 'Pegawai' };
  }
};

const handleStorageChange = (e: StorageEvent) => {
  if (e.key === 'admin_user') {
    parseAdminInfo();
  }
};

const handleProfileUpdated = () => {
  parseAdminInfo();
};

onMounted(() => {
  parseAdminInfo();
  window.addEventListener('storage', handleStorageChange);
  window.addEventListener('admin-profile-updated', handleProfileUpdated);
});

onUnmounted(() => {
  window.removeEventListener('storage', handleStorageChange);
  window.removeEventListener('admin-profile-updated', handleProfileUpdated);
});

const currentRouteName = computed(() => {
  return typeof route.name === 'string' ? route.name.split('.').pop()?.toUpperCase() : 'DASHBOARD';
});
</script>