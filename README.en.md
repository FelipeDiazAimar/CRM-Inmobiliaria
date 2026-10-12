<div align="center">

# 🏠 Real-Estate CRM — Properties, Clients & Deals

**Classic CRM in PHP 8.4 + MySQL: properties, agents, clients, appointments, interactions and transactions. Zero-database demo mode.**

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker)](https://www.docker.com/)

**[📦 Repo](https://github.com/FelipeDiazAimar/CRM-Inmobiliaria) · [🇪🇸 Español](README.md)**

</div>

## What is it?

In-house system for a real-estate agency, in **vanilla PHP with no frameworks and no Composer**: property catalog (USD prices, m², bedrooms, status, images), client files, agent roster, per-client interactions, appointment agenda and deal tracking to closing. Replaces notebooks and loose WhatsApps with a single source of truth.

## Demo (no MySQL)

```bash
git clone https://github.com/FelipeDiazAimar/CRM-Inmobiliaria.git
cd CRM-Inmobiliaria
cp .env.example .env   # keep DEMO_MODE=true
php -S localhost:8000
# 👉 http://localhost:8000/pages/index.php
```

`pages/config.php` (`demo_activo()`, lazy `db_conectar()` returning `null` without throwing) + `pages/demo_data.php` (session seeds: 8 clients, 4 agents, 12 properties, 10 interactions, 6 appointments, 6 deals). No login. Real mode: `DEMO_MODE=false` + `DB_HOST/USER/PASS/NAME/PORT`, load `.sql` + `demo_seed.sql` into MySQL 8. Docker image (`php:8.4-apache` with mysqli/pdo_mysql, listens on Render's `$PORT`) + `render.yaml` Blueprint (healthcheck `/pages/index.php`); full guide in `RENDER.md` (demo in 2 min, or real with external MySQL since Render has no managed MySQL).

## Author

**Felipe Diaz Aimar** — https://www.linkedin.com/in/felipe-aimar/ · felipediazaimar@gmail.com · https://github.com/FelipeDiazAimar · Private project, all rights reserved.
