<template>
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200/60 overflow-hidden">
    
    <div v-if="loading" class="p-12 text-center text-slate-400">Memuat data...</div>

    <div v-else-if="!logs.length" class="p-12 text-center">
      <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <p class="font-bold text-slate-500">Tidak ada log</p>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
            <th class="px-5 py-3 whitespace-nowrap">Waktu</th>
            <th class="px-5 py-3">Aktor</th>
            <th class="px-5 py-3">Role</th>
            <th class="px-5 py-3">Modul</th>
            <th class="px-5 py-3">Aksi</th>
            <th class="px-5 py-3">Target</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50 text-[13px] transition-colors">
            <td class="px-5 py-3 text-slate-500 whitespace-nowrap">{{ formatTime(log.created_at) }}</td>
            <td class="px-5 py-3 font-medium text-slate-800">{{ log.actor_name }}</td>
            <td class="px-5 py-3">
              <span :class="roleBadge(log.actor_role)" class="px-2 py-0.5 rounded-lg text-[10px] font-bold">
                {{ formatRole(log.actor_role) }}
              </span>
            </td>
            <td class="px-5 py-3">
              <span :class="moduleBadge(log.module)" class="px-2 py-0.5 rounded-lg text-[10px] font-bold">
                {{ formatModule(log.module) }}
              </span>
            </td>
            <td class="px-5 py-3 text-slate-700">{{ log.action_label }}</td>
            <td class="px-5 py-3 text-slate-500 font-mono text-[12px]">{{ log.target_name || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
      <span class="text-[12px] text-slate-500">
        {{ (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage, total) }} dari {{ total }} log
      </span>
      
      <nav class="flex items-center gap-1">
        <button 
          :disabled="currentPage <= 1" 
          @click="$emit('pageChange', currentPage - 1)" 
          class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>

        <template v-for="page in visiblePages" :key="page">
          <span v-if="page === '...'" class="w-8 h-8 flex items-center justify-center text-[12px] text-slate-400">...</span>
          <button 
            v-else
            @click="$emit('pageChange', page as number)" 
            :class="[
              'w-8 h-8 text-[12px] font-medium rounded-lg transition-all',
              currentPage === page 
                ? 'bg-[#1B4332] text-white shadow-sm' 
                : 'text-slate-600 hover:bg-slate-100'
            ]"
          >
            {{ page }}
          </button>
        </template>

        <button 
          :disabled="currentPage >= totalPages" 
          @click="$emit('pageChange', currentPage + 1)" 
          class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
      </nav>
    </div>

  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { ActivityLog } from '../../types/activityLog';

const props = defineProps<{
  logs: ActivityLog[];
  loading: boolean;
  currentPage: number;
  totalPages: number;
  total: number;
}>();

defineEmits<{ pageChange: [page: number] }>();

const perPage = 15;

const visiblePages = computed(() => {
  const pages: (number | string)[] = [];
  const current = props.currentPage;
  const last = props.totalPages;

  if (last <= 5) {
    for (let i = 1; i <= last; i++) pages.push(i);
  } else {
    pages.push(1);
    if (current > 3) pages.push('...');
    
    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);
    for (let i = start; i <= end; i++) pages.push(i);
    
    if (current < last - 2) pages.push('...');
    pages.push(last);
  }
  return pages;
});

const formatTime = (date: string) => new Date(date).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });

const formatRole = (role: string | null) => {
  const map: Record<string, string> = {
    super_admin: 'Super Admin', regency_admin: 'Admin Kab', district_admin: 'Admin Kec',
    village_officer: 'Petugas Desa', citizen: 'Warga', system: 'Sistem'
  };
  return map[role || ''] || role || '-';
};

const roleBadge = (role: string | null) => {
  const map: Record<string, string> = {
    super_admin: 'bg-purple-100 text-purple-700', regency_admin: 'bg-blue-100 text-blue-700',
    district_admin: 'bg-cyan-100 text-cyan-700', village_officer: 'bg-teal-100 text-teal-700',
    citizen: 'bg-slate-100 text-slate-600', system: 'bg-amber-100 text-amber-700'
  };
  return map[role || ''] || 'bg-slate-100 text-slate-600';
};

const formatModule = (module: string) => {
  const map: Record<string, string> = {
    auth: 'Auth', program: 'Program', account: 'Akun', verification: 'Verifikasi',
    disbursement: 'Penyaluran', evaluation: 'Evaluasi', registration: 'Pendaftaran', submission: 'Pengajuan'
  };
  return map[module] || module;
};

const moduleBadge = (module: string) => {
  const map: Record<string, string> = {
    auth: 'bg-slate-100 text-slate-700', program: 'bg-green-100 text-green-700',
    account: 'bg-purple-100 text-purple-700', verification: 'bg-blue-100 text-blue-700',
    disbursement: 'bg-emerald-100 text-emerald-700', evaluation: 'bg-orange-100 text-orange-700',
    registration: 'bg-cyan-100 text-cyan-700', submission: 'bg-indigo-100 text-indigo-700'
  };
  return map[module] || 'bg-slate-100 text-slate-600';
};
</script>