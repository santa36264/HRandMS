<template>
  <span class="badge" :class="`badge--${status}`">{{ label }}</span>
</template>

<script setup>
const props = defineProps({ status: { type: String, required: true } })

const labels = {
  pending:    'Pending',
  confirmed:  'Confirmed',
  checked_in: 'Checked In',
  checked_out:'Checked Out',
  cancelled:  'Cancelled',
}
const label = labels[props.status] ?? props.status
</script>

<style scoped>
.badge {
  display: inline-flex; align-items: center;
  padding: 3px 10px; border-radius: 99px;
  font-size: 11.5px; font-weight: 700; white-space: nowrap;
}
.badge--pending    { background: #fef3c7; color: #92400e; }
.badge--confirmed  { background: #d1fae5; color: #065f46; }
.badge--checked_in { background: #dbeafe; color: #1e40af; }
.badge--checked_out{ background: #f3f4f6; color: #374151; }
.badge--cancelled  { background: #fee2e2; color: #991b1b; }
</style>
