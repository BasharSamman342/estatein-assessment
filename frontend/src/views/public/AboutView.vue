<script setup lang="ts">
import { watch } from 'vue'
import { useI18n } from 'vue-i18n'
import ClientLogos from '../../components/sections/ClientLogos.vue'
import SectionHeading from '../../components/sections/SectionHeading.vue'
import TeamGrid from '../../components/sections/TeamGrid.vue'
import { resources } from '../../api/resources'
import { useAsyncResource } from '../../composables/useAsyncResource'

interface TeamMember {
  id: number
  photo_url?: string | null
}

interface ClientLogo {
  id: number
  logo_url: string
}

const { t } = useI18n()

const team = useAsyncResource(async ({ signal }) => {
  const rows = await resources.teamMembers({ signal })
  return rows as TeamMember[]
})
const clients = useAsyncResource(async ({ signal }) => {
  const rows = await resources.clients({ signal })
  return rows as ClientLogo[]
})

const { data: teamItems, status: teamStatus } = team
const { data: clientItems, status: clientStatus } = clients

watch(
  () => null,
  () => {
    void team.run()
    void clients.run()
  },
  { immediate: true },
)
</script>

<template>
  <div class="page-shell flex flex-col gap-[50px] py-[30px] font-sans text-ink md:gap-20 md:py-20">
    <h1
      class="text-[2rem] font-semibold leading-[1.2] md:text-[2.75rem] min-[1440px]:text-[3.75rem]"
    >
      {{ t('about.title') }}
    </h1>
    <section class="flex flex-col gap-10">
      <SectionHeading :title="t('about.teamTitle')" />
      <TeamGrid :items="teamItems ?? []" :status="teamStatus" @retry="team.run()" />
    </section>
    <section class="flex flex-col gap-10">
      <SectionHeading :title="t('about.clientsTitle')" />
      <ClientLogos :items="clientItems ?? []" :status="clientStatus" @retry="clients.run()" />
    </section>
  </div>
</template>
