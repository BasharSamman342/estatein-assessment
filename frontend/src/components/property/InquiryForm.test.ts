import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createEstateI18n } from '../../i18n/index'
import { resources } from '../../api/resources'
import InquiryForm from './InquiryForm.vue'

vi.mock('../../api/resources', () => ({
  resources: { inquiries: vi.fn() },
}))

test('disables submit while the inquiry request is running', async () => {
  let resolveInquiry: (value: { id: number }) => void = () => {}
  vi.mocked(resources.inquiries).mockReturnValue(
    new Promise((done) => {
      resolveInquiry = (value) => {
        done(value)
      }
    }),
  )
  const wrapper = mount(InquiryForm, {
    props: { type: 'contact', propertyId: null },
    global: { plugins: [createPinia(), createEstateI18n('en')] },
  })
  await wrapper.get('[name="name"]').setValue('Ada')
  await wrapper.get('[name="email"]').setValue('ada@estatein.test')
  await wrapper.get('[name="message"]').setValue('Hello')
  await wrapper.get('form').trigger('submit')
  expect(wrapper.get('[data-testid="inquiry-submit"]').attributes('disabled')).toBeDefined()
  resolveInquiry({ id: 1 })
})
