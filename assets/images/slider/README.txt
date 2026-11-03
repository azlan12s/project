Homepage hero slider art goes here.

The homepage slider shows 8 slides. Name the files like this:

    slide-1.jpg
    slide-2.jpg
    slide-3.jpg
    slide-4.jpg
    slide-5.jpg
    slide-6.jpg
    slide-7.jpg
    slide-8.jpg

Rules the site follows:

* Extensions accepted: .jpg, .jpeg, .png, .webp
* Numbers 1 to 8, one per slide, matching the order in
  includes/functions.php -> hero_slides()
* Leave a number out and that slide falls back to its gradient. Nothing
  breaks, nothing 404s, the text still shows.
* Landscape works best. 1600x900 or larger. Anything from 1200px wide up is
  fine. The image is cover-cropped and centred, so portrait shots get their
  sides cut rather than squashed.
* Keep the subject away from the left third if you can. The headline and
  paragraph sit on the left, over a dark scrim.

Slide order right now:

    1  Spider-Man            (Movies)
    2  Anime Worlds          (Anime)
    3  The Speedrun          (Gaming)
    4  Comeback Week         (K-Pop)
    5  Panel Language        (Comics)
    6  Ink and Panel         (Manga)
    7  Series Worth Watching (TV Shows)
    8  Armor Lab             (Cosplay)

To reorder or reword a slide, edit hero_slides() in
includes/functions.php. Nothing else needs to change.
