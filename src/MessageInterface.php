<?php
// vim: sw=4:ts=4:noet:sta:

namespace alexsalt\amqp;

interface MessageInterface {

	/**
	 * get payload string
	 * @return string
	 */
	public function getPayload();
}
