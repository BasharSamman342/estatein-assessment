<script setup lang="ts">
import { reactive, ref, shallowRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { HttpError, type FieldErrors } from '../../api/client'
import { resources } from '../../api/resources'
import FormField from '../ui/FormField.vue'

const props = defineProps<{
  type: 'contact' | 'property'
  propertyId: number | null
}>()

const emit = defineEmits<{
  success: []
}>()

const fieldNames = ['name', 'email', 'phone', 'message'] as const

const { t } = useI18n()

const fields = reactive({
  name: '',
  email: '',
  phone: '',
  message: '',
})

const errors = ref<Record<string, string>>({})
const pending = shallowRef(false)
const failure = shallowRef('')
const succeeded = shallowRef(false)

const controlClass =
  'w-full rounded-lg border border-line bg-canvas px-5 py-[18px] text-lg leading-7 text-ink'

function fieldError(name: string): string {
  return errors.value[name] ?? ''
}

function fieldMap(source: FieldErrors): Record<string, string> {
  const next: Record<string, string> = {}
  for (const [key, messages] of Object.entries(source)) {
    const message = messages[0]
    if (message) next[key] = message
  }
  return next
}

function unmatchedMessages(source: FieldErrors): string[] {
  const messages: string[] = []
  for (const [key, value] of Object.entries(source)) {
    if (fieldNames.includes(key as (typeof fieldNames)[number])) continue
    const message = value[0]
    if (message) messages.push(message)
  }
  return messages
}

function resetFields() {
  fields.name = ''
  fields.email = ''
  fields.phone = ''
  fields.message = ''
}

async function onSubmit() {
  pending.value = true
  errors.value = {}
  failure.value = ''
  succeeded.value = false
  try {
    await resources.inquiries({
      type: props.type,
      property_id: props.propertyId,
      ...fields,
      phone: fields.phone || null,
    })
    emit('success')
    resetFields()
    succeeded.value = true
  } catch (cause: unknown) {
    if (cause instanceof HttpError) {
      const mapped = fieldMap(cause.errors)
      const unmatched = unmatchedMessages(cause.errors)
      errors.value = mapped
      if (unmatched.length > 0) failure.value = unmatched.join(' ')
      else if (Object.keys(mapped).length === 0) failure.value = t('inquiry.failure')
    } else {
      failure.value = t('inquiry.failure')
    }
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <form
    class="flex flex-col gap-[30px] rounded-xl border border-line bg-surface p-6 font-sans text-ink md:p-[50px]"
    @submit.prevent="onSubmit"
  >
    <p v-if="failure" role="alert" class="text-lg leading-7 text-accent">{{ failure }}</p>
    <p v-if="succeeded" role="status" class="text-lg leading-7">{{ t('inquiry.success') }}</p>
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
      <FormField :label="t('inquiry.name')" name="name" :error="fieldError('name')">
        <input
          id="name"
          v-model="fields.name"
          class="mt-2"
          :class="controlClass"
          name="name"
          type="text"
          autocomplete="name"
          required
        />
      </FormField>
      <FormField :label="t('inquiry.email')" name="email" :error="fieldError('email')">
        <input
          id="email"
          v-model="fields.email"
          class="mt-2"
          :class="controlClass"
          name="email"
          type="email"
          autocomplete="email"
          required
        />
      </FormField>
      <FormField :label="t('inquiry.phone')" name="phone" :error="fieldError('phone')">
        <input
          id="phone"
          v-model="fields.phone"
          class="mt-2"
          :class="controlClass"
          name="phone"
          type="tel"
          autocomplete="tel"
        />
      </FormField>
    </div>
    <FormField :label="t('inquiry.message')" name="message" :error="fieldError('message')">
      <textarea
        id="message"
        v-model="fields.message"
        class="mt-2 min-h-40"
        :class="controlClass"
        name="message"
        rows="5"
        required
      />
    </FormField>
    <button
      class="inline-flex w-full items-center justify-center rounded-lg bg-accent px-6 py-[18px] text-lg font-medium leading-7 text-accent-ink disabled:cursor-not-allowed disabled:opacity-60 md:w-auto"
      type="submit"
      data-testid="inquiry-submit"
      :disabled="pending"
    >
      {{ t('inquiry.send') }}
    </button>
  </form>
</template>
