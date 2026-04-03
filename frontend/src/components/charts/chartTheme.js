export const PALETTE = [
  '#4f46e5', '#7c3aed', '#0ea5e9', '#10b981',
  '#f59e0b', '#ef4444', '#ec4899', '#14b8a6',
]

export const PALETTE_ALPHA = (hex, a = 0.15) => {
  const r = parseInt(hex.slice(1, 3), 16)
  const g = parseInt(hex.slice(3, 5), 16)
  const b = parseInt(hex.slice(5, 7), 16)
  return `rgba(${r},${g},${b},${a})`
}

export const baseFont = { family: "'Inter', 'Segoe UI', sans-serif", size: 12 }

export const gridColor = '#f0f0f0'

export const tooltipDefaults = {
  backgroundColor: '#1a202c',
  titleColor: '#fff',
  bodyColor: '#e2e8f0',
  padding: 10,
  cornerRadius: 8,
  displayColors: true,
}

export const legendDefaults = {
  position: 'bottom',
  labels: { font: baseFont, padding: 16, usePointStyle: true, pointStyleWidth: 10 },
}
