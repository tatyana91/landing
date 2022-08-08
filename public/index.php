<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

spl_autoload_register(function ($class) {
   $root = dirname(__DIR__);
   $file = $root . '/' . str_replace('\\', '/', $class) . '.php';
   if (is_readable($file)) {
       require_once $root . '/' . str_replace('\\', '/', $class) . '.php';
   }
});

set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');

require_once '../vendor/autoload.php';

$route = new \Core\Router;

$route->add('', ['controller' => 'Index', 'action' => 'main', 'namespace' => '']);
$route->add('index/ajax', ['controller' => 'Index', 'action' => 'ajax', 'namespace' => '']);

$route->add('admin', ['controller' => 'Admin', 'action' => 'main', 'namespace' => '']);
$route->add('admin/login', ['controller' => 'Admin', 'action' => 'login', 'namespace' => '']);
$route->add('admin/logout', ['controller' => 'Admin', 'action' => 'logout', 'namespace' => '']);
$route->add('admin/settings', ['controller' => 'Admin', 'action' => 'settings', 'namespace' => '']);
$route->add('admin/users', ['controller' => 'Admin', 'action' => 'users', 'namespace' => '']);
$route->add('admin/pages', ['controller' => 'Admin', 'action' => 'pages', 'namespace' => '']);
$route->add('admin/services', ['controller' => 'Admin', 'action' => 'services', 'namespace' => '']);
$route->add('admin/steps', ['controller' => 'Admin', 'action' => 'steps', 'namespace' => '']);
$route->add('admin/site_messages', ['controller' => 'Admin', 'action' => 'site_messages', 'namespace' => '']);
$route->add('admin/orders', ['controller' => 'Admin', 'action' => 'orders', 'namespace' => '']);
$route->add('admin/ajax', ['controller' => 'Admin', 'action' => 'ajax', 'namespace' => '']);
$route->add('admin/logs', ['controller' => 'Admin', 'action' => 'logs', 'namespace' => '']);
$route->add('admin/site_users', ['controller' => 'Admin', 'action' => 'site_users', 'namespace' => '']);
$route->add('admin/subjects', ['controller' => 'Admin', 'action' => 'subjects', 'namespace' => '']);
$route->add('admin/subscribes', ['controller' => 'Admin', 'action' => 'subscribes', 'namespace' => '']);
$route->add('admin/seo', ['controller' => 'Admin', 'action' => 'seo', 'namespace' => '']);
$route->add('admin/postacceptor', ['controller' => 'Admin', 'action' => 'postacceptor', 'namespace' => '']);


$route->add('confirm', ['controller' => 'Lk', 'action' => 'confirm', 'namespace' => '']);

$route->add('lk', ['controller' => 'Lk', 'action' => 'index', 'namespace' => '']);
$route->add('lk/logout', ['controller' => 'Lk', 'action' => 'logout', 'namespace' => '']);
$route->add('lk/ajax', ['controller' => 'Lk', 'action' => 'ajax', 'namespace' => '']);
$route->add('lk/orders', ['controller' => 'Lk', 'action' => 'orders', 'namespace' => '']);
$route->add('lk/nastrojki', ['controller' => 'Lk', 'action' => 'settings', 'namespace' => '']);
$route->add('lk/moi_zakazy', ['controller' => 'Lk', 'action' => 'orders', 'namespace' => '']);

$route->add('{url:.*}', ['controller' => 'Page', 'action' => 'index', 'namespace' => '']);

$route->dispatch($_SERVER['REQUEST_URI']);

//парсинг услуг и типов работ
//прекрепление файлов на форме заказа
//добавить к регистрации фио
//прекрепление файлов к сообщению
//вход/регистрация при создании заказа
//first-child
//письмо о восстановлении пароля
//лоадер кнопкам
//поиграть с лого
//подсказки предмета, предмет инпутом
//варианты оригинальности
//при открытиии параметров заказа проверять наличие выбранного типа работы
//верстка лого
//проверить модалки с телефона
//фавикон
//оптимизация и валидация сайта
//мобильное меню
//подгрузка соответсвующих полей при изменении типа работы
//автоматическое сообщение информационное (В нем должна быть информация типа здравствуйте, стоимость вашего, заказа будет рассчитана в ближайшее время, пароль для входа в личный кабинет отправлен на почту)
//текст баннера
//название кнопки
//? статус заказа выполнен хотлесь бы зелененьким
//? Нумерация заказов у клиентов в ЛК мне кажется должна быть не такая как в админке
//форму на баннер
//вывод полной информации о заказе в админке
//оплата заказа
//политика конфиденциальности - согласие + текст
//вывод инфы о оплате в админке
//текст баннера в настройки
//подписаться на рассылку + вывод в админке списка
//отправка клиенту писем о новых сообщениях

//в письме при регистрации надо убрать номер телефона
//Менеджера нужно сделать Екатерину
//свяжитесь с нами тоже телефон убрать надо
//После создания заказа онлайн тесты в описании заказа в личном кабинете написано Логин от СДО а Пароля нет ( в админке есть)
//На тестах должна быть полная оплата
//При оплате, пишет оплата заказа номер 1000, можно оставить только номер (1000) и все, без описания, а то кошелек забанят)
//В этом списке из 10 нужен скролл Как на стуворке И что бы список появившийся не мешал создавать заказ и пропадал

//И Заказаы со статусом закрыт и отменен вынести отдельно. Там кнопка показывать и что б они были отдельно рядом с ней
//TODO Возможно сделать так что бы если в лк клиент пишет сообщение, в админке рядом со статусом заказа появлялся восклицательный знак и пропадал после прочтения сообщения?

//TODO Готовую работу я в чат прикрепляю? можно в отдельное поле?