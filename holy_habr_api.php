<?php

require('phpQuery-onefile.php');

//@todo единообразные кавычки
//@todo обработка ошибок?
//@todo данные о пользователе
//@todo оптимизировать - убрать дубли запросов.

/**
 * Отладочный вывод - print_r обернутый в pre.
 */
function print_pr($data) {
    echo "<pre>" . print_r($data, true) . "</pre>";
}

class HolyHabrAPI {

    protected $html;
    protected $last_url = "";

    public function __construct() {

    }

    protected function _get_inner_comments($data, $params) {
        $out = array();
        foreach ($data->children("div.comment_item")as $element) {

            if (in_array("html", $params)) {
                $item['html'] = pq($element)->find("div.message:first")->html();
            }
            if (in_array("text", $params)) {
                $item['text'] = pq($element)->find("div.message:first")->text();
            }
            if (in_array("time", $params)) {
                $item['time'] = pq($element)->find("time:first")->text();
            }
            if (in_array("score", $params)) {
                $item['score'] = pq($element)->find("span.score:first")->text();
                $item['score_text'] = pq($element)->find("span.score:first")->attr("title");
            }
            if (in_array("user_info", $params)) {
                $item['user_info']['name'] = pq($element)->find("a.username:first")->text();
                $item['user_info']['avatar'] = pq($element)->find("a.avatar img:first")->attr("src");
            }

            if (pq($element)->find("div.message")->count() > 1) {
                $item['childs'] = $this->_get_inner_comments(pq($element)->find("div.reply_comments:first"), $params);
            };

            $out[] = $item;
        }
        return $out;
    }

    /**
     * Получить список комментариев из статьи. Комментарии следующего уровня лежат в параметре childs.
     *
     * @param type $id
     * @return array
     */
    public function get_comments($id, $params = array("text", "html", "time", "user_info", "score")) {
        $this->change_page("http://habrahabr.ru/post/{$id}/");
        $out = array();

        $items_src = $this->html->find("div.comments_list");

        $out = $this->_get_inner_comments($items_src, $params);

        return $out;
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
    public function get_article($id, $params = array('caption', 'hubs', 'tags', 'content', 'score', "author")) {
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
        if (in_array("score", $params)) {
            $out['score'] = pq($post->find("div.infopanel span.score"))->text();
            $out['score_text'] = pq($post->find("div.infopanel span.score"))->attr("title");
            $out['favs_count'] = pq($post->find("div.infopanel div.favs_count"))->text();
        }
        if (in_array("author", $params)) {
            $out['author'] = pq($post)->find("div.infopanel div.author a:first")->text();
        }
        if (in_array("content", $params)) {
            $out['content'] = pq($post->find("div.content"))->html();
        }
        return $out;
    }

    /**
     * Выбрать страницу для обработки. Если страница уже выбрана та же - повторно загружаться не будет.
     *
     * @param type $url ссылка на страницу
     */
    public function change_page($url) {
        if ($this->last_url != $url) {
            $this->last_url = $url;
            $this->reload();
        }
    }

    /**
     * Перезагружает текущую страницу.
     */
    public function reload() {
        $data = file_get_contents($this->last_url);
        $this->html = phpQuery::newDocumentHTML($data, "utf-8");
        $this->html->find("div.buttons")->remove();
    }

    public function get_user($uid){
        $this->change_page("http://habrahabr.ru/users/{$uid}/");
        $out=Array("uid"=>$uid);

        $out['name']=trim(pq($this->html->find("div.fullname"))->text());
        $out['karma']=trim(pq($this->html->find("div.karma div.score div.num"))->text());
        $out['karma_text']=trim(pq($this->html->find("div.karma div.votes"))->text());
        $out['rating']=trim(pq($this->html->find("div.rating div.num"))->text());
        $out['birthday']=trim(pq($this->html->find("dd.bday"))->text());
        $out['summary']=trim(pq($this->html->find("dd.summary"))->html());
        $out['reg_date']=trim(pq($this->html->find("dd.grey"))->html());

        return $out;
    }

    /**
     * Получить список статей со страницы, плюс ссылку на следующую.
     *
     * @param array $params какие параметры получить ("title", "flag", "content", "hubs", "tags", "next_url")
     * @return array
     */
    function get_article_list($params = array("title", "flag", "content", "hubs", "tags", "next_url", "author", "score")) {
        $list = array();
        foreach ($this->html->find("div.post") as $element) {
            if (in_array("title", $params))
                $item['title'] = trim(pq($element)->find("h1.title")->find("a")->text());

            $id_src = explode("post_", pq($element)->attr("id"));
            $item['id'] = $id_src[1];
            if (in_array("flag", $params))
                $item['flag'] = trim(pq($element)->find("h1.title")->find("span.flag")->text());

            if (in_array("score", $params)) {
                $item['score'] = pq($element)->find("div.infopanel span.score")->text();
                $item['score_text'] = pq($element)->find("div.infopanel span.score")->attr("title");
                $item['favs_count'] = pq($element)->find("div.infopanel div.favs_count")->text();
            }
            if (in_array("author", $params)) {
                $item['author'] = pq($element)->find("div.infopanel div.author a:first")->text();
            }
            if (in_array("hubs", $params)) {
                $item['hubs'] = $this->_get_hubs($element);
            };
            if (in_array("tags", $params)) {
                $item['tags'] = $this->_get_tags($element);
            };
            if (in_array("content", $params))
                $item['content'] = trim(pq($element)->find("div.content")->text());
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