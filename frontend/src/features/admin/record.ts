import { HttpError, type FieldErrors } from '../../api/client'
import type { AdminField } from './fields'

export interface AdminColumn {
  key: string
  label: string
}

export interface AdminRow {
  id: number
  is_published?: boolean
  currency?: string
  price?: string | number
  [key: string]: unknown
}

export interface FeatureDraft {
  label_en: string
  label_ar: string
}

export interface GalleryImage {
  id: number
  url: string
  alt_en: string
  alt_ar: string
  is_cover: boolean
  sort_order: number
}

const imageTypes = new Set(['image/jpeg', 'image/png', 'image/webp'])
const maxImageBytes = 5 * 1024 * 1024

const columnKeys = ['sort_order', 'is_published', 'property_type', 'year_built', 'price']

export function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

export function asBoolean(value: unknown): boolean {
  return value === true || value === 1 || value === '1'
}

export function isPublished(row: object): boolean {
  if (!isRecord(row)) return false
  return asBoolean(row.is_published)
}

export function asRows(data: unknown): AdminRow[] {
  if (!Array.isArray(data)) return []
  const rows: AdminRow[] = []
  for (const item of data) {
    if (!isRecord(item) || typeof item.id !== 'number') continue
    rows.push({ ...item, id: item.id })
  }
  return rows
}

export function errorText(cause: unknown): string {
  if (cause instanceof HttpError) return cause.message
  if (cause instanceof Error && cause.message) return cause.message
  return 'Request failed'
}

export function fieldErrorsOf(cause: unknown): FieldErrors | null {
  if (cause instanceof HttpError && cause.status === 422) return cause.errors
  return null
}

export function imageWarning(file: File): string {
  if (file.type && !imageTypes.has(file.type)) return 'Use a JPEG, PNG, or WebP image.'
  if (file.size > maxImageBytes) return 'Image must be 5 MB or smaller.'
  return ''
}

export function blankValues(fields: readonly AdminField[]): Record<string, unknown> {
  const initial: Record<string, unknown> = {}
  for (const field of fields) {
    if (field.type === 'checkbox') initial[field.name] = false
    else if (field.type === 'file') initial[field.name] = null
    else if (field.type === 'number') initial[field.name] = field.name === 'sort_order' ? 0 : null
    else if (field.type === 'select') initial[field.name] = field.options?.[0]?.value ?? ''
    else if (field.name === 'currency') initial[field.name] = 'USD'
    else initial[field.name] = ''
  }
  return initial
}

export function valuesFromRow(
  fields: readonly AdminField[],
  row: Record<string, unknown>,
): Record<string, unknown> {
  const initial: Record<string, unknown> = {}
  for (const field of fields) {
    const value = row[field.name]
    if (field.type === 'file') initial[field.name] = null
    else if (field.type === 'checkbox') initial[field.name] = asBoolean(value)
    else if (field.type === 'number') {
      initial[field.name] = value === null || value === undefined || value === '' ? null : value
    } else if (value === null || value === undefined) {
      initial[field.name] = field.name === 'currency' ? 'USD' : ''
    } else initial[field.name] = value
  }
  return initial
}

function jsonValue(field: AdminField, value: unknown): unknown {
  if (field.type === 'checkbox') return asBoolean(value)
  if (field.type === 'number') {
    const text = typeof value === 'string' ? value.trim() : value
    if (text === '' || text === null || text === undefined) return null
    const number = typeof text === 'number' ? text : Number(text)
    return Number.isFinite(number) ? number : null
  }
  if (value === null || value === undefined) return ''
  const text = typeof value === 'string' ? value : String(value)
  if (field.name === 'currency') return text.trim().toUpperCase()
  if (field.name === 'slug') return text.trim()
  return text
}

function omitField(field: AdminField, normalized: unknown): boolean {
  // An empty slug is omitted so the server can generate one.
  return field.name === 'slug' && (normalized === '' || normalized === null)
}

export function scalarBody(
  fields: readonly AdminField[],
  values: Record<string, unknown>,
): Record<string, unknown> {
  const body: Record<string, unknown> = {}
  for (const field of fields) {
    if (field.type === 'file') continue
    const normalized = jsonValue(field, values[field.name])
    if (omitField(field, normalized)) continue
    body[field.name] = normalized
  }
  return body
}

export function toRequestBody(
  fields: readonly AdminField[],
  values: Record<string, unknown>,
): Record<string, unknown> | FormData {
  const hasFile = fields.some((field) => field.type === 'file' && values[field.name] instanceof File)
  if (!hasFile) return scalarBody(fields, values)

  const form = new FormData()
  for (const field of fields) {
    const value = values[field.name]
    if (field.type === 'file') {
      if (value instanceof File) form.append(field.name, value)
      continue
    }
    const normalized = jsonValue(field, value)
    if (omitField(field, normalized)) continue
    if (typeof normalized === 'boolean') form.append(field.name, normalized ? '1' : '0')
    else if (normalized === null) {
      if (field.type !== 'number') form.append(field.name, '')
    } else form.append(field.name, String(normalized))
  }
  return form
}

export function tableColumns(fields: readonly AdminField[]): AdminColumn[] {
  const label = fields.find(
    (field) => field.name !== 'slug' && (field.type === 'text' || field.type === 'textarea'),
  )
  const extras = fields.filter((field) => columnKeys.includes(field.name))
  const chosen: AdminField[] = []
  for (const field of label ? [label, ...extras] : extras) {
    if (!chosen.some((item) => item.name === field.name)) chosen.push(field)
  }
  return [
    { key: 'id', label: 'ID' },
    ...chosen.map((field) => ({ key: field.name, label: field.label })),
  ]
}

export function readFeatures(source: unknown): FeatureDraft[] {
  if (!isRecord(source) || !Array.isArray(source.features)) return []
  const features: FeatureDraft[] = []
  for (const item of source.features) {
    if (!isRecord(item)) continue
    features.push({
      label_en: typeof item.label_en === 'string' ? item.label_en : '',
      label_ar: typeof item.label_ar === 'string' ? item.label_ar : '',
    })
  }
  return features
}

export function readImages(source: unknown): GalleryImage[] {
  if (!isRecord(source) || !Array.isArray(source.images)) return []
  const images: GalleryImage[] = []
  for (const item of source.images) {
    if (!isRecord(item) || typeof item.id !== 'number') continue
    images.push({
      id: item.id,
      url: typeof item.url === 'string' ? item.url : '',
      alt_en: typeof item.alt_en === 'string' ? item.alt_en : '',
      alt_ar: typeof item.alt_ar === 'string' ? item.alt_ar : '',
      is_cover: item.is_cover === true,
      sort_order: typeof item.sort_order === 'number' ? item.sort_order : 0,
    })
  }
  return images
}

export function unmatchedErrors(fields: readonly AdminField[], errors: FieldErrors): string {
  const known = new Set(fields.map((field) => field.name))
  const messages: string[] = []
  for (const [key, value] of Object.entries(errors)) {
    if (known.has(key)) continue
    const message = value[0]
    if (message) messages.push(message)
  }
  return messages.join(' ')
}
