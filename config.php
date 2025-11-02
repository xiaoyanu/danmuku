<?php
class Api
{
    // 数据库连接池
    private static $dbConnections = array();

    // 获取数据库配置
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
        // 注意，请在378行配置Redis！！！不配置则是默认配置
    }

    // 获取或创建数据库连接（连接池实现）
    function getDbConnection()
    {
        $config = $this->mysql();
        $connKey = $config['host'] . ':' . $config['port'] . ':' . $config['database'];

        // 检查连接是否存在且有效
        if (isset(self::$dbConnections[$connKey])) {
            $connection = self::$dbConnections[$connKey];
            if ($connection) {
                return $connection;
            }
        }

        // 创建新连接
        $con = mysqli_connect($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);

        if ($con) {
            // 设置连接编码
            mysqli_set_charset($con, 'utf8mb4');
            // 保存连接到连接池
            self::$dbConnections[$connKey] = $con;
        }

        return $con;
    }

    // 关闭所有数据库连接
    function closeAllConnections()
    {
        foreach (self::$dbConnections as $key => $connection) {
            if ($connection) {
                mysqli_close($connection);
                unset(self::$dbConnections[$key]);
            }
        }
    }

    function is_hex_color($color)
    {
        // 检测是否16进制颜色
        if (preg_match("/^#([A-Fa-f0-9]{6})$", $color)) {
            return true;
        } else {
            return false;
        }
    }

    function add($con, $player, $time, $type, $color, $text)
    {
        // 预处理语句防止SQL注入
        $stmt = mysqli_prepare($con, "INSERT INTO `danmu` (`player`, `text`, `color`, `time`, `type`, `timestamp`) VALUES (?, ?, ?, ?, ?, ?)");
        $timestamp = time();
        mysqli_stmt_bind_param($stmt, "ssssss", $player, $text, $color, $time, $type, $timestamp);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
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

    /**
     * 取文本右边
     * @param string $text 输入文本
     * @param string $findText 查找文本
     * @return string 右边文本
     */
    function GetTextRight($text, $findText)
    {
        $left = strpos($text, $findText);
        return substr($text, $left + strlen($findText));
    }

    /**
     * 取文本左边
     * @param string $text 输入文本
     * @param string $findText 查找文本
     * @return string 左边文本
     */
    function GetTextLeft($text, $findText)
    {
        $right = strpos($text, $findText);
        return substr($text, 0, $right);
    }

    /**
     * 取中间文本
     * @param string $text 输入文本
     * @param string $headText 头文本
     * @param string $endText 尾文本
     * @return string 中间文本
     */
    function GetTextCenter($text, $headText, $endText)
    {
        $left = strpos($text, $headText);
        $right = strpos($text, $endText, $left);
        if ($left < 0 or $right < $left) return '';
        return substr($text, $left + strlen($headText), $right - $left - strlen($headText));
    }

    /**
     * 获取一段URL中的域名
     * @param string $url 输入URL
     * @return string 域名
     */
    function Getdomain($url)
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

    /**
     * 获取哔哩哔哩视频的XML数据
     * @param string $url 哔哩哔哩视频URL
     * @return string XML数据
     */
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

    /**
     * 将RGB整数转换为16进制颜色代码
     * @param int $num RGB整数，例如0xFF0000
     * @return string 16进制颜色代码，例如#FF0000
     */
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

    /**
     * 将16进制颜色代码转换为RGB整数
     * @param string $hexColor 16进制颜色代码，例如#FF0000
     * @return int RGB整数，例如0xFF0000
     */
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

    /**
     * 从数组中抽取指定数量数组
     * @param array $array 输入数组
     * @param int $max_count 最大抽取数量
     * @return array 抽取后的数组
     */
    function Array_GetNum($array, $max_count)
    // 从中每隔N个抽取一个
    {
        $new = [];
        $i = 0;
        $num = (int)count($array) / $max_count;
        $num = $num + 1;
        while ($i < $max_count) {
            if (isset($array[$i * $num])) {
                $new[] = $array[$i * $num];
            } else {
                break;
            }
            $i++;
        }
        return $new;
    }

    /**
     * 上传弹幕数组到弹幕库，不要包含默认弹幕
     * @param string $sql 数据库连接对象
     * @param string $md5 弹幕id或url的MD5值
     * @param array $array 弹幕数组，每个元素为一个弹幕，格式为[time, type, color, text, timestamp]
     * @return bool 是否上传成功
     */
    function UploadWebDanmu($sql, $md5, $array)
    // 上传数组到弹幕库
    {
        if (count($array) > 0) {
            // 使用连接池获取数据库连接，而不是直接创建连接
            $con = $this->getDbConnection();
            if (strpos($array[0][3], "请遵守弹幕礼仪") !== false) {
                array_shift($array);
            }
            foreach ($array as $value) {
                $this->add($con, $md5, $value[0], $value[1], $value[2], $value[3]);
            }
        }
        return true;
    }

    /**
     * 将XML内容转换为HTML格式
     * @param string $需要被处理的xml内容 XML内容字符串
     * @return string 处理后的HTML内容字符串
     */
    function XmlToHtml($需要被处理的xml内容)
    {
        // 转义XML内容，防止报错
        $parts = preg_split('/(<[^>]*>)/', $需要被处理的xml内容, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        foreach ($parts as $part) {
            if (preg_match('/<[^>]*>/', $part)) {
                $result .= $part;
            } else {
                $result .= str_replace(
                    array('&', '<', '>', "'", '"', ''),
                    array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;', '·'),
                    $part
                );
            }
        }
        return $result;
    }

    /**
     * 检查字符串是否为有效的MD5值
     * @param string $str 待检查的字符串
     * @return bool 是否为有效的MD5值
     */
    function is_md5($str)
    {
        // MD5格式：32位十六进制字符（不区分大小写）
        return is_string($str) && strlen($str) === 32 && preg_match('/^[0-9a-fA-F]{32}$/', $str);
    }

    function echo_json($array)
    // 输出JSON
    {
        header("Content-type:application/json;charset=utf-8");
        $array["api_source"] =  "公共弹幕库：https://danmu.zxz.ee";
        echo json_encode($array, 448);
    }

    /**
     * 检测哪个平台的链接
     * @param string $url 待检测的URL字符串
     * @return string 不支持的平台则返回10086
     */
    function whoUrl($url)
    {
        $q = 10086;
        if (strpos($url, "v.qq.com") !== false) {
            $q = 'qq';
        }
        if (strpos($url, "bilibili.com") !== false) {
            $q = 'b站';
        }
        if (strpos($url, "iqiyi.com") !== false) {
            $q = '爱奇艺';
        }
        if (strpos($url, "mgtv.com") !== false) {
            $q = '芒果TV';
        }
        if (strpos($url, "youku.com") !== false) {
            $q = '优酷';
        }
        return $q;
    }
}

class RedisCache
{
    private $redis;
    private static $instance;

    // Redis配置
    private $config = array(
        'host' => '127.0.0.1', // Redis服务器地址
        'port' => 6379,       // Redis端口
        'password' => '',     // Redis密码，如果没有则为空
        'timeout' => 1.0,     // 连接超时时间（秒），考虑到2G内存配置，设置合理超时
        'database' => 0,
        'prefix' => 'XiaoYanUdanmu:'  // 键前缀，避免与其他应用冲突
    );

    // 单例模式，避免重复连接Redis
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // 构造函数，连接Redis
    private function __construct()
    {
        try {
            $this->redis = new Redis();
            $this->redis->connect($this->config['host'], $this->config['port'], $this->config['timeout']);

            // 如果有密码，进行认证
            if (!empty($this->config['password'])) {
                $this->redis->auth($this->config['password']);
            }

            // 选择数据库
            $this->redis->select($this->config['database']);
        } catch (Exception $e) {
            // 记录错误日志或执行其他错误处理
            error_log('Redis连接失败: ' . $e->getMessage());
            $this->redis = false;
        }
    }

    // 检查Redis是否可用
    public function isAvailable()
    {
        return $this->redis !== false;
    }

    // 获取缓存
    public function get($key)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $key = $this->config['prefix'] . $key;
        $data = $this->redis->get($key);
        if ($data === false) {
            return false;
        }
        return json_decode($data, true);
    }

    // 设置缓存，默认1小时过期，考虑到2G内存可能有限，可适当调整过期时间
    public function set($key, $value, $expire = 3600)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $key = $this->config['prefix'] . $key;
        $data = json_encode($value);
        return $this->redis->setex($key, $expire, $data);
    }

    // 删除缓存
    public function delete($key)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $key = $this->config['prefix'] . $key;
        return $this->redis->del($key);
    }

    // 检查缓存是否存在
    public function exists($key)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $key = $this->config['prefix'] . $key;
        return $this->redis->exists($key);
    }

    // 批量设置缓存
    public function mset($items, $expire = 3600)
    {
        if (!$this->isAvailable() || empty($items)) {
            return false;
        }

        $pipe = $this->redis->multi(Redis::PIPELINE);
        foreach ($items as $key => $value) {
            $prefixedKey = $this->config['prefix'] . $key;
            $pipe->setex($prefixedKey, $expire, json_encode($value));
        }
        $pipe->exec();
        return true;
    }
}
