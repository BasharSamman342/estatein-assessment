<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { logout } = auth

const showNav = computed(() => route.name !== 'admin-login')

const links = [
  { name: 'admin-dashboard', label: 'Dashboard', to: '/admin' },
  { name: 'admin-testimonials', label: 'Testimonials', to: '/admin/testimonials' },
  { name: 'admin-faqs', label: 'FAQs', to: '/admin/faqs' },
  { name: 'admin-team', label: 'Team', to: '/admin/team' },
  { name: 'admin-clients', label: 'Clients', to: '/admin/clients' },
  { name: 'admin-properties', label: 'Properties', to: '/admin/properties' },
]

async function onLogout() {
  await logout()
  await router.push({ name: 'admin-login' })
}
</script>

<template>
  <div lang="en" dir="ltr" class="flex min-h-screen flex-col bg-canvas font-sans text-ink">
    <header v-if="showNav" class="border-b border-line bg-surface">
      <div class="page-shell flex flex-wrap items-center gap-4 py-5">
        <p class="text-xl font-semibold">Estatein</p>
        <nav aria-label="Admin" class="flex flex-wrap items-center gap-4">
          <RouterLink
            v-for="link in links"
            :key="link.name"
            :to="link.to"
            class="text-base"
            :class="route.name === link.name ? 'text-accent' : 'text-ink'"
            :aria-current="route.name === link.name ? 'page' : undefined"
          >
            {{ link.label }}
          </RouterLink>
        </nav>
        <button
          type="button"
          class="ms-auto rounded-lg border border-line px-4 py-2 text-ink"
          @click="onLogout"
        >
          Sign out
        </button>
      </div>
    </header>
    <main
      class="flex w-full flex-1 flex-col"
      :class="showNav ? '' : 'items-center justify-center px-4'"
    >
      <div :class="showNav ? 'page-shell py-10' : 'w-full max-w-md rounded-xl border border-line bg-surface p-8'">
        <p v-if="!showNav" class="mb-6 text-xl font-semibold">Estatein</p>
        <RouterView />
      </div>
    </main>
  </div>
</template>
