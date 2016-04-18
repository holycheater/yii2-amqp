<?php
// vim: sw=4:ts=4:noet:sta:

namespace alexsalt\amqp;

use Yii;

/**
 * a class representing RMQ queue
 */
class Queue extends \yii\base\Object {
	public $name;

	public $passive = false;

	public $durable = false;

	public $exclusive = false;

	public $auto_delete = true;

	public $nowait = false;

	public $arguments = null;

	public $ticket = null;

	public $component = 'rmq';

	public function getConnection() {
		return Yii::$app->get($this->component);
	}

	public function ensure() {
		$channel = $this->getConnection()->getChannel();
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
