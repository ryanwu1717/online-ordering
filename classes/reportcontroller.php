<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\Exception;
use \PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \PhpOffice\PhpSpreadsheet\Writer\Xls;
use \PhpOffice\PhpSpreadsheet\Writer\Csv;

class reportcontroller
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }
    public function renderReportOverView($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/report/index.html');
    }
    public function renderPreProduct($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/report/preProduct.html');
    }
    public function renderOrderProductCategoryView($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/report/orderProductCategoryView.html');
    }
    public function renderProductionStaff($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/report/productionStaff.html');
    }
    public function renderOrderCurrencyStatistic($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/report/orderCurrencyStatistic.html');
    }
    public function renderItemCategoryAnalsis($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/report/itemCategoryAnalsis.html');
    }
    public function renderClientOrder($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/report/clientOrder.html');
    }
    public function renderProductItem($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/report/productItem.html');
    }
    public function renderProductItemFixedCategory($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/report/productItemFixedCategory.html');
    }
    public function renderQuarterlyTurnover($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/report/quarterlyTurnover.html');
    }
    public function renderOuterSecantComparison($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/report/outerSecantComparison.html');
    }
    public function renderbusinessInventorySearch($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/report/businessInventorySearch.html');
    }
    public function getPreProduction($request, $response, $args)
    {
        $result = [];
        $data = $request->getQueryParams();
        $date_begin = date("Ymd");
        $date_end = date("Ymd");
        foreach ($data as $key => $value) {
            if ($key == 'date_begin') {
                $date_begin = $value;
            } else if ($key == 'date_end') {
                $date_end = $value;
            }
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT DATEPART(WK, \"預計生產完成日\")-1 AS \"週數\",\"預計生產完成日\",\"盤數\",\"訂單數量\",COALESCE(\"外注完成量\",0) \"外注完成量\",COALESCE(\"現場完成量\",0) \"現場完成量\"
                FROM(
                    SELECT 
                        \"預計生產完成日\",
                        COUNT(*) \"盤數\",
                        SUM(\"訂單數量\") \"訂單數量\"
                    FROM(
                        SELECT
                        COPTD.TD215 \"預計生產完成日\",
                        SUM(COPTD.TD008) \"訂單數量\"
                        FROM
                        MIL.dbo.COPTC 
                        INNER JOIN MIL.dbo.COPTD ON (COPTC.TC001=COPTD.TD001 and COPTC.TC002=COPTD.TD002)
                        WHERE
                        (
                        COPTD.TD215  BETWEEN {$date_begin}  AND {$date_end})
                        AND
                        COPTD.TD001  NOT IN  ( '2230','2240','2250','2270'  )
                        AND
                        COPTD.TD016  IN  ( 'N'  )
                        GROUP BY COPTD.TD215,COPTD.TD001 ,COPTD.TD002,COPTD.TD003
                    ) a
                    GROUP BY \"預計生產完成日\"
                )a
                LEFT JOIN (
                    SELECT XD001 TA014,
                        SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5201' OR SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5206' THEN XD017 ELSE 0 END) \"外注完成量\",
                        SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5201' AND SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5206' THEN XD017 ELSE 0 END) \"現場完成量\"
                    FROM MIL.dbo.MOCXD
                    WHERE (MOCXD.XD001 BETWEEN {$date_begin} AND {$date_end}) AND SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)  NOT IN  ( '5202','5205','5207'  )
                    GROUP BY MOCXD.XD001
                )b ON a.\"預計生產完成日\" = b.TA014
                
                ORDER BY \"預計生產完成日\" ASC
                
                "]
            )
        );
        /* 
        SELECT XC001 TA014,SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5201' OR SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5206' THEN XC007 ELSE 0 END) \"外注完成量\",SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5201' OR SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5206' THEN XC007 ELSE 0 END) \"現場完成量\"
        FROM MIL.dbo.MOCXC
        LEFT JOIN MIL.dbo.MOCXD ON MOCXD.XD001 = MOCXC.XC001 AND MOCXD.XD002 = MOCXC.XC002
        WHERE XC001 BETWEEN {$date_begin}  AND {$date_end}) AND SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)  NOT IN  ( '5202','5205','5207'  )
        GROUP BY MOCXC.XC001
        */
        /* 
        SELECT
        b.TA014,
        SUM( CASE WHEN b.TA001='5201' OR b.TA001='5206' THEN b.TD008 ELSE 0 END ) \"外注完成量\",
        SUM( CASE WHEN b.TA001!='5201' AND b.TA001!='5206' THEN b.TD008 ELSE 0 END ) \"現場完成量\"
        FROM(
            SELECT
            MOCTA.TA014,COPTD.TD001,COPTD.TD002,COPTD.TD003,MOCTA.TA001,COPTD.TD008
            FROM
            MIL.dbo.MOCTG 
            INNER JOIN MIL.dbo.MOCTA ON (MOCTA.TA006=MOCTG.TG004  AND  MOCTA.TA001=MOCTG.TG014 and MOCTA.TA002=MOCTG.TG015)
            RIGHT OUTER JOIN MIL.dbo.COPTD ON (COPTD.TD001=MOCTA.TA026 and COPTD.TD002=MOCTA.TA027 and COPTD.TD003=MOCTA.TA028)
            
            WHERE
            MOCTA.TA205 = 'N'
            AND
            MOCTA.TA011  =  'Y'
            AND
            MOCTA.TA001  NOT IN  ( '5202','5205','5207'  )
            AND
            (MOCTA.TA014  BETWEEN {$date_begin}  AND {$date_end})
            GROUP BY MOCTA.TA014,COPTD.TD001,COPTD.TD002,COPTD.TD003,MOCTA.TA001,COPTD.TD008
        )b
        GROUP BY b.TA014
        */
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result['origin'] = json_decode($head, true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT COALESCE(SUM(\"盤數\"),0) \"盤數\",COALESCE(SUM(\"訂單數量\"),0) \"訂單數量\"
                FROM(
                    SELECT 
                        \"預計生產完成日\",
                        COUNT(*) \"盤數\",
                        SUM(\"訂單數量\") \"訂單數量\"
                    FROM(
                        SELECT
                        COPTD.TD215 \"預計生產完成日\",
                        SUM(COPTD.TD008) \"訂單數量\"
                        FROM
                        MIL.dbo.COPTC 
                        INNER JOIN MIL.dbo.COPTD ON (COPTC.TC001=COPTD.TD001 and COPTC.TC002=COPTD.TD002)
                        WHERE
                        (
                        COPTD.TD215  BETWEEN {$date_begin}  AND {$date_end})
                        AND
                        COPTD.TD001  NOT IN  ( '2230','2240','2250','2270'  )
                        AND
                        COPTD.TD016  IN  ( 'N'  )
                        GROUP BY COPTD.TD215,COPTD.TD001 ,COPTD.TD002,COPTD.TD003
                    ) a
                    GROUP BY \"預計生產完成日\"
                )dt
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result['total'] = json_decode($head, true);
        curl_close($ch);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        $date_end = DateTime::createFromFormat('Ymd', $date_begin)->format('Y/m/d');
        $date_end = strtotime($date_end);
        $date_end = strtotime("+30 day", $date_end);
        $date_end = date('Ymd', $date_end);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT *
                    FROM(
                        SELECT XD001 \"日期\",DATEPART(dw,MOCXD.XD001) dw,
                            SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5201' OR SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5206' THEN XD017 ELSE 0 END) \"外注完成量\",
                            SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5201' AND SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5206' THEN XD017 ELSE 0 END) \"現場完成量\",
                            ROW_NUMBER() OVER(ORDER BY XD001) row_num
                        FROM MIL.dbo.MOCXD
                        WHERE (MOCXD.XD001 BETWEEN {$date_begin} AND {$date_end}) AND DATEPART(dw,MOCXD.XD001) NOT IN (1,7) AND SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)  NOT IN  ( '5202','5205','5207'  )
                        GROUP BY MOCXD.XD001
                    )AS dt
                    WHERE row_num < 6
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $fivedays = json_decode($head, true);
        curl_close($ch);
        $result['fivedays'] = [
            "日期" => "",
            "外注完成量" => 0,
            "現場完成量" => 0
        ];
        foreach ($fivedays as $fiveday) {
            foreach ($result['fivedays'] as $key => $value) {
                if (array_key_exists($key, $fiveday)) {
                    if ($key == '日期') {
                        if ($result['fivedays'][$key] !== "") {
                            $result['fivedays'][$key] .= "," . $fiveday[$key];
                        } else {
                            $result['fivedays'][$key] = $fiveday[$key];
                        }
                    } else {
                        $result['fivedays'][$key] += $fiveday[$key];
                    }
                }
            }
        }
        foreach ($result['fivedays'] as $key => $value) {
            if ($key != '日期') {
                $result['fivedays'][$key] = number_format($value / 5, 2);
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOrderProductCategoryDetail($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $date_begin = date("Ymd");
        $date_end = date("Ymd");
        foreach ($data as $key => $value) {
            if ($key == 'date_begin') {
                $date_begin = $value;
            } else if ($key == 'date_end') {
                $date_end = $value;
            }
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT
                    COPTC.TC003 \"訂單日期\",
                    COPTD.TD001 \"單別\",
                    COPTD.TD002 \"單號\",
                    COPTD.TD001+'-'+COPTD.TD002+'-'+COPTD.TD003 \"(單別)+(單號)+(序號)\",
                    COPTD.TD003 \"序號\",
                    COPTC.TC004 \"客戶代號\",
                    COPTD.TD004 \"品號\",
                    COPTD.TD008 \"訂單數量\",
                    MOCTA.TA001 \"製令單別\",
                    MOCTA.TA002 \"製令單號\",
                    MOCTA.TA026+'-'+MOCTA.TA027+'-'+MOCTA.TA028 \"MOCTA.TA026-MOCTA.TA027-MOCTA.TA028\",
                    COPTD.TD215 \"預計生產完成日\",
                    MOCTA.TA012 \"實際開工\",
                    MOCTA.TA014 \"實際完工\",
                    MOCTA.TA015 \"預計產量\",
                    MOCTA.TA001 +'-'+MOCTA.TA002 \"(製令單別)+(製令單號)\",
                    COPTD.TD005 \"品名\",
                    COPTD.TD012 \"金額\",
                    COPTC.TC008 \"交易幣別\",
                    COPTC.TC009 \"匯率\",
                    COPTD.TD011 \"單價\",
                    MOCTA.TA017 \"已生產量\",
                    MOCTA.TA018 \"報廢數量\"
                FROM
                    MIL.dbo.MOCTA 
                RIGHT OUTER JOIN 
                    MIL.dbo.COPTD ON (COPTD.TD001=MOCTA.TA026 and COPTD.TD002=MOCTA.TA027 and COPTD.TD003=MOCTA.TA028)
                INNER JOIN 
                    MIL.dbo.COPTC ON (COPTD.TD001=COPTC.TC001 and COPTD.TD002=COPTC.TC002)
                    
                WHERE
                    (
                    (COPTC.TC003  BETWEEN  $date_begin  AND $date_end)
                    AND
                    MOCTA.TA011  NOT IN  ( 'y'  )
                    AND
                    (
                    MOCTA.TA001  Is Null  
                    OR
                    MOCTA.TA001  NOT IN  ( '5202','5205','5198','5199','5207','5203','5204'  )
                    )
                    )
                ORDER BY COPTC.TC003
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOrderProductCategory($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $date_begin = date("Ymd");
        $date_end = date("Ymd");
        foreach ($data as $key => $value) {
            if ($key == 'date_begin') {
                $date_begin = $value;
            } else if ($key == 'date_end') {
                $date_end = $value;
            }
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT dt.\"編號\",dt.\"類別\",COALESCE(\"訂單數量\",0) \"訂單數量\",COALESCE(\"預計產量\",0) \"預計產量\"
                FROM(
                    SELECT 1 AS \"編號\",'06-前沖棒+07-後沖棒-(WC)' AS \"類別\"
                    UNION ALL SELECT 2,'03-模仁+05-INSERT(嵌入件)(WC)'
                    UNION ALL SELECT 3,'08-通孔沖棒-(銲接MIL-TIP)'
                    UNION ALL SELECT 4,'02-模組(兩種以上鋼料)'
                    UNION ALL SELECT 5,'02-模組(5-鎢+鋼模組)'
                    UNION ALL SELECT 6,'03-模仁(HSS-1整體鋼料)'
                    UNION ALL SELECT 7,'01-切刀'
                    UNION ALL SELECT 8,'04-模殼'
                    UNION ALL SELECT 9,'09-套管'
                    UNION ALL SELECT 10,'10-墊塊'
                    UNION ALL SELECT 11,'11-沖棒固定塊'
                    UNION ALL SELECT 12,'12-公牙'
                    UNION ALL SELECT 13,'13-夾子'
                    UNION ALL SELECT 14,'14-零件'
                    UNION ALL SELECT 15,'15-棘輪'
                    UNION ALL SELECT 16,'16-PIN'
                    UNION ALL SELECT 17,'17-通孔管'
                    UNION ALL SELECT 18,'18-其他'
                )dt
                LEFT JOIN (
                    SELECT category AS \"類別\",SUM(TD008) \"訂單數量\",SUM(\"預計產量\") \"預計產量\"
                    FROM(
                        SELECT
                          CASE 
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '06' OR SUBSTRING(COPTD.TD004, 9, 2) = '07') AND SUBSTRING(COPTD.TD004, 17, 1) = '4'
                             THEN '06-前沖棒+07-後沖棒-(WC)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '03' OR SUBSTRING(COPTD.TD004, 9, 2) = '05') AND SUBSTRING(COPTD.TD004, 17, 1) = '4'
                             THEN '03-模仁+05-INSERT(嵌入件)(WC)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '08') AND SUBSTRING(COPTD.TD004, 17, 1) = '2'
                             THEN '08-通孔沖棒-(銲接MIL-TIP)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '02') AND SUBSTRING(COPTD.TD004, 17, 1) = '3'
                             THEN '02-模組(兩種以上鋼料)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '02') AND SUBSTRING(COPTD.TD004, 17, 1) = '5'
                             THEN '02-模組(5-鎢+鋼模組)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '03') AND SUBSTRING(COPTD.TD004, 17, 1) = '1'
                             THEN '03-模仁(HSS-1整體鋼料)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '01') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '01-切刀'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '04') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '04-模殼'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '09') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '09-套管'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '10') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '10-墊塊'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '11') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '11-沖棒固定塊'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '12') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '12-公牙'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '13') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '13-夾子'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '14') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '14-零件'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '15') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '15-棘輪'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '16') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '16-PIN'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '17') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '17-通孔管'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '18') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '18-其他'
                          END AS category,
                          SUM(MOCTA.TA015) AS \"預計產量\",COPTD.TD001,COPTD.TD002,COPTD.TD003,COPTD.TD008
                        FROM
                          MIL.dbo.COPTD
                          LEFT JOIN MIL.dbo.MOCTA ON (COPTD.TD001=MOCTA.TA026 and COPTD.TD002=MOCTA.TA027 and COPTD.TD003=MOCTA.TA028)
                           INNER JOIN MIL.dbo.COPTC ON (COPTD.TD001=COPTC.TC001 and COPTD.TD002=COPTC.TC002)
                        WHERE
                          (
                           COPTC.TC003  BETWEEN {$date_begin} AND {$date_end}
                           AND
                           (
                            MOCTA.TA001  Is Null
                            OR
                            MOCTA.TA011  NOT IN  ( 'y'  )
                           )
                           AND
                           (
                            MOCTA.TA001  Is Null  
                            OR
                            MOCTA.TA001  NOT IN  ( '5202','5205','5198','5199','5207','5203','5204'  )
                           )
                          )
                          GROUP BY SUBSTRING(COPTD.TD004, 9, 2),SUBSTRING(COPTD.TD004, 17, 1),COPTD.TD001,COPTD.TD002,COPTD.TD003,COPTD.TD008
                    ) a
                    WHERE category IS NOT NULL
                    GROUP BY category
                )dt2 ON dt.\"類別\" = dt2.\"類別\"
                ORDER BY dt.\"編號\"
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getStaffProductivity($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];

        $main = $report->readStaffProductivity($params);

        $result = [
            "data" => []
        ];
        $result['total'] = 0;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $params['size'];
        $start = ($params['cur_page'] - 1) * $params['size'];

        foreach ($main as $key => $order) {
            $result['total'] += 1;
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if ($length > 0 && $key >= $start) {
                array_push($result['data'], $order);
                $length--;
            }
        };


        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getStaffProductivityUser($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $main = $report->readAllStaffProductivityUser($params);

        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($main);
        return $response;
    }

    public function getAllStaffProductivity($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $params = $report->convertDateFormat($params);
        $main = $report->readStaffProductivity($params);
        $total = [];
        $line = [];
        $user = [];
        foreach ($main as $key => $value) {
            if (!isset($total[$value['工號']])) {
                $total[$value['工號']] = 0;
                if (!in_array($value['移出部門'], $line) && $value['移出部門']!='C' && $value['移出部門']!='E') {
                    $line[] = $value['移出部門'];
                }
                $user[] = [
                    'uid' => $value['工號'],
                    'userName' => $value['姓名']
                ];
            }
            $total[$value['工號']] += 1;
        }
        foreach ($main as $key => $value) {
            $main[$key]['總計'] = $total[$value['工號']];
        }
        if (!isset($params['line'])) {
            $result = [
                'main' => $main,
                'line' => $line,
                'user' => $user
            ];
        } else {
            $result = ['main' => $main];
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getItemCategoryAnalysis($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $orders = $report->readItemCategoryAnalysis($params);
        
        $result = [
            "data" => []
        ];
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $params['size'];
        $start = ($params['cur_page'] - 1) * $params['size'];
        foreach ($orders as $key => $order) {
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if ($length > 0 && $key >= $start) {
                array_push($result['data'], $order);
                $length--;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getItemCategoryAnalysisPdf ($request, $response, $args){
        $params = $request->getQueryParams();
        $report = new report($this->container->db);

        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];

        $show_date = $params['date_begin'] . '~' . $params['date_end'];

        $result = $report->readItemCategoryAnalysis($params);

		$rows = "";
		foreach ($result as $key => $value) {
			$rows .= "<tr>";
			foreach ($value as $key => $each_value) {
				$rows .= "<td style=\"text-align:left\">{$each_value}</td>";
			}
			$rows .= "</tr>";
		}

		// create new PDF document
		$pdf = new TCPDF_chinese('L', PDF_UNIT, "A4", true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('mil');
		$pdf->SetTitle("訂單到製令～欲切割 品名類別 分析用");
		$pdf->SetSubject('訂單到製令～欲切割 品名類別 分析用pdf');
		$pdf->SetKeywords('TCPDF, PDF, mil');

		// remove default header/footer
		$pdf->setPrintHeader(false);
		// $pdf->setPrintFooter(false);

		// set header and footer fonts
		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		// $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
			require_once(dirname(__FILE__) . '/lang/eng.php');
			$pdf->setLanguageArray($l);
		}

		// ---------------------------------------------------------

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		// $pdf->SetFont('dejavusans', '', 14, '', true);

		// Set font
		$fontname = TCPDF_FONTS::addTTFfont(__DIR__ . DIRECTORY_SEPARATOR . '/fonts/droidsansfallback.ttf', 'TrueTypeUnicode', '', 96);

		// $pdf->addTTFfont('/Users/laichuanen/droidsansfallback.ttf'); 
		$pdf->SetFont($fontname, '', 9, '', false);
		// $pdf->SetFont('msungstdlight', '', 12);

		// 設定資料與頁面上方的間距 (依需求調整第二個參數即可)
		$pdf->SetMargins(5, 10, 5);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>{$show_date} 訂單到製令～欲切割 品名類別 分析用</h3>
		<table border="0.1" style="width:100%">
			<tr>
				<th style="width:6%">訂單日期</th>
				<th style="width:6%">客戶代號</th>
				<th style="width:4%">訂單單別</th>
				<th style="width:8%">訂單單號</th>
				<th style="width:6%">訂單序號</th>
				<th style="width:6%">訂單變數</th>
				<th style="width:14%">品號</th>
				<th style="width:6%">訂單數量&nbsp;&nbsp;(無聚總)</th>
				<th style="width:6%">製令變數</th>
				<th style="width:6%">品號切割&nbsp;9-10碼</th>
				<th style="width:6%">品名</th>
				<th style="width:6%">製令單別單號</th>
				<th style="width:6%">製令單別</th>
				<th style="width:8%">製令單號</th>
				<th style="width:6%">預計產量</th>
			</tr>
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("訂單到製令～欲切割 品名類別 分析用{$show_date}.pdf");
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output($file_name, 'D');
    }

    public function getCilentOrder($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $orders = $report->readCilentOrder($params);

        $result = [
            "data" => []
        ];
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $params['size'];
        $start = ($params['cur_page'] - 1) * $params['size'];
        foreach ($orders as $key => $order) {
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if ($length > 0 && $key >= $start) {
                array_push($result['data'], $order);
                $length--;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function exportCilentOrder($request, $response, $args, $called = false)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        
        $date = [
            'start' => '2019-01',
            'end' => '2019-01',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);

        //只有年月 所以加上日
        $params['date_begin'] = $date['start'] . "00";
        $params['date_end'] = $date['end'] . "32";

        $orderOne = $report->exportCilentOrderOne($params);
        $orders = $report->exportCilentOrderOther($params);

        //統計
        $result_data = [];
        foreach($orderOne as $value) {
            $result_data[intval(substr($value['訂單日期'], 0, 4))][intval(substr($value['訂單日期'], 4, 2))]['001'] = intval($value['次數']);
        }
        foreach($orders as $value) {
            $now_year = intval(substr($value['訂單日期'], 0, 4));
            $now_month = intval(substr($value['訂單日期'], 4, 2));
            if($value['次數'] == 1) {
                if(!isset($result_data[$now_year][$now_month]['一次'])) {
                $result_data[$now_year][$now_month]['一次'] = 0;
                }
                $result_data[$now_year][$now_month]['一次'] += 1;
            }
            else {
                if(!isset($result_data[$now_year][$now_month]['一次以上'])) {
                    $result_data[$now_year][$now_month]['一次以上'] = 0;
                }
                $result_data[$now_year][$now_month]['一次以上'] += 1;
            }
        }

        //整理格式
        $start_year = intval(substr($params['date_begin'], 0, 4));
        $start_month = intval(substr($params['date_begin'], 4, 2));
        $end_year = intval(substr($params['date_end'], 0, 4));
        $end_month = intval(substr($params['date_end'], 4, 2));

        $month = ['零','一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'];

        $result = [];
        for($now_year=$start_year; $now_year<=$end_year; $now_year++) {
            $result[(string)$now_year] = [];
            $max_month = 12;
            $min_month = 1;
            if($now_year == $start_year) {
                $min_month = $start_month;
            }
            if($now_year == $end_year) {
                $max_month = $end_month;
            }
            for($now_month=$min_month; $now_month<=$max_month; $now_month++) {
                if(!isset($result_data[(string)$now_year][(string)$now_month]['001'])) {
                    $result_data[(string)$now_year][(string)$now_month]['001'] = 0;
                }
                if(!isset($result_data[(string)$now_year][(string)$now_month]['一次'])) {
                    $result_data[(string)$now_year][(string)$now_month]['一次'] = 0;
                }
                if(!isset($result_data[(string)$now_year][(string)$now_month]['一次以上'])) {
                    $result_data[(string)$now_year][(string)$now_month]['一次以上'] = 0;
                }
                
                $now_001 = $result_data[(string)$now_year][(string)$now_month]['001'];
                $now_once = $result_data[(string)$now_year][(string)$now_month]['一次'];
                $now_upper = $result_data[(string)$now_year][(string)$now_month]['一次以上'];

                $total_count = $now_001 + $now_once + $now_upper;
                $array_001 = [
                    '月份'=> $month[$now_month],
                    '類型'=> 1 . "",
                    '總計數'=> $total_count . "",
                    '月度佔比'=> ($total_count == 0)?'0':round(($now_001*100 / $total_count), 2) . "%",
                    '次數'=> $now_001 . "",
                    '品號'=> '001'
                ];
                $array_once = [
                    '月份'=> $month[$now_month],
                    '類型'=> 2 . "",
                    '總計數'=> $total_count . "",
                    '月度佔比'=> ($total_count == 0)?'0':round(($now_once*100 / $total_count), 2) . "%",
                    '次數'=> $now_once . "",
                    '品號'=> '一次'
                ];
                $array_upper = [
                    '月份'=> $month[$now_month],
                    '類型'=> 3 . "",
                    '總計數'=> $total_count . "",
                    '月度佔比'=> ($total_count == 0)?'0%':round(($now_upper*100 / $total_count), 2)."%",
                    '次數'=> $now_upper . "",
                    '品號'=> '一次以上'
                ];

                array_push($result[(string)$now_year],$array_001);
                array_push($result[(string)$now_year],$array_once);
                array_push($result[(string)$now_year],$array_upper);
            }
        }

        if($called) {
            return $result;
        }

        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getcreateCilentOrderSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $db_data = $this->exportCilentOrder($request, $response, $args, true);
        $spreadsheet = $report->createExportCilentOrderSpreadsheet($db_data);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getProductItem($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $orders = $report->readProductItem($params);

        $result = [
            "data" => []
        ];
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $params['size'];
        $start = ($params['cur_page'] - 1) * $params['size'];
        foreach ($orders as $key => $order) {
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if ($length > 0 && $key >= $start) {
                array_push($result['data'], $order);
                $length--;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getProductItemPdf ($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);

        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];

        $show_date = $params['date_begin'] . '~' . $params['date_end'];

        $result = $report->readProductItem($params);

		$rows = "";
		foreach ($result as $key => $value) {
			$rows .= "<tr>";
			foreach ($value as $key => $each_value) {
				$rows .= "<td style=\"text-align:left\">{$each_value}</td>";
			}
			$rows .= "</tr>";
		}

		// create new PDF document
		$pdf = new TCPDF_chinese('L', PDF_UNIT, "A4", true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('mil');
		$pdf->SetTitle("訂單到製令～切割 品名類別佔比分析用(全部類別)");
		$pdf->SetSubject('訂單到製令～切割 品名類別佔比分析用(全部類別)pdf');
		$pdf->SetKeywords('TCPDF, PDF, mil');

		// remove default header/footer
		$pdf->setPrintHeader(false);
		// $pdf->setPrintFooter(false);

		// set header and footer fonts
		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		// $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
			require_once(dirname(__FILE__) . '/lang/eng.php');
			$pdf->setLanguageArray($l);
		}

		// ---------------------------------------------------------

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		// $pdf->SetFont('dejavusans', '', 14, '', true);

		// Set font
		$fontname = TCPDF_FONTS::addTTFfont(__DIR__ . DIRECTORY_SEPARATOR . '/fonts/droidsansfallback.ttf', 'TrueTypeUnicode', '', 96);

		// $pdf->addTTFfont('/Users/laichuanen/droidsansfallback.ttf'); 
		$pdf->SetFont($fontname, '', 9, '', false);
		// $pdf->SetFont('msungstdlight', '', 12);

		// 設定資料與頁面上方的間距 (依需求調整第二個參數即可)
		$pdf->SetMargins(5, 10, 5);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>{$show_date} 訂單到製令～切割 品名類別佔比分析用(全部類別)</h3>
		<table border="0.1" style="width:100%">
			<tr>
				<th style="width:6%">訂單日期</th>
				<th style="width:6%">客戶代號</th>
				<th style="width:6%">訂單單別</th>
				<th style="width:8%">訂單單號</th>
				<th style="width:6%">訂單序號</th>
				<th style="width:6%">訂單數量</th>
				<th style="width:6%">交易幣別</th>
				<th style="width:4%">金額</th>
				<th style="width:13%">品號</th>
				<th style="width:6%">品號切割&nbsp;9-10碼</th>
				<th style="width:6%">品號切割第17碼</th>
				<th style="width:7%">品名</th>
				<th style="width:6%">製令單別</th>
				<th style="width:8%">製令單號</th>
				<th style="width:6%">預計產量</th>
			</tr>
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("訂單到製令～切割 品名類別佔比分析用(全部類別){$show_date}.pdf");
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output($file_name, 'D');
    }

    public function getExportProductItem($request, $response, $args, $called = false)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        
        $now_year = intval(substr($params['date_begin'], 0,4));
        $now_month = (intval(substr($params['date_begin'], 6,1))-1)*3+1;
        $end_year = intval(substr($params['date_end'], 0,4));
        $end_month = (intval(substr($params['date_end'], 6,1))-1)*3+1;

        $group = ['00-業務', '01-切刀', '02-模組', '03-模仁', '04-模殼', '05-嵌入件', '06-前沖棒', 
        '07-後沖棒', '08-通孔沖棒', '09-套管', '10-墊塊', '11-沖棒固定塊', '12-公牙', 
        '13-夾子', '14-零件', '15-棘輪', '16-PIN', '17-通孔管', '18-其他'];
        
        //設定初始值
        $package_num = [];
        $package_num["type"] = '數量';
        $package_num["訂單筆數"] = '0';
        $package_num["訂單數量"] = '0';
        $package_num["訂單金額"] = '0';
        $package_percent = [];
        $package_percent["type"] = '百分比';
        $package_percent["訂單筆數"] = '0';
        $package_percent["訂單數量"] = '0';
        $package_percent["訂單金額"] = '0';
        foreach($group as $key => $value) {
            $package_num["訂單筆數{$key}"] = '0';
            $package_num["訂單數量{$key}"] = '0';
            $package_num["訂單金額{$key}"] = '0';
            $package_percent["訂單筆數{$key}"] = '0%';
            $package_percent["訂單數量{$key}"] = '0%';
            $package_percent["訂單金額{$key}"] = '0%';
        }

        $result = [];
        while($now_year<$end_year || ($now_year==$end_year && $now_month<=$end_month)) {
            $now_date = [
                'date_begin'=> $now_year*100 + $now_month . '00',
                'date_end'=> $now_year*100 + $now_month + 2 . '32'
            ];

            $now_package_num = $package_num;
            $now_package_percent = $package_percent;

            $date_print = (($now_month<10)?$now_year.'-0'.$now_month:$now_year.'-'.$now_month).'~'.(($now_month+2<10)?$now_year.'-0'.($now_month+2):$now_year.'-'.($now_month+2));
            $now_package_num['date'] = $date_print;
            $now_package_percent['date'] = $date_print;
            
            $db_result = $report->readExportProductItem($now_date);

            foreach($db_result['total'] as $key => $value) {
                $now_package_num["訂單筆數"] = $value['訂單筆數'];
                $now_package_num["訂單數量"] = $value['訂單數量'];
                $now_package_num["訂單金額"] = $value['訂單金額'];
                $now_package_percent["訂單筆數"] = $value['訂單筆數'];
                $now_package_percent["訂單數量"] = $value['訂單數量'];
                $now_package_percent["訂單金額"] = $value['訂單金額'];
            }

            foreach($db_result['data'] as $value) {
                $group_key = array_search($value['組合品名'], $group);
                $now_package_num["訂單筆數{$group_key}"] = $value['訂單筆數'];
                $now_package_num["訂單數量{$group_key}"] = $value['訂單數量'];
                $now_package_num["訂單金額{$group_key}"] = $value['訂單金額'];
                $now_package_percent["訂單筆數{$group_key}"] = round(($value['訂單筆數']*100 / $now_package_num['訂單筆數']), 2).'%';
                $now_package_percent["訂單數量{$group_key}"] = round(($value['訂單數量']*100 / $now_package_num['訂單數量']), 2).'%';
                $now_package_percent["訂單金額{$group_key}"] = round(($value['訂單金額']*100 / $now_package_num['訂單金額']), 2).'%';
            }

            array_push($result, [$now_package_num, $now_package_percent]);

            if($now_month == 10) {
                $now_year++;
                $now_month = 1;
            }
            else {
                $now_month += 3;
            }
        }

        if($called) {
            return $result;
        }

        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    
    public function exportExportProductItemPdf ($request, $response, $args){
        
        $params = $request->getQueryParams();

        $result = $this->getExportProductItem($request, $response, $args, true);

		$rows = "";
        $group = [];
		foreach ($result as $key => $value) {

            $group = [$value[0]['date'], '00-業務', '01-切刀', '02-模組', '03-模仁', '04-模殼', '05-嵌入件', '06-前沖棒', 
            '07-後沖棒', '08-通孔沖棒', '09-套管', '10-墊塊', '11-沖棒固定塊', '12-公牙', 
            '13-夾子', '14-零件', '15-棘輪', '16-PIN', '17-通孔管', '18-其他'];
            
            for($group_count=0; $group_count<20; $group_count+=5) {

                $rows .= "
                    <tr>
                        <td colspan=\"3\" style=\"text-align:center\">{$group[$group_count]}</td>
                        <td colspan=\"3\" style=\"text-align:center\">{$group[$group_count+1]}</td>
                        <td colspan=\"3\" style=\"text-align:center\">{$group[$group_count+2]}</td>
                        <td colspan=\"3\" style=\"text-align:center\">{$group[$group_count+3]}</td>
                        <td colspan=\"3\" style=\"text-align:center\">{$group[$group_count+4]}</td>
                    </tr>
                    <tr>
                ";
                
                for($i=0; $i<5; $i++) {
                    $rows .= " 
                        <td style=\"text-align:center\">訂單筆數</td>
                        <td style=\"text-align:center\">訂單數量</td>
                        <td style=\"text-align:center\">訂單金額(原幣)</td>
                    ";
                }
                
                $rows .= "
                    </tr>
                    <tr>
                ";

                for($i=0; $i<5; $i++) {
                    $count_name = '訂單筆數' . ($group_count+$i);
                    $number_name = '訂單數量' . ($group_count+$i);
                    $mount_name = '訂單金額' . ($group_count+$i);

                    if($group_count+$i != 0) {
                        $rows .= " 
                            <td  style=\"text-align:center\">{$value['0'][$count_name]}</td>
                            <td style=\"text-align:center\">{$value['0'][$number_name]}</td>
                            <td style=\"text-align:center\">{$value['0'][$mount_name]}</td>
                        ";
                    }
                    else {
                        $rows .= " 
                            <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['訂單筆數']}</td>
                            <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['訂單數量']}</td>
                            <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['訂單金額']}</td>
                        ";
                    }

                }

                $rows .= "
                    </tr>
                    <tr>
                ";

                for($i=0; $i<5; $i++) {
                    $count_name = '訂單筆數' . ($group_count+$i);
                    $number_name = '訂單數量' . ($group_count+$i);
                    $mount_name = '訂單金額' . ($group_count+$i);

                    if($group_count+$i != 0) {
                        $rows .= " 
                            <td style=\"text-align:center\">{$value['1'][$count_name]}</td>
                            <td style=\"text-align:center\">{$value['1'][$number_name]}</td>
                            <td style=\"text-align:center\">{$value['1'][$mount_name]}</td>
                        ";
                    }
                }

                $rows .= "
                    </tr>
                ";

            }

            if ($key != array_key_last($result)) {
                $rows .= "
                    </table>
                    <p></p>
                    <table border=\"0.1\" style=\"width:100%\">
                ";
            }
		}

		// create new PDF document
		$pdf = new TCPDF_chinese('L', PDF_UNIT, "A4", true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('mil');
		$pdf->SetTitle("訂單到製令～欲切割 品名類別 分析用");
		$pdf->SetSubject('訂單到製令～欲切割 品名類別 分析用pdf');
		$pdf->SetKeywords('TCPDF, PDF, mil');

		// remove default header/footer
		$pdf->setPrintHeader(false);

		// set header and footer fonts
		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		// $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
			require_once(dirname(__FILE__) . '/lang/eng.php');
			$pdf->setLanguageArray($l);
		}

		// ---------------------------------------------------------

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		// $pdf->SetFont('dejavusans', '', 14, '', true);

		// Set font
		$fontname = TCPDF_FONTS::addTTFfont(__DIR__ . DIRECTORY_SEPARATOR . '/fonts/droidsansfallback.ttf', 'TrueTypeUnicode', '', 96);

		// $pdf->addTTFfont('/Users/laichuanen/droidsansfallback.ttf'); 
		$pdf->SetFont($fontname, '', 12, '', false);
		// $pdf->SetFont('msungstdlight', '', 12);

		// 設定資料與頁面上方的間距 (依需求調整第二個參數即可)
		$pdf->SetMargins(10, 10, 10);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>訂單到製令～欲切割 品名類別 分析用</h3>
		<table border="0.1" style="width:100%">
		    {$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("訂單到製令～欲切割 品名類別 分析用.pdf");
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output($file_name, 'D');
    }

    public function getExportProductItemFixedCategory($request, $response, $args, $called = false)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        
        $now_year = intval(substr($params['date_begin'], 0,4));
        $now_month = (intval(substr($params['date_begin'], 6,1))-1)*3+1;
        $end_year = intval(substr($params['date_end'], 0,4));
        $end_month = (intval(substr($params['date_end'], 6,1))-1)*3+1;

        $group = ['00-業務', '01-切刀', '02-模組', '03-模仁', '04-模殼', '05-嵌入件', '06-前沖棒', 
        '07-後沖棒', '08-通孔沖棒', '09-套管', '10-墊塊', '11-沖棒固定塊', '12-公牙', 
        '13-夾子', '14-零件', '15-棘輪', '16-PIN', '17-通孔管', '18-其他'];
        
        //設定初始值
        $package_num = [];
        $package_num["type"] = '數量';
        $package_num["訂單筆數"] = '0';
        $package_num["訂單數量"] = '0';
        $package_num["訂單金額"] = '0';
        $package_num["訂單筆數碳化鎢"] = '0';
        $package_num["訂單數量碳化鎢"] = '0';
        $package_num["訂單金額碳化鎢"] = '0';
        $package_percent = [];
        $package_percent["type"] = '百分比';
        $package_percent["訂單筆數"] = '0';
        $package_percent["訂單數量"] = '0';
        $package_percent["訂單金額"] = '0';
        $package_percent["訂單筆數碳化鎢"] = '0%';
        $package_percent["訂單數量碳化鎢"] = '0%';
        $package_percent["訂單金額碳化鎢"] = '0%';
        foreach($group as $key => $value) {
            $package_num["訂單筆數{$key}"] = '0';
            $package_num["訂單數量{$key}"] = '0';
            $package_num["訂單金額{$key}"] = '0';
            $package_num["訂單筆數碳化鎢{$key}"] = '0';
            $package_num["訂單數量碳化鎢{$key}"] = '0';
            $package_num["訂單金額碳化鎢{$key}"] = '0';
            $package_percent["訂單筆數{$key}"] = '0%';
            $package_percent["訂單數量{$key}"] = '0%';
            $package_percent["訂單金額{$key}"] = '0%';
            $package_percent["訂單筆數碳化鎢{$key}"] = '0%';
            $package_percent["訂單數量碳化鎢{$key}"] = '0%';
            $package_percent["訂單金額碳化鎢{$key}"] = '0%';
        }

        $result = [];
        while($now_year<$end_year || ($now_year==$end_year && $now_month<=$end_month)) {
            $now_date = [
                'date_begin'=> $now_year*100 + $now_month . '00',
                'date_end'=> $now_year*100 + $now_month + 2 . '32'
            ];

            $now_package_num = $package_num;
            $now_package_percent = $package_percent;

            $date_print = (($now_month<10)?$now_year.'-0'.$now_month:$now_year.'-'.$now_month).'~'.(($now_month+2<10)?$now_year.'-0'.($now_month+2):$now_year.'-'.($now_month+2));
            $now_package_num['date'] = $date_print;
            $now_package_percent['date'] = $date_print;

            $db_result = $report->readExportProductItem($now_date);

            foreach($db_result['total'] as $key => $value) {
                $now_package_num["訂單筆數"] = $value['訂單筆數'];
                $now_package_num["訂單數量"] = $value['訂單數量'];
                $now_package_num["訂單金額"] = $value['訂單金額'];
                $now_package_percent["訂單筆數"] = $value['訂單筆數'];
                $now_package_percent["訂單數量"] = $value['訂單數量'];
                $now_package_percent["訂單金額"] = $value['訂單金額'];
            }

            foreach($db_result['data'] as $value) {
                $group_key = array_search($value['組合品名'], $group);
                $now_package_num["訂單筆數{$group_key}"] = $value['訂單筆數'];
                $now_package_num["訂單數量{$group_key}"] = $value['訂單數量'];
                $now_package_num["訂單金額{$group_key}"] = $value['訂單金額'];
                $now_package_percent["訂單筆數{$group_key}"] = round(($value['訂單筆數']*100 / $now_package_num['訂單筆數']), 2).'%';
                $now_package_percent["訂單數量{$group_key}"] = round(($value['訂單數量']*100 / $now_package_num['訂單數量']), 2).'%';
                $now_package_percent["訂單金額{$group_key}"] = round(($value['訂單金額']*100 / $now_package_num['訂單金額']), 2).'%';
            }
            
            $db_result_category = $report->readExportProductItemFixedCategory($now_date);

            foreach($db_result_category['total'] as $key => $value) {
                $now_package_num["訂單筆數碳化鎢"] = $value['訂單筆數'];
                $now_package_num["訂單數量碳化鎢"] = $value['訂單數量'];
                $now_package_num["訂單金額碳化鎢"] = $value['訂單金額'];
                $now_package_percent["訂單筆數碳化鎢"] = round(($value['訂單筆數']*100 / $now_package_num['訂單筆數']), 2).'%';
                $now_package_percent["訂單數量碳化鎢"] = round(($value['訂單數量']*100 / $now_package_num['訂單數量']), 2).'%';
                $now_package_percent["訂單金額碳化鎢"] = round(($value['訂單金額']*100 / $now_package_num['訂單金額']), 2).'%';
            }

            foreach($db_result_category['data'] as $value) {
                $group_key = array_search($value['組合品名'], $group);
                $now_package_num["訂單筆數碳化鎢{$group_key}"] = $value['訂單筆數'];
                $now_package_num["訂單數量碳化鎢{$group_key}"] = $value['訂單數量'];
                $now_package_num["訂單金額碳化鎢{$group_key}"] = $value['訂單金額'];
                $now_package_percent["訂單筆數碳化鎢{$group_key}"] = round(($value['訂單筆數']*100 / $now_package_num['訂單筆數碳化鎢']), 2).'%';
                $now_package_percent["訂單數量碳化鎢{$group_key}"] = round(($value['訂單數量']*100 / $now_package_num['訂單數量碳化鎢']), 2).'%';
                $now_package_percent["訂單金額碳化鎢{$group_key}"] = round(($value['訂單金額']*100 / $now_package_num['訂單金額碳化鎢']), 2).'%';
            }

            array_push($result, [$now_package_num, $now_package_percent]);

            if($now_month == 10) {
                $now_year++;
                $now_month = 1;
            }
            else {
                $now_month += 3;
            }
        }

        if($called) {
            return $result;
        }

        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getExportProductItemFixedCategorySpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $group = ['00-業務', '01-切刀', '02-模組', '03-模仁', '04-模殼', '05-嵌入件', '06-前沖棒', 
        '07-後沖棒', '08-通孔沖棒', '09-套管', '10-墊塊', '11-沖棒固定塊', '12-公牙', 
        '13-夾子', '14-零件', '15-棘輪', '16-PIN', '17-通孔管', '18-其他'];
        $db_data = $this->getExportProductItemFixedCategory($request, $response, $args, true);
        $spreadsheet = $report->createExportProductItemFixedCategorySpreadsheet($db_data, $group);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getExportProductItemSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $group = ['00-業務', '01-切刀', '02-模組', '03-模仁', '04-模殼', '05-嵌入件', '06-前沖棒', 
        '07-後沖棒', '08-通孔沖棒', '09-套管', '10-墊塊', '11-沖棒固定塊', '12-公牙', 
        '13-夾子', '14-零件', '15-棘輪', '16-PIN', '17-通孔管', '18-其他'];
        $db_data = $this->getExportProductItem($request, $response, $args, true);
        $spreadsheet = $report->createExportProductItemSpreadsheet($db_data, $group);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getProductItemFixedCategory($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $orders = $report->readProductItemFixedCategory($params);

        $result = [
            "data" => []
        ];
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $params['size'];
        $start = ($params['cur_page'] - 1) * $params['size'];
        foreach ($orders as $key => $order) {
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if ($length > 0 && $key >= $start) {
                array_push($result['data'], $order);
                $length--;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getProductItemFixedCategoryPdf ($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);

        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];

        $show_date = $params['date_begin'] . '~' . $params['date_end'];

        $result = $report->readProductItemFixedCategory($params);

		$rows = "";
		foreach ($result as $key => $value) {
			$rows .= "<tr>";
			foreach ($value as $key => $each_value) {
				$rows .= "<td style=\"text-align:left\">{$each_value}</td>";
			}
			$rows .= "</tr>";
		}

		// create new PDF document
		$pdf = new TCPDF_chinese('L', PDF_UNIT, "A4", true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('mil');
		$pdf->SetTitle("訂單到製令～切割 品號類別佔比分析用(全部類別-再篩碳化鎢)");
		$pdf->SetSubject('訂單到製令～切割 品號類別佔比分析用(全部類別-再篩碳化鎢)pdf');
		$pdf->SetKeywords('TCPDF, PDF, mil');

		// remove default header/footer
		$pdf->setPrintHeader(false);
		// $pdf->setPrintFooter(false);

		// set header and footer fonts
		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		// $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
			require_once(dirname(__FILE__) . '/lang/eng.php');
			$pdf->setLanguageArray($l);
		}

		// ---------------------------------------------------------

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		// $pdf->SetFont('dejavusans', '', 14, '', true);

		// Set font
		$fontname = TCPDF_FONTS::addTTFfont(__DIR__ . DIRECTORY_SEPARATOR . '/fonts/droidsansfallback.ttf', 'TrueTypeUnicode', '', 96);

		// $pdf->addTTFfont('/Users/laichuanen/droidsansfallback.ttf'); 
		$pdf->SetFont($fontname, '', 9, '', false);
		// $pdf->SetFont('msungstdlight', '', 12);

		// 設定資料與頁面上方的間距 (依需求調整第二個參數即可)
		$pdf->SetMargins(5, 10, 5);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>{$show_date} 訂單到製令～切割 品號類別佔比分析用(全部類別-再篩碳化鎢)</h3>
		<table border="0.1" style="width:100%">
			<tr>
				<th style="width:6%">訂單日期</th>
				<th style="width:6%">客戶代號</th>
				<th style="width:6%">訂單單別</th>
				<th style="width:8%">訂單單號</th>
				<th style="width:6%">訂單序號</th>
				<th style="width:6%">訂單數量</th>
				<th style="width:6%">交易幣別</th>
				<th style="width:4%">金額</th>
				<th style="width:13%">品號</th>
				<th style="width:6%">品號切割&nbsp;9-10碼</th>
				<th style="width:6%">品號切割第17碼</th>
				<th style="width:7%">品名</th>
				<th style="width:6%">製令單別</th>
				<th style="width:8%">製令單號</th>
				<th style="width:6%">預計產量</th>
			</tr>
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("訂單到製令～切割 品號類別佔比分析用(全部類別-再篩碳化鎢){$show_date}.pdf");
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output($file_name, 'D');
    }

    public function getTurnoverChange($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $orders = $report->readTurnoverChange($params);
        foreach ($orders as $key => $value) {
            if ($value['單價'] - intval($value['單價']) == 0) {
                $orders[$key]['單價'] = strval(intval($value['單價']));
            }
            if ($value['金額'] - intval($value['金額']) == 0) {
                $orders[$key]['金額'] = strval(intval($value['金額']));
            }
        }

        $result = [
            "data" => []
        ];
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $params['size'];
        $start = ($params['cur_page'] - 1) * $params['size'];
        foreach ($orders as $key => $order) {
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if ($length > 0 && $key >= $start) {
                array_push($result['data'], $order);
                $length--;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getOuterSecantComparison($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $orders = $report->readOuterSecantComparison($params);

        $result = [
            "data" => []
        ];
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $params['size'];
        $start = ($params['cur_page'] - 1) * $params['size'];
        foreach ($orders as $key => $order) {
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if ($length > 0 && $key >= $start) {
                array_push($result['data'], $order);
                $length--;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getOuterSecantComparisonPdf ($request, $response, $args){
        $params = $request->getQueryParams();
        $report = new report($this->container->db);

        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];

        $show_date = $params['date_begin'] . '~' . $params['date_end'];

        $result = $report->readOuterSecantComparison($params);

		$rows = "";
		foreach ($result as $key => $value) {
			$rows .= "<tr>";
			foreach ($value as $key => $each_value) {
				$rows .= "<td style=\"text-align:left\">{$each_value}</td>";
			}
			$rows .= "</tr>";
		}

		// create new PDF document
		$pdf = new TCPDF_chinese('L', PDF_UNIT, "A4", true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('mil');
		$pdf->SetTitle("各線別生產數量統計表");
		$pdf->SetSubject('各線別生產數量統計表pdf');
		$pdf->SetKeywords('TCPDF, PDF, mil');

		// set default header data
		// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
		// $pdf->SetHeaderData(array(0,64,255), array(0,64,128));
		// $pdf->setFooterData(array(0,64,0), array(0,64,128));

		// remove default header/footer
		$pdf->setPrintHeader(false);
		// $pdf->setPrintFooter(false);

		// set header and footer fonts
		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		// $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
			require_once(dirname(__FILE__) . '/lang/eng.php');
			$pdf->setLanguageArray($l);
		}

		// ---------------------------------------------------------

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		// $pdf->SetFont('dejavusans', '', 14, '', true);

		// Set font
		$fontname = TCPDF_FONTS::addTTFfont(__DIR__ . DIRECTORY_SEPARATOR . '/fonts/droidsansfallback.ttf', 'TrueTypeUnicode', '', 96);

		// $pdf->addTTFfont('/Users/laichuanen/droidsansfallback.ttf'); 
		$pdf->SetFont($fontname, '', 9, '', false);
		// $pdf->SetFont('msungstdlight', '', 12);

		// 設定資料與頁面上方的間距 (依需求調整第二個參數即可)
		$pdf->SetMargins(10, 10, 10);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>{$show_date} 各線別生產數量統計表</h3>
		<table border="0.1" style="width:100%">
			<tr>
				<th style="width:7%">單據日期</th>
				<th style="width:7%">移轉單單別</th>
				<th style="width:9%">移轉單單號</th>
				<th style="width:7%">移轉單序號</th>
				<th style="width:6%">移出部門</th>
				<th style="width:8%">移出部門名稱</th>
				<th style="width:6%">移出製程</th>
				<th style="width:6%">製程名稱</th>
				<th style="width:6%">製令單別</th>
				<th style="width:9%">製令單號</th>
				<th style="width:6%">移轉數量</th>
				<th style="width:6%">驗收數量</th>
				<th style="width:6%">報廢數量</th>
				<th style="width:6%">不良數量</th>
				<th style="width:6%">驗退數量</th>
			</tr>
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("各線別生產數量統計表{$show_date}.pdf");
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output($file_name, 'D');
    }

    public function getOrderCurrencyStatistics($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $orders = $report->readOrderCurrencyStatistics($params);
        foreach ($orders as $key => $value) {
            if ($value['訂單金額'] - intval($value['訂單金額']) == 0) {
                $orders[$key]['訂單金額'] = strval(intval($value['訂單金額']));
            }
        }

        $result = [
            "data" => []
        ];
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        
        $length = $params['size'];
        $start = ($params['cur_page'] - 1) * $params['size'];
        
        foreach ($orders as $key => $order) {
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if ($length > 0 && $key >= $start) {
                array_push($result['data'], $order);
                $length--;
            }
        }
        
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getOrderCurrencyStatisticsPdf ($request, $response, $args){
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];

        $show_date = $params['date_begin'] . '~' . $params['date_end'];

        $result = $report->readOrderCurrencyStatistics($params);
        foreach ($result as $key => $value) {
            if ($value['訂單金額'] - intval($value['訂單金額']) == 0) {
                $result[$key]['訂單金額'] = strval(intval($value['訂單金額']));
            }
        }
        
        // $response = $response->withHeader('Content-type', 'application/json');
        // $response = $response->withJson($result);
        // return $response;

		$rows = "";
		foreach ($result as $key => $value) {
			$rows .= "<tr>";
			foreach ($value as $key => $each_value) {
				$rows .= "<td style=\"text-align:left\">{$each_value}</td>";
			}
			$rows .= "</tr>";
		}

		// create new PDF document
		$pdf = new TCPDF_chinese('L', PDF_UNIT, "A4", true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('mil');
		$pdf->SetTitle("業務月度接單統計-幣別明細");
		$pdf->SetSubject('業務月度接單統計-幣別明細pdf');
		$pdf->SetKeywords('TCPDF, PDF, mil');

		// set default header data
		// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
		// $pdf->SetHeaderData(array(0,64,255), array(0,64,128));
		// $pdf->setFooterData(array(0,64,0), array(0,64,128));

		// remove default header/footer
		$pdf->setPrintHeader(false);
		// $pdf->setPrintFooter(false);

		// set header and footer fonts
		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		// $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
			require_once(dirname(__FILE__) . '/lang/eng.php');
			$pdf->setLanguageArray($l);
		}

		// ---------------------------------------------------------

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		// $pdf->SetFont('dejavusans', '', 14, '', true);

		// Set font
		$fontname = TCPDF_FONTS::addTTFfont(__DIR__ . DIRECTORY_SEPARATOR . '/fonts/droidsansfallback.ttf', 'TrueTypeUnicode', '', 96);

		// $pdf->addTTFfont('/Users/laichuanen/droidsansfallback.ttf'); 
		$pdf->SetFont($fontname, '', 9, '', false);
		// $pdf->SetFont('msungstdlight', '', 12);

		// 設定資料與頁面上方的間距 (依需求調整第二個參數即可)
		$pdf->SetMargins(10, 10, 10);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>{$show_date} 業務月度接單統計-幣別明細</h3>
		<table border="0.1" style="width:100%">
			<tr>
				<th style="width:6%">訂單日期</th>
				<th style="width:6%">訂單單別</th>
				<th style="width:9%">訂單單號</th>
				<th style="width:6%">訂單序號</th>
				<th style="width:7%">客戶代號</th>
				<th style="width:14%">品號</th>
				<th style="width:18%">客戶圖號</th>
				<th style="width:6%">訂單數量</th>
				<th style="width:10%">客戶簡稱</th>
				<th style="width:6%">訂單單價</th>
				<th style="width:6%">訂單金額</th>
				<th style="width:6%">交易幣別</th>
			</tr>
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("業務月度接單統計-幣別明細.pdf");
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output($file_name, 'D');
    }

    public function getAddDelivery($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $params = $report->convertDateFormat($params);
        $result = $report->readAddDelivery($params);
        $plant = 0;
        $order = 0;
        $fonsite = 0;
        $foutsite = 0;
        $week = 0;
        $week_count = 0;
        $merge = [];
        foreach ($result as $key => $value) {
            $plant += intval($value['盤數']);
            $order += intval($value['訂單數量']);
            if ($key < 5) {
                $fonsite += intval($value['現場完成量']);
                $foutsite += intval($value['外注完成量']);
            }
            if ($key == sizeof($result) - 1) {
                $week_count += 1;
                array_push($merge, $week_count);
                $week_count = 1;
                $week = intval($value['週數']);
            } else if (intval($value['週數']) == $week) {
                $week_count += 1;
            } else {
                array_push($merge, $week_count);
                $week_count = 1;
                $week = intval($value['週數']);
            }
        }
        array_unshift($result, ["週數",    "預計生產完成日", "盤數", "訂單數量", "現場完成量", "外注完成量"]);
        array_unshift($result, ["迄", $params['end']]);
        array_unshift($result, ["起", $params['start']]);
        array_push($result, ["總盤數", $plant]);
        array_push($result, ["總訂單數量", $order]);
        array_push($result, ["五日平均現場完成量", round($fonsite / 5)]);
        array_push($result, ["五日平均外注完成量", round($foutsite / 5)]);
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($result, NULL, 'A1');
        $start = 4;
        $end = 4;
        unset($merge[0]);
        foreach ($merge as $key => $value) {
            $end = $start + $value - 1;
            if ($start != $end) {
                $sheet->mergeCells("A{$start}:A{$end}");
            }
            $start = $end + 1;
        }
        $params['filename'] = '預計生產報表';
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function exportTurnoverChange($request, $response, $args, $spreadsheet=false)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $params = $report->convertDateFormat($params);
        $params['start'] = $params['start'] . '0101';
        $params['end'] = $params['end'] . '1231';
        $data = $report->readTurnoverChange($params);
        $result = [];
        foreach ($data as $key => $value) {
            if (!isset($result[substr($value['訂單日期'], 0, 4)])) {
                $result[substr($value['訂單日期'], 0, 4)] = [];
            }
            if (!isset($result[substr($value['訂單日期'], 0, 4)][$value['交易幣別']])) {
                $result[substr($value['訂單日期'], 0, 4)][$value['交易幣別']] = [];
            }
            if (!isset($result[substr($value['訂單日期'], 0, 4)][$value['交易幣別']]['Q' . ceil(intval(substr($value['訂單日期'], 4)) / 331)])) {
                $result[substr($value['訂單日期'], 0, 4)][$value['交易幣別']]['Q' . ceil(intval(substr($value['訂單日期'], 4)) / 331)] = 0;
            }
            $result[substr($value['訂單日期'], 0, 4)][$value['交易幣別']]['Q' . ceil(intval(substr($value['訂單日期'], 4)) / 331)] += intval($value['金額']);
        }
        $result_fin = [];
        foreach ($result as $key => $value) {
            $result_tmp = [];
            foreach ($result[$key] as $key_ => $value_) {
                $result[$key][$key_]['currency'] = $key_;
                array_push($result_tmp, $result[$key][$key_]);
            }
            array_push($result_fin, $result_tmp);
        }
        if($spreadsheet){
            return $result_fin;
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result_fin);
        return $response;
    }
    public function exportSpreadsheet($params, $spreadsheet)
    {
        if (isset($params['filename'])) {
            $filename = "{$params['filename']}.{$params['extension']}";
        } else {
            $filename = "spreadsheet.{$params['extension']}";
        }
        switch ($params['extension']) {
            case 'xlsx':
                $writer = new Xlsx($spreadsheet);
                break;
            case 'xls':
                $writer = new Xls($spreadsheet);
                break;
            case 'csv':
                $writer = new Csv($spreadsheet);
                $writer->setUseBOM(true);
                break;
        }
        $writer->save($this->container->upload_directory . $filename);
        $file_content = file_get_contents($this->container->upload_directory . $filename);
        header("Content-Disposition: attachment; filename={$filename}");
        unlink($this->container->upload_directory . $filename);
        exit($file_content);
    }

    public function getStaffProductivitySpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $db_data = $report->readAllStaffProductivity($params);
        $spreadsheet = $report->createSpreadsheet($db_data);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getStaffProductivityPdf($request, $response, $args)
    {
		$params = $request->getQueryParams();
        $report = new report($this->container->db);
        
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];

        $show_date = $params['date_begin'] . " ~ " . $params['date_end'];

        $result = $report->readAllStaffProductivity($params);

		$rows = "";
		foreach ($result as $key => $value) {
			$rows .= "<tr>";
			foreach ($value as $key => $each_value) {
				$rows .= "<td style=\"border:0.1px solid black;\">{$each_value}</td>";
			}
			$rows .= "</tr>";
		}

		// create new PDF document
		$pdf = new TCPDF_chinese('L', PDF_UNIT, "A4", true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('mil');
		$pdf->SetTitle("人員別生產數量明細");
		$pdf->SetSubject('人員別生產數量明細pdf');
		$pdf->SetKeywords('TCPDF, PDF, mil');

		// set default header data
		// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
		// $pdf->SetHeaderData(array(0,64,255), array(0,64,128));
		// $pdf->setFooterData(array(0,64,0), array(0,64,128));

		// remove default header/footer
		$pdf->setPrintHeader(false);
		// $pdf->setPrintFooter(false);

		// set header and footer fonts
		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		// $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
			require_once(dirname(__FILE__) . '/lang/eng.php');
			$pdf->setLanguageArray($l);
		}

		// ---------------------------------------------------------

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		// $pdf->SetFont('dejavusans', '', 14, '', true);

		// Set font
		$fontname = TCPDF_FONTS::addTTFfont(__DIR__ . DIRECTORY_SEPARATOR . '/fonts/droidsansfallback.ttf', 'TrueTypeUnicode', '', 96);

		// $pdf->addTTFfont('/Users/laichuanen/droidsansfallback.ttf'); 
		$pdf->SetFont($fontname, '', 12, '', false);
		// $pdf->SetFont('msungstdlight', '', 12);

		// 設定資料與頁面上方的間距 (依需求調整第二個參數即可)
		$pdf->SetMargins(10, 5, 10);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>{$show_date} 人員別生產數量明細</h3>
		<table border="2" style="width:100%">
			<tr>
				<th style="border:0.1px solid black; width:8%">工號</th>
				<th style="border:0.1px solid black; width:8%">姓名</th>
				<th style="border:0.1px solid black; width:8%">單據日期</th>
				<th style="border:0.1px solid black; width:12%">移轉單單號</th>
				<th style="border:0.1px solid black; width:7%">移轉單單別</th>
				<th style="border:0.1px solid black; width:8%">移出部門</th>
				<th style="border:0.1px solid black; width:17%">移出部門名稱</th>
				<th style="border:0.1px solid black; width:8%">移轉數量</th>
				<th style="border:0.1px solid black; width:8%">驗收數量</th>
				<th style="border:0.1px solid black; width:8%">不良數量</th>
				<th style="border:0.1px solid black; width:8%">報廢數量</th>
			</tr>
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("人員別生產數量明細.pdf");
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output($file_name, 'D');
    }

    public function getOrderCurrencyStatisticsSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);

        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $db_data = $report->readOrderCurrencyStatistics($params);
        $spreadsheet = $report->createSpreadsheet($db_data);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getItemCategoryAnalysisSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];

        $db_data = $report->readItemCategoryAnalysis($params);
        $spreadsheet = $report->createSpreadsheet($db_data);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getCilentOrderSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $params = $report->convertDateFormat($params);
        $db_data = $report->readCilentOrder($params);
        $spreadsheet = $report->createSpreadsheet($db_data);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getProductItemSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];

        $db_data = $report->readProductItem($params);
        $spreadsheet = $report->createSpreadsheet($db_data);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getProductItemFixedCategorySpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $db_data = $report->readProductItemFixedCategory($params);
        $spreadsheet = $report->createSpreadsheet($db_data);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function exportOrderCurrencyStatistics($request, $response, $args, $call = false)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $params['date_begin'] = $params['startYear'] . str_pad($params['startMonth'], 2, "0", STR_PAD_LEFT) . '01';
        $params['date_end'] = $params['endYear'] . str_pad($params['endMonth'], 2, "0", STR_PAD_LEFT) . '31';
        $data = $report->readOrderCurrencyStatistics($params);
        $result = [];
        $year_arr = [];
        date_default_timezone_set("Asia/Taipei");
        $now_year = date('Y');

        for ($i = $params['startYear']; $i <= $params['endYear']; $i++) {
            if($i <= $now_year) {
                array_push($year_arr, $i);
            }
        }
        for ($i = $params['startMonth']; $i <= $params['endMonth']; $i++) {
            $result[str_pad($i, 2, "0", STR_PAD_LEFT)] = [];
            $result[str_pad($i, 2, "0", STR_PAD_LEFT)]['SUM'] = [];
        }
        foreach ($data as $key => $value) {
            if (isset($result[substr($value['訂單日期'], 4, 2)])) {
                if (!isset($result[substr($value['訂單日期'], 4, 2)][$value['交易幣別']])) {
                    $result[substr($value['訂單日期'], 4, 2)][$value['交易幣別']] = [];
                }
                if (!isset($result[substr($value['訂單日期'], 4, 2)][$value['交易幣別']][array_search(substr($value['訂單日期'], 0, 4), $year_arr) + 1])) {
                    $result[substr($value['訂單日期'], 4, 2)][$value['交易幣別']][array_search(substr($value['訂單日期'], 0, 4), $year_arr) + 1] = 0;
                }
                if (!isset($result[substr($value['訂單日期'], 4, 2)]['SUM'][array_search(substr($value['訂單日期'], 0, 4), $year_arr) + 1])) {
                    $result[substr($value['訂單日期'], 4, 2)]['SUM'][array_search(substr($value['訂單日期'], 0, 4), $year_arr) + 1] = 0;
                }
                if (!isset($result[substr($value['訂單日期'], 4, 2)][$value['交易幣別']]['compare'])) {
                    $result[substr($value['訂單日期'], 4, 2)][$value['交易幣別']]['compare'] = 0;
                }
                $result[substr($value['訂單日期'], 4, 2)][$value['交易幣別']][array_search(substr($value['訂單日期'], 0, 4), $year_arr) + 1] += intval($value['訂單金額']);
                $result[substr($value['訂單日期'], 4, 2)][$value['交易幣別']]['compare'] += intval($value['訂單金額']);
                $result[substr($value['訂單日期'], 4, 2)]['SUM'][array_search(substr($value['訂單日期'], 0, 4), $year_arr) + 1] += intval($value['訂單金額']);
            }
        }

        foreach ($result as $key => $value) {
            $result_tmp = [];
            $result_tmp['expandedData'] = [];
            foreach ($result[$key] as $key_ => $value_) {
                if(isset($result[$key][$key_][count($year_arr)-1])) {
                    $result[$key][$key_]['compare'] = round($result[$key][$key_][count($year_arr)] * 100 / $result[$key][$key_][count($year_arr) - 1], 1) . '%';
                }
                else {
                    $result[$key][$key_]['compare'] = '0%';
                }
                $result[$key][$key_]['type'] = $key_;
                array_push($result[$key], $result[$key][$key_]);
                unset($result[$key][$key_]);
            }
            array_push($result_tmp['expandedData'], $result[$key]);
            $result_tmp['expandedData'] = $result_tmp['expandedData'][0];
            array_push($result_tmp['expandedData'], $result_tmp['expandedData'][0]);
            array_splice($result_tmp['expandedData'], 0, 1);
            $result_tmp['key'] = intval($key);
            array_push($result, $result_tmp);
            unset($result[$key]);
        }
        if ($call === true) {
            return $result;
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function exportStaffProductivity($request, $response, $args, $call = false)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);

        $params['start_ori'] = '2019-01-01';
        $params['end_ori'] = '2019-01-07';
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
            $params['start_ori'] = $params['date_begin'];
            $params['end_ori'] = $params['date_end'];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $data = $report->readExportStaffProductivity($params);

        $uid = [];
        $result = [];
        $result_date = [];
        $result_staff = [];
        $week_arr = array("日", "一", "二", "三", "四", "五", "六");
        $date_begin = explode("-", $params['start_ori']);
        $date_end = explode("-", $params['end_ori']);
        $date_begin = mktime(0, 0, 0, intval($date_begin[1]), intval($date_begin[2]), intval($date_begin[0]));
        $date_end = mktime(0, 0, 0, intval($date_end[1]), intval($date_end[2]), intval($date_end[0]));
        $days = round(($date_end - $date_begin) / 3600 / 24);
        $now = date('Y-m-d', strtotime($params['start_ori']));
        for ($i = 1; $i <= $days + 1; $i++) {
            array_push($result_date, ['date' => str_replace("-", "/", $now), 'weekdays' => $week_arr[date("w", strtotime($now))]]);
            $now = date('Y-m-d', strtotime(" 1 day", strtotime($now)));
        }
        foreach ($data['data'] as $key => $value) {
            $now_date = mktime(0, 0, 0, intval(substr($value['單據日期'], 4, 2)), intval(substr($value['單據日期'], 6, 2)), intval(substr($value['單據日期'], 0, 4)));
            $now_days = round(($now_date - $date_begin) / 3600 / 24) + 1;

            if (in_array(($value['工號'].$value['移出部門']), $uid)) {
                if(!isset($result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2][$now_days])) {
                    $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2][$now_days] = 0;
                }
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2][$now_days] += $value['移轉單單號'];
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2]['total'] += $value['移轉單單號'];
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2]['days'] += 1;
                if(!isset($result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1][$now_days])) {
                    $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1][$now_days] = 0;
                }
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1][$now_days] += $value['移轉數量'];
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1]['total'] += $value['移轉數量'];
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1]['days'] += 1;
            } else {
                array_push($result_staff, ['uid' => $value['工號'], 'name' => $value['姓名'], 'line' => $value['移出部門名稱'], 'type' => '盤數', "{$now_days}" => $value['移轉單單號'], 'total' => $value['移轉單單號'], 'days' => 1, 'avg' => 0]);
                array_push($result_staff, ['uid' => $value['工號'], 'name' => $value['姓名'], 'line' => $value['移出部門名稱'], 'type' => '移轉數量', "{$now_days}" => $value['移轉數量'], 'total' => $value['移轉數量'], 'days' => 1, 'avg' => 0]);
                array_push($uid, ($value['工號'].$value['移出部門']));
            }

        }
        foreach ($result_staff as $key => $value) {
            $result_staff[$key]['avg'] = round($result_staff[$key]['total'] / $result_staff[$key]['days'], 2);
        }

        $result = [
            "data" => []
        ];
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $params['cur_page'] * $params['size'];
        $start = $length - $params['size'];
        foreach ($result_staff as $key => $order) {
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if ($length > 0 && $key >= $start) {
                array_push($result['data'], $order);
                $length--;
            }
        }

        $result['result_date'] = $result_date;

        if ($call === true) {
            return $result;
        }

        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getExportStaffProductivityPdf($request, $response, $args, $call = false)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);

        $params['start_ori'] = '2019-01-01';
        $params['end_ori'] = '2019-01-07';
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
            $params['start_ori'] = $params['date_begin'];
            $params['end_ori'] = $params['date_end'];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $data = $report->readExportStaffProductivity($params);
        $show_date = $params['date_begin'] . ' ~ ' . $params['date_end'];
        $uid = [];

        $result_date = [];
        $result_staff = [];
        $week_arr = array("日", "一", "二", "三", "四", "五", "六");
        $date_begin = explode("-", $params['start_ori']);
        $date_end = explode("-", $params['end_ori']);
        $date_begin = mktime(0, 0, 0, intval($date_begin[1]), intval($date_begin[2]), intval($date_begin[0]));
        $date_end = mktime(0, 0, 0, intval($date_end[1]), intval($date_end[2]), intval($date_end[0]));
        $days = round(($date_end - $date_begin) / 3600 / 24);
        $now = date('Y-m-d', strtotime($params['start_ori']));
        for ($i = 1; $i <= $days + 1; $i++) {
            array_push($result_date, ['date' => str_replace("-", "/", $now), 'weekdays' => $week_arr[date("w", strtotime($now))]]);
            $now = date('Y-m-d', strtotime(" 1 day", strtotime($now)));
        }
        foreach ($data['data'] as $key => $value) {
            $now_date = mktime(0, 0, 0, intval(substr($value['單據日期'], 4, 2)), intval(substr($value['單據日期'], 6, 2)), intval(substr($value['單據日期'], 0, 4)));
            $now_days = round(($now_date - $date_begin) / 3600 / 24) + 1;

            if (in_array(($value['工號'].$value['移出部門']), $uid)) {
                if(!isset($result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2][$now_days])) {
                    $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2][$now_days] = 0;
                }
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2][$now_days] += $value['移轉單單號'];
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2]['total'] += $value['移轉單單號'];
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2]['days'] += 1;
                if(!isset($result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1][$now_days])) {
                    $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1][$now_days] = 0;
                }
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1][$now_days] += $value['移轉數量'];
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1]['total'] += $value['移轉數量'];
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1]['days'] += 1;
            } else {
                array_push($result_staff, ['uid' => $value['工號'], 'name' => $value['姓名'], 'line' => $value['移出部門名稱'], 'type' => '盤數', "{$now_days}" => $value['移轉單單號'], 'total' => $value['移轉單單號'], 'days' => 1, 'avg' => 0]);
                array_push($result_staff, ['uid' => $value['工號'], 'name' => $value['姓名'], 'line' => $value['移出部門名稱'], 'type' => '移轉數量', "{$now_days}" => $value['移轉數量'], 'total' => $value['移轉數量'], 'days' => 1, 'avg' => 0]);
                array_push($uid, ($value['工號'].$value['移出部門']));
            }

        }
        foreach ($result_staff as $key => $value) {
            $result_staff[$key]['avg'] = round($result_staff[$key]['total'] / $result_staff[$key]['days'], 0);
        }

        $days = "";
        
        $weeks = "<tr><td>星期</td>";
        foreach ($result_date as $key => $value) {  
            $days .= '<th>' . $value['date'] . '</th>';
            $weeks .= '<td>(' . $value['weekdays'] . ')</td>';
        }
        $weeks .= '</tr>';

        $rows = $weeks;
        $count_day = count($result_date);

        $rowspan_count = 0;
		foreach ($result_staff as $key => $value) {
            $rows .= "<tr>";

            if($rowspan_count == 0) {

                $now_uid = $result_staff[$key]['uid'];
                for($tmp_key=$key; $result_staff[$tmp_key]['uid']==$now_uid; $tmp_key++) {
                    $rowspan_count ++;
                }

                if($key % 2 == 0) {
                    $rows .= "<td rowspan=\"{$rowspan_count}\">". $value['uid'] ."</td>";
                    $rows .= "<td rowspan=\"{$rowspan_count}\">". $value['name'] ."</td>";
                    $rows .= "<td rowspan=\"2\">". $value['line'] ."</td>";
                    $rows .= "<td style=\"text-align:left\">". $value['type'] ."</td>";
                    for($i=1; $i<=$count_day; $i++) {
                        if(isset($value[$i])) {
                            $rows .= "<td style=\"text-align:right\">". $value[$i] ."</td>";
                        }
                        else {
                            $rows .= "<td></td>";
                        }
                    }
                }
                else {
                    $rows .= "<td style=\"text-align:left\">". $value['type'] ."</td>";
                    for($i=1; $i<=$count_day; $i++) {
                        if(isset($value[$i])) {
                            $rows .= "<td style=\"text-align:right\">". $value[$i] ."</td>";
                        }
                        else {
                            $rows .= "<td></td>";
                        }
                    }
                }
            }
            else {
                if($key % 2 == 0) {
                    $rows .= "<td rowspan=\"2\">". $value['line'] ."</td>";
                    $rows .= "<td style=\"text-align:left\">". $value['type'] ."</td>";
                    for($i=1; $i<=$count_day; $i++) {
                        if(isset($value[$i])) {
                            $rows .= "<td style=\"text-align:right\">". $value[$i] ."</td>";
                        }
                        else {
                            $rows .= "<td></td>";
                        }
                    }
                }
                else {
                    $rows .= "<td style=\"text-align:left\">". $value['type'] ."</td>";
                    for($i=1; $i<=$count_day; $i++) {
                        if(isset($value[$i])) {
                            $rows .= "<td style=\"text-align:right\">". $value[$i] ."</td>";
                        }
                        else {
                            $rows .= "<td></td>";
                        }
                    }
                }
            }

            $rows .= "<td style=\"text-align:right\">". $value['total'] ."</td>";
            $rows .= "<td style=\"text-align:right\">". $value['days'] ."</td>";
            $rows .= "<td style=\"text-align:right\">". $value['avg'] ."</td>";
			$rows .= "</tr>";

            $rowspan_count --;
		}

        $rows .= "<tr>";
        if(isset($params['line'])) {
            $rows .= "<td rowspan=\"2\" colspan=\"3\">". $params['line'] ."線別總和</td>";
        }
        else {
            $rows .= "<td rowspan=\"2\" colspan=\"3\">線別總和</td>";
        }

        $row_bottom = "<tr>";
        $rows .= "<td>盤數</td>";
        $row_bottom .= "<td>生產數量</td>";
        $up_total = 0;
        $bottom_total = 0;

        $total_days = round(($date_end - $date_begin) / 3600 / 24) + 1;

        $prevdate = 0;
        foreach ($data['sum'] as $key => $value) {
            $now_date = mktime(0, 0, 0, intval(substr($value['單據日期'], 4, 2)), intval(substr($value['單據日期'], 6, 2)), intval(substr($value['單據日期'], 0, 4)));
            $now_days = round(($now_date - $date_begin) / 3600 / 24) + 1;

            while($now_days - $prevdate > 1) {
                $rows .= "<td style=\"text-align:right\">0</td>";
                $row_bottom .= "<td style=\"text-align:right\">0</td>";
                $prevdate ++;
            }

            $rows .= "<td style=\"text-align:right\">". $value['移轉單單號'] ."</td>";
            $row_bottom .= "<td style=\"text-align:right\">". $value['移轉數量'] ."</td>";

            $up_total += intval($value['移轉單單號']);
            $bottom_total += intval($value['移轉數量']);

            $prevdate ++;
        }
        
        $rows .= "<td style=\"text-align:right\">". $up_total ."</td>";
        $row_bottom .= "<td style=\"text-align:right\">". $bottom_total ."</td>";

        $rows .= "<td></td>";
        $row_bottom .= "<td style=\"text-align:right\">". $total_days ."</td>";
        $rows .= "<td></td>";
        $row_bottom .= "<td style=\"text-align:right\">". round($bottom_total / $total_days, 0) ."</td>";
        $row_bottom .= "</tr>";
        $rows .= "</tr>" . $row_bottom;

		$pdf = new TCPDF_chinese('L', PDF_UNIT, "A4", true, 'UTF-8', false);

		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('mil');
		$pdf->SetTitle("人員別生產數量統計");
		$pdf->SetSubject('人員別生產數量統計pdf');
		$pdf->SetKeywords('TCPDF, PDF, mil');

		$pdf->setPrintHeader(false);

		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
			require_once(dirname(__FILE__) . '/lang/eng.php');
			$pdf->setLanguageArray($l);
		}

		$pdf->setFontSubsetting(true);
		$fontname = TCPDF_FONTS::addTTFfont(__DIR__ . DIRECTORY_SEPARATOR . '/fonts/droidsansfallback.ttf', 'TrueTypeUnicode', '', 96);
		
        $fontsize = 10;
        if($count_day>27) {
            $fontsize = 5;
        }
        else if($count_day>21) {
            $fontsize = 6;
        }
        else if($count_day>17){
            $fontsize = 8;
        }
        $pdf->SetFont($fontname, '', $fontsize, '', false);

        
		$pdf->SetMargins(5, 7, 5);

		$pdf->AddPage();

		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		$html = <<<EOD
		<h3>{$show_date} 人員別生產數量統計</h3>
		<table border="0.1" style="width:100%">
			<tr>
				<th rowspan="2">工號</th>
				<th rowspan="2">姓名</th>
				<th rowspan="2">線別</th>
				<th>日期</th>
				{$days}
				<th rowspan="2">總和</th>
				<th rowspan="2">天數</th>
				<th rowspan="2">平均值</th>
			</tr>
			{$rows}
		</table>
		EOD;

		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("人員別生產數量統計.pdf");
		$pdf->Output($file_name, 'D');
    }

    public function exportItemCategoryAnalysis($request, $response, $args, $call = false)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        for ($request_year = $params['start']; $request_year <= $params['end']; $request_year++) {
            $per_year_count = [];
            $per_year_percent = [];
            $db_data = $report->readExportItemCategoryAnalysis($request_year);
            
            $per_year_count["type"] = "一般";
            $per_year_count["訂單計數"] = $db_data['year_total']['count'];
            $per_year_count["訂單數量"] = $db_data['year_total']['sum']?$db_data['year_total']['sum']:'0';
            $per_year_count["製令盤數"] = $db_data['year_total']['discs_count'];
            $per_year_count["預計產量"] = $db_data['year_total']['predict_sum']?$db_data['year_total']['predict_sum']:'0';
            $per_year_percent["type"] = "百分比";
            $per_year_percent["訂單計數"] = $db_data['year_total']['count'];
            $per_year_percent["訂單數量"] = $db_data['year_total']['sum']?$db_data['year_total']['sum']:'0';
            $per_year_percent["製令盤數"] = $db_data['year_total']['discs_count'];
            $per_year_percent["預計產量"] = $db_data['year_total']['predict_sum']?$db_data['year_total']['predict_sum']:'0';
            foreach ($db_data['each_item'] as $key => $value) {
                $index = $key + 1;
                $per_year_count["訂單計數{$index}"] = $value['count'];
                $per_year_count["訂單數量{$index}"] = $value['sum'];
                $per_year_percent["訂單計數{$index}"] = $value['count_percent'];
                $per_year_percent["訂單數量{$index}"] = $value['sum_percent'];
            }
            $result[] = [$per_year_count, $per_year_percent];
        }
        if ($call === true) {
            return $result;
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function exportItemCategoryAnalysisPdf ($request, $response, $args){
        
        $params = $request->getQueryParams();
        $year_list = [];
        for ($i = $params['start']; $i <= $params['end']; $i++) {
            array_push($year_list, $i);
        }

        $result = $this->exportItemCategoryAnalysis($request, $response, $args, true);

		$rows = "";
		foreach ($result as $key => $value) {
			$rows .= "
                <tr>
                    <td colspan=\"4\" style=\"text-align:center\">{$year_list[$key]}</td>
                    <td colspan=\"2\" style=\"text-align:center\">02-模組</td>
                    <td colspan=\"2\" style=\"text-align:center\">03-模仁</td>
                    <td colspan=\"2\" style=\"text-align:center\">04-模殼</td>
                </tr>
                <tr>
                    <td style=\"text-align:center\">訂單計數</td>
                    <td style=\"text-align:center\">訂單數量</td>
                    <td style=\"text-align:center\">製令盤數</td>
                    <td style=\"text-align:center\">預計產量</td>
                    <td style=\"text-align:center\">訂單計數</td>
                    <td style=\"text-align:center\">訂單數量</td>
                    <td style=\"text-align:center\">訂單計數</td>
                    <td style=\"text-align:center\">訂單數量</td>
                    <td style=\"text-align:center\">訂單計數</td>
                    <td style=\"text-align:center\">訂單數量</td>
                </tr>
                <tr>
                    <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['訂單計數']}</td>
                    <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['訂單數量']}</td>
                    <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['製令盤數']}</td>
                    <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['預計產量']}</td>
                    <td style=\"text-align:center\">{$value['0']['訂單計數1']}</td>
                    <td style=\"text-align:center\">{$value['0']['訂單數量1']}</td>
                    <td style=\"text-align:center\">{$value['0']['訂單計數2']}</td>
                    <td style=\"text-align:center\">{$value['0']['訂單數量2']}</td>
                    <td style=\"text-align:center\">{$value['0']['訂單計數3']}</td>
                    <td style=\"text-align:center\">{$value['0']['訂單數量3']}</td>
                </tr>
                <tr>
                    <td style=\"text-align:center\">{$value['1']['訂單計數1']}</td>
                    <td style=\"text-align:center\">{$value['1']['訂單數量1']}</td>
                    <td style=\"text-align:center\">{$value['1']['訂單計數2']}</td>
                    <td style=\"text-align:center\">{$value['1']['訂單數量2']}</td>
                    <td style=\"text-align:center\">{$value['1']['訂單計數3']}</td>
                    <td style=\"text-align:center\">{$value['1']['訂單數量3']}</td>
                </tr>";
		}

		// create new PDF document
		$pdf = new TCPDF_chinese('L', PDF_UNIT, "A4", true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('mil');
		$pdf->SetTitle("訂單到製令～欲切割 品名類別 分析用");
		$pdf->SetSubject('訂單到製令～欲切割 品名類別 分析用pdf');
		$pdf->SetKeywords('TCPDF, PDF, mil');

		// remove default header/footer
		$pdf->setPrintHeader(false);

		// set header and footer fonts
		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		// $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
			require_once(dirname(__FILE__) . '/lang/eng.php');
			$pdf->setLanguageArray($l);
		}

		// ---------------------------------------------------------

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		// $pdf->SetFont('dejavusans', '', 14, '', true);

		// Set font
		$fontname = TCPDF_FONTS::addTTFfont(__DIR__ . DIRECTORY_SEPARATOR . '/fonts/droidsansfallback.ttf', 'TrueTypeUnicode', '', 96);

		// $pdf->addTTFfont('/Users/laichuanen/droidsansfallback.ttf'); 
		$pdf->SetFont($fontname, '', 12, '', false);
		// $pdf->SetFont('msungstdlight', '', 12);

		// 設定資料與頁面上方的間距 (依需求調整第二個參數即可)
		$pdf->SetMargins(10, 10, 10);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>訂單到製令～欲切割 品名類別 分析用</h3>
		<table border="0.1" style="width:100%">
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("訂單到製令～欲切割 品名類別 分析用.pdf");
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output($file_name, 'D');
    }

    public function getOrderProductCategorySpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $params = $report->convertDateFormat($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT dt.\"編號\",dt.\"類別\",COALESCE(\"訂單數量\",0) \"訂單數量\",COALESCE(\"預計產量\",0) \"預計產量\"
                FROM(
                    SELECT 1 AS \"編號\",'06-前沖棒+07-後沖棒-(WC)' AS \"類別\"
                    UNION ALL SELECT 2,'03-模仁+05-INSERT(嵌入件)(WC)'
                    UNION ALL SELECT 3,'08-通孔沖棒-(銲接MIL-TIP)'
                    UNION ALL SELECT 4,'02-模組(兩種以上鋼料)'
                    UNION ALL SELECT 5,'02-模組(5-鎢+鋼模組)'
                    UNION ALL SELECT 6,'03-模仁(HSS-1整體鋼料)'
                    UNION ALL SELECT 7,'01-切刀'
                    UNION ALL SELECT 8,'04-模殼'
                    UNION ALL SELECT 9,'09-套管'
                    UNION ALL SELECT 10,'10-墊塊'
                    UNION ALL SELECT 11,'11-沖棒固定塊'
                    UNION ALL SELECT 12,'12-公牙'
                    UNION ALL SELECT 13,'13-夾子'
                    UNION ALL SELECT 14,'14-零件'
                    UNION ALL SELECT 15,'15-棘輪'
                    UNION ALL SELECT 16,'16-PIN'
                    UNION ALL SELECT 17,'17-通孔管'
                    UNION ALL SELECT 18,'18-其他'
                )dt
                LEFT JOIN (
                    SELECT category AS \"類別\",SUM(TD008) \"訂單數量\",SUM(\"預計產量\") \"預計產量\"
                    FROM(
                        SELECT
                          CASE 
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '06' OR SUBSTRING(COPTD.TD004, 9, 2) = '07') AND SUBSTRING(COPTD.TD004, 17, 1) = '4'
                             THEN '06-前沖棒+07-後沖棒-(WC)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '03' OR SUBSTRING(COPTD.TD004, 9, 2) = '05') AND SUBSTRING(COPTD.TD004, 17, 1) = '4'
                             THEN '03-模仁+05-INSERT(嵌入件)(WC)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '08') AND SUBSTRING(COPTD.TD004, 17, 1) = '2'
                             THEN '08-通孔沖棒-(銲接MIL-TIP)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '02') AND SUBSTRING(COPTD.TD004, 17, 1) = '3'
                             THEN '02-模組(兩種以上鋼料)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '02') AND SUBSTRING(COPTD.TD004, 17, 1) = '5'
                             THEN '02-模組(5-鎢+鋼模組)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '03') AND SUBSTRING(COPTD.TD004, 17, 1) = '1'
                             THEN '03-模仁(HSS-1整體鋼料)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '01') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '01-切刀'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '04') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '04-模殼'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '09') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '09-套管'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '10') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '10-墊塊'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '11') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '11-沖棒固定塊'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '12') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '12-公牙'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '13') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '13-夾子'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '14') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '14-零件'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '15') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '15-棘輪'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '16') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '16-PIN'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '17') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '17-通孔管'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '18') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '18-其他'
                          END AS category,
                          SUM(MOCTA.TA015) AS \"預計產量\",COPTD.TD001,COPTD.TD002,COPTD.TD003,COPTD.TD008
                        FROM
                          MIL.dbo.COPTD
                          LEFT JOIN MIL.dbo.MOCTA ON (COPTD.TD001=MOCTA.TA026 and COPTD.TD002=MOCTA.TA027 and COPTD.TD003=MOCTA.TA028)
                           INNER JOIN MIL.dbo.COPTC ON (COPTD.TD001=COPTC.TC001 and COPTD.TD002=COPTC.TC002)
                        WHERE
                          (
                           COPTC.TC003  BETWEEN {$params['start']} AND {$params['end']}
                           AND
                           (
                            MOCTA.TA001  Is Null
                            OR
                            MOCTA.TA011  NOT IN  ( 'y'  )
                           )
                           AND
                           (
                            MOCTA.TA001  Is Null  
                            OR
                            MOCTA.TA001  NOT IN  ( '5202','5205','5198','5199','5207','5203','5204'  )
                           )
                          )
                          GROUP BY SUBSTRING(COPTD.TD004, 9, 2),SUBSTRING(COPTD.TD004, 17, 1),COPTD.TD001,COPTD.TD002,COPTD.TD003,COPTD.TD008
                    ) a
                    WHERE category IS NOT NULL
                    GROUP BY category
                )dt2 ON dt.\"類別\" = dt2.\"類別\"
                ORDER BY dt.\"編號\"
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $db_data = json_decode($head, true);
        curl_close($ch);
        $spreadsheet = $report->createOrderProductCategorySpreadsheet($db_data);
        $params['filename'] = '訂單產品類別產量報表';
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getExportStaffProductivitySpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        
        $params['start_ori'] = '2019-01-01';
        $params['end_ori'] = '2019-01-07';
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
            $params['start_ori'] = $params['date_begin'];
            $params['end_ori'] = $params['date_end'];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $data = $report->readExportStaffProductivity($params);

        $uid = [];
        $result_date = [];
        $result_staff = [];
        $db_data = [];
        $week_arr = array("日", "一", "二", "三", "四", "五", "六");
        $date_begin = explode("-", $params['start_ori']);
        $date_end = explode("-", $params['end_ori']);
        $date_begin = mktime(0, 0, 0, intval($date_begin[1]), intval($date_begin[2]), intval($date_begin[0]));
        $date_end = mktime(0, 0, 0, intval($date_end[1]), intval($date_end[2]), intval($date_end[0]));
        $days = round(($date_end - $date_begin) / 3600 / 24);
        $now = date('Y-m-d', strtotime($params['start_ori']));
        for ($i = 1; $i <= $days + 1; $i++) {
            array_push($result_date, ['date' => str_replace("-", "/", $now), 'weekdays' => $week_arr[date("w", strtotime($now))]]);
            $now = date('Y-m-d', strtotime(" 1 day", strtotime($now)));
        }
        foreach ($data['data'] as $key => $value) {
            $now_date = mktime(0, 0, 0, intval(substr($value['單據日期'], 4, 2)), intval(substr($value['單據日期'], 6, 2)), intval(substr($value['單據日期'], 0, 4)));
            $now_days = round(($now_date - $date_begin) / 3600 / 24) + 1;

            if (in_array(($value['工號'].$value['移出部門']), $uid)) {
                if(!isset($result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2][$now_days])) {
                    $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2][$now_days] = 0;
                }
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2][$now_days] += $value['移轉單單號'];
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2]['total'] += $value['移轉單單號'];
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2]['days'] += 1;
                if(!isset($result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1][$now_days])) {
                    $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1][$now_days] = 0;
                }
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1][$now_days] += $value['移轉數量'];
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1]['total'] += $value['移轉數量'];
                $result_staff[array_search(($value['工號'].$value['移出部門']), $uid)*2 + 1]['days'] += 1;
            } else {
                array_push($result_staff, ['uid' => $value['工號'], 'name' => $value['姓名'], 'line' => $value['移出部門名稱'], 'type' => '盤數', "{$now_days}" => $value['移轉單單號'], 'total' => $value['移轉單單號'], 'days' => 1, 'avg' => 0]);
                array_push($result_staff, ['uid' => $value['工號'], 'name' => $value['姓名'], 'line' => $value['移出部門名稱'], 'type' => '移轉數量', "{$now_days}" => $value['移轉數量'], 'total' => $value['移轉數量'], 'days' => 1, 'avg' => 0]);
                array_push($uid, ($value['工號'].$value['移出部門']));
            }

        }
        foreach ($result_staff as $key => $value) {
            $result_staff[$key]['avg'] = round($result_staff[$key]['total'] / $result_staff[$key]['days'], 2);
        }

        array_push($db_data, $result_date);
        array_push($db_data, $result_staff);

        $spreadsheet = $report->createExportStaffProductivitySpreadsheet($db_data);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getExportOrderCurrencyStatisticsSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $db_data = $this->exportOrderCurrencyStatistics($request, $response, $args, true);
        for ($i = $params['startYear']; $i <= $params['endYear']; $i++) {
            $year_list[] = $i;
        }
        $spreadsheet = $report->createExportOrderCurrencyStatisticsSpreadsheet($db_data, $year_list);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getExportItemCategoryAnalysisSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $params = $report->convertDateFormat($params);
        $db_data = $this->exportItemCategoryAnalysis($request, $response, $args, true);
        for ($i = $params['start']; $i <= $params['end']; $i++) {
            $year_list[] = $i;
        }
        $spreadsheet = $report->createExportItemCategoryAnalysisSpreadsheet($db_data, $year_list);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getExportTurnoverChangeSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $params = $report->convertDateFormat($params);
        $db_data = $this->exportTurnoverChange($request, $response, $args, true);
        $spreadsheet = $report->createExportTurnoverChangeSpreadsheet($db_data, $params);
        $params['filename'] = '營業額變動-季別統計分析' . $params['begin'] . '-' . $params['end'];
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function exportOuterSecantComparison($request, $response, $args, $spreadsheet=false)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $params['start_ori'] = $params['start'];
        $params['end_ori'] = $params['end'];
        $params = $report->convertDateFormat($params);
        $params['end'] = date('Ym', strtotime(" 1 month", strtotime($params['end_ori'])));
        $params['date_begin'] = $params['start'];
        $params['date_end'] = $params['end'];
        $data = $report->readOuterSecantComparison($params);
        $result_date = [];
        $now = date('Y-m-d', strtotime($params['start_ori']));
        $fin = date('Y-m-d', strtotime($params['end_ori']));
        while ($now != $fin) {
            array_push($result_date, substr(str_replace("-", "/", $now), 0, 7));
            $now = date('Y-m-d', strtotime(" 1 month", strtotime($now)));
        }
        array_push($result_date, substr(str_replace("-", "/", $now), 0, 7));
        $result_tmp = [];
        $company_arr = [];
        foreach($data as $key => $value){
            $data[$key]['company'] = '吉兵';
        }
        foreach($data as $key => $value){
            $month = substr(substr_replace($value['單據日期'], '/', 4, 0), 0, 7);
            if(!in_array($value['company'], $company_arr)) {
                array_push($company_arr, $value['company']);
                array_push($result_tmp, [
                    ['company' => $value['company'], 'type' => '盤數', array_search($month, $result_date)+1 => $value['移轉數量'], 'avg' => $value['移轉數量']], 
                    ['company' => $value['company'], 'type' => '驗收數量', array_search($month, $result_date)+1 => $value['驗收數量'], 'avg' => $value['驗收數量']], 
                    ['company' => $value['company'], 'type' => '不良數量', array_search($month, $result_date)+1 => $value['不良數量'], 'avg' => $value['不良數量']]
                ]);
            }
            else {
                $result_tmp[array_search($value['company'], $company_arr)][0][array_search($month, $result_date)+1] += $value['移轉數量'];
                $result_tmp[array_search($value['company'], $company_arr)][0]['avg'] += $value['移轉數量'];
                $result_tmp[array_search($value['company'], $company_arr)][1][array_search($month, $result_date)+1] += $value['驗收數量'];
                $result_tmp[array_search($value['company'], $company_arr)][1]['avg'] += $value['驗收數量'];
                $result_tmp[array_search($value['company'], $company_arr)][2][array_search($month, $result_date)+1] += $value['不良數量'];
                $result_tmp[array_search($value['company'], $company_arr)][2]['avg'] += $value['不良數量'];
            }
        }
        foreach($result_tmp as $key => $value){
            foreach($value as $key_2 => $value_2){
                $result_tmp[$key][$key_2]['avg'] = round($value_2['avg']/count($result_date), 1);
            }
        }
        $result = [];
        array_push($result, $result_date);
        array_push($result, $result_tmp);
        if($spreadsheet == true){
            return $result;
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getTurnoverChangeSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $params = $report->convertDateFormat($params);
        $db_data = $report->readTurnoverChange($params);
        $spreadsheet = $report->createSpreadsheet($db_data);
        $params['filename'] = '營業額變動-季別統計分析' . $params['begin'] . '-' . $params['end'];
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function get_order_processes_100($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $result = $report->get_order_processes_100($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOuterSecantComparisonSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        
        $date = [
            'start' => '2019-01-01',
            'end' => '2019-01-07',
        ];
        if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
            $date = [
                'start' => $params['date_begin'],
                'end' => $params['date_end'],
            ];
        }
        $date = $report->convertDateFormat($date);
        $params['date_begin'] = $date['start'];
        $params['date_end'] = $date['end'];
        $db_data = $report->readOuterSecantComparison($params);
        $spreadsheet = $report->createSpreadsheet($db_data);
        $params['filename'] = '各線別生產數量統計表' . $params['date_begin'] . '-' . $params['date_end'];
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getExportOuterSecantComparisonSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $params = $report->convertDateFormat($params);
        $db_data = $this->exportOuterSecantComparison($request, $response, $args, true);
        $spreadsheet = $report->createExportOuterSecantComparisonSpreadsheet($db_data, $params);
        $params['filename'] = '各線別生產數量統計表' . $params['begin'] . '-' . $params['end'];
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function get_report_type($request, $response, $args)
    {
        global $container;
		$report = new report($container->db);
		$result = $report->get_report_type();
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
    }

    public function get_report($request, $response, $args)
    {
        global $container;
		$report = new report($container->db);
		$data = $request->getQueryParams();
		$result = $report->get_report($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
    }
}
