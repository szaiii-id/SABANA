<template>
  <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-slate-200/60">
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
      
      <select v-model="filters.module" @change="apply" class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-[13px] text-slate-700 outline-none focus:border-[#1B4332]">
        <option value="">Semua Modul</option>
        <option value="auth">Auth</option>
        <option value="program">Program</option>
        <option value="account">Akun</option>
        <option value="verification">Verifikasi</option>
        <option value="disbursement">Penyaluran</option>
        <option value="evaluation">Evaluasi</option>
        <option value="registration">Pendaftaran</option>
        <option value="submission">Pengajuan</option>
      </select>

      <select v-model="filters.actor_role" @change="apply" class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-[13px] text-slate-700 outline-none focus:border-[#1B4332]">
        <option value="">Semua Role</option>
        <option value="super_admin">Super Admin</option>
        <option value="regency_admin">Admin Kabupaten</option>
        <option value="district_admin">Admin Kecamatan</option>
        <option value="village_officer">Petugas Desa</option>
        <option value="citizen">Warga</option>
        <option value="system">Sistem</option>
      </select>

      <input v-model="filters.actor_name" @input="debounceApply" placeholder="Cari nama..." class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-[13px] text-slate-700 outline-none focus:border-[#1B4332]" />

      <input type="date" v-model="filters.from" @change="apply" class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-[13px] text-slate-700 outline-none focus:border-[#1B4332]" />

      <input type="date" v-model="filters.to" @change="apply" class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-[13px] text-slate-700 outline-none focus:border-[#1B4332]" />

    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, onBeforeUnmount } from 'vue';

const emit = defineEmits<{ filter: [filters: Record<string, string>] }>();

const filters = reactive<Record<string, string>>({
  module: '',
  actor_role: '',
  actor_name: '',
  from: '',
  to: '',
});

let timeout: ReturnType<typeof setTimeout>;

const apply = () => {
  const active: Record<string, string> = {};
  Object.entries(filters).forEach(([k, v]) => { if (v) active[k] = v; });
  emit('filter', active);
};

const debounceApply = () => {
  clearTimeout(timeout);
  timeout = setTimeout(apply, 400);
};

onBeforeUnmount(() => {
  clearTimeout(timeout);
});
</script>