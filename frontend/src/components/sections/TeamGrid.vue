<script setup lang="ts">
import { storeToRefs } from 'pinia'
import EmptyState from '../ui/EmptyState.vue'
import ErrorState from '../ui/ErrorState.vue'
import LoadingState from '../ui/LoadingState.vue'
import { useLocaleStore } from '../../stores/locale'
import { textOf } from '../../utils/text'

interface TeamMember {
  id: number
  photo_url?: string | null
}

defineProps<{
  items: TeamMember[]
  status: 'idle' | 'loading' | 'ready' | 'error'
}>()

const emit = defineEmits<{
  retry: []
}>()

const { locale } = storeToRefs(useLocaleStore())

function memberName(item: TeamMember): string {
  return textOf(item, 'name', locale.value)
}

function role(item: TeamMember): string {
  return textOf(item, 'role', locale.value)
}

function photoUrl(item: TeamMember): string {
  return typeof item.photo_url === 'string' ? item.photo_url : ''
}
</script>

<template>
  <div class="font-sans text-ink">
    <LoadingState v-if="status === 'loading'" />
    <ErrorState v-else-if="status === 'error'" @retry="emit('retry')" />
    <EmptyState v-else-if="status === 'ready' && items.length === 0" />
    <ul v-else class="grid grid-cols-1 gap-[30px] sm:grid-cols-2 xl:grid-cols-4">
      <li v-for="item in items" :key="item.id" class="flex flex-col gap-4">
        <img
          v-if="photoUrl(item)"
          :src="photoUrl(item)"
          :alt="memberName(item)"
          class="aspect-[4/5] w-full rounded-xl object-cover"
        />
        <div v-else class="aspect-[4/5] w-full rounded-xl bg-line" />
        <div>
          <p class="whitespace-pre-line text-xl font-semibold leading-7">{{ memberName(item) }}</p>
          <p class="whitespace-pre-line text-lg leading-7 text-muted">{{ role(item) }}</p>
        </div>
      </li>
    </ul>
  </div>
</template>
