<?php
// vim: sw=4:ts=4:noet:sta:

namespace alexsalt\amqp;

use Yii;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Yii2 AMQP connection
 */
class Connection extends \yii\base\Component {

	/**
	 * @var string
	 */
	public $host = 'localhost';

	/**
	 * @var integer
	 */
	public $port = 5672;

	/**
	 * @var string
	 */
	public $user;

	/**
	 * @var string
	 */
	public $password;

	/**
	 * @var string
	 */
	public $vhost = '/';

	/**
	 * @var array queues declaration
	 * format: name => object properties for Queue object
	 */
	public $queues = [ ];

	/**
	 * @var array exchange declaration
	 * format: name => object properties for Exchange object
	 */
	public $exchanges = [ ];

	/**
	 * @var array queue to exchange bindings
	 * format: [ queue, exchange, routing_key ]
	 */
	public $queueBindings = [ ];

	/**
	 * @var array exchange bindings
	 * format: [ dst, src, routing_key ]
	 */
	public $exchangeBindings = [ ];

	private $_conn;

	private $_queues = [ ];

	private $_exchanges = [ ];

	private $_qbindings = [ ];

	private $_ebindings = [ ];

	private $_channels = [ ];

	public function init() {
		parent::init();
		$this->connect();
	}

	/**
	 * @param mixed $channelId
	 * @return PhpAmqpLib\Channel\AMQPChannel
	 */
	public function getChannel($channelId = null) {
		if (isset($this->_channels[$channelId])) {
			return $this->_channels[$channelId];
		} else if ($channelId === null) {
			if (isset($this->_channels['default'])) {
				return $this->_channels['default'];
			} else {
				return $this->_channels['default'] = $this->_conn->channel();
			}
		} else if (is_numeric($channelId)) {
			return $this->_channels[$channelId] = $this->_conn->channel($channelId);
		} else {
			return $this->_channels[$channelId] = $this->_conn->channel();
		}
	}

	public function connect() {
		$this->_conn = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password, $this->vhost);
	}

	/**
	 * @param string|Message $message
	 * @param string $queueName
	 */
	public function sendToQueue($message, $queueName) {
		$this->ensureQueue($queueName);

		if (is_string($message)) {
			$message = new Message([ 'data' => $message ]);
		}
		$this->channel->basic_publish($message->get(), '', $queueName);
	}

	/**
	 * @param string|Message $message
	 * @param string $exchangeName
	 * @param string $routingKey
	 */
	public function sendToExchange($message, $exchangeName, $routingKey) {
		$this->ensureExchange($exchangeName);
		$this->ensureBindings($exchangeName);

		if (is_string($message)) {
			$message = new Message([ 'data' => $message ]);
		}
		$this->channel->basic_publish($message->get(), $exchangeName, $routingKey);
	}

	public function ensureQueue($name) {
		if (isset($this->_queues[$name])) {
			return true;
		}
		if (isset($this->queues[$name])) {
			$params = array_merge($this->queues[$name], [ 'name' => $name ]);
			$queue = new Queue($params);
			$queue->ensure();
			$this->_queues[$name] = true;
			return true;
		} else {
			throw new \Exception("Queue '{$name}' not defined");
		}
	}

	public function ensureExchange($name) {
		if (isset($this->_exchanges[$name])) {
			return true;
		} else {
			if (isset($this->exchanges[$name])) {
				$params = array_merge($this->exchanges[$name], [ 'name' => $name ]);
				$exchange = new Exchange($params);
				$exchange->ensure();
				return true;
			} else {
				throw new \Exception("Exchange '{$name}' not defined");
			}
		}
	}

	public function ensureBindings($exchangeName) {
		foreach ($this->queueBindings as $binding) {
			if ($binding[1] == $exchangeName) {
				$key = implode('-', $binding);
				if (!isset($this->_qbindings[$key])) {
					list($queue, $exchange) = $binding;
					$this->ensureQueue($queue);
					$routing_key = isset($binding[2]) ? $binding[2] : '';
					$this->channel->queue_bind($queue, $exchange, $routing_key);
					$this->_qbindings[$key] = true;
				}
			}
		}
		foreach ($this->exchangeBindings as $binding) {
			if ($binding[0] == $exchangeName || $binding[1] == $exchangeName) {
				$key = implode('-', $binding);
				if (!isset($this->_ebindings[$key])) {
					list($dst, $src, $routing_key) = $binding;
					$this->ensureExchange($dst);
					$this->ensureExchange($src);

					$this->channel->exchange_bind($dst, $src, $routing_key);
					$this->_ebindings[$key] = true;
				}
			}
		}
	}
}
