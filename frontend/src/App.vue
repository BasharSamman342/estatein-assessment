<script setup lang="ts">
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { getActivePinia, storeToRefs } from 'pinia'
import { useLocaleStore } from './stores/locale'

const route = useRoute()

if (route && getActivePinia()) {
  const localeStore = useLocaleStore()
  const { locale } = storeToRefs(localeStore)

  watch(
    () => [route.meta.admin, locale.value],
    () => {
      if (route.meta.admin) {
        document.documentElement.lang = 'en'
        document.documentElement.dir = 'ltr'
        return
      }
      localeStore.applyToDocument()
    },
    { immediate: true },
  )
}
</script>

<template>
  <div data-testid="app-root" class="min-h-screen bg-canvas text-ink">
    <RouterView v-if="route" />
  </div>
</template>
