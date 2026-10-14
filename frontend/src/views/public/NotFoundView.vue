<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <div class="page-shell flex flex-col items-start gap-6 py-[30px] font-sans text-ink md:py-20">
    <h1 class="text-[2rem] font-semibold leading-[1.2] md:text-[2.75rem]">{{ t('notFound.title') }}</h1>
    <RouterLink
      to="/properties"
      class="inline-flex items-center justify-center rounded-lg bg-accent px-6 py-[18px] text-lg font-medium leading-7 text-accent-ink"
    >
      {{ t('notFound.back') }}
    </RouterLink>
  </div>
</template>
