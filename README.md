# ewmarket.sa — سوق رياديات

Multi-vendor marketplace (Saudi, Arabic/RTL, SAR) built on **OpenCart 3.0.2.0**
with a custom seller control panel.

| | |
|---|---|
| Platform | OpenCart 3.0.2.0 (released 2017) |
| Template engine | Twig 1.24.2 (bundled, not Composer) |
| DB | MySQL 8 / MariaDB 10.x, prefix `oc_`, mostly **MyISAM** |
| Default language | `ar` (RTL) · fallback `en-gb` |
| Currency | SAR |
| Live data snapshot | 298 products · 267 orders · 334 sellers |

## Layout

```
ewmarket.sa/
├── public_html/            ← document root
│   ├── index.php           storefront entry
│   ├── config.php          storefront config  (env-driven, gitignored)
│   ├── catalog/            storefront MVC + theme "ryadiatshop"
│   ├── admin/              admin panel  (/admin/)
│   ├── seller-cp/          seller panel (/seller-cp/) — custom, not stock OpenCart
│   ├── system/             framework, libraries, bundled Twig + vendor
│   └── image/              product images (720 MB) + generated image/cache/
├── storage/                cache, sessions, logs, uploads, composer vendor
├── database/               ewmarket_08_01_2024.sql (153 MB dump)
├── .docker/                PHP 8.3 + Apache dev image
└── docker-compose.yml
```

Three separate applications share one database and one `system/` framework:
storefront, `/admin/`, and `/seller-cp/`. Each has its own `config.php`,
`controller/`, `model/`, `language/`, `view/`.

## Running locally

```bash
docker compose up -d
```

First boot imports the 153 MB dump automatically (~2–4 min). Then:

- Storefront — http://localhost:8080/
- Admin — http://localhost:8080/admin/
- Seller panel — http://localhost:8080/seller-cp/

Reset the local admin password (local DB only):

```bash
docker compose exec -T db mysql -uroot -proot ewmarket -e "UPDATE oc_user SET salt=SUBSTRING(MD5(RAND()),1,9) WHERE user_id=1; UPDATE oc_user SET password=SHA1(CONCAT(salt,SHA1(CONCAT(salt,SHA1('YOURPASS'))))) WHERE user_id=1;"
```

Re-import the database from scratch:

```bash
docker compose down -v && docker compose up -d
```

## Configuration

The three `config.php` files derive all `DIR_*` paths from `__DIR__`, so the
project runs from any directory. URL and DB come from the environment:

| Variable | Default |
|---|---|
| `OC_URL` | `https://ewmarket.sa/` |
| `DB_HOSTNAME` | `localhost` |
| `DB_USERNAME` | `forge` |
| `DB_PASSWORD` | *(empty)* |
| `DB_DATABASE` | `ewmarket` |
| `DB_PORT` | `3306` |

`config.php` is gitignored — deploy it via the server, never commit credentials.

Note: the storefront also reads `config_url` / `config_ssl` from the `oc_setting`
table for image and asset URLs. Changing domains means updating those rows too,
not just `OC_URL`.

## Not in the repo

Two things are excluded on purpose and must be copied separately:

- `public_html/image/catalog/` — 720 MB of uploaded product images
- `database/*.sql` — the 153 MB dump

```bash
rsync -az public_html/image/catalog/ user@server:/var/www/ewmarket.sa/public_html/image/catalog/
```

## Further reading

- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) — production server setup
- [docs/PHP83-NOTES.md](docs/PHP83-NOTES.md) — what was changed to run on PHP 8.3, and what is still outstanding
