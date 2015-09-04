<?php
namespace Baiy;

/**
 * 模板处理类
 * $template = new \Baiy\Template($option,Closure $tpl_fun);
 * assign|get|display|displayJs|fetch
 * 支持模板继承 extend|block
 */
class Template {
	private $option = array(
		'http_cache_control' => 'private', // 网页缓存控制
		'cache_path' => '', // 模板缓存路径
		'tpl_path' => '', // 模板路径
		'tpl_cache_time' => 0, // 模板缓存时间
		'suffix' => '.html', // 模板文件后缀
		'cache_suffix' => '.php', // 模板文件后缀
		'debug' => false,
	);
	private $vars = array();

	//模板标识符处理方法(匿名函数)
	private $tpl_fun;

	public function __construct($option = array(),\Closure $tpl_fun = NULL) {
		$this->option = array_merge($this->option, $option);
		if (empty($this->option['tpl_path'])) {
			throw new \Exception('模板目录没有设置');
		}

		$this->option['tpl_path'] = rtrim($this->option['tpl_path'], '/\\') . '/';
		$this->option['cache_path'] = rtrim($this->option['cache_path'], '/\\') . '/';

		define('BAIY_TEMPLATE', True);
		$this->tpl_fun = $tpl_fun;
	}

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
	 */
	public function display($tpl = '') {
		header('Cache-control: ' . $this->option['http_cache_control']); // 页面缓存控制
		echo $this->fetch($tpl);
	}

	/**
	 * 输出模板(js)
	 * @param string $tpl  模板定位符
	 * @param boolean  $is_document_write 是否直接js输出
	 */
	public function displayJs($tpl = '', $is_document_write = true) {
		$string = addslashes(str_replace(array("\r", "\n", "\t"), array('', '', ''), $this->fetch($tpl)));
		return $is_document_write ? 'document.write("' . $string . '");' : $string;
	}

	/**
	 * 加载模板
	 * @param  string $tpl  模板定位符
	 */
	public function fetch($tpl = '') {
		if(!empty($this->tpl_fun)){
			$func = $this->tpl_fun;
			$tpl = $func($tpl);
		}
		$temp = function ($vars, $content) {
			ob_start();
			ob_implicit_flush(0);
			extract($vars, EXTR_OVERWRITE);
			eval('?>' . $content);
			$content = ob_get_clean();
			return $content;
		};

		$tpl = trim($tpl, '\\/');

		$tpl_content = '';

		if ($this->checkCache($tpl)) {
			$tpl_content = file_get_contents($this->getTplCachePath($tpl));
		} else {
			$tpl_content = $this->compiler($tpl);
		}
		return $temp($this->vars, $tpl_content);
	}

	/**
	 * 检测模板缓存是否有效
	 */
	private function checkCache($tpl) {
		$file = $this->getTplPath($tpl);

		// 开启调试
		if ($this->option['debug']) {
			return false;
		}

		// 是否配置缓存目录
		if (!$this->option['cache_path']) {
			return false;
		}

		$cache_tpl_path = $this->getTplCachePath($tpl);

		if (!is_file($cache_tpl_path)) {
			return false;
		}

		if (filemtime($file) > filemtime($cache_tpl_path)) {
			//模板文件如果有更新则缓存需要更新
			return false;
		}

		if ($this->option['tpl_cache_time'] != 0 && filemtime($cache_tpl_path) + $this->option['tpl_cache_time'] < time()) {
			// 缓存是否在有效期
			return false;
		}
		return true;
	}

	/**
	 * 编译模板
	 */
	private function compiler($tpl) {
		$tpl_path = $this->getTplPath($tpl);
		$tpl_content = file_get_contents($tpl_path);
		// 解析继承模板
		$tpl_content = $this->parseExtend($tpl_content);
		// 解析模板语法
		$tpl_content = $this->parse($tpl_content);
		// 优化生成的php代码
		$tpl_content = str_replace('?><?php', '', $tpl_content);

		// 是否配置缓存目录
		if ($this->option['cache_path']) {
			$cache_tpl_path = $this->getTplCachePath($tpl);
			$pathinfo = pathinfo($cache_tpl_path);
			if (!is_dir($pathinfo['dirname'])) {
				mkdir($pathinfo['dirname'], 0777, true);
			}
			file_put_contents($cache_tpl_path, '<?php if (!defined(\'BAIY_TEMPLATE\')) exit();?>' . $tpl_content);
		}
		return $tpl_content;
	}

	/**
	 * 获取模板路径
	 * @param  string $tpl  模板定位符
	 */
	private function getTplPath($tpl) {
		//获取模板位置
		$tpl_path = $this->option['tpl_path'] . $tpl . $this->option['suffix'];
		if (!is_file($tpl_path)) {
			throw new \Exception("模板不存在");
		}
		return $tpl_path;
	}

	/**
	 * 获取模板缓存路径
	 * @param  string $tpl  模板定位符
	 */
	private function getTplCachePath($tpl) {
		return $this->option['cache_path'] . $tpl . $this->option['cache_suffix'];
	}

	/**
	 * 解析继承模板
	 */
	private function parseExtend($content) {
		// 读取模板中的继承标签
		$find = preg_match('/<extend\s(.+?)\s*?\/>/is', $content, $matches);
		$preg = '/<block\sname=[\'"](.+?)[\'"]\s*?>(.*?)<\/block>/is';
		if ($find) {
			// 记录页面中的block标签
			$block = array();
			preg_replace_callback($preg,
				function ($matche) use (&$block) {
					$block[$matche[1]] = $matche[2];
					return '';
				},
				$content);

			$get_extend_content = function ($string, $ob) {
				$preg = '/name=[\'"](?<name>.+?)[\'"]/is';
				preg_match($preg, $string, $matches);
				return file_get_contents($ob->getTplPath($matches['name']));
			};

			// 获取模板内容
			$content = $get_extend_content($matches[1], $this);
			// 替换对应部分
			$content = preg_replace_callback($preg,
				function ($matche) use ($block) {
					return isset($block[$matche[1]]) ? $block[$matche[1]] : $matche[2];
				},
				$content);
		} else {
			$content = preg_replace_callback($preg,
				function ($match) {
					return $match[2];
				},
				$content);
		}
		return $content;
	}

	/**
	 * 模板解析
	 */
	private function parse($str) {
		$ob = $this;
		//清除缩进(tab)
		$str = preg_replace("/([\n\r]+)\t+/s", "\\1", $str);

		//清除注释(<!– –>),方便后续操作
		$str = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $str);

		//子模板载入 使用方法 {template('public/head')}
		$str = preg_replace_callback("/\{template\([\'|\"](.+?)[\'|\"]\)\}/i", function($matche) use($ob){
			return $ob->parse(file_get_contents($ob->getTplPath($matche[1])));
		}, $str);

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
		$str = preg_replace("/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s", "<?php echo \\1;?>", $str);
		return $str;
	}
}
