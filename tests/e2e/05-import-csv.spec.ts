import { test, expect } from '@playwright/test'
import path from 'path'
import { fileURLToPath } from 'url'
import { AdminPage } from './pages/AdminPage'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

test.describe('Importar socios desde CSV', () => {
    test.beforeEach(async ({ page }) => {
        await new AdminPage(page).login()
    })

    test('puede abrir el modal de importación CSV', async ({ page }) => {
        const admin = new AdminPage(page)
        await admin.clickNav('Socios')
        await page.waitForLoadState('networkidle')

        await page.getByRole('button', { name: /Importar CSV/i }).click()
        await expect(page.getByText(/Archivo CSV/i)).toBeVisible({ timeout: 6000 })
    })

    test('importa socios desde un archivo CSV válido', async ({ page }) => {
        test.setTimeout(60000)
        const admin = new AdminPage(page)
        await admin.clickNav('Socios')
        await page.waitForLoadState('networkidle')

        const countBefore = await page.getByRole('row').count()

        await page.getByRole('button', { name: /Importar CSV/i }).click()
        await page.waitForTimeout(500)

        const csvPath = path.resolve(__dirname, 'fixtures/members.csv')

        // Esperar el response del upload XHR de Livewire antes de continuar
        await Promise.all([
            page.waitForResponse(
                resp => resp.url().includes('livewire/upload-file') && resp.status() === 200,
                { timeout: 15000 }
            ),
            page.locator('input[type="file"]').setInputFiles(csvPath),
        ])

        // Esperar que el estado Livewire se actualice (follow-up request tras el upload)
        await page.waitForLoadState('networkidle')
        // Confirmar que el UI refleja el upload completo antes de hacer submit
        await expect(page.getByText('Upload complete', { exact: true }).first()).toBeVisible({ timeout: 10000 })
        await page.waitForTimeout(500)

        // El modal submit tiene texto "Submit" (Filament action modal default)
        await page.getByRole('dialog').getByRole('button', { name: 'Submit' }).click()

        // El título de la notificación exitosa es "Importación exitosa"
        await expect(
            page.getByText(/Importación exitosa|exitosa/i)
        ).toBeVisible({ timeout: 15000 })
        await page.waitForLoadState('networkidle')

        const countAfter = await page.getByRole('row').count()
        expect(countAfter).toBeGreaterThan(countBefore)
    })
})
