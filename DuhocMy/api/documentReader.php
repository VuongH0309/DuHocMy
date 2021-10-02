<?php


class documentReader
{
    function __construct()
    {
        $path ="";
    }

    function readDoc($path, $ajaxfile){
        $file = false;
        if (isset($_FILES[$ajaxfile]['name'])){
            $filename = $_FILES['file']['name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type = finfo_file($finfo, $_FILES['file']['tmp_name']);

            if(isset($type) && in_array($type, array("image/png", "image/jpeg", "application/msword", "text/plain", "application/pdf"))){
                // LIMIT 5MB DOCUMENT FILE
                if($_FILES['file']['size']>500000){
                    $this->response(200, "Your file is exceed 5MB!\nPlease choose a smaller file!");
                }else{
                    $location = $path.$filename;
                    $rawBaseName = pathinfo($filename, PATHINFO_FILENAME );
                    $extension = pathinfo($filename, PATHINFO_EXTENSION );
                    $counter =1;
                    while (file_exists($location)){
                        $filename = $rawBaseName."(".$counter.").".$extension;
                        $location = $path.$filename;
                        $counter++;
                    }
                    move_uploaded_file($_FILES[$ajaxfile]['tmp_name'], $location);
                    $file = $filename;
                }
            }
        }

        return $file;
    }
}