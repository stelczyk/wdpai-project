-- Dystans – bezpieczne dane demonstracyjne (fikcyjne osoby, brak prawdziwych danych osobowych).
-- Wszystkie konta mają hasło: Demo123!  (hash bcrypt z password_hash()).
-- Daty liczone od CURRENT_DATE, żeby wyprawa "w trakcie" była w trakcie niezależnie od dnia demo.

INSERT INTO users (email, password_hash, name, role) VALUES
    ('admin@dystans.app',   '$2y$10$1IM.hk7T7mOwZjNTvlt85OTpbCJw/dwHvAW7RL.vfse.J9fUUUzsa', 'Admin Dystansu',    'ADMIN'),
    ('kapitan@dystans.app', '$2y$10$1IM.hk7T7mOwZjNTvlt85OTpbCJw/dwHvAW7RL.vfse.J9fUUUzsa', 'Marta Kapitan',     'CAPTAIN'),
    ('jan@dystans.app',     '$2y$10$1IM.hk7T7mOwZjNTvlt85OTpbCJw/dwHvAW7RL.vfse.J9fUUUzsa', 'Jan Kowalski',      'USER'),
    ('anna@dystans.app',    '$2y$10$1IM.hk7T7mOwZjNTvlt85OTpbCJw/dwHvAW7RL.vfse.J9fUUUzsa', 'Anna Nowak',        'USER'),
    ('piotr@dystans.app',   '$2y$10$1IM.hk7T7mOwZjNTvlt85OTpbCJw/dwHvAW7RL.vfse.J9fUUUzsa', 'Piotr Wiśniewski',  'USER'),
    ('kasia@dystans.app',   '$2y$10$1IM.hk7T7mOwZjNTvlt85OTpbCJw/dwHvAW7RL.vfse.J9fUUUzsa', 'Kasia Zielińska',   'USER');

INSERT INTO disciplines (name, unit) VALUES
    ('Bieg', 'km'),
    ('Rower', 'km'),
    ('Pływanie', 'km'),
    ('Siłownia', 'min');

-- Trzy wyprawy: w trakcie, planowana i ukończona (zakładki ekranu "Moje wyprawy").
INSERT INTO expeditions (name, captain_id, start_date, end_date, max_members, join_code)
SELECT v.name, u.id, CURRENT_DATE + v.start_offset, CURRENT_DATE + v.end_offset, v.max_members, v.join_code
FROM (VALUES
    ('Kraków → Monza',     -21,  39, 10, '482913'),
    ('Gdańsk → Kopenhaga',  14,  60,  8, '730215'),
    ('Wrocław → Praga',    -90, -30,  6, '519046')
) AS v(name, start_offset, end_offset, max_members, join_code)
JOIN users u ON u.email = 'kapitan@dystans.app';

INSERT INTO checkpoints (expedition_id, name, km_from_start)
SELECT e.id, v.checkpoint, v.km
FROM (VALUES
    ('Kraków → Monza', 'Kraków', 0),
    ('Kraków → Monza', 'Ostrawa', 170),
    ('Kraków → Monza', 'Wiedeń', 460),
    ('Kraków → Monza', 'Graz', 660),
    ('Kraków → Monza', 'Klagenfurt', 800),
    ('Kraków → Monza', 'Udine', 930),
    ('Kraków → Monza', 'Werona', 1170),
    ('Kraków → Monza', 'Monza', 1330),
    ('Gdańsk → Kopenhaga', 'Gdańsk', 0),
    ('Gdańsk → Kopenhaga', 'Kołobrzeg', 230),
    ('Gdańsk → Kopenhaga', 'Szczecin', 360),
    ('Gdańsk → Kopenhaga', 'Rostock', 540),
    ('Gdańsk → Kopenhaga', 'Kopenhaga', 700),
    ('Wrocław → Praga', 'Wrocław', 0),
    ('Wrocław → Praga', 'Kłodzko', 95),
    ('Wrocław → Praga', 'Hradec Králové', 190),
    ('Wrocław → Praga', 'Praga', 300)
) AS v(expedition, checkpoint, km)
JOIN expeditions e ON e.name = v.expedition;

INSERT INTO expedition_members (expedition_id, user_id, joined_at)
SELECT e.id, u.id, e.start_date - 3
FROM (VALUES
    ('Kraków → Monza', 'kapitan@dystans.app'),
    ('Kraków → Monza', 'jan@dystans.app'),
    ('Kraków → Monza', 'anna@dystans.app'),
    ('Kraków → Monza', 'piotr@dystans.app'),
    ('Kraków → Monza', 'kasia@dystans.app'),
    ('Gdańsk → Kopenhaga', 'kapitan@dystans.app'),
    ('Gdańsk → Kopenhaga', 'jan@dystans.app'),
    ('Gdańsk → Kopenhaga', 'anna@dystans.app'),
    ('Wrocław → Praga', 'kapitan@dystans.app'),
    ('Wrocław → Praga', 'jan@dystans.app'),
    ('Wrocław → Praga', 'piotr@dystans.app')
) AS v(expedition, email)
JOIN expeditions e ON e.name = v.expedition
JOIN users u ON u.email = v.email;

-- Każda wyprawa ma wszystkie cztery dyscypliny z tymi samymi przelicznikami (kapitan może je zmienić).
INSERT INTO expedition_disciplines (expedition_id, discipline_id, km_factor)
SELECT e.id, d.id, v.km_factor
FROM expeditions e
CROSS JOIN (VALUES
    ('Bieg', 1.00),
    ('Rower', 0.35),
    ('Pływanie', 4.00),
    ('Siłownia', 0.10)
) AS v(discipline, km_factor)
JOIN disciplines d ON d.name = v.discipline;

-- route_km = (dystans albo minuty, zależnie od jednostki dyscypliny) × przelicznik wyprawy.
INSERT INTO activities (expedition_id, user_id, discipline_id, distance_km, duration_min,
                        activity_date, avg_heart_rate, note, route_km, status, created_at)
SELECT e.id, u.id, d.id, v.distance_km, v.duration_min,
       CURRENT_DATE - v.days_ago, v.avg_heart_rate, v.note,
       round(CASE d.unit WHEN 'km' THEN v.distance_km ELSE v.duration_min END * ed.km_factor, 2),
       v.status,
       (CURRENT_DATE - v.days_ago) + TIME '19:00'
FROM (VALUES
    ('Kraków → Monza', 'jan@dystans.app',     'Bieg',     10.20::numeric, 55,  19, 152::smallint, 'Poranny bieg nad Wisłą', 'APPROVED'),
    ('Kraków → Monza', 'jan@dystans.app',     'Rower',    62.00,          140, 15, 138,           'Wycieczka do Ojcowa',    'APPROVED'),
    ('Kraków → Monza', 'jan@dystans.app',     'Bieg',     8.40,           46,  2,  149,           NULL,                     'PENDING'),
    ('Kraków → Monza', 'anna@dystans.app',    'Pływanie', 2.50,           60,  18, 131,           'Basen AGH',              'APPROVED'),
    ('Kraków → Monza', 'anna@dystans.app',    'Bieg',     21.10,          118, 11, 161,           'Półmaraton treningowy',  'APPROVED'),
    ('Kraków → Monza', 'anna@dystans.app',    'Siłownia', NULL,           75,  1,  NULL,          'Trening siłowy',         'PENDING'),
    ('Kraków → Monza', 'piotr@dystans.app',   'Rower',    105.00,         230, 16, 142,           'Kraków – Tarnów',        'APPROVED'),
    ('Kraków → Monza', 'piotr@dystans.app',   'Rower',    300.00,         240, 9,  120,           'Podejrzanie szybko',     'REJECTED'),
    ('Kraków → Monza', 'piotr@dystans.app',   'Bieg',     6.00,           35,  3,  155,           NULL,                     'PENDING'),
    ('Kraków → Monza', 'kasia@dystans.app',   'Bieg',     5.00,           30,  17, 158,           'Pierwsza piątka',        'APPROVED'),
    ('Kraków → Monza', 'kasia@dystans.app',   'Pływanie', 1.80,           45,  7,  128,           NULL,                     'APPROVED'),
    ('Kraków → Monza', 'kasia@dystans.app',   'Siłownia', NULL,           60,  0,  NULL,          NULL,                     'PENDING'),
    ('Kraków → Monza', 'kapitan@dystans.app', 'Bieg',     12.00,          62,  13, 150,           'Długie wybieganie',      'APPROVED'),
    ('Kraków → Monza', 'kapitan@dystans.app', 'Rower',    80.00,          180, 5,  135,           NULL,                     'APPROVED'),
    ('Wrocław → Praga', 'jan@dystans.app',     'Rower',   120.00,         300, 80, 140,           NULL,                     'APPROVED'),
    ('Wrocław → Praga', 'piotr@dystans.app',   'Bieg',    42.20,          225, 60, 163,           'Maraton!',               'APPROVED'),
    ('Wrocław → Praga', 'kapitan@dystans.app', 'Pływanie', 3.00,          70,  45, 130,           'Seria na basenie',       'APPROVED')
) AS v(expedition, email, discipline, distance_km, duration_min, days_ago, avg_heart_rate, note, status)
JOIN expeditions e ON e.name = v.expedition
JOIN users u ON u.email = v.email
JOIN disciplines d ON d.name = v.discipline
JOIN expedition_disciplines ed ON ed.expedition_id = e.id AND ed.discipline_id = d.id;

-- Decyzje kapitana dla już rozpatrzonych aktywności (następnego dnia rano).
INSERT INTO audit_log (expedition_id, activity_id, actor_id, action, created_at)
SELECT a.expedition_id, a.id, e.captain_id, a.status, a.created_at + INTERVAL '14 hours'
FROM activities a
JOIN expeditions e ON e.id = a.expedition_id
WHERE a.status <> 'PENDING';
