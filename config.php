<?php
class Api
{
    function mysql()
    {
        // 数据库信息
        return array(
            'host' => 'localhost', //数据库地址
            'user' => '', //用户名
            'password' => '', //密码
            'port' => '3306', //数据库端口
            'database' => '', //数据库名
        );
    }

    function is_hex_color($color)
    {
        // 检测是否16进制颜色
        if (preg_match("/^#([A-Fa-f0-9]{6})$/", $color)) {
            return true;
        } else {
            return false;
        }
    }

    function add($con, $player, $time, $type, $color, $text)
    {
        // 向数据库中插入记录
        return mysqli_query($con, "INSERT INTO `danmu` (`player`, `text`, `color`, `time`, `type`, `timestamp`) VALUES ('$player', '$text', '$color', '$time', '$type', '" . time() . "');");
    }

    function isUrl($string)
    {
        // 使用 filter_var 函数来验证 URL  
        $url = filter_var($string, FILTER_VALIDATE_URL);
        // 如果 $url 不为空（即验证成功），则返回 true，否则返回 false  
        return $url !== false;
    }

    function GetIP()
    // 随机国内IP
    {
        $ip_long = array(
            array('607649792', '608174079'), //36.56.0.0-36.63.255.255
            array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
            array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
            array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
            array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
            array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
            array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
            array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
            array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
            array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
        );
        $rand_key = mt_rand(0, 9);
        $ip = long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
        return $ip;
    }

    function CurlGet($url, $randip = true, $代理IP = '', $代理端口 = '')
    // CurlGet请求
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0); //设置header
        //模拟IP
        if ($randip) {
            $ip = $this->GetIP();
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("CLIENT-IP: $ip", "X-FORWARDED-FOR: $ip", "Host: " . $this->Getdomain($url)));
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Host: " . $this->Getdomain($url)));
        }
        if ($代理IP !== "" && $代理端口 !== "") {
            curl_setopt($curl, CURLOPT_PROXY, $代理IP); //代理服务器地址
            curl_setopt($curl, CURLOPT_PROXYPORT, $代理端口); //代理服务器端口
            curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // 如果使用的是HTTP代理，需要指定代理类型
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }

    function CurlPost($url, $randip = true, $post_data = "", $代理IP = '', $代理端口 = '')
    // CurlPost请求
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0); //设置header
        //模拟IP
        if ($randip) {
            $ip = $this->GetIP();
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("CLIENT-IP: $ip", "X-FORWARDED-FOR: $ip", "Host: " . $this->Getdomain($url)));
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Host: " . $this->Getdomain($url)));
        }
        if ($代理IP !== "" && $代理端口 !== "") {
            curl_setopt($curl, CURLOPT_PROXY, $代理IP); //代理服务器地址
            curl_setopt($curl, CURLOPT_PROXYPORT, $代理端口); //代理服务器端口
            curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // 如果使用的是HTTP代理，需要指定代理类型
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        if (empty($post_data) !== true) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }

    function GetTextRight($text, $findText)
    // 取文本右边
    {
        $left = strpos($text, $findText);
        return substr($text, $left + strlen($findText));
    }

    function GetTextLeft($text, $findText)
    // 取文本左边
    {
        $right = strpos($text, $findText);
        return substr($text, 0, $right);
    }

    function GetTextCenter($text, $headText, $endText)
    // 取中间文本
    {
        $left = strpos($text, $headText);
        $right = strpos($text, $endText, $left);
        if ($left < 0 or $right < $left) return '';
        return substr($text, $left + strlen($headText), $right - $left - strlen($headText));
    }

    function Getdomain($url)
    // 获取一段URL中的域名
    {
        // 使用正则表达式来匹配主域名（包括子域名）  
        if (preg_match("/(?P<scheme>https?:\/\/)?(?P<domain>(?:[a-z0-9][a-z0-9-]*[a-z0-9]\.)+[a-z]{2,6})(?:\/|$)/i", $url, $matches)) {
            // $matches['domain'] 是我们想要的主域名（包括子域名）  
            $domain = $matches['domain'];
            return $domain;
        } else {
            return "没有找到匹配的域名";
        }
    }

    function GetBilibiliXml($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                'Accept: */*',
                'Host: api.bilibili.com',
                'Connection: keep-alive'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    function num_hexColor($num)
    {
        // 提取RGB分量  
        $red = ($num >> 16) & 0xFF;
        $green = ($num >> 8) & 0xFF;
        $blue = $num & 0xFF;
        // 将RGB分量转换为16进制字符串，并确保它们都是两位数  
        $hexRed = str_pad(dechex($red), 2, '0', STR_PAD_LEFT);
        $hexGreen = str_pad(dechex($green), 2, '0', STR_PAD_LEFT);
        $hexBlue = str_pad(dechex($blue), 2, '0', STR_PAD_LEFT);
        // 拼接成完整的16进制颜色代码  
        $hexColor = '#' . $hexRed . $hexGreen . $hexBlue;
        return $hexColor;
    }

    function hex_RgbInt($hexColor)
    {
        $hexColor = str_replace('#', '', $hexColor);
        while (strlen($hexColor) < 6) {
            $hexColor .= 'F';
        }
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));
        return ($r << 16) + ($g << 8) + $b;
    }
}
