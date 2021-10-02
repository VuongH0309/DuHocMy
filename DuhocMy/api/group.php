<?php
require 'restfulApi.php';
require "imageReader.php";
session_start();
class group extends restfulApi
{
    function __construct()
    {
        parent::__construct();
    }

    function group(){
        if($this->method == "POST"){
            $listBy = $this->params['username'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $listBy){
                $imgReader = new imageReader();
                $galleryPath = $imgReader->readMultipleImg('../img/group/', 'file');
                if (!$galleryPath) {
                    $galleryPath ='placeholderImg';
                }
                $groupName = $this->params['groupName'];
                $groupNameMetaphone = metaphone($groupName);
                $description = $this->params['description'];
                $address = $this->params['address'];
                $city   =  $this->params['city'];
                $cityMetaphone = metaphone($city);
                $state = $this->params['state'];
                $zip = $this->params['zip'];

                $mapLink = "https://www.google.com/maps/embed/v1/place?key=AIzaSyDn0Q6b4vCSXJV0BlQgvwEJgjdclSHKWVM&q=".preg_replace('/\s+/', '+',$address.','.$city.','.$state.','.$zip);
                ;

                try{
                    $sql= "INSERT INTO groupDirectory (groupName, 
                                                        groupNameMetaphone,
                                                        description, 
                                                        listBy, 
                                                        address, city, state, zip, 
                                                        gallery, 
                                                        cityMetaphone, 
                                                        ggMapAddrLink) 
                                                        VALUES  (?,?,?,?,?,?,?,?,?,?,?)";
                    $stmt = $this->db->prepare($sql);
                    $result = $stmt->execute([$groupName,
                        $groupNameMetaphone,
                        $description, $listBy, $address, $city, $state, $zip,$galleryPath, $cityMetaphone, $mapLink]);
                }catch (PDOException $e){
                    $this->response(200, $e->getMessage());
                }

                if($result){
                    $this->response(200, "Success");
                }else{
                    $this->response(200, "Internal Error");
                }
            }else{
                header('Location: /DuhocMy/login.html');
            }
        }
        //FIND GROUP BY USERNAME
        elseif($this->method =="GET"){
            $groupsJSON = array();

            //GET SUBJECT BY USER NAME

            if (isset($_GET['username'])) {
                $sql = "SELECT * FROM groupDirectory INNER JOIN user ON groupDirectory.listBy = user.username WHERE listBy = ? ORDER BY timestamp DESC ";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$_GET['username']]);
            }elseif (isset($_GET['groupId'])){
                $sql = "SELECT * FROM groupDirectory INNER JOIN user ON groupDirectory.listBy = user.username WHERE id = ? ORDER BY timestamp DESC ";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$_GET['groupId']]);
            }

            //GET ROOMs BY SEARCH
            //GET ROOMS BY RENTER NAME
            else{
                $sql = "SELECT * FROM groupDirectory INNER JOIN user ON groupDirectory.listBy = user.username WHERE ";
                if (isset($_GET['name'])){
                    $nameToken = explode(' ',$_GET['name']);

                    foreach ($nameToken as $word) {
                        $metaWord = metaphone($word);
                        $sql = $sql."(
                                        username LIKE '%".$word."%' 
                                        OR usernameMetaphone LIKE '%".$metaWord."%' 
                                        OR fullname LIKE '%".$word."%' 
                                        OR fullnameMetaphone LIKE '%".$metaWord."%' 
                                    )
                                    AND ";
                    }
                }
                // GET  BY CITY
                if(isset($_GET['city'])){
                    $cityNameToken = explode(' ',$_GET['city']);
                    foreach ($cityNameToken as $word){
                        $metaWord = metaphone($word);
                        $sql = $sql."(
                                        city LIKE '%".$word."%' 
                                        OR cityMetaPhone LIKE '%".$metaWord."%' 
                                    )
                                    AND ";
                    }
                }

                if(isset($_GET['state'])){
                    if ($_GET['state']==''){
                        $sql =$sql."(state LIKE '%%') AND";
                    }else{
                        $sql = $sql."(state ='".$_GET['state']."' )AND ";
                    }
                }

                if(isset($_GET['zip'])){
                    if ($_GET['zip']==''){
                        $sql =$sql."(zip <100000) AND";
                    }else{
                        $sql = $sql."(zip =".$_GET['zip']." )AND ";
                    }
                }


                if(isset($_GET['groupName'])){
                    $companyNameToken = explode(' ',$_GET['groupName']);
                    foreach ($companyNameToken as $word){
                        $metaWord = metaphone($word);
                        $sql = $sql."(
                                        groupName LIKE '%".$word."%' 
                                        OR groupNameMetaPhone LIKE '%".$metaWord."%' 
                                    )
                                    AND ";
                    }
                }


                $sql = $sql."1=1 ORDER BY timestamp DESC";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute();

            }

            //EXECUTE

            if ($result) {
                if ($stmt->rowCount() > 0) {
                    while ($groups = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $groupjson = array(
                            'groupId' => $groups['id'],
                            'leader' => $groups['listBy'],
                            'leaderFullname'=> $groups['fullname'],
                            'address' => $groups['address'],
                            'city' => $groups['city'],
                            'state'=> $groups['state'],
                            'zip'=>$groups['zip'],
                            'description'=>$groups['description'],
                            'gallery'=> $groups['gallery'],
                            'mapLink' => $groups['ggMapAddrLink'],
                            'groupName' =>$groups['groupName'],
                        );
                        array_push($groupsJSON, $groupjson);
                    }

                    $this->response(200, $groupsJSON);
                } else {
                    $this->response(200, "No result");
                }
            } else {
                $this->response(500, "Internal Error");
            }

        }
    }

    function user()
    {
        if ($this->method == 'GET') {
            if (isset($_GET['username'])) {
                $sql = "SELECT * FROM user as username
                        INNER JOIN (SELECT listBy FROM groupDirectory 
                                    WHERE listBy = ? LIMIT 1) as leader
                        ON username.username = leader.listBy  
                        LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$_GET['username']]);
                if($result){
                    if ($stmt->rowCount() > 0) {
                        $renterName = $stmt->fetch(PDO::FETCH_ASSOC);
                        $userJSON = array(
                            'avatar'=> $renterName['avatar'],
                            'username'  => $renterName['username'],
                            'fullname' => $renterName['fullname'],
                            'email'     => $renterName['email'],
                            'tel'=>$renterName['tel'],
                        );
                        $this->response(200, $userJSON);
                    }
                    else{
                        echo $this->response(200, "Username isn't exist");
                    }
                }
                else {
                    $this->response(500, "Internal Error");
                }
            }
        }
    }

    //REMOVE GROUP
    function removeGroup(){
        if ($this->method == 'POST') {

            $username = $this->params['username'];
            $groupId = $this->params['groupId'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $username){
                $sql = "DELETE FROM groupDirectory WHERE id= ?";
                $stmt = $this->db->prepare($sql);
                try{
                    $result = $stmt->execute([$groupId]);

                }catch (PDOException $e){
                    $this->response(200, $e->getMessage());
                }
                if ($result) {
                    $this->response(200, "Success");
                } else {
                    $this->response(500, "Error");
                }
            }else{
                header("Location: /DuhocMy/login.html");
            }

        }
    }
}

$room_api = new group();