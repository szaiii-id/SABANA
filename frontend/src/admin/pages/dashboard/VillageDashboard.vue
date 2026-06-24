<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useDashboardStats } from '../../composables/useDashboardStats'
import type { VillageStats, RecentCitizen, VerificationStatus } from '../../types/dashboard'
import StatCard from '../../components/dashboard/StatCard.vue'
import DonutChart from '../../components/dashboard/DonutChart.vue'

const props = defineProps<{ villageId?: string; districtId?: string }>()

const { loadVillageStats, loadRecentCitizens, loadDistrictVerificationStatus, districtId } = useDashboardStats()

const stats = ref<VillageStats>({
  totalWarga: 0,
  pengajuanBulanIni: 0,
  disetujui: 0,
  disalurkan: 0,
})

const recentCitizens = ref<RecentCitizen[]>([])
const verificationStatus = ref<VerificationStatus[]>([])
const isLoading = ref(true)

const fetchData = async () => {
  isLoading.value = true
  try {
    const vid = props.villageId || undefined
    const did = props.districtId || districtId.value || undefined

    const [s, rc, vs] = await Promise.all([
      loadVillageStats(vid),
      loadRecentCitizens(vid),
      loadDistrictVerificationStatus(did),
    ])
    if (s) stats.value = s
    recentCitizens.value = rc
    verificationStatus.value = vs
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchData)
watch(() => [props.villageId, props.districtId], fetchData)
</script>

<template>
  <div class="p-6 max-w-7xl mx-auto">
    
    <div class="mb-8">
      <h2 class="text-3xl font-black text-[#1B4332] tracking-tight">Dashboard Desa</h2>
      <p class="text-[#6B705C] mt-1">Ringkasan data warga dan pengajuan di desa Anda</p>
    </div>

    <div v-if="isLoading" class="text-center py-20">
      <p class="text-[#6B705C]">Memuat data...</p>
    </div>

    <template v-else>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <StatCard title="Warga Terdaftar" :value="stats.totalWarga" subtitle="Total warga" icon="users" variant="primary" />
        <StatCard title="Pengajuan Bulan Ini" :value="stats.pengajuanBulanIni" subtitle="Bulan berjalan" icon="document" variant="white" />
        <StatCard title="Disetujui" :value="stats.disetujui" subtitle="Pengajuan disetujui" icon="check" variant="white" />
        <StatCard title="Disalurkan" :value="stats.disalurkan" subtitle="Bantuan tersalurkan" icon="cash" variant="primary" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border border-[#E8D5C4] rounded-3xl p-6 shadow-sm">
          <h3 class="font-bold text-[#1B4332] mb-4">Status Verifikasi Kecamatan</h3>
          <DonutChart :labels="verificationStatus.map(v => v.label)" :values="verificationStatus.map(v => v.count)" :colors="verificationStatus.map(v => v.color)" />
        </div>

        <div class="bg-white border border-[#E8D5C4] rounded-3xl p-6 shadow-sm">
          <h3 class="font-bold text-[#1B4332] mb-4">5 Warga Terbaru</h3>
          <div v-if="recentCitizens.length === 0" class="text-center py-8">
            <p class="text-[#6B705C] text-sm">Belum ada warga terdaftar</p>
          </div>
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="border-b border-[#E8D5C4]">
                <th class="text-left py-2 text-[10px] uppercase text-[#6B705C] font-bold">NIK</th>
                <th class="text-left py-2 text-[10px] uppercase text-[#6B705C] font-bold">Nama</th>
                <th class="text-right py-2 text-[10px] uppercase text-[#6B705C] font-bold">Tanggal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="citizen in recentCitizens" :key="citizen.nik" class="border-b border-[#F0E8DA]">
                <td class="py-2.5 text-[#1B4332] font-medium">{{ citizen.nik }}</td>
                <td class="py-2.5 text-[#1B4332] font-medium">{{ citizen.full_name }}</td>
                <td class="py-2.5 text-[#6B705C] text-right">{{ citizen.created_at }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

  </div>
</template>