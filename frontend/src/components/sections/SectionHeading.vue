<script setup lang="ts">
withDefaults(
  defineProps<{
    title: string
    body?: string
  }>(),
  {
    body: '',
  },
)
</script>

<template>
  <div>
    <h2 class="text-3xl">{{ title }}</h2>
    <p v-if="body" class="text-muted whitespace-pre-line">{{ body }}</p>
  </div>
</template>
