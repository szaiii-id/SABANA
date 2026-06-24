<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useDashboardStats } from '../../composables/useDashboardStats'
import type { RegencyStats, DistrictDistribution, MonthlyTrend, VerificationStatus, TopVillage } from '../../types/dashboard'
import StatCard from '../../components/dashboard/StatCard.vue'
import BarChart from '../../components/dashboard/BarChart.vue'
import LineChart from '../../components/dashboard/LineChart.vue'
import DonutChart from '../../components/dashboard/DonutChart.vue'

const props = defineProps<{ regencyId?: string }>()

const { loadRegencyStats, loadDistrictDistribution, loadMonthlyTrend, loadVerificationStatus, loadTopVillages } = useDashboardStats()

const stats = ref<RegencyStats>({
  totalPenerima: 0,
  totalDanaTersalurkan: 0,
  programAktif: 0,
  antreanVerifikasi: 0,
})

const districtDistribution = ref<DistrictDistribution[]>([])
const monthlyTrend = ref<MonthlyTrend[]>([])
const verificationStatus = ref<VerificationStatus[]>([])
const topVillages = ref<TopVillage[]>([])
const isLoading = ref(true)

const fetchData = async () => {
  isLoading.value = true
  try {
    const rid = props.regencyId || undefined

    const [s, dd, mt, vs, tv] = await Promise.all([
      loadRegencyStats(rid),
      loadDistrictDistribution(undefined, rid),
      loadMonthlyTrend(undefined, rid),
      loadVerificationStatus(rid),
      loadTopVillages(undefined, rid),
    ])
    if (s) stats.value = s
    districtDistribution.value = dd
    monthlyTrend.value = mt
    verificationStatus.value = vs
    topVillages.value = tv
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchData)
watch(() => props.regencyId, fetchData)

function formatRupiah(value: number): string {
  if (!value) return 'Rp 0'
  if (value >= 1_000_000_000) return `Rp ${(value / 1_000_000_000).toFixed(1)} M`
  if (value >= 1_000_000) return `Rp ${(value / 1_000_000).toFixed(0)} Jt`
  return `Rp ${value.toLocaleString('id-ID')}`
}
</script>

<template>
  <div class="p-6 max-w-7xl mx-auto">
    
    <div class="mb-8">
      <h2 class="text-3xl font-black text-[#1B4332] tracking-tight">Dashboard Kabupaten</h2>
      <p class="text-[#6B705C] mt-1">Ringkasan penyaluran bantuan sosial di wilayah Anda</p>
    </div>

    <div v-if="isLoading" class="text-center py-20">
      <p class="text-[#6B705C]">Memuat data...</p>
    </div>

    <template v-else>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <StatCard title="Total Penerima" :value="stats.totalPenerima" subtitle="Penerima bantuan" icon="users" variant="primary" />
        <StatCard title="Dana Tersalurkan" :value="formatRupiah(stats.totalDanaTersalurkan)" subtitle="Total dana" icon="cash" variant="white" />
        <StatCard title="Program Aktif" :value="stats.programAktif" subtitle="Program berjalan" icon="briefcase" variant="white" />
        <StatCard title="Antrean Verifikasi" :value="stats.antreanVerifikasi" subtitle="Menunggu diproses" icon="clipboard" variant="primary" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
        <div class="bg-white border border-[#E8D5C4] rounded-3xl p-6 shadow-sm">
          <h3 class="font-bold text-[#1B4332] mb-4">Penerima per Kecamatan</h3>
          <BarChart :labels="districtDistribution.map(d => d.name)" :values="districtDistribution.map(d => d.count)" label="Penerima" color="#2D6A4F" />
        </div>
        <div class="bg-white border border-[#E8D5C4] rounded-3xl p-6 shadow-sm">
          <h3 class="font-bold text-[#1B4332] mb-4">Status Verifikasi</h3>
          <DonutChart :labels="verificationStatus.map(v => v.label)" :values="verificationStatus.map(v => v.count)" :colors="verificationStatus.map(v => v.color)" />
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border border-[#E8D5C4] rounded-3xl p-6 shadow-sm">
          <h3 class="font-bold text-[#1B4332] mb-4">Tren Penyaluran 6 Bulan</h3>
          <LineChart :labels="monthlyTrend.map(m => m.month)" :values="monthlyTrend.map(m => m.amount)" label="Dana Tersalurkan" color="#D4A373" />
        </div>
        <div class="bg-white border border-[#E8D5C4] rounded-3xl p-6 shadow-sm">
          <h3 class="font-bold text-[#1B4332] mb-4">Top Desa Penerima</h3>
          <BarChart :labels="topVillages.map(v => v.name)" :values="topVillages.map(v => v.count)" label="Penerima" color="#1B4332" />
        </div>
      </div>
    </template>

  </div>
</template>