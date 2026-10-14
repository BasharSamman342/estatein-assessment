<script setup lang="ts">
import { ref, shallowRef } from 'vue'
import FormField from '../../components/ui/FormField.vue'
import { api, HttpError, type FieldErrors } from '../../api/client'
import type { GalleryImage } from './record'
import { imageWarning } from './record'

const props = defineProps<{
  propertyId: number
  images: GalleryImage[]
}>()

const emit = defineEmits<{
  changed: []
}>()

const altEn = shallowRef('')
const altAr = shallowRef('')
const file = shallowRef<File | null>(null)
const inputKey = shallowRef(0)
const pending = shallowRef(false)
const banner = shallowRef('')
const errors = ref<FieldErrors>({})

const controlClass =
  'mt-2 w-full rounded-lg border border-line bg-canvas px-4 py-3 text-base leading-6 text-ink'

function fieldError(name: string): string {
  return errors.value[name]?.[0] ?? ''
}

function onFile(event: Event) {
  const target = event.target
  if (!(target instanceof HTMLInputElement)) return
  file.value = target.files?.[0] ?? null
}

async function upload() {
  if (pending.value) return
  const selected = file.value
  if (!(selected instanceof File)) {
    banner.value = 'Choose an image to upload.'
    return
  }
  const warning = imageWarning(selected)
  if (warning) {
    banner.value = warning
    return
  }
  pending.value = true
  banner.value = ''
  errors.value = {}
  try {
    const body = new FormData()
    body.append('image', selected)
    body.append('alt_en', altEn.value)
    body.append('alt_ar', altAr.value)
    body.append('sort_order', String(props.images.length))
    await api.send(`/admin/properties/${props.propertyId}/images`, {
      method: 'POST',
      body,
      auth: true,
    })
    altEn.value = ''
    altAr.value = ''
    file.value = null
    inputKey.value += 1
    emit('changed')
  } catch (cause: unknown) {
    applyFailure(cause)
  } finally {
    pending.value = false
  }
}

async function makeCover(image: GalleryImage) {
  if (pending.value || image.is_cover) return
  pending.value = true
  banner.value = ''
  errors.value = {}
  try {
    await api.send(`/admin/properties/${props.propertyId}/images/${image.id}`, {
      method: 'PATCH',
      body: { is_cover: true },
      auth: true,
    })
    emit('changed')
  } catch (cause: unknown) {
    applyFailure(cause)
  } finally {
    pending.value = false
  }
}

async function removeImage(image: GalleryImage) {
  if (pending.value) return
  if (!window.confirm('Delete this image?')) return
  pending.value = true
  banner.value = ''
  errors.value = {}
  try {
    await api.send(`/admin/properties/${props.propertyId}/images/${image.id}`, {
      method: 'DELETE',
      auth: true,
    })
    emit('changed')
  } catch (cause: unknown) {
    applyFailure(cause)
  } finally {
    pending.value = false
  }
}

function applyFailure(cause: unknown) {
  if (cause instanceof HttpError && cause.status === 422) {
    errors.value = cause.errors
    const imageMessage = cause.errors.image?.[0]
    banner.value = imageMessage ?? ''
    return
  }
  banner.value = cause instanceof Error && cause.message ? cause.message : 'Request failed'
}
</script>

<template>
  <section class="flex flex-col gap-5 font-sans text-ink">
    <h2 class="text-2xl font-semibold">Images</h2>
    <p v-if="banner" role="alert" class="text-accent">{{ banner }}</p>
    <p v-if="images.length === 0" class="text-muted">No images yet.</p>
    <ul v-else class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <li v-for="image in images" :key="image.id" class="flex flex-col gap-3 rounded-xl border border-line bg-surface p-4">
        <img
          v-if="image.url"
          :src="image.url"
          :alt="image.alt_en || 'Property image'"
          class="aspect-[4/3] w-full rounded-lg object-cover"
        />
        <p v-if="image.alt_en" class="whitespace-pre-line text-sm text-muted">{{ image.alt_en }}</p>
        <p v-if="image.alt_ar" class="whitespace-pre-line text-sm text-muted" dir="auto">{{ image.alt_ar }}</p>
        <div class="flex flex-wrap gap-3">
          <button
            type="button"
            class="text-ink underline disabled:opacity-60"
            :disabled="pending || image.is_cover"
            @click="makeCover(image)"
          >
            {{ image.is_cover ? 'Cover' : 'Set as cover' }}
          </button>
          <button
            type="button"
            class="text-accent underline disabled:opacity-60"
            :disabled="pending"
            @click="removeImage(image)"
          >
            Delete
          </button>
        </div>
      </li>
    </ul>
    <form class="grid grid-cols-1 gap-5 md:grid-cols-2" @submit.prevent="upload">
      <FormField label="Image" name="image" :error="fieldError('image')" class="md:col-span-2">
        <input
          id="image"
          :key="inputKey"
          class="mt-2 block w-full text-base"
          type="file"
          accept="image/jpeg,image/png,image/webp"
          required
          @change="onFile"
        />
      </FormField>
      <FormField label="Alt text (English)" name="alt_en" :error="fieldError('alt_en')">
        <input
          id="alt_en"
          v-model="altEn"
          name="alt_en"
          type="text"
          :class="controlClass"
        />
      </FormField>
      <FormField label="Alt text (Arabic)" name="alt_ar" :error="fieldError('alt_ar')">
        <input
          id="alt_ar"
          v-model="altAr"
          name="alt_ar"
          type="text"
          dir="auto"
          :class="controlClass"
        />
      </FormField>
      <button
        class="inline-flex w-fit items-center justify-center rounded-lg bg-accent px-5 py-3 text-base font-medium text-accent-ink disabled:cursor-not-allowed disabled:opacity-60 md:col-span-2"
        type="submit"
        data-testid="admin-image-upload"
        :disabled="pending"
      >
        Upload image
      </button>
    </form>
  </section>
</template>
