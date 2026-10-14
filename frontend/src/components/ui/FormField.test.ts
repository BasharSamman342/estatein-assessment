import { mount } from '@vue/test-utils'
import FormField from './FormField.vue'

test('shows the first server error under the named field', () => {
  const wrapper = mount(FormField, {
    props: { label: 'Email', name: 'email', error: 'The email field is required.' },
    slots: { default: '<input id="email" />' },
  })
  expect(wrapper.get('label').text()).toBe('Email')
  expect(wrapper.get('[data-testid="field-error-email"]').text()).toBe('The email field is required.')
})
