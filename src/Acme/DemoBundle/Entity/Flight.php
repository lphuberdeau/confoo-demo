<?php

namespace Acme\DemoBundle\Entity;
use HireVoice\Neo4j\Annotation as OGM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @OGM\Entity
 */
class Flight
{
	/**
	 * @OGM\Auto
	 */
	private $id;

	/**
	 * @OGM\Property
	 */
	private $cost;

	/**
	 * @OGM\ManyToOne
	 */
	private $origin;

	/**
	 * @OGM\ManyToOne
	 */
	private $destination;

	function getId()
	{
		return $this->id;
	}

	function setId($id)
	{
		$this->id = $id;
	}

	function getCost()
	{
		return $this->cost;
	}

	function setCost($cost)
	{
		$this->cost = $cost;
	}

	function getDestination()
	{
		return $this->destination;
	}

	function setDestination(City $city)
	{
		$this->destination = $city;
	}

	function getOrigin()
	{
		return $this->origin;
	}

	function setOrigin(City $city)
	{
		$this->origin = $city;
	}
}

