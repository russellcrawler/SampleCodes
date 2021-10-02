<?php defined('BASEPATH') OR exit('No direct script access allowed');

use function GuzzleHttp\Psr7\parse_header;
use Ratchet\ConnectionInterface;

class ChatWorker extends MY_Controller
{
    /** @var User_model */
    private $user;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('ratchet_client');

        $this->load->helper('security');
        $this->load->model('user_token_model');
        $this->load->library('messenger');
    }

    /**
     * @return void
     */
    public function start()
    {
        if (!is_cli()) {
            show_404();
        }

        $this->ratchet_client->set_callback('auth', [$this, '_auth']);
        $this->ratchet_client->set_callback('command', [$this, '_command']);
        $this->ratchet_client->run();
    }

    /**
     * @param array $data
     * @param Ratchet\ConnectionInterface $client
     * @return bool|int
     */
    public function _auth(array $data, ConnectionInterface $client)
    {
        $cookie = $client->httpRequest->getHeader('Cookie');
        $ciCookieName = $this->config->item('sess_cookie_name');

        if (!isset($data['user']['userID']) || !is_numeric($data['user']['userID']) || !count($cookie)) {
            return false;
        }

        $cookie = parse_header($cookie)[0];
        $userID = (int)$data['user']['userID'];

        if (!count($cookie) || !isset($cookie[$ciCookieName], $cookie[User_token_model::TOKEN_COOKIE_NAME]) ||
            $this->user_model->get($userID) === null ||
            !User_token_model::validate($cookie[User_token_model::TOKEN_COOKIE_NAME], $userID)) {
            return false;
        }

        return $userID;
    }

    /**
     * @param array $data
     * @param ConnectionInterface $client
     * @param callable $callback
     */
    public function _command(array $data, ConnectionInterface $client, $callback)
    {
        $this->user = $this->user_model->get((int)$data['user']['userID']);

        $self = true;
        $send = true;
        $toClients = [];

        try {
            switch ($data['event']) {
                case Messenger::GET_CONVERSATIONS:
                case Messenger::GET_CONVERSATIONS_SCROLL:
                    $result = $this->messenger->getConversations($data);
                    break;
                case Messenger::GET_MESSAGES:
                case Messenger::GET_MESSAGES_SCROLL:
                    $result = $this->messenger->getMessages($data);
                    break;
                case Messenger::SEND_MESSAGE:
                case Messenger::RECEIVE_MESSAGE:
                    $self = false;
                    $result = $this->messenger->sendMessage($data);
                    $data['event'] = Messenger::RECEIVE_MESSAGE;

                    if (isset($result['status']) && $result['status'] === Messenger::STATUS_ERROR) {
                        $toClients = $result['senderID'];
                    } else {
                        $toClients = [$result['recipientID'], $result['senderID']];
                    }

                    unset($result['recipientID']);
                    break;
                case Messenger::MARK_ALL_READ:
                    $result = $this->messenger->markAllRead($data);
                    break;
                case Messenger::MARK_UNREAD:
                    $result = $this->messenger->markUnRead($data);
                    break;
                case Messenger::GET_CONTACTS:
                    $result = $this->messenger->getContacts($data);
                    break;
                case Messenger::DELETE_CONVERSATION:
                    $result = $this->messenger->deleteConversation($data);
                    break;
                default:
                    $send = false;
                    $result = [];
            }
        } catch (Exception $e) {
            log_message('error', 'Messenger error with event: ' . $data['event'] . ' for user: ' .
                (int)$data['user']['userID'] . '. Message: ' . $e->getMessage());

            $result = [
                'status' => Messenger::STATUS_ERROR,
                'message' => 'There is some error. Try to reload the page.',
            ];
        }

        if ($send) {
            if ($data['event'] !== Messenger::SEND_MESSAGE && $data['event'] !== Messenger::RECEIVE_MESSAGE) {
                $result['unreadCount'] = $this->user->getUnreadCount();
            }

            $callback(json_encode([
                'event' => $data['event'],
                'data' => $result,
            ]), $client, $self, $toClients);
        }
    }
}