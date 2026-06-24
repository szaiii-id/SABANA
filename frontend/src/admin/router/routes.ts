import type { RouteRecordRaw } from 'vue-router';

const adminPrefix = import.meta.env.VITE_ADMIN_PORTAL_PREFIX || 'sabana-center-63';

export const adminRoutes: RouteRecordRaw[] = [
  {
    path: `/${adminPrefix}/login`,
    name: 'admin.login',
    component: () => import('../pages/auth/Login.vue'),
    meta: { isGuestAdmin: true }
  },
  {
    path: `/${adminPrefix}`,
    component: () => import('../layouts/AdminDashboardLayout.vue'),
    meta: { requiresAdmin: true },
    children: [
      {
        path: 'dashboard',
        name: 'admin.dashboard',
        component: () => import('../pages/dashboard/Home.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'dashboard/regency',
        name: 'admin.dashboard.regency',
        component: () => import('../pages/dashboard/RegencyDashboard.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'dashboard/district',
        name: 'admin.dashboard.district',
        component: () => import('../pages/dashboard/DistrictDashboard.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'dashboard/village',
        name: 'admin.dashboard.village',
        component: () => import('../pages/dashboard/VillageDashboard.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'accounts',
        name: 'admin.accounts',
        component: () => import('../pages/accounts/Index.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'profile',
        name: 'admin.profile',
        component: () => import('../pages/profile/Index.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'programs',
        name: 'admin.programs',
        component: () => import('../pages/programs/Index.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'verifications',
        name: 'admin.verifications',
        component: () => import('../pages/verification/Index.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'verifications/:id',
        name: 'admin.verifications.detail',
        component: () => import('../components/verification/VerificationDetail.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'citizen-registration',
        name: 'admin.citizen-registration',
        component: () => import('../pages/citizen-registration/Index.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'citizen-registration/:id',
        name: 'admin.citizen-registration.detail',
        component: () => import('../pages/citizen-registration/CitizenDetail.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'citizen-assistance',
        name: 'admin.citizen-assistance',
        component: () => import('../pages/citizen-assistance/Index.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'evaluations',
        name: 'admin.evaluations',
        component: () => import('../pages/evaluation/Index.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'disbursements',
        name: 'admin.disbursements',
        component: () => import('../pages/disbursement/Index.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'activity-logs',
        name: 'admin.activity-logs',
        component: () => import('../pages/activity-log/Index.vue'),
        meta: { requiresAdmin: true }
      },

      {
        path: 'reports',
        name: 'admin.reports',
        component: () => import('../pages/reports/Index.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'spk',
        name: 'admin.spk',
        component: () => import('../pages/spk/Index.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'spk/:programId',
        name: 'admin.spk.program',
        component: () => import('../pages/spk/ProgramSpk.vue'),
        meta: { requiresAdmin: true },
        props: true
      },
    ]
  }
];