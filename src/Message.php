<?php
// vim: sw=4:ts=4:noet:sta:

namespace alexsalt\amqp;

use Yii;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Base message class
 */
class Message extends \yii\base\Object {
	const DELIVERY_NON_PERSISTENT = 1;
	const DELIVERY_PERSISTENT = 2;

	/**
	 * @var string
	 */
	public $data;

	/**
	 * @var integer
	 */
	public $deliveryMode = self::DELIVERY_PERSISTENT;

	public function create() {
		return new AMQPMessage($this->data, [ 'delivery_mode' => $this->deliveryMode ]);
	}

	public function get() {
		return $this->create();
	}
}
