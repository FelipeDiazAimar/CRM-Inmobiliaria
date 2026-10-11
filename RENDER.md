# Deploy en Render — CRM Inmobiliaria

Render no tiene runtime PHP nativo, por eso se deploya con **Docker**
(`Dockerfile` + `render.yaml` ya incluidos en el repo).

## Opción A: demo sin base de datos (2 minutos)

1. Pusheá `main` a GitHub (ya incluye `Dockerfile`, `render.yaml`, `.dockerignore`).
2. En Render: **New → Blueprint** → conectá el repo `FelipeDiazAimar/CRM-Inmobiliaria`.
3. Render crea el servicio `crm-inmobiliaria` con `DEMO_MODE=true`.
4. Abrí la URL que te asigna (ej. `https://crm-inmobiliaria.onrender.com`).
   Entrá a `/pages/inicio.php`, `/pages/propiedades.php`, etc.

Sin MySQL la app usa datos de ejemplo en sesión. Ideal para mostrar el portfolio.
En plan free, el servicio se duerme tras inactividad (tarda ~1 min en despertar).

## Opción B: modo real con MySQL

Render **no** ofrece MySQL gestionado. Creá la base afuera (opciones gratis):

- **Railway**: New → Database → MySQL (da host/puerto/user/pass).
- **PlanetScale**: base MySQL-compatible (da host + credenciales).
- **Aiven**: free trial con MySQL.

Luego:

1. Aplicá el schema y el seed contra esa base:
   ```bash
   mysql -h TU-HOST -P 3306 -u TU-USER -p TU-DB < .sql
   mysql -h TU-HOST -P 3306 -u TU-USER -p TU-DB < demo_seed.sql
   ```
2. En Render → tu servicio → **Environment**, agregá:
   ```
   DEMO_MODE=false
   DB_HOST=TU-HOST
   DB_PORT=3306
   DB_USER=TU-USER
   DB_PASS=TU-PASS
   DB_NAME=crm-inmobiliaria
   ```
3. Save → redeploy automático.

## Notas

- El `.htaccess` bloquea por Apache el acceso web a `.sql`, `.env`,
  `Dockerfile` y `render.yaml` (el docroot es la raíz del repo).
- `healthCheckPath: /pages/index.php` — Render verifica que el servicio responde.
- Sesiones demo en plan free: al dormirse el servicio se pierde la sesión
  (normal en demos efímeras).
