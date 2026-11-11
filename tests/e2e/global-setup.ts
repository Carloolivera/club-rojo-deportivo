import { execSync } from 'child_process'
import { existsSync, copyFileSync } from 'fs'
import { fileURLToPath } from 'url'
import { dirname, resolve, join } from 'path'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)

const PHP = 'C:\\DEV\\php83\\php.exe'
const WORKTREE = resolve(__dirname, '../../')
const MAIN_PROJECT = 'C:\\DEV\\Sistema de Gestión Administrativa – Club Rojo Deportivo'

function resolveProject(): string {
    if (existsSync(join(WORKTREE, 'vendor', 'autoload.php'))) {
        return WORKTREE
    }
    return MAIN_PROJECT
}

export default async function globalSetup() {
    const project = resolveProject()
    console.log(`\n[E2E] Proyecto: ${project}`)

    const seederSrc = join(WORKTREE, 'database/seeders/E2ESeeder.php')
    const seederDst = join(project, 'database/seeders/E2ESeeder.php')
    if (project !== WORKTREE && existsSync(seederSrc) && !existsSync(seederDst)) {
        copyFileSync(seederSrc, seederDst)
    }

    console.log('[E2E] Preparando base de datos...')
    execSync(
        `"${PHP}" artisan migrate:fresh --seed --seeder=E2ESeeder --force`,
        { cwd: project, stdio: 'inherit' }
    )
    console.log('[E2E] Base de datos lista.\n')
}
