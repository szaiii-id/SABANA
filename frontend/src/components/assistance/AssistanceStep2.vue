<script setup lang="ts">
import { reactive } from 'vue';
import type { AssistanceProgramSchema, AssistanceSubmissionPayload, Region } from '../../types/assistance';
import { MapPinIcon, CreditCardIcon, IdentificationIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
  formData: AssistanceSubmissionPayload;
  selectedProgram: AssistanceProgramSchema;
  regencies: Region[];
  districts: Region[];
  villages: Region[];
}>();

const emit = defineEmits(['next', 'prev', 'regencyChange', 'districtChange', 'errorMsg']);

const fieldErrors = reactive<Record<string, string>>({});

const validateAndNext = () => {
  Object.keys(fieldErrors).forEach(key => delete fieldErrors[key]);
  let isValid = true;

  if (!props.formData.regency_id) { fieldErrors.regency_id = 'Kabupaten wajib dipilih'; isValid = false; }
  if (!props.formData.district_id) { fieldErrors.district_id = 'Kecamatan wajib dipilih'; isValid = false; }
  if (!props.formData.village_id) { fieldErrors.village_id = 'Desa/Kelurahan wajib dipilih'; isValid = false; }

  if (props.formData.disbursement_method === 'bpd_transfer' && !props.formData.bank_account_number) {
    fieldErrors.bank_account_number = 'Nomor Rekening wajib diisi untuk transfer BPD'; 
    isValid = false;
  }

  props.selectedProgram.inputs.forEach(input => {
    if (!props.formData.dynamicInputs[input.key]) {
      fieldErrors[input.key] = `${input.label} wajib diisi`;
      isValid = false;
    }
  });

  if (isValid) {
    emit('next');
  } else {
    emit('errorMsg', 'Mohon lengkapi data pendaftaran sebelum melanjutkan.');
  }
};
</script>

<template>
  <div class="animate-fade-in">
    <div class="mb-10 flex items-center gap-5">
      <div class="w-14 h-14 bg-[#2D6A4F] text-white rounded-3xl flex items-center justify-center shadow-lg shadow-green-900/20">
        <MapPinIcon class="w-7 h-7" />
      </div>
      <div>
        <h3 class="text-2xl font-black text-[#4A3728] tracking-tight uppercase leading-none">Domisili</h3>
        <p class="text-[#2D6A4F] text-[10px] font-black uppercase tracking-[0.3em] mt-1 italic">Data Lokasi & Informasi Banua</p>
      </div>
    </div>

    <form @submit.prevent="validateAndNext" class="space-y-10" novalidate>
      
      <div class="bg-[#FDF8F4] p-10 rounded-[3.5rem] border-2 border-[#F3E5D8] shadow-sm relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <MapPinIcon class="w-24 h-24 text-[#8B5E3C]" />
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative z-10">
          
          <div class="space-y-3">
            <label class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-[0.2em] px-2">Kabupaten/Kota</label>
            <select v-model="formData.regency_id" @change="emit('regencyChange')"
              :key="regencies.length"
              :class="['w-full p-4 rounded-2xl border-2 bg-white transition-all outline-none focus:ring-8 focus:ring-[#2D6A4F]/5 custom-select', 
              fieldErrors.regency_id ? 'border-red-200' : 'border-transparent shadow-md focus:border-[#2D6A4F] text-[#4A3728] font-bold']">
              <option value="">Pilih Kabupaten</option>
              <option v-for="opt in regencies" :key="opt.id" :value="String(opt.id)">{{ opt.name }}</option>
            </select>
            <p v-if="fieldErrors.regency_id" class="text-[9px] font-bold text-red-500 px-2 italic uppercase tracking-tighter">{{ fieldErrors.regency_id }}</p>
          </div>

          <div class="space-y-3">
            <label class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-[0.2em] px-2">Kecamatan</label>
            <select v-model="formData.district_id" @change="emit('districtChange')"
              :key="districts.length"
              :class="['w-full p-4 rounded-2xl border-2 bg-white transition-all outline-none focus:ring-8 focus:ring-[#2D6A4F]/5 custom-select', 
              fieldErrors.district_id ? 'border-red-200' : 'border-transparent shadow-md focus:border-[#2D6A4F] text-[#4A3728] font-bold']">
              <option value="">Pilih Kecamatan</option>
              <option v-for="opt in districts" :key="opt.id" :value="String(opt.id)">{{ opt.name }}</option>
            </select>
            <p v-if="fieldErrors.district_id" class="text-[9px] font-bold text-red-500 px-2 italic uppercase tracking-tighter">{{ fieldErrors.district_id }}</p>
          </div>

          <div class="space-y-3">
            <label class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-[0.2em] px-2">Desa/Kelurahan</label>
            <select v-model="formData.village_id"
              :key="villages.length"
              :class="['w-full p-4 rounded-2xl border-2 bg-white transition-all outline-none focus:ring-8 focus:ring-[#2D6A4F]/5 custom-select', 
              fieldErrors.village_id ? 'border-red-200' : 'border-transparent shadow-md focus:border-[#2D6A4F] text-[#4A3728] font-bold']">
              <option value="">Pilih Desa</option>
              <option v-for="opt in villages" :key="opt.id" :value="String(opt.id)">{{ opt.name }}</option>
            </select>
            <p v-if="fieldErrors.village_id" class="text-[9px] font-bold text-red-500 px-2 italic uppercase tracking-tighter">{{ fieldErrors.village_id }}</p>
          </div>

        </div>
      </div>

      <div class="space-y-6">
        <div class="flex items-center gap-2 px-4">
            <IdentificationIcon class="w-4 h-4 text-[#2D6A4F]" />
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Data Pendukung Program</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div v-for="input in selectedProgram.inputs" :key="input.key" class="space-y-3 group">
            <label class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-[0.2em] px-4 group-hover:text-[#2D6A4F] transition-colors">{{ input.label }}</label>
            <input :type="input.type" v-model="formData.dynamicInputs[input.key]" @input="fieldErrors[input.key] = ''"
                   :placeholder="`Masukkan ${input.label}...`"
                   :class="['w-full px-8 py-5 bg-white border-2 border-[#F3E5D8] rounded-[2rem] shadow-sm outline-none transition-all focus:border-[#2D6A4F] text-[#4A3728] font-bold']" />
            <p v-if="fieldErrors[input.key]" class="text-[10px] font-bold text-red-500 px-4 italic tracking-wider uppercase">{{ fieldErrors[input.key] }}</p>
          </div>
        </div>
      </div>

      <div class="bg-[#2D6A4F] rounded-[4rem] p-10 md:p-14 text-white shadow-2xl shadow-green-900/30">
        <div class="text-center mb-12">
            <div class="inline-flex p-3 bg-white/10 rounded-2xl mb-4"><CreditCardIcon class="w-6 h-6 text-green-100" /></div>
            <h4 class="font-black text-xs tracking-[0.4em] uppercase text-green-50">Metode Penyaluran Banua</h4>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <label v-for="method in ['village_cash', 'bpd_transfer']" :key="method"
            :class="['p-8 rounded-[2.5rem] cursor-pointer border-2 transition-all flex flex-col items-center gap-3 text-center group', 
            formData.disbursement_method === method ? 'border-white bg-white text-[#2D6A4F] shadow-xl scale-105' : 'border-white/20 bg-white/5 text-white opacity-60 hover:opacity-100']">
            
            <input type="radio" v-model="formData.disbursement_method" :value="method" class="hidden" @change="fieldErrors.bank_account_number = ''" />
            
            <span class="text-[11px] font-black uppercase tracking-[0.3em]">{{ method === 'village_cash' ? 'Tunai' : 'Transfer' }}</span>
            <span class="text-[9px] font-bold opacity-70 italic">{{ method === 'village_cash' ? 'KANTOR DESA / LURAH' : 'REKENING BPD KALSEL' }}</span>
            <div v-if="formData.disbursement_method === method" class="mt-2 w-5 h-5 bg-[#2D6A4F] text-white rounded-full flex items-center justify-center text-[10px] shadow-lg shadow-green-900/20">✓</div>
          </label>
        </div>

        <div v-if="formData.disbursement_method === 'bpd_transfer'" class="mt-10 animate-fade-in">
          <div class="space-y-4">
              <input 
                type="text" 
                v-model="formData.bank_account_number" 
                @input="fieldErrors.bank_account_number = ''"
                placeholder="000-00-00-00000-0"
                :class="['w-full px-8 py-6 bg-white/10 border-2 rounded-[2rem] text-white placeholder-white/30 outline-none transition-all font-mono font-black text-center text-xl tracking-[0.3em]', 
                fieldErrors.bank_account_number ? 'border-red-400 focus:border-red-400 shadow-[0_0_20px_rgba(248,113,113,0.2)]' : 'border-white/20 focus:border-white']" 
              />
              <p v-if="fieldErrors.bank_account_number" class="text-[10px] font-black text-red-300 text-center uppercase tracking-widest animate-pulse italic">
                ⚠ {{ fieldErrors.bank_account_number }}
              </p>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between pt-12 border-t-2 border-[#F3E5D8]/50">
        <button type="button" @click="emit('prev')" class="text-[#8B5E3C] font-black text-[10px] tracking-[0.3em] uppercase hover:text-[#2D6A4F] transition-all">Kembali</button>
        <button type="submit" class="px-16 py-6 bg-[#2D6A4F] text-white font-black text-[11px] uppercase tracking-[0.5em] rounded-[2.5rem] shadow-2xl shadow-green-900/20 hover:scale-105 active:scale-95 transition-all">
          Lanjutkan
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Menghilangkan panah bawaan & memasang panah kustom */
.custom-select {
  -webkit-appearance: none !important;
  -moz-appearance: none !important;
  appearance: none !important;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%238B5E3C'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1.5rem center;
  background-size: 1.2rem;
  padding-right: 3.5rem;
}
</style>