<?php


class restfulApi
{
    protected $method ="";
    protected $endpoint = "";
    protected $params = array();
    protected $file = null;

    protected $db = null;
    private $db_user = "root";
    private $db_pass = "";
    private $db_name = "duhocmydb";
    private $db_host = "192.168.137.1";

    public function __construct(){
        try {
            $this->db = new PDO('mysql:host='.$this->db_host.';dbname=' . $this->db_name . ';charset=utf8', $this->db_user, $this->db_pass);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }

        $this->_input();
        $this->_processApi();
    }

    private function _input(){
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        $this->params = explode('/', trim($_SERVER['PATH_INFO'],'/'));
        $this->endpoint = array_shift($this->params);
        $method = $_SERVER['REQUEST_METHOD'];
        $allow_method = array('GET', 'POST', 'PUT', 'DELETE');

        if (in_array($method, $allow_method)){
            $this->method = $method;
        }

        switch ($this->method){
            case 'POST':
                $this->params = $_POST;
                break;
            case 'DELETE':
            case 'GET': break;
            case 'PUT':
                $this->file = file_get_contents("php://input");break;
            default:
                $this->response(500, "Invalid Method");break;
        }
    }

    protected function _processApi(){
        if (method_exists($this, $this->endpoint)){
            $this->{$this->endpoint}();
        }
        else{
            $this->response(404,"Unknown endpoint");
        }
    }

    protected function response($status_code, $data = Null){
        header($this->_build_http_header_string($status_code));
        //header("Content-Type: application/json");
        echo json_encode($data, JSON_PRETTY_PRINT);
        die();
    }

    private function _build_http_header_string($status_code){
        $status = array(
            200 => 'OK',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error'
        );
        return "HTTP/1.1 ".$status_code." ".$status[$status_code];
    }
}