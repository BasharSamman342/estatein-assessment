<script setup lang="ts">
import { onMounted, ref, shallowRef } from 'vue'
import { resources } from '../../api/resources'
import { asRows, errorText } from '../../features/admin/record'

const sections = [
  { resource: 'testimonials', label: 'Testimonials', to: '/admin/testimonials' },
  { resource: 'faqs', label: 'FAQs', to: '/admin/faqs' },
  { resource: 'team-members', label: 'Team', to: '/admin/team' },
  { resource: 'clients', label: 'Clients', to: '/admin/clients' },
  { resource: 'properties', label: 'Properties', to: '/admin/properties' },
] as const

const counts = ref<Record<string, number | null>>({
  testimonials: null,
  faqs: null,
  'team-members': null,
  clients: null,
  properties: null,
})
const loading = shallowRef(true)
const loadError = shallowRef('')

function countFor(resource: string): number | null {
  const value = counts.value[resource]
  return typeof value === 'number' ? value : null
}

async function load() {
  loading.value = true
  loadError.value = ''
  let failure = ''
  await Promise.all(
    sections.map(async (section) => {
      try {
        const data = await resources.admin(section.resource).list()
        counts.value[section.resource] = asRows(data).length
      } catch (cause: unknown) {
        counts.value[section.resource] = null
        if (!failure) failure = errorText(cause)
      }
    }),
  )
  loadError.value = failure
  loading.value = false
}

onMounted(() => {
  void load()
})
</script>

<template>
  <section class="flex flex-col gap-6 font-sans text-ink">
    <h1 class="text-3xl font-semibold">Dashboard</h1>
    <p v-if="loadError" role="alert" class="text-accent">
      {{ loadError }}
      <button type="button" class="ms-3 underline" @click="load">Retry</button>
    </p>
    <ul class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <li
        v-for="section in sections"
        :key="section.resource"
        class="flex items-center justify-between gap-4 rounded-xl border border-line bg-surface px-5 py-4"
      >
        <RouterLink :to="section.to" class="text-lg text-ink underline">{{ section.label }}</RouterLink>
        <span class="text-muted" :data-testid="`admin-count-${section.resource}`">
          <template v-if="loading">Loading…</template>
          <template v-else-if="countFor(section.resource) === null">Unavailable</template>
          <template v-else>{{ countFor(section.resource) }}</template>
        </span>
      </li>
    </ul>
  </section>
</template>
