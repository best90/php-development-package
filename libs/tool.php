<?php

class Tool
{
    /**
    * 将xml转换为数组
    * @param $xml  需要转化的xml
    * @return mixed
    */
    public static function xmlToArray($xml)
    {
        $ob = simplexml_load_string($xml);
        $json = json_encode($ob);
        $array = json_decode($json, true);
        return $array;
    }

    /**
    * 将数组转化成xml
    * @param $data 需要转化的数组
    * @return string
    */
    public static function dataToXml($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        $xml = '';
        foreach ($data as $key => $val) {
            if (is_null($val)) {
                $xml .= "<$key/>\n";
            } else {
                if (!is_numeric($key)) {
                    $xml .= "<$key>";
                }
                $xml .= (is_array($val) || is_object($val)) ? self::data_to_xml($val) : $val;
                if (!is_numeric($key)) {
                    $xml .= "</$key>";
                }
            }
        }
        return $xml;
    }

    /**
    * PHP post请求之发送XML数据
    * @param $url 请求的URL
    * @param $xmlData
    * @return mixed
    */
    public static function xmlPostRequest($url, $xmlData)
    {
        $header[] = "Content-type: text/xml";        //定义content-type为xml,注意是数组
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            print curl_error($ch);
        }
        curl_close($ch);
        return $response;
    }

    /**
    * PHP post请求之发送Json对象数据
    *
    * @param $url 请求url
    * @param $jsonStr 发送的json字符串
    * @return array
    */
    public static function httpPostJson($url, $jsonStr)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return array($httpCode, $response);
    }

    /**
    * PHP post请求之发送数组
    * @param $url
    * @param array $param
    * @return mixed
    * @throws Exception
    */
    public static function httpsPost($url, $param = array())
    {
        $ch = curl_init(); // 初始化一个 cURL 对象
        curl_setopt($ch, CURLOPT_URL, $url); // 设置需要抓取的URL
        curl_setopt($ch, CURLOPT_HEADER, 0); // // 设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        // 如果你想PHP去做一个正规的HTTP POST，设置这个选项为一个非零值。这个POST是普通的 application/x-www-from-urlencoded 类型，多数被HTML表单使用。
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param)); // 传递一个作为HTTP “POST”操作的所有数据的字符串。//http_build_query:生成 URL-encode 之后的请求字符串
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-type:application/x-www-form-urlencoded;charset=utf-8'
        ));

        $result = curl_exec($ch); // 运行cURL，请求网页
        if ($errno = curl_errno($ch)) {
            throw new Exception ('Curl Error(' . $errno . '):' . curl_error($ch));
        }
        curl_close($ch); // 关闭URL请求
        return $result; // 返回获取的数据
    }

    /**
    * 接收xml数据并转化成数组
    * @return array
    */
    public static function getRequestBean()
    {
        $bean = simplexml_load_string(file_get_contents('php://input')); // simplexml_load_string() 函数把 XML 字符串载入对象中。如果失败，则返回 false。
        $request = array();
        foreach ($bean as $key => $value) {
            $request [( string )$key] = ( string )$value;
        }
        return $request;
    }

    /**
    * 接收json数据并转化成数组
    * @return mixed
    */
    public static function getJsonData()
    {
        $bean = file_get_contents('php://input');
        $result = json_decode($bean, true);
        return $result;
    }

    /**
    * 翻译中英文字符串（调换位置）
    */
    public static function mStrrev($string)
    {
        $num = mb_strlen($string, 'utf-8');
        $new_string = "";
        for ($i = $num - 1; $i >= 0; $i--) {
            $char = mb_substr($string, $i, 1, 'utf-8');
            $new_string .= $char;
        }
        return $new_string;
    }

    /**
    * 判断当前服务器系统
    * @return string
    */
    public static function getOS()
    {
        if (PATH_SEPARATOR == ':') {
            return 'Linux';
        } else {
            return 'Windows';
        }
    }

    /**
    * 日志方法
    * @param $log
    */
    public static function writeLog($log)
    {
        $dir = __DIR__ . "/../Log/";
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $filename = $dir . date("Y-m-d") . ".log";
        file_put_contents($filename, date("Y-m-d H:i:s") . "\t" . $log . PHP_EOL, FILE_APPEND);
    }

    /**
    * 签名验证函数
    * @param $param   需要加密的字符串
    * @param $sign     第三方已经机密好的用来比对的字串
    * @return bool
    */
    public static function validateSign($param, $sign)
    {
        if (md5($param) == $sign) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 获取301、302真实地址
     * @param $url
     * @return mixed
     */
    public function parseBaiduUrl($url){
        sleep(1);
        $header = get_headers($url,1);
        if (strpos($header[0],'301') || strpos($header[0],'302')) {
            if(is_array($header['Location'])) {
                return $header['Location'][count($header['Location'])-1];
            }else{
                return $header['Location'];
            }
        }else {
            return $url;
        }
    }
}