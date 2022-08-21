<?php

use \Psr\Container\ContainerInterface;

class Setting
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

    public function upload($clientFileName, $fileName)
    {
        $sql = "INSERT INTO setting.file(
            \"ClientName\", \"FileName\", upload_time)
            VALUES (:clientFileName , :fileName , NOW());
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':clientFileName', $clientFileName, PDO::PARAM_STR);
        $stmt->bindValue(':fileName', $fileName, PDO::PARAM_STR);
        $stmt->execute();
        $id = $this->db->lastInsertId();
        return $id;
    }

    function getFileById($data)
    {
        $sql = "SELECT \"FileName\"
        FROM setting.file
        WHERE file.id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id',$data['id']);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getPicture($data)
    {
        $base64 = "/Setting/file/{$data['id']}";
        $ack = array(
            'picture' => $base64
        );
        return $ack;
    }

    function http_response($url, $status = null, $wait = 3)
    {
        $time = microtime(true);
        $expire = $time + $wait;

        // we fork the process so we don't have to wait for a timeout
        // $pid = pcntl_fork();
        // if ($pid == -1) {
        //     die('could not fork');
        // } else if ($pid) {
        // we are the parent
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // curl_setopt($ch, CURLOPT_HEADER, false);
        // curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // var_dump($httpCode);
        return ($head);

        //     if (!$head) {

        //         return $head;
        //     }
        //     if ($status === null) {
        //         if ($httpCode < 400) {
        //             return TRUE;
        //         } else {
        //             return $head;
        //         }
        //     } elseif ($status == $httpCode) {
        //         return TRUE;
        //     }

        //     return $head;
        //     // pcntl_wait($status); //Protect against Zombie children
        // // } else {
        // //     // we are the child
        // //     while (microtime(true) < $expire) {
        // //         sleep(0.5);
        // //     }
        // //     return FALSE;
        // // }
    }
}
