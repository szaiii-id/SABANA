<template>
  <div class="flex flex-col h-full px-4 md:px-6 py-6">
    <!-- Welcome Section -->
    <div class="mb-8">
      <h2 class="text-4xl font-black text-[#1B4332] tracking-tight">Selamat Datang, {{ adminFirstName }}</h2>
      <p class="text-[#6B705C] font-medium mt-2 text-lg">Pusat kendali operasi penyaluran bantuan sosial SABANA Center.</p>
    </div>

    <!-- Tab Toggle -->
    <div class="flex gap-2 mb-6 flex-wrap">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        :class="[
          'px-5 py-2.5 rounded-xl text-sm font-bold transition-all',
          activeTab === tab.id
            ? 'bg-[#1B4332] text-white shadow-lg'
            : 'bg-white border border-[#E8D5C4] text-[#6B705C] hover:bg-[#FAF6F0]'
        ]"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Dropdown Pemilih Wilayah (hanya Super Admin, bukan tab Ringkasan) -->
    <div v-if="isSuperAdmin && activeTab !== 'summary'" class="flex gap-3 mb-6 flex-wrap items-end">
      <!-- Kabupaten -->
      <div v-if="activeTab === 'regency' || activeTab === 'district' || activeTab === 'village'">
        <label class="block text-[10px] font-bold text-[#6B705C] uppercase mb-1">Kabupaten</label>
        <select v-model="selectedRegency" @change="onRegencyChange" class="px-4 py-2.5 rounded-xl border border-[#E8D5C4] text-sm font-bold text-[#1B4332] bg-white outline-none focus:border-[#1B4332] min-w-[200px]">
          <option value="">Pilih Kabupaten</option>
          <option v-for="r in regencies" :key="r.id" :value="r.id">{{ r.name }}</option>
        </select>
      </div>

      <!-- Kecamatan -->
      <div v-if="activeTab === 'district' || activeTab === 'village'">
        <label class="block text-[10px] font-bold text-[#6B705C] uppercase mb-1">Kecamatan</label>
        <select v-model="selectedDistrict" @change="onDistrictChange" :disabled="!selectedRegency" class="px-4 py-2.5 rounded-xl border border-[#E8D5C4] text-sm font-bold text-[#1B4332] bg-white outline-none focus:border-[#1B4332] min-w-[200px] disabled:opacity-50">
          <option value="">Pilih Kecamatan</option>
          <option v-for="d in districts" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
      </div>

      <!-- Desa -->
      <div v-if="activeTab === 'village'">
        <label class="block text-[10px] font-bold text-[#6B705C] uppercase mb-1">Desa</label>
        <select v-model="selectedVillage" :disabled="!selectedDistrict" class="px-4 py-2.5 rounded-xl border border-[#E8D5C4] text-sm font-bold text-[#1B4332] bg-white outline-none focus:border-[#1B4332] min-w-[200px] disabled:opacity-50">
          <option value="">Pilih Desa</option>
          <option v-for="v in villages" :key="v.id" :value="v.id">{{ v.name }}</option>
        </select>
      </div>
    </div>

    <!-- Notifikasi Anomali -->
    <div v-if="anomalyAlerts.length" class="mb-8 space-y-3">
      <div 
        v-for="alert in anomalyAlerts" 
        :key="alert.id"
        :class="[
          'rounded-2xl p-5 border-2 flex items-start gap-4',
          alert.severity === 'high' ? 'bg-red-50 border-red-200' : 
          alert.severity === 'medium' ? 'bg-orange-50 border-orange-200' : 
          'bg-amber-50 border-amber-200'
        ]"
      >
        <div :class="[
          'p-2.5 rounded-xl flex-shrink-0',
          alert.severity === 'high' ? 'bg-red-100 text-red-600' : 
          alert.severity === 'medium' ? 'bg-orange-100 text-orange-600' : 
          'bg-amber-100 text-amber-600'
        ]">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <h4 class="font-bold text-sm">{{ alert.typeLabel }}</h4>
          <p class="text-xs mt-1">{{ alert.message }}</p>
        </div>
        <div class="text-right flex-shrink-0">
          <span :class="[
            'px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider',
            alert.severity === 'high' ? 'bg-red-100 text-red-700' : 
            alert.severity === 'medium' ? 'bg-orange-100 text-orange-700' : 
            'bg-amber-100 text-amber-700'
          ]">
            {{ alert.severity === 'high' ? 'Tinggi' : alert.severity === 'medium' ? 'Sedang' : 'Rendah' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Notifikasi Kuota -->
    <div v-if="visibleWarnings.length" class="mb-8 space-y-3">
      <div 
        v-for="warning in visibleWarnings" 
        :key="warning.id"
        :class="[
          'rounded-2xl p-5 border-2 flex items-start gap-4 relative',
          warning.percentage >= 95 ? 'bg-red-50 border-red-200' : 
          warning.percentage >= 90 ? 'bg-orange-50 border-orange-200' : 
          'bg-amber-50 border-amber-200'
        ]"
      >
        <div :class="[
          'p-2.5 rounded-xl flex-shrink-0',
          warning.percentage >= 95 ? 'bg-red-100 text-red-600' : 
          warning.percentage >= 90 ? 'bg-orange-100 text-orange-600' : 
          'bg-amber-100 text-amber-600'
        ]">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <h4 class="font-bold text-sm">Kuota Hampir Penuh</h4>
          <p class="text-xs mt-1">
            Program <strong>"{{ warning.name }}"</strong> sudah terpakai 
            <strong>{{ warning.percentage }}%</strong> 
            ({{ warning.count }}/{{ warning.quota_total }})
          </p>
        </div>
        <div class="text-right flex-shrink-0 flex items-center gap-3">
          <span :class="[
            'px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider',
            warning.percentage >= 95 ? 'bg-red-100 text-red-700' : 
            warning.percentage >= 90 ? 'bg-orange-100 text-orange-700' : 
            'bg-amber-100 text-amber-700'
          ]">
            {{ warning.percentage }}%
          </span>
          <button 
            @click="dismissWarning(warning.id)"
            class="text-gray-400 hover:text-gray-600 transition-colors"
            title="Tutup notifikasi"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- TAB: Ringkasan -->
    <div v-if="activeTab === 'summary'">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] rounded-3xl p-7 text-white shadow-xl shadow-[#1B4332]/15 relative overflow-hidden group hover:shadow-2xl hover:shadow-[#1B4332]/25 transition-all duration-300 hover:scale-[1.02]">
          <div class="absolute top-0 right-0 w-32 h-32 bg-[#D4A373]/10 rounded-full -mr-10 -mt-10 blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
          <div class="relative z-10">
            <p class="text-[#D4A373] font-bold text-xs uppercase tracking-widest mb-2">Antrean Verifikasi</p>
            <h3 class="text-5xl font-black tracking-tight">{{ summaryStats.pendingSubmissions }}</h3>
            <p class="text-white/60 text-xs mt-2 font-medium">Menunggu diproses</p>
          </div>
        </div>
        <div class="bg-white border-2 border-[#E8D5C4] rounded-3xl p-7 shadow-sm hover:shadow-xl hover:border-[#D4A373] transition-all duration-300 group hover:scale-[1.02]">
          <p class="text-[#D4A373] font-bold text-xs uppercase tracking-widest mb-2">Program Aktif</p>
          <h3 class="text-5xl font-black text-[#1B4332] tracking-tight">{{ summaryStats.activePrograms }}</h3>
          <p class="text-[#6B705C] text-xs mt-2 font-medium">Program berjalan</p>
        </div>
        <div class="bg-white border-2 border-[#E8D5C4] rounded-3xl p-7 shadow-sm hover:shadow-xl hover:border-[#D4A373] transition-all duration-300 group hover:scale-[1.02]">
          <p class="text-[#D4A373] font-bold text-xs uppercase tracking-widest mb-2">Total Pengajuan</p>
          <h3 class="text-5xl font-black text-[#1B4332] tracking-tight">{{ summaryStats.totalSubmissions }}</h3>
          <p class="text-[#6B705C] text-xs mt-2 font-medium">Semua pengajuan</p>
        </div>
      </div>
    </div>

    <!-- TAB: Per Kabupaten -->
    <div v-if="activeTab === 'regency'">
      <RegencyDashboard :regency-id="selectedRegency || undefined" />
    </div>

    <!-- TAB: Per Kecamatan -->
    <div v-if="activeTab === 'district'">
      <DistrictDashboard :district-id="selectedDistrict || undefined" />
    </div>

    <!-- TAB: Per Desa -->
    <div v-if="activeTab === 'village'">
      <VillageDashboard :village-id="selectedVillage || undefined" :district-id="selectedDistrict || undefined" />
    </div>

    <!-- Security Info -->
    <div class="bg-gradient-to-r from-[#1B4332]/5 to-[#D4A373]/5 border border-[#E8D5C4] rounded-3xl p-7 mt-auto">
      <div class="flex items-start gap-5">
        <div class="bg-white p-3 rounded-2xl shadow-md">
          <svg class="w-7 h-7 text-[#D4A373]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <div>
          <h4 class="font-bold text-[#1B4332] text-lg">Keamanan Sesi Aktif</h4>
          <p class="text-sm text-[#6B705C] mt-1 leading-relaxed">
            Sistem Auto-Lock SABANA sedang memantau aktivitas Anda. Sesi akan ditutup otomatis jika tidak ada aktivitas selama <span class="font-bold text-[#D4A373]">30 menit</span>.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import ProgramService from '../../services/ProgramService';
import { useRegion } from '../../composables/useRegion';
import RegencyDashboard from './RegencyDashboard.vue';
import DistrictDashboard from './DistrictDashboard.vue';
import VillageDashboard from './VillageDashboard.vue';

const { regencies, districts, villages, fetchRegencies, fetchDistricts, fetchVillages } = useRegion();

// =============================================
// TYPES
// =============================================
interface QuotaWarning {
  id: string;
  name: string;
  count: number;
  quota_total: number;
  percentage: number;
}

interface AnomalyAlert {
  id: string;
  type: string;
  typeLabel: string;
  message: string;
  severity: 'high' | 'medium' | 'low';
  count: number;
}

interface ProgramData {
  id: string;
  name: string;
  status: string;
  quota_total: number;
  submissions_count: number;
  anomaly_count: number;
}

interface DashboardStats {
  pendingSubmissions: number;
  activePrograms: number;
  totalSubmissions: number;
}

// =============================================
// STATE
// =============================================
const DISMISS_KEY = 'sabana_dismissed_warnings';
const activeTab = ref('summary');
const tabs = [
  { id: 'summary', label: 'Ringkasan' },
  { id: 'regency', label: 'Per Kabupaten' },
  { id: 'district', label: 'Per Kecamatan' },
  { id: 'village', label: 'Per Desa' },
];
const quotaWarnings = ref<QuotaWarning[]>([]);
const anomalyAlerts = ref<AnomalyAlert[]>([]);
const dismissedIds = ref<string[]>([]);
const summaryStats = ref<DashboardStats>({
  pendingSubmissions: 0,
  activePrograms: 0,
  totalSubmissions: 0,
});

const selectedRegency = ref('');
const selectedDistrict = ref('');
const selectedVillage = ref('');

// =============================================
// COMPUTED
// =============================================
const adminData = computed(() => {
  try {
    return JSON.parse(localStorage.getItem('admin_user') || '{}') as Record<string, string>;
  } catch {
    return {};
  }
});

const isSuperAdmin = computed(() => adminData.value?.role === 'super_admin');

const adminFirstName = computed(() => {
  return adminData.value?.name ? adminData.value.name.split(' ')[0] : 'Admin';
});

const visibleWarnings = computed(() => {
  return quotaWarnings.value.filter(w => !dismissedIds.value.includes(w.id));
});

// =============================================
// LIFECYCLE
// =============================================
onMounted(async () => {
  loadDismissed();
  await fetchQuotaWarnings();
  await fetchAnomalies();
  await fetchStats();

  if (isSuperAdmin.value) {
    await fetchRegencies();
  }
});

// =============================================
// REGION HANDLERS
// =============================================
const onRegencyChange = async () => {
  selectedDistrict.value = '';
  selectedVillage.value = '';
  districts.value = [];
  villages.value = [];

  if (selectedRegency.value) {
    await fetchDistricts(selectedRegency.value);
  }
};

const onDistrictChange = async () => {
  selectedVillage.value = '';
  villages.value = [];

  if (selectedDistrict.value) {
    await fetchVillages(selectedDistrict.value);
  }
};

// =============================================
// METHODS
// =============================================
const loadDismissed = () => {
  try {
    const stored = JSON.parse(localStorage.getItem(DISMISS_KEY) || '{}') as Record<string, string>;
    dismissedIds.value = Object.keys(stored).filter(id => {
      const today = new Date().toDateString();
      return stored[id] === today;
    });
  } catch {
    dismissedIds.value = [];
  }
};

const dismissWarning = (id: string) => {
  dismissedIds.value.push(id);
  try {
    const stored = JSON.parse(localStorage.getItem(DISMISS_KEY) || '{}') as Record<string, string>;
    stored[id] = new Date().toDateString();
    localStorage.setItem(DISMISS_KEY, JSON.stringify(stored));
  } catch {
    // ignore
  }
};

const fetchQuotaWarnings = async () => {
  try {
    const response = await ProgramService.fetchPrograms({ status: 'active' });
    const programs = (response.data || []) as ProgramData[];
    quotaWarnings.value = programs
      .filter((p) => p.quota_total && p.submissions_count > 0)
      .map((p) => {
        const percentage = Math.round((p.submissions_count / p.quota_total) * 100);
        return { id: p.id, name: p.name, count: p.submissions_count, quota_total: p.quota_total, percentage };
      })
      .filter((p) => p.percentage >= 80)
      .sort((a, b) => b.percentage - a.percentage);
  } catch (e: unknown) {
    console.error('Gagal mengambil data kuota:', e);
  }
};

const fetchAnomalies = async () => {
  try {
    const response = await ProgramService.fetchPrograms({ status: 'active' });
    const programs = (response.data || []) as ProgramData[];
    const alerts: AnomalyAlert[] = [];
    for (const program of programs) {
      if (program.anomaly_count > 0) {
        alerts.push({
          id: program.id, type: 'anomaly', typeLabel: 'Anomali Terdeteksi',
          message: `Program "${program.name}" memiliki ${program.anomaly_count} pengajuan mencurigakan.`,
          severity: program.anomaly_count > 5 ? 'high' : 'medium', count: program.anomaly_count,
        });
      }
    }
    anomalyAlerts.value = alerts;
  } catch (e: unknown) {
    console.error('Gagal mengambil data anomali:', e);
  }
};

const fetchStats = async () => {
  try {
    const response = await ProgramService.fetchPrograms({});
    const programs = (response.data || []) as ProgramData[];
    summaryStats.value = {
      activePrograms: programs.filter((p) => p.status === 'active').length,
      pendingSubmissions: programs.reduce((sum, p) => sum + (p.submissions_count || 0), 0),
      totalSubmissions: programs.reduce((sum, p) => sum + (p.submissions_count || 0), 0),
    };
  } catch (e: unknown) {
    console.error('Gagal mengambil statistik:', e);
  }
};
</script>