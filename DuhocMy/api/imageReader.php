<?php


class imageReader
{
    function __construct()
    {
        $path ="";
    }

    function readImage($path, $ajaxfile){
        $file = false;
        if (isset($_FILES[$ajaxfile]['name'])){
            $filename = $_FILES['file']['name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type = finfo_file($finfo, $_FILES['file']['tmp_name']);

            if(isset($type) && in_array($type, array("image/png", "image/jpeg", "image/gif"))){
                // LIMIT 5MB IMAGE FILE
                if($_FILES['file']['size']>500000){
                    $this->response(200, "Image is exceed 5MB!\nPlease choose a smaller image!");
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

    function readMultipleImg($path, $ajaxfile){
        $galleryPath = false;
        $newdir = mt_rand();
        if (isset($_FILES[$ajaxfile]['name'])){
            $numOfFiles = count($_FILES[$ajaxfile]['name']);
            while(file_exists($path.$newdir."/")){
                $newdir= mt_rand();
            }
            mkdir($path.$newdir."/",0700);
            for ($i =0; $i< $numOfFiles;$i++){
                $filename = $_FILES['file']['name'][$i];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $type = finfo_file($finfo, $_FILES['file']['tmp_name'][$i]);

                if(isset($type) && in_array($type, array("image/png", "image/jpeg", "image/gif"))) {
                    // LIMIT 5MB IMAGE FILE
                    if ($_FILES['file']['size'][$i] > 500000) {
                        $this->response(200, "Image is exceed 5MB!\nPlease choose a smaller image!");
                        $galleryPath = false;
                        break;
                    } else {

                        $location = $path.$newdir."/".$filename;
                        $rawBaseName = pathinfo($filename, PATHINFO_FILENAME);
                        $extension = pathinfo($filename, PATHINFO_EXTENSION);
                        $counter = 1;
                        while (file_exists($location)) {
                            $filename = $rawBaseName . "(" . $counter . ")." . $extension;
                            $location = $path . $newdir."/". $filename;
                            $counter++;
                        }
                        move_uploaded_file($_FILES[$ajaxfile]['tmp_name'][$i], $location);
                        $galleryPath = $newdir;
                    }
                }
            }
        }
        return $galleryPath;
    }
}