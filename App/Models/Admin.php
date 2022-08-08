<?php
namespace App\Models;

use Core\Model as CoreModel;
use PDO;
use Core\Error;
use Core\CommonFunctions;


class Admin extends CoreModel{
    /**
     * Меню админки
     * @return array
     */
    public function getMenu(){
        $menu = array();

        $menu['0']['url'] = "/admin/settings";
        $menu['0']['title'] = "Настройки сайта";
        $menu['0']['active'] = "";

        $menu['10']['url'] = "/admin/pages";
        $menu['10']['title'] = "Разделы";
        $menu['10']['active'] = "";

        $menu['20']['url'] = "/admin/steps";
        $menu['20']['title'] = "Как мы работаем";
        $menu['20']['active'] = "";

        $menu['30']['url'] = "/admin/services";
        $menu['30']['title'] = "Услуги";
        $menu['30']['active'] = "";

        $menu['35']['url'] = "/admin/subjects";
        $menu['35']['title'] = "Предметы";
        $menu['35']['active'] = "";

        $menu['40']['url'] = "/admin/orders";
        $menu['40']['title'] = "Заказы";
        $menu['40']['active'] = "";

        $menu['50']['url'] = "/admin/site_messages";
        $menu['50']['title'] = "Обращения с сайта";
        $menu['50']['active'] = "";

        $menu['80']['url'] = "/admin/site_users";
        $menu['80']['title'] = "Пользователи сайта";
        $menu['80']['active'] = "";

        $menu['70']['url'] = "/admin/users";
        $menu['70']['title'] = "Пользователи";
        $menu['70']['active'] = "";

        $menu['60']['url'] = "/admin/logs";
        $menu['60']['title'] = "Логи";
        $menu['60']['active'] = "";

        $menu['90']['url'] = "/admin/subscribes";
        $menu['90']['title'] = "Подписчики";
        $menu['90']['active'] = "";

        $menu['13']['url'] = "/admin/seo";
        $menu['13']['title'] = "Сео";
        $menu['13']['active'] = "";

        if ($_SESSION['admin']['id'] == 4) {
            $menu = array();
            $menu['13']['url'] = "/admin/seo";
            $menu['13']['title'] = "Сео";
            $menu['13']['active'] = "";

            $menu['10']['url'] = "/admin/pages";
            $menu['10']['title'] = "Разделы";
            $menu['10']['active'] = "";

            $menu['20']['url'] = "/admin/steps";
            $menu['20']['title'] = "Как мы работаем";
            $menu['20']['active'] = "";

            $menu['30']['url'] = "/admin/services";
            $menu['30']['title'] = "Услуги";
            $menu['30']['active'] = "";
        }

        return $menu;
    }

    /**
     * Сохранить настройки сайта
     * @param $settings
     * @return array
     */
    public function saveSettings($settings){
        $result = array();
        try{
            $db = static::getDB();
            $res = $db->prepare("	UPDATE settings 
                                            SET 
                                                site_name = :site_name, 
                                                logo_title = :logo_title,
                                                phone = :phone, 
                                                mail = :mail,
                                                mailsend = :mailsend,
                                                mailorders = :mailorders,
                                                copyright = :copyright,
                                                soc_vk = :soc_vk,
                                                soc_ins = :soc_ins,
                                                banner_title = :banner_title,
                                                banner_text = :banner_text
                                            WHERE id = 1");
            $res->bindParam(':site_name', $settings['site_name'], PDO::PARAM_STR);
            $res->bindParam(':logo_title', $settings['logo_title'], PDO::PARAM_STR);
            $res->bindParam(':phone', $settings['phone'], PDO::PARAM_STR);
            $res->bindParam(':mail', $settings['mail'], PDO::PARAM_STR);
            $res->bindParam(':mailsend', $settings['mailsend'], PDO::PARAM_STR);
            $res->bindParam(':mailorders', $settings['mailorders'], PDO::PARAM_STR);
            $res->bindParam(':copyright', $settings['copyright'], PDO::PARAM_STR);
            $res->bindParam(':soc_vk', $settings['soc_vk'], PDO::PARAM_STR);
            $res->bindParam(':soc_ins', $settings['soc_ins'], PDO::PARAM_STR);
            $res->bindParam(':banner_title', $settings['banner_title'], PDO::PARAM_STR);
            $res->bindParam(':banner_text', $settings['banner_text'], PDO::PARAM_STR);
            $res->execute();

            $result['notice'] = 'Запись успешно отредактирована!';
        }
        catch (\PDOException $e){
            Error::logError($e);
            $result['error'] = 'Ошибка сохранения данных';
        }
        return $result;
    }

    /**
     * Страницы сайта
     * @return bool|mixed
     */
    public function getPages(){
        $pages = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM pages ORDER BY rate DESC");
            $res->execute();
            $pages = array();
            while ($page = $res->fetch(PDO::FETCH_ASSOC)){
                $page_info = $this->getPageLinkInfo($page['id']);
                $page['full_url'] = $page_info['url'];
                $page['full_title'] = $page_info['title'];
                $pages[$page['id']] = $page;
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['pages'] = $pages;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Добавить страницу
     * @return array
     */
    public function addPage($page_info){
        $error = '';
        $notice = '';
        try{
            $show_menu = (isset($page_info['show_menu'])) ? 1 : 0;
            $parent_id = $page_info['parent_id'];
            $title = $page_info['title'];
            $title_menu = $page_info['title_menu'];
            $url = CommonFunctions::translit(mb_strtolower($page_info['title'], 'utf8'));
            $description = $page_info['description'];
            $text = $page_info['text'];
            $rate = $page_info['rate'];

            $check_url = $this->checkUrlExists('pages', $url);
            if ($check_url['error']) {
                $error = $check_url['error'];
            }
            else {
                $db = static::getDB();
                $res = $db->prepare("INSERT INTO pages 
                                          SET
                                            show_menu = :show_menu,
                                            parent_id = :parent_id,
                                            title = :title,
                                            title_menu = :title_menu,
                                            url = :url,
                                            description = :description,
                                            text = :text,
                                            rate = :rate");
                $res->bindValue(':show_menu', $show_menu, PDO::PARAM_INT);
                $res->bindValue(':parent_id', $parent_id, PDO::PARAM_INT);
                $res->bindValue(':title', $title, PDO::PARAM_STR);
                $res->bindValue(':title_menu', $title_menu, PDO::PARAM_STR);
                $res->bindValue(':url', $url, PDO::PARAM_STR);
                $res->bindValue(':description', $description, PDO::PARAM_STR);
                $res->bindValue(':text', $text, PDO::PARAM_STR);
                $res->bindValue(':rate', $rate, PDO::PARAM_INT);
                $res->execute();

                $page_id = $db->lastInsertId();
                $page_info['id'] = $page_id;

                $notice = 'Страница успешно добавлена';
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка сохранения информации о странице';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        $result['page'] = $page_info;
        return $result;
    }

    /**
     * Обновить информацию о странице
     * @return array
     */
    public function updatePage($page_info){
        $error = '';
        $notice = '';
        try{
            $id = $page_info['id'];
            $show_menu = (isset($page_info['show_menu'])) ? 1 : 0;
            $parent_id = $page_info['parent_id'];
            $title = $page_info['title'];
            $title_menu = $page_info['title_menu'];
            $description = $page_info['description'];
            $text = $page_info['text'];
            $url = $page_info['url'] ? $page_info['url'] : CommonFunctions::translit(mb_strtolower($page_info['title'], 'utf8'));
            $rate = $page_info['rate'];

            $check_url = $this->checkUrlExists('pages', $url, $id);
            if ($check_url['error']) {
                $error = $check_url['error'];
            }
            else {
                $db = static::getDB();
                $res = $db->prepare("UPDATE pages 
                                          SET
                                            show_menu = :show_menu,                                         
                                            parent_id = :parent_id,                                         
                                            title = :title,
                                            title_menu = :title_menu,                                            
                                            url = :url,
                                            description = :description,
                                            text = :text,
                                            rate = :rate
                                          WHERE id = :id");
                $res->bindValue(':id', $id, PDO::PARAM_INT);
                $res->bindValue(':show_menu', $show_menu, PDO::PARAM_INT);
                $res->bindValue(':parent_id', $parent_id, PDO::PARAM_INT);
                $res->bindValue(':title', $title, PDO::PARAM_STR);
                $res->bindValue(':title_menu', $title_menu, PDO::PARAM_STR);
                $res->bindValue(':url', $url, PDO::PARAM_STR);
                $res->bindValue(':description', $description, PDO::PARAM_STR);
                $res->bindValue(':text', $text, PDO::PARAM_STR);
                $res->bindValue(':rate', $rate, PDO::PARAM_INT);
                $res->execute();

                $page_info['show_menu'] = $show_menu;
                $page_info['url'] = $url;
                $notice = 'Страница успешно отредактирована';
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка обновления информации о странице';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        $result['page'] = $page_info;
        return $result;
    }

    /**
     * Поместить страницу в архив/убрать страницу из архива
     * @param string $table таблица
     * @param int $id идентификатор таблицы
     * @param bool $value добавить/убрать
     * @return array
     */
    public function setArchive($table, $id, $value){
        $notice = '';
        $error = '';
        try{
            $db = static::getDB();
            if ($table == 'pages') {
                $res = $db->prepare("UPDATE pages SET archived = :value WHERE id = :id");
            }
            else if ($table == 'products') {
                $res = $db->prepare("UPDATE products SET archived = :value WHERE id = :id");
            }
            else {
                $error = 'Ошибка передачи необходимых параметров';
            }

            if (!$error) {
                $res->bindValue(':id', $id, PDO::PARAM_INT);
                $res->bindValue(':value', $value, PDO::PARAM_INT);
                $res->execute();

                $notice = ($value == 1) ? 'Запись успешно добавлена в архив' : 'Запись успешно восстановлена из архива';
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = ($value == 1) ? 'Ошибка добавления записи в архив' : 'Ошибка восстановления записи из архива';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        return $result;
    }

    /**
     * Удалить страницу
     * @param $page_id
     * @return array
     */
    public function deletePage($page_id){
        $notice = '';
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("DELETE FROM pages WHERE id = :page_id");
            $res->bindValue(':page_id', $page_id, PDO::PARAM_INT);
            $res->execute();

            $notice = 'Страница успешно удалена';
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка удаления страницы';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        return $result;
    }

    /**
     * Получить шаг
     * @return bool|mixed     *
     */
    public function getStep($id){
        $error = '';
        $step = array();
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT * FROM steps WHERE id = :id");
            $res->bindValue(':id', $id, PDO::PARAM_INT);
            $res->execute();
            $step = $res->fetch(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['step'] = $step;
        return $result;
    }

    /**
     * Добавить шаг
     * @param $ref_char_info
     * @return array
     */
    public function addStep($ref_char_info){
        $notice = '';
        $error = '';
        try{
            $title = $ref_char_info['title'];
            $rate = $ref_char_info['rate'];

            $db = static::getDB();
            $res = $db->prepare("INSERT INTO steps 
                                          SET
                                            title = :title,
                                            rate = :rate");
            $res->bindValue(':title', $title, PDO::PARAM_STR);
            $res->bindValue(':rate', $rate, PDO::PARAM_INT);
            $res->execute();

            $ref_char_id = $db->lastInsertId();
            $notice = 'Шаг успешно добавлен';

        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка обновления информации о шаге';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        $result['ref_char_info'] = $ref_char_info;
        $result['ref_char_id'] = $ref_char_id;
        return $result;
    }

    /**
     * Обновить шаг
     * @param $info
     * @return array
     */
    public function updateStep($info){
        $notice = '';
        $error = '';
        try{
            $id = $info['id'];
            $title = $info['title'];
            $rate = $info['rate'];

            $db = static::getDB();
            $res = $db->prepare("UPDATE steps 
                                          SET  
                                            title = :title,
                                            rate = :rate
                                          WHERE id = :id");
            $res->bindValue(':id', $id, PDO::PARAM_INT);
            $res->bindValue(':title', $title, PDO::PARAM_STR);
            $res->bindValue(':rate', $rate, PDO::PARAM_INT);
            $res->execute();

            $notice = 'Шаг успешно отредактирован';

        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка обновления информации о шаге';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        return $result;
    }

    /**
     * Удалить шаг
     * @param int $id идентификатор
     * @return array
     */
    public function deleteStep($id){
        $notice = '';
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("DELETE FROM steps WHERE id = :id");
            $res->bindValue(':id', $id, PDO::PARAM_INT);
            $res->execute();

            $notice = 'Шаг успешно удалена';
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка удаления шага';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        return $result;
    }

    /**
     * Проверить логин и пароль
     * @param $login
     * @param $password
     * @return array
     */
    public function checkUser($login, $password){
        $db = static::getDB();
        $check = false;
        $res = $db->prepare("SELECT * FROM users WHERE login = :login");
        $res->bindParam(':login', $login, PDO::PARAM_STR);
        $res->execute();
        $count = $res->rowCount();
        $row = $res->fetch(PDO::FETCH_ASSOC);
        if ($count == 1) {
            $password = md5($password);
            $salt = $row['salt'];
            $password = md5($password.$salt);
            if ($password == $row['password']){
                $check = true;
            }
        }

        $result = array();
        $result['check'] = $check;
        $result['user'] = $row;
        return $result;
    }

    /**
     * Пользователи
     * @return bool|mixed
     */
    public function getUsers(){
        $users = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM users ORDER BY id DESC");
            $res->execute();
            $users = $res->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['users'] = $users;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Пользователь
     * @param $id
     * @return array
     */
    public function getUser($id){
        $user = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $res->bindValue(":id", $id, PDO::PARAM_INT);
            $res->execute();
            $user = $res->fetch(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['user'] = $user;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Обновить информацию о пользователе
     * @param $user_info
     * @return array
     */
    public function updateUser($user_info){
        $error = '';
        $notice = '';
        try{
            $id = $user_info['id'];
            $login = $user_info['login'];
            $name = $user_info['name'];
            $password = $user_info['password'];

            $db = static::getDB();
            $password = md5($password);
            $salt = md5(rand());
            $password = md5($password.$salt);

            $res = $db->prepare("UPDATE users SET login = :login, name = :name, password = :password, salt = :salt WHERE id=:id");
            $res->bindValue(':id', $id, PDO::PARAM_INT);
            $res->bindValue(':login', $login, PDO::PARAM_STR);
            $res->bindValue(':name', $name, PDO::PARAM_STR);
            $res->bindValue(':password', $password, PDO::PARAM_STR);
            $res->bindValue(':salt', $salt, PDO::PARAM_STR);
            $res->execute();

            $notice = (!$error) ? 'Пользователь успешно отредактирован' : '';
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка обновления информации о пользователе';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        $result['user'] = $user_info;
        return $result;
    }

    /**
     * Добавить пользователя
     * @param $user_info
     * @return array
     */
    public function addUser($user_info){
        $error = '';
        $notice = '';
        try{
            $login = $user_info['login'];
            $name = $user_info['name'];
            $password = $user_info['password'];

            $db = static::getDB();
            $password = md5($password);
            $salt = md5(rand());
            $password = md5($password.$salt);

            $res = $db->prepare("INSERT INTO users SET login = :login, name = :name, password = :password, salt = :salt");
            $res->bindValue(':login', $login, PDO::PARAM_STR);
            $res->bindValue(':name', $name, PDO::PARAM_STR);
            $res->bindValue(':password', $password, PDO::PARAM_STR);
            $res->bindValue(':salt', $salt, PDO::PARAM_STR);
            $res->execute();

            $user_info['id'] = $db->lastInsertId();

            $notice = (!$error) ? 'Пользователь успешно добавлен' : '';
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка добавления пользователя';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        $result['user'] = $user_info;
        return $result;
    }

    /**
     * Справочник статусов
     * @return array
     */
    public function getRefStatuses(){
        $statuses = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM ref_statuses ORDER BY id ASC");
            $res->execute();
            while($status = $res->fetch(PDO::FETCH_ASSOC)){
                $statuses[$status['id']] = $status;
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['statuses'] = $statuses;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Заказы
     * @param string $status
     * @return array
     */
    public function getOrders($status = ''){
        $orders = array();
        $error = '';
        try{
            $db = static::getDB();
            $params = array();
            if ($status){
                $status = explode(',', $status);
                $format_params = CommonFunctions::getQuestionMarkPlaceholders($status);
                $where_status = " AND status IN ({$format_params['params']}) ";
                $params = array_merge($params, $status);
            }
            else {
                $where_status = '';
            }

            $res = $db->prepare("SELECT id FROM orders WHERE 1 $where_status ORDER BY id DESC");
            $res->execute($params);
            while($order = $res->fetch(PDO::FETCH_ASSOC)){
                array_push($orders, $this->getOrder($order['id'])['order']);
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['orders'] = $orders;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Получить статусы пользователя
     * @return string
     */
    public function getTempStatuses(){
        $statuses = "";
        try{
            $db = static::getDB();
            $res = $db->query("SELECT status_ids 
                                        FROM temp_user_orders_statuses 
                                        WHERE uid = {$_SESSION['admin']['id']}");
            $res->execute();
            $statuses = $res->fetch(PDO::FETCH_ASSOC);
            $statuses = $statuses['status_ids'];
        }
        catch (\PDOException $e){
            Error::logError($e);
        }
        return $statuses;
    }

    /**
     * Обновить статусы пользователя
     * @param $uid
     * @param $statuses
     * @return mixed
     */
    public function updateTempStatuses($uid, $statuses){
        $error = "";
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT NULL 
                                          FROM temp_user_orders_statuses 
                                          WHERE uid = :uid");
            $res->execute([":uid" => $uid]);
            $check_statuses = $res->fetch(PDO::FETCH_ASSOC);
            if ($check_statuses) {
                $res = $db->prepare("UPDATE temp_user_orders_statuses 
                                              SET status_ids = :status_ids 
                                              WHERE uid = :uid");
            }
            else {
                $res = $db->prepare("INSERT INTO temp_user_orders_statuses 
                                              SET 
                                                status_ids = :status_ids, 
                                                uid = :uid");
            }
            $res->execute([":uid" => $uid, ":status_ids" => $statuses]);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = "Ошибка";
        }

        $result = array();
        $result['error'] = $error;
        return $statuses;
    }

    /**
     * Проверить существование url
     * @param $table
     * @param $url
     * @param $id
     * @return array
     */
    public function checkUrlExists($table, $url, $id = 0){
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT NULL
                                          FROM $table                                       
                                          WHERE 
                                            url = :url 
                                            AND id != :id");
            $res->bindValue(":url", $url, PDO::PARAM_STR);
            $res->bindValue(":id", $id, PDO::PARAM_INT);
            $res->execute();
            $count = $res->rowCount();
            if ($count) {
                $error = "Такой url уже существует в базе";
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка выборки информации';
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Добавить логи
     * @param $data
     * @return array
     */
    public function addLog($data){
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("INSERT INTO log 
                                          SET 
                                            log_code = :log_code,
                                            user_id = :user_id,
                                            mod_id = :mod_id,
                                            time = :time,
                                            history = :history");
            $time = time();
            $res->bindValue(":log_code", $data['log_code'], PDO::PARAM_INT);
            $res->bindValue(":user_id", $data['user_id'], PDO::PARAM_INT);
            $res->bindValue(":mod_id", $data['mod_id'], PDO::PARAM_INT);
            $res->bindValue(":time", $time, PDO::PARAM_INT);
            $res->bindValue(":history", $data['history'], PDO::PARAM_STR);
            $res->execute();
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка обновления информации о движении';
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Логи
     * @return array
     */
    public function getLogs(){
        $logs = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->query("SELECT 
                                          l.*, 
                                          lc.name as log_code_name,
                                          u.login
                                        FROM log l
                                        LEFT JOIN log_codes lc On lc.id = l.log_code
                                        LEFT JOIN users u ON u.id = l.user_id
                                        ORDER BY l.time DESC, l.id DESC");
            $res->execute();
            $logs = $res->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['logs'] = $logs;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Обновить рейтинг
     * @param $table
     * @param $array_rate
     * @return array
     */
    public function updateRate($table, $array_rate){
        $error = '';
        try{
            $db = static::getDB();
            if ($table == 'pages') {
                $res = $db->prepare("UPDATE pages SET rate=:rate WHERE id=:id");
            }
            else if ($table == 'services') {
                $res = $db->prepare("UPDATE services SET rate=:rate WHERE id=:id");
            }
            else if ($table == 'steps') {
                $res = $db->prepare("UPDATE steps SET rate=:rate WHERE id=:id");
            }
            else if ($table == 'subjects') {
                $res = $db->prepare("UPDATE subjects SET rate=:rate WHERE id=:id");
            }
            else {
                $error = 'Ошибка передачи таблицы';
            }

            if (!$error){
                $res->bindParam(":id", $key, PDO::PARAM_INT);
                $res->bindParam(":rate", $value, PDO::PARAM_INT);
                foreach ($array_rate as $key => $value){
                    $res->execute();
                }
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка обновления рейтинга';
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Обновить данные заказа
     * @param $table
     * @param $array_rate
     * @return array
     */
    public function editOrderField($order_id, $field, $value){
        $order_info = $this->getOrder($order_id);
        $order = $order_info['order'];
        $error = '';
        try{
            $db = static::getDB();
            $history = '';
            $field_type = PDO::PARAM_INT;
            if ($field == 'fio') {
                $res = $db->prepare("UPDATE orders SET fio=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактировано имя. Было: \"{$order['fio']}\", стало: \"$value\".";
            }
            else if ($field == 'phone') {
                $res = $db->prepare("UPDATE orders SET phone=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактирован телефон. Был: \"{$order['phone']}\", стал: \"$value\".";
            }
            else if ($field == 'email') {
                $res = $db->prepare("UPDATE orders SET email=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактирована почта. Была: \"{$order['email']}\", стала: \"$value\".";
            }
            else if ($field == 'total_cost') {
                $res = $db->prepare("UPDATE orders SET total_cost=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактирована стоимость. Была: \"{$order['total_cost']}\", стала: \"$value\".";
            }
            else if ($field == 'work_type') {
                $res = $db->prepare("UPDATE orders SET work_type=:field WHERE id=:id");
                $field_type = PDO::PARAM_INT;

                $services = $this->getServices();
                if ($services['error']) {
                    $error = $services['error'];
                }
                else {
                    $services = $services['services'];
                    $history = "Отредактирован тип работы. Был: \"{$services[$order['work_type']]['title']}\", стал: \"{$services[$value]['title']}\".";
                }
            }
            else if ($field == 'work_theme') {
                $res = $db->prepare("UPDATE orders SET work_theme=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактирована тема работы. Была: \"{$order['work_theme']}\", стала: \"$value\".";
            }
            else if ($field == 'work_subject') {
                $res = $db->prepare("UPDATE orders SET work_subject=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактирован предмет. Был: \"{$order['work_subject']}\", стал: \"$value\".";
            }
            else if ($field == 'work_count_page') {
                $res = $db->prepare("UPDATE orders SET work_count_page=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактировано количество страниц. Было: \"{$order['work_count_page']}\", стало: \"$value\".";
            }
            else if ($field == 'work_deadline') {
                $value = strtotime($value);
                $res = $db->prepare("UPDATE orders SET work_deadline=:field WHERE id=:id");
                $history = "Отредактирован срок сдачи. Был: \"".date("d.m.Y", $order['work_deadline'])."\", стал: \"".date("d.m.Y", $value)."\".";
            }
            else if ($field == 'work_count_page') {
                $res = $db->prepare("UPDATE orders SET work_count_page=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактировано количество страниц. Было: \"{$order['work_count_page']}\", стало: \"$value\".";
            }
            else if ($field == 'work_original') {
                $res = $db->prepare("UPDATE orders SET work_original=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;

                $get_original = $this->getOriginal();
                $original = $get_original['items'];
                $history = "Отредактирована оригинальность. Было: \"{$original[$order['work_original']]['name']}\", стало: \"{$original[$value]['name']}\".";
            }
            else if ($field == 'work_vuz') {
                $res = $db->prepare("UPDATE orders SET work_vuz=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактировано учебное заведение. Было: \"{$order['work_vuz']}\", стало: \"$value\".";
            }
            else if ($field == 'work_antiplagiat') {
                $res = $db->prepare("UPDATE orders SET work_plagiat=:field WHERE id=:id");
                $get_antiplagiat = $this->getAntiplagiat();
                $antiplagiat = $get_antiplagiat['items'];
                $history = "Отредактирована проверка на антиплагиат. Была: \"{$antiplagiat[$order['work_plagiat']]['name']}\", стала: \"{$antiplagiat[$value]['name']}\".";
            }
            else if ($field == 'work_url') {
                $res = $db->prepare("UPDATE orders SET work_url=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактирован ресурс СДО. Был: \"{$order['work_url']}\", стал: \"$value\".";
            }
            else if ($field == 'work_login') {
                $res = $db->prepare("UPDATE orders SET work_login=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактирован логин СДО. Был: \"{$order['work_login']}\", стал: \"$value\".";
            }
            else if ($field == 'work_password') {
                $res = $db->prepare("UPDATE orders SET work_password=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактирован пароль СДО. Был: \"{$order['work_password']}\", стал: \"$value\".";
            }
            else if ($field == 'work_dis_type') {
                $res = $db->prepare("UPDATE orders SET work_dis_type=:field WHERE id=:id");
                $field_type = PDO::PARAM_STR;
                $history = "Отредактирован тип диссертации. Был: \"{$order['work_dis_type']}\", стал: \"$value\".";
            }
            else if ($field == 'work_vystuplenie') {
                $res = $db->prepare("UPDATE orders SET work_vystuplenie=:field WHERE id=:id");
                $history = "Отредактирована галочка \"Текст выступления\". Было: ";
                $history .=  ($order['work_vystuplenie']) ? 'да' : 'нет';
                $history .= ", стало: ";
                $history .= ($value) ? 'да' : 'нет';
                $history .= ".";
            }
            else if ($field == 'work_presentation') {
                $res = $db->prepare("UPDATE orders SET work_presentation=:field WHERE id=:id");
                $history = "Отредактирована галочка \"Презентация\". Было: ";
                $history .=  ($order['work_presentation']) ? 'да' : 'нет';
                $history .= ", стало: ";
                $history .= ($value) ? 'да' : 'нет';
                $history .= ".";
            }
            else if ($field == 'work_razdat') {
                $res = $db->prepare("UPDATE orders SET work_razdat=:field WHERE id=:id");
                $history = "Отредактирована галочка \"Раздаточный материал\". Было: ";
                $history .=  ($order['work_razdat']) ? 'да' : 'нет';
                $history .= ", стало: ";
                $history .= ($value) ? 'да' : 'нет';
                $history .= ".";
            }
            else if ($field == 'status') {
                $statuses = $this->getRefStatuses();
                if ($statuses['error']) {
                    $error = $statuses['error'];
                }
                else {
                    $statuses = $statuses['statuses'];
                    $res = $db->prepare("UPDATE orders SET status=:field WHERE id=:id");
                    $field_type = PDO::PARAM_INT;
                    $history = "Изменен статус заказа. Был: \"{$statuses[$order['status']]['name']}\", стал: \"{$statuses[$value]['name']}\".";
                }
            }
            else {
                $error = 'Ошибка передачи поля';
            }

            if (!$error){
                $res->bindParam(":id", $order_id, PDO::PARAM_INT);
                $res->bindParam(":field", $value, $field_type);
                $res->execute();

                $data = array();
                $data['log_code'] = 5;
                $data['user_id'] = $_SESSION['admin']['id'];
                $data['mod_id'] = $order_id;
                $data['history'] = $history;
                $add_log = $this->addLog($data);
                if ($add_log['error']){
                    $error = $add_log['error'];
                }
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка обновления информации о заказе';
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Добавить услугу
     * @param $info
     * @return array
     */
    public function addService($info){
        $notice = '';
        $error = '';
        $id = 0;
        try{
            $title = $info['title'];
            $rate = $info['rate'];
            $time = $info['time'];
            $price = $info['price'];
            $page_id = $info['page_id'];

            $path = '';
            if (isset($_FILES['logo']) && $_FILES['logo']['name']) {
                $path_info = pathinfo($_FILES['logo']['name']);
                $extension = $path_info['extension'];
                $path = time() . "." . $extension;
                $types = array('image/png', 'image/jpeg', 'image/svg+xml');
                if(is_uploaded_file($_FILES['logo']['tmp_name'])){
                    if (in_array($_FILES['logo']['type'], $types)){
                        $logo_path = $_SERVER['DOCUMENT_ROOT'].'/images/services/'.$path;
                        move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path);
                    }
                }
            }

            $db = static::getDB();
            $res = $db->prepare("INSERT INTO services
                                          SET
                                            title = :title,
                                            rate = :rate,                                         
                                            path = :path,
                                            time = :time,
                                            price = :price,
                                            page_id = :page_id
                                            ");
            $res->bindValue(':title', $title, PDO::PARAM_STR);
            $res->bindValue(':rate', $rate, PDO::PARAM_INT);
            $res->bindValue(':path', $path, PDO::PARAM_STR);
            $res->bindValue(':time', $time, PDO::PARAM_STR);
            $res->bindValue(':price', $price, PDO::PARAM_STR);
            $res->bindValue(':page_id', $page_id, PDO::PARAM_INT);
            $res->execute();

            $id = $db->lastInsertId();
            $notice = 'Услуга успешно добавлена';

        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка добавления услуги';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        $result['id'] = $id;
        return $result;
    }

    /**
     * Получить услугу
     * @return bool|mixed
     */
    public function getService($id){
        $error = '';
        $service = array();
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT * FROM services WHERE id = :id");
            $res->bindValue(':id', $id, PDO::PARAM_INT);
            $res->execute();
            $service = $res->fetch(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['service'] = $service;
        return $result;
    }

    /**
     * Обновить услугу
     * @param $info
     * @return array
     */
    public function updateService($info){
        $notice = '';
        $error = '';
        try{
            $id = $info['id'];
            $title = $info['title'];
            $rate = $info['rate'];
            $time = $info['time'];
            $price = $info['price'];
            $page_id = $info['page_id'];

            $path = $info['path'];
            if (isset($_POST['del_file'])) {
                unlink($_SERVER['DOCUMENT_ROOT']."/services/".$path);
                $path = "";
            }
            if (isset($_FILES['logo']) && $_FILES['logo']['name']) {
                if ($path) {
                    unlink($_SERVER['DOCUMENT_ROOT']."/images/services/".$path);
                }

                $path_info = pathinfo($_FILES['logo']['name']);
                $extension = $path_info['extension'];
                $path = time() . "." . $extension;
                $types = array('image/png', 'image/jpeg', 'image/svg+xml');
                if(is_uploaded_file($_FILES['logo']['tmp_name'])){
                    if (in_array($_FILES['logo']['type'], $types)){
                        $logo_path = $_SERVER['DOCUMENT_ROOT'].'/images/services/'.$path;
                        move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path);
                    }
                }
            }

            $db = static::getDB();
            $res = $db->prepare("UPDATE services
                                          SET
                                            title = :title,
                                            rate = :rate,                                         
                                            time = :time,                                         
                                            price = :price,                                         
                                            path = :path,                                  
                                            page_id = :page_id                                     
                                          WHERE id = :id");
            $res->bindValue(':id', $id, PDO::PARAM_INT);
            $res->bindValue(':title', $title, PDO::PARAM_STR);
            $res->bindValue(':time', $time, PDO::PARAM_STR);
            $res->bindValue(':rate', $rate, PDO::PARAM_INT);
            $res->bindValue(':path', $path, PDO::PARAM_STR);
            $res->bindValue(':price', $price, PDO::PARAM_STR);
            $res->bindValue(':page_id', $page_id, PDO::PARAM_INT);
            $res->execute();

            $notice = 'Услуга успешно отредактирована';

        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка обновления информации об услуге';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        return $result;
    }

    /**
     * Удалить услугу
     * @param $id
     * @return array
     */
    public function deleteService($id){
        $notice = '';
        $error = '';
        try{
            $service = $this->getService($id);
            if ($service['brand']['path']) {
                unlink($_SERVER['DOCUMENT_ROOT']."/images/services/".$service['brand']['path']);
            }

            $db = static::getDB();
            $res = $db->prepare("DELETE FROM services WHERE id = :id");
            $res->bindValue(':id', $id, PDO::PARAM_INT);
            $res->execute();

            $notice = 'Услуга успешно удалена';

        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка удаления услуги';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        return $result;
    }

    /**
     * Получить пользователей сайта
     * @return bool|mixed
     */
    public function getSiteUsers(){
        $error = '';
        $site_users = array();
        try{
            $db = static::getDB();
            $res = $db->query("SELECT id, time, fio, login, subscribe, phone FROM site_users ORDER BY id ASC");
            $res->execute();
            $site_users = $res->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['site_users'] = $site_users;
        return $result;
    }

    /**
     * Обращения с сайта
     * @return bool|mixed
     */
    public function getSiteMessages(){
        $messages = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM site_messages ORDER BY time DESC");
            $res->execute();
            $messages = array();
            while ($message = $res->fetch(PDO::FETCH_ASSOC)){
                array_push($messages, $message);
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['items'] = $messages;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Подсписчики
     * @return array
     */
    public function getSubscribes(){
        $users = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM subscribes ORDER BY time");
            $res->execute();
            $users = $res->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['users'] = $users;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Получить все записи блока сео
     * @return bool|mixed     *
     */
    public function getSeoItems(){
        $error = '';
        $items = array();
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM seo ORDER BY id DESC");
            $res->execute();
            while ($item = $res->fetch(PDO::FETCH_ASSOC)){
                if ($item['table_name'] == 'pages') {
                    $pages_info = $this->getPage($item['item_id']);
                    $pages_full_info = $this->getPageInfo($pages_info['page']['url']);

                    $item['page_full_title'] = $pages_full_info['page']['full_title'];
                    $item['page_full_url'] = $pages_full_info['page']['full_url'];
                }
                else if ($item['table_name'] == 'products') {
                    $product_info = $this->getProduct($item['item_id']);
                    $product_info = $this->getProductInfo($product_info['product']['url']);
                    $item['page_full_title'] = $product_info['product']['full_title'];
                    $item['page_full_url'] = $product_info['product']['full_url'];
                }

                array_push($items, $item);
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['items'] = $items;
        return $result;
    }

    /**
     * Добавить сео
     * @param $info
     * @return array
     */
    public function addSeoItem($info){
        $notice = '';
        $error = '';
        try{
            $title = $info['title'];
            $description = $info['description'];
            $keywords = $info['keywords'];

            if ($info['page']) {
                $item_info = explode('_', $info['page']);
                $table_name = $item_info[0];
                $item_id = $item_info[1];
            }
            else {
                $table_name = '';
                $item_id = 0;
            }

            $db = static::getDB();
            $res = $db->prepare("INSERT INTO seo 
                                          SET
                                            item_id = :item_id,
                                            table_name = :table_name,
                                            title = :title,
                                            description = :description,
                                            keywords = :keywords
                                            ");
            $res->bindValue(':item_id', $item_id, PDO::PARAM_INT);
            $res->bindValue(':table_name', $table_name, PDO::PARAM_STR);
            $res->bindValue(':title', $title, PDO::PARAM_STR);
            $res->bindValue(':description', $description, PDO::PARAM_STR);
            $res->bindValue(':keywords', $keywords, PDO::PARAM_STR);
            $res->execute();

            $id = $db->lastInsertId();
            $notice = 'Успешно добавлено';

        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка добавления информации';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        $result['item'] = $info;
        $result['id'] = $id;
        return $result;
    }

    /**
     * Получить сео
     * @return bool|mixed     *
     */
    public function getSeoItem($id){
        $error = '';
        $item = array();
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT * FROM seo WHERE id = :id");
            $res->bindValue(':id', $id, PDO::PARAM_INT);
            $res->execute();
            $item = $res->fetch(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['item'] = $item;
        return $result;
    }

    /**
     * Обновить сео
     * @param $info
     * @return array
     */
    public function updateSeoItem($info){
        $notice = '';
        $error = '';
        try{
            $id = $info['id'];
            $title = $info['title'];
            $description = $info['description'];
            $keywords = $info['keywords'];

            if ($info['page']) {
                $item_info = explode('_', $info['page']);
                $table_name = $item_info[0];
                $item_id = $item_info[1];
            }
            else {
                $table_name = '';
                $item_id = 0;
            }

            $db = static::getDB();
            $res = $db->prepare("UPDATE seo 
                                          SET
                                            item_id = :item_id,
                                            table_name = :table_name,
                                            title = :title,
                                            description = :description,
                                            keywords = :keywords
                                          WHERE id = :id");
            $res->bindValue(':id', $id, PDO::PARAM_INT);
            $res->bindValue(':item_id', $item_id, PDO::PARAM_INT);
            $res->bindValue(':table_name', $table_name, PDO::PARAM_STR);
            $res->bindValue(':title', $title, PDO::PARAM_STR);
            $res->bindValue(':description', $description, PDO::PARAM_STR);
            $res->bindValue(':keywords', $keywords, PDO::PARAM_STR);
            $res->execute();

            $notice = 'Успешно отредактировано';

        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка обновления информации';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        return $result;
    }

    /**
     * Удалить сео
     * @param $id
     * @return array
     */
    public function deleteSeoItem($id){
        $notice = '';
        $error = '';
        try{
            $db = static::getDB();
            $id = (int)$id;
            $res = $db->prepare("DELETE FROM seo WHERE id = :id");
            $res->bindValue(':id', $id, PDO::PARAM_INT);
            $res->execute();

            $notice = 'Успешно удалено';

        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка обновления информации';
        }

        $result = array();
        $result['error'] = $error;
        $result['notice'] = $notice;
        return $result;
    }

    public function getSitePages(){
        $site_pages = array();
        $pages = $this->getPages();
        $pages = $pages['pages'];
        $i = 0;
        foreach($pages as $page) {
            if ($page['archived'] == '1') continue;
            $site_pages[$i]['full_title'] =  $page['full_title'];
			$site_pages[$i]['id'] =  $page['id'];
            $site_pages[$i]['table'] =  'pages';
            $site_pages[$i]['data'] =  "pages_".$page['id'];
            $i++;
        }
        return $site_pages;
    }
	
	public function getSitePageWithoutSeo($seo_id = 0){
        $site_pages = $this->getSitePages();
        foreach($site_pages as $key => $site_page){
            $seo_data = $this->getSeoData($site_page['id'], $site_page['table']);
            if (!$seo_data['error'] && count($seo_data['seo'])) {
                if (!$seo_id || ($seo_id && $seo_id != $seo_data['seo']['id'])) {
                    unset($site_pages[$key]);
                }
            }
        }
        return $site_pages;
    }
}