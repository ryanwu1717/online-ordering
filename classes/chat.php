<?php

use Slim\Http\UploadedFile;
use \Psr\Container\ContainerInterface;

class Chat
{
    var $conn;
    var $sesssionID;
    var $change = false;
    var $firstCheck = false;
    function __construct($db)
    {
        $this->conn = $db;
        $this->sessionID = $_SESSION['id'];
        session_write_close();
    }

    function getSaveChat($chatID)
    {
        $UID = $_SESSION['id'];
        if(!array_key_exists('chat',$_SESSION)){
            return [];
        }
        return @$_SESSION['chat'][$UID][$chatID];
    }



    function init()
    {
        $class = $this->getClass();
        $chatroom = $this->getChatroomN();
        $notification = $this->getNotificationNum();
        $starNum = $this->getStar('num');
        $star = array(
            'num' => $starNum,
            'info' => '',
            'chatID' => '-1'
        );


        // unset($chatroom[0]);
        // $chat = $this->getChat();
        $ack = array(
            'status' => 'success',
            'notification' => $notification,
            'star' => $star,
            'class' => $class,
            'chatroom' => $chatroom,
            'chat' => array(),
            'comment' => array(),
            'heart' => array(),
            'delete' => array(),
            'chat' => array(),
            'readCount' => array(),
            'result' => array(
                'class' => array(),
                'chatroom' => array(),
                'chat' => array(
                    'chatID' => 0,
                    'comchatID' => 0,
                    'count' => -1,
                    'new' => array()
                ),
                'readCount' => array(),
            )
        );
        return $ack;
    }
    function routine($data, $chatID)
    {
        $sleepRoutine = 1000000;
        $start = new DateTime('NOW');
        $now = new DateTime('NOW');
        // while ($now->getTimestamp() - $start->getTimestamp() < 45 && !$this->change) {
        //     if ($this->firstCheck) {
        //         usleep($sleepRoutine);
        //         $sleepRoutine += 1000000;
        //         if ($sleepRoutine > 5000000) {
        //             $sleepRoutine = 5000000;
        //         }
        //     }
        // Check for connection abort
        if (connection_aborted() != 0) {
            die();
        }
        $this->firstCheck = true;
        $class = $this->getClass();
        $notification = $this->getNotificationNum();
        $starNum = $this->getStar('num');
        $star = $this->getStar($chatID);
        $newstar = array(
            'num' => $starNum,
            'info' => $star,
            'chatID' => $chatID
        );

        // unset($class[0]);
        // $new = $class[0];
        // $new['id'] = 3;
        // array_push($class,$new);
        // $class[0]['id']=14;
        $result = array();
        $result['notification'] = $this->checkNotification($data['notification'], $notification);
        $result['star'] = $this->checkStar($data['star'], $newstar, $chatID);
        $result['class'] = $this->checkClass($data['class'], $class);
        //$chatroom = $this->getChatroomN();
        //$result['chatroom'] = $this->checkChatroom($data['chatroom'], $chatroom);
        // if ($chatID == $data['result']['chat']['chatID']) {
            $chatroom = $this->getChatroomN();
            $result['chatroom'] = $this->checkChatroom($data['chatroom'], $chatroom);
        // } else {
        //     $chatroom = $data['chatroom'];
        //     $result['chatroom'] = $data['result']['chatroom'];
        // }
        // unset($chatroom[0]);
        // $new = $chatroom[0];
        // $chatroom[0]['chatID'] = 99;
        // $chatroom[0]['classId'] = null;
        // array_push($chatroom,$new);
        // $chat = $this->getChat();
        // var_dump($chat);
        $chat = $this->getChat($chatID);
        $result['chat'] = $this->checkChat($data, $chat, $chatID);
        $readCount = $this->getReadCountN(array('data' => json_encode(array("chatID" => $chatID))));
        $result['readCount'] = $this->checkReadCount($data['readCount'], $readCount);
        $commentNum = $this->getCommentNum($chatID);
        $comment = array(
            'chatID' => $chatID,
            'comment' => $commentNum
        );
        $result['comment'] = $this->checkComment($data['comment'], $commentNum, $chatID);
        $clickHeart = $this->getChatHeart($chatID);
        $heartNum = $this->getChatHeartNum($chatID);
        $heart = array(
            'chatID' => $chatID,
            'num' => $heartNum,
            'click' => $clickHeart
        );
        $result['heartNum'] = $this->checkHeartNum($data['heart'], $heart);
        $result['heartClick'] = $this->checkHeartClick($data['heart'], $heart);
        $deleteMes = $this->getDelete($chatID);
        $otherDelete = $this->getOtherDelete($chatID);
        $delete = array(
            'chatID' => $chatID,
            'delete' => $deleteMes,
            'other' => $otherDelete
        );
        $result['delete'] = $this->checkDelete($data['delete'], $delete);

        $tmpchat = array_slice($chat, -10, 10);

        $now = new DateTime('NOW');
        // }

        $ack = array(
            'status' => 'success',
            'notification' => $notification,
            'star' => $newstar,
            'class' => $class,
            'comment' => $comment,
            'heart' => $heart,
            'delete' => $delete,
            'chatroom' => $chatroom,
            'chat' => $chat,
            'tmpchat' => $tmpchat,
            'readCount' => $readCount,
            'result' => $result
        );
        return $ack;
    }

    function checkDelete($old, $new)
    {
        $map = $mapother = $out = array();
        $out['new'] = array();
        // var_dump($old['chatID'],$new['chatID']);
        if(!array_key_exists('chatID',$old)){
            // var_dump('inin');
            $out['new'] = $new;
            $this->change = true;
            return $out;
        }
        if ($new['chatID'] == $old['chatID']) {
            foreach ($old['delete'] as $val) {
                $map[$val['id']]['type'] = 1;
                $map[$val['id']]['data'] = $val;
            }
            foreach ($new['delete'] as $val) {
                if (isset($map[$val['id']])) {
                    // if($map[$val['id']]['data']['count']!=$val['count']) {
                    // 	$map[$val['id']]['type'] = 3;
                    // 	$map[$val['id']]['data'] = $val;
                    // 	$this->change=true;
                    // } else {
                    $map[$val['id']]['type'] = 0;
                    $map[$val['id']]['data'] = $val;
                    // }
                } else {
                    $map[$val['id']]['type'] = 2;
                    $map[$val['id']]['data'] = $val;
                    $this->change = true;
                }
                foreach ($map as $val => $ok) if ($ok['type'] == 2) $out['new'][] = $ok['data'];
            }
            foreach ($old['other'] as $val) {
                $mapother[$val['id']]['type'] = 1;
                $mapother[$val['id']]['data'] = $val;
            }
            foreach ($new['other'] as $val) {
                if (isset($mapother[$val['id']])) {
                    // if($map[$val['id']]['data']['count']!=$val['count']) {
                    // 	$map[$val['id']]['type'] = 3;
                    // 	$map[$val['id']]['data'] = $val;
                    // 	$this->change=true;
                    // } else {
                    $mapother[$val['id']]['type'] = 0;
                    $mapother[$val['id']]['data'] = $val;
                    // }
                } else {
                    $mapother[$val['id']]['type'] = 2;
                    $mapother[$val['id']]['data'] = $val;
                    $this->change = true;
                }
                foreach ($mapother as $val => $ok) if ($ok['type'] == 2) $out['newOther'][] = $ok['data'];
            }
        } else {
            $out['new'] = $new['delete'];
            $out['newOther'] = $new['other'];
            $this->change = true;
        }
        return $out;
    }


    function checkHeartNum($old, $new)
    {
        $map = $out = array();
        $out['delete'] = $out['new'] = $out['change'] = array();
        if(!array_key_exists('chatID',$old)){
            // var_dump('inin');
            $out['new'] = $new;
            $this->change = true;
            return $out;
        }
        if ($new['chatID'] == $old['chatID']) {
            // var_dump($old);
            // var_dump($new);
            foreach ($old['num'] as $val) {
                $map[$val['sentTime']]['type'] = 1;
                $map[$val['sentTime']]['data'] = $val;
            }

            foreach ($new['num'] as $val) {
                if (isset($map[$val['sentTime']])) {
                    if ($map[$val['sentTime']]['data']['count'] != $val['count']) {
                        $map[$val['sentTime']]['type'] = 3;
                        $map[$val['sentTime']]['data'] = $val;
                        $this->change = true;
                    } else {
                        $map[$val['sentTime']]['type'] = 0;
                        $map[$val['sentTime']]['data'] = $val;
                    }
                } else {
                    $map[$val['sentTime']]['type'] = 2;
                    $map[$val['sentTime']]['data'] = $val;
                    $this->change = true;
                }
            }

            foreach ($map as $val => $ok) if ($ok['type'] == 1) {
                $out['delete'][] = $ok['data'];
                $this->change = true;
            } else if ($ok['type'] == 2) $out['new'][] = $ok['data'];
            else if ($ok['type'] == 3) $out['change'][] = $ok['data'];
        } else {
            $out['new'] = $new['num'];
            $this->change = true;
        }
        return $out;
    }
    function checkHeartClick($old, $new)
    {
        $map = $out = array();
        $out['delete'] = $out['new'] = array();
        if(!array_key_exists('chatID',$old)){
            // var_dump('inin');
            $out['new'] = $new;
            $this->change = true;
            return $out;
        }
        if ($new['chatID'] == $old['chatID']) {
            // var_dump($old);
            // var_dump($new);
            foreach ($old['click'] as $val) {
                $map[$val['id']]['type'] = 1;
                $map[$val['id']]['data'] = $val;
            }

            foreach ($new['click'] as $val) {
                if (isset($map[$val['id']])) {


                    $map[$val['id']]['type'] = 0;
                    $map[$val['id']]['data'] = $val;
                } else {
                    $map[$val['id']]['type'] = 2;
                    $map[$val['id']]['data'] = $val;
                    $this->change = true;
                }
            }

            foreach ($map as $val => $ok) if ($ok['type'] == 1) {
                $out['delete'][] = $ok['data'];
                $this->change = true;
            } else if ($ok['type'] == 2) $out['new'][] = $ok['data'];
        } else {
            $out['new'] = $new['click'];
            $this->change = true;
        }
        return $out;
    }

    function checkComment($old, $new, $chatID)
    {
        $map = $out = array();
        $out['delete'] = $out['new'] = $out['change'] = array();
        if(!array_key_exists('chatID',$old)){
            // var_dump('inin');
            $out['new'] = $new;
            $this->change = true;
            return $out;
        }
        if ($chatID == $old['chatID']) {
            foreach ($old['comment'] as $val) {
                $map[$val['id']]['type'] = 1;
                $map[$val['id']]['data'] = $val;
            }
            foreach ($new as $val) {
                if (isset($map[$val['id']])) {
                    if ($map[$val['id']]['data']['count'] != $val['count']) {
                        $map[$val['id']]['type'] = 3;
                        $map[$val['id']]['data'] = $val;
                        $this->change = true;
                    } else {
                        $map[$val['id']]['type'] = 0;
                        $map[$val['id']]['data'] = $val;
                    }
                } else {
                    $map[$val['id']]['type'] = 2;
                    $map[$val['id']]['data'] = $val;
                    $this->change = true;
                }
            }

            foreach ($map as $val => $ok) if ($ok['type'] == 1) {
                $out['delete'][] = $ok['data'];
                $this->change = true;
            } else if ($ok['type'] == 2) $out['new'][] = $ok['data'];
            else if ($ok['type'] == 3) $out['change'][] = $ok['data'];
        } else {
            // var_dump('inin');
            $out['new'] = $new;
            $this->change = true;
        }
        return $out;
    }

    function getStar($type = null)
    {
        if (is_null($type)) {
            $sql = 'SELECT star.id, star."UID", star."chatID", star."sentTime" AS "fullsendTime", star."detail",to_char( star."sentTime",\'MON DD , HH24:MI\' )as "sentTime",chatroomInfo."chatName"
                FROM staff.star as star
                LEFT JOIN ( SELECT *
                            FROM staff_chat."chatroomInfo") AS chatroomInfo on chatroomInfo."chatID" = star."chatID"
                WHERE "UID"= :UID
                order by "fullsendTime"desc;';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        } else if ($type == 'num') {
            $sql = 'SELECT count(id)
                    FROM staff.star
                    WHERE "UID" = :UID;';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        } else {
            $sql = 'SELECT "sentTime",id
                    FROM staff.star
                    WHERE "UID" = :UID AND "chatID" = :chatID;';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
            $sth->bindParam(':chatID', $type, PDO::PARAM_STR);
        }

        $sth->execute();
        $row = $sth->fetchAll();
        // $ack = array(
        // 	'status'=>'success',
        // );
        return $row;
    }



    function deleteStar()
    {
        $_POST = json_decode($_POST['data'], true);

        $sql = 'DELETE FROM staff.star
                WHERE "UID" =:UID  AND "chatID"=:chatID AND "sentTime"=:sentTime;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->bindParam(':chatID', $_POST['chatID'], PDO::PARAM_INT);
        $sth->bindParam(':sentTime', $_POST['time'], PDO::PARAM_STR);
        $sth->execute();
        // $row = $sth->fetchAll();
        $ack = array(
            'status' => 'success',
        );
        return $ack;
    }

    function getOtherDelete($chatID)
    {
        $sql = 'SELECT id, "chatID", "UID", "sentTime",to_char( "sentTime",\'MON DD , HH24:MI\' )as "showTime"
                FROM staff_chat."chatDelete"
                WHERE "chatID"=:chatID AND  "UID" != :UID;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->bindParam(':chatID', $chatID, PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row;
    }


    function getDelete($chatID)
    {
        $sql = 'SELECT id, "chatID", "UID", "sentTime",to_char( "sentTime",\'MON DD , HH24:MI\' )as "showTime"
                FROM staff_chat."chatDelete"
                WHERE "chatID"=:chatID AND  "UID"=:UID;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->bindParam(':chatID', $chatID, PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row;
    }

    function addDelete()
    {
        $_POST = json_decode($_POST['data'], true);
        // var_dump($_POST);
        $sql = 'INSERT INTO staff_chat."chatDelete"("chatID", "UID", "sentTime")
                VALUES (:chatID, :UID, :sentTime);';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':chatID', $_POST['chatID'], PDO::PARAM_STR);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->bindParam(':sentTime', $_POST['time'], PDO::PARAM_STR);
        $sth->execute();
        return 'success';
    }


    function addStar()
    {
        $_POST = json_decode($_POST['data'], true);

        $sql = 'INSERT INTO staff.star(
                "UID", "chatID", "sentTime", detail)
                VALUES (:UID, :chatID, :sentTime, :detail)';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->bindParam(':chatID', $_POST['chatID'], PDO::PARAM_INT);
        $sth->bindParam(':sentTime', $_POST['time'], PDO::PARAM_STR);
        $sth->bindParam(':detail', $_POST['content'], PDO::PARAM_STR);
        $sth->execute();
        // $row = $sth->fetchAll();
        $ack = array(
            'status' => 'successs',
        );
        return $ack;
    }
    function readNotification($id)
    {
        $sql = 'UPDATE staff.notification
                SET unread=false
                WHERE id=:id;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':id', $id, PDO::PARAM_STR);
        $sth->execute();
        // $row = $sth->fetchAll();
        $ack = array(
            'status' => 'successs',
        );
        return $ack;
    }
    function checkStar($old, $new, $chatID)
    {
        // var_dump($old[0][count]);
        // var_dump($new[0][count]);
        $map = $out = array();

        // var_dump($old);
        // var_dump($new);
        // var_dump($old['chatID']);
        // var_dump($chatID);
        $out['delete'] = $out['new'] = $out['change'] = array();
        if ($old['num'][0]['count'] != $new['num'][0]['count']) {
            $this->change = true;
            $out['change']['num'] = $new['num'][0]['count'];
        }

        if ($chatID == $old['chatID'] && !empty($old['info'])) {
            // var_dump($old['chatID']);
            foreach ($old['info'] as $val) {
                $map[$val['id']]['type'] = 1;
                $map[$val['id']]['data'] = $val;
            }

            foreach ($new['info'] as $val) {
                if (isset($map[$val['id']])) {

                    $map[$val['id']]['type'] = 0;
                    $map[$val['id']]['data'] = $val;
                } else {
                    $map[$val['id']]['type'] = 2;
                    $map[$val['id']]['data'] = $val;
                    $this->change = true;
                }
            }
            // var_dump($map);
            // $this->change=true;
            foreach ($map as $val => $ok) if ($ok['type'] == 1) {
                $out['delete'][] = $ok['data'];
                $this->change = true;
            } else if ($ok['type'] == 2) $out['new'][] = $ok['data'];
        } else {
            // var_dump('inin');
            $out['new'] = $new['info'];
            $this->change = true;
        }


        return $out;
    }


    function getNotificationNum()
    {
        $sql = 'SELECT COUNT (id)
                FROM staff.notification
                WHERE unread = true AND "UID"=:UID ;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row;
    }
    function commentTag()
    {
        $_POST = json_decode($_POST['data'], true);
        $sql = 'SELECT *
                FROM staff.notification
                WHERE  "UID"=:UID AND type = \'comment\' AND sendtime = :sendtime AND "chatID" = :chatID
                ;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $_POST['UID'], PDO::PARAM_STR);
        $sth->bindParam(':sendtime', $_POST['senttime'], PDO::PARAM_STR);
        $sth->bindParam(':chatID', $_POST['chatID'], PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetchAll();
        if (count($row) == 0) {
            $sql = 'INSERT INTO staff.notification("UID",detail, unread, "chatID", sendtime, type, "tagTime")
                VALUES (:UID, :detail, \'true\', :chatID, :senttime, \'comment\',NOW() )
                ;';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':UID', $_POST['UID'], PDO::PARAM_STR);
            $sth->bindParam(':senttime', $_POST['senttime'], PDO::PARAM_STR);
            $sth->bindParam(':chatID', $_POST['chatID'], PDO::PARAM_INT);
            $sth->bindParam(':detail', $_POST['detail'], PDO::PARAM_STR);
            $sth->execute();
            $ack = array('status' => 'success');
            return $ack;
        } else {
            $sql = 'UPDATE staff.notification
                    SET detail = :detail, unread = \'true\', "tagTime"  = NOW()
                    WHERE "UID" = :UID AND "chatID" = :chatID AND sendtime = :senttime
                ;';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':UID', $_POST['UID'], PDO::PARAM_STR);
            $sth->bindParam(':senttime', $_POST['senttime'], PDO::PARAM_STR);
            $sth->bindParam(':chatID', $_POST['chatID'], PDO::PARAM_INT);
            $sth->bindParam(':detail', $_POST['detail'], PDO::PARAM_STR);
            $sth->execute();
            $ack = array('status' => 'update');
            return $ack;
        }
    }

    function getCommentMember($commentID, $orgSender)
    {
        // $data = json_decode($body['data'],true);

        $sql = '
            SELECT  "UID",  "commentID"
            FROM staff_chat."commentChat"
            WHERE "commentID" = :commentID AND "UID" != :UID
            GROUP BY  "UID", "commentID"';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':commentID', $commentID, PDO::PARAM_STR);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->execute();
        $peoplerow = $sth->fetchAll();

        $num = count($peoplerow);


        $sql = '
                SELECT  staff_name,staff_id
                FROM staff."staff"
                WHERE "staff_id" = :staff_id ';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':staff_id', $_SESSION['id'], PDO::PARAM_STR);
        $sth->execute();
        $senderrow = $sth->fetchAll();
        if ($num == 0) {
            if ($senderrow[0]['staff_id'] != $orgSender) {
                $ack = array(
                    'status' => 'no',
                    'text' => $senderrow[0]['staff_name'] . '回復了留言'
                );
            } else {
                $ack = array(
                    'status' => 'nothing',
                    'sender' => $senderrow[0]['staff_id'],
                    'next' => $_SESSION['id']
                );
            }
        } else {
            $sql = '
                SELECT "commentChat"."UID", "commentChat"."sentTime" ,staff.staff_name as name
                FROM staff_chat."commentChat" 
                LEFT JOIN (
                    SELECT staff_name,staff_id 
                    FROM staff.staff 
                )AS staff on staff.staff_id = "commentChat" ."UID"
                WHERE "commentID" = :commentID AND "UID" != :UID
                ORDER BY "commentChat"."sentTime" DESC LIMIT 1
            ';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':commentID', $commentID, PDO::PARAM_STR);
            $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
            $sth->execute();
            $tmprow = $sth->fetchAll();
            if ($num <= 1) {
                $text = $senderrow[0]['staff_name'] . '回復了留言';
            } else {
                $text = $senderrow[0]['staff_name'] . '以及其他' . ($num - 1) . '人
                回復了留言';
            }
            if ($senderrow[0]['staff_id'] == $_SESSION['id']) {
                $textSender = 'dontSend';
            } else {
                $textSender =  $senderrow[0]['staff_name'] . '以及其他' . $num . '人回復了留言';
            }

            $ack = array(
                'status' => 'success',
                'people' => $peoplerow,
                'num' =>  $num,
                'text' => $text,
                'textSender' => $textSender
            );
        }

        return $ack;
    }

    function getSenter($commentID)
    {
        $sql = 'SELECT "commentInfo"."chatID", "commentInfo"."sentTIme", "commentInfo".id,"chatContent"."UID"
                FROM staff_chat."commentInfo"
                LEFT JOIN(
                    SELECT  "UID", "sentTime", "chatID"
                    FROM staff_chat."chatContent"
                )as "chatContent" on "chatContent"."chatID" = "commentInfo"."chatID" AND "chatContent"."sentTime" = "commentInfo"."sentTIme"
                WHERE "commentInfo".id = :commentID';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':commentID', $commentID, PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row[0];
    }


    function getNotification()
    {
        $sql = 'SELECT notification.detail, notification.unread, to_char( notification."sendtime",\'MON DD , HH24:MI\' )as "sendtime", notification.sendtime AS "fullsendTime",notification.type, chatroomInfo."chatName",chatroomInfo."chatID",notification.id,notification."tagTime",
                    CASE notification.type 
                        WHEN \'tag\' THEN notification.sendtime
                        WHEN \'comment\' THEN notification."tagTime"
                    END as "fullTime"
                FROM staff.notification AS notification
                LEFT JOIN ( SELECT *
                        FROM staff_chat."chatroomInfo") AS chatroomInfo
                        on chatroomInfo."chatID" = CAST(notification."chatID" AS INT)
                WHERE "UID"= :UID
                order by "fullTime" desc;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row;
    }



    function checkNotification($old, $new)
    {
        // var_dump($old[0][count]);
        // var_dump($new[0][count]);
        $out = array();
        $out['change'] = array();
        if ($old[0][0] != $new[0][0]) {
            $this->change = true;
            $out['change'] = $new[0][0];
        }
        return $out;
    }
    function tag()
    {
        $_POST = json_decode($_POST['data'], true);
        // var_dump($_POST);
        $sql = "SELECT staff_name FROM staff.staff WHERE staff_id = :staff_id;";
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':staff_id', $_SESSION['id'], PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetchColumn(0);
        var_dump($row);
        $tmpName = '你被 ' . $row . ' 標註在一則訊息';

        try {
            $sql = 'INSERT INTO staff.notification("UID","detail", sendtime, unread, "chatID","type","tagTime")VALUES (:UID,:message,:tmpFullTime,\'true\',:chatID,\'tag\',NOW());';

            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':UID', $_POST['id'], PDO::PARAM_STR);
            $sth->bindParam(':message', $tmpName, PDO::PARAM_STR);

            $sth->bindParam(':tmpFullTime', $_POST['tmpTime'], PDO::PARAM_STR);
            $sth->bindParam(':chatID', $_POST['chatID'], PDO::PARAM_INT);
            // $sth->bindParam(':msgID',$_POST['msgID'],PDO::PARAM_STR);
            $sth->execute();
            // $row = $sth->fetchAll();
            $ack = array(
                'status' => 'success'

            );
        } catch (PDOException $e) {
            $ack = array(
                'status' => 'failed',
                'message' => $e
            );
        }

        return $ack;
    }
    var $readCountState = array(
        'unchange' => 0,
        'delete' => 1,
        'new' => 2,
        'changeName' => 3
    );
    function checkReadCount($a, $b)
    {
        $map = $out = array();
        $out['delete'] = $out['new'] = $out['change'] = array();
        foreach ($a as $val) {
            $map[$val['sentTime']]['type'] = $this->readCountState['delete'];
            $map[$val['sentTime']]['data'] = $val;
        }
        foreach ($b as $val) {
            if (isset($map[$val['sentTime']])) {
                if ($map[$val['sentTime']]['data']['sum'] != $val['sum']) {
                    $map[$val['sentTime']]['type'] = $this->readCountState['changeName'];
                    $map[$val['sentTime']]['data'] = $val;
                    $this->change = true;
                } else {
                    $map[$val['sentTime']]['type'] = $this->readCountState['unchange'];
                    $map[$val['sentTime']]['data'] = $val;
                }
            } else {
                $map[$val['sentTime']]['type'] = $this->readCountState['new'];
                $map[$val['sentTime']]['data'] = $val;
                $this->change = true;
            }
        }
        foreach ($map as $val => $ok) if ($ok['type'] == 1) {
            $out['delete'][] = $ok['data'];
            $this->change = true;
        } else if ($ok['type'] == 2) $out['new'][] = $ok['data'];
        else if ($ok['type'] == 3) $out['change'][] = $ok['data'];
        return $out;
    }
    function getReadCountN($body)
    {
        $data = json_decode($body['data'], true);
        $UID = $_SESSION['id'];
        $sql = '
            SELECT "sentTime" as "sentTime",SUM(count(*)) OVER (ORDER BY "sentTime" DESC)
                FROM(
                SELECT "chatHistory"."UID", MAX("chatContent"."sentTime") AS "sentTime"
                FROM staff_chat."chatHistory"
                LEFT JOIN staff_chat."chatContent" ON "chatHistory"."time" > "chatContent"."sentTime" AND "chatContent"."chatID" = :chatID
                WHERE "chatHistory"."chatID" = :chatID AND "chatHistory"."UID" != :UID
                GROUP BY "chatHistory"."UID"
            )AS A
            WHERE "sentTime" IS NOT NULL
            GROUP BY "sentTime"
            ORDER BY "sentTime" ASC;
        ';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
        $sth->bindParam(':chatID', $data['chatID'], PDO::PARAM_INT);
        $sth->execute();

        $row = $sth->fetchAll();
        return $row;
    }
    var $classState = array(
        'unchange' => 0,
        'delete' => 1,
        'new' => 2,
        'changeName' => 3,
        'changeNum' => 4
    );
    function checkClass($a, $b)
    {
        $map = $out = array();
        $out['delete'] = $out['new'] = $out['change'] = $out['changeNum'] = array();
        foreach ($a as $val) {
            $map[$val['id']]['type'] = $this->classState['delete'];
            $map[$val['id']]['data'] = $val;
        }
        foreach ($b as $val) {
            if (isset($map[$val['id']])) {
                if ($map[$val['id']]['data']['name'] != $val['name']) {
                    $map[$val['id']]['type'] = $this->classState['changeName'];
                    $map[$val['id']]['data'] = $val;
                    $this->change = true;
                } else if ($map[$val['id']]['data']['sum'] != $val['sum']) {
                    // var_dump(val['sum']);
                    $map[$val['id']]['type'] = $this->classState['changeNum'];
                    $map[$val['id']]['data'] = $val;
                    $this->change = true;
                } else {
                    $map[$val['id']]['type'] = $this->classState['unchange'];
                    $map[$val['id']]['data'] = $val;
                }
            } else {
                $map[$val['id']]['type'] = $this->classState['new'];
                $map[$val['id']]['data'] = $val;
                $this->change = true;
            }
        }
        foreach ($map as $val => $ok) if ($ok['type'] == 1) {
            $out['delete'][] = $ok['data'];
            $this->change = true;
        } else if ($ok['type'] == 2) $out['new'][] = $ok['data'];
        else if ($ok['type'] == 3) $out['change'][] = $ok['data'];
        else if ($ok['type'] == 4) $out['changeNum'][] = $ok['data'];
        return $out;
    }
    var $chatroomState = array(
        'unchange' => 0,
        'delete' => 1,
        'new' => 2,
        'changeName' => 3
    );
    function checkChatroom($a, $b)
    {
        $map = $out = array();
        $out['delete'] = $out['new'] = $out['change'] = array();
        foreach ($a as $val) {
            $map[$val['chatID']]['type'] = 1;
            $map[$val['chatID']]['data'] = $val;
        }
        foreach ($b as $val) {
            if (isset($map[$val['chatID']])) {
                if ($map[$val['chatID']]['data']['chatName'] != $val['chatName'] || $map[$val['chatID']]['data']['classID'] != $val['classID'] || $map[$val['chatID']]['data']['countContent'] != $val['countContent'] || $map[$val['chatID']]['data']['CountUnread'] != $val['CountUnread']) {
                    $map[$val['chatID']]['type'] = 3;
                    $map[$val['chatID']]['data'] = $val;
                    $this->change = true;
                } else {
                    $map[$val['chatID']]['type'] = 0;
                    $map[$val['chatID']]['data'] = $val;
                }
            } else {
                $map[$val['chatID']]['type'] = 2;
                $map[$val['chatID']]['data'] = $val;
                $this->change = true;
            }
        }
        foreach ($map as $val => $ok) if ($ok['type'] == 1) {
            $out['delete'][] = $ok['data'];
            $this->change = true;
        } else if ($ok['type'] == 2) $out['new'][] = $ok['data'];
        else if ($ok['type'] == 3) $out['change'][] = $ok['data'];
        return $out;
    }
    function checkChat($data, $chat, $chatID)
    {
        $new = array();
        if ($chatID == $data['result']['chat']['chatID']) {
            if (count($chat) - count($data['chat']) != 0) {
                for ($i = count($chat) - 1; $i >= 0; $i -= 1) {
                    array_push($new, $chat[$i]);
                }
                $this->change = true;
            }
        } else {
            $new = $chat;
            $this->change = true;
        }
        $result = array(
            'chatID' => $chatID,
            'comchatID' => $chatID == $data['result']['chat']['chatID'],
            'count' => (count($chat) - count($data['chat'])),
            'new' => $new
        );
        return $result;
    }
    function getClass($classID = null)
    {
        $staff_id = $_SESSION['id'];
        if (is_null($classID)) {
            $sql = 'SELECT  id, name ,sum("sum") as sum
                FROM(
                    SELECT  id, name ,0 as sum
                    FROM staff_chat."chatClass"
                    WHERE "UID" = :id
                    UNION(
                        SELECT 0,\'未命名議題\',0 as sum
                    )
                    union(
                        SELECT  
                            (case WHEN  "allclass".id IS NULL  then 0 ELSE "allclass".id END) AS id,
                            (case WHEN  "allclass".name IS NULL  then \'未命名議題\' ELSE "allclass".name END)AS name,
                            SUM("tmpClassify"."CountUnread")
                        FROM (
                            SELECT "countUnread"."chatID","countUnread"."UID",COUNT("countUnread"."c")as "CountUnread","cClassify"."classID"
                            FROM(
                                SELECT "chatHistory"."chatID",  "chatHistory"."UID",(case when "time"<"sentTime" then \'1\' else null end) as "c"
                                FROM staff_chat."chatHistory"
                                join staff_chat."chatContent" on "chatHistory"."chatID"="chatContent"."chatID"
                                where "chatHistory"."UID"=:id and "chatContent"."UID" != :id
                            ) as "countUnread"
                            LEFT JOIN (
                                SELECT "chatClassify"."classID" as "classID", "chatClassify"."chatID", "chatClassify"."UID"
                                FROM staff_chat."chatClassify"
                                where "chatClassify"."UID"=:id
                            )as "cClassify" on "cClassify"."chatID" = "countUnread"."chatID"
                            group by "countUnread"."chatID","countUnread"."UID","cClassify"."classID"
                        )as "tmpClassify"
                        LEFT JOIN(
                            SELECT  name,id
                            FROM staff_chat."chatClass"
                            WHERE "UID" = :id
                        )as "allclass" on "allclass".id = "tmpClassify"."classID"
                        GROUP BY "allclass".id,"allclass".name
                    )ORDER BY  name
                )AS A 
                GROUP BY id,name
                ORDER BY name
            ';
            $statement = $this->conn->prepare($sql);
        } else {
            $sql = '
                SELECT  name
                FROM staff_chat."chatClass"
                WHERE "UID" = :id and id=:classID
                ORDER BY name
            ';
            $statement = $this->conn->prepare($sql);
            $statement->bindParam(':classID', $classID);
        }
        $statement->bindParam(':id', $staff_id);
        $statement->execute();
        $row = $statement->fetchAll();
        return $row;
    }

    function getChatHeart($chatID)
    {
        $sql = 'SELECT id,"sentTime"
                FROM staff_chat."chatHeart"	
                WHERE "chatID" = :chatID AND "UID"=:UID;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row;
    }
    function getChatHeartNum($chatID)
    {
        $sql = 'SELECT "sentTime",count(*)
                FROM staff_chat."chatHeart"	
                WHERE "chatID" = :chatID
                GROUP BY"sentTime";';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row;
    }
    function patchHeart($data = [])
    {
        if(array_key_exists('data',$data))
            $_POST = $data;
        $_POST = json_decode($_POST['data'], true);
        $sql = 'SELECT id, "chatID", "UID", "sentTime"
                FROM staff_chat."chatHeart"
                WHERE "chatID"=:chatID AND "UID"=:UID AND "sentTime"=:sentTime;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->bindParam(':chatID', $_POST['chatID'], PDO::PARAM_INT);
        $sth->bindParam(':sentTime', $_POST['time'], PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetchAll();
        if (count($row) == 0) {
            $sql = 'INSERT INTO staff_chat."chatHeart"("chatID", "UID", "sentTime")
                    VALUES (:chatID, :UID, :sentTime);';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
            $sth->bindParam(':chatID', $_POST['chatID'], PDO::PARAM_INT);
            $sth->bindParam(':sentTime', $_POST['time'], PDO::PARAM_INT);
            $sth->execute();
            $ack = array(
                'status' => 'success',
                'type' => 'add'
            );
            return $ack;
        } else {
            $sql = 'DELETE 
                    FROM staff_chat."chatHeart"
                    WHERE "chatID"=:chatID AND "UID"=:UID AND "sentTime"=:sentTime;';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
            $sth->bindParam(':chatID', $_POST['chatID'], PDO::PARAM_INT);
            $sth->bindParam(':sentTime', $_POST['time'], PDO::PARAM_INT);
            $sth->execute();
            $ack = array(
                'status' => 'success',
                'type' => 'delete'
            );
            return $ack;
        }
    }

    function getChatroomN()
    {
        $sql = 'SELECT "chatResult"."chatID","receiver"."staff_name","chatResult"."chatName",cl.id AS"classID",COALESCE(cl.id,0) AS"classID_new",cl.name AS "className","countContent","outerContent"."UID","outerContent"."sentTime" AS "LastTime1",to_char("sentTime",\'MM-DD\')as "LastTime","outerContent"."content","CountUnread",CASE WHEN "CountUnread" > 0 then \'1\'ELSE\'0\' END AS "Priority"
        FROM (
            SELECT "chatHistory"."chatID","chatClassify"."classID","chatHistory"."time","chatroomInfo"."chatName"
            FROM "staff_chat"."chatHistory"
            LEFT JOIN "staff_chat"."chatClassify" ON "chatClassify"."chatID" = "chatHistory"."chatID"
            LEFT JOIN "staff_chat"."chatroomInfo" ON "chatHistory"."chatID" = "chatroomInfo"."chatID"
            WHERE "chatHistory"."UID" = :UID
        )AS "chatResult"
        LEFT JOIN (SELECT "chatID",count(*) AS "countContent" FROM "staff_chat"."chatContent" GROUP BY "chatID") AS "countChatroom" ON "countChatroom"."chatID" = "chatResult"."chatID"
        LEFT JOIN "staff_chat"."chatContent" AS "outerContent" ON "outerContent"."chatID" = "chatResult"."chatID" AND "outerContent"."sentTime" = (SELECT MAX("sentTime") FROM "staff_chat"."chatContent" AS "innerContent" WHERE "innerContent"."chatID"="chatResult"."chatID")
        LEFT JOIN (
         SELECT "cH3"."chatID","UID" AS "chatToWhom","staff_name"
         FROM(
          SELECT "couUID","chatID","time"
          FROM(
           SELECT "chatID" AS "cID", COUNT("UID")AS "couUID"
           FROM staff_chat."chatHistory"
           group by "chatID"
          ) AS "cUID"
          LEFT JOIN staff_chat."chatHistory" AS "cH2" on "cUID"."cID"="cH2"."chatID" AND "cH2"."UID"= :UID AND "couUID"=2
         )AS "check"
         LEFT join staff_chat."chatHistory" AS "cH3" on "check"."chatID"="cH3"."chatID"
         LEFT JOIN staff.staff ON "UID" = "staff_id"
         where "UID"!= :UID
        )AS "receiver" on "chatResult"."chatID"="receiver"."chatID"
        LEFT JOIN (
         SELECT *
         FROM staff_chat."chatClass"
         LEFT JOIN staff_chat."chatClassify" 
         ON "chatClass".id="chatClassify" ."classID"
         WHERE "chatClassify"."UID"=:UID
        ) as cl on cl."chatID"="chatResult" ."chatID"
        LEFT JOIN (
            SELECT "chatHistory"."chatID",  "chatHistory"."UID",COUNT(case when "time"<"sentTime" then true end)as "CountUnread"
            FROM staff_chat."chatHistory"
            join staff_chat."chatContent" on "chatHistory"."chatID"="chatContent"."chatID"
            where "chatHistory"."UID"=:UID and "chatContent"."UID" != :UID
            group by "chatHistory"."chatID",  "chatHistory"."UID"
        ) as "countUnread" on "chatResult"."chatID"="countUnread"."chatID"
        UNION 
        SELECT -1 AS "SUM",\'-1\' AS "SUM",\'-1\' AS "SUM",-1 AS "SUM",-1 AS "SUM",\'-1\' AS "SUM",SUM("countContent"), \'-1\' AS "SUM",\'1000-01-01 00:00:00\' AS "SUM", \'-1\' AS "SUM", \'-1\' AS "SUM",-1 AS "SUM",\'-1\' AS "SUM"
        FROM (SELECT "staff_chat"."chatHistory"."chatID","countContent"
        FROM "staff_chat"."chatHistory"
        LEFT JOIN (SELECT "chatID",count(*) AS "countContent" FROM "staff_chat"."chatContent" GROUP BY "chatID") AS "countChatroom" ON "countChatroom"."chatID" = "staff_chat"."chatHistory"."chatID"
        WHERE "UID" = :UID)AS "SELECT"
        order by "classID","Priority" desc,"LastTime1"desc 
       ';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row;
    }
    function getChat($chatID)
    {
        $sql = '
                SELECT "content",("sentTime")as "fullsentTime",to_char( "sentTime",\'MON DD HH24:MI:SS\' )as "sentTime","UID","diff","Read",staff_name
                FROM (
                    SELECT "chatContent"."content","chatContent"."sentTime","chatContent"."UID",(CASE "chatContent"."UID" WHEN :UID THEN \'me\' ELSE \'other\' END) as "diff",COALESCE("readCount",0) as "Read",staff_name
                    FROM (
                        SELECT *
                        FROM staff_chat."chatContent"
                        Where "chatID"=:chatID
                    )AS "chatContent"
                    LEFT JOIN (
                        SELECT "content","sentTime","sentFrom",COUNT("UID") as "readCount"
                        FROM (
                                SELECT content, "sentTime", "UID" as "sentFrom","chatID"
                                FROM staff_chat."chatContent"
                                WHERE "chatID"= :chatID
                            )as "display",(
                                SELECT "chatID", "time", "UID"
                                FROM staff_chat."chatHistory"
                                Where "chatID"=:chatID
                            ) as "chatHistory" 
                        Where "UID"!=:UID and "display"."chatID"="chatHistory"."chatID" and "chatHistory"."time">"display"."sentTime"
                        Group by "content","sentTime","sentFrom" 
                    ) as "displayContent" on "chatContent"."content"="displayContent"."content" and "chatContent"."sentTime"="displayContent"."sentTime" and "chatContent"."UID"="displayContent"."sentFrom"
                    LEFT JOIN staff."staff" on staff.staff_id="chatContent"."UID"
                    Where "chatID"=:chatID
                    order by "chatContent"."sentTime" desc 
                ) as "tmpChatContent"
                order by "tmpChatContent"."sentTime" asc
            ';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row;
    }
    function insertClass($classID, $chatID)
    {
        try {
            $sql = 'DELETE 
                    FROM staff_chat."chatClassify"
                    WHERE "chatID"=:chatID 
                            AND EXISTS(SELECT "chatID"
                                FROM staff_chat."chatClassify"
                                WHERE  "chatID"=:chatID AND "UID"=:UID);';
            $statement = $this->conn->prepare($sql);
            $statement->bindParam(':chatID', $chatID);
            $statement->bindParam(':UID', $_SESSION['id']);
            $statement->execute();

            $sql = 'INSERT INTO staff_chat."chatClassify"("classID", "chatID","UID")
                    VALUES (:id, :chatID, :UID);';
            $statement = $this->conn->prepare($sql);
            $statement->bindParam(':id', $classID);
            $statement->bindParam(':chatID', $chatID);
            $statement->bindParam(':UID', $_SESSION['id']);
            $statement->execute();
            $ack = array(
                'status' => 'success',
            );
        } catch (PDOException $e) {
            $ack = array(
                'status' => 'failed',
            );
        }
        // try{	
        // 	$sql ='INSERT INTO staff_chat."chatClassify"("classID", "chatID")
        // 			VALUES (:id, :chatID);';
        // 	$statement = $this->conn->prepare($sql);
        // 	$statement->bindParam(':id',$classId);
        // 	$statement->bindParam(':chatID',$chatID);
        // 	$statement->execute();
        // 	$ack = array(
        // 		'status'=>'success2',
        // 	);	
        // 	return $ack;
        // }catch(PDOException $e){
        // 	$ack = array(
        // 		'status'=>'failed',
        // 	);	
        // }
        return $ack;
    }
    function deleteClass($classID)
    {
        $staff_id = $_SESSION['id'];
        $sql = 'DELETE FROM staff_chat."chatClass"
                WHERE id = :id ';
        $statement = $this->conn->prepare($sql);
        $statement->bindParam(':id', $classID);
        $statement->execute();
        $row = $statement->fetchAll();
        return $row;
    }
    function addClass()
    {
        $staff_id = $_SESSION['id'];
        $_POST = json_decode($_POST['data'], true);
        $sql = 'INSERT INTO staff_chat."chatClass"("UID", name)VALUES (:id, :name);';
        $statement = $this->conn->prepare($sql);
        $statement->bindParam(':id', $staff_id);
        $statement->bindParam(':name', $_POST['name']);
        $statement->execute();
        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }
    function getChatroomTitle($chatID)
    {
        $sql = 'SELECT "chatName" FROM staff_chat."chatHistory" LEFT JOIN staff_chat."chatroomInfo" on "chatroomInfo"."chatID" = "chatHistory"."chatID" WHERE "chatroomInfo"."chatID"=:chatID and "UID" = :UID';
        $sth = $this->conn->prepare($sql);
        $UID = $_SESSION['id'];
        $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
        $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row;
    }
    function getChatroom()
    {
        $sql = 'SELECT "receiverList"."chatID","chatToWhom",to_char("LastTime",\'MM-DD\')as "LastTime","content",case when "chatName" = \'\' then "staff_name" else "chatName" end as "chatName","staff_name","LastTime" as "LastTime1","CountUnread",case when "CountUnread" > 0 then \'1\'else\'0\' end as "Priority"
                    FROM(
                        SELECT "chatWith"."chatID","chatToWhom"
                        FROM(
                            SELECT "chatID", "time", "UID"
                            FROM staff_chat."chatHistory"
                            where "UID"= :UID
                            )as "chatWith" 
                            LEFT JOIN (
                                        SELECT "cH3"."chatID","UID" as "chatToWhom"
                                        FROM(
                                            SELECT "couUID","chatID","time"
                                            FROM(
                                                SELECT "chatID" as "cID", COUNT("UID")as "couUID"
                                                FROM staff_chat."chatHistory"
                                                group by "chatID") as "cUID"
                                                LEFT JOIN staff_chat."chatHistory" as "cH2"
                                                on "cUID"."cID"="cH2"."chatID" AND "cH2"."UID"= :UID AND "couUID"=2)as "check"
                                        LEFT join staff_chat."chatHistory" as "cH3"
                                        on "check"."chatID"="cH3"."chatID"
                                        where "UID"!= :UID
                                        )as "receiver" on "chatWith"."chatID"="receiver"."chatID")as "receiverList"
                            LEFT JOIN (
                                        SELECT "cILT"."chatID","LastTime","content","UID" as "sender"
                                        FROM(
                                            SELECT "chatID",MAX("sentTime")as "LastTime"
                                            FROM staff_chat."chatContent"
                                            Group by "chatID")as "cILT" 
                            LEFT JOIN staff_chat."chatContent" as "cC2" on "cILT"."chatID"="cC2"."chatID" 
                            where "LastTime"="sentTime")as "searchResault" on "receiverList"."chatID"="searchResault"."chatID"	
                            LEFT JOIN staff_chat."chatroomInfo" on "receiverList"."chatID"="chatroomInfo"."chatID"
                            LEFT JOIN staff."staff" on "receiverList"."chatToWhom"=staff."staff"."staff_id"
                            LEFT JOIN (SELECT "chatID","UID",COUNT("c")as "CountUnread"
                                        FROM(SELECT "chatHistory"."chatID",  "chatHistory"."UID",(case when "time"<"sentTime" then \'1\' else null end) as "c"
                                            FROM staff_chat."chatHistory"
                                            join staff_chat."chatContent" on "chatHistory"."chatID"="chatContent"."chatID"
                                            where "chatHistory"."UID"=:UID and "chatContent"."UID" != :UID) as "countUnread"
                                        group by "chatID","UID") as "countUnread" on "receiverList"."chatID"="countUnread"."chatID"
                            order by "Priority" desc, "LastTime1" desc;';
        $sth = $this->conn->prepare($sql);
        $UID = $_SESSION['id'];
        $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row;
    }
    function getMember($chatID)
    {
        $sql = 'SELECT staff_name as name,"UID" as id FROM staff_chat."chatHistory" left join "staff"."staff" on staff.staff_id="chatHistory"."UID" WHERE "chatID"= :chatID;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetchAll();

        return $row;
    }
    function getMemberDepartment($chatID)
    {
        $sql = 'SELECT "departmentName".department_id AS id ,"departmentName".department_name AS name 
                FROM staff.staff AS staff
                    LEFT JOIN staff_information.department AS "departmentName"
                        ON "departmentName".department_id=staff.staff_department
                WHERE staff.staff_id 
                    IN(SELECT "UID" as staff_id FROM staff_chat."chatHistory" left join "staff"."staff" on staff.staff_id="chatHistory"."UID" 
                        WHERE "chatID"= :chatID and staff_delete=false and "seniority_workStatus" =1)

                Group by(id);';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row;
    }
    function getReadCount($body)
    {
        $data = json_decode($body['data'], true);
        $UID = $_SESSION['id'];
        $sql = '
            SELECT "sentTime" as "sentTime",SUM(count(*)) OVER (ORDER BY "sentTime" DESC)
                FROM(
                SELECT "chatHistory"."UID", MAX("chatContent"."sentTime") AS "sentTime"
                FROM staff_chat."chatHistory"
                LEFT JOIN staff_chat."chatContent" ON "chatHistory"."time" > "chatContent"."sentTime" AND "chatContent"."chatID" = :chatID
                WHERE "chatHistory"."chatID" = :chatID AND "chatHistory"."UID" != :UID
                GROUP BY "chatHistory"."UID"
            )AS A
            WHERE "sentTime" IS NOT NULL
            GROUP BY "sentTime"
            ORDER BY "sentTime" ASC;
        ';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
        $sth->bindParam(':chatID', $data['chatID'], PDO::PARAM_INT);
        $sth->execute();

        $row = $sth->fetchAll();
        array_push($row, array('chatID' => $data['chatID']));
        return $row;
    }
    function getReadListNew($body)
    {
        $data = json_decode($body['data'], true);
        $sql = '
            SELECT "staff_name","staff_id","checkread"
            FROM(
                SELECT content, "chatHistory"."UID", "chatHistory"."chatID",case when "time" > "sentTime" then \'true\' else \'false\' end as "checkread"
                FROM staff_chat."chatContent" as "chatContent"
                join staff_chat."chatHistory" as "chatHistory" on "chatContent"."chatID"="chatHistory"."chatID"
                Where content=:whichTalk and "chatHistory"."chatID"=:chatID and "sentTime" = :sentTime
            )as "checkUnread"
            left join staff."staff" as "staff" on "staff"."staff_id"="checkUnread"."UID"
            where "staff_id"!=:UID
            group by"staff_name","staff_id","checkread";
        ';
        $sth = $this->conn->prepare($sql);
        $UID = $_SESSION['id'];
        $sth->bindParam(':sentTime', $data['sentTime'], PDO::PARAM_STR);
        $sth->bindParam(':whichTalk', $data['content'], PDO::PARAM_STR);
        $sth->bindParam(':chatID', $data['chatID'], PDO::PARAM_INT);
        $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
        $sth->execute();

        $row = $sth->fetchAll();
        return $row;
    }
    function getReadList($body)
    {
        $data = json_decode($body['data'], true);
        $sql = '
            SELECT "staff_name","staff_id","checkread"
            FROM(
                SELECT content, "chatHistory"."UID", "chatHistory"."chatID",case when "time" > "sentTime" then \'true\' else \'false\' end as "checkread"
                FROM staff_chat."chatContent" as "chatContent"
                join staff_chat."chatHistory" as "chatHistory" on "chatContent"."chatID"="chatHistory"."chatID"
                Where content=:whichTalk and "chatHistory"."chatID"=:chatID and "sentTime" = :sentTime
            )as "checkUnread"
            left join staff."staff" as "staff" on "staff"."staff_id"="checkUnread"."UID"
            where "staff_id"!=:UID and staff_delete=false and "seniority_workStatus" =1
            group by"staff_name","staff_id","checkread";
        ';
        $sth = $this->conn->prepare($sql);
        $UID = $_SESSION['id'];
        $sth->bindParam(':sentTime', $data['sentTime'], PDO::PARAM_STR);
        $sth->bindParam(':whichTalk', $data['content'], PDO::PARAM_STR);
        $sth->bindParam(':chatID', $data['chatID'], PDO::PARAM_INT);
        $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
        $sth->execute();

        $row = $sth->fetchAll();
        return $row;
    }

    function updateCommentReadTime($commentID)
    { //TODO


        $sql = 'UPDATE staff_chat."commentHistory"
                SET "lasttime"=NOW()
                WHERE "UID"=:UID AND "commentID"=:commentID;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->bindParam(':commentID', $commentID, PDO::PARAM_INT);
        $sth->execute();
        $count = $sth->rowCount();
        if ($count == 0) {
            $sql = 'INSERT INTO staff_chat."commentHistory"("UID",lasttime, "commentID")
                VALUES (:UID, NOW(), :commentID);';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
            $sth->bindParam(':commentID', $commentID, PDO::PARAM_INT);
            $sth->execute();
        }
        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }

    function getCommentID($data)
    {
        $sql = '
            SELECT  "commentInfo".id as "org",
                case when "repostComment"."orgCommentID" IS NULL
                    then "commentInfo".id
                    else "repostComment"."orgCommentID"
                    end  as id
            FROM staff_chat."commentInfo"
            LEFT JOIN (
                SELECT *
                FROM staff_chat."repostComment"
            )as "repostComment" on "commentInfo".id = "repostComment"."commentID"
            WHERE "chatID" = :chatID AND "sentTIme"= :sentTIme
        ';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':chatID', $data["chatID"], PDO::PARAM_STR);
        $sth->bindParam(':sentTIme', $data["senttime"], PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetchAll();
        // var_dump($sql);
        if (count($row) == 0) {
            $sql = '
                INSERT INTO staff_chat."commentInfo"("chatID", "sentTIme")
                VALUES (:chatID, :sentTIme);
            ';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':chatID', $data["chatID"], PDO::PARAM_STR);
            $sth->bindParam(':sentTIme', $data["senttime"], PDO::PARAM_INT);
            $sth->execute();
            return intval($this->conn->lastInsertId());
        } else {
            return ($row[0]['id']);
        }
        // return count($row);
    }

    function getComment($commentID)
    {
        $sql = 'SELECT content, "UID", "commentChat"."sentTime",to_char( "commentChat"."sentTime",\'MON DD HH24:MI:SS\' )as "showSentTime" ,"readNum"."count" as "readNum","staffinfo".staff_name,CASE "UID" 
              WHEN :UID THEN \'me\'
              ELSE \'other\'
              END
            FROM staff_chat."commentChat" as "commentChat"
            LEFT JOIN (SELECT staff_name,staff_id FROM staff.staff ) as "staffinfo"
            ON "staffinfo".staff_id = "commentChat"."UID"
            LEFT JOIN(
                SELECT "commentChat"."sentTime" ,COUNT(*)
                FROM staff_chat."commentHistory"  AS "commentHistory" 
                left join 
                    (
                        SELECT  "commentChat" .content,  "commentChat" ."UID", "commentChat" . "sentTime","commentChat"."commentID"
                        FROM staff_chat."commentChat" 
                    )
                    as "commentChat" on "commentHistory"."commentID" = "commentChat"."commentID" AND "commentChat"."sentTime"<"commentHistory".lasttime 
                WHERE "commentChat"."commentID" = :commentID  AND "commentHistory"."UID" != "commentChat"."UID"
                GROUP BY "commentChat"."sentTime" 
            )as "readNum" on "readNum"."sentTime" = "commentChat"."sentTime"
            WHERE "commentID"= :commentID
            ORDER BY "sentTime" ASC;
        ';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':commentID', $commentID, PDO::PARAM_STR);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->execute();

        $row = $sth->fetchAll();
        return $row;
    }

    function getCommentReadList($data)
    {
        // $data = json_decode($body['data'],true);
        $sql = '
            SELECT "UID", lasttime, "commentID",staff_name as name,
            case when "lasttime" > :senttime
                    then 1
                    else 0
                    end  haveread
            FROM staff_chat."commentHistory"
            left join "staff"."staff"
            on staff.staff_id="commentHistory"."UID"
            WHERE "commentID" = :commentID and staff_delete=false and "seniority_workStatus" =1 and "UID"!= :UID; 
        
        ';
        $sth = $this->conn->prepare($sql);
        //$sth->bindParam(':t1',$data['sentTime']);
        $sth->bindParam(':commentID', $data['commentID'], PDO::PARAM_STR);
        $sth->bindParam(':UID', $data['UID'], PDO::PARAM_STR);
        $sth->bindParam(':senttime', $data['senttime'], PDO::PARAM_STR);
        // $sth->bindParam(':chatID',$chatID,PDO::PARAM_STR);
        $sth->execute();

        $row = $sth->fetchAll();
        return $row;
    }

    function updateReport($body)
    {
        $sql = 'SELECT  "orgCommentID"
                FROM staff_chat."repostComment"
                WHERE "commentID" = :commentID;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':commentID', intval($body['orgCommentID']), PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetchAll();
        // return $row[0]['orgCommentID'];

        $sql = 'INSERT INTO staff_chat."repostComment"("commentID", "orgCommentID")
                    VALUES (:commentID, :orgCommentID);';
        $sth = $this->conn->prepare($sql);
        if (count($row) == 0) {
            $sth->bindParam(':commentID', intval($body['newCommentID']), PDO::PARAM_INT);
            $sth->bindParam(':orgCommentID', intval($body['orgCommentID']), PDO::PARAM_INT);
            $sth->execute();
            $ack = array(
                '0' => $body['orgCommentID']
            );
            return $ack;
        } else {
            $sth->bindParam(':commentID', intval($body['newCommentID']), PDO::PARAM_INT);
            $sth->bindParam(':orgCommentID', intval($row[0]['orgCommentID']), PDO::PARAM_INT);
            $sth->execute();
            $ack = array(
                '1' => $row[0]['orgCommentID']
            );
            return $ack;
        }
    }

    function insertComment($commentID, $content)
    {

        $sentMsg = '';
        $content = explode('<br />', $content);
        $first = true;
        foreach ($content as $key => $value) {
            if (!$first) {
                $sentMsg .= '<br/>';
            }
            $MsgSplit = explode(" ", $value);
            foreach ($MsgSplit as $keySplit => $valueSplit) {
                if (strpos($valueSplit, 'http://') == 0 && strpos($valueSplit, 'http://') !== false)
                    $sentMsg .= ' <a href="' . $valueSplit . '" style="color:#CCEEFF;" target="_blank">' . $valueSplit . '</a>';
                else if (strpos($valueSplit, 'https://') == 0 && strpos($valueSplit, 'https://') !== false)
                    $sentMsg .= ' <a href="' . $valueSplit . '" style="color:#CCEEFF;" target="_blank">' . $valueSplit . '</a>';

                else
                    $sentMsg .= ' ' . $valueSplit;
            }
            $first = false;
        }
        $sql = 'INSERT INTO staff_chat."commentChat"(content, "UID", "sentTime", "commentID")
                VALUES (:content, :UID, NOW(), :commentID);';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $_SESSION['id'], PDO::PARAM_STR);
        $sth->bindParam(':content', $sentMsg, PDO::PARAM_STR);
        $sth->bindParam(':commentID', $commentID, PDO::PARAM_STR);
        $sth->execute();
        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }

    function getCommentNum($chatID)
    {
        $sql = 'SELECT  "newcommentChat"."id","newcommentChat"."sentTime",
                    case when "oldcommentChat".count IS NULL
                        then 0 
                        else "oldcommentChat".count
                        end as count
                FROM(	SELECT  "sentTIme" AS "sentTime" , id,"repostComment"."orgCommentID",
                    case when "commentChat".count IS NULL
                        then 0
                        else "commentChat".count
                        end ,
                    case when "repostComment"."orgCommentID" IS NULL
                        then id
                        else "repostComment" ."orgCommentID"
                        end "lastCommentID"

                    FROM staff_chat."commentInfo"
                    LEFT JOIN (
                        SELECT "commentID",count(*)
                        FROM staff_chat."commentChat"
                        GROUP BY "commentID"
                    )as "commentChat" on "commentChat"."commentID" = "commentInfo".id
                    LEFT JOIN (
                        SELECT *
                        FROM staff_chat."repostComment"
                    )as "repostComment" on  "repostComment" ."commentID" = id
                    WHERE "chatID" = :chatID
                )as "newcommentChat"
                LEFT JOIN (
                    SELECT  "sentTIme" AS "sentTime" , id,
                    case when "commentChat".count IS NULL
                        then 0 
                        else "commentChat".count
                        end
                    FROM staff_chat."commentInfo"
                    LEFT JOIN (
                        SELECT "commentID",count(*)
                        FROM staff_chat."commentChat"
                        GROUP BY "commentID"
                    )as "commentChat" on "commentChat"."commentID" = "commentInfo".id


                )as "oldcommentChat" on "oldcommentChat".id = "newcommentChat"."lastCommentID"';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
        $sth->execute();

        $row = $sth->fetchAll();
        return $row;
    }


    function getChatContentNew($chatID, $body)
    {
        $data = json_decode($body['data'], true);
        $UID = $_SESSION['id'];
        for ($i = 0, $timeout = 10; $i < $timeout; $i++) {

            $sql = '
                SELECT "content",("sentTime")as "fullsentTime",to_char( "sentTime",\'MON DD HH24:MI:SS\' )as "sentTime","UID","diff","Read",staff_name
                FROM (
                    SELECT "chatContent"."content","chatContent"."sentTime","chatContent"."UID",(CASE "chatContent"."UID" WHEN :UID THEN \'me\' ELSE \'other\' END) as "diff",COALESCE("readCount",0) as "Read",staff_name
                    FROM staff_chat."chatContent"
                    LEFT JOIN (
                        SELECT "content","sentTime","sentFrom",COUNT("UID") as "readCount"
                        FROM (
                                SELECT content, "sentTime", "UID" as "sentFrom","chatID"
                                FROM staff_chat."chatContent"
                                WHERE "chatID"= :chatID
                            )as "display",(
                                SELECT "chatID", "time", "UID"
                                FROM staff_chat."chatHistory"
                                Where "chatID"=:chatID
                            ) as "chatHistory" 
                        Where "UID"!=:UID and "display"."chatID"="chatHistory"."chatID" and "chatHistory"."time">"display"."sentTime"
                        Group by "content","sentTime","sentFrom" 
                    ) as "displayContent" on "chatContent"."content"="displayContent"."content" and "chatContent"."sentTime"="displayContent"."sentTime" and "chatContent"."UID"="displayContent"."sentFrom"
                    LEFT JOIN staff."staff" on staff.staff_id="chatContent"."UID"
                    Where "chatID"=:chatID
                    order by "chatContent"."sentTime" desc 
                    -- limit :limit 
                ) as "tmpChatContent"
                order by "tmpChatContent"."sentTime" asc
            ';
            // $sql = 'SELECT content, to_char( "sentTime",\'MM-DD HH24:MI:SS\' )as "sentTime", "UID",(CASE "UID" WHEN :UID THEN \'me\' ELSE \'other\' END)
            //         as "diff",staff_name 
            //         FROM staff_chat."chatContent" 
            //         left join "staff"."staff" on staff.staff_id="chatContent"."UID" 
            //         WHERE "chatID"= :chatID 
            //         order by "sentTime" asc;';

            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
            $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
            // $sth->bindParam(':limit',$data['limit'],PDO::PARAM_INT);
            $sth->execute();
            $row = $sth->fetchAll();
            if (count($row) == $data['count']) {
                usleep(5000000);
                clearstatcache();
            } else {
                $result = array();
                for ($j = $data['count']; $j < count($row); $j++) {
                    array_push($result, $row[$j]);
                }
                array_push($result, array('chatID' => $chatID));
                // $body = array('chatID'=>$chatID);
                // $this->updateLastReadTime($body);
                return $result;
            }
        }

        // $result=($row==$data);
        // array_push($row, array('diff'=>$result));

        // $sth = $this->conn->prepare($sql);
        // $UID =$_SESSION['id'];
        // $sth->bindParam(':UID',$UID,PDO::PARAM_STR);
        // $sth->bindParam(':chatID',$chatID,PDO::PARAM_INT);
        // $sth->bindParam(':limit',$data['limit'],PDO::PARAM_INT);
        // $sth->execute();

        // $row = $sth->fetchAll();
        // $body = array('chatID'=>$chatID);
        // $this->updateLastReadTime($body);
        $result = array();
        array_push($result, array('chatID' => $chatID));
        return $result;
    }
    function getChatContent($chatID)
    {

        $sql = 'SELECT "chatContent"."content",to_char( "chatContent"."sentTime",\'MON DD HH24:MI:SS\' )as "sentTime","chatContent"."UID",(CASE "chatContent"."UID" WHEN :UID THEN \'me\' ELSE \'other\' END) as "diff",COALESCE("readCount",0) as "Read",staff_name
            FROM staff_chat."chatContent"
            LEFT JOIN
            (
                SELECT "content","sentTime","sentFrom",COUNT("UID") as "readCount"
                FROM (
                    SELECT content, "sentTime", "UID" as "sentFrom","chatID"
                    FROM staff_chat."chatContent"
                    WHERE "chatID"= :chatID)as "display",
                    (
                        SELECT "chatID", "time", "UID"
                        FROM staff_chat."chatHistory"
                        Where "chatID"=:chatID
                    ) as "chatHistory" 
                Where "UID"!=:UID and "display"."chatID"="chatHistory"."chatID" and "chatHistory"."time">"display"."sentTime"
                Group by "content","sentTime","sentFrom" 
            ) as "displayContent" on "chatContent"."content"="displayContent"."content" and "chatContent"."sentTime"="displayContent"."sentTime" and "chatContent"."UID"="displayContent"."sentFrom"
            LEFT JOIN staff."staff" on staff.staff_id="chatContent"."UID"
            Where "chatID"=:chatID
            order by "chatContent"."sentTime" asc';
        $sth = $this->conn->prepare($sql);
        $UID = $_SESSION['id'];
        $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
        $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetchAll();

        $body = array('chatID' => $chatID);
        $this->updateLastReadTime($body);

        return $row;
    }

    function updateMessage($body)
    {
        try {
            $date = DateTime::createFromFormat('0.u00 U', microtime());
            $timezone = new DateTimeZone('Asia/Taipei');
            $date->setTimezone($timezone);
            $t = microtime(true);
            $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
            $d = new DateTime(date('Y-m-d H:i:s.' . $micro, $t));

            $tmpFullTime = $d->format("Y-m-d H:i:s.u") . '+08';
            // var_dump( $tmpFullTime);
            $sql = 'INSERT INTO staff_chat."chatContent"(	content, "UID", "sentTime", "chatID")
                VALUES ( :Msg , :UID , NOW(), :chatID );';
            $sth = $this->conn->prepare($sql);
            // $UID = $_SESSION['id'];
            $UID = $body['UID'];
            $chatID = $body['chatID'];
            $Msg = $body['Msg'];

            $sentMsg = '';
            $Msg = explode('<br />', $Msg);
            $first = true;
            foreach ($Msg as $key => $value) {
                if (!$first) {
                    $sentMsg .= '<br/>';
                }
                $MsgSplit = explode(" ", $value);
                foreach ($MsgSplit as $keySplit => $valueSplit) {
                    if (strpos($valueSplit, 'http://') == 0 && strpos($valueSplit, 'http://') !== false)
                        $sentMsg .= ' <a href="' . $valueSplit . '" style="color:#CCEEFF;" target="_blank">' . $valueSplit . '</a>';
                    else if (strpos($valueSplit, 'https://') == 0 && strpos($valueSplit, 'https://') !== false)
                        $sentMsg .= ' <a href="' . $valueSplit . '" style="color:#CCEEFF;" target="_blank">' . $valueSplit . '</a>';

                    else
                        $sentMsg .= ' ' . $valueSplit;
                }
                $first = false;
            }
            $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
            $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
            // $sth->bindParam(':fullTime', $tmpFullTime, PDO::PARAM_INT);
            $sth->bindParam(':Msg', $sentMsg, PDO::PARAM_INT);
            $sth->execute();
            // $insert_id = $this->conn->lastInsertId();

            $ack = array(
                'status' => 'success',
                // 'id'=>$insert_id,
                'time' => $tmpFullTime,
                'fulltime' => $d->format("Y-m-d H:i:s.u")
            );
        } catch (PDOException $e) {
            $ack = array(
                'status' => 'failed',
                'message' => $e
            );
        }

        return $ack;
    }
    function getLastOnLine($UID)
    {
        $sql = 'SELECT CASE 
                WHEN (
                        (
                            (
                                DATE_PART(\'day\', NOW()::timestamp - MAX("time")::timestamp) * 24 + DATE_PART(\'hour\', NOW()::timestamp - MAX("time")::timestamp)
                            ) * 60 + DATE_PART(\'minute\', NOW()::timestamp - MAX("time")::timestamp)
                        )  > 60 
                ) then	to_char(MAX("time"), \'YYYY/MM/DD HH12:MI:SS\')
                ELSE (
                            (
                                DATE_PART(\'day\', NOW()::timestamp - MAX("time")::timestamp) * 24 + DATE_PART(\'hour\', NOW()::timestamp - MAX("time")::timestamp)
                            ) * 60 + DATE_PART(\'minute\', NOW()::timestamp - MAX("time")::timestamp)
                        )::text || \' 分鐘前上線\'
                END as "lastOnLine"
                FROM staff_chat."chatHistory"
                WHERE "chatHistory"."UID" = :UID
                ';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetchAll();
        $ack = array(
            'status' => 'success',
            'time' => $row[0]['lastOnLine'],

        );
        return $ack;
    }

    function updateLastReadTime($body)
    {
        $sql = 'UPDATE staff_chat."chatHistory" SET "time"= NOW() WHERE "chatHistory"."chatID"= :chatID AND "chatHistory"."UID"= :UID ;';
        $sth = $this->conn->prepare($sql);
        $UID = $this->sessionID;
        $chatID = $body['chatID'];
        $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
        $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
        $sth->execute();

        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }
    function createChatroom($body)
    {
        $body = json_decode($body['data'], true);
        $sql = 'INSERT INTO staff_chat."chatroomInfo"( "chatName") VALUES (:chatName);';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':chatName', $body['title'], PDO::PARAM_STR);
        $sth->execute();

        $chatID = $this->conn->lastInsertId();
        array_push(
            $body['member'],
            array('UID' => $_SESSION['id'])
        );
        foreach ($body['member'] as $key => $value) {
            $sql = 'INSERT INTO staff_chat."chatHistory"("chatID", "time", "UID") VALUES (:chatID, NOW(), :UID);';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':UID', $value['UID'], PDO::PARAM_STR);
            $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
            $sth->execute();
        }

        $ack = array(
            'status' => 'success',
            "chatID"=>$chatID
        );
        return $ack;
    }
    function updateChatroom($body)
    {
        $body = json_decode($body['data'], true);
        $chatID = $body['chatID'];
        $sql = 'UPDATE staff_chat."chatroomInfo" SET "chatName"=:chatName WHERE "chatID"=:chatID;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':chatName', $body['title'], PDO::PARAM_STR);
        $sth->bindParam(':chatID', $body['chatID'], PDO::PARAM_INT);
        $sth->execute();

        foreach ($body['member'] as $key => $value) {
            $sql = 'INSERT INTO staff_chat."chatHistory"("chatID", "time", "UID") VALUES (:chatID, NOW(), :UID);';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':UID', $value['UID'], PDO::PARAM_STR);
            $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
            $sth->execute();
        }

        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }
    function deleteChatroom($body)
    {
        $body = json_decode($body['data'], true);
        $chatID = $body['chatID'];
        $sql = 'DELETE FROM staff_chat."chatHistory" WHERE "chatID"=:chatID and "UID" = :staff_id;';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':staff_id', $_SESSION['id'], PDO::PARAM_STR);
        $sth->bindParam(':chatID', $body['chatID'], PDO::PARAM_INT);
        $sth->execute();

        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }

    function getList($chatID = null)
    {
        if (is_null($chatID)) {
            $sql = "SELECT staff_name as name,staff_id as id FROM staff.staff WHERE staff_id != :staff_id;";
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':staff_id', $_SESSION['id'], PDO::PARAM_STR);
            $sth->execute();
            $row = $sth->fetchAll();
        } else {
            $sql = 'SELECT staff_name as name,staff_id as id FROM staff.staff LEFT JOIN staff_chat."chatHistory" on staff_chat."chatHistory"."UID" = staff.staff.staff_id and "chatID"=:chatID WHERE "chatID" is null and staff_id != :staff_id;';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':staff_id', $_SESSION['id'], PDO::PARAM_STR);
            $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
            $sth->execute();
            $row = $sth->fetchAll();
        }
        return $row;
    }
    function uploadFile($chatID, $directory, $uploadedFiles, $isPicture)
    {
        // handle single input with single file upload
        $uploadedFile = $uploadedFiles['inputFile'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = $this->moveUploadedFile($directory, $uploadedFile);
            $UID = $_SESSION['id'];
            $sql = 'INSERT INTO staff_chat.files("fileName", "UID", "fileNameClient") VALUES (:fileName, :UID, :fileNameClient);';
            $sth = $this->conn->prepare($sql);
            $sth->bindParam(':fileName', $filename, PDO::PARAM_STR);
            $sth->bindParam(':fileNameClient', $uploadedFile->getClientFilename(), PDO::PARAM_STR);
            $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
            $sth->execute();

            $sql = 'INSERT INTO staff_chat."chatContent"(	content, "UID", "sentTime", "chatID")
                    VALUES ( :Msg , :UID , NOW(), :chatID );';
            $sth = $this->conn->prepare($sql);
            $UID = $_SESSION['id'];
            if ($isPicture) {
                $Msg = '
                    <a href="/chat/picture/' . $this->conn->lastInsertId() . '" target="_blank">
                        <img src="/chat/thumbnail/' . $this->conn->lastInsertId() . '" alt="..." class="img-thumbnail">
                    </a>
                ';
            } else {
                $Msg = '<a href="/chat/file/' . $this->conn->lastInsertId() . '" style="color:#FFFFFF;">' . $uploadedFile->getClientFilename() . '</a>';
            }
            $sth->bindParam(':UID', $UID, PDO::PARAM_STR);
            $sth->bindParam(':chatID', $chatID, PDO::PARAM_INT);
            $sth->bindParam(':Msg', $Msg, PDO::PARAM_STR);
            $sth->execute();
            $result = array(
                'status' => 'success'
            );
        } else {
            $result = array(
                'status' => 'failed'
            );
        }
        return $result;
    }

    private function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }
    function getFileFormat($fileID)
    {
        $supported_image = array(
            'gif',
            'jpg',
            'jpeg',
            'png'
        );
        $sql = '
            SELECT id, "fileName", "fileNameClient", "uploadTime", "UID"
            FROM staff_chat.files
            WHERE id = :fileID;
        ';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':fileID', $fileID, PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetchAll();
        if (count($row) == 1) {
            $result = array(
                'status' => 'success',
            );
            $result['type'] = 'file';
            $ext = strtolower(pathinfo($row[0]['fileName'], PATHINFO_EXTENSION));
            if (in_array($ext, $supported_image)) {
                $result['type'] = 'picture';
            }
        } else {
            $result = array(
                'status' => 'failed'
            );
        }
        return $result;
    }
    function downloadFile($fileID)
    {
        $sql = '
            SELECT id, "fileName", "fileNameClient", "uploadTime", "UID"
            FROM staff_chat.files
            WHERE id = :fileID;
        ';
        $sth = $this->conn->prepare($sql);
        $sth->bindParam(':fileID', $fileID, PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetchAll();
        if (count($row) == 1) {
            $result = array(
                'status' => 'success',
                'data' => $row[0]
            );
        } else {
            $result = array(
                'status' => 'failed'
            );
        }
        return $result;
    }
    function getSystemUserId($data){
        $sql = "SELECT system_token.user_id \"UID\",access_token,\"user\".\"name\"
            FROM setting.system_token
            LEFT JOIN system.\"user\" ON \"user\".id = system_token.user_id;
        ";
        $sth = $this->conn->prepare($sql);
        $sth->execute();
        $row = $sth->fetchAll();
        return $row;
    }
}
