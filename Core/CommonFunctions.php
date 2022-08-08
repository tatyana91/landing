<?php
namespace Core;

class CommonFunctions {
    /**
     * Транслитерировать строку
     * @param string
     * @return string
     */
    public static function translit($input, $need_dot = false){
        $assoc = array(
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g',
            'д'=>'d','е'=>'e','ё'=>'e','ж'=>'j',
            'з'=>'z','и'=>'i','й'=>'j','к'=>'k',
            'л'=>'l','м'=>'m','н'=>'n','о'=>'o',
            'п'=>'p','р'=>'r','с'=>'s','т'=>'t',
            'у'=>'y','ф'=>'f','х'=>'h','ц'=>'c',
            'ч'=>'ch','ш'=>'sh','щ'=>'sh','ы'=>'y',
            'э'=>'e','ю'=>'u','я'=>'ya',
            'А'=>'A','Б'=>'B','В'=>'V','Г'=>'G',
            'Д'=>'D','Е'=>'E','Ё'=>'E','Ж'=>'J',
            'З'=>'Z','И'=>'I','Й'=>'J','К'=>'K',
            'Л'=>'L','М'=>'M','Н'=>'N','О'=>'O',
            'П'=>'P','Р'=>'R','С'=>'S','Т'=>'T',
            'У'=>'Y','Ф'=>'F','Х'=>'H','Ц'=>'C',
            'Ч'=>'Ch','Ш'=>'Sh','Щ'=>'Sh','Ы'=>'Y',
            'Э'=>'E','Ю'=>'U','Я'=>'Ya',
            'ь'=>'','Ь'=>'','ъ'=>'','Ъ'=>'',' '=>'_',
            '.'=>'', ','=>'_', '('=>'', ')'=>'',
            '\''=>'', '"'=>''
        );

        if ($need_dot) unset($assoc['.']);
        return strtr($input, $assoc);
    }

    /**
     * Сгенерировать пароль
     * @param int $length
     * @return string
     */
    public static function genPassword($length = 10) {
        $chars="qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
        $length = intval($length);
        $size=strlen($chars)-1;
        $password = "";
        while($length--) $password.=$chars[rand(0,$size)];
        return $password;
    }

    /**
     * Получить строку
     * @param $string
     * @return null|string|string[]
     */
    public static function correctString($string){
        $assoc = array(
            "q"=>"й", "w"=>"ц", "e"=>"у", "r"=>"к",
            "t"=>"е", "y"=>"н", "u"=>"г", "i"=>"ш",
            "o"=>"щ", "p"=>"з", "["=>"х", "]"=>"ъ",
            "a"=>"ф", "s"=>"ы", "d"=>"в", "f"=>"а",
            "g"=>"п", "h"=>"р", "j"=>"о", "k"=>"л",
            "l"=>"д", ";"=>"ж", "'"=>"э", "z"=>"я",
            "x"=>"ч", "c"=>"с", "v"=>"м", "b"=>"и",
            "n"=>"т", "m"=>"ь", ","=>"б", "."=>"ю"
        );

        return strtr($string, $assoc);
    }

    /**
     * Получить параметры и значения для запроса с именованными параметрами
     * @param $params
     * @return array
     */
    public static function getNamedPlaceholders($params){
        $place_holders = array();
        $place_holders_values = array();
        foreach($params as $key => $value) {
            array_push($place_holders, ":param".$key);
            array_push($place_holders_values, $value);
        }
        $place_holders_str = implode(",", $place_holders);

        $result = array();
        $result['params'] = $place_holders_str;
        $result['values'] = $place_holders_values;
        return $result;
    }

    /**
     * Получить параметры и значения для запроса с неименованными параметрами
     * @param $params
     * @return array
     */
    public static function getQuestionMarkPlaceholders($params){
        $place_holders = implode(',', array_fill(0, count($params), '?'));
        $result = array();
        $result['params'] = $place_holders;
        $result['values'] = $params;
        return $result;
    }

    /**
     * @param $src
     * @param $dest
     * @param $width
     * @param $height
     * @param int $rgb
     * @param int $quality
     * @return bool
     */
    public static function resizeImage($src, $dest, $width, $height, $rgb = 0xFFFFFF, $quality = 100) {
        if (!file_exists($src)) {return false;}
        $size = getimagesize($src);
        if ($size === false) {return false;}
        $format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
        $icfunc = 'imagecreatefrom'.$format;
        if (!function_exists($icfunc)) {return false;}
        $x_ratio = $width  / $size[0];
        $y_ratio = $height / $size[1];
        if ($height == 0) {
            $y_ratio = $x_ratio;
            $height  = $y_ratio * $size[1];
        } elseif ($width == 0) {
            $x_ratio = $y_ratio;
            $width   = $x_ratio * $size[0];
        }
        $ratio       = min($x_ratio, $y_ratio);
        $use_x_ratio = ($x_ratio == $ratio);
        $new_width   = $use_x_ratio  ? $width  : floor($size[0] * $ratio);
        $new_height  = !$use_x_ratio ? $height : floor($size[1] * $ratio);
        $new_left    = $use_x_ratio  ? 0 : floor(($width - $new_width)   / 2);
        $new_top     = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2);
        $isrc  = $icfunc($src);
        $idest = imagecreatetruecolor($width, $height);
        imagefill($idest, 0, 0, $rgb);
        imagecopyresampled($idest, $isrc, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]);

        imagejpeg($idest, $dest, $quality);

        imagedestroy($isrc);
        imagedestroy($idest);

        return true;
    }
}