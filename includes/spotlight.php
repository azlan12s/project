<?php
/* ---------------------------------------------------------------------------
 * Category spotlights
 *
 * Every category page opens with five curated spotlights, most famous first.
 * These are SERIES, franchises and groups, never individual characters.
 * The database only holds one or two seeded rows per category, so the well
 * known material is authored here instead of seeded per install.
 *
 * Each spotlight renders as its own headed block containing three sub blocks,
 * all on the category page: most famous movies, upcoming gallery, and most
 * famous cosplay. Nothing links to a separate detail page.
 *
 * Gallery media is a carousel of short clips, one per caption. Drop files
 * named
 *   assets/videos/spotlight/<category-slug>/<key>-<n>.mp4
 * (any mp4/webm) and slot <n> plays behind caption <n>. The matching picture
 *   assets/images/spotlight/<category-slug>/<key>-<n>.jpg
 * (any jpg/jpeg/png/webp/avif) stays as the poster and the fallback, so a
 * missing clip simply shows its still and a missing picture falls back to a
 * labelled gradient. Nothing ever 404s.
 * ------------------------------------------------------------------------- */

function spotlight_data()
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }

    $data = array(

        /* ------------------------------------------------------------- anime */
        'anime' => array(
            array(
                'key'  => 'dragon-ball',
                'name' => 'Dragon Ball',
                'from' => 'Toei, 1986',
                'line' => 'The series that set the template for shonen action and is still chasing it.',
                'bio'  => 'A wandering martial artist searches for the dragon balls his grandfather left behind. Four decades of tournaments, transformations and world villains, and the reason modern anime power scaling exists at all.',
                'works' => array(
                    array('Dragon Ball Z: Battle of Gods', 2013),
                    array('Dragon Ball Super: Broly', 2018),
                    array('Dragon Ball Super: Super Hero', 2022),
                ),
                'gallery' => array('Kame House training ground', 'First Super Saiyan transformation', 'Cell Games final battle', 'Tournament of Power entrance'),
                'cosplay' => array('Goku orange gi and boots', 'Vegeta blue battle armour', 'Super Saiyan 3 rig with tail', 'Shenron emblem cape'),
            ),
            array(
                'key'  => 'naruto',
                'name' => 'Naruto',
                'from' => 'Shueisha / Pierrot, 2002',
                'line' => 'A village that mocked its own hero is the engine of the whole story.',
                'bio'  => 'An orphan with a sealed nine tailed fox tries to earn recognition from the village that exiled him. The manga ran fifteen years, and the anime is the version most people actually saw.',
                'works' => array(
                    array('Naruto the Movie: Ninja Clash in the Land of Snow', 2006),
                    array('Naruto Shippuden the Movie: The Battle of Raikage', 2008),
                    array('Boruto: Naruto the Movie', 2015),
                ),
                'gallery' => array('Ninja academy graduation', 'Chunin exam final round', 'Pain arc rooftop fight', 'Nine tails sage mode'),
                'cosplay' => array('Naruto orange jumpsuit', 'Sasuke with Sharingan headband', 'Akatsuki cloak', 'Chunin flak jacket set'),
            ),
            array(
                'key'  => 'jujutsu-kaisen',
                'name' => 'Jujutsu Kaisen',
                'from' => 'Shueisha / MAPPA, 2020',
                'line' => 'Modern jujutsu horror where the strongest student is also the teacher.',
                'bio'  => 'Cursed spirits, a dying vocation, and a six eyes sorcerer who makes the job look easy. Manga and anime run in parallel, with the prequel films filling in how that power was inherited.',
                'works' => array(
                    array('Jujutsu Kaisen Season 1', 2020),
                    array('Jujutsu Kaisen 0', 2021),
                    array('Jujutsu Kaisen Season 2', 2023),
                ),
                'gallery' => array('First Sukuna possession', 'Blindfold reveal', 'Domain Expansion', 'Shibuya incident'),
                'cosplay' => array('Gojo blindfold and uniform', 'Yuji pink hair and uniform', 'Sukuna face markings', 'Domain expansion effect build'),
            ),
            array(
                'key'  => 'demon-slayer',
                'name' => 'Demon Slayer: Kimetsu no Yaiba',
                'from' => 'Shueisha / ufotable, 2019',
                'line' => 'A grief story with a very sharp sword in it.',
                'bio'  => 'A swordsman loses his family to a demon and joins the corps to get one of them back. ufotable turned a fairly standard shonen premise into the best fight animation of its decade.',
                'works' => array(
                    array('Demon Slayer: Mugen Train', 2021),
                    array('Demon Slayer: Swordsmith Village Arc', 2023),
                    array('Demon Slayer: Hashira Training Arc', 2024),
                ),
                'gallery' => array('Mount Natagumo night attack', 'Water Breathing form', 'Reunion with Nezuko', 'Final selection with every Hashira'),
                'cosplay' => array('Tanjiro haori and earrings', 'Nezuko bamboo muzzle', 'Inosuke and Zenitsu duo', 'Breathing form effect build'),
            ),
            array(
                'key'  => 'one-punch-man',
                'name' => 'One Punch Man',
                'from' => 'Shueisha / Madhouse, 2015',
                'line' => 'A hero request granted too literally, and the comedy that follows.',
                'bio'  => 'A salaryman trains until he can defeat any opponent in a single punch, then discovers there is no fight worth having. The manga and the anime sit very differently, which matters before you start either.',
                'works' => array(
                    array('One Punch Man Season 1', 2015),
                    array('One Punch Man: The Movie', 2015),
                    array('One Punch Man Season 2', 2019),
                ),
                'gallery' => array('Serious punch impact frame', 'Hero association reveal', 'S class hero ranking test', 'Monster association standoff'),
                'cosplay' => array('Saitama yellow suit and red cape', 'Genos and Tatsumaki duo', 'Hero association casual outfit', 'Monster association suit'),
            ),
        ),

        /* ------------------------------------------------------------ movies */
        'movies' => array(
            array(
                'key'  => 'mcu',
                'name' => 'Marvel Cinematic Universe',
                'from' => 'Interconnected films, 2008',
                'line' => 'Eleven films of consequences, where one problem becomes the next film.',
                'bio'  => 'Built on a shared cast and a running plot thread, which is why the studio could keep making set pieces other franchises only dream of. The first film is a completely different show from the last one.',
                'works' => array(
                    array('Iron Man', 2008),
                    array('The Avengers', 2012),
                    array('Avengers: Endgame', 2019),
                ),
                'gallery' => array('Arc reactor close up', 'Helicarrier breach', 'Titan time heist', 'Thanos snap'),
                'cosplay' => array('Iron Man arc reactor', 'Captain America shield', 'Thor lightning build', 'Infinity gauntlet snap'),
            ),
            array(
                'key'  => 'dark-knight',
                'name' => 'The Dark Knight Trilogy',
                'from' => 'Christopher Nolan, 2005',
                'line' => 'Three films that made a comic book police procedural feel like a thriller.',
                'bio'  => 'Shot on large formats, scored hard, and treated as crime drama rather than superhero shorthand. It is still the blueprint most later franchise films are measured against.',
                'works' => array(
                    array('Batman Begins', 2005),
                    array('The Dark Knight', 2008),
                    array('The Dark Knight Rises', 2012),
                ),
                'gallery' => array('Bat signal over Gotham', 'Tumbler road chase', 'Rooftop silhouette', 'Prisoner transport finale'),
                'cosplay' => array('Nolan batsuit', 'Bat signal lamp', 'Joker origin makeup', 'Gotham police badge prop'),
            ),
            array(
                'key'  => 'bond',
                'name' => 'James Bond',
                'from' => 'Eon Productions, 1962',
                'line' => 'Sixty years of the same job, remade by every generation and never retired.',
                'bio'  => 'The oldest continuing character in mainstream cinema. Twenty seven films, seven actors, and a spy who is consistently better dressed than the situation allows.',
                'works' => array(
                    array('Casino Royale', 2006),
                    array('Skyfall', 2012),
                    array('No Time to Die', 2021),
                ),
                'gallery' => array('Aston Martin DB5', 'Tuxedo ballroom scene', 'Gun barrel title card', 'Helicopter chase'),
                'cosplay' => array('Connery tuxedo', 'Craig navy suit', 'Walther PPK', 'Casino Royale martini'),
            ),
            array(
                'key'  => 'spider-man',
                'name' => 'Spider-Man',
                'from' => 'Sam Raimi trilogy, 2002',
                'line' => 'The same character, filmed four different ways, all of them work.',
                'bio'  => 'Three separate film series plus the animated Spider-Verse, and the guilt of powers nobody asked for survives every reboot. Best starting point is whichever version you will actually sit through.',
                'works' => array(
                    array('Spider-Man 2', 2004),
                    array('Spider-Man: Into the Spider-Verse', 2018),
                    array('Spider-Man: No Way Home', 2021),
                ),
                'gallery' => array('Web swinging over the city', 'Rain scene power loss', 'Spider-Verse leap', 'Multiverse wall', 'Final swing'),
                'cosplay' => array('Red and blue suit', 'Symbiote black suit', 'Spider-Verse hoodie', 'Web shooter gauntlets'),
            ),
            array(
                'key'  => 'harry-potter',
                'name' => 'Harry Potter',
                'from' => 'Warner Bros., 2001',
                'line' => 'Eight films, one very simple plot shape, held for a very long time.',
                'bio'  => 'An adopted kid discovers the school was named after him. The series is remarkable for how long it holds that shape without anyone feeling let down by the ending.',
                'works' => array(
                    array('Harry Potter and the Philosopher Stone', 2001),
                    array('Harry Potter and the Prisoner of Azkaban', 2004),
                    array('Harry Potter and the Deathly Hallows Part 2', 2011),
                ),
                'gallery' => array('Great Hall feast', 'Sorting Hat moment', 'Broom chase in the rain', 'Final battle at Hogwarts', 'Platform 9¾ departure'),
                'cosplay' => array('Gryffindor robe', 'Sorting Hat', 'Elder Wand', 'Hogwarts scarf'),
            ),
        ),

        /* --------------------------------------------------------- tv-shows */
        'tv-shows' => array(
            array(
                'key'  => 'stranger-things',
                'name' => 'Stranger Things',
                'from' => 'Netflix, 2016',
                'line' => 'Small town, missing children, and a lab that never closed cleanly.',
                'bio'  => 'A horror plot dressed as a period adventure about a group of kids. The monster is real, but the friendship is what the show is actually about and what survives the rest.',
                'works' => array(
                    array('Stranger Things Season 1', 2016),
                    array('Stranger Things Season 2', 2017),
                    array('Stranger Things Season 4', 2022),
                ),
                'gallery' => array('Christmas lights reveal', 'Walkie talkie contact', 'Creature in the dark', 'Hawkins lab corridors'),
                'cosplay' => array('Dustin vest and glasses', 'Eleven costume and wig', 'Upside Down set piece', 'Creature suit build'),
            ),
            array(
                'key'  => 'breaking-bad',
                'name' => 'Breaking Bad',
                'from' => 'AMC, 2008',
                'line' => 'A chemistry teacher who turns a diagnosis into a business.',
                'bio'  => 'Every decision narrows his options until the only move left is the worst one. Widely treated as the most accurate portrait of a bad man ever put on television.',
                'works' => array(
                    array('Breaking Bad Pilot', 2008),
                    array('Breaking Bad Season 3', 2010),
                    array('El Camino', 2019),
                ),
                'gallery' => array('Trailer at night', 'Desert standoff', 'Production floor', 'Train heist'),
                'cosplay' => array('Heisenberg hat and glasses', 'Los Pollos Hermanos tee', 'Pineapple mask', 'Hazmat suit'),
            ),
            array(
                'key'  => 'game-of-thrones',
                'name' => 'Game of Thrones',
                'from' => 'HBO, 2011',
                'line' => 'Noble houses fight over a throne while the real threat sits outside the map.',
                'bio'  => 'A medieval power struggle with a dragon problem. It invented modern television drama, then spent its final two seasons arguing about its own invention.',
                'works' => array(
                    array('Game of Thrones Season 1', 2011),
                    array('Game of Thrones Season 4', 2014),
                    array('Game of Thrones Season 8', 2019),
                ),
                'gallery' => array('The Wall breach', 'Dragon over the city', 'Red Wedding', 'Iron Throne room'),
                'cosplay' => array('White Walker makeup', 'Iron Throne prop', 'Hand of the King pin', 'Dothraki costume'),
            ),
            array(
                'key'  => 'last-of-us',
                'name' => 'The Last of Us',
                'from' => 'HBO, 2023',
                'line' => 'Two people crossing a country that no longer has a government.',
                'bio'  => 'A game adaptation that kept the quiet pacing and let the relationship carry the weight. The infected are the least of the problem and the least of the story.',
                'works' => array(
                    array('The Last of Us', 2013),
                    array('The Last of Us Part II', 2020),
                    array('The Last of Us Season 1', 2023),
                ),
                'gallery' => array('Road trip aftermath', 'Cordyceps spore wall', 'Quarantine zone street', 'Fireflies radio scene'),
                'cosplay' => array('Firefly denim jacket', 'Clicker infected makeup', 'Backpack prop', 'Joel and Ellie duo set'),
            ),
            array(
                'key'  => 'wednesday',
                'name' => 'Wednesday',
                'from' => 'Netflix, 2022',
                'line' => 'A deadpan goth investigates murders without ever smiling.',
                'bio'  => 'A school mystery played completely straight, which is what makes the jokes land. Two seasons, and the lead never raises her voice or blinks twice.',
                'works' => array(
                    array('Wednesday Season 1', 2022),
                    array('Wednesday Season 2', 2024),
                ),
                'gallery' => array('Nevermore main hall', 'Raven Tower exterior', 'Cozy scream sequence', 'Hand reveal prop'),
                'cosplay' => array('Nevermore uniform', 'Braids and pallor makeup', 'Raven banner prop', 'Cozy pyjama set'),
            ),
        ),

        /* ------------------------------------------------------------- k-pop */
        'k-pop' => array(
            array(
                'key'  => 'bts',
                'name' => 'BTS',
                'from' => 'Big Hit, 2013',
                'line' => 'Seven members who turned self filmed concert footage into a global language.',
                'bio'  => 'Formed on a survival show and built a self produced era before the label fully knew how to market it. The most decorated act in the industry on the awards circuit.',
                'works' => array(
                    array('Love Yourself: Break', 2018),
                    array('Map of the Soul: 7', 2020),
                    array('Proof', 2022),
                ),
                'gallery' => array('Sold out stadium lights', 'Crowd banners', 'Music awards stage', 'Member solo stage'),
                'cosplay' => array('Army lightstick replica', 'Map of the Soul 7 jacket', 'Purple army outfit', 'Concert dome look'),
            ),
            array(
                'key'  => 'blackpink',
                'name' => 'BLACKPINK',
                'from' => 'YG Entertainment, 2016',
                'line' => 'Four members, four lanes, one very loud debut era.',
                'bio'  => 'Built on the contrast between rap, vocal and stage presence. Their title tracks and fashion travel almost as far as the music itself.',
                'works' => array(
                    array('Square One', 2016),
                    array('Kill This Love', 2019),
                    array('Born Pink', 2022),
                ),
                'gallery' => array('Headlining festival slot', 'Ddu Du performance', 'Solo stages', 'Fashion week front row'),
                'cosplay' => array('Festival pink look', 'Ddu Ddu stage outfits', 'Lightstick set', 'Pretty Face era styling'),
            ),
            array(
                'key'  => 'stray-kids',
                'name' => 'Stray Kids',
                'from' => 'JYP Entertainment, 2018',
                'line' => 'A self produced idol group assembled by a survival show.',
                'bio'  => 'The members write and produce most of their own catalogue, which is why it sounds unusually loud and unusually self contained. The fanbase is famously the loudest in the room.',
                'works' => array(
                    array('GO LIVE', 2020),
                    array('ROCK-STAR', 2023),
                    array('ODDINARY', 2023),
                ),
                'gallery' => array('Concert encore', 'MANIAC performance', 'Fan sign close up', 'World tour finale'),
                'cosplay' => array('Group lightstick', 'Maniac stage outfits', 'Rock star leather styling', 'Fan meeting set'),
            ),
            array(
                'key'  => 'twice',
                'name' => 'TWICE',
                'from' => 'JYP Entertainment, 2015',
                'line' => 'Bright, friendly and unusually consistent for a nine year run.',
                'bio'  => 'Three vocalists, a lot of variety show appearances, and a catalogue that keeps landing the same kind of hook. The group dynamic is the product.',
                'works' => array(
                    array('Cheer Up', 2016),
                    array('What is Love?', 2018),
                    array('Set Me Free Pt. 2', 2024),
                ),
                'gallery' => array('Dome concert finale', 'Music show win stage', 'Fan meeting wall', 'Japan tour crowd'),
                'cosplay' => array('Cheer Up candy styling', 'What is Love outfits', 'Group lightstick', 'Tour merch set'),
            ),
            array(
                'key'  => 'aespa',
                'name' => 'aespa',
                'from' => 'SM Entertainment, 2020',
                'line' => 'Four members and an animated counterpart world built on one idea.',
                'bio'  => 'Every track is paired with an animated alter ego, so the project is half live performance and half virtual concept. Unusual in scale, and it worked.',
                'works' => array(
                    array('Black Mamba', 2020),
                    array('Next Level', 2021),
                    array('Armageddon', 2024),
                ),
                'gallery' => array('World tour stage', 'ae world visual scenes', 'Rooftop concept art', 'Coachella set'),
                'cosplay' => array('ae world avatar styling', 'Armageddon outfits', 'Character tee', 'World tour lightstick'),
            ),
        ),

        /* ------------------------------------------------------------ manga */
        'manga' => array(
            array(
                'key'  => 'one-piece',
                'name' => 'One Piece',
                'from' => 'Shueisha, 1997',
                'line' => 'A crew sails toward one treasure, and the island in the way is always the story.',
                'bio'  => 'The best selling manga in history, built on a deliberately simple promise that has been kept for over a quarter century. Almost every arc introduces an island rather than a new villain.',
                'works' => array(
                    array('One Piece: Baron Omatsuri and the Secret Island', 2005),
                    array('One Piece Film Z', 2012),
                    array('One Piece Film Red', 2022),
                ),
                'gallery' => array('Crew portrait spread', 'Grand Line charts', 'Fish Man Island arc', 'Final arc at Laugh Tale'),
                'cosplay' => array('Straw hat and strawhat', 'Wanted poster', 'Crew coat set', 'Grand Line map scroll'),
            ),
            array(
                'key'  => 'naruto-manga',
                'name' => 'Naruto',
                'from' => 'Shueisha, 1999',
                'line' => 'The village that mocked him becomes the reason he keeps going.',
                'bio'  => 'The original has far more patience about world building than the anime adaptation did. Every arc earns its next fight with an argument first, which is the template the genre still copies.',
                'works' => array(
                    array('Naruto chapter 1: Naruto Uzumaki', 1999),
                    array('Naruto: The Pain and the Great Bridge', 2004),
                    array('Naruto Final Chapter', 2014),
                ),
                'gallery' => array('Academy graduation day', 'Chunin exam final round', 'Pain arc rooftop', 'Final chapter page'),
                'cosplay' => array('Academy uniform', 'Ninja registration headband', 'Scroll and kunai set', 'War cloak'),
            ),
            array(
                'key'  => 'berserk',
                'name' => 'Berserk',
                'from' => 'Hakusensha, 1989',
                'line' => 'A brute of a swordsman in a world rendered unforgivingly.',
                'bio'  => 'Known for astonishing detail, and for the eclipse arc that every new reader gets warned about. The artwork is the reason to keep going through a story that is not a comfortable read.',
                'works' => array(
                    array('Berserk Golden Age arc', 1989),
                    array('Berserk: The Eclipse', 2003),
                    array('Berserk Volume 41', 2022),
                ),
                'gallery' => array('Golden Age tavern', 'Eclipse sequence', 'Beast of Darkness', 'Falcon of Light panel'),
                'cosplay' => array('Berserker armour', 'Beast of Darkness prop', 'Dragon slayer sword', 'Eye prosthetic makeup'),
            ),
            array(
                'key'  => 'attack-on-titan',
                'name' => 'Attack on Titan',
                'from' => 'Kodansha, 2009',
                'line' => 'Humanity behind walls, and a job nobody wants to take.',
                'bio'  => 'Starts as a plain survival premise and steadily changes what the walls were actually for. The structural reveal is the whole point of the first half.',
                'works' => array(
                    array('Attack on Titan Volume 1', 2009),
                    array('Attack on Titan: No Retreat', 2015),
                    array('Attack on Titan Final Season', 2023),
                ),
                'gallery' => array('Wall Maria breach', 'ODM gear launch', 'Colossal Titan reveal', 'Ground at Shiganshina'),
                'cosplay' => array('Survey Corps cloak and wings', 'ODM gas rig', 'Wall gate keys', 'Colossal Titan suit'),
            ),
            array(
                'key'  => 'chainsaw-man',
                'name' => 'Chainsaw Man',
                'from' => 'Shueisha, 2018',
                'line' => 'A devil hunter who keeps making worse deals to stay alive.',
                'bio'  => 'Drawn in a scratchy modern style that suits a story about poverty and bargaining. It moves fast, and the panel work is deliberately rough around the edges.',
                'works' => array(
                    array('Chainsaw Man Volume 1', 2018),
                    array('Chainsaw Man: Reze arc', 2022),
                    array('Chainsaw Man Volume 20', 2024),
                ),
                'gallery' => array('Public safety devil hunt', 'Chainsaw transformation', 'Reze arc alley fight', 'Devil hierarchy chart'),
                'cosplay' => array('Chainsaw devil helmet', 'Public Safety uniform', 'Reze arc outfit', 'Devil contract prop'),
            ),
        ),

        /* ----------------------------------------------------------- comics */
        'comics' => array(
            array(
                'key'  => 'amazing-spider-man',
                'name' => 'The Amazing Spider-Man',
                'from' => 'Marvel, 1963',
                'line' => 'The wall crawler, in print longer than most film superheroes have existed.',
                'bio'  => 'Introduced in 1962 and still the same joke and the same guilt. The Lee and Ditko issues are the reason the character works, and they are still the benchmark every later run is compared against.',
                'works' => array(
                    array('The Amazing Spider-Man #1', 1963),
                    array('The Amazing Spider-Man #121', 1973),
                    array('The Amazing Spider-Man #282', 1985),
                ),
                'gallery' => array('Ditko cover art', 'Wall crawling panel', 'Goblin green pages', 'Black suit run'),
                'cosplay' => array('Ditko era suit', 'Symbiote black suit', 'Spider signal pin', 'Web shooter gauntlets'),
            ),
            array(
                'key'  => 'batman-comics',
                'name' => 'Batman',
                'from' => 'DC, 1940',
                'line' => 'A detective in a cowl, eighty years before the films caught up.',
                'bio'  => 'Drawn first in 1939 and reinvented by every decade since. The films, which came from these comics, now feed straight back into how the print version gets read.',
                'works' => array(
                    array('Batman #1', 1940),
                    array('The Killing Joke', 1988),
                    array('The Long Halloween', 1990),
                ),
                'gallery' => array('Bob Kane cover art', 'Gotham skyline spreads', 'Arkham interiors', 'Knightfall panels'),
                'cosplay' => array('Comic era batsuit', 'Gotham police badge prop', 'Bat signal lamp', 'Arkham prop set'),
            ),
            array(
                'key'  => 'superman',
                'name' => 'Superman',
                'from' => 'DC, 1938',
                'line' => 'The first costumed hero, and still the one everyone is argued against.',
                'bio'  => 'The template that arrived in 1938 and is still doing the job. What the comics do better than the films is the loneliness of being the strongest person in every room.',
                'works' => array(
                    array('Action Comics #1', 1938),
                    array('Whatever Happened to the Man of Tomorrow', 1986),
                    array('All Star Superman', 2008),
                ),
                'gallery' => array('Action Comics #1 cover', 'Daily Planet rooftop', 'Fortress of Solitude', 'All Star colour spreads'),
                'cosplay' => array('Classic red and blue suit', 'Fortress crystals', 'Daily Planet prop', 'Kryptonian crest'),
            ),
            array(
                'key'  => 'deadpool',
                'name' => 'Deadpool',
                'from' => 'Marvel, 1997',
                'line' => 'Knows the fourth wall is there and bills you for knowing it.',
                'bio'  => 'A mercenary held together by a healing factor and a mouth. The comics lean on unreliable narration years before the films built an entire franchise on the same joke.',
                'works' => array(
                    array('Deadpool #1', 1997),
                    array('Deadpool: The Gauntlet', 2010),
                    array('Deadpool: Back to Black', 2018),
                ),
                'gallery' => array('Issue #1 cover', 'Panel box art', 'Fourth wall breaks', 'Merc work montage'),
                'cosplay' => array('Suit and mask pair', 'Katanas and chimera prop', 'Chimera effect build', 'Speech bubble props'),
            ),
            array(
                'key'  => 'venom',
                'name' => 'Venom',
                'from' => 'Marvel, 1988',
                'line' => 'A villain who turned out to be the best part of the book he came from.',
                'bio'  => 'A symbiote with a monstrous appetite, first written as the thing Spider-Man fears most. The second symbiote book fixed the character completely and he has been a lead since.',
                'works' => array(
                    array('The Amazing Spider-Man #375', 1988),
                    array('Venom: Lethal Protector', 2012),
                    array('Venom Volume 3', 2016),
                ),
                'gallery' => array('The Costume origin', 'Toxin panels', 'Claw and tendril spreads', 'Agent Venom run'),
                'cosplay' => array('Black suit with white eyes', 'Claw effect build', 'Symbiote tendril makeup', 'Partial transform makeup'),
            ),
            array(
                'key'  => 'homelander',
                'name' => 'The Boys (Homelander)',
                'from' => 'WildStorm / Dynamite, 2006',
                'line' => 'What happens when corporate superheroes answer to nobody.',
                'bio'  => 'Garth Ennis and Darick Robertson built a brutal satire of superhero celebrity culture led by Homelander, the terrifyingly smiling leader of The Seven.',
                'works' => array(
                    array('The Boys #1: The Name of the Game', 2006),
                    array('The Boys: Herogasm', 2009),
                    array('The Boys: Over the Hill with the Swords of a Thousand Men', 2012),
                ),
                'gallery' => array('Vought Tower portrait', 'The Seven press stage', 'Laser eyes confrontation', 'Skyline cape descent', 'Final issue showdown'),
                'cosplay' => array('Seven flag cape and shoulder eagles', 'Textured blue suit', 'Glowing red eye lenses', 'Vought badge prop'),
            ),
            array(
                'key'  => 'invincible',
                'name' => 'Invincible (Mark Grayson)',
                'from' => 'Image Comics, 2003',
                'line' => 'A teenage superhero discovers what his father\'s empire really is.',
                'bio'  => 'Robert Kirkman, Cory Walker, and Ryan Ottley crafted a 144-issue superhero epic where every battle leaves permanent scars and the status quo never resets.',
                'works' => array(
                    array('Invincible #1: Family Matters', 2003),
                    array('Invincible: The Viltrumite War', 2010),
                    array('Invincible #144: The End of All Things', 2018),
                ),
                'gallery' => array('First flight over the city', 'Guardians of the Globe call', 'Omni-Man mountain clash', 'Conquest battle damage', 'Coalition of Planets'),
                'cosplay' => array('Yellow and blue Invincible suit', 'Padded lens goggles', 'Battle-damaged mask variant', 'Viltrumite emblem cape'),
            ),
        ),

        /* ---------------------------------------------------------- cosplay */
        'cosplay' => array(
            array(
                'key'  => 'naruto',
                'name' => 'Naruto',
                'from' => 'Anime',
                'line' => 'The most cosplayed anime there is, and the most forgiving to start on.',
                'bio'  => 'Orange and blue is a two colour build, the headband is a quick prop, and the silhouette reads from the back of a hall. Most first cosplay projects begin here for good reason.',
                'works' => array(
                    array('Naruto Shippuden', 2007),
                    array('Naruto: Ultimate Ninja Storm', 2016),
                    array('Boruto: Naruto the Movie', 2015),
                ),
                'gallery' => array('Full costume front', 'Headband detail', 'Whisker and cap close up', 'Convention floor test'),
                'cosplay' => array('Orange jumpsuit and headband', 'Sage mode cloak and lenses', 'Akatsuki cloak', 'Chunin flak jacket'),
            ),
            array(
                'key'  => 'dragon-ball',
                'name' => 'Dragon Ball',
                'from' => 'Anime',
                'line' => 'Big silhouettes, loud colours, and a lot of foam work.',
                'bio'  => 'The wigs alone make this a category. Saiyan hair is a sculpting project, so budget for that before the costume itself, which is otherwise quite simple.',
                'works' => array(
                    array('Dragon Ball Z', 1989),
                    array('Dragon Ball: Sparking Zero', 2022),
                    array('Dragon Ball Super', 2015),
                ),
                'gallery' => array('Super Saiyan wig sculpt', 'Armour shoulder detail', 'Boots and belt set', 'Transformation lighting test'),
                'cosplay' => array('Sculpted Super Saiyan wig', 'Goku orange gi', 'Vegeta blue armour', 'Shenron emblem cape'),
            ),
            array(
                'key'  => 'one-piece',
                'name' => 'One Piece',
                'from' => 'Anime and manga',
                'line' => 'Straw hats and wanted posters, so the props do most of the work.',
                'bio'  => 'Cheap to start and instantly recognisable across a room. A painted coat and a poster do more for the read than expensive tailoring would.',
                'works' => array(
                    array('One Piece anime', 1999),
                    array('One Piece Film Red', 2022),
                    array('One Piece: Pirate Warriors', 2020),
                ),
                'gallery' => array('Straw hat close up', 'Coat and sash set', 'Wanted poster prop', 'Full look convention'),
                'cosplay' => array('Straw hat with chin strap', 'Open front coat', 'Wanted poster print', 'Sash and pouch set'),
            ),
            array(
                'key'  => 'marvel',
                'name' => 'Iron Man',
                'from' => 'Movies and comics',
                'line' => 'The most built suit in cosplay, because the armour is the costume.',
                'bio'  => 'A full body print under armour pieces that all share one colour map. The nanotech pattern is where accuracy is won or lost, and it takes the longest.',
                'works' => array(
                    array('Iron Man 2008', 2008),
                    array('Avengers: Endgame', 2019),
                    array('Mark 85 sixth scale', 2019),
                ),
                'gallery' => array('Helmet and chest plate', 'Nanotech texture detail', 'Full suit back view', 'Repulsor light test'),
                'cosplay' => array('Printed bodysuit', '3D printed helmet', 'Arc reactor chest piece', 'Gauntlet LED build'),
            ),
            array(
                'key'  => 'dc',
                'name' => 'Batman',
                'from' => 'Comics and films',
                'line' => 'Armour plates and hard edges, with no skin showing anywhere.',
                'bio'  => 'The tactical suit is the most forgiving Batman build because it covers the whole body. Silicone and leather give the right surface, and the cowl is the part worth the time.',
                'works' => array(
                    array('Batman #1', 1940),
                    array('The Dark Knight Rises', 2012),
                    array('The Batman', 2022),
                ),
                'gallery' => array('Suit shoulder detail', 'Cowl and jaw line', 'Back cape drape', 'Cape and gauntlet set'),
                'cosplay' => array('Shoulder armour build', 'Cowl with lens eyes', 'Cape and gauntlets', 'Utility belt rig'),
            ),
        ),

        /* ------------------------------------------------------------ gaming */
        'gaming' => array(
            array(
                'key'  => 'witcher',
                'name' => 'The Witcher',
                'from' => 'CD Projekt RED, 2007',
                'line' => 'Monster hunting and moral grey areas, in that order of importance.',
                'bio'  => 'Three games and a book series, built on licensed folklore rather than invented lore. The third game is the one everyone means, and its side quests are the reason people still replay it.',
                'works' => array(
                    array('The Witcher', 2007),
                    array('The Witcher 2: Assassins of Kings', 2011),
                    array('The Witcher 3: Wild Hunt', 2015),
                ),
                'gallery' => array('Medallion close up', 'Kaer Morhen battlements', 'Open world horseback', 'Monster bestiary screens'),
                'cosplay' => array('Witcher medallion prop', 'Silver sword and sheath', 'Rider armour build', 'Cat school hood'),
            ),
            array(
                'key'  => 'god-of-war',
                'name' => 'God of War',
                'from' => 'Santa Monica Studio, 2004',
                'line' => 'One grudge, carried across three games and a reboot.',
                'bio'  => 'Went from flat shaded sprite to the most photographed game character in the genre, and the reboot kept the anger while changing everything else. The axe is the build everyone wants.',
                'works' => array(
                    array('God of War III', 2010),
                    array('God of War', 2018),
                    array('God of War Ragnarok', 2022),
                ),
                'gallery' => array('Leviathan axe close up', 'Norse axe reveal', 'Ash and red tattoo detail', 'Boy and companion shot'),
                'cosplay' => array('Leviathan axe build', 'Norse leather armour', 'Red ash makeup', 'Blades of Chaos prop'),
            ),
            array(
                'key'  => 'last-of-us-game',
                'name' => 'The Last of Us',
                'from' => 'Naughty Dog, 2013',
                'line' => 'A survival horror game that is really about two people.',
                'bio'  => 'Two games, one remake and a TV adaptation, and the costume design is worn civilian clothing in every version. That makes it the easiest game cosplay to wear out in public.',
                'works' => array(
                    array('The Last of Us', 2013),
                    array('The Last of Us Part II', 2020),
                    array('The Last of Us Part I', 2022),
                ),
                'gallery' => array('Jackson day scenes', 'Underground tunnels', 'Backpack detail', 'Guitar scene'),
                'cosplay' => array('Firefly denim jacket', 'Worn jeans and boots', 'Backpack prop', 'Joel and Ellie duo set'),
            ),
            array(
                'key'  => 'street-fighter',
                'name' => 'Street Fighter',
                'from' => 'Capcom, 1987',
                'line' => 'The answer to what if the training montage actually worked.',
                'bio'  => 'Fought since 1987 and still the reference fighting game. The costumes are the simplest in the genre, which is exactly why they have aged better than almost anything newer.',
                'works' => array(
                    array('Street Fighter II: The World Warrior', 1991),
                    array('Street Fighter IV', 2008),
                    array('Street Fighter 6', 2023),
                ),
                'gallery' => array('World Warrior sprite study', 'Hadouken pose', 'Costume colour references', 'Tournament stage shots'),
                'cosplay' => array('White gi and red belt', 'Red hachimaki', 'Fighting gloves', 'Shirtless alt costume'),
            ),
            array(
                'key'  => 'super-mario',
                'name' => 'Super Mario',
                'from' => 'Nintendo, 1981',
                'line' => 'The reason the platformer genre exists, and the reason cosplay exists.',
                'bio'  => 'One plumber, one plumber problem, and the clearest character silhouette in games. Almost everyone has worn some version of it at some point, which makes it the safest first costume there is.',
                'works' => array(
                    array('Super Mario Bros.', 1985),
                    array('Super Mario Odyssey', 2017),
                    array('Super Mario Bros. Wonder', 2023),
                ),
                'gallery' => array('Overworld sprite study', 'Power up state grid', 'Cap and moustache detail', 'Mushroom kingdom backdrop'),
                'cosplay' => array('Red cap and blue overalls', 'Mushroom power up suit', 'Gold star prop', 'Plush character set'),
            ),
        ),
    );

    return $data;
}

/* Spotlights for one category, always five, most famous first. */
function spotlight_for($slug)
{
    $all = spotlight_data();
    $slug = (string) $slug;
    return isset($all[$slug]) ? $all[$slug] : array();
}

/* Filename stems a spotlight accepts, keyed by category and then by key. The
   seeded uploads were never renamed, so the matcher looks for a stem that
   appears anywhere in the filename rather than demanding <key>-<n>. */
function spotlight_media_aliases()
{
    return array(
        'anime' => array(
            'dragon-ball' => 'gohan|vegeta|piccolo|frieza|dragon',
            'naruto' => 'naruto|sasuke|sakura|kakashi|itachi',
            'jujutsu-kaisen' => 'gojo|yuji|sukuna|megumi|nobara',
            'demon-slayer' => 'tanjiro|nezuko|rengoku|zenitsu|inosuke',
            'one-punch-man' => 'saitama|genos|tatsumaki|garou|king',
        ),
        'movies' => array(
            'mcu' => 'mcu',
            'dark-knight' => 'dark',
            'bond' => 'bond',
            'spider-man' => 'man',
            'harry-potter' => 'harry',
        ),
        'tv-shows' => array(
            'stranger-things' => 'think',
            'breaking-bad' => 'bad',
            'game-of-thrones' => 'thron|throne',
            'last-of-us' => 'us',
            'wednesday' => 'wed',
        ),
        'k-pop' => array(
            'bts' => 'bts',
            'blackpink' => 'blackpink|blackink',
            'stray-kids' => 'straykid|sraykid',
            'twice' => 'twic|twice',
            'aespa' => 'aespa',
        ),
        'manga' => array(
            'one-piece' => 'pice|luffy|monkey',
            'naruto-manga' => 'naruto|nauto',
            'berserk' => 'bersek|berserk',
            'attack-on-titan' => 'attack',
            'chainsaw-man' => '^[0-9]+man',
        ),
        'comics' => array(
            'amazing-spider-man' => 'spiderman',
            'batman-comics' => 'batman',
            'superman' => 'superman|suprman|images',
            'deadpool' => 'pool',
            'venom' => 'venom|venum',
            'homelander' => 'homelander|homelendar',
            'invincible' => 'markgrayson|markgreyson',
        ),
        'cosplay' => array(
            'naruto' => 'naruto',
            'dragon-ball' => 'dragon|dargon|dragan',
            'one-piece' => 'pice',
            'marvel' => 'ironman',
            'dc' => 'batman',
        ),
        'gaming' => array(
            'witcher' => 'the',
            'god-of-war' => 'war',
            'last-of-us-game' => 'last',
            'street-fighter' => 'street',
            'super-mario' => 'mar',
        ),
    );
}

/* Each category strictly uses its own folder in assets/images/spotlight/<category-slug>. */
function spotlight_media_category($category_slug, $key)
{
    return (string) $category_slug;
}

/* Shared file walk: match the spotlight alias, order naturally by name (or alias priority) and
   return public URLs rooted at site_url(). */
function spotlight_media_files($category_slug, $key, $root, $braces)
{
    $category_slug = (string) $category_slug;
    $key = (string) $key;
    $aliases = spotlight_media_aliases();
    $needle = isset($aliases[$category_slug][$key]) ? $aliases[$category_slug][$key] : preg_quote($key, '/');
    $mediaCategory = spotlight_media_category($category_slug, $key);
    $dir = __DIR__ . '/../' . $root . '/spotlight/' . $mediaCategory;
    $pattern = $dir . '/*' . $braces;
    $files = glob($pattern, GLOB_BRACE);
    if ($files === false) {
        return array();
    }
    $matches = array();
    foreach ($files as $file) {
        $name = pathinfo($file, PATHINFO_FILENAME);
        if (strpos($needle, '^') === 0) {
            if (preg_match('/' . $needle . '/i', $name)) {
                $matches[] = $file;
            }
        } elseif (preg_match('/(?:^|[^a-z])(' . $needle . ')/i', $name)) {
            $matches[] = $file;
        }
    }
    $parts = explode('|', str_replace('^', '', $needle));
    usort($matches, function ($a, $b) use ($parts, $category_slug) {
        $ba = basename($a);
        $bb = basename($b);
        if ($category_slug === 'anime') {
            $pa = 999;
            $pb = 999;
            foreach ($parts as $idx => $p) {
                if ($p !== '' && stripos($ba, $p) !== false && $pa === 999) {
                    $pa = $idx;
                }
                if ($p !== '' && stripos($bb, $p) !== false && $pb === 999) {
                    $pb = $idx;
                }
            }
            if ($pa !== $pb) {
                return $pa - $pb;
            }
        }
        return strnatcasecmp($ba, $bb);
    });
    return array_map(function ($file) use ($mediaCategory, $root) {
        $rel = '/' . $root . '/spotlight/' . $mediaCategory . '/' . rawurlencode(basename($file));
        return function_exists('site_url') ? site_url($rel) : $rel;
    }, $matches);
}

/* Return the supplied pictures for a spotlight. Existing uploads use a numbered
   prefix (for example 1naruto.jpg), so keep the filename convention flexible.
   They act as the video poster and the fallback when no clip is present. */
function spotlight_gallery_images($category_slug, $key)
{
    return spotlight_media_files($category_slug, $key, 'assets/images', '.{jpg,jpeg,png,webp,avif}');
}

/* Return the supplied clips for a spotlight, in the same order as the images,
   so clip <n> sits behind caption <n>. */
function spotlight_gallery_videos($category_slug, $key)
{
    return spotlight_media_files($category_slug, $key, 'assets/videos', '.{mp4,webm,m4v,ogv}');
}

function spotlight_gallery_image($category_slug, $key, $n)
{
    $images = spotlight_gallery_images($category_slug, $key);
    return isset($images[(int) $n - 1]) ? $images[(int) $n - 1] : '';
}

function spotlight_gallery_video($category_slug, $key, $n)
{
    $videos = spotlight_gallery_videos($category_slug, $key);
    return isset($videos[(int) $n - 1]) ? $videos[(int) $n - 1] : '';
}
