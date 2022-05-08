<?php

namespace Prantik\FBbot;

class FBbotMessageElement
{
	private array $data;
	/**
	 * Create an instance
	 *
	 * @return void
	 */
	public function __construct($title = '')
	{
		$this->data = array(
			'title' => $title
		);
	}

	public function setTitle($title)
	{
		$this->data['title'] = $title;
		return $this;
	}

	public function setSubtitle($subtitle)
	{
		$this->data['subtitle'] = $subtitle;
		return $this;
	}

	public function setType($type)
	{
		$this->data['type'] = $type;
		return $this;
	}

	public function setUrl($url)
	{
		$this->data['url'] = $url;
		return $this;
	}

	public function setPayload($payload)
	{
		$this->data['payload'] = $payload;
		return $this;
	}

	public function setImage($image)
	{
		$this->data['image_url'] = $image;
		return $this;
	}

	public function setWebviewHeightRatio($ratio)
	{
		$this->data['webview_height_ratio'] = $ratio;
		return $this;
	}

	public function enableMessengerExtensions()
	{
		$this->data['messenger_extensions'] = "true";
		return $this;
	}

	public function setDefaultAction($url, $ratio, $extension = false)
	{
		$this->data['default_action'] = array(
			'type' => 'web_url',
			'url' => $url,
			'messenger_extensions' => $extension,
			'webview_height_ratio' => $ratio
		);

		return $this;
	}

	public function hideShareButton()
	{
		$this->data['webview_share_button'] = 'hide';
		return $this;
	}

	public function setContentType($type)
	{
		$this->data['content_type'] = $type;
		return $this;
	}

	public function addButton($type, $title, $payload = null, $url = null, $ratio = 'full', $extension = false)
	{
		if (!isset($this->data['buttons'])) {
			$this->data['buttons'] = [];
		}

		if ($payload) {
			$this->data['buttons'][] = array(
				'type' => $type,
				'title' => $title,
				'payload' => $payload
			);
		} elseif ($url) {
			$button = array(
				'type' => $type,
				'title' => $title,
				'url' => $url,
			);

			if ($ratio) $button['webview_height_ratio'] = $ratio;
			if ($extension) $button['messenger_extensions'] = $extension;
			$this->data['buttons'][] = $button;
		}

		return $this;
	}

	public function getData()
	{
		return $this->data;
	}
}
