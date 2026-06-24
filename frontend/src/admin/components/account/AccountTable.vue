<template>
  <div class="bg-white border border-[#E8D5C4] rounded-3xl shadow-sm overflow-hidden flex flex-col">
    
    <div v-if="loading" class="p-8 space-y-4">
      <div v-for="i in 3" :key="i" class="flex items-center gap-4 animate-pulse">
        <div class="h-10 w-10 rounded-xl bg-gray-200"></div>
        <div class="flex-1 space-y-2">
          <div class="h-4 bg-gray-200 rounded w-1/3"></div>
          <div class="h-3 bg-gray-200 rounded w-1/4"></div>
        </div>
        <div class="h-6 w-20 bg-gray-200 rounded-lg"></div>
        <div class="h-6 w-32 bg-gray-200 rounded-lg"></div>
        <div class="h-3 w-3 bg-gray-200 rounded-full"></div>
      </div>
    </div>

    <div v-else-if="!accounts.length" class="py-16 text-center">
      <div class="w-20 h-20 bg-[#FAF6F0] rounded-3xl flex items-center justify-center mx-auto mb-4">
        <svg class="w-10 h-10 text-[#D4A373]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
      </div>
      <p class="font-bold text-[#1B4332] text-lg">Belum ada pegawai</p>
      <p class="text-sm text-[#6B705C] mt-1">Klik tombol "Tambah Pegawai" untuk menambahkan akun baru.</p>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-[#FAF6F0] text-[#8B7355] text-xs uppercase tracking-wider font-black border-b border-[#E8D5C4]">
            <th class="px-6 py-4 rounded-tl-3xl">Pegawai</th>
            <th class="px-6 py-4">Level</th>
            <th class="px-6 py-4 hidden md:table-cell">Wilayah</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right rounded-tr-3xl w-28">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#E8D5C4]/30">
          <tr v-for="account in accounts" :key="account.id" class="hover:bg-[#FAF6F0]/30 transition-colors">
            
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] flex items-center justify-center text-white font-bold text-sm shadow-sm flex-shrink-0">
                  {{ account.name?.charAt(0).toUpperCase() || 'A' }}
                </div>
                <div class="min-w-0">
                  <p class="font-bold text-[#1F2937] text-sm truncate">
                    {{ account.name }}
                    <span v-if="account.id === currentUserId" class="inline-block text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full ml-1.5 font-medium align-middle">Anda</span>
                  </p>
                  <p class="text-xs text-[#6B705C] font-medium mt-0.5">{{ account.nip }}</p>
                </div>
              </div>
            </td>

            <td class="px-6 py-4">
              <span :class="roleBadge(account.role)" class="inline-block px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wide border whitespace-nowrap">
                {{ formatRole(account.role) }}
              </span>
            </td>

            <td class="px-6 py-4 hidden md:table-cell">
              <div v-if="account.role === 'super_admin'" class="text-xs text-[#6B705C] italic">Seluruh Kalsel</div>
              <div v-else class="text-xs text-[#1F2937] font-medium">
                {{ account.region?.village || account.region?.district || account.region?.regency || '-' }}
              </div>
            </td>

            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <div :class="account.is_active ? 'bg-green-500 shadow-green-500/30' : 'bg-red-400 shadow-red-400/30'" class="w-2 h-2 rounded-full shadow-sm"></div>
                <span class="text-xs font-bold" :class="account.is_active ? 'text-green-700' : 'text-red-600'">{{ account.is_active ? 'Aktif' : 'Nonaktif' }}</span>
              </div>
            </td>

            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-1">
                <button v-if="!account.is_active && account.id !== currentUserId" @click="$emit('activate', account)" class="p-2 text-green-600 hover:text-green-700 hover:bg-green-50 rounded-xl transition-all" title="Aktifkan">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </button>
                <button @click="$emit('edit', account)" class="p-2 text-[#6B705C] hover:text-[#1B4332] hover:bg-[#FAF6F0] rounded-xl transition-all" title="Edit">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                </button>
                <button v-if="account.id !== currentUserId" @click="$emit('resetPassword', account)" class="p-2 text-[#6B705C] hover:text-amber-600 hover:bg-amber-50 rounded-xl transition-all" title="Reset Password">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                </button>
                <button v-if="account.is_active && account.id !== currentUserId" @click="$emit('delete', account)" class="p-2 text-[#6B705C] hover:text-red-500 hover:bg-red-50 rounded-xl transition-all" title="Nonaktifkan">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!loading && accounts.length > 0" class="px-6 py-3 border-t border-[#E8D5C4] bg-[#FAF6F0]/50 flex items-center justify-between">
      <span class="text-xs font-medium text-[#6B705C]">
        Total: <span class="font-bold text-[#1B4332]">{{ total }}</span> pegawai
        <span v-if="total > accounts.length" class="text-[#9CA3AF]"> | Menampilkan {{ accounts.length }}</span>
      </span>
      <div v-if="totalPages > 0" class="flex items-center gap-2">
        <button :disabled="currentPage <= 1" @click="$emit('pageChange', currentPage - 1)" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors" :class="currentPage <= 1 ? 'text-gray-300 cursor-not-allowed' : 'text-[#1B4332] hover:bg-[#1B4332]/10'">
          Prev
        </button>
        <span class="text-xs font-bold text-[#1B4332]">{{ currentPage }} / {{ totalPages }}</span>
        <button :disabled="currentPage >= totalPages" @click="$emit('pageChange', currentPage + 1)" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors" :class="currentPage >= totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-[#1B4332] hover:bg-[#1B4332]/10'">
          Next
        </button>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import type { AccountData } from '../../types/account';

defineProps<{
  accounts: AccountData[];
  loading: boolean;
  currentUserId: string;
  total: number;
  currentPage: number;
  totalPages: number;
}>();

defineEmits<{
  edit: [account: AccountData];
  delete: [account: AccountData];
  activate: [account: AccountData];
  resetPassword: [account: AccountData];
  pageChange: [page: number];
}>();

const formatRole = (role: string) => ({ super_admin: 'Super Admin', regency_admin: 'Admin Kabupaten', district_admin: 'Admin Kecamatan', village_officer: 'Petugas Desa' }[role] || role);
const roleBadge = (role: string) => ({ super_admin: 'bg-purple-50 text-purple-700 border-purple-200', regency_admin: 'bg-blue-50 text-blue-700 border-blue-200', district_admin: 'bg-orange-50 text-orange-700 border-orange-200', village_officer: 'bg-teal-50 text-teal-700 border-teal-200' }[role] || 'bg-gray-50 text-gray-700');
</script>