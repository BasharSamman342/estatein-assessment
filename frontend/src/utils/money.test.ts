import { mount } from '@vue/test-utils'
import { formatPrice } from './money'
import BdiText from '../components/ui/BdiText.vue'

test('formats USD with Latin digits', () => {
  expect(formatPrice('1250000.00', 'USD')).toBe('$1,250,000.00')
})

test('wraps a price in bdi', () => {
  const wrapper = mount(BdiText, { props: { value: '$1,250,000.00' } })
  expect(wrapper.get('bdi').text()).toBe('$1,250,000.00')
})
