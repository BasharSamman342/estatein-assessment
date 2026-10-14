import { mount } from '@vue/test-utils'
import AdminRecordForm from './AdminRecordForm.vue'
import type { AdminField } from './fields'

const fields: AdminField[] = [
  { name: 'quote_en', label: 'Quote (English)', type: 'textarea', required: true },
  { name: 'is_published', label: 'Published', type: 'checkbox' },
]

test('emits the field values and disables the button while pending', async () => {
  const wrapper = mount(AdminRecordForm, {
    props: {
      fields,
      initial: { quote_en: '', is_published: false },
      pending: true,
      errors: {},
      submitLabel: 'Save',
    },
  })
  expect(wrapper.get('[data-testid="admin-save"]').attributes('disabled')).toBeDefined()
  await wrapper.setProps({ pending: false })
  await wrapper.get('textarea').setValue('A calm purchase.')
  await wrapper.get('form').trigger('submit')
  const submitted = wrapper.emitted('submit')?.[0]?.[0]
  expect(submitted).toMatchObject({ quote_en: 'A calm purchase.', is_published: false })
})
