-- ============================================================
-- TSOGZ — database schema
-- Target: PostgreSQL 14+
-- Maps to: index, portfolio, project, services, showroom,
-- product, contacts, request
-- (about / delivery / privacy — статичный текст, БД не нужна)
-- (team / faq — статичны, не грузятся из БД)
--
-- Images: в БД хранится ТОЛЬКО путь к файлу в Assets/ (TEXT),
-- сами файлы лежат на диске и отдаются веб-сервером напрямую.
-- ============================================================

-- Clean re-run support (dev only)
DROP TABLE IF EXISTS leads CASCADE;
DROP TABLE IF EXISTS service_package_features CASCADE;
DROP TABLE IF EXISTS service_packages CASCADE;
DROP TABLE IF EXISTS product_images CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS product_categories CASCADE;
DROP TABLE IF EXISTS project_images CASCADE;
DROP TABLE IF EXISTS projects CASCADE;
DROP TABLE IF EXISTS project_types CASCADE;


-- ============================================================
-- 1. Project types
-- ============================================================

CREATE TABLE project_types (
  id          SERIAL PRIMARY KEY,
  name        TEXT NOT NULL UNIQUE,
  slug        TEXT NOT NULL UNIQUE,
  sort_order  INTEGER NOT NULL DEFAULT 0
);


-- ============================================================
-- 2. Projects
-- ============================================================

CREATE TABLE projects (
  id                SERIAL PRIMARY KEY,
  title             TEXT NOT NULL,
  slug              TEXT NOT NULL UNIQUE,
  project_type_id   INTEGER NOT NULL REFERENCES project_types(id),
  area_m2           NUMERIC(8,1),
  style             TEXT,
  year              SMALLINT,
  duration_label    TEXT,
  intro             TEXT,
  about_title       TEXT,
  about_body        TEXT,
  is_featured       BOOLEAN NOT NULL DEFAULT FALSE,
  is_active         BOOLEAN NOT NULL DEFAULT TRUE,
  sort_order        INTEGER NOT NULL DEFAULT 0,
  created_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at        TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_projects_type ON projects(project_type_id);
CREATE INDEX idx_projects_featured ON projects(is_featured) WHERE is_featured = TRUE;


CREATE TABLE project_images (
  id          SERIAL PRIMARY KEY,
  project_id  INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
  path        TEXT NOT NULL,          -- '../../Assets/Photo (1).jpg'
  alt         TEXT,
  role        TEXT NOT NULL DEFAULT 'gallery'
              CHECK (role IN ('cover', 'thumb', 'hero', 'detail', 'gallery')),
  sort_order  INTEGER NOT NULL DEFAULT 0,
  updated_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_project_images_project ON project_images(project_id);


-- ============================================================
-- 3. Product categories
-- ============================================================

CREATE TABLE product_categories (
  id          SERIAL PRIMARY KEY,
  name        TEXT NOT NULL UNIQUE,
  slug        TEXT NOT NULL UNIQUE,
  sort_order  INTEGER NOT NULL DEFAULT 0
);


-- ============================================================
-- 4. Products
-- ============================================================

CREATE TABLE products (
  id                    SERIAL PRIMARY KEY,
  name                  TEXT NOT NULL,
  slug                  TEXT NOT NULL UNIQUE,
  sku                   TEXT UNIQUE,
  product_category_id   INTEGER NOT NULL REFERENCES product_categories(id),
  price                 NUMERIC(10,2) NOT NULL,
  currency              TEXT NOT NULL DEFAULT 'RUB',
  material              TEXT,
  dimensions            TEXT,
  availability          TEXT,
  lead_time             TEXT,
  short_description     TEXT,
  long_description      TEXT,
  is_partner_only       BOOLEAN NOT NULL DEFAULT FALSE,
  is_active             BOOLEAN NOT NULL DEFAULT TRUE,
  sort_order            INTEGER NOT NULL DEFAULT 0,
  created_at            TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at            TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_products_category ON products(product_category_id);


CREATE TABLE product_images (
  id          SERIAL PRIMARY KEY,
  product_id  INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  path        TEXT NOT NULL,          -- '../../Assets/Photo (7).png'
  alt         TEXT,
  is_primary  BOOLEAN NOT NULL DEFAULT FALSE,
  sort_order  INTEGER NOT NULL DEFAULT 0,
  updated_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_product_images_product ON product_images(product_id);

-- Ровно одно основное фото на товар
CREATE UNIQUE INDEX idx_product_images_one_primary
  ON product_images(product_id) WHERE is_primary = TRUE;


-- ============================================================
-- 5. Service packages
-- ============================================================

CREATE TABLE service_packages (
  id             SERIAL PRIMARY KEY,
  name           TEXT NOT NULL,
  slug           TEXT NOT NULL UNIQUE,
  number_label   TEXT,
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
-- 6. Leads
-- ============================================================

CREATE TABLE leads (
  id               BIGSERIAL PRIMARY KEY,
  form_type        TEXT NOT NULL
                   CHECK (form_type IN ('contact_short', 'measurement_request')),
  name             TEXT NOT NULL,
  phone            TEXT NOT NULL,
  email            TEXT,
  project_type_id  INTEGER REFERENCES project_types(id),
  area_m2          NUMERIC(8,1),
  message          TEXT,
  source_page      TEXT NOT NULL,
  consent_given    BOOLEAN NOT NULL DEFAULT FALSE,
  status           TEXT NOT NULL DEFAULT 'new'
                   CHECK (status IN ('new', 'contacted', 'scheduled', 'closed', 'spam')),
  created_at       TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_leads_status ON leads(status);
CREATE INDEX idx_leads_created ON leads(created_at DESC);