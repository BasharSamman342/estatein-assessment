import { ar, en } from './messages'

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null
}

function paths(tree: unknown, prefix = ''): string[] {
  if (!isRecord(tree)) {
    return prefix ? [prefix] : []
  }

  return Object.entries(tree).flatMap(([key, value]) => {
    const next = prefix ? `${prefix}.${key}` : key
    return isRecord(value) ? paths(value, next) : [next]
  })
}

test('Arabic and English expose the same chrome keys', () => {
  expect(paths(ar).sort()).toEqual(paths(en).sort())
})

test('home hero copy is the Figma English string', () => {
  expect(en.home.heroTitle).toBe('Discover Your Dream Property with Estatein')
  expect(ar.home.heroTitle).toBe('اكتشف عقار أحلامك مع إستيتن')
})
