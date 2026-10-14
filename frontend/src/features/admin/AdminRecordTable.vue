<script setup lang="ts">
import BdiText from '../../components/ui/BdiText.vue'
import { formatPrice } from '../../utils/money'
import type { AdminColumn, AdminRow } from './record'
import { isPublished } from './record'

const props = defineProps<{
  rows: AdminRow[]
  columns: AdminColumn[]
  pending?: boolean
}>()

const emit = defineEmits<{
  toggle: [row: AdminRow]
  edit: [row: AdminRow]
  delete: [row: AdminRow]
}>()

function cellText(row: AdminRow, key: string): string {
  if (key === 'is_published') return isPublished(row) ? 'Yes' : 'No'
  const value = row[key]
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (value === null || value === undefined) return ''
  if (typeof value === 'string' || typeof value === 'number') return String(value)
  return ''
}

function priceText(row: AdminRow): string {
  const currency = typeof row.currency === 'string' && row.currency ? row.currency : 'USD'
  return formatPrice(row.price, currency)
}

function confirmDelete(row: AdminRow) {
  if (props.pending) return
  if (window.confirm('Delete this record?')) emit('delete', row)
}

function onEdit(row: AdminRow, event: MouseEvent) {
  event.preventDefault()
  if (props.pending) return
  emit('edit', row)
}
</script>

<template>
  <div class="overflow-x-auto rounded-xl border border-line">
    <table class="w-full border-collapse text-start text-base text-ink">
      <thead class="bg-surface">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            scope="col"
            class="border-b border-line px-4 py-3 text-start font-medium"
          >
            {{ column.label }}
          </th>
          <th scope="col" class="border-b border-line px-4 py-3 text-start font-medium">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.id" class="border-b border-line last:border-b-0">
          <td v-for="column in columns" :key="column.key" class="px-4 py-3 align-top">
            <BdiText v-if="column.key === 'price'" :value="priceText(row)" />
            <span v-else class="block max-w-md whitespace-pre-line">{{ cellText(row, column.key) }}</span>
          </td>
          <td class="px-4 py-3 align-top">
            <div class="flex flex-wrap gap-3">
              <button
                type="button"
                data-testid="admin-publish"
                class="text-ink underline disabled:opacity-60"
                :disabled="pending"
                @click="emit('toggle', row)"
              >
                {{ isPublished(row) ? 'Unpublish' : 'Publish' }}
              </button>
              <a
                href="#admin-record-form"
                data-testid="admin-edit"
                class="text-ink underline"
                :class="pending ? 'pointer-events-none opacity-60' : ''"
                :aria-disabled="pending ? 'true' : undefined"
                @click="onEdit(row, $event)"
              >
                Edit
              </a>
              <button
                type="button"
                data-testid="admin-delete"
                class="text-accent underline disabled:opacity-60"
                :disabled="pending"
                @click="confirmDelete(row)"
              >
                Delete
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
