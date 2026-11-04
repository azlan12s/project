<?php
ob_start();
@ini_set('display_errors', '0');

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/spotlight.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

define('UNAVAILABLE_REPLY', 'It is not available for me. I can only answer questions related to Fan Hub Plus, Anime, Cartoons, K-Pop, and our website\'s fandoms.');

function assistant_reply($answer, $items = array(), $status = 200)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    foreach ($items as &$item) {
        if (isset($item['href'])) {
            $item['href'] = site_url($item['href']);
        }
    }
    unset($item);
    http_response_code($status);
    echo json_encode(array('answer' => $answer, 'items' => $items));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    assistant_reply('Ask me anything about Anime, Cartoons, K-Pop, or Fan Hub Plus!', array(), 405);
}

$message = isset($_POST['message']) ? trim((string) $_POST['message']) : '';
if ($message === '' || strlen($message) > 960) {
    assistant_reply('Please enter a short question (up to 300 characters).', array(), 400);
}

$now = time();
$recent = isset($_SESSION['assistant_requests']) ? $_SESSION['assistant_requests'] : array();
$recent = array_values(array_filter($recent, function ($timestamp) use ($now) {
    return $timestamp > $now - 60;
}));
if (count($recent) >= 20) {
    assistant_reply('You have sent several questions just now. Please wait a moment and try again.', array(), 429);
}
$recent[] = $now;
$_SESSION['assistant_requests'] = $recent;

$q = strtolower($message);


function is_off_topic_query($q)
{

    if (preg_match('/^\s*(what\s+is\s+|calculate\s+|solve\s+)?\d+\s*[\+\-\*\/\%\^]\s*\d+/i', $q)) {
        return true;
    }

    $off_topic_keywords = array(
        'python', 'javascript', 'java code', 'c++', 'html code', 'css code', 'php code', 'sql query',
        'write a code', 'write code', 'program to', 'algorithm', 'react js', 'nodejs',
        'recipe', 'biryani', 'cooking', 'how to cook', 'how to bake',
        'weather', 'temperature in', 'forecast',
        'stock market', 'bitcoin', 'crypto', 'forex', 'tax', 'mortgage',
        'president of', 'prime minister', 'election', 'politics', 'parliament', 'capital of',
        'newton', 'thermodynamics', 'photosynthesis', 'periodic table', 'algebra', 'calculus', 'trigonometry',
        'medicine for', 'symptom', 'headache', 'diabetes', 'blood pressure',
        'essay on pollution', 'essay on cow', 'application for sick leave', 'write an email', 'cv template'
    );
    foreach ($off_topic_keywords as $kw) {
        if (strpos($q, $kw) !== false) {
            return true;
        }
    }
    return false;
}

function is_in_domain_query($q)
{
    $domain_keywords = array(

        'hi', 'hello', 'hey', 'salam', 'assalam', 'aoa', 'who are you', 'what can you do', 'help', 'fan hub', 'fanhub', 'website', 'site',

        'bookmark', 'save', 'register', 'sign up', 'join', 'account', 'login', 'log in', 'password',
        'event', 'meetup', 'convention', 'map', 'character', 'profile', 'merch', 'merchandise', 'shop',
        'article', 'write', 'submit', 'explore', 'category', 'categories', 'fandom', 'multimedia', 'video', 'audio', 'feedback', 'rating', 'review',
        'quiz', 'trivia', 'leaderboard', 'xp', 'rank', 'poll', 'polls', 'vote', 'rsvp', 'calendar',

        'anime', 'cartoon', 'cartoons', 'animation', 'animated', 'k-pop', 'kpop', 'korean pop', 'idol', 'idols',
        'manga', 'manhwa', 'webtoon', 'comic', 'comics', 'marvel', 'dc', 'superhero',
        'game', 'gaming', 'gamer', 'movie', 'movies', 'film', 'tv show', 'series', 'cosplay', 'costume',

        'naruto', 'sasuke', 'kakashi', 'itachi', 'uchiha', 'hokage', 'konoha', 'akatsuki', 'boruto',
        'dragon ball', 'goku', 'vegeta', 'gohan', 'frieza', 'broly', 'saiyan', 'shenron', 'piccolo',
        'jujutsu', 'jjk', 'gojo', 'sukuna', 'yuji', 'itadori', 'megumi', 'toji', 'shibuya',
        'one piece', 'luffy', 'zoro', 'sanji', 'nami', 'shanks', 'gear 5', 'straw hat', 'oda',
        'demon slayer', 'kimetsu', 'tanjiro', 'nezuko', 'zenitsu', 'inosuke', 'muzan', 'hashira', 'rengoku',
        'attack on titan', 'aot', 'shingeki', 'eren', 'mikasa', 'levi', 'armin', 'titan',
        'death note', 'light yagami', 'ryuk', 'kira',
        'solo leveling', 'sung jinwoo', 'jinwoo', 'bleach', 'ichigo', 'aizen',
        'my hero academia', 'mha', 'deku', 'bakugo', 'all might', 'todoroki',
        'chainsaw man', 'denji', 'makima', 'power', 'spy x family', 'anya', 'loid', 'yor',
        'hunter x hunter', 'gon', 'killua', 'kurapika', 'hisoka', 'fullmetal', 'edward elric',
        'tokyo ghoul', 'kaneki', 'blue lock', 'isagi', 'haikyuu', 'hinata', 'frieren', 'dandadan', 'kaiju',
        'shonen', 'isekai', 'studio ghibli', 'spirited away', 'totoro', 'makoto shinkai', 'your name', 'suzhou', 'mappa', 'ufotable', 'toei',

        'ben 10', 'omnitrix', 'avatar', 'aang', 'korra', 'zuko', 'spongebob', 'patrick', 'squidward',
        'tom and jerry', 'scooby', 'doraemon', 'nobita', 'shinchan', 'pokemon', 'pikachu', 'ash ketchum', 'charizard',
        'rick and morty', 'gravity falls', 'dipper', 'mabel', 'bill cipher', 'adventure time', 'finn', 'jake',
        'teen titans', 'robin', 'starfire', 'raven', 'beast boy', 'cyborg', 'gumball', 'regular show',
        'powerpuff', 'samurai jack', 'courage the cowardly dog', 'dexter', 'johnny bravo', 'looney tunes', 'bugs bunny',
        'simpsons', 'family guy', 'arcane', 'jinx', 'vi', 'spider-verse', 'miles morales', 'pixar', 'disney', 'dreamworks', 'shrek', 'kung fu panda', 'toy story', 'frozen', 'minion', 'nicholas', 'nickelodeon', 'cartoon network',

        'bts', 'bangtan', 'jungkook', 'jimin', 'taehyung', 'rm', 'namjoon', 'jin', 'suga', 'yoongi', 'j-hope', 'army',
        'blackpink', 'lisa', 'jennie', 'rose', 'jisoo', 'blink',
        'stray kids', 'skz', 'bang chan', 'felix', 'hyunjin', 'leeknow',
        'newjeans', 'hanni', 'minji', 'haerin', 'danielle', 'hyein',
        'twice', 'nayeon', 'momo', 'sana', 'tzuyu', 'jihyo', 'mina',
        'exo', 'baekhyun', 'kai', 'chanyeol', 'seventeen', 'svt', 'mingyu', 'hoshi', 'scoups',
        'aespa', 'karina', 'winter', 'ningning', 'giselle',
        'le sserafim', 'chaewon', 'sakura', 'yunjin', 'kazuha',
        'itzy', 'yeji', 'ryujin', 'txt', 'yeonjun', 'soobin', 'enhypen', 'heeseung', 'ni-ki',
        'ateez', 'g-idle', 'ive', 'wonyoung', 'babymonster', 'bigbang', 'g-dragon', 'psy', 'hybe', 'sm entertainment', 'jyp', 'yg',

        'spider-man', 'batman', 'superman', 'iron man', 'avengers', 'wolverine', 'deadpool', 'x-men', 'joker', 'doctor doom', 'dr doom', 'darth vader', 'star wars', 'dune', 'john wick', 'stranger things', 'wednesday', 'squid game', 'elden ring', 'zelda', 'valorant', 'minecraft', 'gta', 'fortnite', 'genshin', 'league of legends', 'cyberpunk', 'god of war', 'witcher'
    );

    foreach ($domain_keywords as $kw) {
        if (strpos($q, $kw) !== false) {
            return true;
        }
    }
    return false;
}

if (is_off_topic_query($q)) {
    assistant_reply(UNAVAILABLE_REPLY);
}


function find_site_matches($q)
{
    $matches = array();


    $nav_map = array(
        array('patterns' => array('bookmark', 'saved'), 'title' => 'Open Bookmarks', 'href' => '/bookmarks.php', 'label' => 'Member Tool'),
        array('patterns' => array('register', 'sign up', 'join'), 'title' => 'Join Fan Hub Plus', 'href' => '/register.php', 'label' => 'Account'),
        array('patterns' => array('login', 'log in', 'sign in'), 'title' => 'Login Page', 'href' => '/login.php', 'label' => 'Account'),
        array('patterns' => array('event', 'meetup', 'convention', 'map'), 'title' => 'Upcoming Events', 'href' => '/events.php', 'label' => 'Community Events'),
        array('patterns' => array('character', 'profile'), 'title' => 'Character Directory', 'href' => '/characters.php', 'label' => 'Characters'),
        array('patterns' => array('merch', 'merchandise', 'shop'), 'title' => 'Merchandise Showcase', 'href' => '/merch.php', 'label' => 'Merch'),
        array('patterns' => array('article', 'write', 'submit'), 'title' => 'Fan Articles', 'href' => '/articles.php', 'label' => 'Community Writing'),
        array('patterns' => array('quiz', 'trivia', 'xp', 'leaderboard', 'rank'), 'title' => 'Fandom Trivia & XP Arena', 'href' => '/quiz.php', 'label' => 'Interactive Challenge'),
        array('patterns' => array('poll', 'vote', 'versus'), 'title' => 'Community Polls', 'href' => '/polls.php', 'label' => 'Live Voting'),
        array('patterns' => array('anime'), 'title' => 'Anime Hub', 'href' => '/anime.php', 'label' => 'Category'),
        array('patterns' => array('k-pop', 'kpop', 'bts', 'blackpink', 'stray kids', 'newjeans', 'twice'), 'title' => 'K-Pop Hub', 'href' => '/k-pop.php', 'label' => 'Category'),
        array('patterns' => array('manga'), 'title' => 'Manga Hub', 'href' => '/manga.php', 'label' => 'Category'),
        array('patterns' => array('comic', 'marvel', 'dc'), 'title' => 'Comics Hub', 'href' => '/comics.php', 'label' => 'Category'),
        array('patterns' => array('game', 'gaming'), 'title' => 'Gaming Hub', 'href' => '/gaming.php', 'label' => 'Category'),
        array('patterns' => array('movie', 'film'), 'title' => 'Movies Hub', 'href' => '/movies.php', 'label' => 'Category'),
        array('patterns' => array('tv', 'show', 'series', 'cartoon'), 'title' => 'TV & Animated Shows', 'href' => '/tv-shows.php', 'label' => 'Category'),
        array('patterns' => array('cosplay'), 'title' => 'Cosplay Hub', 'href' => '/cosplay.php', 'label' => 'Category'),
    );

    foreach ($nav_map as $entry) {
        foreach ($entry['patterns'] as $pat) {
            if (strpos($q, $pat) !== false) {
                $matches[] = array('title' => $entry['title'], 'href' => $entry['href'], 'label' => $entry['label']);
                break;
            }
        }
    }


    $stop = array('the', 'and', 'for', 'with', 'from', 'about', 'find', 'show', 'what', 'where', 'which', 'this', 'that', 'are', 'was', 'how', 'can', 'you', 'please', 'tell', 'best', 'top', 'who', 'mujhe', 'kya', 'hai', 'hain', 'ka', 'ki', 'ke', 'mein', 'batao');
    $words = preg_split('/[^\p{L}\p{N}]+/u', $q, -1, PREG_SPLIT_NO_EMPTY);
    $terms = array();
    foreach ($words as $word) {
        if (strlen($word) >= 3 && !in_array($word, $stop, true) && !in_array($word, $terms, true)) {
            $terms[] = $word;
        }
    }
    $terms = array_slice($terms, 0, 5);

    if ($terms) {
        $or_parts = array();
        foreach ($terms as $term) {
            $needle = "LIKE '%" . esc($term) . "%'";
            $or_parts[] = "(contents.title $needle OR contents.body $needle OR contents.genre $needle OR categories.name $needle)";
        }
        $where = implode(' OR ', $or_parts);
        $rows = db_all("SELECT contents.id, contents.title, contents.type, contents.genre, categories.name AS category_name FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.status = 'published' AND ($where) ORDER BY contents.featured DESC, contents.popularity_score DESC LIMIT 3");
        foreach ($rows as $r) {
            $matches[] = array(
                'title' => $r['title'],
                'href'  => '/content.php?id=' . (int) $r['id'],
                'label' => $r['category_name'] . ' · ' . ucfirst($r['type']),
            );
        }


        $char_or = array();
        foreach ($terms as $term) {
            $needle = "LIKE '%" . esc($term) . "%'";
            $char_or[] = "(character_profiles.name $needle OR character_profiles.bio $needle)";
        }
        $char_rows = db_all("SELECT character_profiles.id, character_profiles.name, categories.name AS category_name FROM character_profiles JOIN categories ON categories.id = character_profiles.category_id WHERE " . implode(' OR ', $char_or) . " LIMIT 2");
        foreach ($char_rows as $cr) {
            $matches[] = array(
                'title' => $cr['name'],
                'href'  => '/character.php?id=' . (int) $cr['id'],
                'label' => $cr['category_name'] . ' Character',
            );
        }
    }


    $unique = array();
    $seen = array();
    foreach ($matches as $m) {
        if (!isset($seen[$m['href']])) {
            $seen[$m['href']] = true;
            $unique[] = $m;
        }
    }
    return array_slice($unique, 0, 4);
}


function build_site_context()
{
    $titles = db_all("SELECT contents.title, categories.name AS cat, contents.type, contents.genre FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.status = 'published' ORDER BY contents.popularity_score DESC LIMIT 12");
    $chars = db_all("SELECT character_profiles.name, categories.name AS cat, character_profiles.role_type FROM character_profiles JOIN categories ON categories.id = character_profiles.category_id LIMIT 12");
    $events = db_all("SELECT title, city, event_date FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5");

    $t_list = array();
    foreach ($titles as $t) {
        $t_list[] = $t['title'] . ' (' . $t['cat'] . ')';
    }
    $c_list = array();
    foreach ($chars as $c) {
        $c_list[] = $c['name'] . ' (' . $c['cat'] . ')';
    }
    $e_list = array();
    foreach ($events as $e) {
        $e_list[] = $e['title'] . ' in ' . $e['city'] . ' on ' . $e['event_date'];
    }

    return "Fan Hub Plus site catalogue:\n"
        . "- Categories: Anime, Gaming, Movies, TV Shows / Cartoons, K-Pop, Comics, Manga, Cosplay.\n"
        . "- Featured Anime on site: Dragon Ball, Naruto, Jujutsu Kaisen (Gojo Satoru), Demon Slayer, One Piece, Attack on Titan.\n"
        . "- Featured K-Pop on site: BTS, BLACKPINK, Stray Kids, NewJeans, TWICE.\n"
        . "- Published Titles: " . implode(', ', $t_list) . ".\n"
        . "- Characters on site: " . implode(', ', $c_list) . ".\n"
        . "- Upcoming Events: " . implode('; ', $e_list) . ".";
}


function call_ai_provider($user_message, $site_context, $ai_config)
{
    $system_prompt = "You are the official AI Chatbot for 'Fan Hub Plus', a fandom website dedicated to Anime, Cartoons (animated shows), K-Pop, Manga, Comics, Gaming, Movies, TV Shows, and Cosplay.\n\n"
        . "STRICT DOMAIN RULES:\n"
        . "1. You MUST ONLY answer questions about:\n"
        . "   - Anime, Cartoons / Animated Series, K-Pop (groups, idols, songs, albums, lore)\n"
        . "   - Manga, Comics, Gaming, Movies, TV Shows, and Cosplay\n"
        . "   - The Fan Hub Plus website (its articles, characters, events, merchandise, bookmarks, categories, login/join)\n"
        . "2. If the user asks about ANY OTHER topic (e.g., math, coding, programming, science, politics, history, cooking, weather, medical, finance, or general unrelated questions), you MUST reply with ONLY this exact sentence:\n"
        . "   \"" . UNAVAILABLE_REPLY . "\"\n"
        . "3. Keep your response concise (2 to 4 sentences), engaging, and helpful. Match the user's language (English or Roman Urdu/Hindi).\n\n"
        . $site_context;


    if (!empty($ai_config['gemini_api_key']) && function_exists('curl_init')) {
        $model = !empty($ai_config['gemini_model']) ? $ai_config['gemini_model'] : 'gemini-2.5-flash';
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($ai_config['gemini_api_key']);
        $payload = array(
            'system_instruction' => array(
                'parts' => array(array('text' => $system_prompt))
            ),
            'contents' => array(
                array('role' => 'user', 'parts' => array(array('text' => $user_message)))
            ),
            'generationConfig' => array(
                'temperature' => 0.4,
                'maxOutputTokens' => 280,
            ),
        );
        $res = http_json_post($url, $payload);
        if (isset($res['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($res['candidates'][0]['content']['parts'][0]['text']);
        }
    }


    if (!empty($ai_config['groq_api_key']) && function_exists('curl_init')) {
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $payload = array(
            'model' => !empty($ai_config['groq_model']) ? $ai_config['groq_model'] : 'llama-3.3-70b-versatile',
            'messages' => array(
                array('role' => 'system', 'content' => $system_prompt),
                array('role' => 'user', 'content' => $user_message),
            ),
            'temperature' => 0.4,
            'max_tokens' => 280,
        );
        $res = http_json_post($url, $payload, array('Authorization: Bearer ' . $ai_config['groq_api_key']));
        if (isset($res['choices'][0]['message']['content'])) {
            return trim($res['choices'][0]['message']['content']);
        }
    }


    if (!empty($ai_config['openai_api_key']) && function_exists('curl_init')) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $payload = array(
            'model' => !empty($ai_config['openai_model']) ? $ai_config['openai_model'] : 'gpt-4o-mini',
            'messages' => array(
                array('role' => 'system', 'content' => $system_prompt),
                array('role' => 'user', 'content' => $user_message),
            ),
            'temperature' => 0.4,
            'max_tokens' => 280,
        );
        $res = http_json_post($url, $payload, array('Authorization: Bearer ' . $ai_config['openai_api_key']));
        if (isset($res['choices'][0]['message']['content'])) {
            return trim($res['choices'][0]['message']['content']);
        }
    }


    if (!empty($ai_config['free_ai_enabled']) && function_exists('curl_init')) {
        $url = 'https://text.pollinations.ai/openai';
        $payload = array(
            'model' => 'openai',
            'messages' => array(
                array('role' => 'system', 'content' => $system_prompt),
                array('role' => 'user', 'content' => $user_message),
            ),
            'temperature' => 0.4,
        );
        $res = http_json_post($url, $payload, array(), 8);
        if (isset($res['choices'][0]['message']['content'])) {
            return trim($res['choices'][0]['message']['content']);
        }
    }

    return null;
}

function http_json_post($url, $payload, $extra_headers = array(), $timeout = 10)
{
    $ch = curl_init($url);
    $headers = array_merge(array('Content-Type: application/json', 'Accept: application/json'), $extra_headers);
    curl_setopt_array($ch, array(
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $code < 200 || $code >= 300) {
        return null;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}


function local_fandom_fallback($q, $matches)
{
    if (!is_in_domain_query($q) && empty($matches)) {
        return UNAVAILABLE_REPLY;
    }

    if (preg_match('/\b(hi|hello|hey|salam|assalam|aoa)\b/i', $q)) {
        return 'Hello! I am the Fan Hub Plus AI Assistant. Ask me anything about Anime, Cartoons, K-Pop, or our website\'s characters, articles, events, and merch!';
    }


    $spotlights = spotlight_data();
    foreach ($spotlights as $cat_slug => $entries) {
        foreach ($entries as $entry) {
            if (strpos($q, strtolower($entry['name'])) !== false || strpos($q, str_replace('-', ' ', $entry['key'])) !== false) {
                $works = array();
                foreach ($entry['works'] as $w) {
                    $works[] = $w[0] . ' (' . $w[1] . ')';
                }
                return $entry['name'] . ' (' . $entry['from'] . '): ' . $entry['bio'] . ' Notable releases include ' . implode(', ', $works) . '.';
            }
        }
    }


    if (strpos($q, 'gojo') !== false || strpos($q, 'jujutsu') !== false || strpos($q, 'jjk') !== false) {
        return 'Satoru Gojo is the strongest jujutsu sorcerer in Jujutsu Kaisen, wielding both the Six Eyes and the Limitless cursed technique (including Infinity and Domain Expansion: Unlimited Void). Explore our Anime section for Jujutsu Kaisen spotlights and character profiles!';
    }
    if (strpos($q, 'naruto') !== false || strpos($q, 'sasuke') !== false) {
        return 'Naruto Uzumaki is a shinobi of Konohagakure who becomes the Seventh Hokage after bonding with Kurama, the Nine-Tails. Alongside Sasuke Uchiha and Team 7, Naruto is one of the flagship anime series featured on Fan Hub Plus.';
    }
    if (strpos($q, 'goku') !== false || strpos($q, 'dragon ball') !== false || strpos($q, 'vegeta') !== false) {
        return 'Dragon Ball follows Son Goku and Vegeta across iconic arcs from Super Saiyan on Namek to Ultra Instinct in the Tournament of Power. Check out the Anime page on Fan Hub Plus for Dragon Ball movies, gallery clips, and cosplay!';
    }
    if (strpos($q, 'one piece') !== false || strpos($q, 'luffy') !== false || strpos($q, 'zoro') !== false) {
        return 'One Piece follows Monkey D. Luffy and the Straw Hat Pirates across the Grand Line in search of the ultimate treasure. Luffy\'s Gear 5 awakening and Zoro\'s three-sword style make it one of our top Anime & Manga spotlights!';
    }
    if (strpos($q, 'demon slayer') !== false || strpos($q, 'tanjiro') !== false || strpos($q, 'nezuko') !== false) {
        return 'Demon Slayer (Kimetsu no Yaiba) follows Tanjiro Kamado and his sister Nezuko as they battle Muzan Kibutsuji and the Upper Moons alongside the Hashira.';
    }
    if (strpos($q, 'bts') !== false || strpos($q, 'jungkook') !== false || strpos($q, 'taehyung') !== false || strpos($q, 'jimin') !== false) {
        return 'BTS (Bangtan Sonyeondan) is the globally iconic 7-member K-Pop group (RM, Jin, Suga, J-Hope, Jimin, V, and Jungkook) known for hits like Dynamite, Butter, and Spring Day. Visit our K-Pop hub for spotlights and merch!';
    }
    if (strpos($q, 'blackpink') !== false || strpos($q, 'lisa') !== false || strpos($q, 'jennie') !== false || strpos($q, 'jisoo') !== false) {
        return 'BLACKPINK (Jisoo, Jennie, Rosé, and Lisa) is a record-breaking K-Pop girl group famous for DDU-DU DDU-DU, Pink Venom, and How You Like That. Explore our K-Pop hub on Fan Hub Plus!';
    }
    if (strpos($q, 'stray kids') !== false || strpos($q, 'newjeans') !== false || strpos($q, 'twice') !== false || strpos($q, 'k-pop') !== false || strpos($q, 'kpop') !== false) {
        return 'Our K-Pop Hub highlights top groups including BTS, BLACKPINK, Stray Kids, NewJeans, and TWICE with music videos, comeback spotlights, and fan events!';
    }
    if (strpos($q, 'cartoon') !== false || strpos($q, 'ben 10') !== false || strpos($q, 'avatar') !== false || strpos($q, 'spongebob') !== false || strpos($q, 'tom and jerry') !== false || strpos($q, 'pokemon') !== false || strpos($q, 'doraemon') !== false || strpos($q, 'arcane') !== false) {
        return 'Classic and modern cartoons & animated series—from Avatar: The Last Airbender, Ben 10, Pokémon, Doraemon, and SpongeBob to Arcane and Spider-Verse—are celebrated across our TV Shows, Movies, and Anime sections!';
    }
    if (strpos($q, 'anime') !== false) {
        return 'Fan Hub Plus features top anime franchises including Dragon Ball, Naruto, Jujutsu Kaisen, Demon Slayer, One Piece, and Attack on Titan. Open the Anime category below to explore titles, characters, and clips!';
    }

    if (!empty($matches)) {
        return 'Here is what I found on Fan Hub Plus related to your question. Click any link below to explore!';
    }

    return 'I can help you with anything related to Anime, Cartoons, K-Pop, or Fan Hub Plus content! Try asking about a specific anime, cartoon, K-Pop group, character, or site section.';
}

$ai_config = is_file(__DIR__ . '/config/ai.php') ? require __DIR__ . '/config/ai.php' : array('free_ai_enabled' => true);
$matches = find_site_matches($q);
$site_context = build_site_context();

$ai_answer = call_ai_provider($message, $site_context, $ai_config);
if ($ai_answer === null || $ai_answer === '') {
    $ai_answer = local_fandom_fallback($q, $matches);
}


if (stripos($ai_answer, 'not available for me') !== false) {
    $matches = array();
}


try {
    ensure_srs_tables();
    $u = current_user();
    $uid_sql = $u ? (int) $u['id'] : 'NULL';
    $q_esc = esc($message);
    $a_esc = esc($ai_answer);
    db_query("INSERT INTO chatbot_queries (user_id, message, response, created_at) VALUES ($uid_sql, '$q_esc', '$a_esc', NOW())");
} catch (\Throwable $e) {

}

assistant_reply($ai_answer, $matches);

