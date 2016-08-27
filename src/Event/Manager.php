<?php
namespace ElfStack\Event;

use Exception;

/**
 * 事件控制系统
 *
 * 这是一个事件系统，能够方便系统间的基于事件的信息交流和数据传递
 * 此类的实例用于管理事件邦定和触发
 *
 * @package ElfStack
 * @subpackage Event
 * @category Event
 * @copyright ElfStack Dev Team 2016, all rights reserved.
 * @author ElfStack Dev Team
 */
trait Manager
{
	/**
	 * 是否执行严格模式
	 *
	 * 如果为真,则绑定事件回调时以及触发事件时事件必须已经注册,否则抛出异常
	 * 如果为假则不执行这样的检查
	 *
	 * @var bool
	 */
	public $strictEvent = true;

	/**
	 * 存储事件系统相关资源的属性
	 *
	 * 保证每个以事件名为键的项目为一个数组，且数组包含 'file', 'class', 'method' 键，'method' 键必不可少，其他可选
	 *
	 * @var array
	 */
	public $_event__;

	/**
	 * 注册事件
	 *
	 * @param  string|array $event 待注册的事件
	 * @return void
	 */
	final public function registerEvent($event)
	{
		if (is_array($event)) {
			foreach ($event as $value) {
				$this->registerEvent($value);
			}
			return;
		}

		$this->_event__['_event__registered'][] = $event;
	}

	/**
	 * 绑定事件回调动作
	 *
	 * @param string|array $event 待绑定的事件名称或包含['事件名' => 动作]的关联数组
	 * @param callable|string|array 事件触发时执行的动作
	 * @return $this
	 */
	final public function on($event, $callback = null)
	{
		if (is_array($event) and $callback === null) {
			foreach ($event as $key => $value) {
				$this->on($key, $value);
			}
			return $this;
		}

		if (!is_string($event)) {
			throw new Exception('Invalid argument(s)!');
		}

		if ($this->strictEvent and !in_array($event, $this->_event__['_event__registered'])) {
			throw new Exception("Event `$event` not registered!");
		}

		// 如果callback可直接调用则直接存入数组
		if (is_callable($callback)) {
			$this->_event__[$event][]['method'] = $callback;
			return $this;
		}

		// 如果callback是数组且存在method键则直接存入
		if (is_array($callback) and isset($callback['method'])) {
			$this->_event__[$event][] = $callback;
			return $this;
		}

		// 如果callback是字符串则按照 ((file#)class@)method 的格式分析字符串
		if (is_string($callback)) {
			preg_match('/^(([^\#])\#){0,1}((\w+)@){0,1}(\w+)$/', $callback, $arr);
			$arr = ['file' => $arr[2], 'class' => $arr[4], 'method' => $arr[5]];
			if (empty($arr['method'])) {
				throw new Exception('Invalid arguments(s) [empty callback provided]!');
			}
			$this->_event__[$event][] = $arr;
			return $this;
		}

		throw new Exception('Cannot analysis action provided.');
	}

	/**
	 * 触发事件
	 *
	 * @param string $event 要触发的事件名称
	 * @param array  &$args  可选的传递给动作函数的数组参数
	 * @return $this
	 */
	final public function trigger($event, array &$args = []) {
		if ($this->strictEvent and !in_array($event, $this->_event__['_event__registered'])) {
			throw new Exception("Event `$event` not registered!");
		}

		if (!empty($this->_event__[$event])) {
			foreach ($this->_event__[$event] as $action) {
				$this->_event__callAction($action, $args);
			}
		}
		return $this;
	}

	/**
	 * 分析动作并且调用
	 *
	 * @param  array $action 待分析的动作
	 * @param  array &$args   可选的传递给动作函数的参数
	 * @return mixed 动作函数的返回值
	 */
	final private function _event__callAction(array $action, array &$args = []) {
		if (!empty($action['file'])) {
			require_once($action['file']);
		}
		if (!empty($action['class'])) {
			$class = $action['class'];
			if (!class_exists($class, false)) {
				throw new Exception('The specific class `'.$class.'` does not exists in scope!');
			}
			$method = [new $class, $action['method']];
		} else {
			$method = $action['method'];
		}

		if (!is_callable($method)) {
			throw new Exception('Method not callable! Method provided: ' . print_r($method, true));
		}

		return is_array($method) ? $method[0]->{$method[1]}($args) : $method($args);
	}
}
