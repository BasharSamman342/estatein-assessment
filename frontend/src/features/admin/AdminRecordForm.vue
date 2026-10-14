<script setup lang="ts">
import { reactive, shallowRef, watch } from 'vue'
import FormField from '../../components/ui/FormField.vue'
import type { FieldErrors } from '../../api/client'
import type { AdminField } from './fields'
import { asBoolean } from './record'

const props = defineProps<{
  fields: readonly AdminField[]
  initial: Record<string, unknown>
  pending: boolean
  errors: FieldErrors
  submitLabel: string
}>()

const emit = defineEmits<{
  submit: [values: Record<string, unknown>]
}>()

const model = reactive<Record<string, unknown>>({})
const fileGeneration = shallowRef(0)

const controlClass =
  'mt-2 w-full rounded-lg border border-line bg-canvas px-4 py-3 text-base leading-6 text-ink'

function copy(source: Record<string, unknown>) {
  const names = new Set(props.fields.map((field) => field.name))
  for (const key of Object.keys(model)) {
    if (!names.has(key)) delete model[key]
  }
  for (const field of props.fields) {
    model[field.name] = incomingValue(field, source[field.name])
  }
  fileGeneration.value += 1
}

function incomingValue(field: AdminField, value: unknown): unknown {
  if (field.type === 'checkbox') return asBoolean(value)
  if (field.type === 'file') return null
  if (value === null || value === undefined) return ''
  return String(value)
}

watch(() => props.initial, copy, { immediate: true })

function textValue(name: string): string {
  const value = model[name]
  return typeof value === 'string' ? value : ''
}

function checked(name: string): boolean {
  return model[name] === true
}

function onText(name: string, event: Event) {
  const target = event.target
  if (
    !(target instanceof HTMLInputElement) &&
    !(target instanceof HTMLTextAreaElement) &&
    !(target instanceof HTMLSelectElement)
  ) {
    return
  }
  model[name] = target.value
}

function onCheck(name: string, event: Event) {
  const target = event.target
  if (!(target instanceof HTMLInputElement)) return
  model[name] = target.checked
}

function onFile(name: string, event: Event) {
  const target = event.target
  if (!(target instanceof HTMLInputElement)) return
  model[name] = target.files?.[0] ?? null
}

function fieldError(name: string): string {
  return props.errors[name]?.[0] ?? ''
}

function onSubmit() {
  const body: Record<string, unknown> = {}
  for (const field of props.fields) {
    const value = model[field.name]
    if (field.type === 'checkbox') body[field.name] = value === true
    else if (field.type === 'number') {
      const text = typeof value === 'string' ? value.trim() : value
      if (text === '' || text === null || text === undefined) body[field.name] = null
      else {
        const number = typeof text === 'number' ? text : Number(text)
        body[field.name] = Number.isFinite(number) ? number : null
      }
    } else if (field.type === 'file') body[field.name] = value instanceof File ? value : null
    else {
      let text = typeof value === 'string' ? value : value == null ? '' : String(value)
      if (field.name === 'slug') text = text.trim()
      if (field.name === 'currency') text = text.trim().toUpperCase()
      body[field.name] = text
    }
  }
  emit('submit', { ...body })
}
</script>

<template>
  <form class="flex flex-col gap-5 font-sans text-ink" @submit.prevent="onSubmit">
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
      <FormField
        v-for="field in fields"
        :key="field.name"
        :label="field.label"
        :name="field.name"
        :error="fieldError(field.name)"
        :class="field.type === 'textarea' ? 'md:col-span-2' : ''"
      >
        <textarea
          v-if="field.type === 'textarea'"
          :id="field.name"
          :name="field.name"
          :value="textValue(field.name)"
          :required="field.required === true"
          :dir="field.name.endsWith('_ar') ? 'auto' : undefined"
          class="min-h-32 whitespace-pre-line"
          :class="controlClass"
          rows="5"
          @input="onText(field.name, $event)"
        />
        <input
          v-else-if="field.type === 'checkbox'"
          :id="field.name"
          :name="field.name"
          :checked="checked(field.name)"
          class="mt-2 size-5 accent-accent"
          type="checkbox"
          @change="onCheck(field.name, $event)"
        />
        <input
          v-else-if="field.type === 'file'"
          :id="field.name"
          :key="`${field.name}-${fileGeneration}`"
          :name="field.name"
          :required="field.required === true"
          class="mt-2 block w-full text-base text-ink"
          type="file"
          accept="image/jpeg,image/png,image/webp"
          @change="onFile(field.name, $event)"
        />
        <select
          v-else-if="field.type === 'select'"
          :id="field.name"
          :name="field.name"
          :value="textValue(field.name)"
          :required="field.required === true"
          :class="controlClass"
          @change="onText(field.name, $event)"
        >
          <option v-for="option in field.options ?? []" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <input
          v-else
          :id="field.name"
          :name="field.name"
          :value="textValue(field.name)"
          :required="field.required === true"
          :dir="field.name.endsWith('_ar') ? 'auto' : undefined"
          :type="field.type === 'number' ? 'number' : 'text'"
          :step="field.type === 'number' ? 'any' : undefined"
          :class="controlClass"
          @input="onText(field.name, $event)"
        />
      </FormField>
    </div>
    <slot />
    <button
      class="inline-flex w-full items-center justify-center rounded-lg bg-accent px-5 py-3 text-base font-medium leading-6 text-accent-ink disabled:cursor-not-allowed disabled:opacity-60 md:w-auto"
      type="submit"
      data-testid="admin-save"
      :disabled="pending"
    >
      {{ submitLabel }}
    </button>
  </form>
</template>
