# CRM-Inmobiliaria

## Modo demo (sin MySQL)

Para correr sin base de datos (ideal para revisión rápida):

```bash
# desde la raíz del repo
cp .env.example .env
# asegura DEMO_MODE=true en .env
php -S localhost:8000
# abrir http://localhost:8000/pages/index.php
```

Qué está simulado:
- `pages/config.php`: `demo_activo()` (true salvo `DEMO_MODE=false`), `db_conectar()` lazy que devuelve `null` sin lanzar si MySQL no responde, `db_o_demo()` y `db_pdo()` opcional. En modo real todo intacto.
- `pages/demo_data.php`: seeds en memoria/sesión con helpers `demo_datos()`, `demo_guardar()`, `demo_escribir()` para `clientes` (8), `agentes` (4), `propiedades` (12: casas/deptos/terrenos con precio USD, m2, dormitorios, estado), `interacciones` (10), `citas` (6), `transacciones` (6). Datos de El Salvador (Col. Escalón, San Benito, Santa Tecla, etc.).
- En demo NADA exige MySQL; los cambios se guardan solo en `$_SESSION`.

Para modo real con MySQL: pon `DEMO_MODE=false` en `.env` y configura `DB_HOST/DB_USER/DB_PASS/DB_NAME/DB_PORT`.
