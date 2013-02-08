<?php

namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Acme\DemoBundle\Form\ContactType;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;

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
g.v(1)
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
START root = node(1)
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

		return array(
			'node' => $client->getNode($id),
		);
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
