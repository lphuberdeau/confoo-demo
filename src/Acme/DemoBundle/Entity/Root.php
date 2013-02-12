<?php

namespace Acme\DemoBundle\Entity;
use HireVoice\Neo4j\Annotation as OGM;

/**
 * @OGM\Entity
 */
class Root
{
	/**
	 * @OGM\Auto
	 */
	private $id;

	/**
	 * @OGM\Property
	 */
	private $database;

	function getId()
	{
		return $this->id;
	}

	function setId($id)
	{
		$this->id = $id;
	}

	function getTitle()
	{
		return $this->database;
	}
}
