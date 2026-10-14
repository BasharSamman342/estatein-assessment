import { readdirSync, readFileSync, statSync } from 'node:fs'
import { join } from 'node:path'

// jsdom runs in Vite's client environment, which rewrites
// `new URL(..., import.meta.url)` into an http URL.
const srcDir = join(import.meta.dirname, '../..')

function vueFiles(dir: string, found: string[] = []): string[] {
  for (const entry of readdirSync(dir)) {
    const path = join(dir, entry)
    if (statSync(path).isDirectory()) vueFiles(path, found)
    else if (entry.endsWith('.vue')) found.push(path)
  }
  return found
}

test('templates use logical direction utilities', () => {
  const files = vueFiles(srcDir)
  expect(files.length).toBeGreaterThan(0)
  const offenders = files.flatMap((file) => {
    const text = readFileSync(file, 'utf8')
    const hit = text.match(/\b(text-left|text-right|ml-|mr-|pl-|pr-)\b/)
    const utility = hit?.[1]
    return utility ? [`${file} uses ${utility}`] : []
  })
  expect(offenders).toEqual([])
})
