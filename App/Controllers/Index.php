<?php
namespace App\Controllers;

use Core\Controller as CoreController;
use Core\View as CoreView;
use App\Models\Index as IndexModel;
use Core\View;

class Index extends CoreController{
    private IndexModel $model;

    public function __construct(){
        parent::__construct();
        $this->model = new IndexModel();
    }

    /**
     * Главная страница
     * @return void
     */
    public function main(){
        $get_promises = $this->model->getPromises();
        if ($get_promises['error']) {
            $this->error = $get_promises['error'];
        }
        $promises = $get_promises['pages'];

        $about_info = $this->model->getAboutInfo();
        if ($about_info['error']) {
            $this->error = $about_info['error'];
        }
        $about_info = $about_info['page'];

        $seo_text = $this->model->getSeoText();
        if ($about_info['error']) {
            $this->error = $seo_text['error'];
        }
        $seo_text = $seo_text['page'];

        $steps_info = $this->model->getStepsInfo();
        if ($steps_info['error']) {
            $this->error = $steps_info['error'];
        }
        $steps_info = $steps_info['page'];

        $get_steps = $this->model->getSteps();
        if ($get_steps['error']) {
            $this->error = $get_steps['error'];
        }
        $steps = $get_steps['steps'];

        $page = [];
        $seo_data = $this->model->getSeoData("13", "pages");
        if ($seo_data['error']) {
            $this->error = $seo_data['error'];
        }
        else {
            if (count($seo_data['seo'])) {
                $page['seo'] = $seo_data['seo'];
            }
            else {
                $page['seo']['title'] = "24 диплома - Сервис для студентов";
                $page['seo']['keywords'] = "";
                $page['seo']['description'] = "24 диплома - Сервис для студентов";
            }
        }

        CoreView::renderTemplate('Index/index.html', [
            'settings' => $this->settings,
            'header_pages' => $this->header_pages,
            'steps' => $steps,
            'about_info' => $about_info,
            'seo_text' => $seo_text,
            'steps_info' => $steps_info,
            'promises' => $promises,
            'params' => $this->params,
            'page' => $page,
            'order_params' => $this->order_params
        ]);
    }


    public function ajax(){
        $act = $_POST['act'];

        if ($act == 'send_message') {
            $result = array();
            $_POST['uid'] = (isset($_SESSION['user'])) ? $_SESSION['user']['id'] : 0;
            $add_message = $this->model->addSiteMessage($_POST);
            if ($add_message['error']) {
                $result['error'] = $add_message['error'];
            }
            else {
                $result['result'] = "Ваше сообщение успешно отправлено. Наши менеджеры свяжутся с вами в ближайшее время";
            }

            echo json_encode($result);
            exit();
        }

        if ($act == 'create_order') {
            $error = '';
            $login_error = '';
            //$email = isset($_POST['login_email']) ? $_POST['login_email'] : $_POST['work_email'];

            /*if (!isset($_SESSION['user']['id'])) {
                //пользователь существует
                $check_user = $this->model->getSiteUserByMail($email);
                if ($check_user['user']) {
                    $login = $_POST['login_email'];
                    $password = $_POST['login_password'];

                    $login_res = $this->model->login($login, $password);
                    if ($login_res['error']) {
                        $login_error = $login_res['error'];
                    }
                }
                else {
                    //создаем пользователя
                    $fio = $_POST['work_fio'];
                    $phone = "";
                    $subscribe = '1';
                    $password = CommonFunctions::genPassword();
                    $add_user = $this->model->addSiteUser($email, $fio, $phone, $password, $subscribe);
                    if ($add_user['error']) {
                        $login_error = $add_user['error'];
                    }
                    else {
                        $subject = "Регистрация на сайте {$_SERVER['SERVER_NAME']}";

                        $message = "Ваш пароль для входа в личный кабинет: <b>$password</b><br>";
                        $message .= "Не забудьте сменить пароль в личном кабинете в разделе \"Настройки\".<br><br>";

                        $send_mail = $this->model->sendMail($email, $subject, $message);
                        if (!$send_mail){
                            $login_error = "Ошибка отправки сообщения";
                        }
                        else {
                            $login_res = $this->model->login($email, $password);
                            if ($login_res['error']) {
                                $login_error = $login_res['error'];
                            }
                        }
                    }
                }
            }*/

            $order_id = 0;
            if (!$login_error){
                $create_order = $this->model->createOrder($_POST);
                if ($create_order['error']) {
                    $error = $create_order['error'];
                }
                else {
                    $order_id = $create_order['order_id'];
                    $get_settings = $this->model->getSettings();
                    if ($get_settings['error']) {
                        $error = $get_settings['error'];
                    }
                    else {
                        $settings = $get_settings['settings'];
                        $to = $settings['mailorders'];
                        $subject = "Поступил заказ №$order_id с сайта {$_SERVER['SERVER_NAME']}";
                        $send_order = $this->model->sendOrderMail($to, $subject, $order_id);
                        if ($send_order['error']) {
                            $error = $send_order['error'];
                        }
                    }

                    if (!$error) {
                        $text = "Здравствуйте! Стоимость вашего заказа будет рассчитана в ближайшее время. ";
                        $text .= "Если у Вас есть дополнительная информация о работе, напишите нам. ";
                        $text .= "Вы так же можете прикрепить к сообщению необходимые дополнительные файлы.";
                        $add_message = $this->model->addOrderMessage($order_id, $text, 'auto', false);
                        if ($add_message['error']) {
                            $error = $add_message['error'];
                        }
                    }
                }
            }

            $result = array();
            $result['error'] = $error;
            $result['login_error'] = $login_error;
            $result['order_id'] = $order_id;
            echo json_encode($result);
            exit();
        }

        if ($act == 'accept_policy'){
            setcookie('accept_policy', true, time()+ 3600*24*30*365, '/');
            $result = array();
            echo json_encode($result);
            exit();
        }

        if ($act == 'check_auth'){
            $email = $_POST['email'];
            if (isset($_SESSION['user']['id'])){
                $answer = "auth";
            }
            else {
                $check_user = $this->model->getSiteUserByMail($email);
                if ($check_user['user']) {
                    $answer = "show_auth";
                }
                else {
                    $answer = "reg";
                }
            }

            $result = array();
            $result['answer'] = $answer;
            echo json_encode($result);
            exit();
        }

        if ($act == 'find_subject'){
            $error = "";
            $html = "";
            $name = $_POST['name'];
            $items = $this->model->findSubjects($name);
            if ($items['error']) {
                $error = $items['error'];
            }
            else {
                $html = View::returnTemplate("/inc/subjects_variants.html", [
                    "subjects" =>  $items['items']
                ]);
            }

            $result = array();
            $result['error'] = $error;
            $result['html'] = $html;
            echo json_encode($result);
            exit();
        }

        if ($act == 'update_order_status'){
            $error = "";
            $order_id = (int)$_POST['order_id'];
            $status = (int)$_POST['status'];

            $order_info = $this->model->getOrder($order_id);
            if ($order_info['error']) {
                $error = $order_info['error'];
            }
            else {
                $order_info = $order_info['order'];
                if ($status != 1 && $status != 7) {
                    $error = "Ошибка получения статуса";
                }
                else if ($_SESSION['user']['id'] != $order_info['uid'] && (!isset($_SESSION['admin']))) {
                    $error = "У вас нет прав для изменения статуса этого заказа";
                }
                else if ($status == 7 && $order_info['status'] != 1) {
                    $error = "Разрешена отмена только новых заказов";
                }
                else if ($status == 1 && $order_info['status'] != 7){
                    $error = "Разрешено восстановление только отмененных заказов";
                }
                else {
                    $update_order_status = $this->model->updateOrderStatus($order_id, $status);
                    if ($update_order_status['error']) {
                        $error = $update_order_status['error'];
                    }
                }
            }

            $result = array();
            $result['error'] = $error;
            echo json_encode($result);
            exit();
        }

        if ($act == 'subscribe'){
            $error = "";
            $email = $_POST['email'];

            $subscribe = $this->model->subscribe($email);
            if ($subscribe['error']) {
                $error = $subscribe['error'];
            }

            $result = array();
            $result['error'] = $error;
            echo json_encode($result);
            exit();
        }

        $result = array();
        $result['error'] = "Ошибка запроса";
        $result['post'] = $_POST;
        echo json_encode($result);
        exit();
    }
}