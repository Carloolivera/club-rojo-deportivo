import { test, expect } from '@playwright/test'
import { AdminPage } from './pages/AdminPage'

test.describe('Login al panel admin', () => {
    test('usuario no autenticado es redirigido al login', async ({ page }) => {
        await page.goto('/admin')
        await expect(page).toHaveURL(/admin\/login/)
    })

    test('credenciales incorrectas muestran error', async ({ page }) => {
        await page.goto('/admin/login')
        await page.getByLabel('Email address').fill('admin@club.com')
        await page.locator('input[type="password"]').fill('wrongpassword')
        await page.getByRole('button', { name: 'Sign in' }).click()
        await expect(page.getByText(/credenciales|credentials|These credentials/i)).toBeVisible()
    })

    test('admin puede iniciar sesión y ver el dashboard', async ({ page }) => {
        const admin = new AdminPage(page)
        await admin.login()
        await expect(page).toHaveURL(/\/admin/)
        await expect(page.getByRole('link', { name: /Socios|Dashboard/i }).first()).toBeVisible()
    })
})
