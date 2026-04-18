<script setup lang="ts">
import { ref } from 'vue';

const nik = ref('');
const pin = ref('');

const handleLogin = () => {
  console.log('NIK:', nik.value, 'PIN:', pin.value);
  // Logika panggil API Axios akan kita taruh di sini
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-[--color-bg]">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-2xl shadow-2xl border border-[--color-border]">
      <div class="text-center">
        <h2 class="text-3xl font-bold text-[--color-text-h]">Portal Sabana</h2>
        <p class="mt-2 text-[--color-text]">Masuk dengan NIK & PIN Anda</p>
      </div>
      
      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">NIK (16 Digit)</label>
          <input v-model="nik" type="text" maxlength="16" class="w-full px-4 py-2 rounded-lg border border-[--color-border] focus:ring-2 focus:ring-[--color-accent] outline-none" />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">PIN</label>
          <input v-model="pin" type="password" maxlength="6" class="w-full px-4 py-2 rounded-lg border border-[--color-border] focus:ring-2 focus:ring-[--color-accent] outline-none" />
        </div>

        <button type="submit" class="w-full py-3 px-4 bg-[--color-accent] hover:opacity-90 text-white font-bold rounded-lg transition-all">
          MASUK KE DASHBOARD
        </button>
      </form>
    </div>
  </div>
</template>