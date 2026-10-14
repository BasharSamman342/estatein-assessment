<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <footer class="border-t border-line bg-canvas font-sans text-start">
    <div class="flex h-[6.5rem] items-center px-page">
      <p class="text-base leading-6 text-muted">{{ t('footer.rights') }}</p>
    </div>
  </footer>
</template>
