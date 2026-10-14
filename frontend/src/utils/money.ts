export function formatPrice(amount: unknown, currency = 'USD'): string {
  const number = Number(amount)
  if (!Number.isFinite(number)) return ''
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'USD',
  }).format(number)
}
