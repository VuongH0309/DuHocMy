<?php
require 'restfulApi.php';
session_start();
class message extends restfulApi
{
    function __construct()
    {
        parent::__construct();
    }

    function message(){

        if($this->method == "GET"){
            $username = $_GET['username'];
            $chatMate = $_GET['chatMate'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $username){
                $sql = "SELECT * FROM message WHERE (sender =? AND receiver = ?) OR (sender =? AND receiver = ?) ORDER  BY timeStamp DESC LIMIT 1";
                $stmt = $this->db->prepare($sql);
                try {
                    $result = $stmt->execute([$username, $chatMate, $chatMate, $username]);
                }catch (PDOException $e){
                    $this->response(200, $e->getMessage());
                }
                if($result){
                    $mess = $stmt->fetch(PDO::FETCH_ASSOC);
                    $message = array(
                        'messageId'=> $mess['messageId'],
                        'sender'=> $mess['sender'],
                        'receiver'=> $mess['receiver'],
                        'content' => $mess['content']
                    );
                    $this->response(200, $message);
                }
            }else{
                header("Location: /login.html");
            }

        }

        if($this->method == "POST"){
            $sender = $this->params['sender'];
            $receiver = $this->params['receiver'];
            $content =  $this->params['content'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $sender){
                if($sender>$receiver){
                    $connecID = $sender.'::'.$receiver;
                }else{
                    $connecID = $receiver.'::'.$sender;
                }
                $sql = "INSERT INTO message (sender, receiver, content, connectId) VALUES  (?,?,?,?)";
                $stmt = $this->db->prepare($sql);
                try{
                    $result = $stmt->execute([$sender, $receiver, $content, $connecID]);

                }catch (PDOException $e){
                    $this->response(200, $this->params);
                    $this->response(200, $e->getMessage());
                }
                if($result)
                {
                    if ($stmt->rowCount()>0){
                        $this->response(200, "Success");
                    }else{
                        $this->response(200, "Connecting Error");

                    }
                }
            }else{
                header("Location: /DuhocMy/login.html");
            }

        }
    }

    function conversation(){
        if($this->method == "GET") {
            $username = $_GET['username'];
            $chatMate = $_GET['chatMate'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $username){
                $sql = "SELECT * FROM message WHERE (sender =? AND receiver = ?) OR (sender =? AND receiver = ?) ORDER  BY timeStamp";
                $stmt = $this->db->prepare($sql);
                $conversation = array();
                try {
                    $result = $stmt->execute([$username, $chatMate, $chatMate, $username]);
                }catch (PDOException $e){
                    $this->response(200, $e->getMessage());
                }
                if ($result){
                    while($mess = $stmt->fetch(PDO::FETCH_ASSOC)){
                        $message = array(
                            'sender'=> $mess['sender'],
                            'receiver'=> $mess['receiver'],
                            'content' => $mess['content']
                        );
                        array_push($conversation, $message);
                    }
                    $this->response(200, $conversation);
                }
            }else{
                header("Location: /DuhocMy/login.html");
            }


        }
    }

    function recent(){
        if($this->method == "GET"){
            $username = $_GET['username'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $username) {
                $sql = "SELECT * 
                        FROM message 
                        WHERE   ((sender =?) OR (receiver =?)) 
                                AND (messageId IN ( SELECT MAX(messageid) 
                                                    FROM message 
                                                    GROUP BY connectID))
                        ORDER BY timestamp DESC  ";
                $stmt = $this->db->prepare($sql);
                try{
                    $result = $stmt->execute([$username,$username]);
                }catch(PDOException $e){
                    $this->response(200, $e->getMessage());
                }
                if($result){
                    $recentChatMate= array();
                    while($mess = $stmt->fetch(PDO::FETCH_ASSOC)){
                        if($username== $mess['sender']){
                            $chatmate = array(
                                'name'=> $mess['receiver']
                            );
                        }else{
                            $chatmate = array(
                                'name'=> $mess['sender']
                            );
                        }
                        array_push($recentChatMate, $chatmate);
                    }
                    $this->response(200, $recentChatMate);
                }
            }else{
                header("Location: /DuhocMy/login.html");
            }
        }

    }

    function mostRecent(){
        if($this->method == "GET"){
            $username = $_GET['username'];
            if (isset($_SESSION['login']) && $_SESSION['login'] === $username) {
                $sql = "SELECT * 
                        FROM message 
                        WHERE   ((sender =?) OR (receiver =?)) 
                                AND (messageId IN ( SELECT MAX(messageid) 
                                                    FROM message 
                                                    GROUP BY connectID)) 
                        ORDER BY timestamp DESC 
                        LIMIT 1";
                $stmt = $this->db->prepare($sql);
                try{
                    $result = $stmt->execute([$username,$username]);
                }catch(PDOException $e){
                    $this->response(200, $e->getMessage());
                }
                if($result){
                    $mess = $stmt->fetch(PDO::FETCH_ASSOC);
                    if($username== $mess['sender']){
                        $chatmate = array(
                            'name'=> $mess['receiver']
                        );
                    }else{
                        $chatmate = array(
                            'name'=> $mess['sender']
                        );
                    }
                    $this->response(200, $chatmate);
                }
            }else{
                header("Location: /DuhocMy/login.html");
            }
        }

    }
}
$message_api = new message();