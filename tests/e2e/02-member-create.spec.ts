import { test, expect } from '@playwright/test'
import { AdminPage } from './pages/AdminPage'

test.describe('Crear socio', () => {
    test.beforeEach(async ({ page }) => {
        const admin = new AdminPage(page)
        await admin.login()
    })

    test('puede navegar al listado de socios', async ({ page }) => {
        const admin = new AdminPage(page)
        await admin.clickNav('Socios')
        await expect(page.getByRole('heading', { name: /Socios/i })).toBeVisible()
    })

    test('el botón crear socio está visible en el listado', async ({ page }) => {
        const admin = new AdminPage(page)
        await admin.clickNav('Socios')
        await page.waitForLoadState('networkidle')
        const createBtn = page.locator('a, button').filter({ hasText: /New|Nuevo|Crear/i }).first()
        await expect(createBtn).toBeVisible({ timeout: 10000 })
    })

    test('puede crear un socio adulto nuevo', async ({ page }) => {
        test.setTimeout(90000)
        const admin = new AdminPage(page)
        await admin.clickNav('Socios')
        await page.waitForLoadState('networkidle')

        await page.locator('a, button').filter({ hasText: /New|Nuevo|Crear/i }).first().click()
        await page.waitForURL('**/members/create', { timeout: 15000 })
        await page.waitForLoadState('networkidle')

        await page.getByLabel('Nombre completo').fill('Ana Rodríguez')
        await page.getByLabel('DNI').fill('35111222')

        // DatePicker: interacción real con el calendario flatpickr (Filament 4 / Alpine)
        // Nota: shouldCloseOnDateSelection: false — el calendario queda abierto tras seleccionar el día.
        // El estado se sincroniza a Livewire vía $wire.$entangle('data.birthdate', true).
        await page.getByRole('textbox', { name: 'Fecha de nacimiento*' }).click()
        await page.waitForSelector('[aria-expanded="true"]', { timeout: 5000 })
        await page.waitForTimeout(300)

        // Mes: combobox del calendario (único — otros selects de Filament usan buttons custom)
        await page.getByRole('combobox').selectOption({ label: 'August' })
        await page.waitForTimeout(300)

        // Año: fill + Tab para comprometer el valor antes de clickear el día.
        // Tab no cierra el calendario (shouldCloseOnDateSelection: false).
        const yearSpinner = page.getByRole('spinbutton').last()
        await yearSpinner.fill('1992')
        await yearSpinner.press('Tab')
        await page.waitForTimeout(1000)

        // Día 25: opción dentro del grid del calendario
        await page.getByRole('grid').getByRole('option', { name: '25' }).click()

        // Verificar que el datepicker comprometió "Aug 25, 1992"
        await expect(page.getByRole('button', { name: 'Aug 25, 1992' })).toBeVisible({ timeout: 5000 })

        // Esperar a que afterStateUpdated procese → Carbon::parse('1992-08-25')->age = 33 → is_minor = false
        await page.waitForLoadState('networkidle')
        await page.waitForTimeout(1000)
        await expect(page.getByText('Requerido para menores de 18 años.')).not.toBeVisible({ timeout: 15000 })

        // Selects — Filament 4 usa button "Select an option" (no getByLabel)
        await page.getByRole('button', { name: 'Select an option' }).first().click()
        await page.waitForTimeout(300)
        await page.getByRole('option', { name: 'Fútbol' }).click()
        await page.waitForTimeout(400)

        await page.getByRole('button', { name: 'Select an option' }).first().click()
        await page.waitForTimeout(300)
        await page.getByRole('option', { name: 'Adulto' }).click()
        await page.waitForTimeout(300)

        // Checkbox consentimiento (requerido)
        await page.getByRole('checkbox').click()

        await page.getByRole('button', { name: 'Create', exact: true }).click()
        await page.waitForLoadState('networkidle')

        await expect(page.getByText('Ana Rodríguez')).toBeVisible({ timeout: 10000 })
    })
})
