<?php
namespace App\Controllers;

use Core\CommonFunctions;
use Core\Controller as CoreController;
use Core\View as CoreView;
use \App\Models\Admin as AdminModel;

class Admin extends CoreController{
    private $menu = array();
    private $model;
    private $login;
    private $seo_admin;

    public function __construct(){
        session_start();
        parent::__construct();

        if (!isset($_GET['admin/login'])) {
            if (!isset($_SESSION['admin']['id'])) {
                header("Location: /admin/login");
                exit();
            }
        }

        $this->model = new AdminModel();

        $this->menu = $this->model->getMenu();

        $this->login = $_SESSION['admin']['login'];

        $this->seo_admin = ($_SESSION['admin']['id'] == 4);
    }

    /**
     * Главная страница панели администратора
     * @return void
     */
    public function main(){
        if (isset($_SESSION['admin']['id'])) {
            if ($this->seo_admin) {
                header("Location: /admin/seo");
                exit();
            }
            else {
                header("Location: /admin/settings");
                exit();
            }
        }
        else {
            header("Location: /admin/login");
            exit();
        }
    }

    /**
     * Страница настроек сайта
     * @return void
     */
    public function settings(){
        if ($this->seo_admin) {
            header("Location: /admin/seo");
            exit();
        }

        $this->menu['0']['active'] = true;
        $title = $this->menu['0']['title'];
        $show_add_btn = false;

        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            $save_result = $this->model->saveSettings($_POST);
            if ($save_result['error']) {
                $_SESSION['error'] = $save_result['error'];
            }
            else {
                $_SESSION['notice'] = $save_result['notice'];
            }

            header("Location: /admin/settings");
            exit();
        }
        else {
            $get_settings = $this->model->getSettings();
            if ($get_settings['error']) {
                $this->error = $get_settings['error'];
            }
            $settings = $get_settings['settings'];
        }

        CoreView::renderTemplate('Admin/settings.html', [
            'menu' => $this->menu,
            'title' => $title,
            'notice' => $this->notice,
            'error' => $this->error,
            'show_add_btn' => $show_add_btn,
            'settings' => $settings,
            'login' => $this->login
        ]);
    }

    /**
     * Страницы сайта
     * @return void
     */
    public function pages(){
        $this->menu['10']['active'] = true;
        $title = $this->menu['10']['title'];
        $show_add_btn = true;
        $act = (isset($_GET['act'])) ? $_GET['act'] : '';

        $get_pages = $this->model->getPages();
        $this->error .= ($get_pages['error']) ? $get_pages['error'] : '';
        $pages = $get_pages['pages'];

        if ($act == 'add') {
            if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                $add_page = $this->model->addPage($_POST);
                if ($add_page['error']) {
                    $this->error .= $add_page['error'];
                    CoreView::renderTemplate('Admin/page.html', [
                        'menu' => $this->menu,
                        'title' => 'Добавление страницы',
                        'notice' => $this->notice,
                        'error' => $this->error,
                        'show_add_btn' => $show_add_btn,
                        'add_btn_link' => "/admin/pages?act=add",
                        'page' => $_POST,
                        'pages' => $pages,
                        'act' => 'add',
                        'login' => $this->login
                    ]);
                }
                else {
                    $_SESSION['notice'] = $add_page['notice'];
                    header("Location: /admin/pages?act=edit&id={$add_page['page']['id']}");
                    exit();
                }
            }
            else {
                CoreView::renderTemplate('Admin/page.html', [
                    'menu' => $this->menu,
                    'title' => 'Добавление страницы',
                    'notice' => $this->notice,
                    'error' => $this->error,
                    'show_add_btn' => $show_add_btn,
                    'add_btn_link' => "/admin/pages?act=add",
                    'page' => '',
                    'pages' => $pages,
                    'act' => 'add',
                    'login' => $this->login
                ]);
            }
        }
        else if ($act == 'edit') {
            $id = (int)$_GET['id'];
            if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                var_dump($_POST);
                die();

                $update_page = $this->model->updatePage($_POST);
                $this->error .= ($update_page['error']) ? $update_page['error'] : '';
                $this->notice .= ($update_page['notice']) ? $update_page['notice'] : '';
                $page = $update_page['page'];
            }
            else {
                $get_page = $this->model->getPage($id);
                $this->error .= ($get_page['error']) ? $get_page['error'] : '';
                $page = $get_page['page'];
            }

            CoreView::renderTemplate('Admin/page.html', [
                'menu' => $this->menu,
                'title' => 'Редактирование страницы',
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/pages?act=add",
                'page' => $page,
                'pages' => $pages,
                'act' => 'edit',
                'login' => $this->login
            ]);
        }
        else if ($act == 'add_to_achive') {
            $id = (int)$_GET['id'];
            $set_res = $this->model->setArchive('pages', $id, 1);
            if ($set_res['error']) {
                $_SESSION['error'] = $set_res['error'];
            }
            if ($set_res['notice']) {
                $_SESSION['notice'] = $set_res['notice'];
            }
            header('Location: /admin/pages');
            exit();
        }
        else if ($act == 'remove_from_achive') {
            $id = (int)$_GET['id'];
            $set_res = $this->model->setArchive('pages', $id, 0);
            if ($set_res['error']) {
                $_SESSION['error'] = $set_res['error'];
            }
            if ($set_res['notice']) {
                $_SESSION['notice'] = $set_res['notice'];
            }
            header('Location: /admin/pages');
            exit();
        }
        else if ($act == 'delete') {
            $id = (int)$_GET['id'];
            $del_res = $this->model->deletePage($id);
            if ($del_res['error']) {
                $_SESSION['error'] = $del_res['error'];
            }
            if ($del_res['notice']) {
                $_SESSION['notice'] = $del_res['notice'];
            }
            header('Location: /admin/pages');
            exit();
        }
        else {
            $title = $title." (".count($pages)." шт.)";
            CoreView::renderTemplate('Admin/pages.html', [
                'menu' => $this->menu,
                'title' => $title,
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/pages?act=add",
                'pages' => $pages,
                'login' => $this->login
            ]);
        }
    }


    /**
     * Как мы работаем
     * @return void
     */
    public function steps(){
        $this->menu['20']['active'] = true;
        $title = $this->menu['20']['title'];
        $show_add_btn = true;
        $act = (isset($_GET['act'])) ? $_GET['act'] : '';

        if ($act == 'add') {
            if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                $add_res = $this->model->addStep($_POST);
                if ($add_res['error']) {
                    $this->error .= $add_res['error'];

                    CoreView::renderTemplate('Admin/step.html', [
                        'menu' => $this->menu,
                        'title' => "Добавление характеристики",
                        'notice' => $this->notice,
                        'error' => $this->error,
                        'show_add_btn' => $show_add_btn,
                        'add_btn_link' => "/admin/steps?act=add",
                        'step' => $_POST,
                        'act' => 'add',
                        'login' => $this->login
                    ]);
                }
                else {
                    $step_id = $add_res['step_id'];
                    header("Location: /admin/steps?act=edit&id=$step_id");
                    exit;
                }
            }
            else {
                CoreView::renderTemplate('Admin/step.html', [
                    'menu' => $this->menu,
                    'title' => "Добавление шага",
                    'notice' => $this->notice,
                    'error' => $this->error,
                    'show_add_btn' => $show_add_btn,
                    'add_btn_link' => "/admin/steps?act=add",
                    'step' => "",
                    'act' => 'add',
                    'login' => $this->login
                ]);
            }
        }
        else if ($act == 'edit') {
            $id = (int)$_GET['id'];
            if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                $step = $this->model->updateStep($_POST);
                if ($step['error']) {
                    $this->error = $step['error'];
                    $step = $_POST;
                }
                else if ($step['notice']){
                    $_SESSION['notice'] = $step['notice'];
                    header("Location: /admin/steps?act=edit&id=$id");
                    exit;
                }
            }
            else {
                $step = $this->model->getStep($id);
                $this->error .= ($step['error']) ? $step['error'] : '';
                $step = $step['step'];
            }

            CoreView::renderTemplate('Admin/step.html', [
                'menu' => $this->menu,
                'title' => 'Редактирование шага',
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/steps?act=add",
                'step' => $step,
                'act' => 'edit',
                'login' => $this->login
            ]);
        }
        else if ($act == 'add_to_achive') {
            $id = (int)$_GET['id'];
            $set = $this->model->setArchive('steps', $id, 1);
            if ($set['error']) $_SESSION['error'] = $set['error'];
            if ($set['notice']) $_SESSION['notice'] = $set['notice'];
            header('Location: /admin/steps');
            exit();
        }
        else if ($act == 'remove_from_achive') {
            $id = (int)$_GET['id'];
            $set = $this->model->setArchive('steps', $id, 0);
            if ($set['error']) $_SESSION['error'] = $set['error'];
            if ($set['notice']) $_SESSION['notice'] = $set['notice'];
            header('Location: /admin/steps');
            exit();
        }
        else if ($act == 'delete') {
            $id = (int)$_GET['id'];
            $delete = $this->model->deleteStep($id);
            if ($delete['error']) $_SESSION['error'] = $delete['error'];
            if ($delete['notice']) $_SESSION['notice'] = $delete['notice'];
            header('Location: /admin/steps');
            exit();
        }
        else {
            $steps = $this->model->getSteps();
            $this->error .= ($steps['error']) ? $steps['error'] : '';
            $steps = $steps['steps'];

            CoreView::renderTemplate('Admin/steps.html', [
                'menu' => $this->menu,
                'title' => $title,
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/steps?act=add",
                'steps' => $steps,
                'login' => $this->login
            ]);
        }
    }

    /**
     * Обращения с сайта
     * @return void
     */
    public function site_messages(){
        if ($this->seo_admin) {
            header("Location: /admin/seo");
            exit();
        }

        $this->menu['50']['active'] = true;
        $title = $this->menu['50']['title'];
        $show_add_btn = false;

        $messages = $this->model->getSiteMessages();
        $this->error .= ($messages['error']) ? $messages['error'] : '';
        $messages = $messages['items'];

        CoreView::renderTemplate('Admin/site_messages.html', [
            'menu' => $this->menu,
            'title' => $title,
            'notice' => $this->notice,
            'error' => $this->error,
            'show_add_btn' => $show_add_btn,
            'add_btn_link' => "",
            'messages' => $messages,
            'login' => $this->login
        ]);
    }

    /**
     * Обработчик асинхронных запросов
     */
    public function ajax(){
        $act = $_POST['act'];
        $error = '';

        if ($act == 'update_rate') {
            $error = '';
            $table = $_POST['table'];
            $array_rate = $_POST['array_rate'];
            if ($array_rate) {
                $update = $this->model->updateRate($table, $array_rate);
                if ($update['error']) {
                    $error = $update['error'];
                }
            }

            $result = array();
            $result['error'] = $error;
            echo json_encode($result);
            exit();
        }

        if ($act == 'edit_order'){
            $error = '';
            $order_id = $_POST['order_id'];
            $field = $_POST['field'];
            $value = $_POST['value'];
            $edit = $this->model->editOrderField($order_id, $field, $value);
            if ($edit['error']) {
                $error = $edit['error'];
            }

            $result = array();
            $result['error'] = $error;
            $result['success'] = 'Сохранено';
            echo json_encode($result);
            exit();
        }

        if ($act == 'send_order_message') {
            $order_id = $_POST['order_id'];
            $text = $_POST['text'];

            $error = '';
            $html = '';

            $add_message = $this->model->addOrderMessage($order_id, $text, 'admin');
            if ($add_message['error']) {
                $error = $add_message['error'];
            }
            else {
                $order_info = $this->model->getOrder($order_id);
                $subject = "Новое сообщение на сайте {$_SERVER['SERVER_NAME']}";
                $message = "Поступило новое сообщение по заказу №$order_id:<br><br>";
                $message .= "$text<br><br>";
                $message .= "Для ответа войдите в <a href='https://{$_SERVER['SERVER_NAME']}'> личный кабинет</a>.<br>";
                $send_mail = $this->model->sendMail($order_info['order']['email'], $subject, $message);
                if (!$send_mail){
                    $error = "Ошибка отправки сообщения";
                }

                $message_id = $add_message['id'];
                $message_data = $this->model->getOrderMessage($message_id);
                $html = CoreView::returnTemplate('/Lk/inc/message.html', [
                    "message" => $message_data['message']
                ]);
            }

            $result = array();
            $result['error'] = $error;
            $result['html'] = $html;
            echo json_encode($result);
            exit();
        }

        if ($act == 'update_temp_statuses') {
            $error = '';
            $statuses = $_POST['statuses'];
            $uid = $_SESSION['admin']['id'];
            $update = $this->model->updateTempStatuses($uid, $statuses);
            if ($update['error']) {
                $error = $update['error'];
            }

            $result = array();
            $result['error'] = $error;
            echo json_encode($result);
            exit();
        }
    }

    /**
     * Вход пользователя
     */
    public function login(){
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $login = $_POST['login'];
            $password = $_POST['password'];

            $check_user = $this->model->checkUser($login, $password);
            if ($check_user['check']) {
                $user = $check_user['user'];
                $_SESSION['admin']['id'] = $user['id'];
                $_SESSION['admin']['login'] = $login;

                $data = array();
                $data['log_code'] = 4;
                $data['user_id'] = $user['id'];
                $data['history'] = "Вход пользователя (ip ".$_SERVER['REMOTE_ADDR'].").";
                $data['mod_id'] = $user['id'];
                $add_log = $this->model->addLog($data);
                if ($add_log['error']) {
                    $error = $add_log['error'];
                }
                else {
                    header('Location: /admin');
                    exit;
                }
            }
            else {
                $error = "Введены неверные данные";
            }
        }
        else {
            $login = '';
            $password = '';
        }

        $params = array();
        $params['login'] = $login;
        $params['password'] = $password;
        $params['error'] = $error;
        CoreView::renderTemplate('Admin/login.html', $params);
    }

    /**
     * Выход пользователя
     */
    public function logout(){
        unset($_SESSION['admin']);
        header('Location: /admin/login');
    }

    /**
     * Пользователи
     */
    public function users(){
        if ($this->seo_admin) {
            header("Location: /admin/seo");
            exit();
        }

        $this->menu['70']['active'] = true;
        $title = $this->menu['70']['title'];
        $show_add_btn = true;
        $act = (isset($_GET['act'])) ? $_GET['act'] : '';

        if ($act == 'add') {
            if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                $add_user = $this->model->addUser($_POST);
                if ($add_user['error']) {
                    $this->error .= $add_user['error'];
                }
                else {
                    $_SESSION['notice'] = $add_user['notice'];
                    header("Location: /admin/users?act=edit&id={$add_user['user']['id']}");
                    exit();
                }
            }
            else {
                CoreView::renderTemplate('Admin/user.html', [
                    'menu' => $this->menu,
                    'title' => 'Редактирование пользователя',
                    'notice' => $this->notice,
                    'error' => $this->error,
                    'show_add_btn' => $show_add_btn,
                    'add_btn_link' => "/admin/users?act=add",
                    'user' => "",
                    'users' => "",
                    'act' => 'add',
                    'login' => $this->login
                ]);
            }
        }
        else if ($act == 'edit') {
            $id = (int)$_GET['id'];
            if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                $update_user = $this->model->updateUser($_POST);
                $this->error .= ($update_user['error']) ? $update_user['error'] : '';
                $this->notice .= ($update_user['notice']) ? $update_user['notice'] : '';
                $user = $update_user['user'];
            }
            else {
                $get_user = $this->model->getUser($id);
                $this->error .= ($get_user['error']) ? $get_user['error'] : '';
                $user = $get_user['user'];
            }

            CoreView::renderTemplate('Admin/user.html', [
                'menu' => $this->menu,
                'title' => 'Редактирование пользователя',
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/users?act=add",
                'user' => $user,
                'users' => "",
                'act' => 'edit',
                'login' => $this->login
            ]);
        }
        else {
            $get_users = $this->model->getUsers();
            $this->error .= ($get_users['error']) ? $get_users['error'] : '';
            $users = $get_users['users'];

            $title = $title." (".count($users)." шт.)";
            CoreView::renderTemplate('Admin/users.html', [
                'menu' => $this->menu,
                'title' => $title,
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/users?act=add",
                'users' => $users,
                'login' => $this->login
            ]);
        }
    }

    /**
     * Заказы
     */
    public function orders(){
        if ($this->seo_admin) {
            header("Location: /admin/seo");
            exit();
        }

        $this->menu['40']['active'] = true;
        $title = $this->menu['40']['title'];
        $show_add_btn = false;
        $act = (isset($_GET['act'])) ? $_GET['act'] : '';

        $get_statuses = $this->model->getRefStatuses();
        $this->error .= ($get_statuses['error']) ? $get_statuses['error'] : '';
        $ref_statuses = $get_statuses['statuses'];

        if ($act == 'show') {
            $id = (int)$_GET['id'];

            $this->model->setOrderMessagesTimeRead($id, 'admin');

            $get_order = $this->model->getOrder($id);
            $this->error .= ($get_order['error']) ? $get_order['error'] : '';
            $order = $get_order['order'];

            $get_messages = $this->model->getOrderMessages($id);
            if ($get_messages['error']) {
                $this->error = $get_messages['error'];
            }
            $order['messages'] = $get_messages['messages'];

            $get_messages = $this->model->getOrderFiles($id);
            if ($get_messages['error']) {
                $this->error = $get_messages['error'];
            }
            $order['files'] = $get_messages['items'];

            $get_services = $this->model->getServices();
            $this->error .= ($get_services['error']) ? $get_services['error'] : '';
            $ref_services = $get_services['services'];

            CoreView::renderTemplate('Admin/order.html', [
                'menu' => $this->menu,
                'title' => "Информация о заказе №$id",
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/orders?act=add",
                'orders' => "",
                'order' => $order,
                'ref_statuses' => $ref_statuses,
                'ref_services' => $ref_services,
                'login' => $this->login,
                'order_params' => $this->order_params
            ]);
        }
        else {
            $statuses = $this->model->getTempStatuses();

            $get_orders = $this->model->getOrders($statuses);
            $this->error .= ($get_orders['error']) ? $get_orders['error'] : '';
            $orders = $get_orders['orders'];

            foreach($orders as &$order){
                $get_messages = $this->model->getOrderMessagesCount($order['id'], 'admin');
                if ($get_messages['error']) {
                    $this->error = $get_messages['error'];
                }
                $order['count_messages'] = $get_messages['count'];
            }

            $statuses = explode(',', $statuses);
            $title = $title." (".count($orders)." шт.)";
            CoreView::renderTemplate('Admin/orders.html', [
                'menu' => $this->menu,
                'title' => $title,
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/orders?act=add",
                'orders' => $orders,
                'ref_statuses' => $ref_statuses,
                'login' => $this->login,
                'statuses' => $statuses,
                'order_params' => $this->order_params
            ]);
        }
    }

    /**
     * Логи
     */
    public function logs(){
        if ($this->seo_admin) {
            header("Location: /admin/seo");
            exit();
        }

        $this->menu['60']['active'] = true;
        $title = $this->menu['60']['title'];
        $show_add_btn = false;

        $get_logs = $this->model->getLogs();
        $this->error .= ($get_logs['error']) ? $get_logs['error'] : '';
        $logs = $get_logs['logs'];

        $title = $title." (".count($logs)." шт.)";
        CoreView::renderTemplate('Admin/logs.html', [
            'menu' => $this->menu,
            'title' => $title,
            'notice' => $this->notice,
            'error' => $this->error,
            'show_add_btn' => $show_add_btn,
            'logs' => $logs,
            'login' => $this->login
        ]);
    }


    public function postacceptor(){
        file_put_contents('test.txt', date('d.m.Y H:i:s')." ".json_encode($_FILES)."\r\n", FILE_APPEND);
        $folder = $_SERVER['DOCUMENT_ROOT']."/images/uploads/";
        reset($_FILES);
        $temp = current($_FILES);
        if (is_uploaded_file($temp['tmp_name'])){
            if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
                header("HTTP/1.1 400 Invalid file name.");
                return;
            }

            $allowed_types = array('image/png', 'image/jpeg');
            if (!in_array($temp['type'], $allowed_types)){
                header("HTTP/1.1 400 Invalid extension.");
                return;
            }

            $file_extention = pathinfo($temp['name'], PATHINFO_EXTENSION);
            $file_name = time()."_".rand(0, 1000).".".$file_extention;
            $filetowrite = $folder.$file_name;

            $move = move_uploaded_file($temp['tmp_name'], $filetowrite);
            if ($move){
                CommonFunctions::resizeImage($filetowrite, $filetowrite, 400, 400);
                echo json_encode(array('location' => "/images/uploads/".$file_name));
            }
            else {
                header("HTTP/1.1 400 Invalid extension.");
                return;
            }
        }
        else {
            header("HTTP/1.1 500 Server Error");
        }
    }

    /**
     * Услуги
     * @return void
     */
    public function services(){
        $this->menu['30']['active'] = true;
        $title = $this->menu['30']['title'];
        $show_add_btn = true;
        $act = (isset($_GET['act'])) ? $_GET['act'] : '';

        $pages = $this->model->getPages();
        $pages = $pages['pages'];

        if ($act == 'add') {
            $service = array();
            if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                $add_res = $this->model->addService($_POST);
                if ($add_res['error']) {
                    $this->error = $add_res['error'];
                    $service = $_POST;
                }
                else {
                    $_SESSION['notice'] = $add_res['notice'];
                    header("Location: /admin/services?act=edit&id={$add_res['id']}");
                    exit();
                }
            }

            CoreView::renderTemplate('Admin/service.html', [
                'menu' => $this->menu,
                'title' => "Добавление услуги",
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/services?act=add",
                'service' => $service,
                'act' => 'add',
                'login' => $this->login,
                'pages' => $pages
            ]);
        }
        else if ($act == 'edit') {
            $id = (int)$_GET['id'];
            if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                $service = $this->model->updateService($_POST);
                if ($service['error']) {
                    $this->error = $service['error'];
                    $service = $_POST;
                }
                else if ($service['notice']){
                    $_SESSION['notice'] = $service['notice'];
                    header("Location: /admin/services?act=edit&id=$id");
                    exit;
                }
            }
            else {
                $service = $this->model->getService($id);
                $this->error .= ($service['error']) ? $service['error'] : '';
                $service = $service['service'];
            }

            CoreView::renderTemplate('Admin/service.html', [
                'menu' => $this->menu,
                'title' => 'Редактирование услуги',
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/services?act=add",
                'service' => $service,
                'act' => 'edit',
                'login' => $this->login,
                'pages' => $pages
            ]);
        }
        else if ($act == 'add_to_achive') {
            $id = (int)$_GET['id'];
            $set_res = $this->model->setArchive('services', $id, 1);
            if ($set_res['error']) {
                $_SESSION['error'] = $set_res['error'];
            }
            if ($set_res['notice']) {
                $_SESSION['notice'] = $set_res['notice'];
            }
            header('Location: /admin/services');
            exit();
        }
        else if ($act == 'remove_from_achive') {
            $id = (int)$_GET['id'];
            $set_res = $this->model->setArchive('services', $id, 0);
            if ($set_res['error']) {
                $_SESSION['error'] = $set_res['error'];
            }
            if ($set_res['notice']) {
                $_SESSION['notice'] = $set_res['notice'];
            }
            header('Location: /admin/services');
            exit();
        }
        else if ($act == 'delete') {
            $id = (int)$_GET['id'];
            $delete = $this->model->deleteService($id);
            if ($delete['error']) $_SESSION['error'] = $delete['error'];
            if ($delete['notice']) $_SESSION['notice'] = $delete['notice'];
            header('Location: /admin/services');
            exit();
        }
        else {
            $services = $this->model->getServices();
            $this->error .= ($services['error']) ? $services['error'] : '';
            $services = $services['services'];

            CoreView::renderTemplate('Admin/services.html', [
                'menu' => $this->menu,
                'title' => $title,
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/services?act=add",
                'services' => $services,
                'login' => $this->login,
                'pages' => $pages
            ]);
        }
    }

    /**
     * Предметы
     * @return void
     */
    public function subjects(){
        if ($this->seo_admin) {
            header("Location: /admin/seo");
            exit();
        }

        $this->menu['35']['active'] = true;
        $title = $this->menu['35']['title'];
        $show_add_btn = false;

        $subjects = $this->model->getSubjects();
        $this->error .= ($subjects['error']) ? $subjects['error'] : '';
        $subjects = $subjects['subjects'];

        CoreView::renderTemplate('Admin/subjects.html', [
            'menu' => $this->menu,
            'title' => $title." (".count($subjects)."шт.)",
            'notice' => $this->notice,
            'error' => $this->error,
            'show_add_btn' => $show_add_btn,
            'add_btn_link' => "",
            'subjects' => $subjects,
            'login' => $this->login
        ]);
    }

    /**
     * Пользователи сайта
     * @return void
     */
    public function site_users(){
        if ($this->seo_admin) {
            header("Location: /admin/seo");
            exit();
        }

        $this->menu['80']['active'] = true;
        $title = $this->menu['80']['title'];
        $show_add_btn = false;

        $site_users = $this->model->getSiteUsers();
        $this->error .= ($site_users['error']) ? $site_users['error'] : '';
        $site_users = $site_users['site_users'];

        CoreView::renderTemplate('Admin/site_users.html', [
            'menu' => $this->menu,
            'title' => $title,
            'notice' => $this->notice,
            'error' => $this->error,
            'show_add_btn' => $show_add_btn,
            'site_users' => $site_users,
            'login' => $this->login
        ]);
    }

    /**
     * Подписчики
     */
    public function subscribes(){
        if ($this->seo_admin) {
            header("Location: /admin/seo");
            exit();
        }

        $this->menu['90']['active'] = true;
        $title = $this->menu['90']['title'];
        $show_add_btn = false;

        $get_users = $this->model->getSubscribes();
        $this->error .= ($get_users['error']) ? $get_users['error'] : '';
        $users = $get_users['users'];

        $title = $title." (".count($users)." шт.)";
        CoreView::renderTemplate('Admin/subscribes.html', [
            'menu' => $this->menu,
            'title' => $title,
            'notice' => $this->notice,
            'error' => $this->error,
            'show_add_btn' => $show_add_btn,
            'users' => $users,
            'login' => $this->login
        ]);
    }

    /**
     * Сео
     * @return void
     */
    public function seo(){
        $this->menu[13]['active'] = true;
        $show_add_btn = true;
        $act = (isset($_GET['act'])) ? $_GET['act'] : '';

        if ($act == 'add') {
            if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                $add_res = $this->model->addSeoItem($_POST);
                if ($add_res['error']) {
                    $this->error .= $add_res['error'];

                    $site_pages = $this->model->getSitePageWithoutSeo();

                    CoreView::renderTemplate('Admin/seo_item.html', [
                        'menu' => $this->menu,
                        'title' => "Добавление",
                        'notice' => $this->notice,
                        'error' => $this->error,
                        'show_add_btn' => $show_add_btn,
                        'add_btn_link' => "/admin/seo?act=add",
                        'site_pages' => $site_pages,
                        'seo_item' => "",
                        'act' => 'add',
                        'login' => $this->login
                    ]);
                }
                else {
                    $id = $add_res['id'];
                    $_SESSION['notice'] = $add_res['notice'];
                    header("Location: /admin/seo?act=edit&id=$id");
                    exit;
                }
            }
            else {
                $site_pages = $this->model->getSitePageWithoutSeo();

                CoreView::renderTemplate('Admin/seo_item.html', [
                    'menu' => $this->menu,
                    'title' => "Добавление",
                    'notice' => $this->notice,
                    'error' => $this->error,
                    'show_add_btn' => $show_add_btn,
                    'add_btn_link' => "/admin/seo?act=add",
                    'site_pages' => $site_pages,
                    'seo_item' => "",
                    'item' => "",
                    'act' => 'add',
                    'login' => $this->login
                ]);
            }
        }
        else if ($act == 'edit') {
            $id = (int)$_GET['id'];
            if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                $seo_item = $this->model->updateSeoItem($_POST);
                if ($seo_item['error']) {
                    $this->error = $seo_item['error'];
                    $seo_item = $_POST;
                }
                else if ($seo_item['notice']){
                    $_SESSION['notice'] = $seo_item['notice'];
                    header("Location: /admin/seo?act=edit&id=$id");
                    exit;
                }
            }
            else {
                $seo_item = $this->model->getSeoItem($id);
                $this->error .= ($seo_item['error']) ? $seo_item['error'] : '';
                $seo_item = $seo_item['item'];
            }

            $site_pages = $this->model->getSitePageWithoutSeo($seo_item['id']);

            CoreView::renderTemplate('Admin/seo_item.html', [
                'menu' => $this->menu,
                'title' => 'Редактирование',
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/seo?act=add",
                'site_pages' => $site_pages,
                'seo_item' => $seo_item,
                'act' => 'edit',
                'login' => $this->login
            ]);
        }
        else if ($act == 'delete') {
            $id = (int)$_GET['id'];
            $delete = $this->model->deleteSeoItem($id);
            if ($delete['error']) $_SESSION['error'] = $delete['error'];
            if ($delete['notice']) $_SESSION['notice'] = $delete['notice'];
            header('Location: /admin/seo');
            exit();
        }
        else {
            $seo_items = $this->model->getSeoItems();
            $this->error .= ($seo_items['error']) ? $seo_items['error'] : '';
            $seo_items = $seo_items['items'];

            CoreView::renderTemplate('Admin/seo_items.html', [
                'menu' => $this->menu,
                'title' => $this->menu[13]['title'],
                'notice' => $this->notice,
                'error' => $this->error,
                'show_add_btn' => $show_add_btn,
                'add_btn_link' => "/admin/seo?act=add",
                'seo_items' => $seo_items,
                'login' => $this->login
            ]);
        }
    }
}