<?php

use \Psr\Container\ContainerInterface;

class Environment
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
    public function postQuality($data){
        $values = [
            "temperature" => null,
            "humidity" => null,
            "PM1_0" => null,
            "PM2_5" => null,
            "PM10" => null
        ];
        
        foreach ($data as $key => $value) {
            if($key=='PM1.0'){
                $key = 'PM1_0';
            }else if($key=='PM2.5'){
                $key = 'PM2_5';
            }
            $values[$key] = $value;
        }
        $sql = "INSERT INTO environment.quality(created, temperature, humidity, \"PM1.0\", \"PM2.5\", \"PM10\") VALUES
            (NOW(), :temperature, :humidity, :PM1_0, :PM2_5, :PM10)
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)){
            return [
                "status"=>"failed"
            ];
        }
        return [
            "status"=>"success"
        ];
    }
    public function getQuality(){
        $sql = "SELECT created, temperature, humidity, \"PM1.0\", \"PM2.5\", \"PM10\", thing
            FROM environment.quality
            ORDER BY created DESC 
            LIMIT 1;
        ";
        $stmt = $this->db->prepare($sql);
        if($stmt->execute()){
            return [
                "data"=>$stmt->fetchAll(),
                "status"=>"success"
            ];
        }else{
            return [
                "status"=>"failed"
            ];
        }
    }
}