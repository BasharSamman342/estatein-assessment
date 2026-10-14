<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const emit = defineEmits<{
  retry: []
}>()

const { t } = useI18n()
</script>

<template>
  <div role="alert">
    <p>{{ t('states.error') }}</p>
    <button type="button" @click="emit('retry')">{{ t('states.retry') }}</button>
  </div>
</template>
