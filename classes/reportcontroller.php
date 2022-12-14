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
                ['sql' => "SELECT DATEPART(WK, \"?????????????????????\")-1 AS \"??????\",\"?????????????????????\",\"??????\",\"????????????\",COALESCE(\"???????????????\",0) \"???????????????\",COALESCE(\"???????????????\",0) \"???????????????\"
                FROM(
                    SELECT 
                        \"?????????????????????\",
                        COUNT(*) \"??????\",
                        SUM(\"????????????\") \"????????????\"
                    FROM(
                        SELECT
                        COPTD.TD215 \"?????????????????????\",
                        SUM(COPTD.TD008) \"????????????\"
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
                    GROUP BY \"?????????????????????\"
                )a
                LEFT JOIN (
                    SELECT XD001 TA014,
                        SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5201' OR SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5206' THEN XD017 ELSE 0 END) \"???????????????\",
                        SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5201' AND SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5206' THEN XD017 ELSE 0 END) \"???????????????\"
                    FROM MIL.dbo.MOCXD
                    WHERE (MOCXD.XD001 BETWEEN {$date_begin} AND {$date_end}) AND SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)  NOT IN  ( '5202','5205','5207'  )
                    GROUP BY MOCXD.XD001
                )b ON a.\"?????????????????????\" = b.TA014
                
                ORDER BY \"?????????????????????\" ASC
                
                "]
            )
        );
        /* 
        SELECT XC001 TA014,SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5201' OR SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5206' THEN XC007 ELSE 0 END) \"???????????????\",SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5201' OR SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5206' THEN XC007 ELSE 0 END) \"???????????????\"
        FROM MIL.dbo.MOCXC
        LEFT JOIN MIL.dbo.MOCXD ON MOCXD.XD001 = MOCXC.XC001 AND MOCXD.XD002 = MOCXC.XC002
        WHERE XC001 BETWEEN {$date_begin}  AND {$date_end}) AND SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)  NOT IN  ( '5202','5205','5207'  )
        GROUP BY MOCXC.XC001
        */
        /* 
        SELECT
        b.TA014,
        SUM( CASE WHEN b.TA001='5201' OR b.TA001='5206' THEN b.TD008 ELSE 0 END ) \"???????????????\",
        SUM( CASE WHEN b.TA001!='5201' AND b.TA001!='5206' THEN b.TD008 ELSE 0 END ) \"???????????????\"
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
                ['sql' => "SELECT COALESCE(SUM(\"??????\"),0) \"??????\",COALESCE(SUM(\"????????????\"),0) \"????????????\"
                FROM(
                    SELECT 
                        \"?????????????????????\",
                        COUNT(*) \"??????\",
                        SUM(\"????????????\") \"????????????\"
                    FROM(
                        SELECT
                        COPTD.TD215 \"?????????????????????\",
                        SUM(COPTD.TD008) \"????????????\"
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
                    GROUP BY \"?????????????????????\"
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
                        SELECT XD001 \"??????\",DATEPART(dw,MOCXD.XD001) dw,
                            SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5201' OR SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5206' THEN XD017 ELSE 0 END) \"???????????????\",
                            SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5201' AND SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5206' THEN XD017 ELSE 0 END) \"???????????????\",
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
            "??????" => "",
            "???????????????" => 0,
            "???????????????" => 0
        ];
        foreach ($fivedays as $fiveday) {
            foreach ($result['fivedays'] as $key => $value) {
                if (array_key_exists($key, $fiveday)) {
                    if ($key == '??????') {
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
            if ($key != '??????') {
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
                    COPTC.TC003 \"????????????\",
                    COPTD.TD001 \"??????\",
                    COPTD.TD002 \"??????\",
                    COPTD.TD001+'-'+COPTD.TD002+'-'+COPTD.TD003 \"(??????)+(??????)+(??????)\",
                    COPTD.TD003 \"??????\",
                    COPTC.TC004 \"????????????\",
                    COPTD.TD004 \"??????\",
                    COPTD.TD008 \"????????????\",
                    MOCTA.TA001 \"????????????\",
                    MOCTA.TA002 \"????????????\",
                    MOCTA.TA026+'-'+MOCTA.TA027+'-'+MOCTA.TA028 \"MOCTA.TA026-MOCTA.TA027-MOCTA.TA028\",
                    COPTD.TD215 \"?????????????????????\",
                    MOCTA.TA012 \"????????????\",
                    MOCTA.TA014 \"????????????\",
                    MOCTA.TA015 \"????????????\",
                    MOCTA.TA001 +'-'+MOCTA.TA002 \"(????????????)+(????????????)\",
                    COPTD.TD005 \"??????\",
                    COPTD.TD012 \"??????\",
                    COPTC.TC008 \"????????????\",
                    COPTC.TC009 \"??????\",
                    COPTD.TD011 \"??????\",
                    MOCTA.TA017 \"????????????\",
                    MOCTA.TA018 \"????????????\"
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
                ['sql' => "SELECT dt.\"??????\",dt.\"??????\",COALESCE(\"????????????\",0) \"????????????\",COALESCE(\"????????????\",0) \"????????????\"
                FROM(
                    SELECT 1 AS \"??????\",'06-?????????+07-?????????-(WC)' AS \"??????\"
                    UNION ALL SELECT 2,'03-??????+05-INSERT(?????????)(WC)'
                    UNION ALL SELECT 3,'08-????????????-(??????MIL-TIP)'
                    UNION ALL SELECT 4,'02-??????(??????????????????)'
                    UNION ALL SELECT 5,'02-??????(5-???+?????????)'
                    UNION ALL SELECT 6,'03-??????(HSS-1????????????)'
                    UNION ALL SELECT 7,'01-??????'
                    UNION ALL SELECT 8,'04-??????'
                    UNION ALL SELECT 9,'09-??????'
                    UNION ALL SELECT 10,'10-??????'
                    UNION ALL SELECT 11,'11-???????????????'
                    UNION ALL SELECT 12,'12-??????'
                    UNION ALL SELECT 13,'13-??????'
                    UNION ALL SELECT 14,'14-??????'
                    UNION ALL SELECT 15,'15-??????'
                    UNION ALL SELECT 16,'16-PIN'
                    UNION ALL SELECT 17,'17-?????????'
                    UNION ALL SELECT 18,'18-??????'
                )dt
                LEFT JOIN (
                    SELECT category AS \"??????\",SUM(TD008) \"????????????\",SUM(\"????????????\") \"????????????\"
                    FROM(
                        SELECT
                          CASE 
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '06' OR SUBSTRING(COPTD.TD004, 9, 2) = '07') AND SUBSTRING(COPTD.TD004, 17, 1) = '4'
                             THEN '06-?????????+07-?????????-(WC)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '03' OR SUBSTRING(COPTD.TD004, 9, 2) = '05') AND SUBSTRING(COPTD.TD004, 17, 1) = '4'
                             THEN '03-??????+05-INSERT(?????????)(WC)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '08') AND SUBSTRING(COPTD.TD004, 17, 1) = '2'
                             THEN '08-????????????-(??????MIL-TIP)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '02') AND SUBSTRING(COPTD.TD004, 17, 1) = '3'
                             THEN '02-??????(??????????????????)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '02') AND SUBSTRING(COPTD.TD004, 17, 1) = '5'
                             THEN '02-??????(5-???+?????????)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '03') AND SUBSTRING(COPTD.TD004, 17, 1) = '1'
                             THEN '03-??????(HSS-1????????????)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '01') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '01-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '04') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '04-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '09') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '09-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '10') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '10-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '11') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '11-???????????????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '12') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '12-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '13') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '13-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '14') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '14-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '15') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '15-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '16') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '16-PIN'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '17') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '17-?????????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '18') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '18-??????'
                          END AS category,
                          SUM(MOCTA.TA015) AS \"????????????\",COPTD.TD001,COPTD.TD002,COPTD.TD003,COPTD.TD008
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
                )dt2 ON dt.\"??????\" = dt2.\"??????\"
                ORDER BY dt.\"??????\"
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
            if (!isset($total[$value['??????']])) {
                $total[$value['??????']] = 0;
                if (!in_array($value['????????????'], $line) && $value['????????????']!='C' && $value['????????????']!='E') {
                    $line[] = $value['????????????'];
                }
                $user[] = [
                    'uid' => $value['??????'],
                    'userName' => $value['??????']
                ];
            }
            $total[$value['??????']] += 1;
        }
        foreach ($main as $key => $value) {
            $main[$key]['??????'] = $total[$value['??????']];
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
		$pdf->SetTitle("??????????????????????????? ???????????? ?????????");
		$pdf->SetSubject('??????????????????????????? ???????????? ?????????pdf');
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

		// ???????????????????????????????????? (????????????????????????????????????)
		$pdf->SetMargins(5, 10, 5);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>{$show_date} ??????????????????????????? ???????????? ?????????</h3>
		<table border="0.1" style="width:100%">
			<tr>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:4%">????????????</th>
				<th style="width:8%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:14%">??????</th>
				<th style="width:6%">????????????&nbsp;&nbsp;(?????????)</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????&nbsp;9-10???</th>
				<th style="width:6%">??????</th>
				<th style="width:6%">??????????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:8%">????????????</th>
				<th style="width:6%">????????????</th>
			</tr>
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("??????????????????????????? ???????????? ?????????{$show_date}.pdf");
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

        //???????????? ???????????????
        $params['date_begin'] = $date['start'] . "00";
        $params['date_end'] = $date['end'] . "32";

        $orderOne = $report->exportCilentOrderOne($params);
        $orders = $report->exportCilentOrderOther($params);

        //??????
        $result_data = [];
        foreach($orderOne as $value) {
            $result_data[intval(substr($value['????????????'], 0, 4))][intval(substr($value['????????????'], 4, 2))]['001'] = intval($value['??????']);
        }
        foreach($orders as $value) {
            $now_year = intval(substr($value['????????????'], 0, 4));
            $now_month = intval(substr($value['????????????'], 4, 2));
            if($value['??????'] == 1) {
                if(!isset($result_data[$now_year][$now_month]['??????'])) {
                $result_data[$now_year][$now_month]['??????'] = 0;
                }
                $result_data[$now_year][$now_month]['??????'] += 1;
            }
            else {
                if(!isset($result_data[$now_year][$now_month]['????????????'])) {
                    $result_data[$now_year][$now_month]['????????????'] = 0;
                }
                $result_data[$now_year][$now_month]['????????????'] += 1;
            }
        }

        //????????????
        $start_year = intval(substr($params['date_begin'], 0, 4));
        $start_month = intval(substr($params['date_begin'], 4, 2));
        $end_year = intval(substr($params['date_end'], 0, 4));
        $end_month = intval(substr($params['date_end'], 4, 2));

        $month = ['???','??????','??????','??????','??????','??????','??????','??????','??????','??????','??????','?????????','?????????'];

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
                if(!isset($result_data[(string)$now_year][(string)$now_month]['??????'])) {
                    $result_data[(string)$now_year][(string)$now_month]['??????'] = 0;
                }
                if(!isset($result_data[(string)$now_year][(string)$now_month]['????????????'])) {
                    $result_data[(string)$now_year][(string)$now_month]['????????????'] = 0;
                }
                
                $now_001 = $result_data[(string)$now_year][(string)$now_month]['001'];
                $now_once = $result_data[(string)$now_year][(string)$now_month]['??????'];
                $now_upper = $result_data[(string)$now_year][(string)$now_month]['????????????'];

                $total_count = $now_001 + $now_once + $now_upper;
                $array_001 = [
                    '??????'=> $month[$now_month],
                    '??????'=> 1 . "",
                    '?????????'=> $total_count . "",
                    '????????????'=> ($total_count == 0)?'0':round(($now_001*100 / $total_count), 2) . "%",
                    '??????'=> $now_001 . "",
                    '??????'=> '001'
                ];
                $array_once = [
                    '??????'=> $month[$now_month],
                    '??????'=> 2 . "",
                    '?????????'=> $total_count . "",
                    '????????????'=> ($total_count == 0)?'0':round(($now_once*100 / $total_count), 2) . "%",
                    '??????'=> $now_once . "",
                    '??????'=> '??????'
                ];
                $array_upper = [
                    '??????'=> $month[$now_month],
                    '??????'=> 3 . "",
                    '?????????'=> $total_count . "",
                    '????????????'=> ($total_count == 0)?'0%':round(($now_upper*100 / $total_count), 2)."%",
                    '??????'=> $now_upper . "",
                    '??????'=> '????????????'
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
		$pdf->SetTitle("???????????????????????? ???????????????????????????(????????????)");
		$pdf->SetSubject('???????????????????????? ???????????????????????????(????????????)pdf');
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

		// ???????????????????????????????????? (????????????????????????????????????)
		$pdf->SetMargins(5, 10, 5);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>{$show_date} ???????????????????????? ???????????????????????????(????????????)</h3>
		<table border="0.1" style="width:100%">
			<tr>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:8%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:4%">??????</th>
				<th style="width:13%">??????</th>
				<th style="width:6%">????????????&nbsp;9-10???</th>
				<th style="width:6%">???????????????17???</th>
				<th style="width:7%">??????</th>
				<th style="width:6%">????????????</th>
				<th style="width:8%">????????????</th>
				<th style="width:6%">????????????</th>
			</tr>
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("???????????????????????? ???????????????????????????(????????????){$show_date}.pdf");
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

        $group = ['00-??????', '01-??????', '02-??????', '03-??????', '04-??????', '05-?????????', '06-?????????', 
        '07-?????????', '08-????????????', '09-??????', '10-??????', '11-???????????????', '12-??????', 
        '13-??????', '14-??????', '15-??????', '16-PIN', '17-?????????', '18-??????'];
        
        //???????????????
        $package_num = [];
        $package_num["type"] = '??????';
        $package_num["????????????"] = '0';
        $package_num["????????????"] = '0';
        $package_num["????????????"] = '0';
        $package_percent = [];
        $package_percent["type"] = '?????????';
        $package_percent["????????????"] = '0';
        $package_percent["????????????"] = '0';
        $package_percent["????????????"] = '0';
        foreach($group as $key => $value) {
            $package_num["????????????{$key}"] = '0';
            $package_num["????????????{$key}"] = '0';
            $package_num["????????????{$key}"] = '0';
            $package_percent["????????????{$key}"] = '0%';
            $package_percent["????????????{$key}"] = '0%';
            $package_percent["????????????{$key}"] = '0%';
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
                $now_package_num["????????????"] = $value['????????????'];
                $now_package_num["????????????"] = $value['????????????'];
                $now_package_num["????????????"] = $value['????????????'];
                $now_package_percent["????????????"] = $value['????????????'];
                $now_package_percent["????????????"] = $value['????????????'];
                $now_package_percent["????????????"] = $value['????????????'];
            }

            foreach($db_result['data'] as $value) {
                $group_key = array_search($value['????????????'], $group);
                $now_package_num["????????????{$group_key}"] = $value['????????????'];
                $now_package_num["????????????{$group_key}"] = $value['????????????'];
                $now_package_num["????????????{$group_key}"] = $value['????????????'];
                $now_package_percent["????????????{$group_key}"] = round(($value['????????????']*100 / $now_package_num['????????????']), 2).'%';
                $now_package_percent["????????????{$group_key}"] = round(($value['????????????']*100 / $now_package_num['????????????']), 2).'%';
                $now_package_percent["????????????{$group_key}"] = round(($value['????????????']*100 / $now_package_num['????????????']), 2).'%';
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

            $group = [$value[0]['date'], '00-??????', '01-??????', '02-??????', '03-??????', '04-??????', '05-?????????', '06-?????????', 
            '07-?????????', '08-????????????', '09-??????', '10-??????', '11-???????????????', '12-??????', 
            '13-??????', '14-??????', '15-??????', '16-PIN', '17-?????????', '18-??????'];
            
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
                        <td style=\"text-align:center\">????????????</td>
                        <td style=\"text-align:center\">????????????</td>
                        <td style=\"text-align:center\">????????????(??????)</td>
                    ";
                }
                
                $rows .= "
                    </tr>
                    <tr>
                ";

                for($i=0; $i<5; $i++) {
                    $count_name = '????????????' . ($group_count+$i);
                    $number_name = '????????????' . ($group_count+$i);
                    $mount_name = '????????????' . ($group_count+$i);

                    if($group_count+$i != 0) {
                        $rows .= " 
                            <td  style=\"text-align:center\">{$value['0'][$count_name]}</td>
                            <td style=\"text-align:center\">{$value['0'][$number_name]}</td>
                            <td style=\"text-align:center\">{$value['0'][$mount_name]}</td>
                        ";
                    }
                    else {
                        $rows .= " 
                            <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['????????????']}</td>
                            <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['????????????']}</td>
                            <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['????????????']}</td>
                        ";
                    }

                }

                $rows .= "
                    </tr>
                    <tr>
                ";

                for($i=0; $i<5; $i++) {
                    $count_name = '????????????' . ($group_count+$i);
                    $number_name = '????????????' . ($group_count+$i);
                    $mount_name = '????????????' . ($group_count+$i);

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
		$pdf->SetTitle("??????????????????????????? ???????????? ?????????");
		$pdf->SetSubject('??????????????????????????? ???????????? ?????????pdf');
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

		// ???????????????????????????????????? (????????????????????????????????????)
		$pdf->SetMargins(10, 10, 10);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>??????????????????????????? ???????????? ?????????</h3>
		<table border="0.1" style="width:100%">
		    {$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("??????????????????????????? ???????????? ?????????.pdf");
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

        $group = ['00-??????', '01-??????', '02-??????', '03-??????', '04-??????', '05-?????????', '06-?????????', 
        '07-?????????', '08-????????????', '09-??????', '10-??????', '11-???????????????', '12-??????', 
        '13-??????', '14-??????', '15-??????', '16-PIN', '17-?????????', '18-??????'];
        
        //???????????????
        $package_num = [];
        $package_num["type"] = '??????';
        $package_num["????????????"] = '0';
        $package_num["????????????"] = '0';
        $package_num["????????????"] = '0';
        $package_num["?????????????????????"] = '0';
        $package_num["?????????????????????"] = '0';
        $package_num["?????????????????????"] = '0';
        $package_percent = [];
        $package_percent["type"] = '?????????';
        $package_percent["????????????"] = '0';
        $package_percent["????????????"] = '0';
        $package_percent["????????????"] = '0';
        $package_percent["?????????????????????"] = '0%';
        $package_percent["?????????????????????"] = '0%';
        $package_percent["?????????????????????"] = '0%';
        foreach($group as $key => $value) {
            $package_num["????????????{$key}"] = '0';
            $package_num["????????????{$key}"] = '0';
            $package_num["????????????{$key}"] = '0';
            $package_num["?????????????????????{$key}"] = '0';
            $package_num["?????????????????????{$key}"] = '0';
            $package_num["?????????????????????{$key}"] = '0';
            $package_percent["????????????{$key}"] = '0%';
            $package_percent["????????????{$key}"] = '0%';
            $package_percent["????????????{$key}"] = '0%';
            $package_percent["?????????????????????{$key}"] = '0%';
            $package_percent["?????????????????????{$key}"] = '0%';
            $package_percent["?????????????????????{$key}"] = '0%';
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
                $now_package_num["????????????"] = $value['????????????'];
                $now_package_num["????????????"] = $value['????????????'];
                $now_package_num["????????????"] = $value['????????????'];
                $now_package_percent["????????????"] = $value['????????????'];
                $now_package_percent["????????????"] = $value['????????????'];
                $now_package_percent["????????????"] = $value['????????????'];
            }

            foreach($db_result['data'] as $value) {
                $group_key = array_search($value['????????????'], $group);
                $now_package_num["????????????{$group_key}"] = $value['????????????'];
                $now_package_num["????????????{$group_key}"] = $value['????????????'];
                $now_package_num["????????????{$group_key}"] = $value['????????????'];
                $now_package_percent["????????????{$group_key}"] = round(($value['????????????']*100 / $now_package_num['????????????']), 2).'%';
                $now_package_percent["????????????{$group_key}"] = round(($value['????????????']*100 / $now_package_num['????????????']), 2).'%';
                $now_package_percent["????????????{$group_key}"] = round(($value['????????????']*100 / $now_package_num['????????????']), 2).'%';
            }
            
            $db_result_category = $report->readExportProductItemFixedCategory($now_date);

            foreach($db_result_category['total'] as $key => $value) {
                $now_package_num["?????????????????????"] = $value['????????????'];
                $now_package_num["?????????????????????"] = $value['????????????'];
                $now_package_num["?????????????????????"] = $value['????????????'];
                $now_package_percent["?????????????????????"] = round(($value['????????????']*100 / $now_package_num['????????????']), 2).'%';
                $now_package_percent["?????????????????????"] = round(($value['????????????']*100 / $now_package_num['????????????']), 2).'%';
                $now_package_percent["?????????????????????"] = round(($value['????????????']*100 / $now_package_num['????????????']), 2).'%';
            }

            foreach($db_result_category['data'] as $value) {
                $group_key = array_search($value['????????????'], $group);
                $now_package_num["?????????????????????{$group_key}"] = $value['????????????'];
                $now_package_num["?????????????????????{$group_key}"] = $value['????????????'];
                $now_package_num["?????????????????????{$group_key}"] = $value['????????????'];
                $now_package_percent["?????????????????????{$group_key}"] = round(($value['????????????']*100 / $now_package_num['?????????????????????']), 2).'%';
                $now_package_percent["?????????????????????{$group_key}"] = round(($value['????????????']*100 / $now_package_num['?????????????????????']), 2).'%';
                $now_package_percent["?????????????????????{$group_key}"] = round(($value['????????????']*100 / $now_package_num['?????????????????????']), 2).'%';
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
        $group = ['00-??????', '01-??????', '02-??????', '03-??????', '04-??????', '05-?????????', '06-?????????', 
        '07-?????????', '08-????????????', '09-??????', '10-??????', '11-???????????????', '12-??????', 
        '13-??????', '14-??????', '15-??????', '16-PIN', '17-?????????', '18-??????'];
        $db_data = $this->getExportProductItemFixedCategory($request, $response, $args, true);
        $spreadsheet = $report->createExportProductItemFixedCategorySpreadsheet($db_data, $group);
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getExportProductItemSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $group = ['00-??????', '01-??????', '02-??????', '03-??????', '04-??????', '05-?????????', '06-?????????', 
        '07-?????????', '08-????????????', '09-??????', '10-??????', '11-???????????????', '12-??????', 
        '13-??????', '14-??????', '15-??????', '16-PIN', '17-?????????', '18-??????'];
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
		$pdf->SetTitle("???????????????????????? ???????????????????????????(????????????-???????????????)");
		$pdf->SetSubject('???????????????????????? ???????????????????????????(????????????-???????????????)pdf');
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

		// ???????????????????????????????????? (????????????????????????????????????)
		$pdf->SetMargins(5, 10, 5);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>{$show_date} ???????????????????????? ???????????????????????????(????????????-???????????????)</h3>
		<table border="0.1" style="width:100%">
			<tr>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:8%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:4%">??????</th>
				<th style="width:13%">??????</th>
				<th style="width:6%">????????????&nbsp;9-10???</th>
				<th style="width:6%">???????????????17???</th>
				<th style="width:7%">??????</th>
				<th style="width:6%">????????????</th>
				<th style="width:8%">????????????</th>
				<th style="width:6%">????????????</th>
			</tr>
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("???????????????????????? ???????????????????????????(????????????-???????????????){$show_date}.pdf");
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
            if ($value['??????'] - intval($value['??????']) == 0) {
                $orders[$key]['??????'] = strval(intval($value['??????']));
            }
            if ($value['??????'] - intval($value['??????']) == 0) {
                $orders[$key]['??????'] = strval(intval($value['??????']));
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
		$pdf->SetTitle("??????????????????????????????");
		$pdf->SetSubject('??????????????????????????????pdf');
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

		// ???????????????????????????????????? (????????????????????????????????????)
		$pdf->SetMargins(10, 10, 10);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>{$show_date} ??????????????????????????????</h3>
		<table border="0.1" style="width:100%">
			<tr>
				<th style="width:7%">????????????</th>
				<th style="width:7%">???????????????</th>
				<th style="width:9%">???????????????</th>
				<th style="width:7%">???????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:8%">??????????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:9%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
			</tr>
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("??????????????????????????????{$show_date}.pdf");
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
            if ($value['????????????'] - intval($value['????????????']) == 0) {
                $orders[$key]['????????????'] = strval(intval($value['????????????']));
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
            if ($value['????????????'] - intval($value['????????????']) == 0) {
                $result[$key]['????????????'] = strval(intval($value['????????????']));
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
		$pdf->SetTitle("????????????????????????-????????????");
		$pdf->SetSubject('????????????????????????-????????????pdf');
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

		// ???????????????????????????????????? (????????????????????????????????????)
		$pdf->SetMargins(10, 10, 10);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>{$show_date} ????????????????????????-????????????</h3>
		<table border="0.1" style="width:100%">
			<tr>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:9%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:7%">????????????</th>
				<th style="width:14%">??????</th>
				<th style="width:18%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:10%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
				<th style="width:6%">????????????</th>
			</tr>
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("????????????????????????-????????????.pdf");
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
            $plant += intval($value['??????']);
            $order += intval($value['????????????']);
            if ($key < 5) {
                $fonsite += intval($value['???????????????']);
                $foutsite += intval($value['???????????????']);
            }
            if ($key == sizeof($result) - 1) {
                $week_count += 1;
                array_push($merge, $week_count);
                $week_count = 1;
                $week = intval($value['??????']);
            } else if (intval($value['??????']) == $week) {
                $week_count += 1;
            } else {
                array_push($merge, $week_count);
                $week_count = 1;
                $week = intval($value['??????']);
            }
        }
        array_unshift($result, ["??????",    "?????????????????????", "??????", "????????????", "???????????????", "???????????????"]);
        array_unshift($result, ["???", $params['end']]);
        array_unshift($result, ["???", $params['start']]);
        array_push($result, ["?????????", $plant]);
        array_push($result, ["???????????????", $order]);
        array_push($result, ["???????????????????????????", round($fonsite / 5)]);
        array_push($result, ["???????????????????????????", round($foutsite / 5)]);
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
        $params['filename'] = '??????????????????';
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
            if (!isset($result[substr($value['????????????'], 0, 4)])) {
                $result[substr($value['????????????'], 0, 4)] = [];
            }
            if (!isset($result[substr($value['????????????'], 0, 4)][$value['????????????']])) {
                $result[substr($value['????????????'], 0, 4)][$value['????????????']] = [];
            }
            if (!isset($result[substr($value['????????????'], 0, 4)][$value['????????????']]['Q' . ceil(intval(substr($value['????????????'], 4)) / 331)])) {
                $result[substr($value['????????????'], 0, 4)][$value['????????????']]['Q' . ceil(intval(substr($value['????????????'], 4)) / 331)] = 0;
            }
            $result[substr($value['????????????'], 0, 4)][$value['????????????']]['Q' . ceil(intval(substr($value['????????????'], 4)) / 331)] += intval($value['??????']);
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
		$pdf->SetTitle("???????????????????????????");
		$pdf->SetSubject('???????????????????????????pdf');
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

		// ???????????????????????????????????? (????????????????????????????????????)
		$pdf->SetMargins(10, 5, 10);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>{$show_date} ???????????????????????????</h3>
		<table border="2" style="width:100%">
			<tr>
				<th style="border:0.1px solid black; width:8%">??????</th>
				<th style="border:0.1px solid black; width:8%">??????</th>
				<th style="border:0.1px solid black; width:8%">????????????</th>
				<th style="border:0.1px solid black; width:12%">???????????????</th>
				<th style="border:0.1px solid black; width:7%">???????????????</th>
				<th style="border:0.1px solid black; width:8%">????????????</th>
				<th style="border:0.1px solid black; width:17%">??????????????????</th>
				<th style="border:0.1px solid black; width:8%">????????????</th>
				<th style="border:0.1px solid black; width:8%">????????????</th>
				<th style="border:0.1px solid black; width:8%">????????????</th>
				<th style="border:0.1px solid black; width:8%">????????????</th>
			</tr>
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("???????????????????????????.pdf");
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
            if (isset($result[substr($value['????????????'], 4, 2)])) {
                if (!isset($result[substr($value['????????????'], 4, 2)][$value['????????????']])) {
                    $result[substr($value['????????????'], 4, 2)][$value['????????????']] = [];
                }
                if (!isset($result[substr($value['????????????'], 4, 2)][$value['????????????']][array_search(substr($value['????????????'], 0, 4), $year_arr) + 1])) {
                    $result[substr($value['????????????'], 4, 2)][$value['????????????']][array_search(substr($value['????????????'], 0, 4), $year_arr) + 1] = 0;
                }
                if (!isset($result[substr($value['????????????'], 4, 2)]['SUM'][array_search(substr($value['????????????'], 0, 4), $year_arr) + 1])) {
                    $result[substr($value['????????????'], 4, 2)]['SUM'][array_search(substr($value['????????????'], 0, 4), $year_arr) + 1] = 0;
                }
                if (!isset($result[substr($value['????????????'], 4, 2)][$value['????????????']]['compare'])) {
                    $result[substr($value['????????????'], 4, 2)][$value['????????????']]['compare'] = 0;
                }
                $result[substr($value['????????????'], 4, 2)][$value['????????????']][array_search(substr($value['????????????'], 0, 4), $year_arr) + 1] += intval($value['????????????']);
                $result[substr($value['????????????'], 4, 2)][$value['????????????']]['compare'] += intval($value['????????????']);
                $result[substr($value['????????????'], 4, 2)]['SUM'][array_search(substr($value['????????????'], 0, 4), $year_arr) + 1] += intval($value['????????????']);
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
        $week_arr = array("???", "???", "???", "???", "???", "???", "???");
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
            $now_date = mktime(0, 0, 0, intval(substr($value['????????????'], 4, 2)), intval(substr($value['????????????'], 6, 2)), intval(substr($value['????????????'], 0, 4)));
            $now_days = round(($now_date - $date_begin) / 3600 / 24) + 1;

            if (in_array(($value['??????'].$value['????????????']), $uid)) {
                if(!isset($result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2][$now_days])) {
                    $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2][$now_days] = 0;
                }
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2][$now_days] += $value['???????????????'];
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2]['total'] += $value['???????????????'];
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2]['days'] += 1;
                if(!isset($result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1][$now_days])) {
                    $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1][$now_days] = 0;
                }
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1][$now_days] += $value['????????????'];
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1]['total'] += $value['????????????'];
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1]['days'] += 1;
            } else {
                array_push($result_staff, ['uid' => $value['??????'], 'name' => $value['??????'], 'line' => $value['??????????????????'], 'type' => '??????', "{$now_days}" => $value['???????????????'], 'total' => $value['???????????????'], 'days' => 1, 'avg' => 0]);
                array_push($result_staff, ['uid' => $value['??????'], 'name' => $value['??????'], 'line' => $value['??????????????????'], 'type' => '????????????', "{$now_days}" => $value['????????????'], 'total' => $value['????????????'], 'days' => 1, 'avg' => 0]);
                array_push($uid, ($value['??????'].$value['????????????']));
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
        $week_arr = array("???", "???", "???", "???", "???", "???", "???");
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
            $now_date = mktime(0, 0, 0, intval(substr($value['????????????'], 4, 2)), intval(substr($value['????????????'], 6, 2)), intval(substr($value['????????????'], 0, 4)));
            $now_days = round(($now_date - $date_begin) / 3600 / 24) + 1;

            if (in_array(($value['??????'].$value['????????????']), $uid)) {
                if(!isset($result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2][$now_days])) {
                    $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2][$now_days] = 0;
                }
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2][$now_days] += $value['???????????????'];
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2]['total'] += $value['???????????????'];
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2]['days'] += 1;
                if(!isset($result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1][$now_days])) {
                    $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1][$now_days] = 0;
                }
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1][$now_days] += $value['????????????'];
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1]['total'] += $value['????????????'];
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1]['days'] += 1;
            } else {
                array_push($result_staff, ['uid' => $value['??????'], 'name' => $value['??????'], 'line' => $value['??????????????????'], 'type' => '??????', "{$now_days}" => $value['???????????????'], 'total' => $value['???????????????'], 'days' => 1, 'avg' => 0]);
                array_push($result_staff, ['uid' => $value['??????'], 'name' => $value['??????'], 'line' => $value['??????????????????'], 'type' => '????????????', "{$now_days}" => $value['????????????'], 'total' => $value['????????????'], 'days' => 1, 'avg' => 0]);
                array_push($uid, ($value['??????'].$value['????????????']));
            }

        }
        foreach ($result_staff as $key => $value) {
            $result_staff[$key]['avg'] = round($result_staff[$key]['total'] / $result_staff[$key]['days'], 0);
        }

        $days = "";
        
        $weeks = "<tr><td>??????</td>";
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
            $rows .= "<td rowspan=\"2\" colspan=\"3\">". $params['line'] ."????????????</td>";
        }
        else {
            $rows .= "<td rowspan=\"2\" colspan=\"3\">????????????</td>";
        }

        $row_bottom = "<tr>";
        $rows .= "<td>??????</td>";
        $row_bottom .= "<td>????????????</td>";
        $up_total = 0;
        $bottom_total = 0;

        $total_days = round(($date_end - $date_begin) / 3600 / 24) + 1;

        $prevdate = 0;
        foreach ($data['sum'] as $key => $value) {
            $now_date = mktime(0, 0, 0, intval(substr($value['????????????'], 4, 2)), intval(substr($value['????????????'], 6, 2)), intval(substr($value['????????????'], 0, 4)));
            $now_days = round(($now_date - $date_begin) / 3600 / 24) + 1;

            while($now_days - $prevdate > 1) {
                $rows .= "<td style=\"text-align:right\">0</td>";
                $row_bottom .= "<td style=\"text-align:right\">0</td>";
                $prevdate ++;
            }

            $rows .= "<td style=\"text-align:right\">". $value['???????????????'] ."</td>";
            $row_bottom .= "<td style=\"text-align:right\">". $value['????????????'] ."</td>";

            $up_total += intval($value['???????????????']);
            $bottom_total += intval($value['????????????']);

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
		$pdf->SetTitle("???????????????????????????");
		$pdf->SetSubject('???????????????????????????pdf');
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
		<h3>{$show_date} ???????????????????????????</h3>
		<table border="0.1" style="width:100%">
			<tr>
				<th rowspan="2">??????</th>
				<th rowspan="2">??????</th>
				<th rowspan="2">??????</th>
				<th>??????</th>
				{$days}
				<th rowspan="2">??????</th>
				<th rowspan="2">??????</th>
				<th rowspan="2">?????????</th>
			</tr>
			{$rows}
		</table>
		EOD;

		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("???????????????????????????.pdf");
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
            
            $per_year_count["type"] = "??????";
            $per_year_count["????????????"] = $db_data['year_total']['count'];
            $per_year_count["????????????"] = $db_data['year_total']['sum']?$db_data['year_total']['sum']:'0';
            $per_year_count["????????????"] = $db_data['year_total']['discs_count'];
            $per_year_count["????????????"] = $db_data['year_total']['predict_sum']?$db_data['year_total']['predict_sum']:'0';
            $per_year_percent["type"] = "?????????";
            $per_year_percent["????????????"] = $db_data['year_total']['count'];
            $per_year_percent["????????????"] = $db_data['year_total']['sum']?$db_data['year_total']['sum']:'0';
            $per_year_percent["????????????"] = $db_data['year_total']['discs_count'];
            $per_year_percent["????????????"] = $db_data['year_total']['predict_sum']?$db_data['year_total']['predict_sum']:'0';
            foreach ($db_data['each_item'] as $key => $value) {
                $index = $key + 1;
                $per_year_count["????????????{$index}"] = $value['count'];
                $per_year_count["????????????{$index}"] = $value['sum'];
                $per_year_percent["????????????{$index}"] = $value['count_percent'];
                $per_year_percent["????????????{$index}"] = $value['sum_percent'];
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
                    <td colspan=\"2\" style=\"text-align:center\">02-??????</td>
                    <td colspan=\"2\" style=\"text-align:center\">03-??????</td>
                    <td colspan=\"2\" style=\"text-align:center\">04-??????</td>
                </tr>
                <tr>
                    <td style=\"text-align:center\">????????????</td>
                    <td style=\"text-align:center\">????????????</td>
                    <td style=\"text-align:center\">????????????</td>
                    <td style=\"text-align:center\">????????????</td>
                    <td style=\"text-align:center\">????????????</td>
                    <td style=\"text-align:center\">????????????</td>
                    <td style=\"text-align:center\">????????????</td>
                    <td style=\"text-align:center\">????????????</td>
                    <td style=\"text-align:center\">????????????</td>
                    <td style=\"text-align:center\">????????????</td>
                </tr>
                <tr>
                    <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['????????????']}</td>
                    <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['????????????']}</td>
                    <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['????????????']}</td>
                    <td rowspan=\"2\" style=\"text-align:center\">{$value['0']['????????????']}</td>
                    <td style=\"text-align:center\">{$value['0']['????????????1']}</td>
                    <td style=\"text-align:center\">{$value['0']['????????????1']}</td>
                    <td style=\"text-align:center\">{$value['0']['????????????2']}</td>
                    <td style=\"text-align:center\">{$value['0']['????????????2']}</td>
                    <td style=\"text-align:center\">{$value['0']['????????????3']}</td>
                    <td style=\"text-align:center\">{$value['0']['????????????3']}</td>
                </tr>
                <tr>
                    <td style=\"text-align:center\">{$value['1']['????????????1']}</td>
                    <td style=\"text-align:center\">{$value['1']['????????????1']}</td>
                    <td style=\"text-align:center\">{$value['1']['????????????2']}</td>
                    <td style=\"text-align:center\">{$value['1']['????????????2']}</td>
                    <td style=\"text-align:center\">{$value['1']['????????????3']}</td>
                    <td style=\"text-align:center\">{$value['1']['????????????3']}</td>
                </tr>";
		}

		// create new PDF document
		$pdf = new TCPDF_chinese('L', PDF_UNIT, "A4", true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('mil');
		$pdf->SetTitle("??????????????????????????? ???????????? ?????????");
		$pdf->SetSubject('??????????????????????????? ???????????? ?????????pdf');
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

		// ???????????????????????????????????? (????????????????????????????????????)
		$pdf->SetMargins(10, 10, 10);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
		$html = <<<EOD
		<h3>??????????????????????????? ???????????? ?????????</h3>
		<table border="0.1" style="width:100%">
			{$rows}
		</table>
		EOD;

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		$file_name = strval("??????????????????????????? ???????????? ?????????.pdf");
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
                ['sql' => "SELECT dt.\"??????\",dt.\"??????\",COALESCE(\"????????????\",0) \"????????????\",COALESCE(\"????????????\",0) \"????????????\"
                FROM(
                    SELECT 1 AS \"??????\",'06-?????????+07-?????????-(WC)' AS \"??????\"
                    UNION ALL SELECT 2,'03-??????+05-INSERT(?????????)(WC)'
                    UNION ALL SELECT 3,'08-????????????-(??????MIL-TIP)'
                    UNION ALL SELECT 4,'02-??????(??????????????????)'
                    UNION ALL SELECT 5,'02-??????(5-???+?????????)'
                    UNION ALL SELECT 6,'03-??????(HSS-1????????????)'
                    UNION ALL SELECT 7,'01-??????'
                    UNION ALL SELECT 8,'04-??????'
                    UNION ALL SELECT 9,'09-??????'
                    UNION ALL SELECT 10,'10-??????'
                    UNION ALL SELECT 11,'11-???????????????'
                    UNION ALL SELECT 12,'12-??????'
                    UNION ALL SELECT 13,'13-??????'
                    UNION ALL SELECT 14,'14-??????'
                    UNION ALL SELECT 15,'15-??????'
                    UNION ALL SELECT 16,'16-PIN'
                    UNION ALL SELECT 17,'17-?????????'
                    UNION ALL SELECT 18,'18-??????'
                )dt
                LEFT JOIN (
                    SELECT category AS \"??????\",SUM(TD008) \"????????????\",SUM(\"????????????\") \"????????????\"
                    FROM(
                        SELECT
                          CASE 
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '06' OR SUBSTRING(COPTD.TD004, 9, 2) = '07') AND SUBSTRING(COPTD.TD004, 17, 1) = '4'
                             THEN '06-?????????+07-?????????-(WC)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '03' OR SUBSTRING(COPTD.TD004, 9, 2) = '05') AND SUBSTRING(COPTD.TD004, 17, 1) = '4'
                             THEN '03-??????+05-INSERT(?????????)(WC)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '08') AND SUBSTRING(COPTD.TD004, 17, 1) = '2'
                             THEN '08-????????????-(??????MIL-TIP)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '02') AND SUBSTRING(COPTD.TD004, 17, 1) = '3'
                             THEN '02-??????(??????????????????)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '02') AND SUBSTRING(COPTD.TD004, 17, 1) = '5'
                             THEN '02-??????(5-???+?????????)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '03') AND SUBSTRING(COPTD.TD004, 17, 1) = '1'
                             THEN '03-??????(HSS-1????????????)'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '01') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '01-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '04') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '04-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '09') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '09-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '10') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '10-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '11') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '11-???????????????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '12') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '12-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '13') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '13-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '14') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '14-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '15') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '15-??????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '16') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '16-PIN'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '17') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '17-?????????'
                            WHEN (SUBSTRING(COPTD.TD004, 9, 2) = '18') AND	(SUBSTRING(COPTD.TD004, 17, 1) = '1' OR SUBSTRING(COPTD.TD004, 17, 1) = '2' OR SUBSTRING(COPTD.TD004, 17, 1) = '3' OR SUBSTRING(COPTD.TD004, 17, 1) = '4' OR SUBSTRING(COPTD.TD004, 17, 1) = '5' OR SUBSTRING(COPTD.TD004, 17, 1) = '6')
                             THEN '18-??????'
                          END AS category,
                          SUM(MOCTA.TA015) AS \"????????????\",COPTD.TD001,COPTD.TD002,COPTD.TD003,COPTD.TD008
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
                )dt2 ON dt.\"??????\" = dt2.\"??????\"
                ORDER BY dt.\"??????\"
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $db_data = json_decode($head, true);
        curl_close($ch);
        $spreadsheet = $report->createOrderProductCategorySpreadsheet($db_data);
        $params['filename'] = '??????????????????????????????';
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
        $week_arr = array("???", "???", "???", "???", "???", "???", "???");
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
            $now_date = mktime(0, 0, 0, intval(substr($value['????????????'], 4, 2)), intval(substr($value['????????????'], 6, 2)), intval(substr($value['????????????'], 0, 4)));
            $now_days = round(($now_date - $date_begin) / 3600 / 24) + 1;

            if (in_array(($value['??????'].$value['????????????']), $uid)) {
                if(!isset($result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2][$now_days])) {
                    $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2][$now_days] = 0;
                }
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2][$now_days] += $value['???????????????'];
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2]['total'] += $value['???????????????'];
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2]['days'] += 1;
                if(!isset($result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1][$now_days])) {
                    $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1][$now_days] = 0;
                }
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1][$now_days] += $value['????????????'];
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1]['total'] += $value['????????????'];
                $result_staff[array_search(($value['??????'].$value['????????????']), $uid)*2 + 1]['days'] += 1;
            } else {
                array_push($result_staff, ['uid' => $value['??????'], 'name' => $value['??????'], 'line' => $value['??????????????????'], 'type' => '??????', "{$now_days}" => $value['???????????????'], 'total' => $value['???????????????'], 'days' => 1, 'avg' => 0]);
                array_push($result_staff, ['uid' => $value['??????'], 'name' => $value['??????'], 'line' => $value['??????????????????'], 'type' => '????????????', "{$now_days}" => $value['????????????'], 'total' => $value['????????????'], 'days' => 1, 'avg' => 0]);
                array_push($uid, ($value['??????'].$value['????????????']));
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
        $params['filename'] = '???????????????-??????????????????' . $params['begin'] . '-' . $params['end'];
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
            $data[$key]['company'] = '??????';
        }
        foreach($data as $key => $value){
            $month = substr(substr_replace($value['????????????'], '/', 4, 0), 0, 7);
            if(!in_array($value['company'], $company_arr)) {
                array_push($company_arr, $value['company']);
                array_push($result_tmp, [
                    ['company' => $value['company'], 'type' => '??????', array_search($month, $result_date)+1 => $value['????????????'], 'avg' => $value['????????????']], 
                    ['company' => $value['company'], 'type' => '????????????', array_search($month, $result_date)+1 => $value['????????????'], 'avg' => $value['????????????']], 
                    ['company' => $value['company'], 'type' => '????????????', array_search($month, $result_date)+1 => $value['????????????'], 'avg' => $value['????????????']]
                ]);
            }
            else {
                $result_tmp[array_search($value['company'], $company_arr)][0][array_search($month, $result_date)+1] += $value['????????????'];
                $result_tmp[array_search($value['company'], $company_arr)][0]['avg'] += $value['????????????'];
                $result_tmp[array_search($value['company'], $company_arr)][1][array_search($month, $result_date)+1] += $value['????????????'];
                $result_tmp[array_search($value['company'], $company_arr)][1]['avg'] += $value['????????????'];
                $result_tmp[array_search($value['company'], $company_arr)][2][array_search($month, $result_date)+1] += $value['????????????'];
                $result_tmp[array_search($value['company'], $company_arr)][2]['avg'] += $value['????????????'];
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
        $params['filename'] = '???????????????-??????????????????' . $params['begin'] . '-' . $params['end'];
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
        $params['filename'] = '??????????????????????????????' . $params['date_begin'] . '-' . $params['date_end'];
        return $this->exportSpreadsheet($params, $spreadsheet);
    }

    public function getExportOuterSecantComparisonSpreadsheet($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        $params = $report->convertDateFormat($params);
        $db_data = $this->exportOuterSecantComparison($request, $response, $args, true);
        $spreadsheet = $report->createExportOuterSecantComparisonSpreadsheet($db_data, $params);
        $params['filename'] = '??????????????????????????????' . $params['begin'] . '-' . $params['end'];
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
