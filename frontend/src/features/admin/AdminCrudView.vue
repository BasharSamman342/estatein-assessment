<script setup lang="ts">
import { computed, ref, shallowRef, watch } from 'vue'
import { api, type FieldErrors } from '../../api/client'
import { resources } from '../../api/resources'
import AdminRecordForm from './AdminRecordForm.vue'
import AdminRecordTable from './AdminRecordTable.vue'
import type { AdminField } from './fields'
import {
  asRows,
  blankValues,
  errorText,
  fieldErrorsOf,
  imageWarning,
  isPublished,
  tableColumns,
  toRequestBody,
  unmatchedErrors,
  valuesFromRow,
  type AdminRow,
} from './record'

const props = defineProps<{
  resource: string
  fields: readonly AdminField[]
  title: string
}>()

const rows = ref<AdminRow[]>([])
const initial = ref<Record<string, unknown>>({})
const errors = ref<FieldErrors>({})
const editing = ref<AdminRow | null>(null)
const pending = shallowRef(false)
const loadError = shallowRef('')
const banner = shallowRef('')

const columns = computed(() => tableColumns(props.fields))
const formFields = computed(() =>
  props.fields.map((field) =>
    field.type === 'file' && editing.value ? { ...field, required: false } : field,
  ),
)
const formKey = computed(() => (editing.value ? `edit-${editing.value.id}` : 'create'))

function resetForm() {
  editing.value = null
  initial.value = blankValues(props.fields)
  errors.value = {}
}

function applyFailure(cause: unknown) {
  const fieldErrors = fieldErrorsOf(cause)
  if (!fieldErrors) {
    banner.value = errorText(cause)
    return
  }
  errors.value = fieldErrors
  banner.value = unmatchedErrors(props.fields, fieldErrors)
}

async function fetchRows() {
  const data = await resources.admin(props.resource).list()
  rows.value = asRows(data)
}

async function load() {
  pending.value = true
  loadError.value = ''
  try {
    await fetchRows()
  } catch (cause: unknown) {
    loadError.value = errorText(cause)
  } finally {
    pending.value = false
  }
}

function onEdit(row: AdminRow) {
  editing.value = row
  initial.value = valuesFromRow(props.fields, row)
  errors.value = {}
  banner.value = ''
}

async function onToggle(row: AdminRow) {
  if (pending.value) return
  pending.value = true
  banner.value = ''
  try {
    const values = valuesFromRow(props.fields, row)
    values.is_published = !isPublished(row)
    await resources.admin(props.resource).update(row.id, toRequestBody(props.fields, values))
    await fetchRows()
    if (editing.value?.id === row.id) {
      const fresh = rows.value.find((item) => item.id === row.id)
      if (fresh) onEdit(fresh)
    }
  } catch (cause: unknown) {
    applyFailure(cause)
  } finally {
    pending.value = false
  }
}

async function onDelete(row: AdminRow) {
  if (pending.value) return
  pending.value = true
  banner.value = ''
  try {
    await resources.admin(props.resource).remove(row.id)
    if (editing.value?.id === row.id) resetForm()
    await fetchRows()
  } catch (cause: unknown) {
    applyFailure(cause)
  } finally {
    pending.value = false
  }
}

async function writeRecord(id: number | null, body: Record<string, unknown> | FormData) {
  const collection = resources.admin(props.resource)
  if (id === null) {
    await collection.create(body)
    return
  }
  if (!(body instanceof FormData)) {
    await collection.update(id, body)
    return
  }
  // PHP only parses uploaded files on POST, so spoof the PUT route.
  body.append('_method', 'PUT')
  await api.send(`/admin/${props.resource}/${id}`, { method: 'POST', body, auth: true })
}

async function onSubmit(values: Record<string, unknown>) {
  if (pending.value) return
  for (const field of props.fields) {
    if (field.type !== 'file') continue
    const value = values[field.name]
    if (value instanceof File) {
      const warning = imageWarning(value)
      if (warning) {
        banner.value = warning
        return
      }
    }
  }
  pending.value = true
  errors.value = {}
  banner.value = ''
  try {
    const body = toRequestBody(props.fields, values)
    await writeRecord(editing.value ? editing.value.id : null, body)
    resetForm()
    await fetchRows()
  } catch (cause: unknown) {
    applyFailure(cause)
  } finally {
    pending.value = false
  }
}

watch(
  () => props.resource,
  () => {
    resetForm()
    void load()
  },
  { immediate: true },
)
</script>

<template>
  <section class="flex flex-col gap-8 font-sans text-ink">
    <h1 class="text-3xl font-semibold">{{ title }}</h1>
    <p v-if="loadError" role="alert" class="text-accent">
      {{ loadError }}
      <button type="button" class="ms-3 underline" @click="load">Retry</button>
    </p>
    <p v-else-if="pending && rows.length === 0" role="status">Loading…</p>
    <p v-else-if="rows.length === 0" class="text-muted">No records yet.</p>
    <AdminRecordTable
      v-if="rows.length > 0"
      :rows="rows"
      :columns="columns"
      :pending="pending"
      @toggle="onToggle"
      @edit="onEdit"
      @delete="onDelete"
    />
    <div id="admin-record-form" class="flex flex-col gap-4 rounded-xl border border-line bg-surface p-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-2xl font-semibold">{{ editing ? 'Edit record' : 'New record' }}</h2>
        <button
          v-if="editing"
          type="button"
          class="rounded-lg border border-line px-4 py-2 text-ink"
          @click="resetForm"
        >
          Cancel
        </button>
      </div>
      <p v-if="banner" role="alert" class="text-accent">{{ banner }}</p>
      <AdminRecordForm
        :key="formKey"
        :fields="formFields"
        :initial="initial"
        :pending="pending"
        :errors="errors"
        :submit-label="editing ? 'Save' : 'Create'"
        @submit="onSubmit"
      />
    </div>
  </section>
</template>
