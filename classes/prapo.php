<?php

use \Psr\Container\ContainerInterface;
use Slim\Http\UploadedFile;

use function Complex\ln;

class prapo
{
    protected $container;
    protected $db;


    // constructor receives container instance
    public function __construct()
    {
        global $container;
        $this->container = $container;
        $this->db = $container->db;
    }


    public function getProduct(){
        $sql="SELECT * FROM prapo.product";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function postProduct($data){
        $values=[
            "name" => '',
            "cost" => 0,
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = ($data[$key]);
            }
        }
        // var_dump($values);
        $sql="INSERT INTO  prapo.product(name, cost)
        VALUES (:name,:cost)";
        $stmt = $this->db->prepare($sql);
        if($stmt->execute($values)){
            return ["status" => "success"];
        }


        
    }

    public function patchProduct($data){
        $values=[
            "name" => '',
            "cost" => 0,
            "product_id" => 0

        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = ($data[$key]);
            }
        }
        $sql="UPDATE prapo.product
        SET name = :name, cost = :cost
        WHERE id=:product_id";
        $stmt = $this->db->prepare($sql);

        if($stmt->execute($values)){
            return ["status" => "success"];
        }else{
            return  $stmt->errorInfo();
        }

    }
    public function deleteProduct($data){
        $values=[
            "product_id" => 0
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = ($data[$key]);
            }
        }
        $sql="DELETE FROM  prapo.product
        WHERE id=:product_id";
        $stmt = $this->db->prepare($sql);
        if($stmt->execute($values)){
            return ["status" => "success"];
        }else{
            return ["status" => "failed"];

            // return  $stmt->errorInfo();
        }
    }

    public function getOrder(){
        $sql="SELECT * FROM prapo.\"order\"";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function postOrder($data){
        $values=[
            "name" => '',
            "user_id" => 0,
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = ($data[$key]);
            }
        }
        // var_dump($values);
        $sql="INSERT INTO  prapo.\"order\" (name, user_id)
        VALUES (:name,:user_id)";
        $stmt = $this->db->prepare($sql);
        if($stmt->execute($values)){
            return ["status" => "success"];
        }
        // return ["status" => "success"];
    }

    public function patchOrder($data){
        $values=[
            "name" => '',
            "user_id" => 0,
            "order_id" => 0

        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = ($data[$key]);
            }
        }
        $sql="UPDATE prapo.\"order\"
        SET name = :name, user_id = :user_id
        WHERE id=:order_id";
        $stmt = $this->db->prepare($sql);

        if($stmt->execute($values)){
            return ["status" => "success"];
        }else{
            return  $stmt->errorInfo();
        }

    }

    public function deleteOrder($data){
        $values=[
            "order_id" => 0
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = ($data[$key]);
            }
        }
        $sql="DELETE FROM  prapo.\"order\"
        WHERE id=:order_id";
        $stmt = $this->db->prepare($sql);
        if($stmt->execute($values)){
            return ["status" => "success"];
        }else{
            return ["status" => "failed"];

            // return  $stmt->errorInfo();
        }
    }


   
}
