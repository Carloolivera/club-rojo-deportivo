import { test, expect } from '@playwright/test'
import { AdminPage } from './pages/AdminPage'

test.describe('Enviar comunicado broadcast', () => {
    test.beforeEach(async ({ page }) => {
        await new AdminPage(page).login()
    })

    test('la página de comunicados carga correctamente', async ({ page }) => {
        const admin = new AdminPage(page)
        await admin.clickNav('Comunicados')
        await page.waitForLoadState('networkidle')

        // El $title = 'Enviar comunicado' se renderiza como h1 en x-filament-panels::page
        await expect(page.getByRole('heading', { name: /Enviar comunicado/i })).toBeVisible({ timeout: 8000 })
        // El form tiene placeholder en el input de Asunto y un textarea para el cuerpo
        await expect(page.getByPlaceholder(/Novedades del club/i)).toBeVisible()
        await expect(page.locator('textarea')).toBeVisible()
    })

    test('validación falla con campos vacíos', async ({ page }) => {
        const admin = new AdminPage(page)
        await admin.clickNav('Comunicados')
        await page.waitForLoadState('networkidle')

        await page.getByRole('button', { name: /Enviar/i }).click()
        // Laravel validation en locale 'en' → "required" en el mensaje
        await expect(page.getByText(/required|requerido/i).first()).toBeVisible({ timeout: 4000 })
    })

    test('puede enviar un broadcast con asunto y mensaje válidos', async ({ page }) => {
        test.setTimeout(30000)
        const admin = new AdminPage(page)
        await admin.clickNav('Comunicados')
        await page.waitForLoadState('networkidle')

        await page.getByPlaceholder(/Novedades del club/i).fill('Aviso importante del Club')
        await page.locator('textarea').fill('Estimados socios, les informamos que las instalaciones estarán cerradas el día sábado.')

        await page.getByRole('button', { name: /Enviar/i }).click()

        // Si hay socios activos: "Comunicado enviado a N socio(s)."
        // Si no hay socios con email: "No hay socios activos con email registrado."
        await expect(
            page.getByText(/enviado|Comunicado enviado|No hay socios/i)
        ).toBeVisible({ timeout: 10000 })
    })
})
