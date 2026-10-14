import { mount } from '@vue/test-utils'
import App from '../App.vue'

test('renders the application root', () => {
  const wrapper = mount(App)
  expect(wrapper.find('[data-testid="app-root"]').exists()).toBe(true)
})
