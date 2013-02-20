<?php

header('Content-Type: text/html; charset=utf-8');
include_once (dirname(__DIR__)) . "/holy_habr_api.php";

$item_src = new HolyHabrAPI();
$content = $item_src->get_article(155557);
print_pr($content);
?>
