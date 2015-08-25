<?php
namespace Baiy;
use DOMDocument;

/**
 * RSS解析类 将RSS装换为数组
 *
 * @source https://github.com/zelenin/RSSGet
 * @example
 * <?php
 * $rssget_rss = new \Baiy\RSS( 'https://news.google.com/news/feeds?pz=1&cf=all&ned=us&hl=en&topic=h&num=3&output=rss' );
 * $rssget_atom = new \Baiy\RSS( 'https://github.com/zelenin/RSSGet/commits/master.atom' );
 * echo '<pre>';
 * print_r( $rssget_rss->getChannel() );
 * print_r( $rssget_rss->getItems() );
 * print_r( $rssget_atom->getChannel() );
 * print_r( $rssget_atom->getItems() );
 * ?>
 *
 */
class Rss extends DOMDocument {
	const VERSION = '1.1.0';
	private $_feed_url;
	private $_feed_content;
	private $_feed_type;
	private $_channel_tag;
	private $_item_tag;
	private $_channel = array();
	private $_items   = array();

	public function __construct($feed_url) {
		$this->_feed_url = $feed_url;
		$feed            = $this->_get($this->_feed_url);
		if ($feed['info']['http_code'] != 200) {
			return false;
		}

		$this->_feed_content = $this->_sanitizeString($feed['body']);
		$this->loadXML($this->_feed_content);
		$this->_checkType();

		$this->getChannel();
		$this->getItems();
	}

	private function _checkType() {
		if (is_object($this->getElementsByTagName('feed')->item(0))) {
			$this->_feed_type   = 'atom';
			$this->_channel_tag = 'feed';
			$this->_item_tag    = 'entry';
		} else {
			$this->_feed_type   = 'rss';
			$this->_channel_tag = 'channel';
			$this->_item_tag    = 'item';
		}
	}

	public function getChannel() {
		$channel          = $this->getElementsByTagName($this->_channel_tag)->item(0);
		$channel_elements = $channel->getElementsByTagName('*');
		for ($i = 0; $i < $channel_elements->length; $i++) {
			if ($channel_elements->item($i)->nodeName == $this->_item_tag) {
				break;
			}
			$this->_channel[$channel_elements->item($i)->nodeName] = $this->_normalizeString($channel_elements->item($i)->nodeValue);
			if ($channel_elements->item($i)->hasAttributes()) {
				foreach ($channel_elements->item($i)->attributes as $attribute) {
					$this->_channel[$channel_elements->item($i)->nodeName . '_' . $attribute->name] = $this->_normalizeString($attribute->value);
				}
			}
		}
		return $this->_channel;
	}

	public function getItems() {
		$elements = $this->getElementsByTagName($this->_item_tag);
		for ($i = 0; $i < $elements->length; $i++) {
			$item_elements = $elements->item($i)->getElementsByTagName('*');
			for ($j = 0; $j < $item_elements->length; $j++) {
				$this->_items[$i][$item_elements->item($j)->nodeName] = $this->_normalizeString($item_elements->item($j)->nodeValue);
				if ($item_elements->item($j)->hasAttributes()) {
					foreach ($item_elements->item($j)->attributes as $attribute) {
						$this->_items[$i][$item_elements->item($j)->nodeName . '_' . $attribute->name] = $this->_normalizeString($attribute->value);
					}
				}
			}
		}
		return $this->_items;
	}

	private function _normalizeString($string) {
		return htmlspecialchars($string, ENT_QUOTES, 'utf-8', false);
	}

	private function _sanitizeString($string) {
		return preg_replace('/[\x00-\x08\x0E-\x1F]/', '', $string);
	}

	private function _get($url) {
		$request = curl_init($url);
		$options = array(
			CURLOPT_HEADER         => true,
			CURLOPT_NOBODY         => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_USERAGENT      => 'RSSGet ' . self::VERSION,
			CURLOPT_SSL_VERIFYPEER => false,
		);
		curl_setopt_array($request, $options);
		$result = curl_exec($request);

		if ($result) {
			$info             = curl_getinfo($request);
			$response         = $this->_parseResponse($result);
			$response['info'] = $info;
		} else {
			$response = array(
				'number' => curl_errno($request),
				'error'  => curl_error($request),
			);
		}
		curl_close($request);
		return $response;
	}

	private function _parseResponse($response) {
		$response_parts = explode("\r\n\r\n", $response, 2);
		$response       = array();
		$cookie         = array();

		$response['header'] = explode("\r\n", $response_parts[0]);

		if (preg_match_all('/Set-Cookie: (.*?)=(.*?)(\n|;)/i', $response_parts[0], $matches)) {
			if (!empty($matches)) {
				foreach ($matches[1] as $key => $value) {
					$cookie[] = $value . '=' . $matches[2][$key] . ';';
				}
				$response['cookie'] = $cookie;
			}
		}
		$response['body'] = $response_parts[1];
		return $response;
	}
}