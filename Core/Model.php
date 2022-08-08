<?php
namespace Core;

use App\Config;
use PDO;
use Core\Error;
use Core\View;

class Model
{
    /**
     * Получить PDO для работы с БД
     * @return null|PDO
     */
    public static function getDB(){
        static $db = null;
        if ($db === null){
            try {
                $host = Config::DB_HOST;
                $dbname = Config::DB_NAME;
                $username = Config::DB_USERNAME;
                $passwd = Config::DB_PASSWORD;

                $dns = "mysql:host=$host;dbname=$dbname;charset=utf8";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ];
                $db = new PDO($dns, $username, $passwd, $options);
                return $db;
            }
            catch (\PDOException $e) {
                Error::logError($e);
                View::renderTemplate('connect_db_error.html');
                exit();
            }
        }

        return $db;
    }

    /**
     * Добавить логи
     * @param $message
     */
    public function log($message){
        $file = '/var/www/html/public/logs/models.log';
        $error = date('d.m.Y H:i:s')." $message \r\n";
        file_put_contents($file, $error, FILE_APPEND);
    }

    /**
     * Получить настройки сайта
     * @return array
     */
    public function getSettings(){
        $error = '';
        $settings = array();
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM settings WHERE id = 1");
            $res->execute();
            $settings = $res->fetch(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['settings'] = $settings;
        return $result;
    }

    /**Получить страницы меню для шапки
     * @return array
     */
    public function getHeaderPages(){
        $error = '';
        $pages = array();
        try{
            $db = static::getDB();
            $res = $db->query("SELECT 
                                          url, 
                                          IF(title_menu = '', title, title_menu) as title_menu
                                         FROM pages
                                         WHERE show_menu = '1' AND archived = '0'
                                         ORDER BY rate DESC");
            $res->execute();
            while($page = $res->fetch(PDO::FETCH_ASSOC)){
                $pages[$page['id']] = $page;
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

    /**
     * Получить информацию о странице
     * @param $url
     * @return array
     */
    public function getPageInfo($url){
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("  SELECT *
                                          FROM pages
                                          WHERE 
                                            url = :url
                                            AND archived = 0
                                          LIMIT 1");
            $res->bindValue(":url", $url, PDO::PARAM_STR);
            $res->execute();
            $page = $res->fetch(PDO::FETCH_ASSOC);

            if ($page) {
                $page['full_url'] = $page['url'];
                $page['full_title'] = $page['title'];

                $breadcrumbs = array();
                $breadcrumbs[0]['title'] = "Главная";
                $breadcrumbs[0]['url'] = "/";

                if ($page['parent_id']) {
                    $page_link_info = $this->getPageLinkInfo($page['parent_id']);
                    $page['full_url'] = $page_link_info['url']."/".$page['url'];
                    $page['full_title'] = $page_link_info['title']." -> ".$page['title'];

                    $parent_pages_titles = explode(' -> ', $page_link_info['title']);
                    $parent_pages_urls = explode('/', $page_link_info['url']);
                    $base_link = '';
                    foreach($parent_pages_titles as $key => $parent_pages_title) {
                        $base_link .= "/".$parent_pages_urls[$key];
                        $breadcrumbs[$key+1]['title'] = $parent_pages_title;
                        $breadcrumbs[$key+1]['url'] = $base_link;
                    }
                }
                else {
                    $page['full_url'] = $page['url'];
                }

                $seo_data = $this->getSeoData($page['id'], "pages");
                if ($seo_data['error']) {
                    $error = $seo_data['error'];
                }
                else {
                    if (count($seo_data['seo'])) {
                        $page['seo'] = $seo_data['seo'];
                    }
                    else {
                        $seo_title = $page['title']." - Сервис для студентов";
                        $seo_description = $seo_title;

                        $page['seo']['title'] = $seo_title;
                        $page['seo']['keywords'] = "";
                        $page['seo']['description'] = $seo_description;
                    }
                }

                $page['breadcrumbs'] = $breadcrumbs;
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $page = array();
            $error = 'Ошибка получения информации о странице';
        }

        $result = array();
        $result['error'] = $error;
        $result['page'] = $page;
        return $result;
    }

    /**
     * Получить ссылку на страницу с учетом родителей
     * @param $id
     * @return array
     */
    public function getPageLinkInfo($id){
        $db = static::getDB();
        $res = $db->query("SELECT * FROM pages WHERE id = $id");
        $res->execute();
        $page = $res->fetch();
        $url = $page['url'];
        $title = $page['title'];
        if ($page['parent_id']){
            $page_info = $this->getPageLinkInfo($page['parent_id']);
            $url = $page_info['url']."/".$url;
            $title = $page_info['title']." -> ".$title;
        }

        return array(
            'url' => $url,
            'title' => $title
        );
    }

    /**
     * Заказ
     * @param $id
     * @return array
     */
    public function getOrder($id){
        $order = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("  SELECT 
                                              o.*, 
                                              s.title as work_type_title,
                                              ra.name as work_plagiat_title, 
                                              ro.name as work_original_title, 
                                              rs.name as status_name,
                                              rs.bgcolor as status_bgcolor,
                                              (SELECT IFNULL(SUM(op.amount), 0) FROM order_payments op WHERE op.order_id = o.id) as payment_sum,                                            
                                              (SELECT COUNT(*) FROM order_files of WHERE of.order_id = o.id) as files_count                                            
                                            FROM orders o
                                            LEFT JOIN services s ON s.id = o.work_type
                                            LEFT JOIN ref_statuses rs ON rs.id = o.status
                                            LEFT JOIN ref_antiplagiat ra ON ra.id = o.work_plagiat
                                            LEFT JOIN ref_original ro ON ro.id = o.work_original
                                            WHERE o.id = :id");
            $res->bindValue(":id", $id, PDO::PARAM_INT);
            $res->execute();
            $order = $res->fetch(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['order'] = $order;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Пользователь
     * @param $id
     * @return array
     */
    public function getSiteUser($id){
        $user = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT * FROM site_users WHERE id = :id LIMIT 1");
            $res->bindValue(":id", $id, PDO::PARAM_INT);
            $res->execute();
            $user = $res->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $error = 'Пользователь не найден - 1';
            }
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
     * Пользователь
     * @param $email
     * @return array
     */
    public function getSiteUserByMail($email){
        $user = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT * FROM site_users WHERE email = :email LIMIT 1");
            $res->bindValue(":email", $email, PDO::PARAM_STR);
            $res->execute();
            $user = $res->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $error = 'Пользователь не найден - 2';
            }
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
     * Отправить письмо на почту
     * @param $mail
     * @param $title
     * @param $message
     * @return bool
     */
    public function sendMail($to, $subject, $message = ''){
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        $headers .= "From:  <no-reply@".$_SERVER['SERVER_NAME'].">\r\n";

        $message .= "   
            <div style='padding: 80px 0 40px 0;'>                
                <div>
                    C уважением,<br>
                    Команда 24 диплома<br>
                    г. Санкт-Петербург<br>
                    Email: site@mail.ru<br>
                    Сайт: <a href='https://site.ru'>site.ru</a><br><br>                    
                </div>
            </div>";

        return mail($to, $subject, $message, $headers);
    }

    /**
     * Отправить письмо с информацией о заказе
     * @param $to
     * @param $subject
     * @param $order_id
     * @return array
     */
    public function sendOrderMail($to, $subject, $order_id){
        $error = '';
        $order_info = $this->getOrder($order_id);
        if ($order_info['error']) {
            $error = $order_info['error'];
        }
        else {
            $order = $order_info['order'];
            $deadline = ($order['work_deadline']) ? date('d.m.Y', $order['work_deadline']) : '';
            $order_date = ($order['time']) ? date('d.m.Y', $order['time']) : '';

            $message = "<h2>Информация о заказе №$order_id</h2>";
            $message .= "<div><strong>Дата:</strong> $order_date</div>";
            $message .= "<div><strong>ФИО:</strong> {$order['fio']}</div>";
            $message .= "<div><strong>Почта:</strong> {$order['email']}</div>";

            $message .= "<div><strong>Тип работы:</strong> {$order['work_type_title']}</div>";
            if ($order['work_theme']) $message .= "<div><strong>Тема работы:</strong> {$order['work_theme']}</div>";
            if ($deadline) $message .= "<div><strong>Срок сдачи:</strong> $deadline</div>";
            if ($order['work_subject']) $message .= "<div><strong>Предмет:</strong> {$order['work_subject']}</div>";
            if ($order['work_count_page']) $message .= "<div><strong>Количество страниц:</strong> {$order['work_count_page']}</div>";
            if ($order['work_original']) $message .= "<div><strong>Оригинальность:</strong> {$order['work_original_title']}</div>";
            if ($order['work_vuz']) $message .= "<div><strong>Образовательное учреждение:</strong> {$order['work_vuz']}</div>";
            if ($order['work_plagiat']) $message .= "<div><strong>Проверка на антиплагиат:</strong> {$order['work_plagiat_title']}</div>";
            if ($order['work_url']) $message .= "<div><strong>Ссылка на ресурс СДО:</strong> {$order['work_url']}</div>";
            if ($order['work_login']) $message .= "<div><strong>Логин в СДО:</strong> {$order['work_login']}</div>";
            if ($order['work_password']) $message .= "<div><strong>Пароль в СДО:</strong> {$order['work_password']}</div>";
            if ($order['work_dis_type']) $message .= "<div><strong>Тип диссертации:</strong> {$order['work_dis_type']}</div>";
            if ($order['work_vystuplenie']) $message .= "<div><strong>Текст выступления к защите:</strong> да</div>";
            if ($order['work_presentation']) $message .= "<div><strong>Презентация:</strong> да</div>";
            if ($order['work_razdat']) $message .= "<div><strong>Раздаточный материал:</strong> да</div>";
            if ($order['work_requirements']) $message .= "<div><strong>Требования:</strong> {$order['work_requirements']}</div>";
            if ($order['files_count']) $message .= "<div><strong>Прикрепленные файлы:</strong> {$order['files_count']} шт.</div>";

            $send_mail = $this->sendMail($to, $subject, $message);
            if (!$send_mail) {
                $error = "Ошибка отправки письма на почту";
            }
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Отправить письмо с новым паролем
     * @param $to
     * @param $subject
     * @return array
     */
    public function sendResetMail($to, $subject, $password){
        $error = '';

        $message = "<div>Вы запросили восстановление пароля на нашем сайте.</div>";
        $message .= "<div>Для входа в личный кабинет используйте временный пароль: <strong>$password</strong></div>";
        $message .= "<div>Не забудьте после первого входа обязательно сменить пароль в личном кабинете.</div>";

        $send_mail = $this->sendMail($to, $subject, $message);
        if (!$send_mail) {
            $error = "Ошибка отправки письма на почту";
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Страница сайта
     * @param $id
     * @return array
     */
    public function getPage($id){
        $error = '';
        $page = array();
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT * FROM pages WHERE id = :id");
            $res->bindValue(':id', $id, PDO::PARAM_INT);
            $res->execute();
            $page = $res->fetch(PDO::FETCH_ASSOC);
            $page_info = $this->getPageLinkInfo($page['id']);
            $page['full_url'] = $page_info['url'];
            $page['full_title'] = $page_info['title'];
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['page'] = $page;
        return $result;
    }

    /**
     * Получить услуги
     * @return bool|mixed
     */
    public function getServices(){
        $error = '';
        $services = array();
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM services ORDER BY rate DESC");
            $res->execute();
            while ($service = $res->fetch(PDO::FETCH_ASSOC)){
                $service['page_full_url'] = "";
                if ($service['page_id']){
                    $page = $this->getPage($service['page_id']);
                    $service['page_full_url'] = $page['page']['full_url'];
                }

                $services[$service['id']] = $service;
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['services'] = $services;
        return $result;
    }

    /**
     * Получить все шаги
     * @return bool|mixed
     */
    public function getSteps(){
        $error = '';
        $steps = array();
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM steps ORDER BY rate DESC");
            $res->execute();
            while ($step = $res->fetch(PDO::FETCH_ASSOC)){
                array_push($steps, $step);
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['steps'] = $steps;
        return $result;
    }

    /**
     * Создать заказ
     * @param $data
     * @return array
     */
    public function createOrder($data){
        $error = '';
        try{
            $uid = (isset($_SESSION['user']['id'])) ? $_SESSION['user']['id'] : 0;

            $db = static::getDB();
            $time = time();
            $work_deadline = strtotime($data['work_deadline']);
            $res = $db->prepare("INSERT INTO orders
                                          SET 
                                            status = 1,
                                            uid = :uid,
                                            time = :time,                                            
                                            work_type = :work_type,                                            
                                            work_deadline = :work_deadline,
                                            email = :email,     
                                            fio = :fio,                                                                                  
                                            work_subject = :work_subject");
            $res->bindValue(":uid", $uid, PDO::PARAM_INT);
            $res->bindValue(":time", $time, PDO::PARAM_INT);

            $res->bindValue(":work_type", $data['work_type']);
            $res->bindValue(":work_deadline", $work_deadline);
            $res->bindValue(":email", $data['work_email']);
            $res->bindValue(":fio", $data['work_fio']);

            $res->bindValue(":work_subject", $data['work_subject']);
            $res->execute();

            $order_id = $db->lastInsertId();
            if (!$order_id) {
                $error = "Ошибка добавления заказа";
            }
            else if ($_FILES) {
                $upload = $this->uploadFiles($order_id, 0);
                $error = $upload['error'];
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка создания заказа';
        }

        $result = array();
        $result['error'] = $error;
        $result['order_id'] = $order_id;
        return $result;
    }

    /**
     * Загрузить файлы
     * @param $order_id
     * @param int $message_id
     * @return array
     */
    public function uploadFiles($order_id, $message_id = 0){
        $error = "";
        $uploaddir = $_SERVER['DOCUMENT_ROOT']."/files/orders/$order_id/";
        if (!file_exists($uploaddir)) {
            if (!mkdir($uploaddir, 0777, true)) {
                $error = "Не удалось создать директорию для файлов заказа №$order_id.";
            }
        }

        if (!$error) {
            foreach($_FILES as $file) {
                $original_name = $file['name'];
                $translit_name = time()."_".CommonFunctions::translit($file['name'], true);

                $tmp_name = $file['tmp_name'];
                $file_size = $file['size'];

                if ($file_size <= 30000000 && $file_size != 0) {
                    $uploadfile = $uploaddir.$translit_name;

                    $upload_result = move_uploaded_file($tmp_name, $uploadfile);
                    if (!$upload_result) {
                        $error = 'Ошибка загрузки файлов';
                    }
                    else {
                        $file_info = array();
                        $file_info['order_id'] = $order_id;
                        $file_info['message_id'] = $message_id;
                        $file_info['name'] = $translit_name;
                        $file_info['original_name'] = $original_name;
                        $insert_file = $this->insertFile($file_info);
                        if ($insert_file['error']){
                            $error = $insert_file['error'];
                        }
                    }
                }
                else {
                    if ($file_size > 30000000) $error = 'Размер файла(ов) превышает допустимый 30Мб';
                    if ($file_size == 0) $error = 'Файл пустой';
                }
            }
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Информация как мы работаем
     * @return array
     */
    public function getStepsInfo(){
        $error = '';
        $page = array();
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM pages WHERE id = 3");
            $res->execute();
            $page = $res->fetch(PDO::FETCH_ASSOC);

            $page['full_url'] = $page['url'];
            if ($page['parent_id']) {
                $page_link_info = $this->getPageLinkInfo($page['parent_id']);
                $page['full_url'] = $page_link_info['url']."/".$page['url'];
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['page'] = $page;
        return $result;
    }

    /**
     * Добавить информацию о файле в базе
     * @param array $file_info - информация о файле
     * @return array
     */
    public function insertFile($file_info){
        $error = "";
        try {
            $db = self::getDB();
            $insert_file_query = "INSERT INTO `order_files`
                                  SET										
                                    `order_id` = :order_id,
                                    `message_id` = :message_id,
                                    `name` = :name,
                                    `original_name` = :original_name";
            $stmt = $db->prepare($insert_file_query);
            $stmt->bindValue(':order_id', $file_info['order_id'], PDO::PARAM_INT);
            $stmt->bindValue(':message_id', $file_info['message_id'], PDO::PARAM_INT);
            $stmt->bindValue(':name', $file_info['name'], PDO::PARAM_STR);
            $stmt->bindValue(':original_name', $file_info['original_name'], PDO::PARAM_STR);
            $stmt->execute();
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка добавления файла';
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Сообщения по заказу
     * @param $id
     * @return array
     */
    public function getOrderMessages($id){
        $messages = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("  SELECT *
                                            FROM order_messages
                                            WHERE order_id = :order_id
                                            ORDER BY time ASC");
            $res->bindValue(":order_id", $id, PDO::PARAM_INT);
            $res->execute();
            while ($message = $res->fetch(PDO::FETCH_ASSOC)){
                $messages[$message['id']] = $this->getOrderMessage($message['id'])['message'];
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['messages'] = $messages;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Сообщения по заказу
     * @param $id
     * @param $type
     * @return array
     */
    public function getOrderMessagesCount($id, $type = 0){
        $error = '';
        $count = 0;
        try{
            $where = "";
            if ($type) {
                if ($type == 'user') {
                    $where = " AND user_time_read = 0";
                }
                else {
                    $where = " AND admin_time_read = 0";
                }
            }

            $db = static::getDB();
            $res = $db->prepare("  SELECT COUNT(*) as count
                                            FROM order_messages
                                            WHERE 
                                              order_id = :order_id
                                              $where
                                            ORDER BY time ASC");
            $res->bindValue(":order_id", $id, PDO::PARAM_INT);
            $res->execute();
            $message = $res->fetch(PDO::FETCH_ASSOC);
            $count = $message['count'];
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['count'] = $count;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Прочитать сообщения по заказу
     * @param $id
     * @param $type
     * @return array
     */
    public function setOrderMessagesTimeRead($id, $type = 0){
        $error = '';
        $count = 0;
        try{
            $params = array();
            $params[":order_id"] = $id;

            $set = "";
            $where = "";
            if ($type) {
                if ($type == 'user') {
                    $set = " user_time_read = :time_read";
                    $where = " AND user_time_read = 0";
                }
                else {
                    $set = " admin_time_read = :time_read";
                    $where = " AND admin_time_read = 0";
                }
                $params[':time_read'] = time();
            }

            $db = static::getDB();
            $res = $db->prepare("  UPDATE order_messages
                                            SET $set
                                            WHERE 
                                              order_id = :order_id
                                              $where");
            $res->execute($params);
            $message = $res->fetch(PDO::FETCH_ASSOC);
            $count = $message['count'];
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['count'] = $count;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Файлы заказа
     * @param $id
     * @return array
     */
    public function getOrderFiles($id){
        $items = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT * FROM order_files WHERE order_id = :order_id ORDER BY time ASC");
            $res->bindValue(":order_id", $id, PDO::PARAM_INT);
            $res->execute();
            $items = $res->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['items'] = $items;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Работы заказа
     * @param $id
     * @return array
     */
    public function getOrderWorks($id){
        $items = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT * FROM order_works WHERE order_id = :order_id ORDER BY time ASC");
            $res->bindValue(":order_id", $id, PDO::PARAM_INT);
            $res->execute();
            $items = $res->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['items'] = $items;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Добавить сообщение
     * @param $order_id
     * @param $text
     * @param $user_type
     * @param $save_files
     * @return array
     */
    public function addOrderMessage($order_id, $text, $user_type, $save_files = true){
        $error = '';
        $id = 0;
        try{
            $db = static::getDB();
            $time = time();
            $uid = ($user_type == 'user') ? $_SESSION['user']['id'] : $_SESSION['admin']['id'];

            if ($user_type == 'user') {
                $set = " user_time_read = :time_read,";
            }
            else if ($user_type == 'auto') {
                $user_type = 'admin';
                $uid = 3;
                $set = " admin_time_read = :time_read,";
            }
            else {
                $set = " admin_time_read = :time_read,";
            }

            $res = $db->prepare("INSERT INTO order_messages 
                                          SET 
                                            uid = :uid, 
                                            order_id = :order_id,                                            
                                            time = :time,
                                            text = :text,
                                            $set
                                            user_type = :user_type");
            $res->bindValue(':uid', $uid, PDO::PARAM_INT);
            $res->bindValue(':order_id', $order_id, PDO::PARAM_INT);
            $res->bindValue(':time', $time, PDO::PARAM_INT);
            $res->bindValue(':text', $text, PDO::PARAM_STR);
            $res->bindValue(':user_type', $user_type, PDO::PARAM_STR);
            $res->bindValue(':time_read', $time, PDO::PARAM_INT);
            $res->execute();
            $id = $db->lastInsertId();

            if ($save_files) {
                if ($_FILES) {
                    $upload = $this->uploadFiles($order_id, $id);
                    $error = $upload['error'];
                }
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка добавления сообщения';
        }

        $result = array();
        $result['error'] = $error;
        $result['id'] = $id;
        return $result;
    }

    /**
     * Получить информацию о сообщении
     * @param $id
     * @return array
     */
    public function getOrderMessage($id){
        $error = '';
        $message = array();
        try{
            $db = static::getDB();
            $stmt = $db->prepare("SELECT 
                                            om.*, 
                                            IF (om.user_type = 'user', 
                                                (SELECT fio FROM site_users WHERE id = om.uid), 
                                                (SELECT name FROM users WHERE id = om.uid)
                                            ) as fio
                                          FROM order_messages om
                                          WHERE om.id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $message = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT *
                                          FROM order_files                                         
                                          WHERE message_id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $message['files'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($message['user_type'] == 'user') {
                $message['user_photo'] = "/images/profile.svg";
            }
            else {
                $message['user_photo'] = "/images/manager_image.svg";
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения сообщения';
        }

        $result = array();
        $result['error'] = $error;
        $result['message'] = $message;
        return $result;
    }

    /**
     * Предметы
     * @return bool|mixed
     */
    public function getSubjects(){
        $subjects = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->query("SELECT s.*, CONCAT(ss.name, ' -> ', s.name) as full_name
                                        FROM ref_subjects s
                                        LEFT JOIN ref_subjects_sections ss ON ss.id = s.section 
                                        ORDER BY s.rate DESC");
            $res->execute();
            $subjects = array();
            while ($subject = $res->fetch(PDO::FETCH_ASSOC)){
                array_push($subjects, $subject);
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['subjects'] = $subjects;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Предметы
     * @param $name
     * @return array
     */
    public function findSubjects($name){
        $subjects = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT `name`
                                        FROM ref_subjects
                                        WHERE 
                                          `name` LIKE :name 
                                          OR `name` LIKE :name2");
            $name = "%$name%";
            $name2 = "%".CommonFunctions::correctString($name)."%";
            if ($name2 == $name) $name2 = $name;
            $res->bindValue(':name', $name, PDO::PARAM_STR);
            $res->bindValue(':name2', $name2, PDO::PARAM_STR);
            $res->execute();
            $subjects = $res->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['items'] = $subjects;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Оригинальность
     * @return bool|mixed
     */
    public function getOriginal(){
        $items = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->query("SELECT *
                                        FROM ref_original
                                        ORDER BY rate DESC");
            $res->execute();
            while ($item = $res->fetch(PDO::FETCH_ASSOC)){
                $items[$item['id']] = $item;
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['items'] = $items;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Антиплагиат
     * @return bool|mixed
     */
    public function getAntiplagiat(){
        $items = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->query("SELECT *
                                        FROM ref_antiplagiat
                                        ORDER BY rate DESC");
            $res->execute();
            while ($item = $res->fetch(PDO::FETCH_ASSOC)){
                $items[$item['id']] = $item;
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['items'] = $items;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Статусы
     * @return bool|mixed
     */
    public function getStatuses(){
        $items = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM ref_statuses");
            $res->execute();
            while ($item = $res->fetch(PDO::FETCH_ASSOC)){
                $items[$item['id']] = $item;
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['items'] = $items;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Вход пользователя
     * @param $login
     * @param $password
     * @return array
     */
    public function login($login, $password){
        $error = '';
        $check_user = $this->getSiteUserByMail($login);
        if (!$check_user['user']) {
            $error = 'Пользователь с такой почтой еще не зарегистрирован';
        }
        else {
            $check_user = $this->checkSiteUser($login, $password);
            if ($check_user['check']) {
                $user = $check_user['user'];
                $_SESSION['user']['id'] = $user['id'];
                $_SESSION['user']['login'] = $user['email'];
            }
            else {
                $error = "Указан неверный пароль";
            }
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Проверить логин и пароль
     * @param $login
     * @param $password
     * @return array
     */
    public function checkSiteUser($login, $password){
        $check = false;
        try {
            $db = static::getDB();
            $res = $db->prepare("SELECT * FROM site_users WHERE login = :login");
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
        }
        catch (\PDOException $e){
            Error::logError($e);
            $check = false;
        }

        $result = array();
        $result['check'] = $check;
        $result['user'] = $row;
        return $result;
    }

    /**
     * Добавить пользователя
     * @param $email
     * @param $fio
     * @param $phone
     * @param $password
     * @param $subscribe
     * @return array
     */
    public function addSiteUser($email, $fio, $phone, $password, $subscribe){
        $error = '';
        $confirm_hash = '';
        try{
            $db = static::getDB();

            $check_user = $this->getSiteUserByMail($email);
            if ($check_user['user']) {
                $error = 'Пользователь с такой почтой уже зарегистрирован';
            }
            else {
                $password = md5($password);
                $salt = md5(rand());
                $password = md5($password.$salt);
                $confirm_hash = md5(rand());
                $time = time();

                $res = $db->prepare("INSERT INTO site_users 
                                          SET 
                                            login = :login, 
                                            password = :password,                                            
                                            salt = :salt,
                                            email = :email, 
                                            fio = :fio, 
                                            phone = :phone,
                                            confirm_hash = :confirm_hash,
                                            time = :time,
                                            subscribe = :subscribe");
                $res->bindValue(':login', $email, PDO::PARAM_STR);
                $res->bindValue(':password', $password, PDO::PARAM_STR);
                $res->bindValue(':salt', $salt, PDO::PARAM_STR);
                $res->bindValue(':email', $email, PDO::PARAM_STR);
                $res->bindValue(':fio', $fio, PDO::PARAM_STR);
                $res->bindValue(':phone', $phone, PDO::PARAM_STR);
                $res->bindValue(':confirm_hash', $confirm_hash, PDO::PARAM_STR);
                $res->bindValue(':time', $time, PDO::PARAM_INT);
                $res->bindValue(':subscribe', $subscribe, PDO::PARAM_INT);
                $res->execute();
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка добавления пользователя';
        }

        $result = array();
        $result['error'] = $error;
        $result['confirm_hash'] = $confirm_hash;
        return $result;
    }

    /**
     * Получить сео страницы
     * @param $item_id
     * @param $table_name
     * @return array
     */
    public function getSeoData($item_id, $table_name){
        $error = '';
        $seo_data = array();
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT *
                                          FROM seo
                                          WHERE 
                                            item_id = :item_id
                                            AND table_name = :table_name
                                          LIMIT 1");
            $res->bindValue(":item_id", $item_id, PDO::PARAM_INT);
            $res->bindValue(":table_name", $table_name, PDO::PARAM_STR);
            $res->execute();
            $seo = $res->fetch(PDO::FETCH_ASSOC);

            if ($seo){
				$seo_data['id'] = $seo['id'];
                $seo_data['title'] = $seo['title'];
                $seo_data['keywords'] = $seo['keywords'];
                $seo_data['description'] = $seo['description'];
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения информации';
        }

        $result = array();
        $result['error'] = $error;
        $result['seo'] = $seo_data;
        return $result;
    }
}