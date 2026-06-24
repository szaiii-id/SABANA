<script setup lang="ts">
import { onMounted } from 'vue'
import { useReportFilter } from '../../composables/useReportFilter'
import { useReportDownload } from '../../composables/useReportDownload'
import { REPORT_LIST } from '../../types/reportAdmin'
import { adminReportService } from '../../services/adminReportService'
import ReportFilterBar from '../../components/reports/ReportFilterBar.vue'
import ReportCard from '../../components/reports/ReportCard.vue'

const {
  filters, programs, regencies, districts, villages,
  selectedRegencyId, selectedDistrictId, currentRole,
  hasFilter, filterLabel, resetFilters,
  loadPrograms, initWilayah
} = useReportFilter()

const { isDownloading, currentReport, download } = useReportDownload()

onMounted(() => {
  loadPrograms()
  initWilayah()
})

type ReportMethod = Exclude<keyof typeof adminReportService, 'fetchActivePrograms'>

const reportMethodMap: Record<string, ReportMethod> = {
  'budget-summary': 'budgetSummary',
  'program-recipients': 'programRecipients',
  'most-applied-programs': 'mostAppliedPrograms',
  'citizen-registered-by-admin': 'citizenRegisteredByAdmin',
  'ready-for-disbursement': 'readyForDisbursement',
  'pending-evaluation': 'pendingEvaluation',
  'revoked-recipients': 'revokedRecipients',
  'approved-recipients': 'approvedRecipients',
  'disbursed-recipients': 'disbursedRecipients',
}

function handleDownload(_endpoint: string, reportId: string): void {
  const reportType = reportMethodMap[reportId]
  if (!reportType) {
    console.error('Report type tidak ditemukan:', reportId)
    return
  }
  download(reportType, filters.value)
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-[#FAF6F0] via-white to-[#F0F7F4]">
    <div class="p-6 max-w-7xl mx-auto">
      
      <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-[#1B4332] tracking-tight">📊 Laporan</h1>
        <p class="text-[#6B705C] mt-2 text-sm">Cetak laporan bantuan sosial dalam format PDF siap arsip</p>
      </div>

      <ReportFilterBar
        :filters="filters"
        :programs="programs"
        :regencies="regencies"
        :districts="districts"
        :villages="villages"
        :selected-regency-id="selectedRegencyId"
        :selected-district-id="selectedDistrictId"
        :current-role="currentRole"
        :has-filter="hasFilter"
        :filter-label="filterLabel"
        @update:filters="filters = $event"
        @update:selected-regency-id="selectedRegencyId = $event"
        @update:selected-district-id="selectedDistrictId = $event"
        @reset="resetFilters"
      />

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mt-8">
        <ReportCard
          v-for="report in REPORT_LIST"
          :key="report.id"
          :report="report"
          :is-downloading="isDownloading && currentReport === reportMethodMap[report.id]"
          @download="handleDownload(report.endpoint, report.id)"
        />
      </div>

      <div v-if="REPORT_LIST.length === 0" class="text-center py-20">
        <p class="text-[#6B705C] text-lg">Belum ada laporan tersedia</p>
      </div>

    </div>
  </div>
</template>