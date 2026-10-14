<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { useLocaleStore } from '../../stores/locale'

const localeStore = useLocaleStore()
const { locale } = storeToRefs(localeStore)
const { setLocale } = localeStore
const { locale: i18nLocale } = useI18n()

i18nLocale.value = locale.value

function choose(next: 'en' | 'ar') {
  i18nLocale.value = next
  setLocale(next)
}
</script>

<template>
  <div class="flex items-center gap-2">
    <button
      type="button"
      data-testid="locale-en"
      class="rounded-lg border px-3 py-2 text-sm font-medium"
      :class="
        locale === 'en' ? 'border-transparent bg-accent text-accent-ink' : 'border-line text-ink'
      "
      @click="choose('en')"
    >
      EN
    </button>
    <button
      type="button"
      data-testid="locale-ar"
      class="rounded-lg border px-3 py-2 text-sm font-medium"
      :class="
        locale === 'ar' ? 'border-transparent bg-accent text-accent-ink' : 'border-line text-ink'
      "
      @click="choose('ar')"
    >
      عربي
    </button>
  </div>
</template>
