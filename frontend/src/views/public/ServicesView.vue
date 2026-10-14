<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <div class="page-shell py-[30px] font-sans text-ink md:py-20">
    <h1
      class="text-[2rem] font-semibold leading-[1.2] md:text-[2.75rem] min-[1440px]:text-[3.75rem]"
    >
      {{ t('services.title') }}
    </h1>
  </div>
</template>
