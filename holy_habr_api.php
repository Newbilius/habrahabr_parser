<?php

require('phpQuery-onefile.php');

//@todo единообразные кавычки
//@todo обработка ошибок?

/**
 * Отладочный вывод - print_r обернутый в pre.
 */
function print_pr($data) {
    echo "<pre>" . print_r($data, true) . "</pre>";
}

class HolyHabrAPI {

    protected $html;

    public function __construct() {

    }

    /**
     * Получить список комментариев из статьи
     * @param type $id
     * @return array
     */
    public function get_comments($id) {
        return false;
    }

    protected function _get_hubs($element) {
        $hubs_src = pq($element)->find("div.hubs")->find("a.hub");
        foreach ($hubs_src as $_hub) {
            $hubs[] = array(
                "url"  => pq($_hub)->attr("href"),
                "name" => pq($_hub)->text(),
            );
        }
        return $hubs;
    }

    protected function _get_tags($element) {
        $hubs_src = pq($element)->find("div.hubs")->find("a.hub");
        foreach ($hubs_src as $_hub) {
            $hubs[] = array(
                "url"  => pq($_hub)->attr("href"),
                "name" => pq($_hub)->text(),
            );
        }
        return $hubs;
    }

    /**
     * Получить статью
     *
     * @param int $id уникальный идентификатор статьи
     * @param array $params какие параметры получить ('caption', 'hubs', 'tags', 'content')
     * @return array
     */
    public function get_article($id, $params = array('caption', 'hubs', 'tags', 'content')) {
        $this->change_page("http://habrahabr.ru/post/{$id}/");
        $out = array();
        $post = $this->html->find("div.post");
        $out['caption'] = trim(pq($post->find("h1.title"))->text());
        if (in_array("caption", $params)) {
            $out['caption'] = $this->_get_hubs($item);
        }

        if (in_array("hubs", $params)) {
            $out['hubs'] = $this->_get_hubs($item);
        }

        if (in_array("tags", $params)) {
            $out['tags'] = $this->_get_tags($item);
        }
        if (in_array("content", $params)) {
            $out['content'] = pq($post->find("div.content"))->html();
        }
        return $out;
    }

    /**
     * Выбрать страницу для обработки.
     * @param type $url ссылка на страницу
     */
    public function change_page($url) {
        $data = file_get_contents($url);
        $this->html = phpQuery::newDocumentHTML($data, "utf-8");
        $this->html->find("div.buttons")->remove();
    }

    /**
     * Получить список статей со страницы, плюс ссылку на следующую.
     *
     * @param array $params какие параметры получить ("title", "flag", "content", "hubs", "tags", "next_url")
     * @return array
     */
    function get_article_list($params = array("title", "flag", "content", "hubs", "tags", "next_url")) {
        $list = array();
        foreach ($this->html->find("div.post") as $element) {
            if (in_array("title", $params))
                $item['title'] = trim(pq($element)->find("h1.title")->find("a")->text());

            //$item['url'] = trim(pq($element)->find("h1.title")->find("a")->attr("href"));
            $id_src = explode("post_", pq($element)->attr("id"));
            $item['id'] = $id_src[1];

            if (in_array("flag", $params))
                $item['flag'] = trim(pq($element)->find("h1.title")->find("span.flag")->text());

            if (in_array("content", $params))
                $item['content'] = trim(pq($element)->find("div.content")->text());

            if (in_array("hubs", $params)) {
                $item['hubs'] = $this->_get_hubs($element);
            };
            if (in_array("tags", $params)) {
                $item['tags'] = $this->_get_tags($element);
            };
            $list[] = $item;
        }

        $out['items'] = $list;
        if (in_array("next_url", $params)) {
            $next_src = $this->html->find("#next_page");
            $next_url = pq($next_src)->attr("href");
            if ($next_url)
                $out['next_url'] = $next_url;
        };
        return $out;
    }

}

?>