<?php
require_once __DIR__ . '/includes/functions.php';

$extra = "\n\n<p>These legends continue to inspire fans worldwide with their unwavering dedication, iconic legacy, and ever‑expanding influence across media and pop culture.</p>";

$chars = db_all("SELECT id, bio FROM character_profiles");
foreach ($chars as $c) {
    $newBio = $c['bio'] . "\n\nThese legends continue to inspire fans worldwide with their unwavering dedication, iconic legacy, and ever‑expanding influence across media and pop culture.";
    $escaped = addslashes($newBio);
    db_query("UPDATE character_profiles SET bio = '$escaped' WHERE id = {$c['id']}");
    echo "Updated ID {$c['id']} with extra paragraph.\n";
}

echo "All done.\n";
?>