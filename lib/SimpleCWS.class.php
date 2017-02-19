<?php
// 词典文件为 XDB
define ( 'SCWS_XDICT_XDB', 1 );
// 将词典全部加载到内存里
define ( 'SCWS_XDICT_MEM', 2 );
// 词典文件为 TXT（纯文本）
define ( 'SCWS_XDICT_TXT', 3 );

// 不进行复合分词
define ( 'SCWS_MULTI_NONE', 4 );
// 短词复合
define ( 'SCWS_MULTI_SHORT', 5 );
// 散字二元复合
define ( 'SCWS_MULTI_DUALITY', 6 );
// 重要单字
define ( 'SCWS_MULTI_ZMAIN', 7 );
// 全部单字
define ( 'SCWS_MULTI_ZALL', 8 );

/**
 *
 * 创建并返回一个 SimpleCWS 类操作对象
 *
 * @return SimpleCWS|false
 */
function scws_new() {
}
/**
 * 创建并返回一个分词操作句柄
 *
 * @return resource
 */
function scws_open() {
}
/**
 * 关闭一个已打开的 scws 分词操作句柄
 *
 * @param resource $scws_handle
 * @return true
 */
function scws_close($scws_handle) {
}
/**
 * 设定分词词典、规则集、欲分文本字符串的字符集
 *
 * @param resource $scws_handle
 * @param string $charset
 *        	要新设定的字符集，目前只支持 utf8 和 gbk。（注：默认为 gbk，utf8不要写成utf-8）
 * @return bool 始终为true
 */
function scws_set_charset($scws_handle, $charset) {
}
/**
 * 添加分词所用的词典，新加入的优先查找
 *
 * @param resource $scws_handle
 * @param string $dict_path
 *        	词典的路径，可以是相对路径或完全路径。（遵循安全模式下的 open_basedir）
 * @param int $mode
 *        	可选，表示加载的方式。其值有：
 *        	SCWS_XDICT_TXT 表示要读取的词典文件是文本格式，可以和后2项结合用
 *        	SCWS_XDICT_XDB 表示直接读取 xdb 文件（此为默认值）
 *        	SCWS_XDICT_MEM 表示将 xdb 文件全部加载到内存中，以 XTree 结构存放，可用异或结合另外2个使用。
 * @return bool
 */
function scws_add_dict($scws_handle, $dict_path, $mode = SCWS_XDICT_XDB) {
}
/**
 * 设定分词所用的词典并清除已存在的词典列表
 *
 * @param resource $scws_handle
 * @param string $dict_path
 *        	词典的路径，可以是相对路径或完全路径。（遵循安全模式下的 open_basedir）
 * @param int $mode
 *        	可选，表示加载的方式。参见 scws_add_dict()
 * @return bool
 */
function scws_set_dict($scws_handle, $dict_path, $mode = SCWS_XDICT_XDB) {
}
/**
 * 设定分词所用的新词识别规则集（用于人名、地名、数字时间年代等识别）
 *
 * @param resource $scws_handle
 * @param string $charset
 *        	规则集的路径，可以是相对路径或完全路径。（遵循安全模式下的 open_basedir）
 * @return bool
 */
function scws_set_rule($scws_handle, $rule_path) {
}
/**
 * 设定分词返回结果时是否去除一些特殊的标点符号之类
 *
 * @param resource $scws_handle
 * @param bool $yes
 *        	设定值，如果为 true 则结果中不返回标点符号，如果为 false 则会返回，缺省为 false。
 * @return bool
 */
function scws_set_ignore($scws_handle, $yes) {
}
/**
 * 设定分词返回结果时是否复式分割，如“中国人”返回“中国＋人＋中国人”三个词
 *
 * @param resource $scws_handle
 * @param int $mode
 *        	复合分词法的级别，缺省不复合分词。取值由下面几个常量异或组合（也可用 1-15 来表示）：
 *        	SCWS_MULTI_SHORT (1)短词
 *        	SCWS_MULTI_DUALITY (2)二元（将相邻的2个单字组合成一个词）
 *        	SCWS_MULTI_ZMAIN (4)重要单字
 *        	SCWS_MULTI_ZALL (8)全部单字
 * @return bool 始终为true
 */
function scws_set_multi($scws_handle, $mode) {
}
/**
 * 设定是否将闲散文字自动以二字分词法聚合
 *
 * @param resource $scws_handle
 * @param int $mode
 *        	设定值，如果为 true 则结果中多个单字会自动按二分法聚分，如果为 false 则不处理，缺省为 false。
 * @return bool 始终为true
 */
function scws_set_duality($scws_handle, $yes) {
}
/**
 * 发送设定分词所要切割的文本
 * 注意：
 * 系统底层处理方式为对该文本增加一个引用，故不论多长的文本并不会造成内存浪费；
 * 执行本函数时，若未加载任何词典和规则集，则会自动试图在 ini 指定的缺省目录下查找缺省字符集的词典和规则集。
 *
 * @param resource $scws_handle
 * @param string $text
 *        	要切分的文本的内容
 * @return bool
 */
function scws_send_text($scws_handle, $text) {
}
/**
 * 根据 send_text 设定的文本内容，返回一系列切好的词汇
 * 注意：
 * 每次切词后本函数应该循环调用，直到返回 false 为止，因为程序每次返回的词数是不确定的。
 *
 * @param resource $scws_handle
 * @return mixed 成功返回切好的词汇组成的数组，若无更多词汇，返回 false。返回的词汇包含的键值如下：
 *         word _string_ 词本身
 *         idf _float_ 逆文本词频
 *         off _int_ 该词在原文本路的位置
 *         attr _string_ 词性
 */
function scws_get_result($scws_handle) {
}
/**
 * 根据 send_text 设定的文本内容，返回系统计算出来的最关键词汇列表
 *
 * @param resource $scws_handle
 * @param int $limit
 *        	可选参数，返回的词的最大数量，缺省是 10
 * @param string $xattr
 *        	可选参数，是一系列词性组成的字符串，各词性之间以半角的逗号隔开，
 *        	这表示返回的词性必须在列表中，如果以~开头，则表示取反，词性必须不在列表中，缺省为NULL，返回全部词性，不过滤。
 * @return mixed 成功返回统计好的的词汇组成的数组，返回 false。返回的词汇包含的键值如下：
 *         word _string_ 词本身
 *         times _int_ 词在文本中出现的次数
 *         weight _float_ 该词计算后的权重
 *         attr _string_ 词性
 */
function scws_get_tops($scws_handle, $limit = 10, $attr) {
}
/**
 * 根据 send_text 设定的文本内容，返回系统中是否包括符合词性要求的关键词
 *
 * @param resource $scws_handle
 * @param string $xattr
 *        	是一系列词性组成的字符串，各词性之间以半角的逗号隔开，
 *        	这表示返回的词性必须在列表中，如果以~开头，则表示取反，词性必须不在列表中，若为空则返回全部词。
 * @return mixed 如果有则返回 true，没有就返回 false
 */
function scws_has_word($scws_handle, $attr) {
}
/**
 * 根据 send_text 设定的文本内容，返回系统中词性符合要求的关键词汇
 *
 * @param resource $scws_handle
 * @param string $xattr
 *        	是一系列词性组成的字符串，各词性之间以半角的逗号隔开，
 *        	这表示返回的词性必须在列表中，如果以~开头，则表示取反，词性必须不在列表中，若为空则返回全部词。
 * @return bool 成功返回符合要求词汇组成的数组，返回 false。返回的词汇包含的键值参见 scws_get_result()
 */
function scws_get_words($scws_handle, $attr) {
}
/**
 * 返回 scws 版本号名称信息（字符串）
 *
 * @return string
 */
function version() {
}
class SimpleCWS {
	private $handle;
	/**
	 * 关闭一个已打开的 scws 分词操作句柄
	 *
	 * @return bool 始终为true
	 */
	function close();
	/**
	 * 设定分词词典、规则集、欲分文本字符串的字符集
	 *
	 * @param string $charset
	 *        	要新设定的字符集，目前只支持 utf8 和 gbk。（注：默认为 gbk，utf8不要写成utf-8）
	 * @return bool 始终为true
	 */
	function set_charset($charset);
	/**
	 * 添加分词所用的词典，新加入的优先查找
	 *
	 * @param string $dict_path
	 *        	词典的路径，可以是相对路径或完全路径。（遵循安全模式下的 open_basedir）
	 * @param int $mode
	 *        	可选，表示加载的方式。其值有：
	 *        	SCWS_XDICT_TXT 表示要读取的词典文件是文本格式，可以和后2项结合用
	 *        	SCWS_XDICT_XDB 表示直接读取 xdb 文件（此为默认值）
	 *        	SCWS_XDICT_MEM 表示将 xdb 文件全部加载到内存中，以 XTree 结构存放，可用异或结合另外2个使用。
	 * @return bool
	 */
	function add_dict($dict_path, $mode = SCWS_XDICT_XDB);
	/**
	 * 设定分词所用的词典并清除已存在的词典列表
	 *
	 * @param string $dict_path
	 *        	词典的路径，可以是相对路径或完全路径。（遵循安全模式下的 open_basedir）
	 * @param int $mode
	 *        	可选，表示加载的方式。参见 scws_add_dict()
	 * @return bool
	 */
	function set_dict($dict_path, $mode = SCWS_XDICT_XDB);
	/**
	 * 设定分词所用的新词识别规则集（用于人名、地名、数字时间年代等识别）
	 *
	 * @param string $charset
	 *        	规则集的路径，可以是相对路径或完全路径。（遵循安全模式下的 open_basedir）
	 * @return bool
	 */
	function set_rule($rule_path);
	/**
	 * 设定分词返回结果时是否去除一些特殊的标点符号之类
	 *
	 * @param bool $yes
	 *        	设定值，如果为 true 则结果中不返回标点符号，如果为 false 则会返回，缺省为 false。
	 * @return bool
	 */
	function set_ignore($yes);
	/**
	 * 设定分词返回结果时是否复式分割，如“中国人”返回“中国＋人＋中国人”三个词
	 *
	 * @param int $mode
	 *        	复合分词法的级别，缺省不复合分词。取值由下面几个常量异或组合（也可用 1-15 来表示）：
	 *        	SCWS_MULTI_SHORT (1)短词
	 *        	SCWS_MULTI_DUALITY (2)二元（将相邻的2个单字组合成一个词）
	 *        	SCWS_MULTI_ZMAIN (4)重要单字
	 *        	SCWS_MULTI_ZALL (8)全部单字
	 * @return bool 始终为true
	 */
	function set_multi($mode);
	/**
	 * 设定是否将闲散文字自动以二字分词法聚合
	 *
	 * @param int $mode
	 *        	设定值，如果为 true 则结果中多个单字会自动按二分法聚分，如果为 false 则不处理，缺省为 false。
	 * @return bool 始终为true
	 */
	function set_duality($yes);
	/**
	 * 发送设定分词所要切割的文本
	 * 注意：
	 * 系统底层处理方式为对该文本增加一个引用，故不论多长的文本并不会造成内存浪费；
	 * 执行本函数时，若未加载任何词典和规则集，则会自动试图在 ini 指定的缺省目录下查找缺省字符集的词典和规则集。
	 *
	 * @param string $text
	 *        	要切分的文本的内容
	 * @return bool
	 */
	function send_text($text);
	/**
	 * 根据 send_text 设定的文本内容，返回一系列切好的词汇
	 * 注意：
	 * 每次切词后本函数应该循环调用，直到返回 false 为止，因为程序每次返回的词数是不确定的。
	 *
	 * @return mixed 成功返回切好的词汇组成的数组，若无更多词汇，返回 false。返回的词汇包含的键值如下：
	 *         word _string_ 词本身
	 *         idf _float_ 逆文本词频
	 *         off _int_ 该词在原文本路的位置
	 *         attr _string_ 词性
	 */
	function get_result();
	/**
	 * 根据 send_text 设定的文本内容，返回系统计算出来的最关键词汇列表
	 *
	 * @param int $limit
	 *        	可选参数，返回的词的最大数量，缺省是 10
	 * @param string $xattr
	 *        	可选参数，是一系列词性组成的字符串，各词性之间以半角的逗号隔开，
	 *        	这表示返回的词性必须在列表中，如果以~开头，则表示取反，词性必须不在列表中，缺省为NULL，返回全部词性，不过滤。
	 * @return mixed 成功返回统计好的的词汇组成的数组，返回 false。返回的词汇包含的键值如下：
	 *         word _string_ 词本身
	 *         times _int_ 词在文本中出现的次数
	 *         weight _float_ 该词计算后的权重
	 *         attr _string_ 词性
	 */
	function get_tops($limit = 10, $xattr);
	/**
	 * 根据 send_text 设定的文本内容，返回系统中是否包括符合词性要求的关键词
	 *
	 * @param string $xattr
	 *        	是一系列词性组成的字符串，各词性之间以半角的逗号隔开，
	 *        	这表示返回的词性必须在列表中，如果以~开头，则表示取反，词性必须不在列表中，若为空则返回全部词。
	 * @return mixed 如果有则返回 true，没有就返回 false
	 */
	function has_word($xattr);
	/**
	 * 根据 send_text 设定的文本内容，返回系统中词性符合要求的关键词汇
	 *
	 * @param string $xattr
	 *        	是一系列词性组成的字符串，各词性之间以半角的逗号隔开，
	 *        	这表示返回的词性必须在列表中，如果以~开头，则表示取反，词性必须不在列表中，若为空则返回全部词。
	 * @return bool 成功返回符合要求词汇组成的数组，返回 false。返回的词汇包含的键值参见 scws_get_result()
	 */
	function get_words($xattr);
	/**
	 * 返回 scws 版本号名称信息（字符串），1.2.2
	 *
	 * @return string
	 */
	function version();
}