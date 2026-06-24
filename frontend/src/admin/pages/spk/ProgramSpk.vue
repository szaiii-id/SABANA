<!-- src/admin/pages/spk/ProgramSpk.vue -->
<template>
  <div class="flex flex-col h-full px-4 md:px-6 py-6">
    
    <div class="flex items-center gap-4 mb-6">
      <button @click="goBack" class="p-2.5 bg-white border border-[#E8D5C4] rounded-xl hover:bg-[#FAF6F0] transition-colors" title="Kembali">
        <svg class="w-5 h-5 text-[#1B4332]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </button>
      <div>
        <h2 class="text-2xl font-black text-[#1B4332] tracking-tight">{{ spkData?.program.name }}</h2>
        <div class="flex flex-wrap gap-3 mt-1 text-xs text-[#6B705C]">
          <span>Kuota: <strong class="text-[#1B4332]">{{ spkData?.program.quota_total ?? '-' }}</strong></span>
          <span>Alternatif: <strong class="text-[#1B4332]">{{ spkData?.total_alternatives }}</strong></span>
          <span>Kriteria: <strong class="text-[#1B4332]">{{ spkData?.criteria.length }}</strong></span>
        </div>
      </div>
    </div>

    <div v-if="isLoading" class="flex items-center justify-center py-20"><div class="flex items-center gap-2 text-[#6B705C] text-sm"><svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Memuat data SPK...</div></div>
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center"><p class="text-sm font-bold text-red-700">{{ error }}</p></div>

    <template v-else-if="spkData">

      <SpkFormulaCard class="mb-5" />

      <div class="bg-white border border-[#E8D5C4] rounded-2xl p-5 mb-5">
        <div class="flex flex-wrap gap-3 items-end">
          <div><label class="block text-[10px] font-bold text-[#6B705C] uppercase mb-1">Kabupaten</label><select v-model="filters.regency_id" @change="onRegencyChange" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs font-bold text-[#1B4332] bg-white outline-none"><option value="">Semua</option><option v-for="r in regencies" :key="r.id" :value="r.id">{{ r.name }}</option></select></div>
          <div><label class="block text-[10px] font-bold text-[#6B705C] uppercase mb-1">Kecamatan</label><select v-model="filters.district_id" @change="onDistrictChange" :disabled="!filters.regency_id" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs font-bold text-[#1B4332] bg-white outline-none disabled:opacity-50"><option value="">Semua</option><option v-for="d in districts" :key="d.id" :value="d.id">{{ d.name }}</option></select></div>
          <div><label class="block text-[10px] font-bold text-[#6B705C] uppercase mb-1">Desa</label><select v-model="filters.village_id" :disabled="!filters.district_id" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs font-bold text-[#1B4332] bg-white outline-none disabled:opacity-50"><option value="">Semua</option><option v-for="v in villages" :key="v.id" :value="v.id">{{ v.name }}</option></select></div>
          <div><label class="block text-[10px] font-bold text-[#6B705C] uppercase mb-1">Status</label><select v-model="filters.status" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs font-bold text-[#1B4332] bg-white outline-none"><option value="">Semua</option><option value="pending">Pending</option><option value="validated">Disetujui</option><option value="rejected">Ditolak</option><option value="completed">Selesai</option><option value="needs_revision">Revisi</option></select></div>
          <button @click="applyFilter" class="px-4 py-2 bg-[#1B4332] text-white text-xs font-bold rounded-lg hover:bg-[#2D6A4F] transition-colors">Terapkan</button>
        </div>
      </div>
      
      <SpkCriteriaTable :criteria="spkData.criteria" class="mb-5" />

      <div class="bg-white border border-[#E8D5C4] rounded-2xl overflow-hidden shadow-sm mb-5">
        <div class="px-8 py-5 bg-[#FAF6F0] border-b border-[#E8D5C4]">
          <div class="flex items-center justify-center">
            <template v-for="(step, idx) in steps" :key="idx">
              <div @click="currentStep = idx" :class="['flex flex-col items-center cursor-pointer', idx <= currentStep ? 'text-[#1B4332]' : 'text-[#B8A99A]']">
                <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-black transition-all', idx < currentStep ? 'bg-green-500 text-white' : idx === currentStep ? 'bg-[#1B4332] text-white ring-4 ring-[#1B4332]/20' : 'bg-[#E8D5C4] text-[#8B7355]']">{{ idx < currentStep ? '✓' : idx + 1 }}</div>
                <span class="text-[9px] font-bold uppercase mt-2 tracking-wider text-center leading-tight">{{ step }}</span>
              </div>
              <div v-if="idx < steps.length - 1" :class="['flex-1 h-1 mx-2 rounded-full transition-all', idx < currentStep ? 'bg-green-500' : 'bg-[#E8D5C4]']"></div>
            </template>
          </div>
        </div>

        <div class="p-6">
          <div v-if="currentStep === 0"><SpkDecisionMatrix :data="paginatedDecision" :criteria="spkData.criteria" /></div>
          <div v-else-if="currentStep === 1"><SpkNormalizedMatrix :data="paginatedNormalized" :criteria="spkData.criteria" /></div>
          <div v-else-if="currentStep === 2"><SpkWeightedMatrix :data="paginatedWeighted" :criteria="spkData.criteria" /></div>
          <div v-else-if="currentStep === 3"><SpkRankingTable :data="paginatedRanking" :quota="spkData.program.quota_total" /></div>
        </div>

        <div class="px-6 py-4 bg-[#FAF6F0] border-t border-[#E8D5C4] flex items-center justify-between">
          <button @click="currentStep--" :disabled="currentStep === 0" class="px-5 py-2.5 bg-white border border-[#E8D5C4] rounded-xl text-xs font-bold text-[#1B4332] hover:bg-[#1B4332] hover:text-white disabled:opacity-40 disabled:cursor-not-allowed transition-all">Sebelumnya</button>
          <div class="flex items-center gap-3">
            <button @click="currentPage--" :disabled="currentPage <= 1" class="px-3 py-1.5 text-xs font-bold rounded-lg" :class="currentPage <= 1 ? 'text-gray-300 cursor-not-allowed' : 'text-[#1B4332] hover:bg-[#1B4332]/10'">Prev</button>
            <span class="text-xs font-bold text-[#1B4332]">{{ currentPage }} / {{ totalPages }}</span>
            <button @click="currentPage++" :disabled="currentPage >= totalPages" class="px-3 py-1.5 text-xs font-bold rounded-lg" :class="currentPage >= totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-[#1B4332] hover:bg-[#1B4332]/10'">Next</button>
          </div>
          <button @click="currentStep++" :disabled="currentStep === steps.length - 1" class="px-5 py-2.5 bg-[#1B4332] text-white rounded-xl text-xs font-bold hover:bg-[#2D6A4F] disabled:opacity-40 disabled:cursor-not-allowed transition-all">Berikutnya</button>
        </div>
      </div>

      <SpkSummaryCards :summary="spkData.summary" />

    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSpk } from '../../composables/useSpk'
import { useRegion } from '../../composables/useRegion'
import SpkCriteriaTable from '../../components/spk/SpkCriteriaTable.vue'
import SpkDecisionMatrix from '../../components/spk/SpkDecisionMatrix.vue'
import SpkNormalizedMatrix from '../../components/spk/SpkNormalizedMatrix.vue'
import SpkWeightedMatrix from '../../components/spk/SpkWeightedMatrix.vue'
import SpkRankingTable from '../../components/spk/SpkRankingTable.vue'
import SpkSummaryCards from '../../components/spk/SpkSummaryCards.vue'
import SpkFormulaCard from '../../components/spk/SpkFormulaCard.vue'

const route = useRoute(); const router = useRouter()
const { spkData, isLoading, error, fetchSpkData } = useSpk()
const { regencies, districts, villages, fetchRegencies, fetchDistricts, fetchVillages } = useRegion()

const programId = route.params.programId as string
const steps = ['Keputusan', 'Normalisasi', 'Terbobot', 'Ranking']
const currentStep = ref(0); const currentPage = ref(1); const perPage = 20

const filters = reactive({ regency_id: '', district_id: '', village_id: '', status: '' })
const goBack = () => router.push({ name: 'admin.spk' })

const onRegencyChange = async () => { filters.district_id = ''; filters.village_id = ''; districts.value = []; villages.value = []; if (filters.regency_id) await fetchDistricts(filters.regency_id) }
const onDistrictChange = async () => { filters.village_id = ''; villages.value = []; if (filters.district_id) await fetchVillages(filters.district_id) }
const applyFilter = () => { const p: Record<string, string> = {}; if (filters.village_id) p.village_id = filters.village_id; else if (filters.district_id) p.district_id = filters.district_id; else if (filters.regency_id) p.regency_id = filters.regency_id; if (filters.status) p.status = filters.status; currentPage.value = 1; fetchSpkData(programId, p) }

const paginate = <T>(d: T[]): T[] => d.slice((currentPage.value - 1) * perPage, currentPage.value * perPage)
const paginatedDecision = computed(() => paginate(spkData.value?.decision_matrix ?? []))
const paginatedNormalized = computed(() => paginate(spkData.value?.normalized_matrix ?? []))
const paginatedWeighted = computed(() => paginate(spkData.value?.weighted_matrix ?? []))
const paginatedRanking = computed(() => paginate(spkData.value?.ranking ?? []))

const totalItems = computed(() => { if (!spkData.value) return 0; if (currentStep.value === 0) return spkData.value.decision_matrix.length; if (currentStep.value === 1) return spkData.value.normalized_matrix.length; if (currentStep.value === 2) return spkData.value.weighted_matrix.length; if (currentStep.value === 3) return spkData.value.ranking.length; return 0 })
const totalPages = computed(() => Math.ceil(totalItems.value / perPage) || 1)
watch(currentStep, () => { currentPage.value = 1 })

onMounted(async () => { await fetchRegencies(); await fetchSpkData(programId) })
</script>