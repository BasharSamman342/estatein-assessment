import { readFileSync } from 'node:fs'
import { join } from 'node:path'

test('theme tokens match the Estatein dark palette', () => {
  // jsdom runs in Vite's client environment, which rewrites
  // `new URL(..., import.meta.url)` into an http URL. Read the source file instead.
  const css = readFileSync(join(import.meta.dirname, 'main.css'), 'utf8')
  expect(css).toContain('--color-canvas: #141414;')
  expect(css).toContain('--color-surface: #1a1a1a;')
  expect(css).toContain('--color-line: #262626;')
  expect(css).toContain('--color-ink: #ffffff;')
  expect(css).toContain('--color-muted: #999999;')
  expect(css).toContain('--color-accent: #703bf7;')
  expect(css).toContain('--spacing-page: 1rem;')
})
