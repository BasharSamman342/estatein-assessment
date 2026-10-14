<script setup lang="ts">
import { watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PropertyCard from '../../components/property/PropertyCard.vue'
import SectionHeading from '../../components/sections/SectionHeading.vue'
import FaqList from '../../components/sections/FaqList.vue'
import TestimonialList from '../../components/sections/TestimonialList.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import ErrorState from '../../components/ui/ErrorState.vue'
import LoadingState from '../../components/ui/LoadingState.vue'
import { resources } from '../../api/resources'
import { useAsyncResource } from '../../composables/useAsyncResource'

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

interface Identified {
  id: number
}

const { t } = useI18n()

const properties = useAsyncResource(async ({ signal }) => {
  const response = await resources.properties({ per_page: 3 }, { signal })
  return response.data as ListedProperty[]
})
const testimonials = useAsyncResource(async ({ signal }) => {
  const rows = await resources.testimonials({ signal })
  return rows as Identified[]
})
const faqs = useAsyncResource(async ({ signal }) => {
  const rows = await resources.faqs({ signal })
  return rows as Identified[]
})

const { data: featured, status: featuredStatus } = properties
const { data: testimonialItems, status: testimonialStatus } = testimonials
const { data: faqItems, status: faqStatus } = faqs

watch(
  () => null,
  () => {
    void properties.run()
    void testimonials.run()
    void faqs.run()
  },
  { immediate: true },
)
</script>

<template>
  <div class="page-shell flex flex-col gap-[50px] py-[30px] font-sans text-ink md:gap-20 md:py-20">
    <section class="flex flex-col gap-6 md:gap-10">
      <h1
        class="max-w-4xl text-[2rem] font-semibold leading-[1.2] md:text-[2.75rem] min-[1440px]:text-[3.75rem]"
      >
        {{ t('home.heroTitle') }}
      </h1>
      <p class="max-w-3xl whitespace-pre-line text-base leading-6 text-muted md:text-lg md:leading-7">
        {{ t('home.heroBody') }}
      </p>
      <div class="flex flex-col gap-4 md:flex-row">
        <RouterLink
          to="/about"
          class="inline-flex items-center justify-center rounded-lg border border-line px-6 py-[18px] text-center text-lg font-medium leading-7 text-ink"
        >
          {{ t('nav.learnMore') }}
        </RouterLink>
        <RouterLink
          to="/properties"
          class="inline-flex items-center justify-center rounded-lg bg-accent px-6 py-[18px] text-center text-lg font-medium leading-7 text-accent-ink"
        >
          {{ t('home.browse') }}
        </RouterLink>
      </div>
    </section>

    <section>
      <dl
        class="grid grid-cols-1 overflow-hidden rounded-xl border border-line bg-surface md:grid-cols-3"
      >
        <div class="p-[30px] md:border-e md:border-line md:p-10">
          <dd class="text-[2.5rem] font-semibold leading-none">{{ t('home.statCustomers') }}</dd>
          <dt class="mt-2 text-base leading-6 text-muted md:text-lg md:leading-7">
            {{ t('home.statCustomersLabel') }}
          </dt>
        </div>
        <div class="border-t border-line p-[30px] md:border-e md:border-t-0 md:p-10">
          <dd class="text-[2.5rem] font-semibold leading-none">{{ t('home.statProperties') }}</dd>
          <dt class="mt-2 text-base leading-6 text-muted md:text-lg md:leading-7">
            {{ t('home.statPropertiesLabel') }}
          </dt>
        </div>
        <div class="border-t border-line p-[30px] md:border-t-0 md:p-10">
          <dd class="text-[2.5rem] font-semibold leading-none">{{ t('home.statYears') }}</dd>
          <dt class="mt-2 text-base leading-6 text-muted md:text-lg md:leading-7">
            {{ t('home.statYearsLabel') }}
          </dt>
        </div>
      </dl>
    </section>

    <section>
      <ul class="grid grid-cols-1 gap-5 md:grid-cols-2 min-[1440px]:grid-cols-4">
        <li>
          <RouterLink
            to="/services"
            class="flex min-h-40 flex-col justify-between gap-8 rounded-xl border border-line bg-surface p-[30px] md:min-h-52 md:p-[40px]"
          >
            <span class="ms-auto text-2xl leading-none rtl:-scale-x-100" aria-hidden="true">↗</span>
            <span class="text-xl font-semibold leading-7">{{ t('home.cardHome') }}</span>
          </RouterLink>
        </li>
        <li>
          <RouterLink
            to="/services"
            class="flex min-h-40 flex-col justify-between gap-8 rounded-xl border border-line bg-surface p-[30px] md:min-h-52 md:p-[40px]"
          >
            <span class="ms-auto text-2xl leading-none rtl:-scale-x-100" aria-hidden="true">↗</span>
            <span class="text-xl font-semibold leading-7">{{ t('home.cardValue') }}</span>
          </RouterLink>
        </li>
        <li>
          <RouterLink
            to="/services"
            class="flex min-h-40 flex-col justify-between gap-8 rounded-xl border border-line bg-surface p-[30px] md:min-h-52 md:p-[40px]"
          >
            <span class="ms-auto text-2xl leading-none rtl:-scale-x-100" aria-hidden="true">↗</span>
            <span class="text-xl font-semibold leading-7">{{ t('home.cardManage') }}</span>
          </RouterLink>
        </li>
        <li>
          <RouterLink
            to="/services"
            class="flex min-h-40 flex-col justify-between gap-8 rounded-xl border border-line bg-surface p-[30px] md:min-h-52 md:p-[40px]"
          >
            <span class="ms-auto text-2xl leading-none rtl:-scale-x-100" aria-hidden="true">↗</span>
            <span class="text-xl font-semibold leading-7">{{ t('home.cardInvest') }}</span>
          </RouterLink>
        </li>
      </ul>
    </section>

    <section class="flex flex-col gap-10">
      <SectionHeading :title="t('home.propertiesTitle')" :body="t('home.propertiesBody')" />
      <LoadingState v-if="featuredStatus === 'loading' || featuredStatus === 'idle'" />
      <ErrorState v-else-if="featuredStatus === 'error'" @retry="properties.run()" />
      <EmptyState v-else-if="!featured || featured.length === 0" />
      <ul v-else class="grid grid-cols-1 gap-[30px] md:grid-cols-2 min-[1440px]:grid-cols-3">
        <li v-for="property in featured ?? []" :key="property.id">
          <PropertyCard :property="property" />
        </li>
      </ul>
    </section>

    <section class="flex flex-col gap-10">
      <SectionHeading :title="t('home.testimonialsTitle')" />
      <TestimonialList
        :items="testimonialItems ?? []"
        :status="testimonialStatus"
        @retry="testimonials.run()"
      />
    </section>

    <section class="flex flex-col gap-10">
      <SectionHeading :title="t('home.faqTitle')" />
      <FaqList :items="faqItems ?? []" :status="faqStatus" @retry="faqs.run()" />
    </section>
  </div>
</template>
