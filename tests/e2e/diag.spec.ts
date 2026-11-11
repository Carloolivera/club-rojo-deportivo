import { test } from '@playwright/test'
import { AdminPage } from './pages/AdminPage'

test('diagnose create member form', async ({ page }) => {
    const admin = new AdminPage(page)
    await admin.login()
    await admin.clickNav('Socios')
    await page.waitForLoadState('networkidle')
    await page.locator('a, button').filter({ hasText: /New|Nuevo|Crear/i }).first().click()
    await page.waitForURL('**/members/create', { timeout: 15000 })
    await page.waitForLoadState('networkidle')

    const info = await page.evaluate(() => {
        const fields = Array.from(document.querySelectorAll('input, select, [role="combobox"]'))
        return fields.slice(0, 30).map(el => ({
            tag: el.tagName,
            id: (el as HTMLElement).id,
            type: el.getAttribute('type'),
            wireModel: el.getAttribute('wire:model'),
            role: el.getAttribute('role'),
            xModel: el.getAttribute('x-model'),
        }))
    })
    console.log('INPUTS:', JSON.stringify(info, null, 2))

    const labels = await page.evaluate(() => {
        return Array.from(document.querySelectorAll('label')).map(l => ({
            text: l.textContent?.trim().replace(/\s+/g, ' '),
            for: l.getAttribute('for'),
        }))
    })
    console.log('LABELS:', JSON.stringify(labels, null, 2))
})
