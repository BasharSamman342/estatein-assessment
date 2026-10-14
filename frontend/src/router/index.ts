import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import AdminLayout from '../components/layout/AdminLayout.vue'
import PublicLayout from '../components/layout/PublicLayout.vue'
import AdminCrudView from '../features/admin/AdminCrudView.vue'
import PropertyAdminView from '../features/admin/PropertyAdminView.vue'
import {
  clientFields,
  faqFields,
  teamFields,
  testimonialFields,
  type AdminField,
} from '../features/admin/fields'
import { useAuthStore } from '../stores/auth'
import DashboardView from '../views/admin/DashboardView.vue'
import LoginView from '../views/admin/LoginView.vue'
import AboutView from '../views/public/AboutView.vue'
import ContactView from '../views/public/ContactView.vue'
import HomeView from '../views/public/HomeView.vue'
import NotFoundView from '../views/public/NotFoundView.vue'
import PropertiesView from '../views/public/PropertiesView.vue'
import PropertyDetailView from '../views/public/PropertyDetailView.vue'
import ServicesView from '../views/public/ServicesView.vue'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    admin?: boolean
    guestOnly?: boolean
  }
}

function adminResource(
  path: string,
  name: string,
  resource: string,
  title: string,
  fields: readonly AdminField[],
): RouteRecordRaw {
  return {
    path,
    name,
    component: AdminCrudView,
    props: { resource, title, fields },
    meta: { admin: true, requiresAuth: true },
  }
}

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: PublicLayout,
    children: [
      { path: '', name: 'home', component: HomeView },
      { path: 'about', name: 'about', component: AboutView },
      { path: 'properties', name: 'properties', component: PropertiesView },
      { path: 'properties/:slug', name: 'property-detail', component: PropertyDetailView },
      { path: 'services', name: 'services', component: ServicesView },
      { path: 'contact', name: 'contact', component: ContactView },
      { path: ':pathMatch(.*)*', name: 'not-found', component: NotFoundView },
    ],
  },
  {
    path: '/admin',
    component: AdminLayout,
    meta: { admin: true },
    children: [
      {
        path: 'login',
        name: 'admin-login',
        component: LoginView,
        meta: { admin: true, guestOnly: true },
      },
      {
        path: '',
        name: 'admin-dashboard',
        component: DashboardView,
        meta: { admin: true, requiresAuth: true },
      },
      adminResource('testimonials', 'admin-testimonials', 'testimonials', 'Testimonials', testimonialFields),
      adminResource('faqs', 'admin-faqs', 'faqs', 'FAQs', faqFields),
      adminResource('team', 'admin-team', 'team-members', 'Team', teamFields),
      adminResource('clients', 'admin-clients', 'clients', 'Clients', clientFields),
      {
        path: 'properties',
        name: 'admin-properties',
        component: PropertyAdminView,
        meta: { admin: true, requiresAuth: true },
      },
    ],
  },
]

export function createEstateRouter() {
  const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
  })

  router.beforeEach((to) => {
    const auth = useAuthStore()
    if (to.name !== 'admin-login' && to.meta.requiresAuth && !auth.token) {
      return { name: 'admin-login', query: { redirect: to.fullPath } }
    }
    if (to.meta.guestOnly && auth.token) {
      return { name: 'admin-dashboard' }
    }
  })

  return router
}
