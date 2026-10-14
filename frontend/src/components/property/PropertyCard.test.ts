import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createEstateI18n } from '../../i18n'
import PropertyCard from './PropertyCard.vue'

const property = {
  id: 1,
  slug: 'seaside-serenity-villa',
  title_en: 'Seaside Serenity Villa',
  title_ar: 'فيلا هدوء الساحل',
  description_en: 'A coastal home.',
  description_ar: 'منزل ساحلي.',
  price: '1250000.00',
  currency: 'USD',
  location_en: 'Malibu, 12 Coast',
  location_ar: 'ماليبو، 12 Coast',
  bedrooms: 4,
  bathrooms: 3,
  area_sqft: 2500,
  cover_image: { id: 9, url: 'https://example.test/cover.webp', alt_en: 'Villa exterior', alt_ar: 'واجهة الفيلا' },
}

afterEach(() => {
  localStorage.removeItem('estatein.locale')
})

test('renders the Arabic title and a bdi price', () => {
  localStorage.setItem('estatein.locale', 'ar')
  const wrapper = mount(PropertyCard, {
    props: { property },
    global: {
      plugins: [createPinia(), createEstateI18n('ar')],
      stubs: { RouterLink: { props: ['to'], template: '<a :href="to"><slot /></a>' } },
    },
  })
  expect(wrapper.text()).toContain('فيلا هدوء الساحل')
  expect(wrapper.get('bdi').text()).toBe('$1,250,000.00')
  expect(wrapper.get('img').attributes('alt')).toBe('واجهة الفيلا')
  expect(wrapper.get('a').attributes('href')).toBe('/properties/seaside-serenity-villa')
})
