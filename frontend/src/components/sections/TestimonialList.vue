<script setup lang="ts">
import { storeToRefs } from 'pinia'
import EmptyState from '../ui/EmptyState.vue'
import ErrorState from '../ui/ErrorState.vue'
import LoadingState from '../ui/LoadingState.vue'
import { useLocaleStore } from '../../stores/locale'
import { textOf } from '../../utils/text'

interface TestimonialItem {
  id: number
  rating?: number | null
  avatar_url?: string | null
}

defineProps<{
  items: TestimonialItem[]
  status: 'idle' | 'loading' | 'ready' | 'error'
}>()

const emit = defineEmits<{
  retry: []
}>()

const { locale } = storeToRefs(useLocaleStore())

function quote(item: TestimonialItem): string {
  return textOf(item, 'quote', locale.value)
}

function author(item: TestimonialItem): string {
  return textOf(item, 'author_name', locale.value)
}

function role(item: TestimonialItem): string {
  return textOf(item, 'role', locale.value)
}

function avatarUrl(item: TestimonialItem): string {
  return typeof item.avatar_url === 'string' ? item.avatar_url : ''
}

function ratingValue(item: TestimonialItem): number | null {
  return typeof item.rating === 'number' ? item.rating : null
}

function ratingText(item: TestimonialItem): string {
  const rating = ratingValue(item)
  return rating === null ? '' : String(rating)
}

function stars(item: TestimonialItem): number[] {
  const rating = ratingValue(item)
  if (rating === null || !Number.isFinite(rating)) return []
  const count = Math.max(0, Math.min(5, Math.trunc(rating)))
  return Array.from({ length: count }, (_, index) => index + 1)
}
</script>

<template>
  <div class="font-sans text-ink">
    <LoadingState v-if="status === 'loading'" />
    <ErrorState v-else-if="status === 'error'" @retry="emit('retry')" />
    <EmptyState v-else-if="status === 'ready' && items.length === 0" />
    <ul v-else class="grid grid-cols-1 gap-[30px] md:grid-cols-2 xl:grid-cols-3">
      <li
        v-for="item in items"
        :key="item.id"
        class="flex flex-col gap-10 rounded-xl border border-line bg-surface p-[50px]"
      >
        <p v-if="ratingValue(item) !== null" class="flex flex-wrap gap-2.5">
          <span
            v-for="star in stars(item)"
            :key="star"
            class="flex size-11 items-center justify-center rounded-full border border-line text-lg text-accent"
            aria-hidden="true"
          >★</span>
          <span class="sr-only">{{ ratingText(item) }}</span>
        </p>
        <p class="whitespace-pre-line text-lg leading-7">{{ quote(item) }}</p>
        <div class="flex items-center gap-3">
          <img
            v-if="avatarUrl(item)"
            :src="avatarUrl(item)"
            :alt="author(item)"
            class="size-[60px] rounded-full object-cover"
          />
          <div class="min-w-0">
            <p class="whitespace-pre-line text-xl font-semibold leading-7">{{ author(item) }}</p>
            <p class="whitespace-pre-line text-lg leading-7 text-muted">{{ role(item) }}</p>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>
