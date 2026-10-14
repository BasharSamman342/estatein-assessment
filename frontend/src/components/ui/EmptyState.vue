<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  message?: string
}>()

const { t } = useI18n()
const text = computed(() => (props.message ? props.message : t('states.empty')))
</script>

<template>
  <p>{{ text }}</p>
</template>
