<template>
  <div class="fixed top-0 left-0 w-full z-50 flex justify-center p-6">
    <nav 
      :class="[
        'flex items-center justify-between shadow-xl border transition-all duration-500',
        'w-full max-w-[1100px] h-16 rounded-full px-8',
        isScrolled 
          ? 'bg-[#2D6A4F]/90 backdrop-blur-md border-white/20 shadow-[#2D6A4F]/20' 
          : 'bg-white/90 backdrop-blur-md border-[#D4A373]/30'
      ]"
    >
      <router-link to="/" class="flex items-center group">
        <span 
          :class="[
            'font-black italic tracking-tighter text-xl transition-colors',
            isScrolled ? 'text-[#E9EDC9]' : 'text-[#2D6A4F]'
          ]"
        >
          SABANA
        </span>
      </router-link>

      <div class="hidden md:flex items-center gap-8">
        <a 
          v-for="item in navItems" 
          :key="item.id" 
          @click.prevent="scrollToSection(item.id)"
          :class="[
            'text-sm font-bold transition-all duration-300 cursor-pointer relative py-1',
            activeSection === item.id 
              ? 'text-[#D4A373] scale-110' 
              : (isScrolled ? 'text-white/70 hover:text-white' : 'text-gray-600 hover:text-[#2D6A4F]')
          ]"
        >
          {{ item.name }}
          <span 
            v-if="activeSection === item.id"
            class="absolute bottom-0 left-0 w-full h-0.5 bg-[#D4A373] rounded-full"
          ></span>
        </a>
      </div>

      <router-link 
        to="/login" 
        :class="[
          'px-6 py-2 text-sm font-bold rounded-full transition-all duration-300 shadow-md active:scale-95',
          isScrolled 
            ? 'bg-[#D4A373] text-white hover:bg-white hover:text-[#2D6A4F]' 
            : 'bg-[#2D6A4F] text-white hover:bg-[#D4A373]'
        ]"
      >
        Masuk
      </router-link>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const isScrolled = ref(false);
const activeSection = ref('beranda');

const navItems = [
  { name: 'Beranda', id: 'beranda' },
  { name: 'Program', id: 'program' },
  { name: 'Panduan', id: 'panduan' },
  { name: 'Tentang', id: 'tentang' },
  { name: 'Kontak', id: 'kontak' },
];

const scrollToSection = (id: string) => {
  const element = document.getElementById(id);
  if (element) {
    activeSection.value = id;
    
    const offset = 100;
    const bodyRect = document.body.getBoundingClientRect().top;
    const elementRect = element.getBoundingClientRect().top;
    const elementPosition = elementRect - bodyRect;
    const offsetPosition = elementPosition - offset;

    window.scrollTo({
      top: offsetPosition,
      behavior: 'smooth'
    });
  }
};

const handleObserver = () => {
  const options = {
    root: null,
    rootMargin: '-150px 0px -150px 0px',
    threshold: 0.2
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        activeSection.value = entry.target.id;
      }
    });
  }, options);

  navItems.forEach((item) => {
    const el = document.getElementById(item.id);
    if (el) observer.observe(el);
  });
};

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50;
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
  setTimeout(handleObserver, 1000); 
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>