<?php
namespace App\Models;

use Core\Model as CoreModel;
use PDO;
use Core\Error;
use Core\CommonFunctions;


class Page extends CoreModel{
    /**
     * Получить дочерние страницы
     * @param $id
     * @return array
     */
    public function getChildrenInfo($id){
        $error = '';
        $pages = array();
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT * FROM pages WHERE parent_id = :id");
            $res->bindValue(":id", $id, PDO::PARAM_INT);
            $res->execute();
            while ($page = $res->fetch(PDO::FETCH_ASSOC)) {
                $page['full_url'] = $page['url'];
                if ($page['parent_id']) {
                    $page_link_info = $this->getPageLinkInfo($page['parent_id']);
                    $page['full_url'] = $page_link_info['url']."/".$page['url'];
                }
                array_push($pages, $page);
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['pages'] = $pages;
        return $result;
    }
}