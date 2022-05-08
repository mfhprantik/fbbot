<?php

namespace Prantik\FBbot;

class FBbotMessageToHTML
{
	private FBbotMessage $message;
	/**
	 * Create an instance
	 *
	 * @return void
	 */
	public function __construct(FBbotMessage $message)
	{
		$this->message = $message;
	}

	public function getView()
	{
		$data = $this->message->getData();

		if ($this->message->isTemplate()) {
			switch ($data['template_type']) {
				case 'generic':
					return view('fbbot.generic', compact('data'))->render();
					break;
				case 'button':
					return view('fbbot.button', compact('data'))->render();
					break;
				case 'one_time_notif_req':
					return view('fbbot.notif', compact('data'))->render();
					break;
			}
		}

		return view('fbbot.simple', compact('data'))->render();
	}
}
