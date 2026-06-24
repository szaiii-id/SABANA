<script setup lang="ts">
defineProps<{
  title: string
  value: string | number
  subtitle: string
  icon: string
  variant: 'primary' | 'white'
}>()
</script>

<template>
  <div
    :class="[
      'rounded-3xl p-7 shadow-sm hover:scale-[1.02] transition-all duration-300 group',
      variant === 'primary'
        ? 'bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] text-white shadow-xl shadow-[#1B4332]/15'
        : 'bg-white border-2 border-[#E8D5C4]'
    ]"
  >
    <p
      :class="[
        'font-bold text-xs uppercase tracking-widest mb-2',
        variant === 'primary' ? 'text-[#D4A373]' : 'text-[#D4A373]'
      ]"
    >
      {{ title }}
    </p>
    <h3 class="text-5xl font-black tracking-tight">{{ value }}</h3>
    <p
      :class="[
        'text-xs mt-2 font-medium',
        variant === 'primary' ? 'text-white/60' : 'text-[#6B705C]'
      ]"
    >
      {{ subtitle }}
    </p>
  </div>
</template>