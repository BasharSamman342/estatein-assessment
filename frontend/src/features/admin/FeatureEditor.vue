<script setup lang="ts">
import type { FeatureDraft } from './record'

const features = defineModel<FeatureDraft[]>({
  default: (): FeatureDraft[] => [],
})

const controlClass =
  'w-full rounded-lg border border-line bg-canvas px-4 py-3 text-base leading-6 text-ink'

function addRow() {
  if (features.value.length >= 30) return
  features.value = [...features.value, { label_en: '', label_ar: '' }]
}

function removeRow(index: number) {
  features.value = features.value.filter((_, featureIndex) => featureIndex !== index)
}

function update(index: number, key: 'label_en' | 'label_ar', event: Event) {
  const target = event.target
  if (!(target instanceof HTMLInputElement)) return
  const current = features.value[index]
  if (!current) return
  const next = features.value.slice()
  next[index] = { ...current, [key]: target.value }
  features.value = next
}
</script>

<template>
  <fieldset class="flex flex-col gap-4 rounded-xl border border-line p-4">
    <legend class="px-2 text-lg font-medium">Features</legend>
    <p v-if="features.length === 0" class="text-muted">No features yet.</p>
    <div v-for="(feature, index) in features" :key="index" class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_auto]">
      <label class="flex flex-col gap-2 text-sm text-muted">
        English
        <input
          :value="feature.label_en"
          class="whitespace-pre-line"
          :class="controlClass"
          type="text"
          :aria-label="`Feature ${index + 1} English`"
          @input="update(index, 'label_en', $event)"
        />
      </label>
      <label class="flex flex-col gap-2 text-sm text-muted">
        Arabic
        <input
          :value="feature.label_ar"
          class="whitespace-pre-line"
          :class="controlClass"
          type="text"
          dir="auto"
          :aria-label="`Feature ${index + 1} Arabic`"
          @input="update(index, 'label_ar', $event)"
        />
      </label>
      <button
        type="button"
        class="self-end rounded-lg border border-line px-4 py-3 text-ink"
        @click="removeRow(index)"
      >
        Remove
      </button>
    </div>
    <button
      type="button"
      class="inline-flex w-fit items-center justify-center rounded-lg border border-line px-4 py-3 text-ink disabled:opacity-60"
      :disabled="features.length >= 30"
      @click="addRow"
    >
      Add feature
    </button>
    <p v-if="features.length >= 30" class="text-sm text-muted">A property can have at most 30 features.</p>
  </fieldset>
</template>
