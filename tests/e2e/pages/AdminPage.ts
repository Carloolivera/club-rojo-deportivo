import { Page, expect } from '@playwright/test'

export class AdminPage {
    constructor(readonly page: Page) {}

    async login(email = 'admin@club.com', password = 'password') {
        await this.page.goto('/admin/login', { waitUntil: 'domcontentloaded' })
        await this.page.getByLabel('Email address').fill(email)
        await this.page.locator('input[type="password"]').fill(password)
        await this.page.getByRole('button', { name: 'Sign in' }).click()
        await this.page.waitForURL('**/admin**')
        await this.page.waitForLoadState('domcontentloaded')
    }

    async clickNav(label: string) {
        await this.page.getByRole('link', { name: label }).click()
        await this.page.waitForLoadState('networkidle')
    }
}
