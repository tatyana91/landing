<?php
namespace App\Controllers;

use Core\CommonFunctions;
use Core\Controller as CoreController;
use Core\View as CoreView;
use \App\Models\Lk as LkModel;
use Twig\Loader\FilesystemLoader;

class Lk extends CoreController{
    private $model;
    private $user;
    public function __construct(){
        parent::__construct();
        $this->model = new LkModel();

        $get_header_pages = $this->model->getHeaderPages();
        if ($get_header_pages['error']) {
            $this->error = $get_header_pages['error'];
        }
        $this->header_pages = $get_header_pages['pages'];

        $this->user = array();
        if (isset($_SESSION['user']['id'])) {
            $get_user = $this->model->getSiteUser($_SESSION['user']['id']);
            if ($get_user['error']) {
                $this->error = $get_user['error'];
            }
            $this->user = $get_user['user'];
        }
    }

    /**
     * Главная страница лк
     * @return void
     */
    public function index(){
        if (!isset($_SESSION['user'])) {
            header("Location: /");
            exit();
        }

        $page = $this->model->getPageInfo('lk');

        CoreView::renderTemplate('Lk/index.html', [
            'settings' => $this->settings,
            'header_pages' => $this->header_pages,
            'page' => $page['page'],
            'user' => $this->user,
            'params' => $this->params
        ]);
    }

    /**
     * Обработчик асинхронных запросов
     */
    public function ajax(){
        $act = $_POST['act'];
        $error = '';

        if ($act == 'req') {
            $error = '';
            $email = $_POST['email'];
            $fio = $_POST['fio'];
            $phone = $_POST['phone'];
            $password = $_POST['password'];
            $subscribe = $_POST['subscribe'];

            $add_user = $this->model->addSiteUser($email, $fio, $phone, $password, $subscribe);
            if ($add_user['error']) {
                $error = $add_user['error'];
            }
            else {
                $subject = "Регистрация сайте {$_SERVER['SERVER_NAME']}.";
                $message = "Здравствуйте! Вы успешно зарегистрировались на нашем сайте {$_SERVER['SERVER_NAME']}.";
                $send_mail = $this->model->sendMail($email, $subject, $message);
                if (!$send_mail){
                    $error = "Ошибка отправки сообщения";
                }
            }

            $result = array();
            $result['error'] = $error;
            $result['result'] = "Регистрация прошла успешно.";
            echo json_encode($result);
            exit();
        }

        if ($act == 'login') {
            $error = '';
            $email = $_POST['email'];
            $password = $_POST['password'];

            $login_res = $this->model->login($email, $password);
            if ($login_res['error']) {
                $error = $login_res['error'];
            }

            $result = array();
            $result['error'] = $error;
            echo json_encode($result);
            exit();
        }

        if ($act == 'change_password') {
            $old_password = $_POST['old_password'];
            $new_password = $_POST['new_password'];

            $change_password = $this->model->changeSiteUserPassword($old_password, $new_password);
            if ($change_password['error']) {
                $error = $change_password['error'];
            }

            $result = array();
            $result['error'] = $error;
            $result['result'] = ($error) ? '' : "Пароль успешно изменен";
            echo json_encode($result);
            exit();
        }

        if ($act == 'save_lk_personal') {
            $result = $this->model->saveLkPersonal($_POST);
            echo json_encode($result);
            exit();
        }

        if ($act == 'save_lk_contacts') {
            $result = $this->model->saveLkContacts($_POST);
            echo json_encode($result);
            exit();
        }

        if ($act == 'get_order_payment') {
            $result = $this->model->getOrderPayment((int)$_POST['order_id']);
            echo json_encode($result);
            exit();
        }

        if ($act == 'reset') {
            $email = $_POST['email'];
            $error = '';
            $success = '';

            $check_user = $this->model->getSiteUserByMail($email);
            if (!$check_user['user']) {
                $error = 'Пользователь с такой почтой еще не зарегистрирован';
            }
            else {
                $user_info = $check_user['user'];
                $password = CommonFunctions::genPassword();

                $set_password = $this->model->setSiteUserPassword($user_info['id'], $password);
                if ($set_password['error']) {
                    $error = $set_password['error'];
                }
                else {
                    $to = $email;
                    $subject = "Восстановление пароля на сайте {$_SERVER['SERVER_NAME']}";
                    $send_mail = $this->model->sendResetMail($to, $subject, $password);
                    if ($send_mail['error']) {
                        $error = $send_mail['error'];
                    }
                    else {
                        $success = "На указанную эл.почту было отправлено письмо для восстановления доступа";
                    }
                }
            }

            $result = array();
            $result['error'] = $error;
            $result['success'] = $success;
            echo json_encode($result);
            exit();
        }

        if ($act == 'send_order_message') {
            $order_id = $_POST['order_id'];
            $text = $_POST['text'];

            $error = '';
            $html = '';

            $add_message = $this->model->addOrderMessage($order_id, $text, 'user');
            if ($add_message['error']) {
                $error = $add_message['error'];
            }
            else {
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
    }

    /**
     * Выход пользователя
     */
    public function logout(){
        unset($_SESSION['user']);
        header('Location: /');
        exit();
    }

    /**
     * Настройки профиля
     */
    public function settings(){
        if (!isset($_SESSION['user'])) {
            header("Location: /");
            exit();
        }

        $page = $this->model->getPageInfo('nastrojki');

        CoreView::renderTemplate('Lk/settings.html', [
            'settings' => $this->settings,
            'header_pages' => $this->header_pages,
            'page' => $page['page'],
            'user' => $this->user,
            'params' => $this->params
        ]);
    }

    /**
     * Заказы
     */
    public function orders(){
        if (!isset($_SESSION['user'])) {
            header("Location: /");
            exit();
        }

        $page = $this->model->getPageInfo('moi_zakazy');

        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];

            $this->model->setOrderMessagesTimeRead($id, 'user');

            $get_order = $this->model->getOrder($id);
            if ($get_order['error']) {
                $this->error = $get_order['error'];
            }
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

            CoreView::renderTemplate('Lk/order.html', [
                'settings' => $this->settings,
                'header_pages' => $this->header_pages,
                'page' => $page['page'],
                'user' => $this->user,
                'params' => $this->params,
                'order' => $order
            ]);
        }
        else {
            $search_status = (isset($_GET['status'])) ? (int)$_GET['status'] : 0;
            $get_orders = $this->model->getSiteUserOrders($search_status);
            if ($get_orders['error']) {
                $this->error = $get_orders['error'];
            }
            $orders = $get_orders['orders'];

            foreach($orders as &$order){
                $get_messages = $this->model->getOrderMessagesCount($order['id'], 'user');
                if ($get_messages['error']) {
                    $this->error = $get_messages['error'];
                }
                $order['count_messages'] = $get_messages['count'];
            }

            CoreView::renderTemplate('Lk/orders.html', [
                'settings' => $this->settings,
                'header_pages' => $this->header_pages,
                'page' => $page['page'],
                'user' => $this->user,
                'params' => $this->params,
                'order_params' => $this->order_params,
                'orders' => $orders,
                'search_status' => $search_status
            ]);
        }
    }

    /**
     * Оплата заказа
     */
    public function confirm(){
        $secret_key = 'kElxQ9JCmlgMYuF18MBC6KoX';
        $sha1 = sha1( $_POST['notification_type'] . '&'. $_POST['operation_id']. '&' . $_POST['amount'] . '&643&' . $_POST['datetime'] . '&'. $_POST['sender'] . '&' . $_POST['codepro'] . '&' . $secret_key. '&' . $_POST['label'] );
        if ($sha1 != $_POST['sha1_hash'] ) {
            $result = "Ошибка верификации";
        }
        else if ($_POST['unaccepted'] == 'true' ||  $_POST['codepro'] == 'true') {
            $result = "Перевод еще не зачислен на счет получателя";
        }
        else {
            $order_id = (int)$_POST['label'];
            $add_order_payment = $this->model->addOrderPayment($_POST);
            if ($add_order_payment['error']) {
                $result = $add_order_payment['error'];
            }
            else {
                $result = "Оплата заказа №$order_id прошла успешно.";
            }
        }

        $page = $this->model->getPageInfo('confirm');

        CoreView::renderTemplate('Lk/confirm.html', [
            'settings' => $this->settings,
            'header_pages' => $this->header_pages,
            'page' => $page['page'],
            'confirm_result' => $result,
            'params' => $this->params
        ]);

        exit();
    }
}