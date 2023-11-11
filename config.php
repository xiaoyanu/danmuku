<?php
class Api
{
    function mysql()
    {
        // 数据库信息
        return array(
            'host' => 'localhost', //数据库地址
            'user' => '用户名', //用户名
            'password' => '密码', //密码
            'port' => '3306', //数据库端口
            'database' => '数据库名', //数据库名
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

    function hex_rgb($hex)
    {
        // 16进制颜色转10进制
        $red = hexdec(substr($hex, 1, 2)); // 提取红色部分并转换为十进制
        $green = hexdec(substr($hex, 3, 2)); // 提取绿色部分并转换为十进制
        $blue = hexdec(substr($hex, 5, 2)); // 提取蓝色部分并转换为十进制
        $decimalColor = $red * 65536 + $green * 256 + $blue; // 计算十进制颜色值
        return  $decimalColor;
    }
}
