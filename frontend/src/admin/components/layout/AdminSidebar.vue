<template>
  <div class="sticky top-6 h-[calc(100vh-3rem)]">
    <aside class="bg-white/95 backdrop-blur-xl border border-[#E8D5C4] rounded-[3rem] shadow-[0_20px_50px_rgba(27,67,50,0.05)] p-5 flex flex-col h-full overflow-hidden">
      
      <nav class="flex flex-col gap-1 flex-1 overflow-y-auto mt-2">
        
        <router-link 
          :to="{ name: 'admin.dashboard' }"
          :class="linkClass({ name: 'admin.dashboard' })"
        >
          <Squares2X2Icon class="w-5 h-5 flex-shrink-0" />
          <span>Dashboard</span>
        </router-link>

        <!-- ✅ SPK -->
        <router-link 
          :to="{ name: 'admin.spk' }"
          :class="linkClass({ name: 'admin.spk' })"
        >
          <AcademicCapIcon class="w-5 h-5 flex-shrink-0" />
          <span>SPK</span>
        </router-link>

        <router-link 
          v-if="currentUser?.role === 'super_admin'"
          :to="{ name: 'admin.programs' }"
          :class="linkClass({ name: 'admin.programs' })"
        >
          <BriefcaseIcon class="w-5 h-5 flex-shrink-0" />
          <span>Program</span>
        </router-link>

        <router-link 
          :to="{ name: 'admin.verifications' }"
          :class="linkClass({ name: 'admin.verifications' })"
        >
          <ClipboardDocumentCheckIcon class="w-5 h-5 flex-shrink-0" />
          <span>Verifikasi</span>
        </router-link>

        <router-link 
          :to="{ name: 'admin.citizen-registration' }"
          :class="linkClass({ name: 'admin.citizen-registration' })"
        >
          <UserPlusIcon class="w-5 h-5 flex-shrink-0" />
          <span>Pendaftaran Warga</span>
        </router-link>

        <router-link 
          :to="{ name: 'admin.citizen-assistance' }"
          :class="linkClass({ name: 'admin.citizen-assistance' })"
        >
          <DocumentTextIcon class="w-5 h-5 flex-shrink-0" />
          <span>Ajukan Bantuan</span>
        </router-link>

        <router-link 
          :to="{ name: 'admin.disbursements' }"
          :class="linkClass({ name: 'admin.disbursements' })"
        >
          <BanknotesIcon class="w-5 h-5 flex-shrink-0" />
          <span>Penyaluran</span>
        </router-link>

        <router-link 
          v-if="currentUser?.role !== 'village_officer'"
          :to="{ name: 'admin.evaluations' }"
          :class="linkClass({ name: 'admin.evaluations' })"
        >
          <ClipboardDocumentCheckIcon class="w-5 h-5 flex-shrink-0" />
          <span>Evaluasi</span>
        </router-link>

        <router-link 
          :to="{ name: 'admin.activity-logs' }"
          :class="linkClass({ name: 'admin.activity-logs' })"
        >
          <ClockIcon class="w-5 h-5 flex-shrink-0" />
          <span>Log Aktivitas</span>
        </router-link>

        <router-link 
          :to="{ name: 'admin.reports' }"
          :class="linkClass({ name: 'admin.reports' })"
        >
          <DocumentChartBarIcon class="w-5 h-5 flex-shrink-0" />
          <span>Laporan</span>
        </router-link>

        <router-link 
          v-if="currentUser?.role !== 'village_officer'"
          :to="{ name: 'admin.accounts' }"
          :class="linkClass({ name: 'admin.accounts' })"
        >
          <UsersIcon class="w-5 h-5 flex-shrink-0" />
          <span>Kelola Akun</span>
        </router-link>

        <router-link 
          :to="{ name: 'admin.profile' }"
          :class="linkClass({ name: 'admin.profile' })"
        >
          <UserCircleIcon class="w-5 h-5 flex-shrink-0" />
          <span>Profil</span>
        </router-link>

      </nav>

      <div class="flex-shrink-0 pt-4 mt-2 border-t border-[#E8D5C4]">
        <div class="flex items-center gap-2 px-3 py-2">
          <span class="relative flex h-2.5 w-2.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
          </span>
          <p class="text-xs font-medium text-[#6B705C]">Sistem berjalan normal</p>
        </div>
      </div>

    </aside>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, type RouteRecordName } from 'vue-router';
import { 
  Squares2X2Icon,
  BriefcaseIcon,
  ClipboardDocumentCheckIcon,
  UsersIcon,
  UserCircleIcon,
  UserPlusIcon,
  DocumentTextIcon,
  BanknotesIcon,
  ClockIcon,
  DocumentChartBarIcon,
  AcademicCapIcon,
} from '@heroicons/vue/24/outline';

interface RouteLike {
  name: RouteRecordName;
}

const route = useRoute();
const isActive = (to: RouteLike) => route.name === to.name;

const currentUser = computed(() => {
  try {
    const data = localStorage.getItem('admin_user');
    return data ? JSON.parse(data) : null;
  } catch {
    return null;
  }
});

const linkClass = (to: RouteLike) => [
  'flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all duration-300',
  isActive(to) 
    ? 'bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] text-white shadow-lg shadow-[#1B4332]/20' 
    : 'text-[#6B705C] hover:bg-[#FAF6F0] hover:text-[#1B4332]'
];
</script>