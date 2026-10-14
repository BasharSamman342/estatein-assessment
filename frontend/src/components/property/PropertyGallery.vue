<script setup lang="ts">
import { computed, shallowRef } from 'vue'
import { storeToRefs } from 'pinia'
import { useLocaleStore } from '../../stores/locale'
import { textOf } from '../../utils/text'

interface GalleryImage {
  id: number
  url?: string
  is_cover?: boolean
}

const props = defineProps<{
  images: GalleryImage[]
}>()

const { locale } = storeToRefs(useLocaleStore())
const selectedId = shallowRef<number | null>(null)

const active = computed(() => {
  const selected = props.images.find((image) => image.id === selectedId.value)
  if (selected) return selected
  const cover = props.images.find((image) => image.is_cover)
  return cover ?? props.images[0] ?? null
})

const activeUrl = computed(() => (typeof active.value?.url === 'string' ? active.value.url : ''))
const activeId = computed(() => active.value?.id ?? null)

function imageUrl(image: GalleryImage): string {
  return typeof image.url === 'string' ? image.url : ''
}

function selectImage(id: number) {
  selectedId.value = id
}
</script>

<template>
  <div v-if="active" class="flex flex-col gap-5 font-sans text-ink">
    <img
      v-if="activeUrl"
      :src="activeUrl"
      :alt="textOf(active, 'alt', locale)"
      class="aspect-[16/10] w-full rounded-xl object-cover"
    />
    <ul class="grid grid-cols-3 gap-[10px] md:grid-cols-4 md:gap-5">
      <li v-for="image in images" :key="image.id">
        <button
          type="button"
          class="block w-full overflow-hidden rounded-lg border-2"
          :class="image.id === activeId ? 'border-accent' : 'border-line'"
          :aria-pressed="image.id === activeId"
          @click="selectImage(image.id)"
        >
          <img
            v-if="imageUrl(image)"
            :src="imageUrl(image)"
            :alt="textOf(image, 'alt', locale)"
            class="aspect-[4/3] w-full object-cover"
          />
        </button>
      </li>
    </ul>
  </div>
</template>
