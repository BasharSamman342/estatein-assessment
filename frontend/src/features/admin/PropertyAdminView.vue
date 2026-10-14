<script setup lang="ts">
import { computed, onMounted, ref, shallowRef } from 'vue'
import type { FieldErrors } from '../../api/client'
import { api } from '../../api/client'
import { resources } from '../../api/resources'
import AdminRecordForm from './AdminRecordForm.vue'
import AdminRecordTable from './AdminRecordTable.vue'
import FeatureEditor from './FeatureEditor.vue'
import PropertyImageEditor from './PropertyImageEditor.vue'
import { propertyFields } from './fields'
import {
  asRows,
  blankValues,
  errorText,
  fieldErrorsOf,
  isPublished,
  isRecord,
  readFeatures,
  readImages,
  scalarBody,
  tableColumns,
  unmatchedErrors,
  valuesFromRow,
  type AdminRow,
  type FeatureDraft,
  type GalleryImage,
} from './record'

const rows = ref<AdminRow[]>([])
const initial = ref<Record<string, unknown>>(blankValues(propertyFields))
const features = ref<FeatureDraft[]>([])
const images = ref<GalleryImage[]>([])
const errors = ref<FieldErrors>({})
const editingId = shallowRef<number | null>(null)
const pending = shallowRef(false)
const loadError = shallowRef('')
const banner = shallowRef('')

const columns = tableColumns(propertyFields)
const formKey = computed(() => (editingId.value === null ? 'create' : `edit-${editingId.value}`))

function resetForm() {
  editingId.value = null
  initial.value = blankValues(propertyFields)
  features.value = []
  images.value = []
  errors.value = {}
}

function applyFailure(cause: unknown) {
  const fieldErrors = fieldErrorsOf(cause)
  if (!fieldErrors) {
    banner.value = errorText(cause)
    return
  }
  errors.value = fieldErrors
  banner.value = unmatchedErrors(propertyFields, fieldErrors)
}

function propertyBody(values: Record<string, unknown>, featureRows: readonly FeatureDraft[]) {
  return {
    ...scalarBody(propertyFields, values),
    features: featureRows.map((feature) => ({
      label_en: feature.label_en,
      label_ar: feature.label_ar,
    })),
  }
}

async function fetchRows() {
  const data = await resources.admin('properties').list()
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

async function openProperty(id: number) {
  const detail = await api.get<unknown>(`/admin/properties/${id}`, { auth: true })
  const record = isRecord(detail) ? detail : {}
  editingId.value = id
  initial.value = valuesFromRow(propertyFields, record)
  features.value = readFeatures(detail)
  images.value = readImages(detail)
  errors.value = {}
  banner.value = ''
}

async function onEdit(row: AdminRow) {
  if (pending.value) return
  pending.value = true
  banner.value = ''
  try {
    await openProperty(row.id)
  } catch (cause: unknown) {
    editingId.value = row.id
    initial.value = valuesFromRow(propertyFields, row)
    features.value = []
    images.value = []
    banner.value = errorText(cause)
  } finally {
    pending.value = false
  }
}

async function refreshProperty() {
  if (editingId.value === null) return
  try {
    const detail = await api.get<unknown>(`/admin/properties/${editingId.value}`, { auth: true })
    images.value = readImages(detail)
    await fetchRows()
  } catch (cause: unknown) {
    banner.value = errorText(cause)
  }
}

async function onToggle(row: AdminRow) {
  if (pending.value) return
  pending.value = true
  banner.value = ''
  try {
    const detail = await api.get<unknown>(`/admin/properties/${row.id}`, { auth: true })
    const record = isRecord(detail) ? detail : row
    const values = valuesFromRow(propertyFields, record)
    values.is_published = !isPublished(record)
    await resources.admin('properties').update(row.id, propertyBody(values, readFeatures(detail)))
    await fetchRows()
    if (editingId.value === row.id) await openProperty(row.id)
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
    await resources.admin('properties').remove(row.id)
    if (editingId.value === row.id) resetForm()
    await fetchRows()
  } catch (cause: unknown) {
    applyFailure(cause)
  } finally {
    pending.value = false
  }
}

async function onSubmit(values: Record<string, unknown>) {
  if (pending.value) return
  pending.value = true
  errors.value = {}
  banner.value = ''
  const featureRows = features.value.map((feature) => ({ ...feature }))
  try {
    const body = propertyBody(values, featureRows)
    if (editingId.value === null) {
      const created = await resources.admin('properties').create(body)
      await fetchRows()
      if (isRecord(created) && typeof created.id === 'number') await openProperty(created.id)
      else resetForm()
    } else {
      const id = editingId.value
      await resources.admin('properties').update(id, body)
      await fetchRows()
      await openProperty(id)
    }
  } catch (cause: unknown) {
    applyFailure(cause)
  } finally {
    pending.value = false
  }
}

onMounted(() => {
  void load()
})
</script>

<template>
  <section class="flex flex-col gap-8 font-sans text-ink">
    <h1 class="text-3xl font-semibold">Properties</h1>
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
        <h2 class="text-2xl font-semibold">{{ editingId === null ? 'New record' : 'Edit record' }}</h2>
        <button
          v-if="editingId !== null"
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
        :fields="propertyFields"
        :initial="initial"
        :pending="pending"
        :errors="errors"
        :submit-label="editingId === null ? 'Create' : 'Save'"
        @submit="onSubmit"
      >
        <FeatureEditor v-model="features" />
      </AdminRecordForm>
    </div>
    <PropertyImageEditor
      v-if="editingId !== null"
      :property-id="editingId ?? 0"
      :images="images"
      @changed="refreshProperty"
    />
  </section>
</template>
