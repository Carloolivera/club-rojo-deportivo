#!/usr/bin/env bash
# Publishes the committed code (HEAD) to the AIDO server.
#   bash deploy/publicar.sh            -> sube y reinicia solo los contenedores de club-rojo
#   bash deploy/publicar.sh --sembrar  -> además carga deportes, categorías y socios ficticios
# Never touches other projects on the server. Secrets are generated on the server
# the first time and never leave it.
set -euo pipefail

SSH=(/c/Windows/System32/OpenSSH/ssh.exe -o BatchMode=yes -p 5651 root@138.36.238.182)
cd "$(dirname "$0")/.."

git diff --quiet HEAD || { echo "Hay cambios sin commit: primero commit."; exit 1; }

echo "== Subiendo código ($(git rev-parse --short HEAD))"
git archive --format=tar HEAD | "${SSH[@]}" 'rm -rf /srv/club-rojo.nuevo && mkdir -p /srv/club-rojo.nuevo && tar -x -C /srv/club-rojo.nuevo && rm -rf /srv/club-rojo && mv /srv/club-rojo.nuevo /srv/club-rojo'
"${SSH[@]}" 'mkdir -p /srv/club-rojo-compose && cp /srv/club-rojo/deploy/compose.yml /srv/club-rojo-compose/compose.yml'

echo "== Variables (se crean una sola vez en el servidor)"
"${SSH[@]}" 'bash -s' <<'REMOTE'
set -euo pipefail
f=/srv/club-rojo-compose/.env
if [ ! -f "$f" ]; then
  umask 077
  cat > "$f" <<ENV
APP_NAME="Club Rojo Deportivo"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:$(openssl rand -base64 32)
APP_URL=https://club-rojo.138-36-238-182.sslip.io
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_AR
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=club_rojo
DB_USERNAME=club_rojo
DB_PASSWORD=$(openssl rand -hex 24)
DNI_KEY=$(openssl rand -hex 32)
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log
CLUB_CONTACT_EMAIL=admin@clubrojo.demo
ENV
  echo "creado $f (permisos 600)"
else
  echo "ya existía, no se toca"
fi
REMOTE

echo "== Construyendo y levantando solo club-rojo"
"${SSH[@]}" 'cd /srv/club-rojo-compose && docker compose build --pull app && docker compose up -d && docker compose ps'

if [ "${1:-}" = "--sembrar" ]; then
  echo "== Datos ficticios"
  "${SSH[@]}" 'cd /srv/club-rojo-compose && for c in RoleSeeder SportSeeder FeeCategorySeeder LeaveReasonSeeder MemberSeeder; do docker compose exec -T app php artisan db:seed --class=$c --force; done'
fi
