<script setup lang="ts">
import { computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import PropertyCard from '../../components/property/PropertyCard.vue'
import PropertyFilters from '../../components/property/PropertyFilters.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import ErrorState from '../../components/ui/ErrorState.vue'
import LoadingState from '../../components/ui/LoadingState.vue'
import { resources } from '../../api/resources'
import { useAsyncResource } from '../../composables/useAsyncResource'
import { usePropertyQuery } from '../../composables/usePropertyQuery'
import { useLocaleStore } from '../../stores/locale'

interface ListedProperty {
  id: number
  slug: string
  price: string | number
  currency?: string
  bedrooms: number
  bathrooms: number
  area_sqft: number
  cover_image?: {
    id: number
    url: string
    alt_en?: string
    alt_ar?: string
  } | null
}

interface PropertyPage {
  data: ListedProperty[]
  meta: {
    current_page: number
    last_page: number
  }
}

const { t } = useI18n()
const { locale } = storeToRefs(useLocaleStore())
const { params, setPage } = usePropertyQuery()

const properties = useAsyncResource(async ({ signal }) => {
  const response = await resources.properties(
    { ...params.value, per_page: 9, locale: locale.value },
    { signal },
  )
  return response as PropertyPage
})
const filters = useAsyncResource(({ signal }) => resources.propertyFilters(locale.value, { signal }))

const { data: propertyPage, status: listStatus } = properties
const { data: filterData, status: filtersStatus } = filters

const locations = computed(() => filterData.value?.locations ?? [])
const listings = computed(() => propertyPage.value?.data ?? [])
const page = computed(() => {
  const raw = params.value.page
  const value = Number(raw)
  return Number.isInteger(value) && value > 0 ? value : 1
})
const hasNext = computed(() => {
  const meta = propertyPage.value?.meta
  return meta ? meta.current_page < meta.last_page : false
})

watch(
  params,
  () => {
    void properties.run()
  },
  { immediate: true },
)

watch(
  locale,
  () => {
    void filters.run()
    if (params.value.q || params.value.location) void properties.run()
  },
  { immediate: true },
)

function showPage(next: number) {
  void setPage(next)
}
</script>

<template>
  <div class="page-shell flex flex-col gap-10 py-[30px] font-sans text-ink md:gap-[60px] md:py-20">
    <h1
      class="text-[2rem] font-semibold leading-[1.2] md:text-[2.75rem] min-[1440px]:text-[3.75rem]"
    >
      {{ t('properties.title') }}
    </h1>
    <ErrorState v-if="filtersStatus === 'error'" @retry="filters.run()" />
    <PropertyFilters :locations="locations" />
    <LoadingState v-if="listStatus === 'loading' || listStatus === 'idle'" />
    <ErrorState v-else-if="listStatus === 'error'" @retry="properties.run()" />
    <EmptyState v-else-if="listings.length === 0" :message="t('properties.empty')" />
    <ul v-else class="grid grid-cols-1 gap-[30px] md:grid-cols-2 min-[1440px]:grid-cols-3">
      <li v-for="property in listings" :key="property.id">
        <PropertyCard :property="property" />
      </li>
    </ul>
    <nav
      v-if="listStatus === 'ready' && (page > 1 || hasNext)"
      class="flex items-center justify-center gap-4"
      :aria-label="t('properties.title')"
    >
      <button
        type="button"
        class="inline-flex size-12 items-center justify-center rounded-lg border border-line text-lg disabled:opacity-40"
        :disabled="page <= 1"
        @click="showPage(page - 1)"
      >
        <span class="rtl:-scale-x-100" aria-hidden="true">←</span>
        <span v-if="page > 1" class="sr-only">{{ page - 1 }}</span>
      </button>
      <span class="min-w-8 text-center text-lg leading-7">{{ page }}</span>
      <button
        type="button"
        class="inline-flex size-12 items-center justify-center rounded-lg border border-line text-lg disabled:opacity-40"
        :disabled="!hasNext"
        @click="showPage(page + 1)"
      >
        <span class="rtl:-scale-x-100" aria-hidden="true">→</span>
        <span class="sr-only">{{ page + 1 }}</span>
      </button>
    </nav>
  </div>
</template>
