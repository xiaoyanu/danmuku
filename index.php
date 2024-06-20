<?php
// 弹幕加载上传失败？配置一下伪静态咯
// Apache：如果你使用的是Apache服务器，可以在 .htaccess 文件中添加以下内容：
// <IfModule mod_headers.c>
// Header set Access-Control-Allow-Origin "*"
// </IfModule>
// 
// Nginx：在 Nginx 中，你可以在你的配置文件中的 server 块内添加以下内容：
// add_header 'Access-Control-Allow-Origin' '*';
include 'config.php';
$Api = new Api;
$sql = $Api->mysql();

$id = $_GET['id'];
$type = $_REQUEST['type']; //弹幕位置或返回JSON、XML类型
$mode = $_GET['mode']; //专门适配某个播放器

// 提交弹幕
$player = $_POST['player'];
$text = $_POST['text'];
$color = $_POST['color'];
$time = $_POST['time'];

// 插入弹幕
if (!empty($player) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($player) && !empty($text) && !empty($color) && $type !== '' && $time !== "" && $text !== "") {
        header("Content-Type: application/json;charset=utf-8");
        $con = mysqli_connect($sql['host'], $sql['user'], $sql['password'], $sql['database'], $sql['port']);
        if ($con == false) {
            header("Content-Type: application/json;charset=utf-8");
            echo json_encode(['code' => 404, 'msg' => '与数据库连接失败，请检查config.php中的数据库项是否正确'], 448);
            die;
        }
        if ($Api->isUrl($player)) {
            echo json_encode(['code' => 404, 'msg' => '弹幕发送失败，请提交MD5值，不支持提交链接'], 448);
        } else {
            // 限制提交最大长度100字
            $text = str_replace('<', '&lt;', $text);
            $text = str_replace('>', '&gt;', $text);
            if ($Api->add($con, $player, $time, $type, $color, mb_substr($text, 0, 100, 'utf-8'))) {
                echo json_encode(['code' => 200, 'msg' => '弹幕发送成功'], 448);
            } else {
                echo json_encode(['code' => 404, 'msg' => '弹幕发送失败'], 448);
            }
            mysqli_close($con);
        }
    } else {
        echo json_encode(['code' => 404, 'msg' => '参数不能为空，请确保填写了所有参数，并以POST方式提交'], 448);
    }
    die;
}

// 获取弹幕
if (!empty($type) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($id)) {
        switch ($type) {
            case 'xml':
                header("Content-Type: text/xml;charset=utf-8");
                $data = getDanmu($id, $mode);
                switch ($mode) {
                    case 'artplayer':
                        for ($i = 0; $i < count($data); $i++) {
                            $data[$i]['mode'] = str_replace("0", "1", $data[$i]['mode']);
                            $data[$i]['mode'] = str_replace("2", "4", $data[$i]['mode']);
                            $d = $d . '<d p="' . $data[$i]['time'] . ',' . $data[$i]['mode'] . ',25,' . $Api->hex_RgbInt($data[$i]['color']) . ',' . $data[$i]['timestamp'] . ',,,' . ($i + 1) . '">' . $data[$i]['text'] . '</d>';
                        }
                        break;
                    default:
                        for ($i = 0; $i < count($data); $i++) {
                            $d = $d . '<d p="' . $data[$i][0] . ',' . $data[$i][1] . ',25,' . $Api->hex_RgbInt($data[$i][2]) . ',' . $data[$i][4] . ',,,' . ($i + 1) . '">' . $data[$i][3] . '</d>';
                        }
                        break;
                }
                echo '<?xml version="1.0" encoding="utf-8"?><i><code>' . count($data) . '</code><id>' . $id . '</id>' . $d . '</i>';
                break;


            case 'json':
                header("Content-Type: application/json;charset=utf-8");
                $data = getDanmu($id, $mode);
                switch ($mode) {
                    case "artplayer":
                        for ($i = 0; $i < count($data); $i++) {
                            $data[$i]['mode'] = (int)str_replace("1", "0", $data[$i]['mode']);
                            $data[$i]['mode'] = (int)str_replace("5", "1", $data[$i]['mode']);
                            $data[$i]['mode'] = (int)str_replace("4", "2", $data[$i]['mode']);
                        }
                        echo json_encode($data);
                        break;
                    default:
                        echo json_encode([
                            'code' => 200,
                            'id' => $id,
                            'count' => count($data),
                            'danmuku' => $data
                        ]);
                        break;
                }
                break;


            default:
                header("Content-Type: application/json;charset=utf-8");
                echo json_encode(['code' => 404, 'msg' => 'type类型不正确，可用：xml或json'], 448);
                break;
        }
    } else {
        header("Content-Type: application/json;charset=utf-8");
        echo json_encode(['code' => 404, 'msg' => '参数id不能都为空'], 448);
    }
    die;
}

function getDanmu($id_url, $mode)
// 获取弹幕
{
    global $sql, $Api;
    // 弹幕数组顺序：time、type、color、text、timestamp
    if ($Api->isUrl($id_url) == false) {
        $is_url = false;
    } else {
        $is_url = true;
    }
    // 判断是不是链接，如果不是则从数据库中获取弹幕
    if ($is_url == false) {
        $con = mysqli_connect($sql['host'], $sql['user'], $sql['password'], $sql['database'], $sql['port']);
        if ($con == false) {
            header("Content-Type: application/json;charset=utf-8");
            echo json_encode(['code' => 404, 'msg' => '与数据库连接失败，请检查config.php中的数据库项是否正确'], 448);
            die;
        }
        $sql = mysqli_query($con, "SELECT * FROM `danmu` WHERE `player` = '$id_url' ORDER BY `time` ASC");
        $count = mysqli_num_rows($sql);
        $db = [];
        if ($count < 1) {
            $db[] = ["1", 5, '#4994c4', '请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
            $count = 1;
        } else {
            $db[] = ['1', 5, '#4994c4', '有' . $count . '条弹幕正在赶来，请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
        }
        while ($row = mysqli_fetch_array($sql, MYSQLI_ASSOC)) {
            $db[] = [
                $row['time'],
                $row['type'],
                $row['color'],
                $row['text'],
                $row['timestamp'],
            ];
        }
        mysqli_close($con);
    } else {
        $pt = whoUrl($id_url);
        switch ($pt) {
            default:
                $web[] = ['1', 5, '#4994c4', '请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
                $count = 1;
                break;
            case 'b站':
                // https://www.bilibili.com/video/BV1mf421B7rZ/
                $bv = $Api->GetTextRight($id_url, "video/");
                if (strpos($bv, "/") !== false) {
                    $bv = $Api->GetTextLeft($bv, "/");
                }
                $cid = json_decode($Api->CurlGet("https://api.bilibili.com/x/player/pagelist?bvid=$bv", false), true);
                $cid = $cid['data'][0]['cid'];
                if (empty($cid)) {
                    $count = 1;
                    $web[] = ["1", 5, "#4994c4", '请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
                } else {
                    $dm = $Api->GetBilibiliXml("https://api.bilibili.com/x/v1/dm/list.so?oid=$cid", false);
                    $dm = simplexml_load_string($dm);
                    // 获取弹幕内容
                    $json = json_encode($dm);
                    $nr = json_decode($json, true);
                    $nr = $nr['d'];
                    // 获取弹幕参数
                    $cs = [];
                    foreach ($dm->d as $d) {
                        $pValue = (string)$d['p'];
                        $psz = explode(",", $pValue);
                        $cs[] = [$psz[0], $psz[1], $psz[3], $psz[4]];
                    }
                    // 重新组装
                    $count = count($nr);
                    if ($count > 3000) {
                        $count = 3000;
                    }
                    $web = [];
                    if ($count < 1) {
                        $web[] = ["1", 5, "#4994c4", '请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
                        $count = 1;
                    } else {
                        $web[] = ['1', 5, "#4994c4", '有' . $count . '条弹幕正在赶来，请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
                    }
                    for ($i = 0; $i < $count; $i++) {
                        $nr[$i] = str_replace('<', '&lt;', $nr[$i]);
                        $nr[$i] = str_replace('>', '&gt;', $nr[$i]);
                        $web[] = [
                            $cs[$i][0],
                            $cs[$i][1],
                            $Api->num_hexColor($cs[$i][2]),
                            $nr[$i],
                            $cs[$i][3]
                        ];
                    }
                }
                break;
            case 'qq':
                $dm = $Api->GetQQXml("https://fc.lyz05.cn/?url=$id_url");
                if (strpos($dm, "xml") !== false) {
                    $dm = simplexml_load_string($dm);
                }else{
                    $dm = $Api->GetQQ2Xml($id_url);
                    if (strpos($dm, "xml") !== false) {
                        $dm = simplexml_load_string($dm);
                    }
                }
                // 获取弹幕内容
                $json = json_encode($dm);
                $nr = json_decode($json, true);
                if (empty($nr['d'][0])) {
                    $count = 1;
                    $web[] = ["1", 5, "#4994c4", '请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
                } else {
                    $nr = $nr['d'];
                    // 获取弹幕参数
                    $cs = [];
                    foreach ($dm->d as $d) {
                        $pValue = (string)$d['p'];
                        $psz = explode(",", $pValue);
                        $cs[] = [$psz[0], $psz[1], $psz[3], $psz[4]];
                    }
                    // 重新组装
                    $count = count($nr);
                    if ($count > 3000) {
                        $count = 3000;
                    }
                    $web = [];
                    if ($count < 1) {
                        $web[] = ["1", 5, "#4994c4", '请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
                        $count = 1;
                    } else {
                        $web[] = ['1', 5, "#4994c4", '有' . $count . '条弹幕正在赶来，请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
                    }
                    for ($i = 0; $i < $count; $i++) {
                        $nr[$i] = str_replace('<', '&lt;', $nr[$i]);
                        $nr[$i] = str_replace('>', '&gt;', $nr[$i]);
                        $web[] = [
                            $cs[$i][0],
                            $cs[$i][1],
                            $Api->num_hexColor($cs[$i][2]),
                            $nr[$i],
                            $cs[$i][3]
                        ];
                    }
                }
                break;
        }
    }

    // 最后根据播放器返回结果【#】
    $data = [];
    switch ($mode) {
        case "artplayer":
            if ($is_url == false) {
                if ($count > 0) {
                    for ($z = 0; $z < count($db); $z++) {
                        $data[] = [
                            "time" => (float)$db[$z][0],
                            "mode" => (int)$db[$z][1],
                            "color" => $db[$z][2],
                            "text" => $db[$z][3],
                            "timestamp" => $db[$z][4]
                        ];
                    }
                }
            } else {
                if ($count > 0) {
                    for ($z = 0; $z < count($web); $z++) {
                        $data[] = [
                            "time" => (float)$web[$z][0],
                            "mode" => (int)$web[$z][1],
                            "color" => $web[$z][2],
                            "text" => $web[$z][3],
                            "timestamp" => $web[$z][4]
                        ];
                    }
                }
            }
            break;
        default:
            if ($is_url == false) {
                if ($count > 0) {
                    for ($z = 0; $z < count($db); $z++) {
                        $data[] = [
                            (float)$db[$z][0],
                            (int)$db[$z][1],
                            $db[$z][2],
                            $db[$z][3],
                            $db[$z][4]
                        ];
                    }
                }
            } else {
                if ($count > 0) {
                    for ($z = 0; $z < count($web); $z++) {
                        $data[] = [
                            (float)$web[$z][0],
                            (int)$web[$z][1],
                            $web[$z][2],
                            $web[$z][3],
                            $web[$z][4]
                        ];
                    }
                }
            }
            break;
    }
    return $data;
}

function whoUrl($url)
// 检测哪个平台的链接
{
    $q = 1008611;
    if (strpos($url, "v.qq.com") !== false) {
        $q = 'qq';
    }
    if (strpos($url, "bilibili.com") !== false) {
        $q = 'b站';
    }
    return $q;
}

?>

<!--
                ii.                                         ;9ABH,          
               SA391,                                    .r9GG35&G          
               &#ii13Gh;                               i3X31i;:,rB1         
               iMs,:,i5895,                         .5G91:,:;:s1:8A         
                33::::,,;5G5,                     ,58Si,,:::,sHX;iH1        
                Sr.,:;rs13BBX35hh11511h5Shhh5S3GAXS:.,,::,,1AG3i,GG        
                .G51S511sr;;iiiishS8G89Shsrrsh59S;.,,,,,..5A85Si,h8        
               :SB9s:,............................,,,.,,,SASh53h,1G.       
            .r18S;..,,,,,,,,,,,,,,,,,,,,,,,,,,,,,....,,.1H315199,rX,       
          ;S89s,..,,,,,,,,,,,,,,,,,,,,,,,....,,.......,,,;r1ShS8,;Xi       
        i55s:.........,,,,,,,,,,,,,,,,.,,,......,.....,,....r9&5.:X1       
       59;.....,.     .,,,,,,,,,,,...        .............,..:1;.:&s       
      s8,..;53S5S3s.   .,,,,,,,.,..      i15S5h1:.........,,,..,,:99       
      93.:39s:rSGB@A;  ..,,,,.....    .SG3hhh9G&BGi..,,,,,,,,,,,,.,83      
      G5.G8  9#@@@@@X. .,,,,,,.....  iA9,.S&B###@@Mr...,,,,,,,,..,.;Xh     
      Gs.X8 S@@@@@@@B:..,,,,,,,,,,. rA1 ,A@@@@@@@@@H:........,,,,,,.iX:    
     ;9. ,8A#@@@@@@#5,.,,,,,,,,,... 9A. 8@@@@@@@@@@M;    ....,,,,,,,,S8    
     X3    iS8XAHH8s.,,,,,,,,,,...,..58hH@@@@@@@@@Hs       ...,,,,,,,:Gs   
    r8,        ,,,...,,,,,,,,,,,,,...  ,h8XABMMHX3r.          .,,,,,,,.rX:  
   :9, .    .:,..,:;;;::,.,,,,,..          .,,.               ..,,,,,,.59  
  .Si      ,:.i8HBMMMMMB&5,....                    .            .,,,,,,.sMr
  SS       :: h@@@@@@@@@@#; .                     ...  .         ..,,,,iM5
  91  .    ;:.,1&@@@@@@MXs.                            .          .,,:,:&S
  hS ....  .:;,,,i3MMS1;..,..... .  .     ...                     ..,:,.99
  ,8; ..... .,:,..,8Ms:;,,,...                                     .,::.83
   s&: ....  .sS553B@@HX3s;,.    .,;13h.                            .:::&1
    SXr  .  ...;s3G99XA&X88Shss11155hi.                             ,;:h&,
     iH8:  . ..   ,;iiii;,::,,,,,.                                 .;irHA  
      ,8X5;   .     .......                                       ,;iihS8Gi
        1831,                                                 .,;irrrrrs&@
           ;5A8r.                                            .:;iiiiirrss1H
             :X@H3s.......                                .,:;iii;iiiiirsrh
              r#h:;,...,,.. .,,:;;;;;:::,...              .:;;;;;;iiiirrss1
             ,M8 ..,....,.....,,::::::,,...         .     .,;;;iiiiiirss11h
             8B;.,,,,,,,.,.....          .           ..   .:;;;;iirrsss111h
            i@5,:::,,,,,,,,.... .                   . .:::;;;;;irrrss111111
            9Bi,:,,,,......                        ..r91;;;;;iirrsss1ss1111
-->

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/ico" href="https://zxz.ee/favicon.ico">
    <title>公共弹幕库</title>
    <meta name="description" content="一个长期免费公共的弹幕库，提供稳定的弹幕接口，欢迎使用。">
    <meta name="keywords" content="弹幕库,弹幕系统,弹幕后台,弹幕API,弹幕接口,公共弹幕">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div id="box">
        <div id="info">
            <p>公共弹幕库</p>
            <p style="font-size: 15px;">
                [ 此弹幕库为公共弹幕库，欢迎使用 ]
            </p>
        </div>
        <div class="one">
            <h1>弹幕礼仪</h1>
            <div id="ly">
                <p>尊重他人和内容： 不发布辱骂、侮辱、人身攻击等不良言论，尊重主播、其他观众以及内容创作者。</p>
                <p>避免刷屏： 不要连续发送大量弹幕，以免占据过多的屏幕空间，影响其他人的观看体验。</p>
                <p>合理使用表情和颜色： 适度使用表情和颜色，但不要过度使用，以免影响其他人的阅读。</p>
                <p>遵循主题： 在直播或视频评论中，尽量保持评论与内容主题相关，避免离题讨论。</p>
                <p>不泄露个人信息： 不要在弹幕中透露自己或他人的个人信息，包括手机号、地址等。</p>
                <p>避免敏感话题： 不发布与政治、宗教、种族等敏感话题相关的评论，以避免引发争议和冲突。</p>
                <p>不恶意抢楼： 不要在其他人发送的弹幕下迅速发送评论，以便自己的评论排在前面。</p>
                <p>理性表达意见： 如果对内容有意见或建议，可以用理性和文明的语言表达，避免过于激烈的言辞。</p>
            </div>
        </div>
        <div class="one" style="user-select:auto">
            <h1>弹幕接口信息</h1>
            <br>
            <div class="card" id="doc2">
                <div class="card-header">
                    获取弹幕
                </div>
                <div class="card-body">
                    <p>请求方式：GET</p>
                    <p>请求URL：</p>
                    <p>
                        <code>https://danmu.zxz.ee/?type=&amp;id=</code>
                    </p>
                    请求参数说明：
                    <table class="table table-hover table-striped table-bordered table-sm">
                        <thead>
                            <tr>
                                <th scope="col">参数名</th>
                                <th scope="col">参数类型</th>
                                <th scope="col">参数说明</th>
                                <th scope="col">参数示例</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>type</td>
                                <td>Text</td>
                                <td>返回类型</td>
                                <td>可填：json或xml<br>xml为Bilibili格式</td>
                            </tr>
                            <tr>
                                <td>id</td>
                                <td>Text</td>
                                <td>视频标识</td>
                                <td>视频链接MD5或平台链接<br><br>已支持平台：<br>b站、腾讯视频</td>
                            </tr>
                            <tr>
                                <td>mode</td>
                                <td>Text</td>
                                <td>播放器</td>
                                <td>专门适配指定播放器，可留空<br>目前已适配：artplayer</td>
                            </tr>
                        </tbody>
                    </table>
                    <p>例如：https://danmu.zxz.ee/?type=json&id=59056300aa9416f4470038223a374993</p>
                    <p>例如（专门适配播放器）：<br>https://danmu.zxz.ee/?mode=artplayer&type=json&id=59056300aa9416f4470038223a374993</p>
                </div>
            </div>
            <br><br>
            <div class="card" id="doc2">
                <div class="card-header">
                    发送弹幕
                </div>
                <div class="card-body">
                    <p>请求方式：POST</p>
                    <p>请求URL：</p>
                    <p>
                        <code>https://danmu.zxz.ee</code>
                    </p>
                    请求参数说明：
                    <table class="table table-hover table-striped table-bordered table-sm">
                        <thead>
                            <tr>
                                <th scope="col">参数名</th>
                                <th scope="col">参数类型</th>
                                <th scope="col">参数说明</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>player</td>
                                <td>Text</td>
                                <td>视频链接MD5，对应着获取弹幕中的id参数</td>
                            </tr>
                            <tr>
                                <td>text</td>
                                <td>Text</td>
                                <td>弹幕文本</td>
                            </tr>
                            <tr>
                                <td>color</td>
                                <td>RGB/16进制RGB</td>
                                <td>弹幕颜色，根据你的播放器决定，通常16进制</td>
                            </tr>
                            <tr>
                                <td>time</td>
                                <td>Number</td>
                                <td>弹幕出现时间</td>
                            </tr>
                            <tr>
                                <td>type</td>
                                <td>Int</td>
                                <td>弹幕位置</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div id="footer">
        交流/反馈QQ群：<a href="https://qm.qq.com/q/rDi8Kj7H7G" target="_blank">431887641</a>
    </div>
    <script>
        console.log("\n\n\n %c 弹幕管理系统 By：小言u %c https://github.com/xiaoyanu/danmuku", "color:#fff;background:linear-gradient(90deg,#448bff,#44e9ff);padding:5px 0;", "color:#000;background:linear-gradient(90deg,#44e9ff,#ffffff);padding:5px 10px 5px 0px;");
    </script>
</body>

</html>
