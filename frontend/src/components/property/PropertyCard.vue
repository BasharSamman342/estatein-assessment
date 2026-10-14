<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import BdiText from '../ui/BdiText.vue'
import { useLocaleStore } from '../../stores/locale'
import { textOf } from '../../utils/text'
import { formatPrice } from '../../utils/money'

interface PropertyImage {
  id: number
  url: string
  alt_en?: string
  alt_ar?: string
}

interface PropertyCardProperty {
  id: number
  slug: string
  price: string | number
  currency?: string
  bedrooms: number
  bathrooms: number
  area_sqft: number
  cover_image?: PropertyImage | null
}

const props = defineProps<{
  property: PropertyCardProperty
}>()

const { t } = useI18n()
const { locale } = storeToRefs(useLocaleStore())

const title = computed(() => textOf(props.property, 'title', locale.value))
const description = computed(() => textOf(props.property, 'description', locale.value))
const location = computed(() => textOf(props.property, 'location', locale.value))
const alt = computed(() => textOf(props.property.cover_image, 'alt', locale.value))
const price = computed(() => formatPrice(props.property.price, props.property.currency || 'USD'))
const coverUrl = computed(() => {
  const url = props.property.cover_image?.url
  return typeof url === 'string' ? url : ''
})
</script>

<template>
  <article class="flex flex-col gap-[30px] rounded-xl border border-line bg-surface p-6 font-sans text-ink">
    <img
      v-if="coverUrl"
      :src="coverUrl"
      :alt="alt"
      class="aspect-[4/3] w-full rounded-lg object-cover"
    />
    <div class="flex flex-col gap-1.5">
      <h3 class="whitespace-pre-line text-2xl font-semibold leading-9">{{ title }}</h3>
      <p v-if="description" class="whitespace-pre-line text-lg leading-7 text-muted">{{ description }}</p>
      <p v-if="location" class="whitespace-pre-line text-lg leading-7 text-muted">{{ location }}</p>
    </div>
    <ul class="flex flex-wrap gap-2.5">
      <li class="rounded-lg border border-line px-4 py-2 text-lg leading-7">{{ property.bedrooms }}</li>
      <li class="rounded-lg border border-line px-4 py-2 text-lg leading-7">{{ property.bathrooms }}</li>
      <li class="rounded-lg border border-line px-4 py-2 text-lg leading-7">{{ property.area_sqft }}</li>
    </ul>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-2xl font-semibold leading-9">
        <BdiText :value="price" />
      </p>
      <RouterLink
        class="inline-flex shrink-0 items-center justify-center rounded-lg bg-accent px-6 py-[18px] text-center text-lg font-medium leading-7 text-accent-ink"
        :to="`/properties/${property.slug}`"
      >
        {{ t('properties.details') }}
      </RouterLink>
    </div>
  </article>
</template>
