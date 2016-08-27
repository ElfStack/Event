<?php
namespace ElfStack\Event;

/**
 * Simple Container of message
 */
class Event
{
	public function __construct($result)
	{
		$this->result = $result;
	}
}
