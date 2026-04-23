<template>
  <header class="bg-white/80 backdrop-blur-2xl border border-white/60 rounded-[2rem] px-8 py-5 flex justify-between items-center shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
    
    <div class="flex items-baseline flex-wrap gap-x-2 gap-y-1 pr-4">
      <span class="text-sm font-bold text-gray-500 tracking-wide">{{ timeGreeting }},</span>
      <h2 class="text-base font-[1000] text-gray-900 tracking-tight uppercase">{{ firstName }}!</h2>
      <span class="text-sm font-medium text-[#D4A373] tracking-wide ml-1">{{ interactiveMessage }}</span>
    </div>
    
    <button 
      :disabled="loading"
      @click="$emit('logout')"
      class="shrink-0 whitespace-nowrap px-8 py-3.5 bg-gradient-to-b from-white to-gray-50 text-gray-600 rounded-2xl font-black text-[11px] uppercase tracking-widest transition-all duration-300 border border-gray-200/80 shadow-[0_4px_15px_rgba(0,0,0,0.03)] hover:from-red-50 hover:to-red-100 hover:text-red-600 hover:border-red-200 hover:shadow-[0_8px_20px_rgba(220,38,38,0.12)] disabled:opacity-50"
    >
      {{ loading ? 'MEMPROSES...' : 'KELUAR' }}
    </button>
    
  </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

defineProps<{ loading?: boolean }>();
defineEmits(['logout']);

const firstName = ref('');

// 1. Menentukan Waktu
const timeGreeting = computed(() => {
  const hour = new Date().getHours();
  if (hour >= 5 && hour < 11) return 'Selamat pagi';
  if (hour >= 11 && hour < 15) return 'Selamat siang';
  if (hour >= 15 && hour < 18) return 'Selamat sore';
  return 'Selamat malam';
});

// 2. Pesan Interaktif yang Berubah-ubah (Acak)
const interactiveMessage = computed(() => {
  const hour = new Date().getHours();
  let timeKey: 'pagi' | 'siang' | 'sore' | 'malam' = 'malam';
  
  if (hour >= 5 && hour < 11) timeKey = 'pagi';
  else if (hour >= 11 && hour < 15) timeKey = 'siang';
  else if (hour >= 15 && hour < 18) timeKey = 'sore';

  const messages = {
    pagi: [
      'Ada yang bisa kami bantu pagi ini?',
      'Sudah siap mengecek status bantuanmu hari ini?',
      'Semoga hari ini penuh dengan kabar baik.'
    ],
    siang: [
      'Jangan lupa istirahat dan makan siang, ya.',
      'Semoga urusanmu dilancarkan siang ini.',
      'Yuk, cek update terbaru pengajuanmu.'
    ],
    sore: [
      'Waktunya bersantai sejenak setelah beraktivitas.',
      'Masih semangat kan? Mari lihat progres bantuanmu.',
      'Semoga harimu menyenangkan sampai sore ini.'
    ],
    malam: [
      'Selamat beristirahat bersama keluarga tercinta.',
      'Ada yang mau dicek sebelum tidur?',
      'Kami siap menjaga data pengajuanmu malam ini.'
    ]
  };

  const dailyMessages = messages[timeKey];
  const randomIndex = Math.floor(Math.random() * dailyMessages.length);
  return dailyMessages[randomIndex];
});

onMounted(() => {
  const userData = localStorage.getItem('user');
  if (userData) {
    try {
      const parsedUser = JSON.parse(userData);
      const fullName = parsedUser.full_name || parsedUser.name || 'Warga';
      
      // Mengambil kata pertama dari nama agar terasa lebih personal dan akrab
      // Misal: "AKHMAD JAINUDIN" menjadi "AKHMAD"
      firstName.value = fullName.split(' ')[0]; 
    } catch (error) {
      firstName.value = 'Warga';
    }
  } else {
    firstName.value = 'Warga';
  }
});
</script>