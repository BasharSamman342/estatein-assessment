import { textOf } from './text'

test('reads the field for the active locale', () => {
  const record = { title_en: 'Seaside Villa', title_ar: 'فيلا الساحل' }
  expect(textOf(record, 'title', 'ar')).toBe('فيلا الساحل')
  expect(textOf(record, 'title', 'en')).toBe('Seaside Villa')
})

test('returns an empty string when the field is missing', () => {
  expect(textOf({}, 'title', 'en')).toBe('')
})
