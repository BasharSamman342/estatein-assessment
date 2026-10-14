<script setup lang="ts">
import { reactive, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { usePropertyQuery } from '../../composables/usePropertyQuery'

defineProps<{
  locations: string[]
}>()

const route = useRoute()
const { t } = useI18n()
const { submit: submitQuery } = usePropertyQuery()

const form = reactive({
  q: '',
  location: '',
  type: '',
  price_range: '',
  size_range: '',
  year_range: '',
})

const controlClass =
  'mt-2 w-full rounded-lg border border-line bg-canvas px-5 py-[18px] text-lg leading-7 text-ink'

function queryString(key: string): string {
  const value = route.query[key]
  return typeof value === 'string' ? value : ''
}

function allowed(value: string, options: readonly string[]): string {
  return options.includes(value) ? value : ''
}

function readRoute() {
  form.q = queryString('q')
  form.location = queryString('location')
  form.type = allowed(queryString('type'), ['villa', 'apartment', 'commercial'])
  form.price_range = allowed(queryString('price_range'), [
    'lt500000',
    '500000to1000000',
    'gt1000000',
  ])
  form.size_range = allowed(queryString('size_range'), ['lt1500', '1500to3000', 'gt3000'])
  form.year_range = allowed(queryString('year_range'), ['pre2000', '2000to2015', 'post2015'])
}

watch(() => route.query, readRoute, { immediate: true, deep: true })

function onSubmit() {
  void submitQuery({
    q: form.q,
    location: form.location,
    type: form.type,
    price_range: form.price_range,
    size_range: form.size_range,
    year_range: form.year_range,
  })
}
</script>

<template>
  <form
    class="flex flex-col gap-5 rounded-xl border border-line bg-surface p-6 font-sans text-ink md:p-[50px]"
    @submit.prevent="onSubmit"
  >
    <div class="flex flex-col gap-4 md:flex-row md:items-end">
      <label class="flex min-w-0 flex-1 flex-col text-lg leading-7 text-muted">
        {{ t('properties.searchLabel') }}
        <input v-model="form.q" :class="controlClass" name="q" type="search" />
      </label>
      <button
        class="inline-flex items-center justify-center rounded-lg bg-accent px-6 py-[18px] text-lg font-medium leading-7 text-accent-ink"
        type="submit"
      >
        {{ t('properties.searchButton') }}
      </button>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 min-[1440px]:grid-cols-5">
      <label class="flex flex-col text-lg leading-7 text-muted">
        {{ t('properties.location') }}
        <select v-model="form.location" :class="controlClass" name="location">
          <option value="">{{ t('properties.anyLocation') }}</option>
          <option
            v-if="form.location && !locations.includes(form.location)"
            :value="form.location"
          >
            {{ form.location }}
          </option>
          <option v-for="location in locations" :key="location" :value="location">
            {{ location }}
          </option>
        </select>
      </label>
      <label class="flex flex-col text-lg leading-7 text-muted">
        {{ t('properties.type') }}
        <select v-model="form.type" :class="controlClass" name="type">
          <option value="">{{ t('properties.anyType') }}</option>
          <option value="villa">{{ t('properties.villa') }}</option>
          <option value="apartment">{{ t('properties.apartment') }}</option>
          <option value="commercial">{{ t('properties.commercial') }}</option>
        </select>
      </label>
      <label class="flex flex-col text-lg leading-7 text-muted">
        {{ t('properties.price') }}
        <select v-model="form.price_range" :class="controlClass" name="price_range">
          <option value="">{{ t('properties.anyPrice') }}</option>
          <option value="lt500000">{{ t('properties.priceUnder') }}</option>
          <option value="500000to1000000">{{ t('properties.priceMid') }}</option>
          <option value="gt1000000">{{ t('properties.priceOver') }}</option>
        </select>
      </label>
      <label class="flex flex-col text-lg leading-7 text-muted">
        {{ t('properties.size') }}
        <select v-model="form.size_range" :class="controlClass" name="size_range">
          <option value="">{{ t('properties.anySize') }}</option>
          <option value="lt1500">{{ t('properties.sizeUnder') }}</option>
          <option value="1500to3000">{{ t('properties.sizeMid') }}</option>
          <option value="gt3000">{{ t('properties.sizeOver') }}</option>
        </select>
      </label>
      <label class="flex flex-col text-lg leading-7 text-muted">
        {{ t('properties.year') }}
        <select v-model="form.year_range" :class="controlClass" name="year_range">
          <option value="">{{ t('properties.anyYear') }}</option>
          <option value="pre2000">{{ t('properties.yearEarly') }}</option>
          <option value="2000to2015">{{ t('properties.yearMid') }}</option>
          <option value="post2015">{{ t('properties.yearLate') }}</option>
        </select>
      </label>
    </div>
  </form>
</template>
