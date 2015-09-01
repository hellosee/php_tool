<?php
namespace Baiy;

/**
 * 缓存类
 */
class Cache {

	private $handler;

	public function __construct($type, $option = array()) {
		switch ($type) {
			case 'file':
				$this->handler = new File();
				break;
			case 'mencache':
				$this->handler = new Mencache();
				break;
			case 'mysql':
				$this->handler = new Mysql();
				break;
			default:
				throw new \Exception('缓存类型设置不正确');
				break;
		}
		//初始化
		$this->handler->init($option);
	}

	public function set($name, $value, $expire = 0) {
		$this->checkName($name);
		return $this->handler->set($name, $value, $expire);
	}

	public function get($name) {
		$this->checkName($name);
		return $this->handler->get($name);
	}

	public function delete($name) {
		$this->checkName($name);
		return $this->handler->delete($name);
	}

	public function flush() {
		return $this->handler->flush();
	}

	private function checkName($name) {
		if (empty($name)) {
			throw new \Exception('缓存名称不能为空');
		}
	}
}

/**
 * 缓存类接口
 */
interface Base {
	public function init($option);
	public function set($name, $value, $expire = 0);
	public function get($name);
	public function delete($name);
	public function flush();
}

/**
 * 文件缓存类
 * 配置参数 array('paht'=>'缓存路径','type'=>'serialize|array');
 */
class File implements Base {
	private $path;
	private $type;
	public function init($option = array()) {
		if (!isset($option['path'])) {
			throw new \Exception('必须设置缓存路径');
		}

		if (!is_dir($option['path'])) {
			mkdir($option['path'], 0777, true);
		}

		if (!$this->dir_writeable($option['path'])) {
			throw new \Exception('[文件缓存]缓存目录不可写');
		}

		$this->path = rtrim($option['path'], '\\/') . DIRECTORY_SEPARATOR;
		$this->type = $option['type'] == 'serialize' ? 'serialize' : 'array';
	}

	public function set($name, $value, $expire = 0) {
		$file = $this->path . self::cacheName($name);
		if ($expire > 0) {
			$value = array('content' => $value, md5('expire') => time() + $expire);
		}
		if($this->type == 'array'){
			$value = "<?php\nreturn ".var_export($value, true).";\n?>";
		}
		else{
			$value = serialize($value);
		}
		file_put_contents($file, $value);
		return;
	}

	public function get($name) {
		$file  = $this->path . self::cacheName($name);
		$value = '';
		if (is_file($file)) {
			if($this->type == 'array'){
				$cache = include $file;
			}
			else{
				$cache = unserialize(file_get_contents($file));
			}

			if (!isset($cache[md5('expire')])) {
				$value = $cache;
			} else {
				if (time() <= $cache[md5('expire')]) {
					$value = $cache['content'];
				}
			}
		}
		return $value;
	}
	public function delete($name) {
		$file = $this->path . self::cacheName($name);
		if (is_file($file)) {
			@unlink($file);
		}
		return;
	}

	public function flush() {
		$path  = $this->path;
		$files = scandir($path);
		if ($files) {
			foreach ($files as $file) {
				if ($file != '.' && $file != '..' && is_dir($path . $file)) {
					array_map('unlink', glob($path . $file . '/*.*'));
				} elseif (is_file($path . $file)) {
					unlink($path . $file);
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * 缓存文件名
	 */
	private static function cacheName($name) {
		return md5($name) . '.cache.db';
	}

	/**
	 * 检查目录是否可写
	 */
	private function dir_writeable($path) {
		$testfile = $path . "/test.test";

		$fp = @fopen($testfile, "wb");
		if ($fp) {
			@fclose($fp);
			@unlink($testfile);
			return true;
		}
		return false;
	}
}

class Mencache{

}

class Mysql{

}
