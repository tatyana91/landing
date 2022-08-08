<?php
namespace Core;

use Core\Model;

class Controller
{
    protected $notice = '';
    protected $error = '';
    private $core_model;
    protected $catalog_menu = array();
    protected $settings = array();
    protected $header_pages = array();
    protected $catalog_menu_pages = array();
    protected $params = array();
    protected $order_params = array();

    public function __construct(){
        session_start();
        if (isset($_SESSION['notice'])){
            $this->notice = $_SESSION['notice'];
            unset($_SESSION['notice']);
        }

        $this->error = '';
        if ((isset($_SESSION['error']))) {
            $this->error = $_SESSION['error'];
            unset($_SESSION['error']);
        }

        $this->core_model = new Model();

        $get_header_pages = $this->core_model->getHeaderPages();
        if ($get_header_pages['error']) {
            $this->error = $get_header_pages['error'];
        }
        $this->header_pages = $get_header_pages['pages'];

        $get_settings = $this->core_model->getSettings();
        if ($get_settings['error']) {
            $this->error = $get_settings['error'];
        }
        $this->settings = $get_settings['settings'];

        $this->params['auth'] = (bool)(isset($_SESSION['user']));

        $this->params['show_policy'] = isset($_COOKIE['accept_policy']) ? false : true;

        $this->params['server_name'] = "http://".$_SERVER['SERVER_NAME'];

        $this->params['request_url'] = "http://site.ru";
        $this->params['request_url'] .= ($_SERVER['REQUEST_URI'] == "/") ? "" : $_SERVER['REQUEST_URI'];

        $get_services = $this->core_model->getServices();
        if ($get_services['error']) {
            $this->error = $get_services['error'];
        }
        $services = $get_services['services'];

        $get_subjects = $this->core_model->getSubjects();
        if ($get_subjects['error']) {
            $this->error = $get_subjects['error'];
        }
        $subjects = $get_subjects['subjects'];

        $get_original = $this->core_model->getOriginal();
        if ($get_original['error']) {
            $this->error = $get_original['error'];
        }
        $original = $get_original['items'];

        $get_antiplagiat = $this->core_model->getAntiplagiat();
        if ($get_antiplagiat['error']) {
            $this->error = $get_antiplagiat['error'];
        }
        $antiplagiat = $get_antiplagiat['items'];

        $get_statuses = $this->core_model->getStatuses();
        if ($get_statuses['error']) {
            $this->error = $get_statuses['error'];
        }
        $statuses = $get_statuses['items'];

        $this->order_params['services'] = $services;
        $this->order_params['subjects'] = $subjects;
        $this->order_params['original'] = $original;
        $this->order_params['antiplagiat'] = $antiplagiat;
        $this->order_params['statuses'] = $statuses;
    }

    /**
     * Страница 404
     */
    public function getNotFoundPage(){
        View::renderTemplate("404.html", [
            'settings' => $this->settings,
            'header_pages' => $this->header_pages,
            'catalog_menu_pages' => $this->catalog_menu_pages,
            'catalog_menu' => $this->catalog_menu,
            'params' => $this->params
        ]);
    }
}