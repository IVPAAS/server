<?php
/**
 * @package plugins.elasticSearch
 * @subpackage model
 */
class kESearchQueryManager
{
	const BODY_KEY = 'body';
	const BOOL_KEY = 'bool';
	const SHOULD_KEY = 'should';
	const FILTER_KEY = 'filter';
	const MULTI_MATCH_KEY = 'multi_match';
	const QUERY_KEY = 'query';
	const FIELDS_KEY = 'fields';
	const TYPE_KEY = 'type';
	const MOST_FIELDS = 'most_fields';
	const MATCH_KEY = 'match';
	const MINIMUM_SHOULD_MATCH_KEY = 'minimum_should_match';
	const PREFIX_KEY = 'prefix';
	const TERM_KEY = 'term';
	const RANGE_KEY = 'range';
	const GT_KEY = 'gt';
	const GTE_KEY = 'gte';
	const LT_KEY = 'lt';
	const LTE_KEY = 'lte';
	const EXISTS_KEY = 'exists';
	const FIELD_KEY = 'field';
	const NGRAMS_FIELD_SUFFIX = 'ngrams';
	const RAW_FIELD_SUFFIX = 'raw';

	const DEFAULT_TRIGRAM_PERCENTAGE = 80;

	public static function getMultiMatchQuery($searchItem, $fieldName, $shouldAddLanguageFields = false)
	{
		$multiMatch = array();
		$multiMatch[self::BOOL_KEY][self::SHOULD_KEY][0][self::MULTI_MATCH_KEY][self::QUERY_KEY] = $searchItem->getSearchTerm();
		$multiMatch[self::BOOL_KEY][self::SHOULD_KEY][0][self::MULTI_MATCH_KEY][self::FIELDS_KEY] = array(
			$fieldName.'.'.self::RAW_FIELD_SUFFIX.'^3',
			$fieldName.'^2',
		);
		$multiMatch[self::BOOL_KEY][self::SHOULD_KEY][0][self::MULTI_MATCH_KEY][self::TYPE_KEY] = self::MOST_FIELDS;

		if($shouldAddLanguageFields)
			$multiMatch[self::BOOL_KEY][self::SHOULD_KEY][0][self::MULTI_MATCH_KEY][self::FIELDS_KEY][] = $fieldName.'_*^2';

		$trigramFieldName = $fieldName.'.'.self::NGRAMS_FIELD_SUFFIX;
		$multiMatch[self::BOOL_KEY][self::SHOULD_KEY][1][self::MATCH_KEY][$trigramFieldName][self::QUERY_KEY] = $searchItem->getSearchTerm();
		$trigramPercentage = kConf::get('ngramPercentage', 'elastic', self::DEFAULT_TRIGRAM_PERCENTAGE);
		$multiMatch[self::BOOL_KEY][self::SHOULD_KEY][1][self::MATCH_KEY][$trigramFieldName][self::MINIMUM_SHOULD_MATCH_KEY] = "$trigramPercentage%";
		$multiMatch[self::BOOL_KEY][self::MINIMUM_SHOULD_MATCH_KEY] = 1;

		return $multiMatch;
	}

	public static function getExactMatchQuery($searchItem, $fieldName, $allowedSearchTypes)
	{
		$exactMatch = array();
		$queryType = self::TERM_KEY;
		$fieldSuffix = '';

		if (in_array(ESearchItemType::PARTIAL, $allowedSearchTypes[$fieldName]))
			$fieldSuffix = '.'.self::RAW_FIELD_SUFFIX;

		$searchTerm = elasticSearchUtils::formatSearchTerm($searchItem->getSearchTerm());
		$exactMatch[$queryType] = array( $fieldName . $fieldSuffix => $searchTerm);
		return $exactMatch;
	}

	public static function getPrefixQuery($searchItem, $fieldName, $allowedSearchTypes)
	{
		$prefixQuery = array();
		$queryType = self::PREFIX_KEY;
		$fieldSuffix = '';

		if(in_array(ESearchItemType::PARTIAL, $allowedSearchTypes[$fieldName]))
			$fieldSuffix = '.'.self::RAW_FIELD_SUFFIX;

		$searchTerm = elasticSearchUtils::formatSearchTerm($searchItem->getSearchTerm());
		$prefixQuery[$queryType] = array( $fieldName . $fieldSuffix => $searchTerm);

		return $prefixQuery;
	}

	public static function getRangeQuery($searchItem, $fieldName, $allowedSearchTypes)
	{
		$rangeObject = $searchItem->getRange();
		if(!$rangeObject)
			return null;
		/**@var $rangeObject ESearchRange*/
		$rangeSubQuery = array();
		$rangeQuery =  array();
		$queryType = self::RANGE_KEY;
		if(is_numeric($rangeObject->getGreaterThan()))
			$rangeSubQuery[self::GT_KEY] = $rangeObject->getGreaterThan();
		if(is_numeric($rangeObject->getGreaterThanOrEqual()))
			$rangeSubQuery[self::GTE_KEY] = $rangeObject->getGreaterThanOrEqual();
		if(is_numeric($rangeObject->getLessThan()))
			$rangeSubQuery[self::LT_KEY] = $rangeObject->getLessThan();
		if(is_numeric($rangeObject->getLessThanOrEqual()))
			$rangeSubQuery[self::LTE_KEY] = $rangeObject->getLessThanOrEqual();

		$rangeQuery[$queryType][$fieldName] = $rangeSubQuery;
		return $rangeQuery;
	}

	public static function getExistsQuery($searchItem, $fieldName, $allowedSearchTypes)
	{
		$ExistsQuery = array();
		$queryType = self::EXISTS_KEY;
		$ExistsQuery[$queryType][self::FIELD_KEY] = $fieldName;
		return $ExistsQuery;
	}

}
