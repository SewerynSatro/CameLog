-- CameLog – dane seed
-- Dane logowania:
--   admin:  admin@camelog.pl  / admin123
--   user:   user@camelog.pl   / user123

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE notifications;
TRUNCATE TABLE care_history;
TRUNCATE TABLE care_tasks;
TRUNCATE TABLE plant_photos;
TRUNCATE TABLE plants;
TRUNCATE TABLE species;
TRUNCATE TABLE users;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Użytkownicy
-- ============================================================
INSERT INTO users (id, name, email, password_hash, role, status, bio, created_at, updated_at) VALUES
(1, 'Administrator', 'admin@camelog.pl',
   '$2y$10$x0hpTol03z0ZDAtuKlxHruUhO.NGPrrrLG/slX3x9WFJCL4bzEq6C',
   'admin', 'active', 'Główny administrator systemu CameLog.',
   NOW() - INTERVAL 90 DAY, NOW()),
(2, 'Anna Nowak', 'user@camelog.pl',
   '$2y$10$n1k8Tj3ZeqYaW.Ns6C9i/uzAFyJkgq.CI2hXPg63L667MEUF8DHtC',
   'user', 'active', 'Pielęgnuję 5 roślin i staram się o nich nie zapominać.',
   NOW() - INTERVAL 60 DAY, NOW()),
(3, 'Marek Kowal', 'marek@camelog.pl',
   '$2y$10$n1k8Tj3ZeqYaW.Ns6C9i/uzAFyJkgq.CI2hXPg63L667MEUF8DHtC',
   'user', 'active', 'Świeżo upieczony fan zieleni.',
   NOW() - INTERVAL 30 DAY, NOW()),
(4, 'Ewa Test', 'ewa@camelog.pl',
   '$2y$10$n1k8Tj3ZeqYaW.Ns6C9i/uzAFyJkgq.CI2hXPg63L667MEUF8DHtC',
   'user', 'blocked', NULL,
   NOW() - INTERVAL 15 DAY, NOW());

-- ============================================================
-- Gatunki (przykładowe; można też dociągać z Perenual)
-- ============================================================
INSERT INTO species (id, external_api_id, common_name, scientific_name, care_level, watering_info, sunlight_info, climate_info, raw_api_data) VALUES
(1, 'monstera-deliciosa', 'Monstera Deliciosa', 'Monstera deliciosa', 'easy',
 'Średnio – co 7-10 dni, gdy wierzchnia warstwa podłoża wyschnie.',
 'Jasne, rozproszone światło – unikać bezpośredniego słońca.',
 'Klimat tropikalny, 18-27°C, wilgotność 60-80%.',
 NULL),
(2, 'ficus-elastica', 'Fikus Sprężysty', 'Ficus elastica', 'easy',
 'Co 7-14 dni – tolerancyjny na okresowe przesuszenie.',
 'Jasne miejsce, znosi półcień.',
 'Klimat ciepły, 18-24°C.',
 NULL),
(3, 'calathea-orbifolia', 'Kalatea Orbifolia', 'Calathea orbifolia', 'medium',
 'Co 5-7 dni; wymaga wilgotnego podłoża, ale nie mokrego.',
 'Półcień, miękkie rozproszone światło.',
 'Wysoka wilgotność powietrza (60-80%), ciepło 18-25°C.',
 NULL),
(4, 'zamioculcas-zamiifolia', 'Zamiokulkas', 'Zamioculcas zamiifolia', 'easy',
 'Rzadko – co 14-21 dni, między podlewaniami całkowicie wysuszać.',
 'Toleruje cień, ale lepiej rośnie w jasnym miejscu.',
 'Klimat ciepły, znosi suche powietrze.',
 NULL),
(5, 'pilea-peperomioides', 'Pilea (Roślina UFO)', 'Pilea peperomioides', 'easy',
 'Co 7-10 dni, gdy podłoże podschnie.',
 'Jasne rozproszone światło.',
 'Klimat umiarkowany, 16-24°C.',
 NULL);

-- ============================================================
-- Rośliny użytkownika #2 (Anna Nowak)
-- ============================================================
INSERT INTO plants (id, user_id, species_id, name, custom_species_name, location, planted_at, notes, watering_interval_days, fertilizing_interval_days, care_level, api_recommendations_used, health_status, created_at, updated_at) VALUES
(1, 2, 1, 'Monstera w salonie', 'Monstera Deliciosa', 'Salon', '2024-03-15',
 'Stoi w jasnym kącie, ale bez bezpośredniego słońca. Latem przyspiesza wzrost.',
 7, 30, 'easy', 1, 'healthy', NOW() - INTERVAL 50 DAY, NOW()),
(2, 2, 2, 'Fikus w sypialni', 'Fikus Sprężysty', 'Sypialnia', '2024-05-20',
 'Lubi stałe miejsce, nie przestawiać często.',
 10, 45, 'easy', 1, 'healthy', NOW() - INTERVAL 40 DAY, NOW()),
(3, 2, 3, 'Kalatea Orbifolia', 'Calathea orbifolia', 'Łazienka', '2024-06-01',
 'Wstawiona do łazienki – wysoka wilgotność jej służy.',
 5, 30, 'medium', 1, 'needs_attention', NOW() - INTERVAL 30 DAY, NOW()),
(4, 2, 4, 'Zamiokulkas', 'Zamioculcas zamiifolia', 'Biuro', '2023-11-10',
 'Ulubieniec biurka – wytrzymałość ekstra.',
 14, 60, 'easy', 1, 'healthy', NOW() - INTERVAL 25 DAY, NOW()),
(5, 2, 5, 'Pilea-prezent', 'Pilea peperomioides', 'Parapet', '2024-08-12',
 'Prezent od mamy. Ma już jednego potomka.',
 7, 30, 'easy', 1, 'healthy', NOW() - INTERVAL 20 DAY, NOW());

-- ============================================================
-- Taski (mix zaległych, dziś, nadchodzących, wykonanych)
-- ============================================================
INSERT INTO care_tasks (id, user_id, plant_id, type, title, description, due_date, status, repeat_interval_days, priority, completed_at, created_at) VALUES
-- Zaległe (overdue)
(1, 2, 3, 'watering', 'Podlewanie Kalatea Orbifolia',
 'Kalatea wymaga regularnego podlewania – przesuszenie skutkuje zwijaniem liści.',
 NOW() - INTERVAL 2 DAY, 'pending', 5, 'high', NULL, NOW() - INTERVAL 30 DAY),
(2, 2, 1, 'fertilizing', 'Nawożenie Monstera',
 'Nawóz uniwersalny w połowie zalecanego stężenia.',
 NOW() - INTERVAL 1 DAY, 'pending', 30, 'normal', NULL, NOW() - INTERVAL 25 DAY),

-- Dzisiaj
(3, 2, 1, 'watering', 'Podlewanie Monstera',
 NULL, NOW(), 'pending', 7, 'normal', NULL, NOW() - INTERVAL 7 DAY),
(4, 2, 5, 'watering', 'Podlewanie Pilea',
 'Sprawdź wilgotność palcem przed podlaniem.',
 NOW(), 'pending', 7, 'normal', NULL, NOW() - INTERVAL 7 DAY),
(5, 2, 3, 'misting', 'Zraszanie Kalatea',
 'Codzienne lekkie zraszanie liści.',
 NOW(), 'pending', 1, 'low', NULL, NOW() - INTERVAL 2 DAY),

-- Nadchodzące
(6, 2, 2, 'watering', 'Podlewanie Fikus',
 NULL, NOW() + INTERVAL 2 DAY, 'pending', 10, 'normal', NULL, NOW() - INTERVAL 8 DAY),
(7, 2, 4, 'watering', 'Podlewanie Zamiokulkas',
 'Sprawdź czy podłoże jest całkowicie suche.',
 NOW() + INTERVAL 5 DAY, 'pending', 14, 'low', NULL, NOW() - INTERVAL 9 DAY),
(8, 2, 1, 'pruning', 'Przycięcie Monstera',
 'Usuń uschnięte liście dolne.',
 NOW() + INTERVAL 7 DAY, 'pending', NULL, 'normal', NULL, NOW() - INTERVAL 1 DAY),

-- Wykonane (historia)
(9, 2, 1, 'watering', 'Podlewanie Monstera',
 NULL, NOW() - INTERVAL 7 DAY, 'done', 7, 'normal', NOW() - INTERVAL 7 DAY, NOW() - INTERVAL 14 DAY),
(10, 2, 5, 'watering', 'Podlewanie Pilea',
 NULL, NOW() - INTERVAL 7 DAY, 'done', 7, 'normal', NOW() - INTERVAL 7 DAY, NOW() - INTERVAL 14 DAY),
(11, 2, 2, 'watering', 'Podlewanie Fikus',
 NULL, NOW() - INTERVAL 8 DAY, 'done', 10, 'normal', NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 18 DAY),
(12, 2, 3, 'watering', 'Podlewanie Kalatea',
 NULL, NOW() - INTERVAL 7 DAY, 'done', 5, 'high', NOW() - INTERVAL 7 DAY, NOW() - INTERVAL 12 DAY),
(13, 2, 1, 'fertilizing', 'Nawożenie Monstera',
 NULL, NOW() - INTERVAL 31 DAY, 'done', 30, 'normal', NOW() - INTERVAL 31 DAY, NOW() - INTERVAL 35 DAY);

-- ============================================================
-- Historia pielęgnacji
-- ============================================================
INSERT INTO care_history (user_id, plant_id, task_id, type, note, performed_at) VALUES
(2, 1, 9,  'watering', 'Podłoże było już lekko podsuszone.', NOW() - INTERVAL 7 DAY),
(2, 5, 10, 'watering', NULL, NOW() - INTERVAL 7 DAY),
(2, 2, 11, 'watering', 'Bardzo zdrowe liście, błyszczące.', NOW() - INTERVAL 8 DAY),
(2, 3, 12, 'watering', 'Liście zaczynały się lekko zwijać.', NOW() - INTERVAL 7 DAY),
(2, 1, 13, 'fertilizing', 'Nawóz uniwersalny 50% stężenia.', NOW() - INTERVAL 31 DAY),
(2, 4, NULL, 'watering', 'Pierwsze podlewanie po dwóch tygodniach przerwy.', NOW() - INTERVAL 14 DAY),
(2, 1, NULL, 'watering', NULL, NOW() - INTERVAL 14 DAY),
(2, 5, NULL, 'pruning', 'Odjęto sadzonkę dla mamy.', NOW() - INTERVAL 21 DAY);

-- ============================================================
-- Powiadomienia
-- ============================================================
INSERT INTO notifications (user_id, task_id, title, message, type, is_read, created_at) VALUES
(2, 1, 'Kalatea czeka na podlanie', 'Zadanie jest spóźnione o 2 dni. Sprawdź wilgotność podłoża.', 'overdue', 0, NOW() - INTERVAL 2 DAY),
(2, 2, 'Monstera potrzebuje nawożenia', 'Termin nawożenia minął wczoraj.', 'overdue', 0, NOW() - INTERVAL 1 DAY),
(2, 3, 'Czas podlać Monsterę',  'Zaplanowane na dziś.', 'today', 0, NOW()),
(2, 4, 'Czas podlać Pileę', 'Zaplanowane na dziś.', 'today', 0, NOW()),
(2, 5, 'Zraszanie Kalatea', 'Codzienne zraszanie liści.', 'today', 1, NOW()),
(2, 6, 'Nadchodzące podlewanie Fikusa', 'Za 2 dni.', 'incoming', 0, NOW() - INTERVAL 1 HOUR),
(2, NULL, 'Witamy w CameLog!', 'Dzięki, że dbasz o swoją zieloną oazę. Powodzenia!', 'system', 1, NOW() - INTERVAL 60 DAY);
