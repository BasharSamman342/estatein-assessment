export type AdminFieldType = 'text' | 'textarea' | 'number' | 'checkbox' | 'select' | 'file'

export interface AdminFieldOption {
  value: string
  label: string
}

export interface AdminField {
  name: string
  label: string
  type: AdminFieldType
  required?: boolean
  options?: readonly AdminFieldOption[]
}

const sortOrder: AdminField = {
  name: 'sort_order',
  label: 'Sort order',
  type: 'number',
  required: true,
}

const published: AdminField = {
  name: 'is_published',
  label: 'Published',
  type: 'checkbox',
  required: false,
}

const propertyTypeOptions: readonly AdminFieldOption[] = [
  { value: 'villa', label: 'Villa' },
  { value: 'apartment', label: 'Apartment' },
  { value: 'commercial', label: 'Commercial' },
]

export const testimonialFields: AdminField[] = [
  { name: 'quote_en', label: 'Quote (English)', type: 'textarea', required: true },
  { name: 'quote_ar', label: 'Quote (Arabic)', type: 'textarea', required: true },
  { name: 'author_name_en', label: 'Author name (English)', type: 'text', required: true },
  { name: 'author_name_ar', label: 'Author name (Arabic)', type: 'text', required: true },
  { name: 'role_en', label: 'Role (English)', type: 'text', required: false },
  { name: 'role_ar', label: 'Role (Arabic)', type: 'text', required: false },
  { name: 'rating', label: 'Rating', type: 'number', required: false },
  { name: 'avatar', label: 'Avatar', type: 'file', required: false },
  sortOrder,
  published,
]

export const faqFields: AdminField[] = [
  { name: 'question_en', label: 'Question (English)', type: 'textarea', required: true },
  { name: 'question_ar', label: 'Question (Arabic)', type: 'textarea', required: true },
  { name: 'answer_en', label: 'Answer (English)', type: 'textarea', required: true },
  { name: 'answer_ar', label: 'Answer (Arabic)', type: 'textarea', required: true },
  sortOrder,
  published,
]

export const teamFields: AdminField[] = [
  { name: 'name_en', label: 'Name (English)', type: 'text', required: true },
  { name: 'name_ar', label: 'Name (Arabic)', type: 'text', required: true },
  { name: 'role_en', label: 'Role (English)', type: 'text', required: true },
  { name: 'role_ar', label: 'Role (Arabic)', type: 'text', required: true },
  { name: 'bio_en', label: 'Bio (English)', type: 'textarea', required: false },
  { name: 'bio_ar', label: 'Bio (Arabic)', type: 'textarea', required: false },
  { name: 'photo', label: 'Photo', type: 'file', required: false },
  sortOrder,
  published,
]

export const clientFields: AdminField[] = [
  { name: 'name_en', label: 'Name (English)', type: 'text', required: true },
  { name: 'name_ar', label: 'Name (Arabic)', type: 'text', required: true },
  { name: 'logo', label: 'Logo', type: 'file', required: true },
  sortOrder,
  published,
]

export const propertyFields: AdminField[] = [
  { name: 'slug', label: 'Slug', type: 'text', required: false },
  { name: 'title_en', label: 'Title (English)', type: 'text', required: true },
  { name: 'title_ar', label: 'Title (Arabic)', type: 'text', required: true },
  { name: 'description_en', label: 'Description (English)', type: 'textarea', required: true },
  { name: 'description_ar', label: 'Description (Arabic)', type: 'textarea', required: true },
  { name: 'price', label: 'Price', type: 'number', required: true },
  { name: 'currency', label: 'Currency', type: 'text', required: true },
  { name: 'location_en', label: 'Location (English)', type: 'text', required: true },
  { name: 'location_ar', label: 'Location (Arabic)', type: 'text', required: true },
  { name: 'bedrooms', label: 'Bedrooms', type: 'number', required: true },
  { name: 'bathrooms', label: 'Bathrooms', type: 'number', required: true },
  { name: 'area_sqft', label: 'Area (sq ft)', type: 'number', required: true },
  { name: 'year_built', label: 'Year built', type: 'number', required: true },
  {
    name: 'property_type',
    label: 'Property type',
    type: 'select',
    required: true,
    options: propertyTypeOptions,
  },
  sortOrder,
  published,
]
