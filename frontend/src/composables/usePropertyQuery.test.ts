import { compile, type CompilerOptions } from '@vue/compiler-dom'
import { flushPromises, mount } from '@vue/test-utils'
import { defineComponent, registerRuntimeCompiler, type RenderFunction } from 'vue'
import * as Vue from 'vue'
import { mountApp } from '../test/mountApp'
import { usePropertyQuery } from './usePropertyQuery'

// This app ships Vue's runtime-only build, which cannot compile string templates.
function compileTemplate(template: string, options?: CompilerOptions): RenderFunction {
  const { code } = compile(template, options)
  const createRender = new Function('Vue', code) as (
    vue: typeof Vue,
  ) => RenderFunction & { _rc?: boolean }
  const render = createRender(Vue)
  render._rc = true
  return render
}

registerRuntimeCompiler(compileTemplate)

const Host = defineComponent({
  setup() {
    return usePropertyQuery()
  },
  template:
    '<button data-testid="go" @click="submit({ q: \'villa\', type: \'villa\' })">Go</button>',
})

test('submit writes filters into the URL and omits the default page', async () => {
  const { router } = mountApp()
  await router.push('/properties')
  const wrapper = mount(Host, { global: { plugins: [router] } })
  await wrapper.get('[data-testid="go"]').trigger('click')
  await flushPromises()
  expect(router.currentRoute.value.query).toMatchObject({ q: 'villa', type: 'villa' })
  expect(router.currentRoute.value.query.page).toBeUndefined()
})
