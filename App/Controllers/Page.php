<?php
namespace App\Controllers;

use Core\Controller as CoreController;
use Core\View as CoreView;
use \App\Models\Page as PageModel;

class Page extends CoreController{
    private $model;

    public function __construct(){
        parent::__construct();
        $this->model = new PageModel();
    }

    public function index($url){
        $url_params = explode('/', $url);
        $page_url = $url_params[count($url_params) - 1];
        $page = $this->model->getPageInfo($page_url);

        if ($page['error']) {
            throw new \Exception("404 Маршрут не найден", 404);
        }
        else if (!$page['page'] || $page['page']['full_url'] != $url){
            throw new \Exception("404 Маршрут не найден", 404);
        }
        else {
            if ($url == 'poleznaya_informaciya'){
                $page['page']['articles'] = $this->model->getChildrenInfo($page['page']['id'])['pages'];

                CoreView::renderTemplate('Page/info.html', [
                    'settings' => $this->settings,
                    'header_pages' => $this->header_pages,
                    'catalog_menu_pages' => $this->catalog_menu_pages,
                    'page' => $page['page'],
                    'catalog_menu' => $this->catalog_menu,
                    'params' => $this->params,
                    'order_params' => $this->order_params
                ]);
            }
            else {
                CoreView::renderTemplate('Page/page.html', [
                    'settings' => $this->settings,
                    'header_pages' => $this->header_pages,
                    'catalog_menu_pages' => $this->catalog_menu_pages,
                    'page' => $page['page'],
                    'catalog_menu' => $this->catalog_menu,
                    'params' => $this->params,
                    'order_params' => $this->order_params
                ]);
            }
        }
    }
}