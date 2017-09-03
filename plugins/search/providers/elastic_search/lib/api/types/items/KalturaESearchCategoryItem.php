<?php
/**
 * @package plugins.elasticSearch
 * @subpackage api.objects
 */
class KalturaESearchCategoryItem extends KalturaESearchItem
{

	/**
	 * @var KalturaESearchCategoryFieldName
	 */
	public $fieldName;

	private static $map_between_objects = array(
		'fieldName'
	);

	private static $map_dynamic_enum = array();

	protected function getMapBetweenObjects()
	{
		return array_merge(parent::getMapBetweenObjects(), self::$map_between_objects);
	}

	public function toObject($object_to_fill = null, $props_to_skip = array())
	{
		if (!$object_to_fill)
			$object_to_fill = new ESearchCategoryItem();
		return parent::toObject($object_to_fill, $props_to_skip);
	}

	protected function getItemFieldName()
	{
		return $this->fieldName;
	}

	protected function getDynamicEnumMap()
	{
		return self::$map_dynamic_enum;
	}

}
