export interface ReportFilter {
  tgl_mulai: string
  tgl_akhir: string
  program_id: string
  wilayah_id: string
}

export interface ReportItem {
  id: string
  title: string
  description: string
  endpoint: string
}

export interface ProgramOption {
  id: string
  name: string
}

export interface WilayahOption {
  id: string
  name: string
  type: string
}

export const REPORT_LIST: ReportItem[] = [
  {
    id: 'budget-summary',
    title: 'Ringkasan Anggaran Program',
    description: 'Total anggaran, tersalurkan, dan sisa per program',
    endpoint: '/reports/budget-summary',
  },
  {
    id: 'program-recipients',
    title: 'Daftar Penerima per Program',
    description: 'Daftar warga penerima bantuan dengan status completed',
    endpoint: '/reports/program-recipients',
  },
  {
    id: 'most-applied-programs',
    title: 'Program Paling Banyak Diminati',
    description: 'Urutan program berdasarkan pendaftar unik',
    endpoint: '/reports/most-applied-programs',
  },
  {
    id: 'citizen-registered-by-admin',
    title: 'Warga Didaftarkan oleh Admin',
    description: 'Data warga yang didaftarkan admin (Akses Pribadi / Bantuan Petugas)',
    endpoint: '/reports/citizen-registered-by-admin',
  },
  {
    id: 'ready-for-disbursement',
    title: 'Data Warga Siap Disalurkan',
    description: 'Daftar warga tervalidasi yang siap menerima bantuan',
    endpoint: '/reports/ready-for-disbursement',
  },
  {
    id: 'pending-evaluation',
    title: 'Data Warga Akan Dievaluasi',
    description: 'Daftar warga yang masuk masa evaluasi 6 bulan',
    endpoint: '/reports/pending-evaluation',
  },
  {
    id: 'revoked-recipients',
    title: 'Data Warga Bantuan Dihentikan',
    description: 'Daftar warga yang bantuannya dihentikan',
    endpoint: '/reports/revoked-recipients',
  },
  {
    id: 'approved-recipients',
    title: 'Data Warga Tetap Menerima Bantuan',
    description: 'Daftar warga yang tetap lanjut menerima bantuan',
    endpoint: '/reports/approved-recipients',
  },
  {
    id: 'disbursed-recipients',
    title: 'Data Warga Sudah Disalurkan',
    description: 'Daftar warga yang sudah menerima bantuan',
    endpoint: '/reports/disbursed-recipients',
  },
]