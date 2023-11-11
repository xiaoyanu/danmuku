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
$player = $_POST['player'];
$text = $_POST['text'];
$color = $_POST['color'];
$time = $_POST['time'];
$type = $_REQUEST['type'];

if ($id !== null || $player !== null) {
    $con = mysqli_connect($sql['host'], $sql['user'], $sql['password'], $sql['database'], $sql['port']);
    // 判断是否连接数据库成功
    if ($con) {
        // 获取弹幕
        if ($id !== null) {
            $sql = mysqli_query($con, "SELECT * FROM `danmu` WHERE `player` = '$id'");
            $data = array();
            if (mysqli_num_rows($sql) < 1) {
                $data[] = ['1', '5', '#4994c4', '请遵守弹幕礼仪，祝您观影愉快~', time()];
            } else {
                $data[] = ['1', '5', '#4994c4', '有' . mysqli_num_rows($sql) . '条弹幕正在赶来，请遵守弹幕礼仪，祝您观影愉快~', time()];
            }
            while ($row = mysqli_fetch_array($sql, MYSQLI_ASSOC)) {
                $data[] = [
                    $row['time'],
                    $row['type'],
                    $row['color'],
                    $row['text'],
                    $row['timestamp'],
                ];
            }

            $count = count($data);

            // 判别输出格式
            if ($type == "json") {
                header("Content-Type: application/json;charset=utf-8");
                echo json_encode([
                    'code' => 200,
                    'id' => $id,
                    'count' => $num,
                    'count' => $count,
                    'danmu' => $data
                ]);
            } else {
                header("Content-Type: text/xml;charset=utf-8");
                for ($i = 0; $i < $count; $i++) {
                    $d = $d . '<d p="' . $data[$i][0] . ',' . $data[$i][1] . ',25,' . $Api->hex_rgb($data[$i][2]) . ',' . $data[$i][4] . ',,,' . ($i + 1) . '">' . $data[$i][3] . '</d>';
                }
                echo '<?xml version="1.0" encoding="utf-8"?><i><code>' . count($data) . '</code><id>' . $id . '</id>' . $d . '</i>';
            }
        }

        // 插入弹幕
        if ($player !== null) {
            if ($player !== null && $text !== null && $color !== null && $type !== null && $time !== null) {
                if ($Api->is_hex_color($color) == false) {
                    $color = "#FFFFFF";
                }
                if ($Api->add($con, $player, $time, $type, $color, $text)) {
                    echo json_encode(['code' => 200, 'msg' => '弹幕发送成功']);
                } else {
                    echo json_encode(['code' => 404, 'msg' => '弹幕发送失败']);
                }
            } else {
                echo json_encode(['code' => 404, 'msg' => '参数不能为空，请确保填写了所有参数，并以POST方式提交']);
            }
        }
    } else {
        echo json_encode(['code' => 404, 'msg' => '与数据库连接失败，请检查config.php中的数据库项是否正确']);
    }
    mysqli_close($con);
    die;
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
    <meta name="description" content="一个长期免费公共的弹幕库，欢迎使用。">
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
        <div id="one">
            <h1>弹幕礼仪</h1>
            <p>尊重他人和内容： 不发布辱骂、侮辱、人身攻击等不良言论，尊重主播、其他观众以及内容创作者。</p>
            <p>避免刷屏： 不要连续发送大量弹幕，以免占据过多的屏幕空间，影响其他人的观看体验。</p>
            <p>合理使用表情和颜色： 适度使用表情和颜色，但不要过度使用，以免影响其他人的阅读。</p>
            <p>遵循主题： 在直播或视频评论中，尽量保持评论与内容主题相关，避免离题讨论。</p>
            <p>不泄露个人信息： 不要在弹幕中透露自己或他人的个人信息，包括手机号、地址等。</p>
            <p>避免敏感话题： 不发布与政治、宗教、种族等敏感话题相关的评论，以避免引发争议和冲突。</p>
            <p>不恶意抢楼： 不要在其他人发送的弹幕下迅速发送评论，以便自己的评论排在前面。</p>
            <p>理性表达意见： 如果对内容有意见或建议，可以用理性和文明的语言表达，避免过于激烈的言辞。</p>
        </div>
        <div id="two">
            <h1>弹幕接口信息</h1>
            </p>
            <p>https://danmu.zxz.ee</p>
            <br>
            <p>————获取弹幕————</p>
            <p> <b>id</b>：视频URL转MD5的文本</p>
            <p> <b>type</b>：json/xml（默认xml格式）</p>
            <p>例如：<br>
                <i>https://danmu.zxz.ee/?type=json&id=8dc54505e1a716b880c1a327d61f9198</i><br>
                <i>https://danmu.zxz.ee/?type=xml&id=8dc54505e1a716b880c1a327d61f9198</i>
            </p>
            <p>————发送弹幕————</p>
            <p> 提交方法：post</p>
            提交参数：
            <center>
                <table cellpadding="10" border="1">
                    <thead>
                        <tr>
                            <th>player</th>
                            <th>text</th>
                            <th>color</th>
                            <th>time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>视频URL转MD5的文本</td>
                            <td>弹幕文本</td>
                            <td>弹幕颜色</td>
                            <td>弹幕时间</td>
                        </tr>
                    </tbody>
                </table>
            </center>
        </div>
    </div>
    <div id="footer">
        如有问题请联系QQ：1872786834
    </div>
    <script>
        console.log("\n\n\n %c 弹幕管理系统 By：小言u %c https://github.com/xiaoyanu/danmuku", "color:#fff;background:linear-gradient(90deg,#448bff,#44e9ff);padding:5px 0;", "color:#000;background:linear-gradient(90deg,#44e9ff,#ffffff);padding:5px 10px 5px 0px;");
    </script>
</body>

</html>