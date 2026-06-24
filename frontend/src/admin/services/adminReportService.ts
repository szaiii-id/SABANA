import { reportEndpoint } from '../api/adminReportApi'
import type { ReportFilter } from '../types/reportAdmin'

function buildParams(filters: ReportFilter): Record<string, string> {
  const params: Record<string, string> = {}

  if (filters.tgl_mulai) params.tgl_mulai = filters.tgl_mulai
  if (filters.tgl_akhir) params.tgl_akhir = filters.tgl_akhir
  if (filters.program_id) params.program_id = filters.program_id

  return params
}

function previewPdf(response: Blob): void {
  const blob = new Blob([response], { type: 'application/pdf' })
  const url = window.URL.createObjectURL(blob)
  window.open(url, '_blank')
  setTimeout(() => window.URL.revokeObjectURL(url), 60000)
}

export const adminReportService = {
  async budgetSummary(filters: ReportFilter): Promise<void> {
    const response = await reportEndpoint.budgetSummary(buildParams(filters))
    previewPdf(response.data as Blob)
  },

  async programRecipients(filters: ReportFilter): Promise<void> {
    const response = await reportEndpoint.programRecipients(buildParams(filters))
    previewPdf(response.data as Blob)
  },

  async mostAppliedPrograms(filters: ReportFilter): Promise<void> {
    const response = await reportEndpoint.mostAppliedPrograms(buildParams(filters))
    previewPdf(response.data as Blob)
  },

  async citizenRegisteredByAdmin(filters: ReportFilter): Promise<void> {
    const response = await reportEndpoint.citizenRegisteredByAdmin(buildParams(filters))
    previewPdf(response.data as Blob)
  },

  async readyForDisbursement(filters: ReportFilter): Promise<void> {
    const response = await reportEndpoint.readyForDisbursement(buildParams(filters))
    previewPdf(response.data as Blob)
  },

  async pendingEvaluation(filters: ReportFilter): Promise<void> {
    const response = await reportEndpoint.pendingEvaluation(buildParams(filters))
    previewPdf(response.data as Blob)
  },

  async revokedRecipients(filters: ReportFilter): Promise<void> {
    const response = await reportEndpoint.revokedRecipients(buildParams(filters))
    previewPdf(response.data as Blob)
  },

  async approvedRecipients(filters: ReportFilter): Promise<void> {
    const response = await reportEndpoint.approvedRecipients(buildParams(filters))
    previewPdf(response.data as Blob)
  },

  async disbursedRecipients(filters: ReportFilter): Promise<void> {
    const response = await reportEndpoint.disbursedRecipients(buildParams(filters))
    previewPdf(response.data as Blob)
  },

  async fetchActivePrograms() {
    const { data } = await reportEndpoint.activePrograms()
    return data.data
  },
}