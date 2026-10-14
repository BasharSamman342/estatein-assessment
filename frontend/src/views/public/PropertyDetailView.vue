<script setup lang="ts">
import { computed, shallowRef, watch } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { HttpError } from '../../api/client'
import { resources } from '../../api/resources'
import { useAsyncResource } from '../../composables/useAsyncResource'
import InquiryForm from '../../components/property/InquiryForm.vue'
import PropertyGallery from '../../components/property/PropertyGallery.vue'
import FaqList from '../../components/sections/FaqList.vue'
import SectionHeading from '../../components/sections/SectionHeading.vue'
import BdiText from '../../components/ui/BdiText.vue'
import ErrorState from '../../components/ui/ErrorState.vue'
import LoadingState from '../../components/ui/LoadingState.vue'
import { useLocaleStore } from '../../stores/locale'
import { formatPrice } from '../../utils/money'
import { textOf } from '../../utils/text'
import NotFoundView from './NotFoundView.vue'

interface GalleryImage {
  id: number
  url?: string
  is_cover?: boolean
}

interface PropertyFeature {
  id: number
}

interface PropertyDetail {
  id: number
  price: string | number
  currency?: string
  bedrooms: number
  bathrooms: number
  area_sqft: number
  images?: GalleryImage[]
  features?: PropertyFeature[]
}

interface Identified {
  id: number
}

const route = useRoute()
const { t } = useI18n()
const { locale } = storeToRefs(useLocaleStore())
const currentSlug = shallowRef('')

const detail = useAsyncResource(async ({ signal }) => {
  const slug = currentSlug.value
  if (!slug) throw new HttpError(404, 'Not found')
  const payload = await resources.property(slug, { signal })
  return payload as PropertyDetail
})
const faqs = useAsyncResource(async ({ signal }) => {
  const rows = await resources.faqs({ signal })
  return rows as Identified[]
})

const { data: detailData, error: detailError, status: detailStatus } = detail
const { data: faqItems, status: faqStatus } = faqs

function slugText(value: unknown): string {
  if (typeof value === 'string') return value
  if (Array.isArray(value)) {
    const first = value[0]
    return typeof first === 'string' ? first : ''
  }
  return ''
}

watch(
  () => route.params.slug,
  (slug) => {
    currentSlug.value = slugText(slug)
    void detail.run()
    void faqs.run()
  },
  { immediate: true },
)

const notFound = computed(
  () => detailError.value instanceof HttpError && detailError.value.status === 404,
)
// Frame 102:8754 has bed, bath, and area. It does not add year_built as a fourth fact.
const property = computed(() => (detailStatus.value === 'ready' ? detailData.value : null))
const title = computed(() => textOf(property.value, 'title', locale.value))
const description = computed(() => textOf(property.value, 'description', locale.value))
const location = computed(() => textOf(property.value, 'location', locale.value))
const price = computed(() => {
  const record = property.value
  if (!record) return ''
  return formatPrice(record.price, record.currency || 'USD')
})
const images = computed(() => {
  const rows = property.value?.images
  return Array.isArray(rows) ? rows : []
})
const features = computed(() => {
  const rows = property.value?.features
  return Array.isArray(rows) ? rows : []
})
</script>

<template>
  <LoadingState
    v-if="detailStatus === 'loading' || detailStatus === 'idle'"
    class="page-shell py-[30px] md:py-20"
  />
  <NotFoundView v-else-if="notFound" />
  <div v-else-if="detailStatus === 'error'" class="page-shell py-[30px] md:py-20">
    <ErrorState @retry="detail.run()" />
  </div>
  <article
    v-else-if="property"
    class="page-shell flex flex-col gap-10 py-[30px] font-sans text-ink md:gap-[60px] md:py-20"
  >
    <PropertyGallery :key="currentSlug" :images="images" />
    <header class="grid gap-3 md:grid-cols-[1fr_auto] md:items-start">
      <h1 class="whitespace-pre-line text-[2rem] font-semibold leading-[1.2] md:text-[2.75rem]">
        {{ title }}
      </h1>
      <p class="text-[2rem] font-semibold leading-[1.2] md:text-end">
        <BdiText :value="price" />
      </p>
      <p v-if="location" class="whitespace-pre-line text-lg leading-7 text-muted">{{ location }}</p>
    </header>
    <p v-if="description" class="whitespace-pre-line text-lg leading-7 text-muted">{{ description }}</p>
    <ul class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <li class="rounded-xl border border-line bg-surface p-6 text-2xl font-semibold leading-9">
        {{ property?.bedrooms }}
      </li>
      <li class="rounded-xl border border-line bg-surface p-6 text-2xl font-semibold leading-9">
        {{ property?.bathrooms }}
      </li>
      <li class="rounded-xl border border-line bg-surface p-6 text-2xl font-semibold leading-9">
        {{ property?.area_sqft }}
      </li>
    </ul>
    <ul v-if="features.length > 0" class="flex flex-wrap gap-3">
      <li
        v-for="feature in features"
        :key="feature.id"
        class="rounded-lg border border-line px-4 py-2 text-lg leading-7 whitespace-pre-line"
      >
        {{ textOf(feature, 'label', locale) }}
      </li>
    </ul>
    <section class="flex flex-col gap-10">
      <SectionHeading :title="t('home.faqTitle')" />
      <FaqList :items="faqItems ?? []" :status="faqStatus" @retry="faqs.run()" />
    </section>
    <InquiryForm v-if="property" type="property" :property-id="property?.id ?? null" />
  </article>
</template>
