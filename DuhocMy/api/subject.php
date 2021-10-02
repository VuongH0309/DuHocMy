<?php
require 'restfulApi.php';
session_start();
class subject extends restfulApi
{
    function __construct()
    {
        parent::__construct();
    }

    function subject()
    {

        //CREATE NEW SUBJECT

        if ($this->method == 'POST') {

            $createdBy = $this->params['username'];
            $subject = $this->params['subject'];
            $price = $this->params['price'];
            $subjectMetaphone = metaphone($subject);
            $description = $this->params['description'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $createdBy){
                $sql = "INSERT INTO tutordirector (createdBy, subject, price, subjectMetaphone, description) VALUES (?,?,?,?,?)";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$createdBy, $subject, $price, $subjectMetaphone, $description]);
                if ($result) {
                    $this->response(200, "Success");
                } else {
                    $this->response(500, "Error");
                }
            }else{
                header("Location: /DuhocMy/login.html");
            }


            //GET SUBJECTS

        } elseif ($this->method == 'GET') {
            $subjectsjson = array();

            //GET SUBJECT BY USER NAME

            if (isset($_GET['username'])) {
                $sql = "SELECT * FROM tutordirector INNER JOIN user ON tutordirector.createdBy = user.username WHERE createdBy = ? ORDER BY timestampe ";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$_GET['username']]);
            }
            //GET SUBJECT BY SEARCH
            //GET SUBJECT BY TUTOR NAME
            else{
                $sql = "SELECT * FROM tutordirector INNER JOIN user ON tutordirector.createdBy = user.username WHERE ";
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
                // GET SUBJECT BY SUBJECT NAME
                if(isset($_GET['subjectName'])){
                    $subjectNameToken = explode(' ',$_GET['subjectName']);
                    foreach ($subjectNameToken as $word){
                        $metaWord = metaphone($word);
                        $sql = $sql."(
                                        subject LIKE '%".$word."%' 
                                        OR subjectMetaphone LIKE '%".$metaWord."%' 
                                    )
                                    AND ";
                    }
                }

                if (isset($_GET['price'])){
                    if ($_GET['price'] ==''){
                        $price = 1000000;
                    }else{
                        $price = $_GET['price'];
                    }

                    $sql = $sql."( price <= ".$price.") AND ";
                }
                $sql = $sql."1=1 ORDER BY timestampe";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute();

            }

            //EXECUTE

            if ($result) {
                if ($stmt->rowCount() > 0) {
                    while ($subjects = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $subjectjson = array(
                            'subjectId' => $subjects['id'],
                            'subject' => $subjects['subject'],
                            'price' => $subjects['price'],
                            'tutor' => $subjects['createdBy'],
                            'tutorName'=>$subjects['fullname'],
                            'description'=>$subjects['description']
                        );
                        array_push($subjectsjson, $subjectjson);
                    }

                    $this->response(200, $subjectsjson);
                } else {
                    $this->response(200, "No result");
                }
            } else {
                $this->response(500, "Internal Error");
            }

        }
    }

    //GET USER INFO FORM TUTOR DIRECTORY
    function user()
    {
        if ($this->method == 'GET') {
            if (isset($_GET['username'])) {
                $sql = "SELECT * FROM user as username
                        INNER JOIN (SELECT createdBy FROM tutorDirector 
                                    WHERE createdBy = ? LIMIT 1) as tutor
                        ON username.username = tutor.createdBy  
                        LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$_GET['username']]);
                if($result){
                    if ($stmt->rowCount() > 0) {
                        $tutorName = $stmt->fetch(PDO::FETCH_ASSOC);
                        $userJSON = array(
                            'avatar'=> $tutorName['avatar'],
                            'username'  => $tutorName['username'],
                            'fullname' => $tutorName['fullname'],
                            'email'     => $tutorName['email'],
                            'tel'=>$tutorName['tel']
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

    //REMOVE SUBJECT
    function removeSubject(){
        if ($this->method == 'POST') {

            $username = $this->params['username'];
            $subjectId = $this->params['subjectId'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $username){
                $sql = "DELETE FROM tutorDirector WHERE id= ?";
                $stmt = $this->db->prepare($sql);
                try{
                    $result = $stmt->execute([$subjectId]);

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

$subject_api = new subject();