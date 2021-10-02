<?php

require 'restfulApi.php';
require 'imageReader.php';
session_start();
class user extends restfulApi
{
    function __construct()
    {
        parent::__construct();
    }

    function user()
    {
        if ($this->method == 'GET') {
            if (isset($_GET['username'])) {
                $username = $_GET['username'];
                $sql = "SELECT * FROM user WHERE BINARY username = ? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$username]);
                if ($result) {
                    if ($stmt->rowCount() > 0) {
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        $userJSON = array(
                            'avatar' => $user['avatar'],
                            'firstname' => $user['firstname'],
                            'lastname' => $user['lastname'],
                            'username' => $user['username'],
                            'fullname' => $user['fullname'],
                            'email' => $user['email'],
                            'tel'=>$user['tel'],
                            'bio' =>$user['bio']
                        );
                        $this->response(200, $userJSON);
                    } else {
                        echo $this->response(200, "Username isn't exist");
                    }

                } else {
                    $this->response(500, "Internal Error");
                }
            } else {
                $sql = "SELECT * FROM user";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute();
                if ($result) {
                    if ($stmt->rowCount() > 0) {
                        $usersJSON = [];

                        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $userJSON = array(
                                'avatar' => $user['avatar'],
                                'username' => $user['username'],
                                'fullname' => $user['fullname'],
                                'email' => $user['email'],
                                'tel'=>$user['tel'],
                                'bio' =>$user['bio']
                            );
                            array_push($usersJSON, $userJSON);
                        }
                        $this->response (200, $usersJSON);
                    } else {
                        echo $this->response(200, "Username isn't exist");
                    }

                } else {
                    $this->response(500, "Internal Error");
                }
            }
        }

        // REGISTER
        elseif ($this->method == 'POST') {
            $imgReader = new imageReader();
            $filename = $imgReader->readImage('../img/avatar/', 'file');
            if (!$filename) {
                $filename = "placeholderPic.png";
            }

            $username = $this->params['username'];
            $usernameMetaphone = metaphone($username);
            $password = $password = password_hash($this->params['password'], PASSWORD_DEFAULT);
            $email = $this->params['email'];
            $firstname = $this->params['firstname'];
            $lastname = $this->params['lastname'];
            $fullname = $this->params['firstname'] . ' ' . $this->params['lastname'];
            $fullnameMetaphone = metaphone($fullname);


            try {
                $sql = "INSERT INTO user (username, usernameMetaphone ,password,email, fullname, fullnameMetaphone, avatar, firstname, lastname) VALUES (?,?,?,?,?,?,?,?,?)";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$username, $usernameMetaphone, $password, $email, $fullname, $fullnameMetaphone, $filename, $firstname, $lastname]);
            } catch (PDOException $e) {
                if ($e->getCode() == '23000')
                    $this->response(200, "Username or Email has already exist");
                else {
                    $this->response(500, "Internal Error");
                }
            }
            if ($result) {
                $this->response(200, "Success");
            } else {
                $this->response(500, "Error");
            }
        }
    }

    //CHANGE AVATAR
    function changeAvatar()
    {

        if ($this->method == 'POST') {
            $imgReader = new imageReader();
            $filename = $imgReader->readImage('../img/avatar/', 'file');
            if (!$filename) {
                $this->response(200, "Internal Error");
            }

            $username = $this->params['username'];

            if (isset($_SESSION['login']) && $_SESSION['login'] === $username){
                try {
                    $sql = "UPDATE user SET avatar = ? WHERE username =?";
                    $stmt = $this->db->prepare($sql);
                    $result = $stmt->execute([$filename, $username]);
                } catch (PDOException $e) {
                    $this->response(200, "Internal Error");
                }
                if ($result) {
                    $this->response(200, "Success");
                } else {
                    $this->response(200, "Error");
                }
            }else{
                header("Location: /DuhocMy/login.html");
            }
        }
    }

    //CHANGE GENERAL INFO

    function changeInfo()
    {
        if ($this->method == 'POST') {
            $username = $this->params['username'];
            $firstname = $this->params['firstname'];
            $lastname = $this->params['lastname'];
            $email = $this->params['email'];
            $fullname = $firstname.' '.$lastname;
            $fullnameMetaphone = metaphone($fullname);
            $tel = $this->params['tel'];
            $bio = $this->params['bio'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $username){
                try {
                    $sql = "UPDATE user SET firstname = ? , lastname = ?, email = ?, fullname = ?, fullnameMetaphone = ?, tel= ?, bio = ? WHERE username = ?";
                    $stmt = $this->db->prepare($sql);
                    $result = $stmt->execute([$firstname, $lastname, $email,$fullname, $fullnameMetaphone,$tel,$bio,$username]);
                } catch (PDOException $e) {
                    if ($e->getCode() == '23000')
                        $this->response(200, "Email has been register for a different user!");
                    else {
                        $this->response(500, "Internal Error");
                    }
                }
                if ($result) {
                    $this->response(200, "Success");
                } else {
                    $this->response(200, "Error");
                }
            }else{
                header("Location: /DuhocMy/login.html");
            }

        }

    }

    function changePassword(){
        if($this->method == "POST"){
            $username = $this->params['username'];
            $password = $this->params['currentPassword'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $username){
                try {
                    $sql = "SELECT * FROM user WHERE BINARY username = ? LIMIT 1";
                    $stmt = $this->db->prepare($sql);
                    $result = $stmt->execute([$username]);
                }catch (PDOException $e){
                    $this->response(500, "Internal Error");
                }

                if($result) {
                    if ($stmt->rowCount() > 0) {
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        $hash = $user['password'];
                        if (password_verify($password, $hash)) {
                            $password = password_hash($this->params['password'], PASSWORD_DEFAULT);
                            try {
                                $sql = "UPDATE user SET password = ? WHERE username =?";
                                $stmt = $this->db->prepare($sql);
                                $result = $stmt->execute([$password, $username]);
                            } catch (PDOException $e) {
                                $this->response(200, "Internal Error");
                            }
                            if ($result) {
                                $this->response(200, "Success");
                            } else {
                                $this->response(200, "Error");
                            }
                        } else {
                            $this->response(200, "Current Password Isn't Correct!");
                        }
                    }
                }
            }else{
                header("Location: /DuhocMy/login.html");
            }


        }
    }

}
$user_api = new user();