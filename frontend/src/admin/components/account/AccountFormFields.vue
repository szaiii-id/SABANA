<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    <!-- NIP Field -->
    <div>
      <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">
        NIP (18 Digit)
        <span v-if="mode === 'add'" class="text-red-400">*</span>
      </label>
      <div class="relative group">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
          <svg 
            class="h-5 w-5 transition-colors" 
            :class="errors.nip ? 'text-[#DC2626]' : 'text-[#9CA3AF] group-focus-within:text-[#1B4332]'" 
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4z" />
          </svg>
        </div>
        <input 
          v-model="form.nip" 
          @input="validateNip"
          type="text" 
          inputmode="numeric" 
          maxlength="18"
          :disabled="mode === 'edit'"
          placeholder="198501012010011001"
          :class="[
            'w-full pl-12 pr-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm',
            mode === 'edit' ? 'bg-gray-50 cursor-not-allowed text-gray-500' : 'bg-white',
            errors.nip ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:border-[#DC2626] focus:ring-4 focus:ring-[#DC2626]/10' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10'
          ]"
          required
        >
      </div>
      <p v-if="errors.nip" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1 flex items-center gap-1">
        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ errors.nip }}
      </p>
      <p v-else-if="mode === 'edit'" class="text-[10px] font-medium text-amber-600 mt-1.5 ml-1 flex items-center gap-1">
        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        NIP tidak dapat diubah karena bersifat permanen.
      </p>
      <p v-else class="text-[10px] font-medium text-[#6B705C] mt-1.5 ml-1">18 digit NIP pegawai</p>
    </div>

    <!-- Name Field -->
    <div>
      <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">
        Nama Lengkap
        <span class="text-red-400">*</span>
      </label>
      <div class="relative group">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
          <svg 
            class="h-5 w-5 transition-colors" 
            :class="errors.name ? 'text-[#DC2626]' : 'text-[#9CA3AF] group-focus-within:text-[#1B4332]'" 
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
        <input 
          v-model="form.name" 
          @input="validateName"
          type="text" 
          placeholder="Nama lengkap pegawai"
          :class="[
            'w-full pl-12 pr-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm bg-white',
            errors.name ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:border-[#DC2626] focus:ring-4 focus:ring-[#DC2626]/10' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10'
          ]"
          required
        >
      </div>
      <p v-if="errors.name" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1 flex items-center gap-1">
        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ errors.name }}
      </p>
    </div>

    <!-- Role Field -->
    <div>
      <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">
        Level Akses
        <span class="text-red-400">*</span>
      </label>
      <select 
        v-model="form.role" 
        :disabled="mode === 'edit'" 
        @change="$emit('roleChange')"
        class="w-full pl-4 pr-10 py-3.5 rounded-xl border-2 border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10 outline-none transition-all font-medium text-sm appearance-none bg-white disabled:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-500 bg-[url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%238B5E3C%22%3E%3Cpath stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 9l-7 7-7-7%22/%3E%3C/svg%3E')] bg-[length:1.2rem] bg-[right_1rem_center] bg-no-repeat"
        required
      >
        <option value="regency_admin">Admin Kabupaten</option>
        <option value="district_admin">Admin Kecamatan</option>
        <option value="village_officer">Petugas Desa</option>
      </select>
      <p v-if="mode === 'edit'" class="text-[10px] font-medium text-amber-600 mt-1.5 ml-1 flex items-center gap-1">
        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Role tidak dapat diubah demi keamanan.
      </p>
    </div>
  </div>

  <!-- Password & Status Row -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <!-- Password Field -->
    <div>
      <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">
        Kata Sandi
        <span v-if="mode === 'add'" class="text-red-400">*</span>
      </label>
      <div class="relative group">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
          <svg 
            class="h-5 w-5 transition-colors" 
            :class="errors.password ? 'text-[#DC2626]' : 'text-[#9CA3AF] group-focus-within:text-[#D4A373]'" 
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <input 
          v-model="form.password" 
          :type="showPassword ? 'text' : 'password'"
          @input="validatePassword"
          :placeholder="mode === 'edit' ? 'Kosongkan jika tidak ingin mengubah' : 'Minimal 8 karakter'"
          :class="[
            'w-full pl-12 pr-12 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm bg-white',
            errors.password ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:border-[#DC2626] focus:ring-4 focus:ring-[#DC2626]/10' : 'border-[#E8D5C4] focus:border-[#D4A373] focus:ring-4 focus:ring-[#D4A373]/10'
          ]"
          :required="mode === 'add'"
        >
        <button 
          type="button" 
          @click="showPassword = !showPassword" 
          class="absolute inset-y-0 right-0 pr-4 flex items-center text-[#9CA3AF] hover:text-[#6B705C] transition-colors"
          tabindex="-1"
          aria-label="Toggle password visibility"
        >
          <!-- Eye Icon (Show) -->
          <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          <!-- Eye Off Icon (Hide) -->
          <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
          </svg>
        </button>
      </div>
      <p v-if="errors.password" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1 flex items-center gap-1">
        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ errors.password }}
      </p>
      <p v-else class="text-[10px] font-medium text-[#6B705C] mt-1.5 ml-1">
        {{ mode === 'edit' ? 'Biarkan kosong jika tidak ingin mengubah' : 'Minimal 8 karakter' }}
      </p>
    </div>

    <!-- Status Akun (Readonly Badge, bukan Toggle) -->
    <div v-if="mode === 'edit'">
      <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Status Akun</label>
      <div class="flex items-center gap-3 h-[50px]">
        <!-- Badge Status -->
        <div 
          :class="form.is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'" 
          class="px-4 py-2 rounded-xl text-xs font-bold border flex items-center gap-2"
        >
          <div :class="form.is_active ? 'bg-green-500 shadow-green-500/30' : 'bg-red-400 shadow-red-400/30'" class="w-2 h-2 rounded-full shadow-sm"></div>
          {{ form.is_active ? 'Akun Aktif' : 'Akun Nonaktif' }}
        </div>
        <!-- Helper Text -->
        <p class="text-[10px] text-[#6B705C]">
          {{ form.is_active ? 'Gunakan tombol Nonaktifkan di tabel' : 'Gunakan tombol Aktifkan di tabel' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { AccountPayload } from '../../types/account';

const props = defineProps<{ 
  form: AccountPayload; 
  mode: 'add' | 'edit'; 
  errors: Record<string, string> 
}>();

defineEmits<{ roleChange: [] }>();

const showPassword = ref(false);

const validateNip = () => {
  props.errors.nip = '';
  const nip = props.form.nip?.replace(/\D/g, '') || '';
  props.form.nip = nip;
  
  if (props.mode === 'add') {
    if (!nip) {
      props.errors.nip = 'NIP wajib diisi.';
    } else if (nip.length !== 18) {
      props.errors.nip = 'NIP harus tepat 18 digit.';
    }
  }
};

const validateName = () => {
  props.errors.name = '';
  if (!props.form.name?.trim()) {
    props.errors.name = 'Nama lengkap wajib diisi.';
  }
};

const validatePassword = () => {
  props.errors.password = '';
  
  if (props.mode === 'add') {
    // Add mode: password wajib
    if (!props.form.password) {
      props.errors.password = 'Kata sandi wajib diisi.';
    } else if (props.form.password.length < 8) {
      props.errors.password = 'Kata sandi minimal 8 karakter.';
    }
  } else {
    // Edit mode: password opsional, tapi kalau diisi harus >= 8
    if (props.form.password && props.form.password.length > 0 && props.form.password.length < 8) {
      props.errors.password = 'Kata sandi minimal 8 karakter.';
    }
  }
};
</script>