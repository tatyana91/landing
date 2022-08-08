<?php
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