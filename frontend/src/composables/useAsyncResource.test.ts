import { compile, type CompilerOptions } from '@vue/compiler-dom'
import { mount } from '@vue/test-utils'
import { defineComponent, registerRuntimeCompiler, type RenderFunction } from 'vue'
import * as Vue from 'vue'
import { useAsyncResource } from './useAsyncResource'

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

test('run moves from loading to ready with the resolved data', async () => {
  const Host = defineComponent({
    setup() {
      const state = useAsyncResource(() => Promise.resolve([{ id: 4 }]))
      return state
    },
    template:
      '<p data-testid="status">{{ status }}</p><p data-testid="count">{{ data?.length ?? 0 }}</p>',
  })
  const wrapper = mount(Host)
  await wrapper.vm.run()
  expect(wrapper.get('[data-testid="status"]').text()).toBe('ready')
  expect(wrapper.get('[data-testid="count"]').text()).toBe('1')
})
