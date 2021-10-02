<?php
require 'restfulApi.php';
session_start();

class login extends restfulApi{


    function __construct()
    {
        parent::__construct();
    }


    function login(){
        if ($this->method == 'POST'){
            $username = $this->params['username'];
            $password = $this->params['password'];
            try {
                $sql = "SELECT * FROM user WHERE BINARY username = ? OR BINARY email = ? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$username, $username]);
            }catch (PDOException $e){
                    $this->response(500, "Internal Error");
            }

            if($result){
                if($stmt->rowCount()>0){
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $hash = $user['password'];
                    if(password_verify($password, $hash)){
                        $_SESSION['login'] = $user['username'];
                        $userjson = array(
                            'username' =>$user['username'],
                            );
                        $this->response(200,$userjson);
                    }
                    else{
                        $this->response(200,"Wrong Password");
                    }
                }
                else{
                    $this->response(200, "Username or Email isn't exist");
                }
            }
            else{
                $this->response(500, "Internal Error");
            }
        }
        else{
            $this->response(405, "Internal Error");
        }
    }

    function logout(){
        session_destroy();
        unset($_SESSION);
        if(!isset($_SESSION[['login']])){
            $this->response(200,"Success");
        }
    }

}

$user_api = new login();