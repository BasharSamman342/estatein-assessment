<script setup lang="ts">
defineProps<{
  label: string
  name: string
  error?: string
}>()
</script>

<template>
  <div>
    <label :for="name">{{ label }}</label>
    <div>
      <slot />
    </div>
    <p
      v-if="error"
      class="text-sm text-accent"
      :data-testid="`field-error-${name}`"
    >
      {{ error }}
    </p>
  </div>
</template>
