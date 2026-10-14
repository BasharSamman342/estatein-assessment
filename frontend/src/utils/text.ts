export function textOf(
  record: object | null | undefined,
  field: string,
  locale: string,
): string {
  const value = (record as Record<string, unknown> | null | undefined)?.[`${field}_${locale}`]
  return typeof value === 'string' ? value : ''
}
