<?php
require 'restfulApi.php';
require "documentReader.php";
session_start();
class application extends restfulApi
{
    function __construct()
    {
        parent::__construct();
    }

    function jobApply(){
        if($this->method =="POST"){
            $username = $this->params['username'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $username){
                $docReader = new documentReader();
                $filename = $docReader->readDoc('../doc/application/', 'file');
                if (!$filename) {
                    $this->response(200, "Internal Error");
                }
                $jobPostId = $this->params['jobPostId'];
                try{
                    $sql = "INSERT INTO jobApplication (applicant, jobPostId, docFile) VALUES (?,?,?)";
                    $stmt= $this->db->prepare($sql);
                    $result = $stmt->execute([$username, $jobPostId, $filename]);
                }catch (PDOException $e){
                    if($e->getCode()==='23000'){
                        try {
                            $sql = "UPDATE jobApplication SET docFile = ? WHERE applicant =? AND jobPostId =?";
                            $stmt= $this->db->prepare($sql);
                            $result = $stmt->execute([$filename,$username, $jobPostId]);
                        }catch (PDOException $e){
                            $this->response(200, $e->getMessage());
                        }
                    }else{
                        $this->response(200, $e->getMessage());
                    }
                }
                if($result){
                    $this->response(200,"Success");
                }else{
                    $this->response(200,"Internal Error!");
                }
            }else{
                header("Location: /DuhocMy/login.html");

            }

        }
    }
}

$application_api = new application();