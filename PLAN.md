# Sistema de Gestión Administrativa — Club Rojo Deportivo

**Stack:** Laravel 12 · MySQL · Filament 4 · Tailwind · Alpine.js
**Repo:** demo pública derivada de un sistema real
**Cliente:** club deportivo real; en esta demo el nombre y todos los datos son ficticios
**Inicio:** 2026-04-21

---

## Flujo de trabajo Git

- **Una fase = un feature branch** → `feature/fase-N-nombre`
- **Cada acción significativa = commit inmediato** en la feature branch (no acumular trabajo sin commitear)
- **Mensajes:** Conventional Commits — `feat:`, `fix:`, `chore:`, `test:`, `refactor:`
- **PR a master** cuando la fase esté completa y tests verdes
- **Nunca commitear** `.env`, credenciales ni `dd()` / `console.log`

---

## Decisiones de arquitectura

| # | Decisión | Valor |
|---|----------|-------|
| D01 | Base de datos | MySQL (Hostinger shared compatible) |
| D02 | Panel admin | Filament 4 |
| D03 | Permisos | Spatie Permission + Policies |
| D04 | Audit log | Spatie ActivityLog |
| D05 | Auth | Laravel Fortify |
| D06 | Pagos | MercadoPago SDK — sandbox primero |
| D07 | Export Excel | Maatwebsite Excel |
| D08 | Export PDF | Laravel DomPDF |
| D09 | Seeders | Faker — 500+ socios ficticios, 12 meses cuotas |
| D10 | Compliance | Ley 25.326 full (DNI + domicilio encriptados) |
| D11 | Tests | TDD obligatorio, cobertura ≥80% |
| D12 | DNI búsqueda | `dni_hash` (SHA-256) adicional al campo encrypted |

---

## Roles y permisos

| Permiso | Admin | Encargado | Autoridad | Público |
|---------|:-----:|:---------:|:---------:|:-------:|
| Ver socios (propio deporte) | ✅ all | ✅ sport | ❌ | ❌ |
| Crear/editar socios | ✅ | ✅ sport | ❌ | ❌ |
| Registrar pago manual | ✅ | ✅ sport | ❌ | ❌ |
| Generar link MP | ✅ | ✅ sport | ❌ | ❌ |
| Ver reportes | ✅ | ❌ | ✅ | ❌ |
| Configuración global | ✅ | ❌ | ❌ | ❌ |
| Consulta pública DNI | — | — | — | ✅ rate-limited |

**IDOR crítico:** `MemberPolicy` verifica `$member->sport_id === $user->sport_id` para Encargados.

---

## Criterios de aceptación

| # | Criterio |
|---|---------|
| CA01 | Encargado fútbol accede a socio hockey → 403 + audit log |
| CA02 | Socio moroso 35 días + cron → email enviado + semáforo rojo |
| CA03 | Webhook MP firma válida → cuota `paid` + audit log |
| CA04 | Webhook MP firma inválida → 401 + log |
| CA05 | `GET /estado/{dni}` → solo "al_dia/en_deuda", rate limit 10 req/min/IP |
| CA06 | Socio menor sin responsable → falla validación |
| CA07 | Cobertura tests ≥ 80% |

---

## Checklist de fases

### Fase 0 — Scaffolding + Infraestructura ✅ COMPLETADA (2026-04-21)
- [x] `laravel new` con stack base
- [x] Instalar dependencias: Filament 4.10.2, Spatie Permission 6, Fortify 1, Maatwebsite Excel 3, DomPDF 3, MercadoPago SDK 3
- [x] `.env.example` documentado (MySQL, locale es_AR, MP keys, ALERT_DAYS_THRESHOLD)
- [x] `git init` + repo GitHub privado + commits convencionales
- [x] GitHub Actions CI: PHP 8.3 + MySQL 8.0 en cada push
- [x] PR #1 mergeado a master

### Fase 1 — Schema + Migraciones
- [ ] Migration: `sports`
- [ ] Migration: `fee_categories`
- [ ] Migration: `leave_reasons`
- [ ] Migration: `members` (con `dni_hash`, encrypted casts, soft deletes)
- [ ] Migration: `guardians`
- [ ] Migration: `member_guardian` (pivot)
- [ ] Migration: `fees` (con `mp_preference_id`, `mp_payment_id`, soft deletes)
- [ ] Migration: `member_periods`
- [ ] Migration: `reenrollments`
- [ ] Índices: `members.dni_hash`, `members.sport_id`, `fees.member_id`, `fees.status`, `fees.period`
- [ ] Factories + Seeders Faker (500 socios, 12 meses, ~70% pagadas)
- [ ] Tests: migrations limpias, factories válidas, encrypted casts

### Fase 2 — Auth + Roles + Policies ✅ COMPLETADA (2026-04-21)
- [x] Fortify: login, recuperación contraseña, recordar sesión
- [x] Seeder de roles: `admin`, `manager`, `authority`
- [x] Columna `users.sport_id` nullable (solo Encargados)
- [x] `MemberPolicy` con scope por deporte
- [x] `FeePolicy`, `ReportPolicy`
- [x] Middleware `EnsureSportScope`
- [x] Filament panel protegido con `auth`
- [x] Tests: CA01 + login rate limit + autorización por rol

### Fase 3 — Módulo Socios
- [ ] `MemberResource` Filament (list/create/edit/view)
- [ ] Filtros: estado, deporte, categoría, nombre, DNI
- [ ] Formulario con consentimiento (checkbox + timestamp + IP)
- [ ] Vinculación responsables si menor de 18
- [ ] Cambio de estado con motivo
- [ ] ActivityLog automático en cambios
- [ ] Form Request con validación completa
- [ ] Tests: CA06 + DNI duplicado + scope encargado + audit log

### Fase 4 — Módulo Cuotas + MercadoPago
- [ ] Command `fees:generate` + Schedule mensual
- [ ] `FeeResource` Filament con semáforo
- [ ] Registro pago manual
- [ ] Integración MP: crear preferencia, guardar `mp_preference_id`
- [ ] Webhook endpoint `POST /webhook/mercadopago`
- [ ] Validación HMAC signature obligatoria
- [ ] Lógica idempotente (mismo `mp_payment_id` no duplica)
- [ ] Cálculo deuda acumulada
- [ ] Tests: CA03 + CA04 + idempotencia + generación sin duplicados

### Fase 5 — Altas/Bajas + Reempadronamiento
- [ ] `MemberPeriod`: períodos por deporte
- [ ] `LeaveReason` configurable desde Filament
- [ ] Baja: estado + motivo + fecha + nota
- [ ] Workflow reempadronamiento (placeholder): disparo masivo → revisión individual
- [ ] Tests: baja con motivo, baja sin motivo falla, workflow reempadronamiento

### Fase 6 — Alertas + Cron
- [ ] Command `fees:send-alerts` con threshold configurable
- [ ] Schedule diario 9hs
- [ ] `OverdueFeeNotification` Mailable
- [ ] Log de envío (idempotente — no doble envío)
- [ ] Widget semáforo en Filament dashboard
- [ ] Tests: CA02 + idempotencia + threshold correcto

### Fase 7 — Reportes + Exportación
- [ ] Widgets KPIs en dashboard Filament
- [ ] Page de reportes: socios activos/inactivos, bajas, ingresos
- [ ] Export Excel con Maatwebsite (queued)
- [ ] Export PDF con DomPDF + branding placeholder
- [ ] Cache queries agregadas (10 min)
- [ ] Tests: KPIs correctos, export genera archivo, autorización Autoridad vs Encargado

### Fase 8 — Portal Público
- [ ] Ruta `GET /estado` con Blade + Alpine.js
- [ ] `PublicMemberController@check` — respuesta sin datos personales
- [ ] Rate limiting `throttle:10,1`
- [ ] Anti-enumeration (respuesta homogénea para inexistente)
- [ ] Log de acceso sin guardar DNI
- [ ] Tests: CA05 + anti-enumeration + sin data leak

### Fase 9 — Compliance + Hardening
- [ ] Endpoint derecho al olvido (`DELETE /socios/{id}/datos`)
- [ ] Endpoint derecho de acceso (`GET /socios/{id}/exportar`)
- [ ] Verificar encrypted casts end-to-end MySQL
- [ ] Security checklist completo (Regla 7):
  - [ ] IDOR tests verdes
  - [ ] Webhook HMAC tests verdes
  - [ ] Form Requests en todos los endpoints
  - [ ] Todas las rutas con middleware auth
  - [ ] `.env.example` sin valores reales
  - [ ] Sin `dd()` / `console.log` en producción
- [ ] Tests: derecho al olvido anonimiza + conserva historial

### Fase 10 — Seeders + QA Final
- [ ] Seeder completo con guard `App::isLocal()`
- [ ] `php artisan test --coverage` → ≥80%
- [ ] Validar todos los CAs manualmente en browser
- [ ] `README.md`: setup local, `.env` requerido, comandos cron

---

## Riesgos activos

| # | Riesgo | Severidad | Estado |
|---|--------|-----------|--------|
| R1 | IDOR Encargado cross-deporte | CRÍTICO | ✅ RESUELTO (Fase 2) |
| R2 | Webhook MP sin HMAC | CRÍTICO | Pendiente (Fase 4) |
| R3 | DNI encrypted sin búsqueda SQL | ALTO | Mitigado con `dni_hash` (Fase 1) |
| R4 | Portal público revela existencia socios | MEDIO | Pendiente (Fase 8) |
| R5 | Cron doble-ejecución emails duplicados | MEDIO | Pendiente (Fase 6) |
| R6 | Seeders corren en producción | BAJO | Mitigar con guard `isLocal()` |

---

## Preguntas abiertas del cliente

1. Deadline / hitos (temporada, asamblea, cierre fiscal)
2. Cantidad real de socios + lista exacta de deportes
3. Workflow de reempadronamiento (flag vs workflow vs self-service)
4. Valores reales de categorías de cuota
5. Medios de pago fuera de MP (transferencia, efectivo, débito)
6. Branding (logo, colores, tipografía)
7. Política de baja con deuda impaga
8. Template de emails
9. Nombre de dominio
