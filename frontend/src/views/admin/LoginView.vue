<script setup lang="ts">
import { reactive } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import FormField from '../../components/ui/FormField.vue'
import { useAuthStore } from '../../stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { errorMessage, status } = storeToRefs(auth)
const { login } = auth

const form = reactive({
  email: '',
  password: '',
})

const controlClass =
  'mt-2 w-full rounded-lg border border-line bg-canvas px-4 py-3 text-base leading-6 text-ink'

async function onSubmit() {
  const signedIn = await login({ email: form.email, password: form.password })
  if (!signedIn) return
  const redirect = route.query.redirect
  if (typeof redirect === 'string' && redirect.startsWith('/admin')) {
    await router.push(redirect)
    return
  }
  await router.push({ name: 'admin-dashboard' })
}
</script>

<template>
  <form class="flex flex-col gap-5 font-sans text-ink" @submit.prevent="onSubmit">
    <h1 class="text-3xl font-semibold">Sign in</h1>
    <p v-if="route.query.expired === '1'">Your session has ended. Sign in again.</p>
    <p v-if="errorMessage" role="alert" class="text-accent">{{ errorMessage }}</p>
    <FormField label="Email" name="email">
      <input
        id="email"
        v-model="form.email"
        class="mt-2"
        :class="controlClass"
        name="email"
        type="email"
        autocomplete="username"
        required
      />
    </FormField>
    <FormField label="Password" name="password">
      <input
        id="password"
        v-model="form.password"
        class="mt-2"
        :class="controlClass"
        name="password"
        type="password"
        autocomplete="current-password"
        required
      />
    </FormField>
    <button
      class="inline-flex w-full items-center justify-center rounded-lg bg-accent px-5 py-3 text-base font-medium leading-6 text-accent-ink disabled:cursor-not-allowed disabled:opacity-60"
      type="submit"
      data-testid="login-submit"
      :disabled="status === 'submitting'"
    >
      Sign in
    </button>
  </form>
</template>
