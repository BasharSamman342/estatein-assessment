<script setup lang="ts">
defineProps<{
  value: string
}>()
</script>

<template>
  <bdi>{{ value }}</bdi>
</template>
