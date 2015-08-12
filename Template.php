<?php
namespace Baiy;

/**
 * 模板处理类
 */
class Template {
	private $vars = array();

	public function __construct() {}
	/**
	 * 模板变量赋值
	 * @param  string|array $name  变量名|数组赋值
	 * @param  mixed $value 变量值
	 */
	public function assign($name, $value = '') {
		if (is_array($name)) {
			$this->vars = array_merge($this->vars, $name);
		} else {
			$this->vars[$name] = $value;
		}
		return true;
	}

	/**
	 * 获取模板变量
	 * @param  string $name 变量名 为空获取全部
	 */
	public function get($name = '') {
		if (empty($name)) {
			return $this->vars;
		}
		return isset($this->vars[$name]) ? $this->vars[$name] : '';
	}

	/**
	 * 输出模板
	 * @param  string $tpl  模板定位符
	 * @param  string $suffix 模板文件后缀
	 */
	public function display($tpl, $suffix = "") {
		extract($this->vars, EXTR_OVERWRITE);
		eval('?>' . $this->parse($this->getTplContent($this->getTplPath($tpl, $suffix))));
	}

	/**
	 * 获取模板路径
	 * @param  string $tpl  模板定位符
	 * @param  string $suffix 模板文件后缀
	 * 实际使用需要对模板路径做重写
	 */
	private function getTplPath($tpl, $suffix = ".html") {
		if (empty($suffix)) {
			$suffix = ".html";
		}
		//获取模板位置
		$tpl_path = $tpl . $suffix;
		if (!is_file($tpl_path)) {
			throw new Exception("模板不存在");
		}
		return $tpl_path;
	}

	/**
	 * 转义 // 为 /
	 * @param $var	转义的字符
	 * @return 转义后的字符
	 */
	private function addQuote($var) {
		return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
	}

	/**
	 * 子模板处理
	 * @param  string $tpl  模板定位符
	 * @param  string $suffix 模板文件后缀
	 */
	private function LoadSonTpl($tpl, $suffix = '') {
		if (empty($tpl)) {
			return '';
		}
		return $this->parse($this->getTplContent($this->getTplPath($tpl, $suffix)));
	}

	/**
	 * 获取模板文件内容
	 * @param  string $tpl_path 模板路径
	 */
	private function getTplContent($tpl_path) {
		return file_get_contents($tpl_path);
	}

	/**
	 * 模板解析
	 */
	private function parse($str) {
		//清除缩进(tab)
		$str = preg_replace("/([\n\r]+)\t+/s", "\\1", $str);

		//清除注释(<!– –>),方便后续操作
		$str = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $str);

		//子模板载入 使用方法 {template('public/head')}
		$str = preg_replace("/\{template\([\'|\"](.+?)[\'|\"]\)\}/ie", "\$this->LoadSonTpl('$1')", $str);

		//文件包含
		$str = preg_replace("/\{include\s+(.+)\}/", "<?php include \\1; ?>", $str);

		//PHP代码
		$str = preg_replace("/\{php\s+(.+)\}/", "<?php \\1?>", $str);

		//if
		$str = preg_replace("/\{if\s+(.+?)\}/", "<?php if(\\1) { ?>", $str);
		$str = preg_replace("/\{else\}/", "<?php } else { ?>", $str);
		$str = preg_replace("/\{elseif\s+(.+?)\}/", "<?php } elseif (\\1) { ?>", $str);
		$str = preg_replace("/\{\/if\}/", "<?php } ?>", $str);

		//for 循环
		$str = preg_replace("/\{for\s+(.+?)\}/", "<?php for(\\1) { ?>", $str);
		$str = preg_replace("/\{\/for\}/", "<?php } ?>", $str);

		//++ --
		$str = preg_replace("/\{\+\+(.+?)\}/", "<?php ++\\1; ?>", $str);
		$str = preg_replace("/\{\-\-(.+?)\}/", "<?php ++\\1; ?>", $str);
		$str = preg_replace("/\{(.+?)\+\+\}/", "<?php \\1++; ?>", $str);
		$str = preg_replace("/\{(.+?)\-\-\}/", "<?php \\1--; ?>", $str);

		//foreach
		$str = preg_replace("/\{loop\s+(\S+)\s+(\S+)\}/", "<?php if(is_array(\\1)) foreach(\\1 AS \\2) { ?>", $str);
		$str = preg_replace("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/", "<?php if(is_array(\\1)) foreach(\\1 AS \\2 => \\3) { ?>", $str);
		$str = preg_replace("/\{\/loop\}/", "<?php } ?>", $str);

		//变量输出 函数执行
		$str = preg_replace("/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $str);
		$str = preg_replace("/\{\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $str);
		$str = preg_replace("/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/", "<?php echo \\1;?>", $str);
		$str = preg_replace("/\{(\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\}/es", "\$this->addQuote('<?php echo \\1;?>')", $str);
		$str = preg_replace("/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s", "<?php echo \\1;?>", $str);
		return $str;
	}
}
