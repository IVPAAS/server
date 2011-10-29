<?php
/**
 * @package plugins.metadataBulkUploadXml
 * @subpackage lib
 */
class MetadataBulkUploadXmlEngineHandler implements IKalturaBulkUploadXmlHandler
{
	/**
	 * @var KalturaMetadataObjectType
	 */
	private $objectType = KalturaMetadataObjectType::ENTRY;
	
	/**
	 * @var string class name
	 */
	private $objectClass = 'KalturaBaseEntry';
	
	
	/**
	 * @var string XML node name
	 */
	private $containerName = 'customDataItems';
	
	/**
	 * @var string XML node name
	 */
	private $nodeName = 'customData';
	
	/**
	 * @var array<string, int> of metadata profiles by their system name
	 */
	private static $metadataProfiles = null;
	
	/**
	 * @var BulkUploadEngineXml
	 */
	private $xmlBulkUploadEngine = null;
	
	public function __construct($objectType, $objectClass, $nodeName, $containerName = null)
	{
		$this->objectType = $objectType;
		$this->objectClass = $objectClass;
		$this->containerName = $containerName;
		$this->nodeName = $nodeName;
	} 
	
	public function getMetadataProfileId($systemName)
	{
		if(is_null(self::$metadataProfiles))
		{
			$metadataPlugin = KalturaMetadataClientPlugin::get($this->xmlBulkUploadEngine->getClient());
			$metadataProfileListResponse = $metadataPlugin->metadataProfile->listAction();
			if(!is_array($metadataProfileListResponse->objects))
				return null;
				
			self::$metadataProfiles = array();
			foreach($metadataProfileListResponse->objects as $metadataProfile)
				if(!is_null($metadataProfile->systemName))
					self::$metadataProfiles[$metadataProfile->systemName] = $metadataProfile->id;
		}
		
		if(isset(self::$metadataProfiles["$systemName"]))
			return self::$metadataProfiles["$systemName"];
			
		return null;
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaBulkUploadXmlHandler::configureBulkUploadXmlHandler()
	 */
	public function configureBulkUploadXmlHandler(BulkUploadEngineXml $xmlBulkUploadEngine)
	{
		$this->xmlBulkUploadEngine = $xmlBulkUploadEngine;
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaBulkUploadXmlHandler::handleItemAdded()
	 */
	public function handleItemAdded(KalturaObjectBase $object, SimpleXMLElement $item)
	{
		if(!($object instanceof $this->objectClass))
			return;

		$metadataItems = $item;
		if($this->containerName)
		{	
			$containerName = $this->containerName;
			if(empty($item->$containerName))
				return;
				
			$metadataItems = $item->$containerName;
		}
				
		$nodeName = $this->nodeName;
		if(empty($metadataItems->$nodeName)) // if there is no costum data then we exit
			return;
			
		KalturaLog::debug("Handles custom metadata for object type [$this->objectType] class [$this->objectClass] id [$object->id] partner id [$object->partnerId]");
			
		$this->xmlBulkUploadEngine->impersonate();
		foreach($metadataItems->$nodeName as $customData)
			$this->handleCustomData($object->id, $customData);
		
		$this->xmlBulkUploadEngine->unimpersonate();
	}

	public function handleCustomData($objectId, SimpleXMLElement $customData)
	{
		$metadataProfileId = null;
		if(!empty($customData['metadataProfileId']))
			$metadataProfileId = (int)$customData['metadataProfileId'];

		if(!$metadataProfileId && !empty($customData['metadataProfile']))
			$metadataProfileId = $this->getMetadataProfileId($customData['metadataProfile']);
				
		if(!$metadataProfileId)
			throw new KalturaBatchException("Missing custom data metadataProfile attribute", KalturaBatchJobAppErrors::BULK_MISSING_MANDATORY_PARAMETER);
		
		$metadataPlugin = KalturaMetadataClientPlugin::get($this->xmlBulkUploadEngine->getClient());
		
		$metadataFilter = new KalturaMetadataFilter();
		$metadataFilter->metadataObjectTypeEqual = $this->objectType;
		$metadataFilter->objectIdEqual = $objectId;
		$metadataFilter->metadataProfileIdEqual = $metadataProfileId;
		
		$pager = new KalturaFilterPager();
		$pager->pageSize = 1;
		
		$metadataListResponse = $metadataPlugin->metadata->listAction($metadataFilter, $pager);
		
		$metadataId = null;
		if(is_array($metadataListResponse->objects) && count($metadataListResponse->objects) > 0)
		{
			$metadata = reset($metadataListResponse->objects);
			$metadataId = $metadata->id;
		}
		
		$metadataXmlObject = $customData->children();
		$metadataXml = $metadataXmlObject->asXML();
		
		if($metadataId)
			$metadataPlugin->metadata->update($metadataId, $metadataXml);
		else
			$metadataPlugin->metadata->add($metadataProfileId, $this->objectType, $objectId, $metadataXml);
	}

	/* (non-PHPdoc)
	 * @see IKalturaBulkUploadXmlHandler::handleItemUpdated()
	 */
	public function handleItemUpdated(KalturaObjectBase $object, SimpleXMLElement $item)
	{
		if(!$this->containerName)
			return;
		
		$containerName = $this->containerName;
		if(empty($item->$containerName))
			return;
			
		$metadataItems = $item->$containerName;
		
		$action = KBulkUploadEngine::$actionsMap[KalturaBulkUploadAction::UPDATE];
		if(isset($metadataItems->action))
			$action = strtolower($metadataItems->action);
			
		switch (strtolower($metadataItems->action))
		{
			case KBulkUploadEngine::$actionsMap[KalturaBulkUploadAction::UPDATE]:
			case KBulkUploadEngine::$actionsMap[KalturaBulkUploadAction::ADD]:
				$this->handleItemAdded($object, $item);
				break;
			default:
				throw new KalturaBatchException($containerName . "->action: {$metadataItems->action} is not supported", KalturaBatchJobAppErrors::BULK_ACTION_NOT_SUPPORTED);
		}
	}

	/* (non-PHPdoc)
	 * @see IKalturaBulkUploadXmlHandler::handleItemDeleted()
	 */
	public function handleItemDeleted(KalturaObjectBase $object, SimpleXMLElement $item)
	{
		// No handling required
	}
}
