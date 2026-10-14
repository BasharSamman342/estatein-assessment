import { computed } from 'vue'
import { useRoute, useRouter, type LocationQueryRaw } from 'vue-router'

const keys = ['q', 'location', 'type', 'price_range', 'size_range', 'year_range', 'page'] as const

type PropertyQueryKey = (typeof keys)[number]

export type PropertyQueryInput = Partial<
  Record<PropertyQueryKey, string | number | null | undefined>
>

export function usePropertyQuery() {
  const route = useRoute()
  const router = useRouter()

  const params = computed(() => {
    const query: Record<string, string> = {}
    keys.forEach((key) => {
      const value = route.query[key]
      if (typeof value === 'string' && value !== '') query[key] = value
    })
    if (!query.page) query.page = '1'
    return query
  })

  function submit(next: PropertyQueryInput) {
    const query: LocationQueryRaw = {}
    keys.forEach((key) => {
      if (key === 'page') return
      const value = next[key]
      if (value !== undefined && value !== null && value !== '') query[key] = String(value)
    })
    return router.push({ name: 'properties', query })
  }

  function setPage(page: number) {
    const query: LocationQueryRaw = { ...route.query }
    if (page <= 1) delete query.page
    else query.page = String(page)
    return router.push({ name: 'properties', query })
  }

  return { params, submit, setPage }
}
