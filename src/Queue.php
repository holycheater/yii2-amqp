<?php
// vim: sw=4:ts=4:noet:sta:

namespace alexsalt\amqp;

use Yii;
use yii\base\Object;

/**
 * a class representing RMQ queue
 */
class Queue extends Object {
	public $name;

	public $passive = false;

	public $durable = false;

	public $exclusive = false;

	public $auto_delete = true;

	public $nowait = false;

	public $arguments = null;

	public $ticket = null;

	public function getConnection() {
		return Yii::$app->rmq;
	}

	/**
	 * @deprecated
	 */
	public function ensure() {
		$channel = $this->getConnection()->channel();
		$channel->queue_declare(
			$this->name,
			$this->passive,
			$this->durable,
			$this->exclusive,
			$this->nowait,
			$this->arguments,
			$this->ticket
		);
	}
}
