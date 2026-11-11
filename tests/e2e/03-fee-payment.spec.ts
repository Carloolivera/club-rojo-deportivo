import { test, expect } from '@playwright/test'
import { AdminPage } from './pages/AdminPage'

test.describe('Registrar pago de cuota', () => {
    test.beforeEach(async ({ page }) => {
        await new AdminPage(page).login()
    })

    test('puede marcar una cuota pendiente como pagada', async ({ page }) => {
        const admin = new AdminPage(page)
        await admin.clickNav('Cuotas')
        await page.waitForLoadState('networkidle')

        const row = page.getByRole('row').filter({ hasText: /Juan Pérez/i }).first()
        await expect(row).toBeVisible({ timeout: 8000 })
        await row.getByRole('button', { name: /Marcar pagada/i }).click()

        // El div[role="dialog"] con x-show tiene display:none durante la transición de Alpine.
        // Verificar por el heading del modal que sí aparece en el ARIA tree.
        await expect(page.getByRole('heading', { name: 'Marcar cuota como pagada' })).toBeVisible({ timeout: 6000 })
        await page.getByRole('button', { name: 'Confirm' }).click()

        // Buscar "Pagada" en la tabla (getByText también matchea <option> ocultos, usar cell)
        await expect(page.locator('table').getByText('Pagada').first()).toBeVisible({ timeout: 8000 })
    })

    test('puede crear una cuota nueva para un socio', async ({ page }) => {
        test.setTimeout(60000)
        const admin = new AdminPage(page)
        await admin.clickNav('Cuotas')
        await page.waitForLoadState('networkidle')

        await page.locator('a, button').filter({ hasText: /New|Nuevo|Crear/i }).first().click()
        await page.waitForURL('**/fees/create', { timeout: 10000 })
        await page.waitForLoadState('networkidle')

        // Socio: custom searchable select (native: false)
        await page.getByRole('button', { name: 'Select an option' }).first().click()
        await page.waitForTimeout(300)
        await page.getByPlaceholder('Search...').fill('Juan')
        await page.waitForTimeout(500)
        await page.getByRole('option', { name: /Juan Pérez/i }).click()
        await page.waitForTimeout(300)

        const nextMonth = new Date()
        nextMonth.setMonth(nextMonth.getMonth() + 1)
        const period = `${nextMonth.getFullYear()}-${String(nextMonth.getMonth() + 1).padStart(2, '0')}`

        await page.getByLabel('Período').fill(period)
        await page.getByLabel('Monto').fill('8000')

        // Estado ya viene con "Pendiente" por default (->default('pending')) — no interactuar

        await page.getByRole('button', { name: 'Create', exact: true }).click()
        await page.waitForLoadState('networkidle')

        // Filament redirige al edit del registro nuevo tras crear
        await expect(page).toHaveURL(/fees\/\d+\/edit/, { timeout: 8000 })
        await expect(page.getByRole('textbox', { name: /Período/i })).toHaveValue(period)
    })
})
