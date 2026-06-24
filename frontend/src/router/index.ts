import { createRouter, createWebHistory } from 'vue-router'
import { adminRoutes } from '../admin/router/routes';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // === 1. PUBLIC ROUTES ===
    {
      path: '/',
      component: () => import('../layouts/LandingLayout.vue'),
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('../pages/public/Home.vue')
        }
      ]
    },

    // === 2. AUTH ROUTES (CITIZEN) === 
    {
      path: '/login',
      name: 'login',
      component: () => import('../pages/auth/Login.vue'),
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../pages/auth/Register.vue')
    },
    {
      path: '/verify-otp',
      name: 'verify-otp',
      component: () => import('../pages/auth/VerifyOtp.vue')
    },
    {
      path: '/forgot-pin',
      name: 'forgot-pin', 
      component: () => import('../pages/auth/ForgotPin.vue')
    },
    {
      path: '/reset-pin',
      name: 'reset-pin',
      component: () => import('../pages/auth/ResetPin.vue')
    },

    // === 3. PROTECTED ROUTES (CITIZEN) ===
    {
      path: '/dashboard',
      component: () => import('../layouts/DashboardLayout.vue'), 
      meta: { requiresAuth: true }, 
      children: [
        {
          path: '',
          name: 'dashboard.home',
          component: () => import('../pages/dashboard/Home.vue')
        },
        {
          path: '/security',
          name: 'security',
          component: () => import('../pages/dashboard/Security.vue')
        },
        {
          path: '/profile',
          name: 'profile',
          component: () => import('../pages/dashboard/Profile.vue')
        },
        {
          path: '/report',
          name: 'report',
          component: () => import('../pages/dashboard/Report.vue')
        },
        {
          path: '/assistance',
          name: 'assistance',
          component: () => import('../pages/dashboard/Assistance.vue')
        },
        {
          path: '/history',
          name: 'history',
          component: () => import('../pages/dashboard/History.vue')
        },
        {
          path: '/assistance/edit/:id', 
          name: 'assistance.edit',
          component: () => import('../pages/dashboard/AssistanceEdit.vue')
        },
        {
          path: '/assistance/detail/:id',
          name: 'assistance.detail',
          component: () => import('../pages/dashboard/AssistanceDetail.vue')
        }
      ]
    },

    // === 4. ADMIN ROUTES (MODULAR) ===
    ...adminRoutes 
  ]
})

// === GLOBAL MULTI-GUARD ===
router.beforeEach((to, _from, next) => {
  const citizenToken = localStorage.getItem('sabana_token');
  const adminToken = localStorage.getItem('admin_token');

  if (to.meta.requiresAdmin && !adminToken) {
    return next({ name: 'admin.login' });
  }
  if (to.meta.isGuestAdmin && adminToken) {
    return next({ name: 'admin.dashboard' });
  }

  const isCitizenAuthenticated = !!citizenToken;
  if (to.meta.requiresAuth && !isCitizenAuthenticated) {
    next({ name: 'login' });
  } 
  else if ((to.name === 'login' || to.name === 'register') && isCitizenAuthenticated) {
    next({ name: 'dashboard.home' });
  }
  else {
    next(); // Izinkan lewat (untuk rute publik)
  }
});

export default router;