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

// 获取Redis缓存实例
$redisCache = RedisCache::getInstance();

// 获取各种参数
$id = $_GET['id']; // URL/MD5
// - 提交弹幕
$player = $_POST['player']; // MD5
$text = $_POST['text']; // 弹幕内容
$color = $_POST['color']; // 弹幕颜色
$time = $_POST['time']; // 弹幕出现时间
$type = $_REQUEST['type']; // 弹幕位置或返回JSON、XML类型
$mode = $_GET['mode']; // 专门适配某个播放器

// 如果是URL链接，则简单净化一下
if (!empty($id)) {
    // 正则匹配是否含有链接
    if (strpos($id, "://") !== false) {
        preg_match_all('/https?:\/\/\S+/', $id, $matches);
        if (count($matches) > 0) {
            $id = $matches[0][0];
            if (strpos($id, "?") !== false) {
                // 如果是链接则截取?之前的内容
                $id = $Api->GetTextLeft($id, "?");
            }
        }
    }
}

// 配置项
$max_conunt = 500; // 截取最大弹幕数量，从第三方获取时

// 插入弹幕
if (!empty($player) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($player) && !empty($text) && !empty($color) && $type !== '' && $time !== "" && $text !== "") {
        // 限制提交弹幕类型
        if ($Api->isUrl($player) || !$Api->is_md5($player)) {
            $Api->echo_json(['code' => 404, 'msg' => '弹幕发送失败，请提交MD5值，不支持提交链接']);
        } else {
            $con = $Api->getDbConnection();
            if ($con == false) {
                $Api->echo_json(['code' => 404, 'msg' => '与数据库连接失败，请检查数据库项是否正确']);
                die;
            }
            // 限制提交最大长度100字
            if ($Api->add($con, $player, $time, $type, $color, $Api->XmlToHtml(mb_substr($text, 0, 100, 'utf-8')))) {
                // 清除相关缓存，确保新数据能被获取到
                $redisCache->delete($player);
                $Api->echo_json(['code' => 200, 'msg' => '弹幕发送成功']);
            } else {
                $Api->echo_json(['code' => 404, 'msg' => '弹幕发送失败']);
            }
        }
    } else {
        $Api->echo_json(['code' => 404, 'msg' => '参数不能为空，请确保填写了所有参数，并以POST方式提交']);
    }
    die;
}

// 获取弹幕
if (!empty($type) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($id)) {
        // 限制获取弹幕类型
        if ($Api->whoUrl($id) == 10086) {
            if ($Api->is_md5($id) == false) {
                $Api->echo_json(['code' => 404, 'msg' => '弹幕获取失败，不是标准的MD5或提交了尚未支持的平台链接']);
                die;
            }
        }
        switch ($type) {
            case 'xml':
                header("Content-Type: text/xml;charset=utf-8");
                $data = getDanmu($id, $mode);
                $d = '';
                // B站XML格式返回
                foreach ($data as $index => $item) {
                    $item['mode'] = str_replace("0", "1", $item['mode']);
                    $item['mode'] = str_replace("2", "4", $item['mode']);
                    $d .= '<d p="' . $item[0] . ',' . $item[1] . ',25,' . $Api->hex_RgbInt($item[2]) . ',' . $item[4] . ',,,' . ($index + 1) . '">' . $item[3] . '</d>';
                }
                // 将Url转换为Md5
                if ($Api->isUrl($id)) {
                    $id = md5($id);
                }
                echo '<?xml version="1.0" encoding="utf-8"?><i><code>' . count($data) . '</code><id>' . $id . '</id>' . $d . '</i>';
                break;


            case 'json':
                $data = getDanmu($id, $mode);
                // 将Url转换为Md5
                if ($Api->isUrl($id)) {
                    $id = md5($id);
                }
                $Api->echo_json([
                    'code' => 200,
                    'id' => $id,
                    'count' => count($data),
                    'danmuku' => $data
                ]);
                break;


            default:
                $Api->echo_json(['code' => 404, 'msg' => 'type类型不正确，可用：xml或json']);
                break;
        }
    } else {
        $Api->echo_json(['code' => 404, 'msg' => '参数id不能为空']);
    }
    die;
}

/**
 * 获取弹幕
 * @param string $id_url 弹幕id或url
 * @param string $mode 播放器格式
 * @return array 弹幕数组
 */
function getDanmu($id_url, $mode)
{
    global $sql, $Api, $max_conunt, $redisCache, $type;

    // 检测是不是URL链接
    if ($Api->isUrl($id_url)) {
        $userUrl = $id_url; // 存储用户提交的URL
        $id_url = md5($userUrl); // 将URL转为MD5
    }
    $cacheKey = $id_url; // 指定缓存键值

    // 尝试从Redis缓存获取数据
    $cachedData = $redisCache->get($cacheKey);
    if ($cachedData !== false) {
        // 如果有缓存，则直接获取缓存内容
        $db = $cachedData;
        $cachedData = [];
        $count = count($db);
    } else {
        // 如果没有，则走正常判断
        // 获取数据库弹幕数量
        $con = $Api->getDbConnection();
        if ($con == false) {
            $Api->echo_json(['code' => 404, 'msg' => '与数据库连接失败，请检查数据库项是否正确']);
            die;
        }

        // 使用预处理语句防止SQL注入
        $stmt = mysqli_prepare($con, "SELECT * FROM `danmu` WHERE `player` = ? ORDER BY `time` ASC");
        mysqli_stmt_bind_param($stmt, "s", $id_url);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        // 查询数量结果
        $count = mysqli_num_rows($result);
        $db = [];

        // 判断数据库中是否有弹幕，有的话则从数据库获取，没有则从第三方获取弹幕
        if ($count > 0) {
            $db[] = ['1', 5, '#4994c4', '有' . $count . '条弹幕正在赶来，请遵守弹幕礼仪，祝您观影愉快~', (string)time()];

            // 从数据库中获取弹幕
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $db[] = [
                    $row['time'],
                    $row['type'],
                    $row['color'],
                    $row['text'],
                    $row['timestamp'],
                ];
            }
            mysqli_stmt_close($stmt);
        } else {
            // 从第三方获取弹幕
            $pt = $Api->whoUrl($userUrl);

            switch ($pt) {
                // 什么平台也不是 或者 真的是0弹幕，返回默认弹幕
                case 10086:
                    $db[] = ["1", 5, '#4994c4', '请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
                    $count = 1;
                    break;
                case 'b站':
                    $db = getBlibili($userUrl, $Api, $max_conunt);
                    $count = count($db);
                    $upDb = $db;
                    array_shift($upDb); // 删除第一个元素，删除默认弹幕
                    // 将获取的弹幕存入弹幕库
                    if (count($upDb) > 0) {
                        $Api->UploadWebDanmu($sql, $id_url, $upDb);
                    }
                    break;
                default:
                    $jsonData = json_decode(file_get_contents('https://dm.itcxo.cn/?ac=dm&url=' . $userUrl), true);
                    if ($jsonData['code'] !== 23 && count($jsonData['danmuku']) < 2) {
                        $db[] = ["1", 5, '#4994c4', '请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
                        $count = 1;
                    } else {
                        foreach ($jsonData['danmuku'] as $item) {
                            $db[] = [
                                $item[0], //出现时间
                                $item[1], //位置
                                $item[2], //颜色
                                $item[4], //内容
                                (string)time() //timestamp
                            ];
                        }
                        array_shift($db); // 删除第一个元素，删除默认弹幕
                        $count = count($db);
                        if ($count > $max_conunt) {
                            $db = $Api->Array_GetNum($db, $max_conunt);
                            $count = count($db);
                        }
                        if (count($db) > 0) {
                            $Api->UploadWebDanmu($sql, $id_url, $db);
                        }

                        // 在最前面添加一条弹幕
                        array_unshift($db, ['1', 5, '#4994c4', '有' . $count . '条弹幕正在赶来，请遵守弹幕礼仪，祝您观影愉快~', (string)time()]);
                    }
                    break;
            }
        }

        // 将结果存入Redis缓存
        if ($redisCache->isAvailable()) {
            $expireTime = 3600; // 存1小时
            $redisCache->set($cacheKey, $db, $expireTime);
        }
    }


    // 最后根据播放器返回结果【#】
    $data = [];
    switch ($mode) {
        case "artplayer":
            if ($count > 0) {
                if ($type == "json") {
                    foreach ($db as $item) {
                        $modeValue = $item[1];
                        $modeValue = str_replace("1", "0", $modeValue);
                        $modeValue = str_replace("5", "1", $modeValue);
                        $modeValue = str_replace("4", "2", $modeValue);
                        $data[] = [
                            "time" => (float)$item[0],
                            "mode" => (int)$modeValue,
                            "color" => $item[2],
                            "text" => $item[3],
                            "timestamp" => $item[4]
                        ];
                    }
                } else {
                    foreach ($db as $item) {
                        $data[] = [
                            (float)$item[0],
                            (int)$item[1],
                            $item[2],
                            $item[3],
                            $item[4]
                        ];
                    }
                }
            }
            break;
        default:
            if ($count > 0) {
                foreach ($db as $item) {
                    $data[] = [
                        (float)$item[0], // 弹幕时间
                        (int)$item[1], // 弹幕位置
                        $item[2], // 弹幕颜色
                        $item[3], // 弹幕内容
                        $item[4] // 弹幕时间戳
                    ];
                }
            }
            break;
    }
    return $data;
}



function getBlibili($userUrl, $Api, $max_conunt)
{
    // https://www.bilibili.com/video/BV1mf421B7rZ/
    // https://www.bilibili.com/bangumi/play/ep780019
    if (strpos($userUrl, "bangumi") !== false) {
        $bv = $Api->GetTextRight($userUrl, "/ep");
        if (strpos($bv, "?") !== false) {
            $bv = $Api->GetTextLeft($bv, "?");
        }
        $cid = json_decode($Api->CurlGet("https://api.bilibili.com/pgc/view/web/season?ep_id=$bv", false), true);
        $cid = $cid['result']['episodes'][0]['cid'];
    } else {
        $bv = $Api->GetTextRight($userUrl, "video/");
        if (strpos($bv, "/") !== false) {
            $bv = $Api->GetTextLeft($bv, "/");
        }
        $cid = json_decode($Api->CurlGet("https://api.bilibili.com/x/player/pagelist?bvid=$bv", false), true);
        $cid = $cid['data'][0]['cid'];
    }
    if (empty($cid)) {
        $count = 1;
        $web[] = ["1", 5, "#4994c4", '请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
    } else {
        $dm = $Api->GetBilibiliXml("https://api.bilibili.com/x/v1/dm/list.so?oid=$cid", false);
        $dm = $Api->XmlToHtml($dm);
        $dm = simplexml_load_string($dm);
        // 获取弹幕内容
        $json = json_encode($dm);
        $nr = json_decode($json, true);
        $nr = $nr['d'];
        $count = count($nr);

        // 获取弹幕参数
        $cs = [];
        foreach ($dm->d as $d) {
            $pValue = (string)$d['p'];
            $psz = explode(",", $pValue);
            $cs[] = [$psz[0], $psz[1], $psz[3], $psz[4]];
        }

        // 判断弹幕条数是否超过最大条数，如果超过则进行裁切
        if ($count > $max_conunt) {
            $nr = $Api->Array_GetNum($nr, $max_conunt);
            $cs = $Api->Array_GetNum($cs, $max_conunt);
            $count = count($nr);
        }

        // 重新组装
        $web = [];
        if ($count < 1) {
            $web[] = ["1", 5, "#4994c4", '请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
            $count = 1;
        } else {
            $web[] = ['1', 5, "#4994c4", '有' . $count . '条弹幕正在赶来，请遵守弹幕礼仪，祝您观影愉快~', (string)time()];
        }
        $i = 0;
        foreach ($cs as $key => $item) {
            if ($i < $count) {
                $web[] = [
                    $item[0],
                    $item[1],
                    $Api->num_hexColor($item[2]),
                    $Api->XmlToHtml($nr[$key]),
                    $item[3]
                ];
            } else {
                break;
            }
            $i++;
        }
    }
    return $web;
}

?>


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
                                <td>视频链接MD5或平台链接<br><br>已支持平台：<br>b站、腾讯视频、爱奇艺、芒果TV、优酷</td>
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
    <script>
        var _hmt = _hmt || [];
        (function() {
            var hm = document.createElement("script");
            hm.src = "https://hm.baidu.com/hm.js?679eeaa45e6261f40f83c25cb3743b48";
            var s = document.getElementsByTagName("script")[0];
            s.parentNode.insertBefore(hm, s);
        })();
    </script>

</body>

</html>
