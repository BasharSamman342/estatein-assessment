<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import LanguageSwitcher from './LanguageSwitcher.vue'

const route = useRoute()
const { t } = useI18n()

function linkClass(name: 'home' | 'about' | 'properties' | 'services') {
  if (route.name !== name) return ''
  return 'rounded-lg border border-line px-6 py-[0.875rem]'
}
</script>

<template>
  <header class="border-b border-line bg-canvas font-sans text-start">
    <div class="flex h-[3.9375rem] items-center justify-center px-page text-lg leading-[1.6875rem]">
      <div class="flex flex-wrap items-center justify-center gap-4">
        <p>{{ t('nav.announcement') }}</p>
        <RouterLink to="/about" class="underline underline-offset-4">{{ t('nav.learnMore') }}</RouterLink>
      </div>
    </div>
    <div
      class="flex min-h-[6.1875rem] flex-wrap items-center gap-4 px-page py-5 min-[1280px]:grid min-[1280px]:grid-cols-[1fr_auto_1fr]"
    >
      <RouterLink to="/" class="justify-self-start text-xl font-semibold leading-none"
        >Estatein</RouterLink
      >
      <nav class="flex flex-wrap items-center justify-self-center gap-4 text-lg leading-[1.6875rem]">
        <RouterLink to="/" data-testid="nav-home" class="text-ink" :class="linkClass('home')">{{ t('nav.home') }}</RouterLink>
        <RouterLink to="/about" data-testid="nav-about" class="text-ink" :class="linkClass('about')">{{ t('nav.about') }}</RouterLink>
        <RouterLink
          to="/properties"
          data-testid="nav-properties"
          class="text-ink"
          :class="linkClass('properties')"
          >{{ t('nav.properties') }}</RouterLink
        >
        <RouterLink
          to="/services"
          data-testid="nav-services"
          class="text-ink"
          :class="linkClass('services')"
          >{{ t('nav.services') }}</RouterLink
        >
      </nav>
      <div class="ms-auto flex items-center gap-4">
        <LanguageSwitcher />
        <RouterLink
          to="/contact"
          data-testid="nav-contact"
          class="rounded-lg bg-accent px-6 py-4 text-lg leading-[1.6875rem] text-accent-ink"
          >{{ t('nav.contact') }}</RouterLink
        >
      </div>
    </div>
  </header>
</template>
