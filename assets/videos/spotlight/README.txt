Category page spotlight carousels

Every spotlight on the eight fandom pages (anime.php, k-pop.php, ...) has a
four slide carousel. The caption on each slide is the label of the clip that
plays behind it, so files are matched to captions by number, one folder per
category:

  assets/videos/spotlight/<category-slug>/<key>-<n>.mp4

For example, caption 1 of the Dragon Ball spotlight on the anime page
("Kame House training ground") is:

  assets/videos/spotlight/anime/dragon-ball-1.mp4

and caption 2 ("First Super Saiyan transformation") is dragon-ball-2.mp4.
Numbering runs 1..4 in caption order, mp4 or webm accepted.

The keys per category are the ones in includes/spotlight.php:

  anime     dragon-ball, naruto, jujutsu-kaisen, demon-slayer, one-punch-man
  movies    mcu, dark-knight, bond, spider-man, superman, deadpool, venom,
            harry-potter
  tv-shows  stranger-things, breaking-bad, game-of-thrones, last-of-us,
            wednesday
  k-pop     bts, blackpink, stray-kids, twice, aespa
  manga     one-piece, naruto-manga, berserk, attack-on-titan, chainsaw-man
  comics    amazing-spider-man, batman-comics, superman, deadpool, venom
  cosplay   naruto, dragon-ball, one-piece, marvel, dc
  gaming    witcher, god-of-war, last-of-us-game, street-fighter, super-mario

The Movies spotlights for superman, deadpool and venom read from the comics
folder, because that is where their artwork already lives.

Use short, web optimized H.264 clips. The matching picture at
assets/images/spotlight/<category-slug>/<key>-<n>.jpg stays as the poster and
is what a visitor sees when a clip is missing, so nothing ever shows an empty
box. Only the active slide plays; every other clip stays paused, and playback
is skipped entirely when the visitor requests reduced motion.

Homepage Spotlight Fandoms videos

The homepage carousel in the site root uses its own flat naming, one clip per
fandom rather than per caption:

  assets/videos/spotlight/anime.mp4
  assets/videos/spotlight/gaming.mp4
  assets/videos/spotlight/k-pop.mp4
  assets/videos/spotlight/comics.mp4
  assets/videos/spotlight/manga.mp4
  assets/videos/spotlight/tv-shows.mp4
  assets/videos/spotlight/cosplay.mp4

The homepage Movies / Spider-Man slide uses the YouTube link configured in
includes/functions.php. The other slides use the MP4s above, with the existing
slide artwork as poster and fallback.
