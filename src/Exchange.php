<?php
// vim: sw=4:ts=4:noet:sta:

namespace alexsalt\amqp;

class Exchange extends \yii\base\Object {
	public $name;

	public $type;

	public $passive = false;

	public $durable = false;

	public $auto_delete = true;

	public $internal = false;

	public $nowait = false;

	public $arguments = null;

	public $ticket = null;

	public $component = 'rmq';

	public function getConnection() {
		return Yii::$app->get($this->component);
	}

	public function ensure() {
		$channel = $this->getConnection()->getChannel();
		$channel->exchange_declare(
			$this->name,
			$this->type,
			$this->passive,
			$this->durable,
			$this->auto_delete,
			$this->internal,
			$this->nowait,
			$this->arguments,
			$this->ticket
		);
	}
}
