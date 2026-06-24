<template>
  <div class="min-h-screen bg-[#FDFBF9] p-4 md:p-8 font-sans text-[#4A3728]">
    
    <div v-if="loading" class="flex items-center justify-center py-32">
      <div class="text-center">
        <svg class="animate-spin h-12 w-12 text-[#1B4332] mx-auto mb-4" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <p class="text-[#6B705C] font-bold text-sm">Memuat data warga...</p>
      </div>
    </div>

    <div v-else-if="!citizen" class="flex items-center justify-center py-32">
      <div class="text-center">
        <p class="text-[#6B705C] font-bold text-lg">Data tidak ditemukan.</p>
        <button @click="$router.push({ name: 'admin.citizen-registration' })" class="mt-4 text-sm font-bold text-[#1B4332] hover:underline">Kembali ke daftar</button>
      </div>
    </div>

    <div v-else class="max-w-4xl mx-auto space-y-8 animate-fade-in">
      
      <div class="flex items-center justify-between">
        <button @click="$router.push({ name: 'admin.citizen-registration' })" class="flex items-center gap-2 text-[#8B5E3C] hover:text-[#1B4332] font-black text-[10px] uppercase tracking-widest transition-all group">
          <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
          Kembali ke Daftar Warga
        </button>
      </div>

      <div class="bg-white rounded-[3rem] p-8 md:p-10 border-2 border-[#E8D5C4] shadow-sm">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-5 mb-8">
          <div>
            <div class="flex items-center gap-3 mb-2">
              <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] flex items-center justify-center text-white font-black text-xl shadow-lg">
                {{ citizen.full_name?.charAt(0).toUpperCase() }}
              </div>
              <div>
                <h1 class="text-2xl font-black text-[#1B4332] tracking-tight">{{ citizen.full_name }}</h1>
                <p class="text-sm text-[#6B705C] font-mono">{{ citizen.nik }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-6 border-t border-[#E8D5C4]">
          <div class="flex justify-between py-3 px-5 bg-[#FAF6F0] rounded-2xl">
            <span class="text-xs font-bold text-[#6B705C] uppercase tracking-wider">No. KK</span>
            <span class="text-sm font-bold text-[#1B4332]">{{ citizen.family_card_number }}</span>
          </div>
          <div class="flex justify-between py-3 px-5 bg-[#FAF6F0] rounded-2xl">
            <span class="text-xs font-bold text-[#6B705C] uppercase tracking-wider">WhatsApp</span>
            <span class="text-sm font-bold text-[#1B4332]">{{ citizen.whatsapp_number || '-' }}</span>
          </div>
          <div class="flex justify-between py-3 px-5 bg-[#FAF6F0] rounded-2xl">
            <span class="text-xs font-bold text-[#6B705C] uppercase tracking-wider">Login Terakhir</span>
            <span class="text-sm font-bold text-[#1B4332]">{{ citizen.last_login_at || 'Belum pernah login' }}</span>
          </div>
          <div class="flex justify-between py-3 px-5 bg-[#FAF6F0] rounded-2xl">
            <span class="text-xs font-bold text-[#6B705C] uppercase tracking-wider">Terdaftar Sejak</span>
            <span class="text-sm font-bold text-[#1B4332]">{{ citizen.created_at }}</span>
          </div>
        </div>

        <div class="flex items-center gap-3 pt-6">
          <button 
            @click="handleResetAndPrint"
            :disabled="isResetting"
            class="px-5 py-3 bg-[#1B4332] text-white font-bold rounded-xl hover:bg-[#2D6A4F] transition-all flex items-center gap-2 shadow-sm disabled:opacity-50"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            {{ isResetting ? 'Memproses...' : 'Reset & Cetak PIN' }}
          </button>
        </div>
      </div>

      <div v-if="registrationLogs.length" class="bg-white rounded-[3rem] p-8 md:p-10 border-2 border-[#E8D5C4] shadow-sm">
        <h3 class="text-[#8B5E3C] font-black uppercase text-[10px] tracking-[0.2em] mb-6 flex items-center gap-3">
          <div class="w-8 h-8 bg-[#FAF6F0] rounded-xl flex items-center justify-center text-[#1B4332]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          Riwayat Pendaftaran
        </h3>
        <div class="space-y-3">
          <div v-for="log in registrationLogs" :key="log.id" class="flex items-start gap-4 p-4 bg-[#FAF6F0] rounded-2xl">
            <div class="w-9 h-9 rounded-xl bg-[#1B4332]/10 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-[#1B4332]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </div>
            <div class="flex-1">
              <p class="text-sm font-bold text-[#1B4332]">{{ log.admin_name || 'Admin' }}</p>
              <p class="text-xs text-[#6B705C]">{{ actionLabel(log.action) }}</p>
            </div>
            <span class="text-[10px] text-[#6B705C]">{{ log.created_at }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-[3rem] p-8 md:p-10 border-2 border-[#E8D5C4] shadow-sm">
        <h3 class="text-[#8B5E3C] font-black uppercase text-[10px] tracking-[0.2em] mb-6 flex items-center gap-3">
          <div class="w-8 h-8 bg-[#FAF6F0] rounded-xl flex items-center justify-center text-[#1B4332]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          </div>
          Riwayat Pengajuan
        </h3>
        <div v-if="submissions.length" class="space-y-3">
          <div v-for="sub in submissions" :key="sub.id" class="flex items-center justify-between p-4 bg-[#FAF6F0] rounded-2xl">
            <div>
              <p class="text-sm font-bold text-[#1B4332]">{{ sub.registration_number }}</p>
              <p class="text-xs text-[#6B705C]">{{ sub.program?.name || 'Program' }}</p>
            </div>
            <span :class="submissionStatusBadge(sub.status)" class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase">
              {{ sub.status }}
            </span>
          </div>
        </div>
        <p v-else class="text-sm text-[#6B705C] text-center py-6">Belum ada pengajuan.</p>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import adminApi from '../../api/axios';
import { registrationCitizenApi } from '../../api/registrationCitizenApi';

// ===== TYPES =====
interface CitizenDetail {
  id: string;
  nik: string;
  full_name: string;
  family_card_number: string;
  whatsapp_number: string | null;
  last_login_at: string | null;
  created_at: string;
}

interface RegistrationLog {
  id: string;
  admin_name: string;
  admin_role: string;
  action: string;
  created_at: string;
}

interface SubmissionItem {
  id: string;
  registration_number: string;
  status: string;
  program?: {
    id?: string;
    name?: string;
  } | null;
}

// ===== STATE =====
const route = useRoute();
const citizen = ref<CitizenDetail | null>(null);
const registrationLogs = ref<RegistrationLog[]>([]);
const submissions = ref<SubmissionItem[]>([]);
const loading = ref(true);
const isResetting = ref(false);

// ===== FETCH =====
onMounted(async () => {
  const id = route.params.id as string;
  try {
    const [citizenRes, logsRes, submissionsRes] = await Promise.all([
      adminApi.get(`citizen-registration/citizens/${id}`),
      adminApi.get(`citizen-registration/citizens/${id}/logs`),
      adminApi.get(`citizen-registration/citizens/${id}/submissions`),
    ]);
    citizen.value = citizenRes.data.data;
    registrationLogs.value = logsRes.data.data || [];
    submissions.value = submissionsRes.data.data || [];
  } catch (e: unknown) {
    console.error('Failed to load citizen detail', e);
  } finally {
    loading.value = false;
  }
});

// ===== ACTIONS =====
const handleResetAndPrint = async (): Promise<void> => {
  if (!citizen.value) return;
  isResetting.value = true;
  try {
    const response = await registrationCitizenApi.resetAndPrintPin(citizen.value.id);
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    window.open(url, '_blank');
  } catch (e: unknown) {
    console.error('Failed to reset and print PIN', e);
  } finally {
    isResetting.value = false;
  }
};

// ===== HELPERS =====
const actionLabel = (action: string): string => ({
  register_with_pin: 'Mendaftarkan warga dengan PIN akses',
  register_without_pin: 'Mendaftarkan warga',
  resend_pin: 'Mengirim ulang PIN akses',
}[action] || action);

const submissionStatusBadge = (status: string): string => ({
  pending: 'bg-yellow-100 text-yellow-700',
  validated: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
  needs_revision: 'bg-blue-100 text-blue-700',
  completed: 'bg-gray-100 text-gray-700',
}[status] || 'bg-gray-100 text-gray-700');
</script>