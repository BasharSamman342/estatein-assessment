import { test } from '@playwright/test'
import * as path from 'node:path'

const docsScreenshots = path.resolve(process.cwd(), '../Bashar/Bashar/docs/screenshots')
const rootDocsScreenshots = path.resolve(process.cwd(), '../docs/screenshots')

test.describe('Capture Final Responsive & RTL Screenshots', () => {
  test.setTimeout(120000)

  test('Capture all viewports and locales', async ({ page }) => {
    async function saveScreenshot(filename: string) {
      await page.screenshot({ path: path.join(docsScreenshots, filename), fullPage: true })
      await page.screenshot({ path: path.join(rootDocsScreenshots, filename), fullPage: true })
    }

    // 1. Home Desktop EN
    await page.setViewportSize({ width: 1440, height: 900 })
    await page.goto('/')
    await page.locator('h1').waitFor({ state: 'visible' })
    await page.waitForTimeout(1000)
    await saveScreenshot('home-desktop-en.png')

    // 2. Home Desktop AR (RTL)
    await page.locator('[data-testid="locale-ar"]').click()
    await page.waitForTimeout(1000)
    await saveScreenshot('home-desktop-ar.png')

    // 3. Home Mobile AR (RTL)
    await page.setViewportSize({ width: 390, height: 844 })
    await page.waitForTimeout(1000)
    await saveScreenshot('home-mobile-ar.png')

    // 4. Home Mobile EN
    await page.locator('[data-testid="locale-en"]').click()
    await page.waitForTimeout(1000)
    await saveScreenshot('home-mobile-en.png')

    // 5. Properties Desktop EN
    await page.setViewportSize({ width: 1440, height: 900 })
    await page.goto('/properties')
    await page.locator('h1').waitFor({ state: 'visible' })
    await page.waitForTimeout(1000)
    await saveScreenshot('properties-desktop-en.png')

    // 6. Properties Desktop AR
    await page.locator('[data-testid="locale-ar"]').click()
    await page.waitForTimeout(1000)
    await saveScreenshot('properties-desktop-ar.png')

    // Switch back to EN
    await page.locator('[data-testid="locale-en"]').click()
    await page.waitForTimeout(500)

    // 7. Property Detail Desktop EN
    await page.goto('/properties/seaside-serenity-villa')
    await page.locator('h1').waitFor({ state: 'visible' })
    await page.waitForTimeout(1000)
    await saveScreenshot('property-detail-desktop-en.png')

    // 8. Admin Dashboard
    await page.goto('/admin/login')
    await page.locator('#email').fill('admin@estatein.test')
    await page.locator('#password').fill('password')
    await page.locator('button[data-testid="login-submit"]').click()
    await page.waitForURL('/admin')
    await page.locator('h1').waitFor({ state: 'visible' })
    await page.waitForTimeout(1000)
    await saveScreenshot('admin-dashboard.png')

    // 9. Admin Property Management
    await page.goto('/admin/properties')
    await page.locator('h1').waitFor({ state: 'visible' })
    await page.waitForTimeout(1000)
    await saveScreenshot('admin-property-editor.png')
  })
})
