<?php

namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Acme\DemoBundle\Form\ContactType;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;

use Everyman, HireVoice;
use Acme\DemoBundle\Entity;

class DemoController extends Controller
{
	/**
	 * @Route("/", name="_demo")
	 * @Template()
	 */
	public function indexAction()
	{
		return array();
	}

	/**
	 * @Route("/gremlin", name="demo_gremlin")
	 * @Template()
	 */
	public function gremlinAction(Request $request)
	{
		$query = $request->query->get('q');

		if (empty($query)) {
			$query = <<<Q1
g.v(0)
Q1;
		}

		$manager = $this->get('neo4j.manager');
		$client = $manager->getClient();

		try {
			$clientQuery = new \Everyman\Neo4j\Gremlin\Query($client, $query, array());
			$result = $clientQuery->getResultSet();

			return array(
				'query' => $query,
				'result' => $this->renderResult($result),
			);
		} catch (\Exception $e) {
			$result = $e;

			return array(
				'query' => $query,
				'result' => "<pre>{$e->getMessage()}</pre>",
			);
		}
	}

	/**
	 * @Route("/cypher", name="demo_cypher")
	 * @Template()
	 */
	public function cypherAction(Request $request)
	{
		$query = $request->query->get('q');

		if (empty($query)) {
			$query = <<<Q1
START root = node(0)
RETURN root
Q1;
		}

		$manager = $this->get('neo4j.manager');
		$client = $manager->getClient();

		try {
			$clientQuery = new \Everyman\Neo4j\Cypher\Query($client, $query, array());
			$result = $clientQuery->getResultSet();

			return array(
				'query' => $query,
				'result' => $this->renderResult($result),
			);
		} catch (\Exception $e) {
			$result = $e;

			return array(
				'query' => $query,
				'result' => "<pre>{$e->getMessage()}</pre>",
			);
		}
	}

	/**
	 * @Route("/relation/{id}", name="demo_relation")
	 * @Template()
	 */
	public function relationAction($id)
	{
		$manager = $this->get('neo4j.manager');
		$client = $manager->getClient();
		$relation = $client->getRelationship($id);

		return array(
			'relation' => $relation,
			'start' => $this->renderResult($relation->getStartNode()),
			'end' => $this->renderResult($relation->getEndNode()),
		);
	}

	/**
	 * @Route("/node/{id}", name="demo_node")
	 * @Template()
	 */
	public function nodeAction($id)
	{
		$manager = $this->get('neo4j.manager');
		$client = $manager->getClient();
		$node = $client->getNode($id);

		$url = null;
		switch ($node->getProperty('class'))
		{
		case 'Acme\\DemoBundle\\Entity\\Flight':
			$url = $this->generateUrl('managed_flight', ['id' => $id]);
			break;
		case 'Acme\\DemoBundle\\Entity\\City':
			$url = $this->generateUrl('managed_city', ['id' => $id]);
			break;
		}

		return array(
			'node' => $node,
			'url' => $url,
		);
	}

	/**
	 * @Route("/basic/add", name="basic_add")
	 * @Template
	 */
	public function addBasicFlightAction(Request $request)
	{
		$getOrCreateCity = function ($client, $name) {
			$index = new Everyman\Neo4j\Index\NodeIndex($client, 'cities');
			$index->save();

			if (! $node = $index->findOne('name', $name)) {
				$node = $client->makeNode()
					->setProperty('name', $name)
					->save();

				$index->add($node, 'name', $name);
				$index->save();
			}

			return $node;
		};

		$form = $this->getFlightForm();

		if ($request->isMethod('POST')) {
			$form->bindRequest($request);

			if ($form->isValid()) {
				$data = $form->getData();

				$client = $this->get('neo4j.manager')->getClient();

				$from = $getOrCreateCity($client, $data['from']);
				$to = $getOrCreateCity($client, $data['to']);

				$relation = $client->makeRelationship()
					->setType('flight')
					->setStartNode($from)
					->setEndNode($to)
					->setProperty('cost', $data['cost'])
					->save();

				return $this->redirect($this->generateUrl('demo_relation', ['id' => $relation->getId()]));
			}
		}

		return array(
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/managed/add", name="managed_add")
	 * @Template
	 */
	public function addManagedFlightAction(Request $request)
	{
		$getOrCreateCity = function ($cities, $name) {
			if (! $entity = $cities->findOneByName($name)) {
				$entity = new Entity\City;
				$entity->setName($name);
			}

			return $entity;
		};

		$form = $this->getFlightForm();

		if ($request->isMethod('POST')) {
			$form->bindRequest($request);

			if ($form->isValid()) {
				$em = $this->get('neo4j.manager');

				$cities = $em->getRepository('Acme\\DemoBundle\\Entity\\City');

				$data = $form->getData();

				$from = $getOrCreateCity($cities, $data['from']);
				$to = $getOrCreateCity($cities, $data['to']);

				$flight = new Entity\Flight;
				$flight->setOrigin($from);
				$flight->setDestination($to);
				$flight->setCost($data['cost']);
				
				$em->persist($flight);
				$em->flush();

				return $this->redirect($this->generateUrl('managed_flight', ['id' => $flight->getId()]));
			}
		}

		return array(
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/managed/flight/{id}", name="managed_flight")
	 * @Template
	 */
	public function showFlightAction($id)
	{
		$em = $this->get('neo4j.manager');
		$flight = $em->find('Acme\\DemoBundle\\Entity\\Flight', $id);

		return array(
			'flight' => $flight,
		);
	}

	/**
	 * @Route("/managed/city/{id}", name="managed_city")
	 * @Template
	 */
	public function showCityAction($id)
	{
		$em = $this->get('neo4j.manager');
		$flight = $em->find('Acme\\DemoBundle\\Entity\\City', $id);

		return array(
			'city' => $flight,
		);
	}

	private function getFlightForm()
	{
		return $this->createFormBuilder()
			->add('from', 'text')
			->add('to', 'text')
			->add('cost', 'text')
			->getForm();
	}


	private function renderResult($result)
	{
		if ($result instanceof \Everyman\Neo4j\Query\ResultSet) {
			$out = '';

			foreach ($result as $row) {
				$out .= '<tr>';

				foreach ($row as $cell) {
					$out .= '<td style="padding: 0.2em;">' . $this->renderResult($cell) . '</td>';
				}

				$out .= '</tr>';
			}

			return "<table>$out</table>";
		} elseif ($result instanceof \Everyman\Neo4j\Query\Row) {
			$out = '';
			foreach ($result as $row) {
				$out .= $this->renderResult($row);
			}

			return $out;
		} elseif ($result instanceof \Everyman\Neo4j\Relationship) {
			$url = $this->generateUrl('demo_relation', array(
				'id' => $result->getId(),
			));

			return "<a href=\"$url\" class=\"symfony-button-grey relation-link\">Rel {$result->getId()}</a>";
		} elseif ($result instanceof \Everyman\Neo4j\Node) {
			$url = $this->generateUrl('demo_node', array(
				'id' => $result->getId(),
			));

			return "<a href=\"$url\" class=\"symfony-button-grey node-link\">Node {$result->getId()}</a>";
		} elseif ($result instanceof \Everyman\Neo4j\Path) {
			$out = '';
	
			$result->setContext(\Everyman\Neo4j\Path::ContextRelationship);
			$first = true;
			foreach ($result as $rel) {
				if ($first) {
					$first = false;
					$out .= $this->renderResult($rel->getStartNode());
				}

				$out .= $this->renderResult($rel);
				$out .= $this->renderResult($rel->getEndNode());
			}

			return $out;
		} elseif (is_scalar($result)) {
			return $result;
		} else {
			return get_class($result);
		}
	}
}
