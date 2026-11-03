<?php
require_once __DIR__ . '/includes/functions.php';

// Insert a new video content: Attack on Titan Final Arc Trailer Yesterday
$title = "Attack on Titan Final Arc Trailer Yesterday";
$thumbnail = "uploads/articles/attack_on_titan_thumb.jpg"; // placeholder, ensure file exists or use existing image
$body = "Official trailer for the final arc of Attack on Titan. Experience the epic conclusion of the series.";
$type = "video";
$genre = "Trailer";
$category_id = 1; // Anime category
$media_url = "https://www.youtube.com/watch?v=example123"; // replace with actual YouTube URL
$status = "published";
$release_year = date('Y');
$views = 0;
$popularity_score = 0;

$escaped_title = addslashes($title);
$escaped_thumbnail = addslashes($thumbnail);
$escaped_body = addslashes($body);
$escaped_genre = addslashes($genre);
$escaped_media_url = addslashes($media_url);

$sql = "INSERT INTO contents (title, thumbnail, body, type, genre, category_id, media_url, status, release_year, views, popularity_score) VALUES ('$escaped_title', '$escaped_thumbnail', '$escaped_body', '$type', '$escaped_genre', $category_id, '$escaped_media_url', '$status', $release_year, $views, $popularity_score)";

db_query($sql);

echo "Video inserted.\n";
?>