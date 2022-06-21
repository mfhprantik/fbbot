<?php

namespace Prantik\FBbot;

use Prantik\FBbot\FBbotMessage;
use Cache;
use Opis\Closure\SerializableClosure;

class FBbot
{
    private $data;
    private $controls;
    private $controller;
    private $fallback;
    private $custom_actions;
    private $access_token;
    private $user;

    /**
     * Initialize the bot
     *
     * @return void
     */
    public function __construct($controller, $controls)
    {
        $this->controller = $controller;
        $this->controls = $controls;
        $this->access_token = $controller->organization_profile->fb_page_access_token;
        $this->data = request()->all();
        $this->message_direction = 'incoming';
        $this->store_function = null;
    }

    public function setFallback($fallback)
    {
        $this->fallback = $fallback;
    }

    public function setCustomActions($custom_actions)
    {
        $this->custom_actions = $custom_actions;
    }

    public function run()
    {
        try {
            $this->typesAndWaits();
            $this->recall();

            if (count($this->custom_actions) > 0) {
                foreach ($this->custom_actions as $action) {
                    call_user_func(array($this->controller, $action));
                }
            }

            $this->listen();
        } catch (\Exception $e) {
            \Log::info($e);
            self::endConversation();
        }
    }

    public static function verify()
    {
        $data = request()->all();
        if (isset($data['hub_mode'])) {
            $VERIFY_TOKEN = env('FACEBOOK_VERIFICATION');
            $mode = $data['hub_mode'];
            $token = $data['hub_verify_token'];
            $challenge = $data['hub_challenge'];

            if ($mode && $token) {
                if ($mode === 'subscribe' && $token === $VERIFY_TOKEN) {
                    echo $challenge;
                    self::endConversation();
                } else abort(403);
            } else abort(403);
        }
    }

    private function listen()
    {
        if (!$this->isReadReceipt() && !$this->isDeliveryReceipt() && !$this->isEcho()) {
            $fallback = true;
            foreach ($this->controls as $control) {
                if ($control[2]) {
                    if ($matches = $this->hears($control[0])) {
                        $fallback = false;
                        call_user_func(array($this->controller, $control[1]), $matches);
                    }
                } else {
                    if ($this->hears($control[0])) {
                        $fallback = false;
                        call_user_func(array($this->controller, $control[1]));
                    }
                }

                if (!$fallback) break;
            }

            if ($fallback && $this->fallback) {
                call_user_func(array($this->controller, $this->fallback));
            }
        }
    }

    private function hears($pattern)
    {
        $matches = [];
        if (is_array($pattern)) $pattern = '/^(' . implode('|', $pattern) . ')$/si';
        else $pattern = '/^(' . $pattern . ')$/si';

        if ($this->getPayload()) {
            preg_match($pattern, $this->getPayload(), $matches);
        } elseif ($this->getMessage()) {
            preg_match($pattern, $this->getMessage(), $matches);
        }

        return count($matches) ? $matches : false;
    }

    public function getData()
    {
        return $this->data;
    }

    public static function createMessage($message)
    {
        $payload = $message->getData();
        if ($message->isTemplate()) {
            if (isset($payload['template_type'])) {
                return array(
                    'attachment' => array(
                        'type' => 'template',
                        'payload' => $payload
                    )
                );
            }
        }

        return $payload;
    }

    public function reply(FBbotMessage $message, $notify = false)
    {
        $recipient_field = 'id';
        if ($notify) $recipient_field = 'one_time_notif_token';

        $data = array(
            'recipient' => array(
                $recipient_field => $this->getUserId()
            ),
            'message' => self::createMessage($message)
        );

        $url = config('fbbot.graph_api_endpoint') . 'me/messages?access_token=' . $this->access_token;
        $response = self::call($url, 'POST', $data);
        return json_decode($response, true);
    }

    public static function say($access_token, $recipient_id, FBbotMessage $message, $notify = false, $recipient_field = 'id', $tag = null)
    {
        if ($notify) $recipient_field = 'one_time_notif_token';

        $data = array(
            'recipient' => array(
                $recipient_field => $recipient_id
            ),
            'message' => self::createMessage($message)
        );

        if (!is_null($tag)) {
            $data['messaging_type'] = 'MESSAGE_TAG';
            $data['tag'] = $tag;
        }

        $url = config('fbbot.graph_api_endpoint') . 'me/messages?access_token=' . $access_token;
        $response = self::call($url, 'POST', $data);
        return json_decode($response, true);
    }

    public function ask(FBbotMessage $message, $callback)
    {
        $data = array(
            'recipient' => array(
                'id' => $this->getUserId()
            ),
            'message' => self::createMessage($message)
        );

        self::remember($this->getUserId(), $callback);
        $url = config('fbbot.graph_api_endpoint') . 'me/messages?access_token=' . $this->access_token;
        $response = self::call($url, 'POST', $data);
        return json_decode($response, true);
    }

    public static function remember($user_id, $callback)
    {
        Cache::put(sha1($user_id), serialize(new SerializableClosure($callback)), 3600);
    }

    public function recall()
    {
        if (!$this->isReadReceipt() && !$this->isDeliveryReceipt() && !$this->isEcho()) {
            $key = sha1($this->getUserId());
            if (Cache::has($key)) {
                $callback = unserialize(Cache::pull($key))->getClosure();
                call_user_func($callback, $this);
                exit;
            }
        }
    }

    public function typesAndWaits($time = 0)
    {
        $data = array(
            'recipient' => array(
                'id' => $this->getUserId()
            ),
            'sender_action' => 'typing_on'
        );

        $url = config('fbbot.graph_api_endpoint') . 'me/messages?access_token=' . $this->access_token;
        $response = self::call($url, 'POST', $data);
        if ($time > 0) sleep($time);
        return json_decode($response, true);
    }

    public static function endConversation()
    {
        http_response_code(200);
        exit;
    }

    public function getUser()
    {
        if (isset($this->user)) return $this->user;

        $id = $this->getUserId($this->data);
        $url = config('fbbot.graph_api_endpoint') . $id . '?fields=name,first_name,last_name,profile_pic&access_token=' . $this->access_token;
        $response = self::call($url, 'GET');
        $this->user = json_decode($response, true);
        return $this->user;
    }

    public function getUserId()
    {
        if (isset($this->data['entry'][0]['messaging'][0]['sender']['id']) && $this->data['entry'][0]['messaging'][0]['sender']['id'] != $this->data['entry'][0]['id']) {
            return $this->data['entry'][0]['messaging'][0]['sender']['id'];
        } elseif (isset($this->data['entry'][0]['messaging'][0]['recipient']['id'])) {
            $this->message_direction = 'outgoing';
            return $this->data['entry'][0]['messaging'][0]['recipient']['id'];
        } elseif (isset($this->data['entry'][0]['changes'][0]['value']['from']['id'])) {
            return $this->data['entry'][0]['changes'][0]['value']['from']['id'];
        }

        return null;
    }

    public function getMessage()
    {
        $message = '';
        if (isset($this->data['entry'][0]['messaging'][0]['message']['text'])) $message = trim($this->data['entry'][0]['messaging'][0]['message']['text']);
        elseif (isset($this->data['entry'][0]['messaging'][0]['postback']['title'])) $message = trim($this->data['entry'][0]['messaging'][0]['postback']['title']);
        elseif (isset($this->data['entry'][0]['changes'][0]['value']['message'])) $message = trim($this->data['entry'][0]['changes'][0]['value']['message']);
        return strlen($message) ? $message : false;
    }

    public function getMessageId()
    {
        if (isset($this->data['entry'][0]['messaging'][0]['message']['mid'])) return $this->data['entry'][0]['messaging'][0]['message']['mid'];
        return null;
    }

    public function getPayload()
    {
        $payload = '';
        if (isset($this->data['entry'][0]['messaging'][0]['message']['payload'])) $payload = $this->data['entry'][0]['messaging'][0]['message']['payload'];
        elseif (isset($this->data['entry'][0]['messaging'][0]['postback']['payload'])) $payload = trim($this->data['entry'][0]['messaging'][0]['postback']['payload']);
        elseif (isset($this->data['entry'][0]['messaging'][0]['optin']['payload'])) $payload = trim($this->data['entry'][0]['messaging'][0]['optin']['payload']);
        elseif (isset($this->data['entry'][0]['messaging'][0]['message']['quick_reply']['payload'])) $payload = trim($this->data['entry'][0]['messaging'][0]['message']['quick_reply']['payload']);
        return strlen($payload) ? $payload : false;
    }

    public function getNotifyToken()
    {
        if (isset($this->data['entry'][0]['messaging'][0]['optin']['one_time_notif_token'])) return $this->data['entry'][0]['messaging'][0]['optin']['one_time_notif_token'];
    }

    public function getAttachments()
    {
        if (isset($this->data['entry'][0]['messaging'][0]['message']['attachments']) && count($this->data['entry'][0]['messaging'][0]['message']['attachments']) > 0 && $this->data['entry'][0]['messaging'][0]['message']['attachments'][0]['type'] !== 'template') {
            return $this->data['entry'][0]['messaging'][0]['message']['attachments'];
        }
        return [];
    }

    public function getMessageTimestamp()
    {
        if (isset($this->data['entry'][0]['messaging'][0]['timestamp'])) return $this->data['entry'][0]['messaging'][0]['timestamp'];
        return time();
    }

    public function getController()
    {
        return $this->controller;
    }

    public function isEcho()
    {
        return isset($this->data['entry'][0]['messaging'][0]['message']['is_echo']);
    }

    public function isReadReceipt()
    {
        return isset($this->data['entry'][0]['messaging'][0]['read']);
    }

    public function isDeliveryReceipt()
    {
        return isset($this->data['entry'][0]['messaging'][0]['delivery']);
    }

    public static function call($url, $method, $data = [])
    {
        try {
            if ($method == 'GET') {
                return file_get_contents($url);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        } catch (\Exception $e) {
            \Log::info('Exception occurred in fbbot [' . $e->getMessage() . ']');
            return null;
        }
    }
}
