-- ============================================================
-- TSOGZ — database schema
-- Target: PostgreSQL 14+
-- Maps 1:1 to the content on: index, portfolio, project, services,
-- showroom, product-yoga, about, contacts, request, delivery, privacy
-- ============================================================

-- Clean re-run support (dev only — remove in production)
DROP TABLE IF EXISTS leads CASCADE;
DROP TABLE IF EXISTS policy_sections CASCADE;
DROP TABLE IF EXISTS delivery_rules CASCADE;
DROP TABLE IF EXISTS studio_locations CASCADE;
DROP TABLE IF EXISTS studio_values CASCADE;
DROP TABLE IF EXISTS service_package_features CASCADE;
DROP TABLE IF EXISTS service_packages CASCADE;
DROP TABLE IF EXISTS faq_items CASCADE;
DROP TABLE IF EXISTS team_members CASCADE;
DROP TABLE IF EXISTS product_images CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS collections CASCADE;
DROP TABLE IF EXISTS product_categories CASCADE;
DROP TABLE IF EXISTS project_images CASCADE;
DROP TABLE IF EXISTS projects CASCADE;
DROP TABLE IF EXISTS project_types CASCADE;
DROP TABLE IF EXISTS company_info CASCADE;


-- ============================================================
-- 1. Project types
--    Used by: portfolio.html filters (Квартиры/Дома/Коммерция),
--    request.html + contacts.html form chips (Квартира/Дом/Коммерция)
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
  area_m2           NUMERIC(8,1),                -- 86.0
  style             TEXT,                        -- 'Современная классика'
  year              SMALLINT,                    -- 2025
  duration_label    TEXT,                        -- '10 недель'
  intro             TEXT,                        -- hero subheading on project.html
  about_title       TEXT,                        -- 'Свет, текстуры и спокойный ритм'
  about_body        TEXT,                        -- about-card paragraph
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
  url         TEXT NOT NULL,
  alt         TEXT,
  role        TEXT NOT NULL DEFAULT 'gallery'
              CHECK (role IN ('cover', 'thumb', 'hero', 'detail', 'gallery')),
  sort_order  INTEGER NOT NULL DEFAULT 0
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
-- ============================================================

CREATE TABLE collections (
  id           SERIAL PRIMARY KEY,
  name         TEXT NOT NULL,          -- 'Коллекция Alva'
  slug         TEXT NOT NULL UNIQUE,
  meta_label   TEXT,                   -- 'Мебель · в наличии'
  description  TEXT,
  cover_image  TEXT,
  is_active    BOOLEAN NOT NULL DEFAULT TRUE
);


-- ============================================================
-- 5. Products (showroom.html catalog, product-yoga.html detail)
-- ============================================================

CREATE TABLE products (
  id                    SERIAL PRIMARY KEY,
  name                  TEXT NOT NULL,             -- 'Кресло Yoga'
  slug                  TEXT NOT NULL UNIQUE,      -- 'kreslo-yoga'
  sku                   TEXT UNIQUE,               -- 'YG-01'
  product_category_id   INTEGER NOT NULL REFERENCES product_categories(id),
  collection_id         INTEGER REFERENCES collections(id),
  price                 NUMERIC(10,2) NOT NULL,    -- 89000.00
  currency              TEXT NOT NULL DEFAULT 'RUB',
  material              TEXT,                      -- 'Бук, ткань'
  dimensions            TEXT,                      -- '78 × 82 × 75 см'
  availability          TEXT,                      -- 'В шоуруме'
  lead_time             TEXT,                      -- '1–3 дня'
  short_description     TEXT,                      -- card/hero description
  long_description      TEXT,                      -- 'О товаре' block
  is_partner_only       BOOLEAN NOT NULL DEFAULT FALSE,
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
  url         TEXT NOT NULL,
  alt         TEXT,
  is_primary  BOOLEAN NOT NULL DEFAULT FALSE,
  sort_order  INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX idx_product_images_product ON product_images(product_id);


-- ============================================================
-- 6. Team (index.html "Команда")
-- ============================================================

CREATE TABLE team_members (
  id          SERIAL PRIMARY KEY,
  name        TEXT NOT NULL,     -- 'Анна К.'
  role        TEXT NOT NULL,     -- 'Ведущий дизайнер'
  photo_url   TEXT,
  sort_order  INTEGER NOT NULL DEFAULT 0,
  is_active   BOOLEAN NOT NULL DEFAULT TRUE
);


-- ============================================================
-- 7. FAQ (index.html "Частые вопросы")
-- ============================================================

CREATE TABLE faq_items (
  id          SERIAL PRIMARY KEY,
  question    TEXT NOT NULL,
  answer      TEXT NOT NULL,
  sort_order  INTEGER NOT NULL DEFAULT 0,
  is_active   BOOLEAN NOT NULL DEFAULT TRUE
);


-- ============================================================
-- 8. Service packages (services.html pricing rows)
-- ============================================================

CREATE TABLE service_packages (
  id             SERIAL PRIMARY KEY,
  name           TEXT NOT NULL,             -- 'Дизайн-проект'
  slug           TEXT NOT NULL UNIQUE,
  number_label   TEXT,                      -- '01', '02', '03' (display only)
  price_from     NUMERIC(10,2) NOT NULL,    -- 4500.00
  price_unit     TEXT NOT NULL DEFAULT '₽/м²',
  description    TEXT,
  sort_order     INTEGER NOT NULL DEFAULT 0,
  is_active      BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE service_package_features (
  id                  SERIAL PRIMARY KEY,
  service_package_id  INTEGER NOT NULL REFERENCES service_packages(id) ON DELETE CASCADE,
  feature             TEXT NOT NULL,        -- 'Обмерный план'
  sort_order          INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX idx_service_features_package ON service_package_features(service_package_id);


-- ============================================================
-- 9. Studio values (about.html "Ценности")
-- ============================================================

CREATE TABLE studio_values (
  id            SERIAL PRIMARY KEY,
  number_label  TEXT NOT NULL,   -- '01'
  title         TEXT NOT NULL,   -- 'Ясность'
  description   TEXT NOT NULL,
  sort_order    INTEGER NOT NULL DEFAULT 0
);


-- ============================================================
-- 10. Studio locations / geography (about.html "География")
-- ============================================================

CREATE TABLE studio_locations (
  id           SERIAL PRIMARY KEY,
  city         TEXT NOT NULL,   -- 'Санкт-Петербург'
  description  TEXT NOT NULL,   -- 'Студия, шоурум, замеры'
  sort_order   INTEGER NOT NULL DEFAULT 0
);


-- ============================================================
-- 11. Delivery rules (delivery.html "Условия доставки")
-- ============================================================

CREATE TABLE delivery_rules (
  id           SERIAL PRIMARY KEY,
  title        TEXT NOT NULL,   -- 'Санкт-Петербург', 'Ленинградская область', 'Крупногабарит'
  description  TEXT NOT NULL,
  sort_order   INTEGER NOT NULL DEFAULT 0
);


-- ============================================================
-- 12. Company info — single-row settings table
--     (header meta, footer, contacts.html, delivery.html pickup block)
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
-- 13. Privacy policy sections (privacy.html, numbered 1..9)
-- ============================================================

CREATE TABLE policy_sections (
  id          SERIAL PRIMARY KEY,
  number      SMALLINT NOT NULL UNIQUE,  -- 1..9
  title       TEXT NOT NULL,             -- 'Какие данные мы собираем'
  body        TEXT NOT NULL,
  sort_order  INTEGER NOT NULL DEFAULT 0
);


-- ============================================================
-- 14. Leads — both the short contacts.html form and the full
--     request.html measurement-request form write here
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
  consent_given     BOOLEAN NOT NULL DEFAULT FALSE,
  status           TEXT NOT NULL DEFAULT 'new'
                   CHECK (status IN ('new', 'contacted', 'scheduled', 'closed', 'spam')),
  created_at       TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_leads_status ON leads(status);
CREATE INDEX idx_leads_created ON leads(created_at DESC);