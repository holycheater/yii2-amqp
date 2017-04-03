<?php
// vim: sw=4:ts=4:noet:sta:

namespace alexsalt\amqp;
use PhpAmqpLib\Message\AMQPMessage;

use Yii;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use InvalidArgumentException;

class Connection extends \yii\base\Component {
	const DELIVERY_NON_PERSISTENT = 1;
	const DELIVERY_PERSISTENT = 2;

	public $host = 'localhost';

	public $port = 5672;

	public $user;

	public $password;

	public $vhost = '/';

	private $_conn;

	private $_definedQueues = [ ];

	public function init() {
		parent::init();
		$this->connect();
	}

	/**
	 * get amqp channel
	 * @param integer $channelId channel id to use, defaults to 0, put null to autogenerate channel id
	 * @return \PhpAmqpLib\Channel\AMQPChannel
	 */
	public function channel($channelId = 1) {
		return $this->_conn->channel($channelId);
	}

	public function connect() {
		$this->_conn = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password, $this->vhost);
	}

	/**
	 * basic send message to queue
	 * @param MessageInterface $msg
	 * @param string $queue queue name to publish
	 */
	public function sendToQueue(MessageInterface $msg, $queue) {
		$amqpMessage = new AMQPMessage($msg->getPayload(), [
			'delivery_mode' => self::DELIVERY_PERSISTENT
		]);
		$channel = $this->channel();
		$channel->basic_publish($amqpMessage, '', $queue);
	}

	public function sendToExchange(MessageInterface $msg, $exchange, $routing_key = '') {
		$amqpMessage = new AMQPMessage($msg->getPayload(), [
			'delivery_mode' => self::DELIVERY_PERSISTENT
		]);
		$channel = $this->channel();
		$channel->basic_publish($amqpMessage, $exchange, $routing_key);
	}

	/**
	 * Ensures queue exists.
	 * Creates a new queue with defaults or from predefined config
	 * if it does not exist
	 * @param string|Queue $queue queue name or object
	 * @throws \InvalidArgumentException
	 */
	public function ensureQueue($queue) {

		if (is_string($queue)) {
			$q = new Queue([ 'name' => $queue ]);
		} else if ($queue instanceof Queue) {
			$q = $queue;
		} else if (is_array($queue)) {
			$q = new Queue($queue);
		} else {
			throw new InvalidArgumentException('invalid queue pass');
		}

		if (isset($this->_definedQueues[$q->name])) {
			return;
		}

		$channel = $this->channel();
		$channel->queue_declare(
			$q->name,
			$q->passive,
			$q->durable,
			$q->exclusive,
			$q->auto_delete,
			$q->nowait,
			$q->arguments,
			$q->ticket
		);
		$this->_definedQueues[$q->name] = true;
	}
}
