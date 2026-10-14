import { onScopeDispose, ref, shallowRef } from 'vue'

export type AsyncResourceStatus = 'idle' | 'loading' | 'ready' | 'error'

export function useAsyncResource<T>(
  fetcher: (context: { signal: AbortSignal }) => Promise<T>,
) {
  const data = ref<T | null>(null)
  const error = shallowRef<unknown>(null)
  const status = shallowRef<AsyncResourceStatus>('idle')
  let controller: AbortController | null = null

  async function run(): Promise<void> {
    controller?.abort()
    const active = new AbortController()
    controller = active
    status.value = 'loading'
    error.value = null
    try {
      data.value = await fetcher({ signal: active.signal })
      if (active.signal.aborted) return
      status.value = 'ready'
    } catch (cause: unknown) {
      if (isAbortError(cause)) return
      error.value = cause
      status.value = 'error'
    }
  }

  onScopeDispose(() => {
    controller?.abort()
  })

  return { data, error, status, run }
}

function isAbortError(cause: unknown): boolean {
  return (
    typeof cause === 'object' &&
    cause !== null &&
    'name' in cause &&
    cause.name === 'AbortError'
  )
}
