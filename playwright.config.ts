import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    reporter: [['html', { open: 'never' }], ['list']],

    use: {
        baseURL: 'http://127.0.0.1:8000',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'on-first-retry',
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],

    globalSetup: './tests/e2e/global-setup.ts',

    webServer: {
        command: 'C:\\DEV\\php83\\php.exe artisan serve --host=127.0.0.1 --port=8000',
        cwd: 'C:\\DEV\\Sistema de Gestión Administrativa – Club Rojo Deportivo',
        url: 'http://127.0.0.1:8000',
        reuseExistingServer: true,
        timeout: 30000,
    },
})
