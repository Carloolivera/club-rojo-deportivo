import { test, expect } from '@playwright/test'
import { AdminPage } from './pages/AdminPage'

test.describe('Exportar Excel desde Reportes', () => {
    test.beforeEach(async ({ page }) => {
        await new AdminPage(page).login()
    })

    test('la página de reportes carga correctamente', async ({ page }) => {
        const admin = new AdminPage(page)
        await admin.clickNav('Reportes')
        await page.waitForLoadState('networkidle')

        await expect(page.getByRole('heading', { name: /Reportes/i })).toBeVisible({ timeout: 8000 })
        // Usar exact para evitar strict mode violation con "Socios inactivos"
        await expect(page.getByText('Socios activos', { exact: true })).toBeVisible()
    })

    test('el botón de exportar nómina de socios descarga un archivo', async ({ page }) => {
        test.setTimeout(45000)
        const admin = new AdminPage(page)
        await admin.clickNav('Reportes')
        await page.waitForLoadState('networkidle')

        // Los botones son Livewire actions que hacen redirect()->to(route(...))
        // Playwright captura el download cuando el browser navega a la URL con Content-Disposition: attachment
        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 15000 }),
            page.getByRole('button', { name: /Excel.*Nómina/i }).click(),
        ])

        expect(download.suggestedFilename()).toMatch(/\.xlsx$/)
    })

    test('el botón de exportar morosos descarga un archivo', async ({ page }) => {
        test.setTimeout(45000)
        const admin = new AdminPage(page)
        await admin.clickNav('Reportes')
        await page.waitForLoadState('networkidle')

        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 15000 }),
            page.getByRole('button', { name: /Excel.*Morosos/i }).click(),
        ])

        expect(download.suggestedFilename()).toMatch(/\.xlsx$/)
    })
})
