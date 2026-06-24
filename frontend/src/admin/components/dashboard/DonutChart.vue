<script setup lang="ts">
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps<{
  labels: string[]
  values: number[]
  colors: string[]
}>()

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [
    {
      data: props.values,
      backgroundColor: props.colors,
      borderWidth: 0,
    },
  ],
}))

const options = {
  responsive: true,
  plugins: {
    legend: { position: 'bottom' as const },
  },
}
</script>

<template>
  <Doughnut :data="chartData" :options="options" />
</template>