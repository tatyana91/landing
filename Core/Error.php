<?php
namespace Core;
use App\Config;

class Error{
    public static function errorHandler($level, $message, $file, $line){
        $log = dirname(__DIR__)."/logs/errors/".date('d.m.Y').".log";
        ini_set('error_log', $log);
    }

    public static function exceptionHandler($exception){
        $code = $exception->getCode();
        if ($code != 404){
            $code = 500;
        }
        http_response_code($code);

        if (Config::SHOW_ERRORS){
            echo "<h1>Fatal error</h1>";
            echo "<p>Uncaught exception: '".get_class($exception)."'</p>";
            echo "<p>Message: '".$exception->getMessage()."'</p>";
            echo "<p>Stack trace:<pre>".$exception->getTraceAsString()."</pre></p>";
            echo "<p>Throw in '".$exception->getFile()."' on line ".$exception->getLine()."</p>";
        }
        else {
            $log = dirname(__DIR__)."/logs/errors/".date('d.m.Y').".log";
            ini_set('error_log', $log);

            $message = "Uncaught exception: '".get_class($exception)."'";
            $message .= "with message: '".$exception->getMessage()."'";
            $message .= "\nStack trace: '".$exception->getTraceAsString()."'";
            $message .= "\nThrow in: '".$exception->getFile()."' on line ".$exception->getLine();

            if ($code == 404){
                error_log($message);
                $controller = new Controller();
                $controller->getNotFoundPage();
            }
            else {
                error_log($message);
                View::renderTemplate("$code.html");
            }
        }
    }

    public static function logError($exception, $msg = ''){
        $log = dirname(__DIR__)."/logs/errors/".date('d.m.Y').".log";
        ini_set('error_log', $log);

        $message = '';
        $message .= ($msg) ? "$msg. " : '';
        $message .= "Exception: '".get_class($exception)."'";
        $message .= "with message: '".$exception->getMessage()."'";
        $message .= "\nStack trace: '".$exception->getTraceAsString()."'";
        $message .= "\nThrow in: '".$exception->getFile()."' on line ".$exception->getLine();

        error_log($message);
    }
}