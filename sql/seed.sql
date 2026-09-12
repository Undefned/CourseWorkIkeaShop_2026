-- ============================================================
-- TSOGZ — seed data
-- Run after SCHEMA.SQL
-- ============================================================

-- ---------- 1. Project types ----------

INSERT INTO project_types (id, name, slug, sort_order) VALUES
  (1, 'Квартира',  'apartment',  1),
  (2, 'Дом',        'house',      2),
  (3, 'Коммерция',  'commercial', 3);

SELECT setval('project_types_id_seq', 3);


-- ---------- 2. Projects (portfolio.html + project.html) ----------

INSERT INTO projects
  (id, title, slug, project_type_id, area_m2, style, year, duration_label,
   intro, about_title, about_body, is_featured, sort_order)
VALUES
  (1, 'Садовые кварталы', 'sadovye-kvartaly', 1, 86.0, 'Современная классика', 2025, '10 недель',
   'Квартира в жилом комплексе — это современная классика с теплым светом и натуральными текстурами',
   'Свет, текстуры и спокойный ритм',
   'Заказчики хотели спокойный интерьер без визуального шума. Мы пересобрали планировку, усилили естественный свет и собрали мебель и декор из собственного шоурума.',
   TRUE, 1),
  (2, 'Дом в Подмосковье', 'dom-v-podmoskovie', 2, 240.0, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 2),
  (3, 'Квартира на Патриарших', 'kvartira-na-patriarshih', 1, 95.0, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 3),
  (4, 'Кафе Патриаршие', 'kafe-patriarshie', 3, 120.0, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 4),
  (5, 'Апартаменты на Патриарших', 'apartamenty-na-patriarshih', 1, 112.0, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 5),
  (6, 'Дом в Хамовниках', 'dom-v-hamovnikah', 2, 180.0, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 6),
  (7, 'Таунхаус в Сколково', 'taunhaus-v-skolkovo', 2, 195.0, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 7),
  (8, 'Квартира на Якиманке', 'kvartira-na-yakimanke', 1, 74.0, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 8);

SELECT setval('projects_id_seq', 8);

INSERT INTO project_images (project_id, url, alt, role, sort_order) VALUES
  (1, 'https://placehold.co/980x520', 'Садовые кварталы', 'cover', 1),
  (1, 'https://placehold.co/740x520', 'Садовые кварталы — интерьер', 'detail', 2),
  (1, 'https://placehold.co/380x300', 'Садовые кварталы — деталь', 'detail', 3),
  (1, 'https://placehold.co/1360x560', 'Кадр проекта', 'gallery', 4),
  (1, 'https://placehold.co/664x440', 'Кадр проекта', 'gallery', 5),
  (1, 'https://placehold.co/664x440', 'Кадр проекта', 'gallery', 6),
  (2, 'https://placehold.co/432x340', 'Дом в Подмосковье', 'cover', 1),
  (3, 'https://placehold.co/432x340', 'Квартира на Патриарших', 'cover', 1),
  (4, 'https://placehold.co/432x340', 'Кафе Патриаршие', 'cover', 1),
  (5, 'https://placehold.co/664x480', 'Апартаменты на Патриарших', 'cover', 1),
  (6, 'https://placehold.co/664x480', 'Дом в Хамовниках', 'cover', 1),
  (7, 'https://placehold.co/664x420', 'Таунхаус в Сколково', 'cover', 1),
  (8, 'https://placehold.co/664x420', 'Квартира на Якиманке', 'cover', 1);


-- ---------- 3. Product categories (showroom.html filters) ----------

INSERT INTO product_categories (id, name, slug, sort_order) VALUES
  (1, 'Мебель',          'furniture', 1),
  (2, 'Свет',            'lighting',  2),
  (3, 'Декор',           'decor',     3),
  (4, 'Для партнеров',   'partners',  4);

SELECT setval('product_categories_id_seq', 4);


-- ---------- 4. Collections ----------

INSERT INTO collections (id, name, slug, meta_label, description, cover_image, is_active) VALUES
  (1, 'Коллекция Alva', 'alva', 'Мебель · в наличии',
   'Мягкие формы, натуральные материалы и спокойная палитра — для жилых пространств.',
   'https://placehold.co/980x520', TRUE);

SELECT setval('collections_id_seq', 1);


-- ---------- 5. Products (showroom.html + product-yoga.html) ----------

INSERT INTO products
  (id, name, slug, sku, product_category_id, collection_id, price, material, dimensions,
   availability, lead_time, short_description, long_description, sort_order)
VALUES
  (1, 'Кресло Yoga', 'kreslo-yoga', 'YG-01', 1, 1, 89000.00, 'Бук, ткань', '78 × 82 × 75 см',
   'В шоуруме', '1–3 дня',
   'Мягкий силуэт, плотная ткань и спокойный тон — кресло для чтения, разговоров и долгих вечеров.',
   'Yoga собрано на каркасе из массива бука с эластичными ремнями и пенополиуретаном разной плотности. Обивка снимается для ухода. Подойдет к жилым пространствам в современной классике и минимализме.',
   1),
  (2, 'Стол MATILDA', 'stol-matilda', NULL, 1, NULL, 74000.00, NULL, NULL, NULL, NULL, NULL, NULL, 2),
  (3, 'Двуспальная кровать LAV BED', 'krovat-lav-bed', NULL, 1, NULL, 56000.00, NULL, NULL, NULL, NULL, NULL, NULL, 3),
  (4, 'Диван Atelier', 'divan-atelier', NULL, 1, NULL, 28000.00, NULL, NULL, NULL, NULL, NULL, NULL, 4),
  (5, 'Стеллаж Line', 'stellazh-line', NULL, 1, NULL, 210000.00, NULL, NULL, NULL, NULL, NULL, NULL, 5),
  (6, 'Пуф Arc', 'puf-arc', NULL, 1, NULL, 12000.00, NULL, NULL, NULL, NULL, NULL, NULL, 6);

SELECT setval('products_id_seq', 6);

INSERT INTO product_images (product_id, url, alt, is_primary, sort_order) VALUES
  (1, 'https://placehold.co/760x640', 'Кресло Yoga, общий вид', TRUE, 1),
  (1, 'https://placehold.co/120x96',  'Кресло Yoga, вид 1', FALSE, 2),
  (1, 'https://placehold.co/120x96',  'Кресло Yoga, вид 2', FALSE, 3),
  (1, 'https://placehold.co/120x96',  'Кресло Yoga, вид 3', FALSE, 4),
  (2, 'https://placehold.co/432x360', 'Стол MATILDA', TRUE, 1),
  (3, 'https://placehold.co/432x360', 'Двуспальная кровать LAV BED', TRUE, 1),
  (4, 'https://placehold.co/432x360', 'Диван Atelier', TRUE, 1),
  (5, 'https://placehold.co/432x360', 'Стеллаж Line', TRUE, 1),
  (6, 'https://placehold.co/432x360', 'Пуф Arc', TRUE, 1);


-- ---------- 6. Team (index.html) ----------

INSERT INTO team_members (name, role, photo_url, sort_order) VALUES
  ('Анна К.',   'Ведущий дизайнер', 'https://placehold.co/322x300', 1),
  ('Игорь М.',  'Архитектор',       'https://placehold.co/322x300', 2),
  ('Елена С.',  'Комплектатор',     'https://placehold.co/322x300', 3),
  ('Мария В.',  'Коммерция',        'https://placehold.co/322x300', 4);


-- ---------- 7. FAQ (index.html) ----------

INSERT INTO faq_items (question, answer, sort_order) VALUES
  ('Сколько длится проект?', 'Обычно 4–12 недель в зависимости от площади и пакета.', 1),
  ('Можно работать удаленно?', 'Да. После замера согласования онлайн и в личном кабинете.', 2),
  ('Что входит в фиксированную цену?', 'Выбранный пакет работ без скрытых этапов.', 3),
  ('Можно купить только в шоуруме?', 'Да. Для дизайнеров-партнеров отдельные условия.', 4);


-- ---------- 8. Service packages (services.html) ----------

INSERT INTO service_packages (id, name, slug, number_label, price_from, price_unit, description, sort_order) VALUES
  (1, 'Концепция', 'concept', '01', 2500.00, '₽/м²',
   'Планировка, moodboard и коллажи — чтобы зафиксировать направление до полного проекта.', 1),
  (2, 'Дизайн-проект', 'design-project', '02', 4500.00, '₽/м²',
   'Полный комплект для реализации: чертежи, визуализации и спецификации материалов.', 2),
  (3, 'Проект + комплектация', 'project-plus-sourcing', '03', 6500.00, '₽/м²',
   'Дизайн-проект и комплектация мебели, света и декора из собственного шоурума.', 3);

SELECT setval('service_packages_id_seq', 3);

INSERT INTO service_package_features (service_package_id, feature, sort_order) VALUES
  (1, 'Обмерный план', 1),
  (1, 'Планировочное решение', 2),
  (1, 'Moodboard и коллажи', 3),
  (1, 'Подбор референсов', 4),

  (2, 'Концепция и планировка', 1),
  (2, '3D-визуализации', 2),
  (2, 'Рабочие чертежи', 3),
  (2, 'Спецификации материалов', 4),

  (3, 'Полный дизайн-проект', 1),
  (3, 'Комплектация из шоурума', 2),
  (3, 'Авторский надзор', 3),
  (3, 'Стилизация при сдаче', 4);


-- ---------- 9. Studio values (about.html) ----------

INSERT INTO studio_values (number_label, title, description, sort_order) VALUES
  ('01', 'Ясность', 'Фиксируем состав работ и ориентир по стоимости до старта — без скрытых этапов.', 1),
  ('02', 'Материальность', 'Подбираем отделку и мебель так, чтобы решение было красивым и реализуемым.', 2),
  ('03', 'Сопровождение', 'Остаемся на связи на стройке и при комплектации из шоурума.', 3),
  ('04', 'Партнерство', 'Для дизайнеров — отдельные условия и приоритетный подбор наличия.', 4);


-- ---------- 10. Studio locations (about.html) ----------

INSERT INTO studio_locations (city, description, sort_order) VALUES
  ('Санкт-Петербург', 'Студия, шоурум, замеры', 1),
  ('Москва',           'Проекты по запросу', 2),
  ('Онлайн',           'Консультации и сопровождение', 3);


-- ---------- 11. Delivery rules (delivery.html) ----------

INSERT INTO delivery_rules (title, description, sort_order) VALUES
  ('Санкт-Петербург',
   'Доставка по городу — от 1–3 рабочих дней после подтверждения наличия и оплаты. Стоимость рассчитывается по зоне и объему заказа.', 1),
  ('Ленинградская область',
   'Доставка в область — по согласованию. Срок обычно 2–5 рабочих дней. Точную стоимость сообщает менеджер после уточнения адреса.', 2),
  ('Крупногабарит',
   'Диваны, кровати и корпусная мебель доставляются отдельным рейсом. Подъем и занос обсуждаются заранее и могут тарифицироваться отдельно.', 3);


-- ---------- 12. Company info (single row) ----------

INSERT INTO company_info
  (id, city_short, footer_city, phone, email, address, work_hours, telegram_handle, instagram_handle)
VALUES
  (1, 'Санкт-Петербург', 'Москва', '+7 495 000–00–00', 'hello@tsogz.ru',
   'ул. Патриаршие Пруды, 12', 'Ежедневно 10:00–20:00', '@tsogz.studio', '@tsogz.studio');


-- ---------- 13. Privacy policy sections (privacy.html) ----------

INSERT INTO policy_sections (number, title, body, sort_order) VALUES
  (1, 'Общие положения',
   'Настоящая Политика конфиденциальности определяет порядок обработки и защиты персональных данных пользователей сайта студии интерьера и шоурума TSOGZ (далее — «Сайт», «мы»). Используя Сайт и оставляя заявку, вы подтверждаете согласие с условиями Политики.', 1),
  (2, 'Какие данные мы собираем',
   'Мы можем обрабатывать: имя; номер телефона; адрес электронной почты; сведения об объекте (тип, площадь, город); текст сообщения и иные данные, которые вы добровольно указываете в формах; технические данные (IP-адрес, cookies, тип устройства и браузера) — в объёме, необходимом для работы Сайта и аналитики.', 2),
  (3, 'Цели обработки',
   'Персональные данные используются для: обработки заявок на замер и консультацию; связи с вами по вопросам проекта и шоурума; подготовки коммерческих предложений; улучшения работы Сайта; исполнения требований законодательства РФ.', 3),
  (4, 'Правовые основания',
   'Обработка осуществляется на основании вашего согласия, необходимости исполнения договора / преддоговорных мер по вашему запросу, а также законных интересов оператора — в пределах, не нарушающих права субъектов персональных данных.', 4),
  (5, 'Передача третьим лицам',
   'Мы не продаём персональные данные. Передача возможна подрядчикам (хостинг, CRM, аналитика, связь) строго для указанных целей по договорам с требованиями конфиденциальности, а также по законному требованию государственных органов.', 5),
  (6, 'Хранение и защита',
   'Данные хранятся не дольше, чем этого требуют цели обработки или закон. Мы применяем организационные и технические меры защиты от неправомерного доступа, изменения, раскрытия или уничтожения.', 6),
  (7, 'Права субъекта данных',
   'Вы вправе запросить доступ к своим данным, их уточнение, блокирование или удаление, отозвать согласие на обработку, а также обратиться с жалобой в уполномоченный орган. Для запросов напишите на hello@tsogz.ru.', 7),
  (8, 'Cookies и аналитика',
   'Сайт может использовать cookies и аналогичные технологии для корректной работы, сохранения настроек и обезличенной статистики. Вы можете ограничить cookies в настройках браузера; часть функций Сайта при этом может работать ограниченно.', 8),
  (9, 'Контакты оператора',
   'TSOGZ, студия интерьера и шоурум. Email: hello@tsogz.ru. Телефон: +7 495 000–00–00. По вопросам персональных данных: hello@tsogz.ru.', 9);


-- ---------- 14. Sample leads (contacts.html short form + request.html full form) ----------

INSERT INTO leads (form_type, name, phone, email, project_type_id, area_m2, message, source_page, consent_given, status) VALUES
  ('contact_short', 'Дмитрий', '+7 921 000-00-01', NULL, NULL, NULL,
   'Интересует консультация по перепланировке квартиры 60 м².', 'contacts', TRUE, 'new'),
  ('measurement_request', 'Ольга', '+7 921 000-00-02', 'olga@example.com', 1, 86.0,
   'Нужен замер и предварительная смета на дизайн-проект.', 'request', TRUE, 'contacted'),
  ('measurement_request', 'Сергей', '+7 921 000-00-03', 'sergey@example.com', 2, 240.0,
   'Дом в Подмосковье, нужна комплектация из шоурума.', 'request', TRUE, 'scheduled');