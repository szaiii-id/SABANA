import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // 1. PUBLIC ROUTES
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

    // 2. AUTH ROUTES 
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

    // 3. PROTECTED ROUTES
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
        },
        
      ]
    }
  ]
})

router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('token');
  const isAuthenticated = !!token;

  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'login' });
  } 
  else if ((to.name === 'login' || to.name === 'register') && isAuthenticated) {
    next({ name: 'dashboard.home' });
  }
  else {
    next();
  }
});

export default router