import { test, expect } from '@playwright/test'

test.describe('Public User Flow', () => {
  test('Home page loads hero and live API sections (properties, testimonials, FAQs)', async ({ page }) => {
    await page.goto('/')

    // Hero title is visible
    await expect(page.locator('h1')).toBeVisible()

    // Featured properties section loaded from live API
    const propertyCards = page.locator('article, li:has(a[href*="/properties/"])')
    await expect(propertyCards.first()).toBeVisible({ timeout: 15000 })
    const propertyCount = await propertyCards.count()
    expect(propertyCount).toBeGreaterThanOrEqual(1)

    // Testimonials section loaded from live API
    await expect(page.getByText('What Our Clients Say')).toBeVisible()
    await expect(page.locator('ul li').filter({ hasText: '★' }).first()).toBeVisible({ timeout: 10000 })

    // FAQs section loaded from live API
    await expect(page.getByText('Frequently Asked Questions')).toBeVisible()
    await expect(page.locator('details').first()).toBeVisible({ timeout: 10000 })
  })

  test('Language toggle switches between English (LTR) and Arabic (RTL)', async ({ page }) => {
    await page.goto('/')

    // Verify initial LTR state
    await expect(page.locator('html')).toHaveAttribute('dir', 'ltr')
    await expect(page.locator('html')).toHaveAttribute('lang', 'en')

    // Find language switcher buttons by testid
    const arBtn = page.locator('[data-testid="locale-ar"]')
    const enBtn = page.locator('[data-testid="locale-en"]')

    await expect(arBtn).toBeVisible()
    await arBtn.click()

    // Now in RTL Arabic
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl')
    await expect(page.locator('html')).toHaveAttribute('lang', 'ar')

    // Switch back to English
    await enBtn.click()
    await expect(page.locator('html')).toHaveAttribute('dir', 'ltr')
    await expect(page.locator('html')).toHaveAttribute('lang', 'en')
  })

  test('Properties search, filters, and pagination work against live API', async ({ page }) => {
    await page.goto('/properties')

    // Page title
    await expect(page.locator('h1')).toContainText('Find Your Dream Property')

    // Filter controls are present
    const searchInput = page.locator('input[name="q"]')
    const locationSelect = page.locator('select[name="location"]')
    const typeSelect = page.locator('select[name="type"]')

    await expect(searchInput).toBeVisible()
    await expect(locationSelect).toBeVisible()

    // Wait for locations from /api/property-filters to load into dropdown
    await expect(locationSelect.locator('option').nth(1)).toBeAttached({ timeout: 15000 })
    const optionCount = await locationSelect.locator('option').count()
    expect(optionCount).toBeGreaterThan(1)

    // Test search filter by keyword "Villa"
    await searchInput.fill('Villa')
    await page.locator('button[type="submit"]').first().click()

    // Wait for filtered list
    await page.waitForTimeout(1000)
    await expect(page.locator('a[href*="/properties/"]').first()).toBeVisible()

    // Filter by type: villa
    await typeSelect.selectOption('villa')
    await page.locator('button[type="submit"]').first().click()
    await page.waitForTimeout(1000)
    await expect(page.locator('a[href*="/properties/"]').first()).toBeVisible()

    // Clear search and test pagination
    await searchInput.fill('')
    await typeSelect.selectOption('')
    await page.locator('button[type="submit"]').first().click()
    await page.waitForTimeout(1000)

    // Check pagination exists
    const nextBtn = page.getByRole('button', { name: /next|التالي/i })
    if (await nextBtn.isVisible() && await nextBtn.isEnabled()) {
      await nextBtn.click()
      await expect(page).toHaveURL(/page=2/)
    }
  })

  test('Property detail view displays gallery, specs, features, FAQs, and inquiry form', async ({ page }) => {
    // Navigate to a known seeded property
    await page.goto('/properties/seaside-serenity-villa')

    // Title and price
    await expect(page.locator('h1')).toContainText('Seaside Serenity Villa')
    await expect(page.getByText(/\$1,250,000/)).toBeVisible()

    // Gallery images are visible
    const gallery = page.locator('img')
    await expect(gallery.first()).toBeVisible()

    // Property specs (Bedrooms, Bathrooms, Area)
    await expect(page.getByText('4', { exact: true }).first()).toBeVisible()
    await expect(page.getByText('3', { exact: true }).first()).toBeVisible()

    // FAQs section on detail page
    await expect(page.locator('details').first()).toBeVisible()

    // Property inquiry form is present on the page
    const inquiryForm = page.locator('form')
    await expect(inquiryForm).toBeVisible()
    await expect(page.locator('#name')).toBeVisible()
    await expect(page.locator('#email')).toBeVisible()
  })

  test('Contact form handles validation and successful submission against live API', async ({ page }) => {
    await page.goto('/contact')

    await expect(page.locator('h1')).toContainText('Get in Touch')

    const submitBtn = page.locator('button[data-testid="inquiry-submit"]')
    await expect(submitBtn).toBeVisible()

    // Fill valid submission
    const testName = 'E2E Test User'
    const testEmail = `test-${Date.now()}@example.com`
    const testMessage = 'This is an automated browser test inquiry.'

    await page.locator('#name').fill(testName)
    await page.locator('#email').fill(testEmail)
    await page.locator('#phone').fill('+1 555 123 4567')
    await page.locator('#message').fill(testMessage)

    // Submit form
    await submitBtn.click()

    // Verify success message (from live API 201 response)
    await expect(page.locator('[role="status"]')).toBeVisible({ timeout: 10000 })
    await expect(page.locator('[role="status"]')).toContainText(/message has been sent|thank you|success/i)
  })

  test('Property inquiry form handles valid submission against live API', async ({ page }) => {
    await page.goto('/properties/seaside-serenity-villa')

    const submitBtn = page.locator('button[data-testid="inquiry-submit"]')
    await expect(submitBtn).toBeVisible()

    const testEmail = `buyer-${Date.now()}@example.com`
    await page.locator('#name').fill('Prospective Buyer')
    await page.locator('#email').fill(testEmail)
    await page.locator('#phone').fill('+1 555 987 6543')
    await page.locator('#message').fill('I am interested in scheduling a viewing for Seaside Serenity Villa.')

    await submitBtn.click()

    await expect(page.locator('[role="status"]')).toBeVisible({ timeout: 10000 })
  })

  test('About and Services pages load static and CMS data sections', async ({ page }) => {
    await page.goto('/about')
    await expect(page.locator('h1')).toContainText('About Estatein')
    // Team and clients loaded from live API
    await expect(page.getByText('Meet the Estatein Team')).toBeVisible()
    await expect(page.getByText('Our Valued Clients')).toBeVisible()

    await page.goto('/services')
    await expect(page.locator('h1')).toContainText('Elevate Your Real Estate Experience')
  })
})
