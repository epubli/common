<?php
/**
 * Created by PhpStorm.
 * User: teamore
 * Date: 23.06.17
 * Time: 11:02
 */

namespace Epubli\Common\Tools;


use Doctrine\ORM\QueryBuilder;

class QueryTools
{
	/**
	 * checks whether a given sort parameter is valid for the respective query object
	 * (accepts instances of QueryBuilder and WP_Query as $query)
	 * @param string $sortParameter
	 * @param mixed $queryBuilder
	 * @return string
	 */
	public static function sanitizeOrder($sortParameter, &$query = null) {
		$sortParameter = trim($sortParameter);
		$sortDirection = 'ASC';
		if (substr($sortParameter,0,1) === '-') {
			$sortParameter = substr($sortParameter,1);
			$sortDirection = 'DESC';
		}
		if ($query instanceof QueryBuilder) {
			$query->resetDQLParts(['orderBy']);
			$tableAliases = $query->getAllAliases();
			/** @var string $dql */
			$dql = $query->getDQLPart('select');
			$select = '';
			foreach($dql as $q) {
				$select .= " ".$q;
			}
			preg_match_all('/[ ,^]*([a-z0-9\._ ]+)[ ]*/is',$select,$m);
			$matches = end($m);
			$allowed = [];
			foreach($matches as $fieldName) {
				$fieldName = explode(' ',$fieldName);
				$fieldName = end($fieldName);
				if ($fieldName) {
					$allowed[] = $fieldName;
				}
			}
			$match = array_search($sortParameter,$allowed);
			foreach($tableAliases as $a) {
				$match = $match ? $match : array_search($a . "." . $sortParameter, $allowed);
			}

			if ($match) {
				$query->addOrderBy($allowed[$match], $sortDirection);
			}
		} else if (is_array($query)) {
			/* we have a wordpress WP query */
			$wpAllowedSort = ['relevance'=>'score','title','date'];
			if (in_array($sortParameter,$wpAllowedSort)) {
				$key = array_search($sortParameter,$wpAllowedSort);
				if (is_string($key)) {
					$sortParameter = $key;
				} else if(false === $key) {
					$sortParameter = false;
				}
				$query['orderby'] = $sortParameter;
				$query['sort_order'] = $sortDirection;
			}
		}

		return $sortParameter ? $sortParameter : null;
	}
}