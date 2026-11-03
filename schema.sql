CREATE DATABASE IF NOT EXISTS fan_hub_plus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fan_hub_plus;

DROP TABLE IF EXISTS quiz_attempts;
DROP TABLE IF EXISTS quiz_questions;
DROP TABLE IF EXISTS fandom_poll_votes;
DROP TABLE IF EXISTS fandom_polls;
DROP TABLE IF EXISTS event_rsvps;
DROP TABLE IF EXISTS chatbot_queries;
DROP TABLE IF EXISTS item_bookmarks;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS personal_notes;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS media_ratings;
DROP TABLE IF EXISTS bookmarks;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS merchandise_items;
DROP TABLE IF EXISTS character_profiles;
DROP TABLE IF EXISTS contents;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  avatar VARCHAR(255) DEFAULT '',
  favorite_categories VARCHAR(255) DEFAULT '',
  theme ENUM('dark','light') NOT NULL DEFAULT 'dark',
  font_size ENUM('sm','md','lg') NOT NULL DEFAULT 'md',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE,
  description TEXT,
  icon VARCHAR(40) DEFAULT ''
) ENGINE=InnoDB;

CREATE TABLE contents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  type ENUM('article','video','audio','explainer') NOT NULL DEFAULT 'article',
  genre VARCHAR(80) DEFAULT '',
  body TEXT,
  media_url VARCHAR(500) DEFAULT '',
  thumbnail VARCHAR(255) DEFAULT '',
  release_year INT DEFAULT NULL,
  popularity_score INT NOT NULL DEFAULT 0,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  views INT NOT NULL DEFAULT 0,
  status ENUM('draft','pending','approved','rejected','published') NOT NULL DEFAULT 'published',
  created_at DATETIME NOT NULL,
  INDEX idx_category (category_id),
  INDEX idx_slug (slug),
  INDEX idx_popularity (popularity_score),
  INDEX idx_year (release_year),
  CONSTRAINT fk_contents_cat FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT fk_contents_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE character_profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  bio TEXT,
  role_type VARCHAR(80) DEFAULT '',
  image VARCHAR(255) DEFAULT '',
  attributes TEXT,
  timeline TEXT,
  related_content_ids VARCHAR(120) DEFAULT '',
  created_at DATETIME NOT NULL,
  INDEX idx_char_cat (category_id),
  CONSTRAINT fk_char_cat FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE merchandise_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  image VARCHAR(255) DEFAULT '',
  tag VARCHAR(80) DEFAULT '',
  views INT NOT NULL DEFAULT 0,
  description TEXT,
  created_at DATETIME NOT NULL,
  INDEX idx_merch_cat (category_id),
  CONSTRAINT fk_merch_cat FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE bookmarks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  content_id INT UNSIGNED NOT NULL,
  notes TEXT,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_user_content (user_id, content_id),
  CONSTRAINT fk_bm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_bm_content FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  city VARCHAR(120) NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  event_date DATE NOT NULL,
  ticket_url VARCHAR(400) DEFAULT '',
  description TEXT,
  image VARCHAR(255) DEFAULT '',
  created_at DATETIME NOT NULL,
  INDEX idx_event_cat (category_id),
  INDEX idx_event_city (city),
  INDEX idx_event_date (event_date),
  CONSTRAINT fk_event_cat FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE media_ratings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  content_id INT UNSIGNED NOT NULL,
  rating TINYINT NOT NULL,
  review TEXT,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_rating (user_id, content_id),
  CONSTRAINT fk_rt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_rt_content FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE feedback (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  type VARCHAR(80) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('open','reviewed','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(160) NOT NULL,
  token VARCHAR(64) NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE personal_notes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  body TEXT,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_notes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  action VARCHAR(80) NOT NULL,
  detail VARCHAR(255) DEFAULT '',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE chatbot_queries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  message TEXT NOT NULL,
  response TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_cq_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE item_bookmarks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  item_type ENUM('character','merch') NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  notes TEXT,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_user_item (user_id, item_type, item_id),
  CONSTRAINT fk_ibm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE event_rsvps (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  status ENUM('going','interested') NOT NULL DEFAULT 'going',
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_event_user (event_id, user_id),
  INDEX idx_rsvp_event (event_id),
  INDEX idx_rsvp_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE fandom_polls (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  question VARCHAR(255) NOT NULL,
  option_1 VARCHAR(140) NOT NULL,
  option_2 VARCHAR(140) NOT NULL,
  option_3 VARCHAR(140) DEFAULT '',
  option_4 VARCHAR(140) DEFAULT '',
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL,
  INDEX idx_poll_cat (category_id)
) ENGINE=InnoDB;

CREATE TABLE fandom_poll_votes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  poll_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  voter_key VARCHAR(80) NOT NULL,
  selected_option TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_poll_voter (poll_id, voter_key),
  INDEX idx_pv_poll (poll_id)
) ENGINE=InnoDB;

CREATE TABLE quiz_questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  question VARCHAR(300) NOT NULL,
  option_a VARCHAR(160) NOT NULL,
  option_b VARCHAR(160) NOT NULL,
  option_c VARCHAR(160) NOT NULL,
  option_d VARCHAR(160) NOT NULL,
  correct_option CHAR(1) NOT NULL DEFAULT 'A',
  explanation TEXT,
  points INT UNSIGNED NOT NULL DEFAULT 20,
  INDEX idx_qq_cat (category_id)
) ENGINE=InnoDB;

CREATE TABLE quiz_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED DEFAULT NULL,
  score INT UNSIGNED NOT NULL DEFAULT 0,
  total_questions INT UNSIGNED NOT NULL DEFAULT 0,
  xp_earned INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX idx_qa_user (user_id)
) ENGINE=InnoDB;

INSERT INTO users (name, email, password, role, avatar, favorite_categories, theme, font_size, created_at) VALUES
('System Admin', 'admin@fanhubplus.test', '$2y$12$6bRto1eEC/ap9hhV/QuaV.xfx57h17DYe9DRwdElF5ksWCtbuuunK', 'admin', '', 'anime,gaming,manga', 'dark', 'md', NOW()),
('Alex Rivera', 'user@fanhubplus.test', '$2y$12$dUbKz5RP0dqlsrRElHlxcuS0RCCw98qBM28NooMhRAZmvkDiAVeUa', 'user', '', 'k-pop,movies,cosplay', 'dark', 'md', NOW());

INSERT INTO categories (name, slug, description, icon) VALUES
('Anime', 'anime', 'Series, films, and studio culture from Japan and beyond.', ''),
('Gaming', 'gaming', 'Consoles, PC, indie worlds, esports, and speedruns.', ''),
('Movies', 'movies', 'Blockbusters, cult classics, and fandom cinema.', ''),
('TV Shows', 'tv-shows', 'Serialized storytelling, streaming hits, and recaps.', ''),
('K-Pop', 'k-pop', 'Idols, comebacks, choreography, and lightstick culture.', ''),
('Comics', 'comics', 'Superheroes, indie issues, and graphic storytelling.', ''),
('Manga', 'manga', 'Shonen, shojo, seinen, and collector editions.', ''),
('Cosplay', 'cosplay', 'Costume craft, conventions, and character performance.', '');

INSERT INTO contents (user_id, category_id, title, slug, type, genre, body, media_url, thumbnail, release_year, popularity_score, featured, views, status, created_at) VALUES
(1, 1, 'Why Shonen Openings Still Hit Hard', 'why-shonen-openings-still-hit-hard', 'article', 'Action', 'Opening themes remain a ritual: a 90-second promise of pace, friendship, and the next arc. This explainer tracks how OP animation evolved from still montages to cinematic micro-films.', '', 'https://images.unsplash.com/photo-1578632767115-351597cf2477?auto=format&fit=crop&w=1200&q=80', 2024, 96, 1, 412, 'published', NOW()),
(1, 1, 'Attack on Titan Final Arc Trailer Study', 'aot-final-arc-trailer-study', 'video', 'Dark Fantasy', 'A frame-by-frame look at how the final trailer sells scale, silence, and consequence.', 'https://www.youtube.com/embed/M_OauHnAFc8', 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?auto=format&fit=crop&w=1200&q=80', 2023, 99, 1, 880, 'published', NOW()),
(1, 2, 'Open-World Design: Traversal That Feels Alive', 'open-world-design-traversal', 'explainer', 'Adventure', 'Animated explainer on landmarks, verticality, and player curiosity loops.', 'https://www.youtube.com/embed/s_n6vNtdtV0', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1200&q=80', 2022, 91, 1, 540, 'published', NOW()),
(1, 2, 'Indie Soundtrack Hour', 'indie-soundtrack-hour', 'audio', 'Soundtrack', 'A curated hour of indie game scores that carry melancholy, wonder, and pixel-era warmth.', 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=1200&q=80', 2021, 77, 0, 210, 'published', NOW()),
(1, 3, 'Practical Effects vs CGI in Modern Blockbusters', 'practical-vs-cgi', 'article', 'Sci-Fi', 'Fans still cheer when a miniature or suit performance is visible. This piece compares tactile craft with digital spectacle.', '', 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=1200&q=80', 2020, 84, 1, 301, 'published', NOW()),
(1, 4, 'TV Recap Culture and Spoiler Ethics', 'tv-recap-spoiler-ethics', 'article', 'Drama', 'How recap writers keep discussion alive without ruining first-watch magic.', '', 'https://images.unsplash.com/photo-1522869635100-9f4c5e86aa37?auto=format&fit=crop&w=1200&q=80', 2024, 73, 0, 190, 'published', NOW()),
(1, 5, 'Comeback Week: Choreo, Color, and Lightsticks', 'comeback-week-choreo', 'video', 'Performance', 'A breakdown of how title-track staging is designed for both arena and phone screens.', 'https://www.youtube.com/embed/gdZLi9oWNZg', 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=1200&q=80', 2023, 95, 1, 760, 'published', NOW()),
(1, 6, 'Panel Layouts That Teach You How to Read Speed', 'comic-panel-speed', 'article', 'Superhero', 'Gutters, splash pages, and motion lines as a language of time.', '', 'https://images.unsplash.com/photo-1612036782180-6f0b6cd846fe?auto=format&fit=crop&w=1200&q=80', 2019, 69, 0, 155, 'published', NOW()),
(1, 7, 'Collecting Tankobon: Editions and Paper Stock', 'collecting-tankobon', 'article', 'Seinen', 'A practical guide to kanzenban, bunko, and why paper weight matters to collectors.', '', 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=1200&q=80', 2022, 80, 1, 266, 'published', NOW()),
(1, 8, 'Armor Foam 101', 'armor-foam-101', 'explainer', 'Craft', 'A beginner explainer on EVA foam, heat forming, and convention-safe edges.', 'https://www.youtube.com/embed/2Vv-BfVoq4A', 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1200&q=80', 2024, 88, 1, 344, 'published', NOW()),
(2, 1, 'My First Watch of Steins;Gate', 'my-first-watch-steins-gate', 'article', 'Sci-Fi', 'A fan essay on time travel, lab memes, and why episode 12 still hurts.', '', 'https://images.unsplash.com/photo-1482192505345-5655af888cc4?auto=format&fit=crop&w=1200&q=80', 2024, 60, 0, 40, 'pending', NOW());

INSERT INTO contents (user_id, category_id, title, slug, type, genre, body, media_url, thumbnail, release_year, popularity_score, featured, views, status, created_at) VALUES
(1, 1, 'Why Great Anime Action Scenes Make Room for Silence', 'anime-action-scenes-use-silence', 'article', 'Animation', 'A memorable fight is built from rhythm as much as motion. A held glance, a still frame, or a sudden drop in sound gives the next movement weight. That pause lets the audience read the stakes before the scene accelerates again.\n\nThe strongest sequences also give every movement a purpose. Camera distance, impact timing, and a character reaction can tell us who has control without explaining it in dialogue. When the animation balances quiet beats with bursts of motion, the fight feels dramatic instead of merely busy.', '', '/assets/images/fandoms/anime.jfif', 2026, 86, 0, 0, 'published', NOW()),
(1, 3, 'How Practical Effects Make Movie Worlds Feel Real', 'practical-effects-make-movie-worlds-tangible', 'article', 'Film Craft', 'A practical effect gives performers something physical to react to. Light from a real fire, the weight of a prop, or dust moving through a set can make a scene feel grounded before visual effects add scale or impossible detail.\n\nThat does not make practical work a rival to digital effects. The best productions combine both: build texture and believable light on set, then use digital tools to extend what the camera captured. The result feels convincing because the materials, performances, and finishing work support one another.', '', '/assets/images/mo1.jfif', 2026, 82, 0, 0, 'published', NOW()),
(1, 6, 'The Page Turn as a Storytelling Reveal in Comics', 'comic-page-turns-as-storytelling', 'article', 'Comics', 'A page turn controls when a reader discovers the next image. Creators can use that small delay to hide a surprise, build anticipation, or make a scene land with more force. The blank space before the turn becomes part of the pacing.\n\nPanel size and reading direction matter too. A quiet sequence can slow the eye, while a wide splash can reward the reader with a sudden change in scale. These choices guide attention without taking control away from the person holding the book.', '', '/assets/images/fandoms/comics.jfif', 2026, 79, 0, 0, 'published', NOW()),
(1, 4, 'What Makes a TV Season Finale Feel Earned', 'tv-finale-payoffs-and-character-arcs', 'article', 'Television', 'A satisfying finale pays off the choices a story has been building toward. It does not need to answer every question, but the characters should arrive at the ending changed by what they have experienced. That emotional continuity is what makes a final scene feel earned.\n\nThe best endings leave a little room for discussion while still giving the season a clear shape. A last image, a resolved relationship, or one carefully chosen unanswered question can stay with viewers long after the credits roll.', '', '/assets/images/fandoms/tv shows.jfif', 2026, 77, 0, 0, 'published', NOW());

INSERT INTO character_profiles (category_id, name, bio, role_type, image, attributes, timeline, related_content_ids, created_at) VALUES
(1, 'Mikasa Ackerman', 'Mikasa Ackerman is one of the most formidable and disciplined soldiers in the Attack on Titan universe, serving in the elite Survey Corps. Following the tragic loss of her parents during childhood, she was rescued and welcomed into the Yeager family, forming an unbreakable bond with Eren Yeager. Her scarf, gifted by Eren on the day of her rescue, remains her most treasured possession and a profound symbol of warmth in a harsh, cruel world.\n\nPossessing the latent combat prowess of the legendary Ackerman clan, Mikasa boasts superhuman reflexes, surgical precision, and tactical genius that rival humanity\'s strongest warrior, Captain Levi. Graduating at the top of the 104th Training Corps, she demonstrated mastery of Omni-Directional Mobility (ODM) gear, effortlessly weaving through urban ruins and towering forests to strike Titans at their nape. Throughout the harrowing campaigns against the Titans, the defense of Trost District, the reclaiming of Wall Maria, and the conflicts across the sea, Mikasa stands as an unshakeable guardian driven by a quiet ferocity, fierce compassion, and an enduring desire to protect those she loves.', 'Protagonist', '/assets/images/fandoms/anime.jfif', 'Combat, Loyalty, Tactical awareness', 'Cadet years → Survey Corps → Final war', '1,2', NOW()),
(2, 'Aloy', 'Aloy is the spirited hunter, seeker, and machine archaeologist at the heart of the Horizon series (Horizon Zero Dawn and Horizon Forbidden West). Cast out from the Nora tribe at birth due to superstitions surrounding her mysterious origins at the mountain cradle of All-Mother, she was raised in the rugged wilderness by the solitary outcast Rost. Guided by Rost\'s rigorous survival training and her discovery of a mysterious ancient technological device known as a \'Focus,\' Aloy developed a profound curiosity about the world that existed before the catastrophic fall of human civilization.\n\nArmed with her iconic bow, tripcaster, and spear capable of overriding lethal machines, Aloy navigates a breathtaking post-apocalyptic frontier populated by formidable robotic predators like Thunderjaws, Stormbirds, and Slaughterspines. Beyond her exceptional marksmanship and stealth abilities, Aloy\'s relentless intellect drives her to uncover the ancient truth of Project Zero Dawn and her genetic connection to Dr. Elisabet Sobeck. Braving deadly machine cauldrons, treacherous rogue AI threats like HADES and HEPHAESTUS, and inter-tribal politics across the sacred lands, the Carja Sun-Kingdom, and the Forbidden West, Aloy transforms from a shunned outcast into the savior of human civilization.', 'Hero', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=800&q=80', 'Archery, Focus, Curiosity', 'Nora outcast → Machine hunter → Savior of tribes', '3', NOW()),
(5, 'Stage Captain Hana', 'Stage Captain Hana is the celebrated leader, lead vocalist, and visual centerpiece of a premier fictional K-pop sensation that has captured the hearts of millions across the globe. Beginning her journey as a passionate trainee at the tender age of fourteen, Hana underwent years of rigorous training spanning vocal control, synchronized group choreography, stage charisma, and multilingual media communication. Her unrelenting discipline and natural artistic intuition quickly distinguished her as the guiding compass of her group.\n\nRenowned for commanding the stage with effortless elegance and dynamic energy, Hana treats every comeback season as a masterclass in visual storytelling, combining intricate synchronized dance breaks, powerful high notes, and cutting-edge fashion concepts. Beyond the spotlight and roaring arena stadium tours with waving lightsticks, Hana actively participates in songwriting, arrangement, and creative concept development for the group\'s concept albums. Her empathetic leadership and genuine bond with the fandom have cemented her status as an inspirational role model in modern pop culture.', 'Idol Leader', 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=800&q=80', 'Dance, Vocals, Fan service craft', 'Trainee → Debut → World tour', '7', NOW()),
(8, 'Knight of Neon', 'Knight of Neon is an award-winning original character concept that bridges the gap between medieval gothic chivalry and high-tech cyberpunk aesthetics. Conceived by master convention prop-makers and armor artisans, the design envisions a lone paladin wandering through a neon-drenched futuristic metropolis, enforcing justice in sprawling alleyways illuminated by holographic billboards and rain-slicked asphalt.\n\nThe suit itself is a triumph of cosplay engineering, crafted from high-density EVA foam layered with thermo-formed Worbla, custom 3D-printed mechanical gauntlets, and hundreds of addressable RGB LED light strips powered by custom Arduino microcontrollers. The armor\'s weathered gunmetal finish, realistic battle scars, and illuminated power conduits create an awe-inspiring presence on convention floors. Recognized with multiple best-in-show accolades at international comic-cons and cosplay summits, the Knight of Neon has inspired costume builders and workshop mentors worldwide to push the boundaries of wearable art and performance crafting.', 'Original Character', 'https://pics.craiyon.com/2023-07-16/54b50d8dc1d14d9792f79c7d5d43cc0f.webp', 'Foam craft, LED, Performance', 'First con → Award stage → Workshop mentor', '10', NOW()),
(3, 'Ellen Ripley', 'Ellen Ripley, portrayed by Sigourney Weaver in the landmark Alien film franchise, is widely celebrated as one of the greatest, most enduring heroes in cinematic history. Introduced as the pragmatic warrant officer aboard the commercial towing starship USCSS Nostromo, Ripley\'s calm intellect, adherence to safety quarantine protocols, and sharp intuition set her apart from her ill-fated crewmates when an unknown extraterrestrial parasitic organism is brought aboard.\n\nWhen the deadly Xenomorph turns the ship into a claustrophobic slaughterhouse, Ripley relies on sheer ingenuity, courage, and relentless willpower to survive, ultimately blasting the creature into the vacuum of space. Her battle intensifies in James Cameron\'s action-packed sequel Aliens, where she accompanies a squad of Colonial Marines to the besieged colony on LV-426. There, driven by maternal protective fury, she famously straps into a hydraulic Power Loader exoskeleton to battle the towering Xenomorph Queen in one of cinema\'s most iconic showdowns. Ripley remains an unmatched feminist icon whose heroism is defined not by superpowers, but by raw resilience, moral integrity, and relentless survival.', 'Survivor', '/assets/images/characters/ellen-ripley.webp', 'Leadership, Engineering, Survival', 'Cargo officer → Deep-space survivor → Earth return', '5', NOW()),
(6, 'Batman', 'Bruce Wayne, known worldwide as Batman and Gotham City\'s Dark Knight, is one of the most iconic and complex superheroes ever created. Witnessing the tragic murder of his parents, Thomas and Martha Wayne, in the dark shadows of Crime Alley during his childhood, young Bruce made a solemn vow to dedicate his life to waging an unrelenting war against all criminals. Backed by his immense family fortune, he spent years traveling the globe, mastering dozens of martial arts, forensic sciences, criminology, stealth, escapology, and strategic warfare.\n\nReturning to Gotham City, Bruce adopted the terrifying persona of a bat to strike fear into the hearts of superstitious and cowardly villains. Without possessing superhuman abilities, Batman stands shoulder-to-shoulder with gods in the Justice League purely through peak human physical conditioning, unparalleled tactical genius, and an arsenal of high-tech marvels housed in the secret depths of the Batcave—including the Batmobile, grappling guns, and Batarangs. Despite wrestling with profound inner trauma, Batman\'s strict moral code—most notably his refusal to take a human life—defines his endless vigil over Gotham\'s skies.', 'Vigilante', '/assets/images/characters/batman.webp', 'Detective work, Strategy, Martial arts', 'Family tragedy → Gotham protector → Mentor', '8', NOW()),
(7, 'Monkey D. Luffy', 'Monkey D. Luffy is the exuberant, fearless, and rubber-bodied protagonist of Eiichiro Oda\'s record-shattering manga masterpiece, One Piece. Inspired as a young boy by the legendary pirate Red-Haired Shanks—who sacrificed his arm to save Luffy and entrusted him with his treasured straw hat—Luffy vowed to assemble the greatest pirate crew in the world, journey across the treacherous Grand Line, find the mythical treasure One Piece, and earn the coveted title of King of the Pirates.\n\nHaving accidentally eaten the Gum-Gum Fruit (later revealed as the mythical Sun God Nika fruit), Luffy gained a body with the properties of rubber, allowing him to stretch his limbs across immense distances and deliver devastating kinetic strikes. Throughout his voyage, he invents extraordinary combat forms known as Gears—from Gear Second\'s speed enhancement to the reality-bending joyful awakening of Gear Fifth. What truly defines Luffy, however, is his boundless optimism, infectious laughter, and fierce loyalty to his nakama; anyone who threatens the freedom or happiness of his friends will face the full fury of his Conqueror\'s Haki.', 'Captain', '/assets/images/characters/monkey-d-luffy.webp', 'Bravery, Elasticity, Crew leadership', 'East Blue voyage → Grand Line → New World', '9', NOW()),
(4, 'Wednesday Addams', 'Wednesday Addams is the brilliantly macabre, deadpan, and fiercely independent daughter of Gomez and Morticia Addams, brought to vibrant new life in the smash-hit television series Wednesday. Following a dramatic confrontation at her previous high school involving piranhas and bullies, Wednesday is enrolled at Nevermore Academy—a historic private boarding school nestled in the secluded forests of Vermont, specifically established for society\'s supernatural outcasts.\n\nPossessing burgeoning psychic visions, a sharp forensic mind, world-class fencing skills, and a penchant for playing mournful cello solos on dorm balconies, Wednesday quickly becomes embroiled in a dark mystery involving a monstrous serial-killing beast terrorizing the surrounding town of Jericho. Aided by her severed yet remarkably expressive companion, Thing, Wednesday navigates the treacherous waters of secret student societies, ancient town curses, and unlikely friendships with cheerful roomie Enid Sinclair, remaining unapologetically true to her dark, brilliant self.', 'Protagonist', '/assets/images/characters/wednesday-addams.webp', 'Observation, Investigation, Deadpan humor', 'Nevermore arrival → Mystery solved → New clues', '6', NOW());

INSERT INTO merchandise_items (category_id, title, image, tag, views, description, created_at) VALUES
(1, 'Survey Corps Cloak Replica', 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=900&q=80', 'Limited Edition', 120, 'Display cloak with embroidered wings. Showcase only — no checkout.', NOW()),
(5, 'Official Lightstick Display', 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=900&q=80', 'Pre-Order', 98, 'Acrylic stand and glow ring for desk display. Showcase only.', NOW()),
(7, 'Collector Box Set Mockup', 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=900&q=80', 'New Drop', 76, 'Slipcase mockup for a 12-volume run. Showcase only.', NOW()),
(2, 'Controller Skin Art Print', 'https://images.unsplash.com/photo-1605901309584-818e49575462?auto=format&fit=crop&w=900&q=80', 'Fan Art', 54, 'Poster-size print celebrating analog sticks and neon nights.', NOW()),
(3, 'Retro Cinema Model Ship Display', '/assets/images/spotlight/movies/1dark.jpg', 'Collector Display', 0, 'A dramatic miniature starship display piece for fans of practical movie craft. Showcase only.', NOW()),
(4, 'Mystery Academy Enamel Pin Set', '/assets/images/spotlight/tv-shows/1wed.webp', 'Limited Edition', 0, 'A themed set of enamel pins inspired by unforgettable mystery-series wardrobes. Showcase only.', NOW()),
(6, 'City Guardian Illustrated Print', '/assets/images/spotlight/comics/1batman.webp', 'Fan Art', 0, 'A high-contrast illustrated print celebrating the shadowy world of comic-book detectives. Showcase only.', NOW()),
(8, 'Chrome Armor Workshop Display Kit', '/assets/images/spotlight/cosplay/1ironman.jpg', 'New Drop', 0, 'A cosplay-craft display kit concept with armor details and convention-ready finishes. Showcase only.', NOW());

INSERT INTO events (category_id, title, city, latitude, longitude, event_date, ticket_url, description, image, created_at) VALUES
(1, 'Midnight Anime Screening', 'Tokyo', 35.6762000, 139.6503000, '2026-11-15', 'https://example.com/tickets/tokyo-anime', 'Late screening plus opening-theme live piano.', '/assets/images/fandoms/anime.jfif', NOW()),
(2, 'Indie Expo Floor', 'Los Angeles', 34.0522000, -118.2437000, '2026-11-03', 'https://example.com/tickets/la-indie', 'Playable builds, soundtrack booths, and speedrun stage.', '/assets/images/g1.jfif', NOW()),
(5, 'Lightstick Night', 'Seoul', 37.5665000, 126.9780000, '2026-11-22', 'https://example.com/tickets/seoul-kpop', 'Dance workshop and comeback watch party.', '/assets/images/kpop.jpg', NOW()),
(6, 'Gotham & Multiverse Comic-Con Panel', 'New York', 40.7128000, -74.0060000, '2026-11-29', 'https://example.com/tickets/ny-comiccon', 'Artist alley signings, live ink demonstrations, and superhero multiverse Q&A panels.', '/assets/images/fandoms/comics.jfif', NOW()),
(8, 'Cosplay Craft Con', 'London', 51.5074000, -0.1278000, '2026-12-06', 'https://example.com/tickets/london-cosplay', 'Armor foam labs and photography alley.', '/assets/images/co1.jfif', NOW()),
(7, 'Manga Library Fair', 'Osaka', 34.6937000, 135.5023000, '2026-12-14', 'https://example.com/tickets/osaka-manga', 'Publisher tables and collector swaps.', '/assets/images/ma1.jfif', NOW()),
(3, 'Sci-Fi & Practical Effects Premiere Night', 'Los Angeles', 34.0928000, -118.3287000, '2026-12-19', 'https://example.com/tickets/la-scifi-premiere', 'IMAX fan screening with miniature prop showcase and director commentary.', '/assets/images/mo1.jfif', NOW()),
(4, 'Winter Finale & Animation Watch Party', 'Toronto', 43.6532000, -79.3832000, '2027-01-10', 'https://example.com/tickets/toronto-watchparty', 'Community marathon screening, trivia showdown, and fan theory roundtable.', '/assets/images/fandoms/tv shows.jfif', NOW());

INSERT INTO media_ratings (user_id, content_id, rating, review, created_at) VALUES
(2, 2, 5, 'Trailer study is excellent. The silence cuts still work.', NOW()),
(2, 7, 4, 'Great staging notes for dance fans.', NOW()),
(1, 3, 5, 'Traversal explanation is classroom-ready.', NOW());

INSERT INTO bookmarks (user_id, content_id, notes, created_at) VALUES
(2, 1, 'Use this for club presentation.', NOW()),
(2, 7, 'Choreo references.', NOW());

INSERT INTO personal_notes (user_id, title, body, created_at) VALUES
(2, 'Watch list', 'Finish AOT study notes and collect OP timestamps.', NOW());

INSERT INTO feedback (user_id, type, message, status, created_at) VALUES
(2, 'site', 'Love the category cards. Please add more K-Pop recaps.', 'open', NOW());

CREATE TABLE IF NOT EXISTS merch_orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  order_number VARCHAR(60) NOT NULL UNIQUE,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_method VARCHAR(60) NOT NULL DEFAULT 'Cash on Delivery',
  status ENUM('pending','successful','cancelled') NOT NULL DEFAULT 'pending',
  shipping_name VARCHAR(120) NOT NULL,
  shipping_phone VARCHAR(40) NOT NULL,
  shipping_address TEXT NOT NULL,
  shipping_city VARCHAR(80) NOT NULL,
  order_notes TEXT,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_mo_user (user_id),
  INDEX idx_mo_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS merch_order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  merch_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  image VARCHAR(255) DEFAULT '',
  INDEX idx_moi_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
