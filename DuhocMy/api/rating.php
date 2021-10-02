<?php
require "restfulApi.php";
session_start();
class rating extends restfulApi
{
    function __construct()
    {
        parent::__construct();

    }

    function rate(){
        if($this->method == "POST")
        {
            $rater = $this->params['rater'];
            $ratedUser = $this->params['username'];
            $rateValue = $this->params['rateValue'];

            if (isset($_SESSION['login']) && $_SESSION['login'] === $rater){
                try {
                    $sql = "INSERT INTO rating (rater, ratedUser , rateValue) VALUES (?,?,?)";
                    $stmt = $this->db->prepare($sql);
                    $result = $stmt->execute([$rater, $ratedUser, $rateValue]);
                }catch (PDOException $e){
                    if($e->getCode() =='23000'){
                        try {
                            $sql = "UPDATE rating SET rateValue = ? WHERE rater =? AND ratedUser = ?";
                            $stmt = $this->db->prepare($sql);
                            $result = $stmt->execute([$rateValue, $rater, $ratedUser]);
                        }catch (PDOException $e){
                            $this->response(200, "Internal Error");
                        }
                    }else{
                        $this->response(200, "Internal Error");
                    }
                }
                if($result)
                {
                    $this->response(200, "Success");
                }
                else{
                    $this->response(200, "Error");
                }
            }else{
                header("Location: /DuhocMy/login.html");
            }
        }





        if($this->method == "GET")
        {
            $ratedUser = $_GET['username'];
            try {
                $sql =  "SELECT AVG(rateValue) as aveRateVal FROM rating WHERE ratedUser =?";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$ratedUser]);
            }catch (PDOException $e){
                $this->response(500, 'Internal Error' );
            }

            if($result){
                if ($stmt->rowCount() > 0) {
                    $aveRateVal = $stmt->fetch(PDO::FETCH_ASSOC);
                    if(isset($aveRateVal['aveRateVal'])) {
                        $aveRate = array(
                            'aveRateVal'=> $aveRateVal['aveRateVal']
                        );
                    }
                    else{
                        $aveRate = array(
                            'aveRateVal'=> '0'
                        );
                    }
                $this->response(200, $aveRate);
            }
            }
        }
    }
}

$rate_api =new rating();