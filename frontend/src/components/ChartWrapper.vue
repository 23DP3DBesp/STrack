<template>
  <div class="chart-wrapper">
    <Line :data="data" :options="options" :height="height" />
  </div>
</template>

<script setup>
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend
)

defineProps({
  data: {
    type: Object,
    default: () => ({})
  },
  options: {
    type: Object,
    default: () => ({})
  },
  height: {
    type: Number,
    default: 300
  }
})
</script>
