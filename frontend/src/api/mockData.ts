export interface MockImage {
  id: number
  url: string
  alt_en: string
  alt_ar: string
  is_cover?: boolean
  sort_order?: number
}

export interface MockProperty {
  id: number
  slug: string
  title_en: string
  title_ar: string
  description_en: string
  description_ar: string
  price: number
  currency: string
  location_en: string
  location_ar: string
  bedrooms: number
  bathrooms: number
  area_sqft: number
  year_built: number
  property_type: 'villa' | 'apartment' | 'commercial'
  cover_image: MockImage
  images: MockImage[]
  features: Array<{ id: number; label_en: string; label_ar: string; sort_order: number }>
}

const photo = (id: number, url: string, alt: string): MockImage => ({
  id,
  url,
  alt_en: alt,
  alt_ar: `صورة ${alt}`,
  is_cover: true,
  sort_order: 0,
})

const propertySeeds = [
  ['seaside-serenity-villa', 'Seaside Serenity Villa', 'فيلا هدوء البحر', 'Malibu, California', 'ماليبو، كاليفورنيا', 1250000, 4, 3, 2500, 2019, 'villa', 'photo-1613490493576-7fde63acd811'],
  ['metropolitan-haven', 'Metropolitan Haven', 'ملاذ المدينة', 'New York City, USA', 'نيويورك، الولايات المتحدة', 650000, 2, 2, 1200, 2012, 'apartment', 'photo-1600607687920-4e2a09cf159d'],
  ['rustic-retreat-cottage', 'Rustic Retreat Cottage', 'كوخ ريفي هادئ', 'Asheville, North Carolina', 'آشفيل، كارولاينا الشمالية', 350000, 3, 2, 1800, 1995, 'villa', 'photo-1510798831971-661eb04b3739'],
  ['urban-loft-residence', 'Urban Loft Residence', 'سكن لوفت حضري', 'Austin, Texas', 'أوستن، تكساس', 480000, 1, 1, 950, 2021, 'apartment', 'photo-1522708323590-d24dbb6b0267'],
  ['harborfront-office-suites', 'Harborfront Office Suites', 'أجنحة مكاتب على المرفأ', 'Dubai Marina, UAE', 'مرسى دبي، الإمارات', 2400000, 0, 4, 6200, 2008, 'commercial', 'photo-1497366754035-f200968a6e72'],
  ['palm-grove-estate', 'Palm Grove Estate', 'عقار نخيل جروف', 'Palm Jumeirah, Dubai', 'نخلة جميرا، دبي', 3100000, 6, 7, 5400, 2016, 'villa', 'photo-1600585154340-be6161a56a0c'],
  ['skyline-penthouse', 'Skyline Penthouse', 'بنتهاوس سكاي لاين', 'Downtown Dubai, UAE', 'وسط مدينة دبي، الإمارات', 980000, 3, 3, 2100, 2018, 'apartment', 'photo-1600607688969-a5bfcd646154'],
  ['old-town-heritage-house', 'Old Town Heritage House', 'بيت تراثي في البلدة القديمة', 'Al Balad, Jeddah', 'البلد، جدة', 720000, 5, 4, 3000, 1987, 'villa', 'photo-1600047509807-ba8f99d2cdde'],
  ['corner-retail-space', 'Corner Retail Space', 'مساحة تجارية على زاوية', 'Hamra Street, Beirut', 'شارع الحمرا، بيروت', 500000, 0, 1, 1400, 2000, 'commercial', 'photo-1497366811353-6870744d04b2'],
] as const

export const mockProperties: MockProperty[] = propertySeeds.map((seed, index) => {
  const [slug, titleEn, titleAr, locationEn, locationAr, price, bedrooms, bathrooms, area, year, type, imageId] = seed
  const cover = photo(index * 10 + 1, `https://images.unsplash.com/${imageId}?auto=format&fit=crop&w=1200&q=80`, titleEn)
  return {
    id: index + 1,
    slug,
    title_en: titleEn,
    title_ar: titleAr,
    description_en: `A thoughtfully designed ${bedrooms || 'open-plan'} bedroom ${type} with standout finishes, generous natural light, and an exceptional ${locationEn} setting.`,
    description_ar: `عقار مميز بتصميم مدروس وتشطيبات راقية وإضاءة طبيعية وفيرة في موقع استثنائي في ${locationAr}.`,
    price,
    currency: 'USD',
    location_en: locationEn,
    location_ar: locationAr,
    bedrooms,
    bathrooms,
    area_sqft: area,
    year_built: year,
    property_type: type,
    cover_image: cover,
    images: [cover, photo(index * 10 + 2, `${cover.url}&sat=-10`, `${titleEn} interior`), photo(index * 10 + 3, `${cover.url}&con=12`, `${titleEn} detail`)],
    features: [
      { id: index * 10 + 1, label_en: 'Premium finishes', label_ar: 'تشطيبات راقية', sort_order: 0 },
      { id: index * 10 + 2, label_en: 'Dedicated parking', label_ar: 'مواقف مخصصة', sort_order: 1 },
      { id: index * 10 + 3, label_en: 'Flexible viewing times', label_ar: 'مواعيد معاينة مرنة', sort_order: 2 },
    ],
  }
})

export const mockTestimonials = [
  ['Wade Warren', 'وايد وارن', 'California, USA', 'كاليفورنيا، الولايات المتحدة', 'Estatein made finding our home feel effortless. Every detail was handled with care.', 'جعلت إستيتين رحلة العثور على منزلنا سهلة ومريحة، واهتم الفريق بكل التفاصيل.', 5],
  ['Layla Haddad', 'ليلى حداد', 'Dubai, UAE', 'دبي، الإمارات', 'Clear advice in Arabic and English helped us make a confident decision.', 'ساعدتنا النصيحة الواضحة بالعربية والإنجليزية على اتخاذ قرار واثق.', 5],
  ['Omar Al-Khatib', 'عمر الخطيب', 'Riyadh, Saudi Arabia', 'الرياض، المملكة العربية السعودية', 'Their commercial-property knowledge saved us time and gave us confidence.', 'خبرتهم في العقارات التجارية وفرت علينا الوقت ومنحتنا ثقة كبيرة.', 4],
].map(([nameEn, nameAr, roleEn, roleAr, quoteEn, quoteAr, rating], index) => ({
  id: index + 1,
  author_name_en: nameEn,
  author_name_ar: nameAr,
  role_en: roleEn,
  role_ar: roleAr,
  quote_en: quoteEn,
  quote_ar: quoteAr,
  rating,
  avatar_url: `https://i.pravatar.cc/160?img=${index + 12}`,
  sort_order: index,
}))

export const mockFaqs = [
  ['How do I search for properties?', 'كيف أبحث عن العقارات؟', 'Use the filters to narrow results by location, type, price, size, or build year.', 'استخدم عوامل التصفية لتحديد النتائج حسب الموقع والنوع والسعر والمساحة أو سنة البناء.'],
  ['Can I schedule a viewing online?', 'هل يمكنني حجز معاينة عبر الإنترنت؟', 'Yes. Open any property and send an inquiry with your preferred time.', 'نعم. افتح أي عقار وأرسل استفساراً مع الوقت الذي تفضله.'],
  ['Are listed prices negotiable?', 'هل الأسعار المعروضة قابلة للتفاوض؟', 'Many owners consider offers. Your agent can help you prepare one.', 'يقبل كثير من المالكين العروض، ويمكن لوكيلك مساعدتك في إعداد عرض مناسب.'],
].map(([questionEn, questionAr, answerEn, answerAr], index) => ({ id: index + 1, question_en: questionEn, question_ar: questionAr, answer_en: answerEn, answer_ar: answerAr, sort_order: index }))

export const mockTeamMembers = [
  ['Max Mitchell', 'ماكس ميتشل', 'Founder', 'المؤسس', 32],
  ['Sarah Johnson', 'سارة جونسون', 'Chief Real Estate Officer', 'الرئيسة التنفيذية للعقارات', 47],
  ['Nour Al-Hassan', 'نور الحسن', 'Senior Sales Agent', 'وكيلة مبيعات أولى', 44],
  ['Karim Saleh', 'كريم صالح', 'Investment Advisor', 'مستشار استثمار', 53],
].map(([nameEn, nameAr, roleEn, roleAr, avatar], index) => ({ id: index + 1, name_en: nameEn, name_ar: nameAr, role_en: roleEn, role_ar: roleAr, photo_url: `https://i.pravatar.cc/600?img=${avatar}`, sort_order: index }))

function logo(name: string, color: string): string {
  return `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="260" height="100"><rect width="100%" height="100%" rx="12" fill="${color}"/><text x="50%" y="56%" fill="white" font-family="Arial" font-size="20" font-weight="bold" text-anchor="middle">${name}</text></svg>`)}`
}

export const mockClients = [
  ['ABC Corporation', 'مؤسسة ABC', '#703bf7'], ['GreenTech', 'جرين تك', '#1b7f5f'], ['Skyline', 'سكاي لاين', '#2563eb'], ['Gulf Horizon', 'أفق الخليج', '#b45309'],
] as const

export const mockClientItems = mockClients.map(([nameEn, nameAr, color], index) => ({
  id: index + 1,
  name_en: nameEn,
  name_ar: nameAr,
  logo_url: logo(nameEn, color),
  sort_order: index,
}))

export function mockPropertyPage(params: Record<string, unknown> = {}) {
  const locale = params.locale === 'ar' ? 'ar' : 'en'
  const query = String(params.q ?? '').trim().toLocaleLowerCase()
  const filtered = mockProperties.filter((property) => {
    const title = property[`title_${locale}`].toLocaleLowerCase()
    const location = property[`location_${locale}`]
    const priceOk = !params.price_range || (params.price_range === 'lt500000' ? property.price < 500000 : params.price_range === '500000to1000000' ? property.price >= 500000 && property.price <= 1000000 : property.price > 1000000)
    const sizeOk = !params.size_range || (params.size_range === 'lt1500' ? property.area_sqft < 1500 : params.size_range === '1500to3000' ? property.area_sqft >= 1500 && property.area_sqft <= 3000 : property.area_sqft > 3000)
    const yearOk = !params.year_range || (params.year_range === 'pre2000' ? property.year_built < 2000 : params.year_range === '2000to2015' ? property.year_built >= 2000 && property.year_built <= 2015 : property.year_built > 2015)
    return (!query || title.includes(query)) && (!params.location || location === params.location) && (!params.type || property.property_type === params.type) && priceOk && sizeOk && yearOk
  })
  const perPage = Math.max(1, Number(params.per_page) || 9)
  const page = Math.max(1, Number(params.page) || 1)
  const total = filtered.length
  const lastPage = Math.max(1, Math.ceil(total / perPage))
  const currentPage = Math.min(page, lastPage)
  return {
    data: filtered.slice((currentPage - 1) * perPage, currentPage * perPage),
    meta: { current_page: currentPage, last_page: lastPage, per_page: perPage, total },
    links: { next: currentPage < lastPage ? `?page=${currentPage + 1}` : null },
  }
}

export function mockPropertyList(params: Record<string, unknown> = {}) {
  return mockPropertyPage(params).data
}

export function mockLocations(locale: string): string[] {
  const key = locale === 'ar' ? 'location_ar' : 'location_en'
  return [...new Set(mockProperties.map((property) => property[key]))].sort()
}
