-- ============================================================
-- TSOGZ — database schema
-- Target: PostgreSQL 14+
-- Maps to: index, portfolio, project, services, showroom,
-- product-yoga, contacts, request
-- (about / delivery / privacy — статичный текст, БД не нужна)
-- (team / faq — статичны, не грузятся из БД)
--
-- No external file storage: all images live in the DB as BYTEA.
-- Serve them via an app route, e.g. GET /image/:table/:id that
-- does SELECT data, mime_type FROM ... and sets Content-Type,
-- using updated_at for ETag / Last-Modified so browsers can cache.
-- ============================================================

-- Clean re-run support (dev only — remove in production)
DROP TABLE IF EXISTS leads CASCADE;
DROP TABLE IF EXISTS service_package_features CASCADE;
DROP TABLE IF EXISTS service_packages CASCADE;
DROP TABLE IF EXISTS product_images CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS collection_images CASCADE;
DROP TABLE IF EXISTS collections CASCADE;
DROP TABLE IF EXISTS product_categories CASCADE;
DROP TABLE IF EXISTS project_images CASCADE;
DROP TABLE IF EXISTS projects CASCADE;
DROP TABLE IF EXISTS project_types CASCADE;
DROP TABLE IF EXISTS company_info CASCADE;


-- ============================================================
-- 1. Project types
--    Used by: portfolio.html filters, request.html + contacts.html chips
-- ============================================================

CREATE TABLE project_types (
  id          SERIAL PRIMARY KEY,
  name        TEXT NOT NULL UNIQUE,      -- 'Квартира', 'Дом', 'Коммерция'
  slug        TEXT NOT NULL UNIQUE,      -- 'apartment', 'house', 'commercial'
  sort_order  INTEGER NOT NULL DEFAULT 0
);


-- ============================================================
-- 2. Projects (portfolio.html, project.html, index.html teasers)
-- ============================================================

CREATE TABLE projects (
  id                SERIAL PRIMARY KEY,
  title             TEXT NOT NULL,               -- 'Садовые кварталы'
  slug              TEXT NOT NULL UNIQUE,        -- 'sadovye-kvartaly'
  project_type_id   INTEGER NOT NULL REFERENCES project_types(id),
  area_m2           NUMERIC(8,1),
  style             TEXT,
  year              SMALLINT,
  duration_label    TEXT,
  intro             TEXT,
  about_title       TEXT,
  about_body        TEXT,
  is_featured       BOOLEAN NOT NULL DEFAULT FALSE,
  sort_order        INTEGER NOT NULL DEFAULT 0,
  created_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at        TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_projects_type ON projects(project_type_id);
CREATE INDEX idx_projects_featured ON projects(is_featured) WHERE is_featured = TRUE;

CREATE TABLE project_images (
  id          SERIAL PRIMARY KEY,
  project_id  INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
  data        BYTEA NOT NULL,               -- бинарник изображения
  mime_type   TEXT NOT NULL,                -- 'image/webp', 'image/jpeg', 'image/png'
  byte_size   INTEGER NOT NULL CHECK (byte_size <= 8388608),  -- max 8 MB
  width       INTEGER,                      -- для <img width/height>, чтобы не прыгал layout
  height      INTEGER,
  alt         TEXT,
  role        TEXT NOT NULL DEFAULT 'gallery'
              CHECK (role IN ('cover', 'thumb', 'hero', 'detail', 'gallery')),
  sort_order  INTEGER NOT NULL DEFAULT 0,
  updated_at  TIMESTAMPTZ NOT NULL DEFAULT now()   -- для ETag / Last-Modified при отдаче
);

CREATE INDEX idx_project_images_project ON project_images(project_id);


-- ============================================================
-- 3. Product categories (showroom.html filter chips)
-- ============================================================

CREATE TABLE product_categories (
  id          SERIAL PRIMARY KEY,
  name        TEXT NOT NULL UNIQUE,   -- 'Мебель', 'Свет', 'Декор', 'Для партнеров'
  slug        TEXT NOT NULL UNIQUE,
  sort_order  INTEGER NOT NULL DEFAULT 0
);


-- ============================================================
-- 4. Collections (featured collection card, e.g. 'Коллекция Alva')
--    Cover image lives in its own table so it carries the same
--    metadata (size/dimensions/alt) as project/product images.
-- ============================================================

CREATE TABLE collections (
  id           SERIAL PRIMARY KEY,
  name         TEXT NOT NULL,          -- 'Коллекция Alva'
  slug         TEXT NOT NULL UNIQUE,
  meta_label   TEXT,                   -- 'Мебель · в наличии'
  description  TEXT,
  is_active    BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE collection_images (
  id             SERIAL PRIMARY KEY,
  collection_id  INTEGER NOT NULL REFERENCES collections(id) ON DELETE CASCADE,
  data           BYTEA NOT NULL,
  mime_type      TEXT NOT NULL,
  byte_size      INTEGER NOT NULL CHECK (byte_size <= 8388608),
  width          INTEGER,
  height         INTEGER,
  alt            TEXT,
  sort_order     INTEGER NOT NULL DEFAULT 0,
  updated_at     TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_collection_images_collection ON collection_images(collection_id);


-- ============================================================
-- 5. Products (showroom.html catalog, product-yoga.html detail)
-- ============================================================

CREATE TABLE products (
  id                    SERIAL PRIMARY KEY,
  name                  TEXT NOT NULL,
  slug                  TEXT NOT NULL UNIQUE,
  sku                   TEXT UNIQUE,
  product_category_id   INTEGER NOT NULL REFERENCES product_categories(id),
  collection_id         INTEGER REFERENCES collections(id),
  price                 NUMERIC(10,2) NOT NULL,
  currency              TEXT NOT NULL DEFAULT 'RUB',
  material              TEXT,
  dimensions            TEXT,
  availability          TEXT,
  lead_time             TEXT,
  short_description     TEXT,
  long_description      TEXT,
  is_partner_only       BOOLEAN NOT NULL DEFAULT FALSE,  -- независимо от категории:
                                                          -- обычный товар, доступный только партнёрам
  is_active             BOOLEAN NOT NULL DEFAULT TRUE,
  sort_order            INTEGER NOT NULL DEFAULT 0,
  created_at            TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at            TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_products_category ON products(product_category_id);
CREATE INDEX idx_products_collection ON products(collection_id);

CREATE TABLE product_images (
  id          SERIAL PRIMARY KEY,
  product_id  INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  data        BYTEA NOT NULL,
  mime_type   TEXT NOT NULL,
  byte_size   INTEGER NOT NULL CHECK (byte_size <= 8388608),
  width       INTEGER,
  height      INTEGER,
  alt         TEXT,
  is_primary  BOOLEAN NOT NULL DEFAULT FALSE,
  sort_order  INTEGER NOT NULL DEFAULT 0,
  updated_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_product_images_product ON product_images(product_id);

-- Ровно одно основное фото на товар (для превью в каталоге)
CREATE UNIQUE INDEX idx_product_images_one_primary
  ON product_images(product_id) WHERE is_primary = TRUE;


-- ============================================================
-- 6. Service packages (services.html pricing rows)
-- ============================================================

CREATE TABLE service_packages (
  id             SERIAL PRIMARY KEY,
  name           TEXT NOT NULL,
  slug           TEXT NOT NULL UNIQUE,
  number_label   TEXT,                      -- '01', '02', '03' (display only)
  price_from     NUMERIC(10,2) NOT NULL,
  price_unit     TEXT NOT NULL DEFAULT '₽/м²',
  description    TEXT,
  sort_order     INTEGER NOT NULL DEFAULT 0,
  is_active      BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE service_package_features (
  id                  SERIAL PRIMARY KEY,
  service_package_id  INTEGER NOT NULL REFERENCES service_packages(id) ON DELETE CASCADE,
  feature             TEXT NOT NULL,
  sort_order          INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX idx_service_features_package ON service_package_features(service_package_id);


-- ============================================================
-- 7. Company info — single-row settings table
--    Используется на contacts.html (и в header/footer)
-- ============================================================

CREATE TABLE company_info (
  id                  SMALLINT PRIMARY KEY DEFAULT 1 CHECK (id = 1),  -- singleton row
  city_short          TEXT NOT NULL,      -- 'Санкт-Петербург' (header)
  footer_city         TEXT NOT NULL,      -- 'Москва' (footer contact line)
  phone               TEXT NOT NULL,      -- '+7 495 000–00–00'
  email               TEXT NOT NULL,      -- 'hello@tsogz.ru'
  address             TEXT NOT NULL,      -- 'ул. Патриаршие Пруды, 12'
  work_hours          TEXT NOT NULL,      -- 'Ежедневно 10:00–20:00'
  map_lat             NUMERIC(9,6),
  map_lng             NUMERIC(9,6),
  telegram_handle     TEXT,               -- '@tsogz.studio'
  instagram_handle    TEXT,
  updated_at          TIMESTAMPTZ NOT NULL DEFAULT now()
);


-- ============================================================
-- 8. Leads — both the short contacts.html form and the full
--    request.html measurement-request form write here
-- ============================================================

CREATE TABLE leads (
  id               BIGSERIAL PRIMARY KEY,
  form_type        TEXT NOT NULL
                   CHECK (form_type IN ('contact_short', 'measurement_request')),
  name             TEXT NOT NULL,
  phone            TEXT NOT NULL,
  email            TEXT,                          -- only on request.html
  project_type_id  INTEGER REFERENCES project_types(id),  -- chip on request.html
  area_m2          NUMERIC(8,1),                  -- only on request.html
  message          TEXT,
  source_page      TEXT NOT NULL,                 -- 'contacts', 'request', etc.
  consent_given    BOOLEAN NOT NULL DEFAULT FALSE,
  status           TEXT NOT NULL DEFAULT 'new'
                   CHECK (status IN ('new', 'contacted', 'scheduled', 'closed', 'spam')),
  created_at       TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_leads_status ON leads(status);
CREATE INDEX idx_leads_created ON leads(created_at DESC);