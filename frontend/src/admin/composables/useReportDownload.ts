import { ref } from 'vue'
import type { ReportFilter } from '../types/reportAdmin'
import { adminReportService } from '../services/adminReportService'

type ReportType = Exclude<keyof typeof adminReportService, 'fetchActivePrograms'>

export function useReportDownload() {
  const isDownloading = ref(false)
  const currentReport = ref<ReportType | null>(null)

  async function download(reportType: ReportType, filters: ReportFilter): Promise<void> {
    isDownloading.value = true
    currentReport.value = reportType

    try {
      await adminReportService[reportType](filters)
    } catch (error) {
      console.error('Gagal mengunduh laporan:', error)
      throw new Error('Gagal mengunduh laporan. Silakan coba lagi.')
    } finally {
      isDownloading.value = false
      currentReport.value = null
    }
  }

  return {
    isDownloading,
    currentReport,
    download,
  }
}