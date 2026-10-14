<script setup lang="ts">
import { storeToRefs } from 'pinia'
import EmptyState from '../ui/EmptyState.vue'
import ErrorState from '../ui/ErrorState.vue'
import LoadingState from '../ui/LoadingState.vue'
import { useLocaleStore } from '../../stores/locale'
import { textOf } from '../../utils/text'

interface ClientLogo {
  id: number
  logo_url: string
}

defineProps<{
  items: ClientLogo[]
  status: 'idle' | 'loading' | 'ready' | 'error'
}>()

const emit = defineEmits<{
  retry: []
}>()

const { locale } = storeToRefs(useLocaleStore())

function clientName(item: ClientLogo): string {
  return textOf(item, 'name', locale.value)
}
</script>

<template>
  <div class="font-sans text-ink">
    <LoadingState v-if="status === 'loading'" />
    <ErrorState v-else-if="status === 'error'" @retry="emit('retry')" />
    <EmptyState v-else-if="status === 'ready' && items.length === 0" />
    <ul v-else class="grid grid-cols-2 gap-[30px] sm:grid-cols-3 lg:grid-cols-4">
      <li
        v-for="item in items"
        :key="item.id"
        class="flex items-center justify-center rounded-xl border border-line bg-surface p-6"
      >
        <img :src="item.logo_url" :alt="clientName(item)" class="max-h-12 w-full object-contain" />
      </li>
    </ul>
  </div>
</template>
