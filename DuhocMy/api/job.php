<?php
require 'restfulApi.php';
require "imageReader.php";
session_start();
class job extends restfulApi
{
    function __construct()
    {
        parent::__construct();
    }

    function job(){
        if($this->method == "POST"){
            $listBy = $this->params['username'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $listBy){
                $imgReader = new imageReader();
                $galleryPath = $imgReader->readMultipleImg('../img/job/', 'file');
                if (!$galleryPath) {
                    $galleryPath ='placeholderImg';
                }
                $companyName = $this->params['companyName'];
                $companyNameMetaphone = metaphone($companyName);
                $description = $this->params['description'];
                $address = $this->params['address'];
                $city   =  $this->params['city'];
                $cityMetaphone = metaphone($city);
                $state = $this->params['state'];
                $zip = $this->params['zip'];
                $jobType = $this->params['jobType'];
                $position = $this->params['position'];
                $positionMetaphone = metaphone($position);
                $salary = $this->params['salary'];

                $mapLink = "https://www.google.com/maps/embed/v1/place?key=AIzaSyDn0Q6b4vCSXJV0BlQgvwEJgjdclSHKWVM&q=".preg_replace('/\s+/', '+',$address.','.$city.','.$state.','.$zip);
                ;

                try{
                    $sql= "INSERT INTO jobPostdirectory (companyName, 
                                                        companyNameMetaphone, 
                                                        jobType,
                                                        position,
                                                        positionMetaphone,
                                                        salary, 
                                                        description, 
                                                        listBy, 
                                                        address, city, state, zip, 
                                                        gallery, 
                                                        cityMetaphone, 
                                                        ggMapAddrLink) 
                                                        VALUES  (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    $stmt = $this->db->prepare($sql);
                    $result = $stmt->execute([$companyName,
                                                $companyNameMetaphone,
                                                $jobType,
                                                $position,
                                                $positionMetaphone,
                                                $salary,
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
        //FIND JOB BY USERNAME
        elseif($this->method =="GET"){
            $jobsJSON = array();

            //GET SUBJECT BY USER NAME

            if (isset($_GET['username'])) {
                $sql = "SELECT * FROM jobPostDirectory INNER JOIN user ON jobPostDirectory.listBy = user.username WHERE listBy = ? ORDER BY timestamp DESC ";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$_GET['username']]);
            }elseif (isset($_GET['jobPostId'])){
                $sql = "SELECT * FROM jobPostDirectory INNER JOIN user ON jobPostDirectory.listBy = user.username WHERE id = ? ORDER BY timestamp DESC ";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$_GET['jobPostId']]);
            }

            //GET ROOMs BY SEARCH
            //GET ROOMS BY RENTER NAME
            else{
                $sql = "SELECT * FROM jobPostDirectory INNER JOIN user ON jobPostDirectory.listBy = user.username WHERE ";
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

                if (isset($_GET['salary'])){
                    if ($_GET['salary'] ==''){
                        $salary = 1000000;
                    }else{
                        $salary = $_GET['salary'];
                    }

                    $sql = $sql."( salary <= ".$salary.") AND ";
                }

                if(isset($_GET['jobType']) && $_GET['jobType']!='Both'){
                    $sql =$sql."(jobType =".$_GET['jobType'].") AND";
                }

                if(isset($_GET['companyName'])){
                    $companyNameToken = explode(' ',$_GET['companyName']);
                    foreach ($companyNameToken as $word){
                        $metaWord = metaphone($word);
                        $sql = $sql."(
                                        companyName LIKE '%".$word."%' 
                                        OR companyNameMetaPhone LIKE '%".$metaWord."%' 
                                    )
                                    AND ";
                    }
                }

                if(isset($_GET['position'])){
                    $positionToken = explode(' ',$_GET['position']);
                    foreach ($positionToken as $word){
                        $metaWord = metaphone($word);
                        $sql = $sql."(
                                        position LIKE '%".$word."%' 
                                        OR positionMetaPhone LIKE '%".$metaWord."%' 
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
                    while ($jobs = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $jobjson = array(
                            'jobPostId' => $jobs['id'],
                            'recruiter' => $jobs['listBy'],
                            'recruiterFullname'=> $jobs['fullname'],
                            'salary' => $jobs['salary'],
                            'address' => $jobs['address'],
                            'city' => $jobs['city'],
                            'state'=> $jobs['state'],
                            'zip'=>$jobs['zip'],
                            'description'=>$jobs['description'],
                            'gallery'=> $jobs['gallery'],
                            'mapLink' => $jobs['ggMapAddrLink'],
                            'companyName' =>$jobs['companyName'],
                            'jobTitle'=>$jobs['position'],
                            'jobType'=>$jobs['jobType'],
                        );
                        array_push($jobsJSON, $jobjson);
                    }

                    $this->response(200, $jobsJSON);
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
                        INNER JOIN (SELECT listBy FROM jobPostDirectory 
                                    WHERE listBy = ? LIMIT 1) as recruiter
                        ON username.username = recruiter.listBy  
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

    //REMOVE JOB
    function removeJob(){
        if ($this->method == 'POST') {

            $username = $this->params['username'];
            $jobPostId = $this->params['jobPostId'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $username){
                $sql = "DELETE FROM jobPostDirectory WHERE id= ?";
                $stmt = $this->db->prepare($sql);
                try{
                    $result = $stmt->execute([$jobPostId]);

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

$room_api = new job();