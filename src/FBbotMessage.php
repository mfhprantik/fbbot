<?php

namespace Prantik\FBbot;

class FBbotMessage
{
	private array $data;
	private string $element;
	private bool $template = false;
	/**
	 * Create an instance
	 *
	 * @return void
	 */
	public function __construct($text = '')
	{
		$this->data = array(
			'text' => $text
		);
	}

	public function useButtonTemplate()
	{
		$this->element = 'buttons';
		$this->data['template_type'] = 'button';
		$this->data[$this->element] = [];
		$this->template = true;
		return $this;
	}

	public function useGenericTemplate()
	{
		$this->element = 'elements';
		$this->data['template_type'] = 'generic';
		$this->data[$this->element] = [];
		$this->template = true;
		return $this;
	}

	public function useQuickReplyTemplate()
	{
		$this->data['template_type'] = 'quick_reply';
		$this->element = 'quick_replies';
		$this->data[$this->element] = [];
		$this->template = true;
		return $this;
	}

	public function useOneTimeNotificationTemplate($payload)
	{
		$this->data['template_type'] = 'one_time_notif_req';
		$this->template = true;
		$this->data['payload'] = $payload;
		return $this;
	}

	public function useAttachmentTemplate($type, $url, $isReusable = false)
	{
		$this->data = array(
			'attachment' => array(
				'type' => $type,
				'payload' => array(
					'is_reusable' => $isReusable,
					'url' => $url
				)
			)
		);
	}

	public function useCustomerFeedbackFormTemplate($title, $subtitle, $button_title, $business_privacy_url, $expires_in_days = 1)
	{
		$this->template = true;
		$this->data = array(
			'template_type' => 'customer_feedback',
			'title' => $title,
			'subtitle' => $subtitle,
			'button_title' => $button_title,
			'business_privacy' => array(
				'url' => $business_privacy_url
			),
			'expires_in_days' => $expires_in_days,
		);
		$this->element = 'feedback_screens';
		$this->data[$this->element] = array();
	}

	public function setText($text)
	{
		$this->data['text'] = $text;
	}

	public function addElement($title = ''): FBbotMessageElement
	{
		if (count($this->data[$this->element]) > config('fbbot.max_elements')) throw new \Exception("Maximum " . config('fbbot.max_elements') . " elements per message.");

		$element = new FBbotMessageElement($title);
		$this->data[$this->element][] = $element;
		return $element;
	}

	public function isTemplate(): bool
	{
		return $this->template;
	}

	public function getData(): array
	{
		if ($this->isTemplate()) {
			if ($this->data['template_type'] === 'generic') unset($this->data['text']);
			elseif ($this->data['template_type'] === 'one_time_notif_req') {
				$this->data['title'] = $this->data['text'];
				unset($this->data['text']);
			} elseif ($this->data['template_type'] === 'quick_reply') {
				unset($this->data['template_type']);
			} elseif ($this->data['template_type'] === 'customer_feedback') {
				foreach ($this->data[$this->element] as $key => $element) {
					unset($element->getData()['title']);
				}
			}

			if (isset($this->element)) {
				unset($this->data['text']);
				foreach ($this->data[$this->element] as $key => $element) {
					$this->data[$this->element][$key] = $element->getData();
				}
			}
		}

		return $this->data;
	}
}
