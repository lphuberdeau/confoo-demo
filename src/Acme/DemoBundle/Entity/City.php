<?php

namespace Acme\DemoBundle\Entity;
use HireVoice\Neo4j\Annotation as OGM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @OGM\Entity
 */
class City
{
	/**
	 * @OGM\Auto
	 */
	private $id;

	/**
	 * @OGM\Property
	 * @OGM\Index
	 */
	private $name;

	/**
	 * @OGM\ManyToMany(readOnly=true, relation="origin")
	 */
	private $departures;

	/**
	 * @OGM\ManyToMany(readOnly=true, relation="destination")
	 */
	private $arrivals;

	function __construct()
	{
		$this->departures = new ArrayCollection;
		$this->arrivals = new ArrayCollection;
	}

	function getId()
	{
		return $this->id;
	}

	function setId($id)
	{
		$this->id = $id;
	}

	function getName()
	{
		return $this->name;
	}

	function setName($name)
	{
		$this->name = $name;
	}

	function getDepartures()
	{
		return $this->departures;
	}

	function getArrivals()
	{
		return $this->arrivals;
	}
}

