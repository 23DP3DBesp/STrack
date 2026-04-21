<template>
  <div class="chart-wrapper">
    <Line
      :data="data"
      :options="options"
      :height="height"
    />
  </div>
</template>

<script setup>
import { Line } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

defineProps({
  data: Object,
  options: Object,
  height: {
    type: Number,
    default: 300
  }
})
</script>

<style scoped>
.chart-wrapper {
  height: 300px;
}

:deep(canvas) {
  max-height: 100% !important;
}
</style>
