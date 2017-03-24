<?php
namespace DIQA\Util\Data;

/**
 * Tree representation for use with fancytree js-lib
 * @author Kai
 *
 */
class TreeNode implements \JsonSerializable {

	private $key;
	private $title;
	private $children;
	private $folder;

	public function jsonSerialize() {
		$obj = new \stdClass();
		$obj->key = $this->key;
		$obj->title = $this->title;
		$obj->children = $this->children;
		$obj->folder = $this->folder;
		return $obj;
	}

	/**
	 * @return the $key
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @return the $title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return the $children
	 */
	public function getChildren() {
		return $this->children;
	}

	public function __construct($key = 'root', $title = 'root') {
		$this->key = $key;
		$this->title = $title;
		$this->children = [];
		$this->folder = true;
	}

	public function containsChildWithTitle($title) {
		return !is_null($this->getChildByTitle($title));
	}

	public function getChildByTitle($title) {
		foreach($this->children as $c) {
			if ($c->getTitle() === $title) {
				return $c;
			}
		}
		return NULL;
	}

	public function addChild($child) {
		$this->children[] = $child;
		return $child;
	}

	public function getTreeAsJSON() {
		return json_encode($this->getChildren());
	}
}