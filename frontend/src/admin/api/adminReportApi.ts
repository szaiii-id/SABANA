import adminApi from './axios'
import type { ProgramOption } from '../types/reportAdmin'

export const reportEndpoint = {
  budgetSummary: (params?: Record<string, string>) =>
    adminApi.get('reports/budget-summary', { params, responseType: 'blob' }),

  programRecipients: (params?: Record<string, string>) =>
    adminApi.get('reports/program-recipients', { params, responseType: 'blob' }),

  mostAppliedPrograms: (params?: Record<string, string>) =>
    adminApi.get('reports/most-applied-programs', { params, responseType: 'blob' }),

  citizenRegisteredByAdmin: (params?: Record<string, string>) =>
    adminApi.get('reports/citizen-registered-by-admin', { params, responseType: 'blob' }),

  readyForDisbursement: (params?: Record<string, string>) =>
    adminApi.get('reports/ready-for-disbursement', { params, responseType: 'blob' }),

  pendingEvaluation: (params?: Record<string, string>) =>
    adminApi.get('reports/pending-evaluation', { params, responseType: 'blob' }),

  revokedRecipients: (params?: Record<string, string>) =>
    adminApi.get('reports/revoked-recipients', { params, responseType: 'blob' }),

  approvedRecipients: (params?: Record<string, string>) =>
    adminApi.get('reports/approved-recipients', { params, responseType: 'blob' }),

  disbursedRecipients: (params?: Record<string, string>) =>
    adminApi.get('reports/disbursed-recipients', { params, responseType: 'blob' }),

  activePrograms: () =>
    adminApi.get<{ data: ProgramOption[] }>('programs/active'),
}