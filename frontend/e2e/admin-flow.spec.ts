import { test, expect } from '@playwright/test'
import * as fs from 'node:fs'
import * as path from 'node:path'

// Helper to create a valid minimal 1x1 PNG file for upload tests
function getTestImagePath(): string {
  const tmpDir = path.resolve(process.cwd(), 'e2e/fixtures')
  if (!fs.existsSync(tmpDir)) {
    fs.mkdirSync(tmpDir, { recursive: true })
  }
  const filePath = path.join(tmpDir, 'test-pixel.png')
  // 1x1 transparent PNG buffer
  const pngBuffer = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
    'base64',
  )
  fs.writeFileSync(filePath, pngBuffer)
  return filePath
}

test.describe('Admin User Flow', () => {
  test('Unauthenticated user is redirected to /admin/login', async ({ page }) => {
    // Clear localStorage to ensure guest
    await page.goto('/')
    await page.evaluate(() => localStorage.clear())

    await page.goto('/admin', { waitUntil: 'commit' })
    await expect(page).toHaveURL(/\/admin\/login/, { timeout: 10000 })
    await expect(page.locator('h1')).toContainText('Sign in')
  })

  test('Login failure with incorrect credentials shows error', async ({ page }) => {
    await page.goto('/admin/login')

    await page.locator('#email').fill('admin@estatein.test')
    await page.locator('#password').fill('wrongpassword123')
    await page.locator('button[data-testid="login-submit"]').click()

    await expect(page.locator('[role="alert"]')).toBeVisible({ timeout: 5000 })
    await expect(page.locator('[role="alert"]')).toContainText(/invalid|incorrect|credentials/i)
  })

  test('Login success with admin credentials navigates to dashboard', async ({ page }) => {
    await page.goto('/admin/login')

    await page.locator('#email').fill('admin@estatein.test')
    await page.locator('#password').fill('password')
    await page.locator('button[data-testid="login-submit"]').click()

    await expect(page).toHaveURL('/admin')
    await expect(page.locator('h1')).toContainText('Dashboard')

    // Dashboard navigation links in header
    const adminNav = page.getByRole('navigation', { name: 'Admin' })
    await expect(adminNav.getByRole('link', { name: 'Testimonials' })).toBeVisible()
    await expect(adminNav.getByRole('link', { name: 'FAQs' })).toBeVisible()
    await expect(adminNav.getByRole('link', { name: 'Properties' })).toBeVisible()
  })

  test('CMS Record CRUD: create, edit, toggle publish, and delete an FAQ', async ({ page }) => {
    // Log in
    await page.goto('/admin/login')
    await page.locator('#email').fill('admin@estatein.test')
    await page.locator('#password').fill('password')
    await page.locator('button[data-testid="login-submit"]').click()
    await expect(page).toHaveURL('/admin')

    // Navigate to FAQs
    await page.goto('/admin/faqs')
    await expect(page.locator('h1')).toContainText('FAQs')

    const uniqueTag = `E2E Test FAQ ${Date.now()}`

    // Fill form for new record
    await page.locator('#question_en').fill(uniqueTag)
    await page.locator('#question_ar').fill('سؤال تجريبي للاختبار')
    await page.locator('#answer_en').fill('This is an automated test answer.')
    await page.locator('#answer_ar').fill('هذه إجابة تجريبية للاختبار.')
    await page.locator('#sort_order').fill('99')

    // Submit create
    await page.locator('#admin-record-form button[type="submit"]').click()

    // Verify row appears in table
    await expect(page.getByText(uniqueTag)).toBeVisible({ timeout: 10000 })

    // Find the row
    const row = page.locator('tr', { hasText: uniqueTag })
    await expect(row).toBeVisible()

    // Toggle publish
    const toggleBtn = row.locator('[data-testid="admin-publish"]')
    await toggleBtn.click()
    await page.waitForTimeout(1000)

    // Edit the record
    const editBtn = row.locator('[data-testid="admin-edit"]')
    await editBtn.click()

    await expect(page.locator('#question_en')).toHaveValue(uniqueTag)
    const updatedTag = `${uniqueTag} Updated`
    await page.locator('#question_en').fill(updatedTag)
    await page.locator('#admin-record-form button[type="submit"]').click()

    await expect(page.getByText(updatedTag)).toBeVisible({ timeout: 10000 })

    // Delete the record
    page.once('dialog', (dialog) => dialog.accept())
    const updatedRow = page.locator('tr', { hasText: updatedTag })
    const deleteBtn = updatedRow.locator('[data-testid="admin-delete"]')
    await deleteBtn.click()

    await expect(page.getByText(updatedTag)).not.toBeVisible({ timeout: 10000 })
  })

  test('Image upload and replacement on Clients', async ({ page }) => {
    await page.goto('/admin/login')
    await page.locator('#email').fill('admin@estatein.test')
    await page.locator('#password').fill('password')
    await page.locator('button[data-testid="login-submit"]').click()
    await expect(page).toHaveURL('/admin')

    await page.goto('/admin/clients')
    await expect(page.locator('h1')).toContainText('Clients')

    const testImagePath = getTestImagePath()
    const uniqueClient = `E2E Client ${Date.now()}`

    // Fill client form
    await page.locator('#name_en').fill(uniqueClient)
    await page.locator('#name_ar').fill('عميل تجريبي')
    await page.locator('#sort_order').fill('99')
    await page.locator('#logo').setInputFiles(testImagePath)

    await page.locator('#admin-record-form button[type="submit"]').click()

    // Verify row created in table
    await expect(page.getByText(uniqueClient)).toBeVisible({ timeout: 10000 })

    const row = page.locator('tr', { hasText: uniqueClient })
    await expect(row).toBeVisible()

    // Edit and replace image
    await row.locator('[data-testid="admin-edit"]').click()
    await page.locator('#logo').setInputFiles(testImagePath)
    await page.locator('#admin-record-form button[type="submit"]').click()

    await page.waitForTimeout(1500)
    await expect(page.getByText(uniqueClient)).toBeVisible()

    // Clean up
    page.once('dialog', (dialog) => dialog.accept())
    await page.locator('tr', { hasText: uniqueClient }).locator('[data-testid="admin-delete"]').click()
    await expect(page.getByText(uniqueClient)).not.toBeVisible({ timeout: 10000 })
  })

  test('Property CRUD and Gallery/Feature management', async ({ page }) => {
    await page.goto('/admin/login')
    await page.locator('#email').fill('admin@estatein.test')
    await page.locator('#password').fill('password')
    await page.locator('button[data-testid="login-submit"]').click()
    await expect(page).toHaveURL('/admin')

    await page.goto('/admin/properties')
    await expect(page.locator('h1')).toContainText('Properties')

    const uniqueProp = `E2E Modern Loft ${Date.now()}`
    const testImagePath = getTestImagePath()

    // Create property draft
    await page.locator('#title_en').fill(uniqueProp)
    await page.locator('#title_ar').fill('شقة عصرية تجريبية')
    await page.locator('#description_en').fill('A beautiful loft with panoramic skyline views.')
    await page.locator('#description_ar').fill('شقة جميلة بإطلالات بانورامية على المدينة.')
    await page.locator('#location_en').fill('Dubai Marina, Dubai')
    await page.locator('#location_ar').fill('مرسى دبي، دبي')
    await page.locator('#price').fill('850000')
    await page.locator('#bedrooms').fill('2')
    await page.locator('#bathrooms').fill('2')
    await page.locator('#area_sqft').fill('1400')
    await page.locator('#year_built').fill('2022')
    await page.locator('#property_type').selectOption('apartment')

    // Submit create -> opens property editor
    await page.locator('#admin-record-form button[type="submit"]').click()

    await expect(page.getByText('Edit record')).toBeVisible({ timeout: 10000 })

    // Add a gallery image
    const fileInput = page.locator('input[type="file"]')
    if (await fileInput.isVisible()) {
      await fileInput.setInputFiles(testImagePath)
      await page.getByRole('button', { name: /upload/i }).click()
      await page.waitForTimeout(2000)
    }

    // Return to list
    await page.goto('/admin/properties')
    const row = page.locator('tr', { hasText: uniqueProp })
    await expect(row).toBeVisible({ timeout: 10000 })

    // Clean up
    page.once('dialog', (dialog) => dialog.accept())
    await row.locator('[data-testid="admin-delete"]').click()
    await expect(page.getByText(uniqueProp)).not.toBeVisible({ timeout: 10000 })
  })
})
