<?php

header('Content-Type: text/html; charset=utf-8');
include_once (dirname(__DIR__)) . "/holy_habr_api.php";
$post_id=155557;

$item_src = new HolyHabrAPI();
$content = $item_src->get_article($post_id);

print_pr(HolyHabrAPI::prepare_content_for_download($content['content'],"tmp/{$post_id}_"));
?>
