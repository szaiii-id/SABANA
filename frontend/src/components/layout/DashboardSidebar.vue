<template>
  <aside class="flex flex-col gap-4 py-2">
    <nav class="flex flex-col gap-2">
      <router-link 
        v-for="item in menuItems" 
        :key="item.path"
        :to="item.path"
        class="flex items-center gap-4 px-6 py-4 rounded-[2rem] font-bold text-xs uppercase tracking-wider transition-all duration-300 group"
        :class="isActive(item.path) ? 'bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] text-white shadow-lg shadow-[#2D6A4F]/20 scale-[1.02]' : 'text-gray-600 hover:bg-white/90 hover:text-[#2D6A4F]'"
      >
        <component :is="item.icon" class="w-6 h-6 transition-transform group-hover:scale-110" />
        {{ item.name }}
      </router-link>
    </nav>
  </aside>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router';
import { 
  Squares2X2Icon, 
  DocumentPlusIcon, 
  ClipboardDocumentListIcon, // Icon baru
  UserCircleIcon, 
  ChatBubbleBottomCenterTextIcon,
  ShieldCheckIcon 
} from '@heroicons/vue/24/outline';

const route = useRoute();
const isActive = (path: string) => route.path === path;

const menuItems = [
  { name: 'Beranda', path: '/dashboard', icon: Squares2X2Icon },
  { name: 'Daftar Bantuan', path: '/assistance', icon: DocumentPlusIcon },
  { name: 'Riwayat Bantuan', path: '/history', icon: ClipboardDocumentListIcon }, // Tambahkan ini
  { name: 'Lapor Warga', path: '/report', icon: ChatBubbleBottomCenterTextIcon },
  { name: 'Data Saya', path: '/profile', icon: UserCircleIcon },
  { name: 'Keamanan PIN', path: '/security', icon: ShieldCheckIcon },
];
</script>