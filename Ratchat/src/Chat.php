<?php

namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $chatList = [];
    protected $conn;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $dbhost = '140.127.49.168';
        $dbport = '5432';
        $dbuser = 'mil';
        $dbpasswd = '7172930';
        $dbname = 'mil';
        
        $dsn = "pgsql:host=" . $dbhost . ";port=" . $dbport . ";dbname=" . $dbname;
        try {

            $conn = new \PDO($dsn, $dbuser, $dbpasswd);
            // $conn->exec("SET CHARACTER SET utf8");
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            //echo "Connected Successfully";
            $this->conn = $conn;
        } catch (\PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    #load
    public function load()
    {
        $client_count = count($this->clients);

        // echo "\n\n\n".'Serving to '.$client_count.' clients. '.time();
        // $start = $istart = microtime(true);

        // $threads = array();
        // foreach( $this->clients as $key => $client ){       

        //     // HANDLE CLIENT

        //     // This works just fine, the only problem is that if I have lets say 50 simultaneous users, the people near the end of the clients array will have to wait till the other users have been processed. This is not desirable
        //     $client->send(json_encode(['foo'=>'bar']));
        // }
        // echo (json_encode($this->chatList));    
        $result = $this->conn->pgsqlGetNotify(\PDO::FETCH_ASSOC, 100);
        if ($result) {
            foreach ($result as $key => $value) {
                if($key=='message'){
                    foreach ($this->chatList as $chatID => $clients) {
                        if($value == $chatID){
                            foreach ($clients as $key => $from) {
                                $from->send(json_encode(["type"=>"chat","data"=>$result]));
                                // else{
                                    // 	return ["status"=>"failed"];
                                    // }
                            }
                        }
                    }
                }
            }
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $numRecv = count($this->clients) - 1;
        echo sprintf(
            'Connection %d sending message "%s" to %d other connection%s' . "\n",
            $from->resourceId,
            $msg,
            $numRecv,
            $numRecv == 1 ? '' : 's'
        );
        $chatList = $this->chatList;
        $msg = json_decode($msg);
        foreach ($this->chatList as $key => $value) {
            foreach ($value as $iterator => $datas) {
                if ($datas->resourceId == $from->resourceId) {
                    unset($this->chatList[$key][$iterator]);
                }
            }
        }
        foreach ($msg as $key => $chatID) {
            if ($chatID != -1) {
                if (empty($chatList[$chatID])) {
                    $chatList[$chatID] = [];
                    $this->conn->exec("LISTEN \"{$chatID}\"");
                }
                if (!in_array($from, $chatList[$chatID])) {
                    array_push($chatList[$chatID], $from);
                }
            }
        }
        $this->chatList = $chatList;
        // foreach ($this->clients as $client) {
        //     if ($from !== $client) {
        //         // The sender is not the receiver, send to each client connected
        //         $client->send($msg);
        //     }
        // }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        foreach ($this->chatList as $key => $value) {
            foreach ($value as $iterator => $datas) {
                if ($datas->resourceId == $conn->resourceId) {
                    // var_dump("success");
                    array_splice($this->chatList, $iterator);
                }
            }
        }
        // if ($conn->resourceId == $this->clients->resourceId) {
        //     echo "resourceId is same.";
        // }
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}
