<script setup lang="ts">
import { storeToRefs } from 'pinia'
import EmptyState from '../ui/EmptyState.vue'
import ErrorState from '../ui/ErrorState.vue'
import LoadingState from '../ui/LoadingState.vue'
import { useLocaleStore } from '../../stores/locale'
import { textOf } from '../../utils/text'

interface FaqItem {
  id: number
}

defineProps<{
  items: FaqItem[]
  status: 'idle' | 'loading' | 'ready' | 'error'
}>()

const emit = defineEmits<{
  retry: []
}>()

const { locale } = storeToRefs(useLocaleStore())

function question(item: FaqItem): string {
  return textOf(item, 'question', locale.value)
}

function answer(item: FaqItem): string {
  return textOf(item, 'answer', locale.value)
}
</script>

<template>
  <div class="font-sans text-ink">
    <LoadingState v-if="status === 'loading'" />
    <ErrorState v-else-if="status === 'error'" @retry="emit('retry')" />
    <EmptyState v-else-if="status === 'ready' && items.length === 0" />
    <ul v-else class="grid grid-cols-1 gap-[30px] md:grid-cols-2 xl:grid-cols-3">
      <li v-for="item in items" :key="item.id">
        <details class="rounded-xl border border-line bg-surface p-[50px]">
          <summary class="cursor-pointer whitespace-pre-line text-2xl font-semibold leading-9">
            {{ question(item) }}
          </summary>
          <p class="mt-[30px] whitespace-pre-line text-lg leading-7 text-muted">{{ answer(item) }}</p>
        </details>
      </li>
    </ul>
  </div>
</template>
