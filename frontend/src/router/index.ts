import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: () => import('../layouts/LandingLayout.vue'),
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('../pages/public/Home.vue')
        },
        // Tambahkan rute public lainnya di sini
      ]
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../pages/auth/Login.vue')
    }
  ]
})

export default router