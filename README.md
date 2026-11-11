# Club Rojo Deportivo — sistema de socios y cuotas

Sistema web para que un club deportivo administre **socios, cuotas, altas y bajas, reempadronamientos y reportes**,
con una web pública en **tres idiomas** y un portal donde cada socio consulta su situación con el DNI.

> **Es una demo.** Nació como sistema real para un club de Buenos Aires. Para poder mostrarla, se le cambió
> el nombre, la marca y todos los datos: el club "Club Rojo Deportivo" no existe y los socios son inventados.

**Autor:** Carlos Olivera — [AIDO](https://aidoagenciaweb.com.ar) · Desarrollado con Claude Code (ver [Cómo se hizo](#cómo-se-hizo)).

---

## Qué resuelve

| Problema del club | Qué hace el sistema |
|---|---|
| Las cuotas se anotaban en planillas y nadie sabía quién debía | Genera las cuotas de todos los socios el día 1 de cada mes y las marca vencidas solas |
| El socio tenía que ir a la secretaría a preguntar si estaba al día | Portal público: pone su DNI y ve si debe, sin usuario ni contraseña |
| La comisión pedía números y había que armarlos a mano | Reportes en PDF y Excel: socios por deporte, morosos, ingresos del mes |
| Datos personales en papel y en un Excel compartido | Nombre, DNI, teléfono y domicilio guardados **cifrados**; cada encargado ve solo su deporte |
| Pagos solo en efectivo | Link de pago de Mercado Pago por cuota (modo prueba) |

## Lo que se puede ver

- **Web pública** (`/`): deportes, formulario para asociarse, historia. Español, inglés y portugués.
- **Portal del socio** (`/estado`): consulta por DNI, con límite de 10 consultas por minuto.
- **Panel de administración** (`/admin`): socios, cuotas, reportes, comunicados, historial y reempadronamiento.

## Tecnologías

| Capa | Tecnología |
|---|---|
| Lenguaje | PHP 8.3 |
| Framework | Laravel 12 |
| Panel | Filament 4 |
| Base de datos | MySQL 8 (SQLite en pruebas) |
| Front | Blade, Tailwind, Alpine.js |
| Permisos y auditoría | Spatie Permission, Spatie Activity Log |
| Pruebas | Pest / PHPUnit, Playwright |

## Cómo está armado (MVC)

```
routes/web.php                    rutas públicas y de exportación (con permisos)
app/Http/Controllers/             controladores: landing, portal DNI, idioma, pagos, webhook
app/Http/Middleware/SetLocale.php aplica el idioma elegido a cada pedido
app/Models/                       Member, Fee, Sport, FeeCategory, Guardian, MemberPeriod...
app/Policies/                     quién puede ver o tocar cada cosa
app/Console/Commands/             tareas programadas: generar cuotas, marcar vencidas, avisar morosos
app/Filament/Resources/           pantallas del panel
database/migrations/              16 migraciones: la estructura de la base, versionada
lang/{es,en,pt}/                  textos de la web pública en tres idiomas
tests/                            más de 300 pruebas automáticas
```

## Problemas reales que aparecieron y cómo se resolvieron

1. **El buscador no encontraba a nadie.** Los nombres se guardan cifrados y la búsqueda comparaba contra el texto
   cifrado. Se movió el filtro a PHP después de descifrar, y para el DNI se guarda una huella (hash) que permite
   buscar por igualdad en la base sin guardar el número a la vista.
2. **Las pruebas pasaban, pero el sistema fallaba.** Ningún proceso pasaba las cuotas de "pendiente" a "vencida";
   las pruebas no lo veían porque cargaban el estado vencido a mano. Se agregó el comando `fees:mark-overdue`.
3. **Se podía abrir la cuota de otro socio** cambiando el número en la dirección del pago. Ahora la dirección va
   firmada y se verifica.
4. **Dos pruebas fallaban "a veces".** Una dependía del día del mes en que se corría (la cuota vence el día 1 del
   período); la otra generaba teléfonos con formato inválido al azar. Se fijó la fecha dentro de la prueba y el
   formato del teléfono, y ahora dan siempre el mismo resultado.

## Cómo se hizo

Trabajo con **Claude Code** como herramienta principal. Yo defino qué hay que resolver y cómo se tiene que comportar
el sistema; la IA propone e implementa; yo reviso que funcione en el navegador y con pruebas antes de darlo por
terminado. El proceso:

- Un plan por fases ([PLAN.md](PLAN.md)), una rama de Git por fase y pull request para unirla.
- Mensajes de commit con formato convencional (`feat:`, `fix:`, `test:`).
- Pruebas automáticas antes de unir; revisión de seguridad antes de publicar.

## Correrlo en tu computadora

Requisitos: PHP 8.2+, Composer 2, Node 20+ y MySQL 8 (o SQLite).

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed     # crea la base con socios de ejemplo
php artisan serve              # http://localhost:8000
```

Crear el usuario del panel (pide la contraseña sin mostrarla):

```bash
php artisan club:admin tu@correo.com
```

Pruebas:

```bash
php artisan test
```

## Publicarla en un servidor

`Dockerfile` (PHP 8.3 + Nginx) y `deploy/compose.yml`: la app, MySQL en una red interna sin salida y un
contenedor para las tareas programadas, cada uno con tope de memoria. `deploy/publicar.sh` sube el último commit,
crea las claves en el servidor la primera vez y levanta solo estos contenedores.

## Tareas programadas

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

| Comando | Cuándo | Qué hace |
|---|---|---|
| `fees:generate` | día 1 de cada mes | crea la cuota del mes para cada socio activo |
| `fees:mark-overdue` | todos los días | pasa a "vencida" toda cuota impaga de un mes anterior |
| `fees:send-alerts` | todos los días | avisa por correo a administradores si hay morosos de más de 35 días |

## Roles

| Rol | Puede |
|---|---|
| `admin` | todo, incluido borrar datos personales a pedido del socio (Ley 25.326) y exportarlos |
| `manager` | socios y cuotas de su deporte |
| `authority` | solo reportes |
