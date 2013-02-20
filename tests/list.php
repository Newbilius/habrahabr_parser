<?php

header('Content-Type: text/html; charset=utf-8');
include_once (dirname(__DIR__)) . "/holy_habr_api.php";

$list_src = new HolyHabrAPI();
$list_src->change_page("http://habrahabr.ru/users/newbilius/favorites/");
$list = $list_src->get_article_list();
print_pr($list);
?>
