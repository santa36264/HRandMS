<template>
  <slot v-if="!caught" />
  <ErrorState
    v-else
    :title="error.title"
    :description="error.description"
    :type="error.type"
    :debug="error.debug"
    @retry="reset"
  />
</template>

<script setup>
import { ref, onErrorCaptured } from 'vue'
import ErrorState from './ErrorState.vue'

const caught = ref(false)
const error  = ref({ title: '', description: '', type: 'server', debug: null })

onErrorCaptured((err) => {
  caught.value = true
  error.value  = {
    title:       'Something went wrong',
    description: 'An unexpected error occurred in this section.',
    type:        'server',
    debug:       import.meta.env.DEV ? (err?.stack ?? String(err)) : null,
  }
  // Prevent propagation to parent boundaries
  return false
})

function reset() {
  caught.value = false
  error.value  = { title: '', description: '', type: 'server', debug: null }
}
</script>
