<?php
class VirusScanPlugin extends KalturaPlugin implements IKalturaPermissions, IKalturaServices, IKalturaEventConsumers, IKalturaEnumerator, IKalturaObjectLoader
{
	const PLUGIN_NAME = 'virusScan';
	
	public static function getPluginName()
	{
		return self::PLUGIN_NAME;
	}
	
	public static function isAllowedPartner($partnerId)
	{
		$partner = PartnerPeer::retrieveByPK($partnerId);
		return $partner->getPluginEnabled(self::PLUGIN_NAME);
	}
	
	/**
	 * @return array<string,string> in the form array[serviceName] = serviceClass
	 */
	public static function getServicesMap()
	{
		$map = array(
			'virusScanProfile' => 'VirusScanProfileService',
		);
		return $map;
	}
	
	/**
	 * @return string - the path to services.ct
	 */
	public static function getServiceConfig()
	{
		return realpath(dirname(__FILE__).'/config/virus_scan.ct');
	}

	/**
	 * @return array
	 */
	public static function getEventConsumers()
	{
		return array(
		);
	}
	
	/**
	 * @return array<string> list of enum classes names that extend the base enum name
	 */
	public static function getEnums($baseEnumName)
	{
		if($baseEnumName == 'entryStatus')
			return array('VirusScanEntryStatus');
			
		if($baseEnumName == 'BatchJobType')
			return array('VirusScanBatchJobType');
			
		return array();
	}
	
	/**
	 * @param string $baseClass
	 * @param string $enumValue
	 * @param array $constructorArgs
	 * @return object
	 */
	public static function loadObject($baseClass, $enumValue, array $constructorArgs = null)
	{
		if($baseClass == 'kJobData')
		{
			if($enumValue == VirusScanBatchJobType::get()->coreValue(VirusScanBatchJobType::VIRUS_SCAN))
			{
				return new kVirusScanJobData();
			}
		}
	
		if($baseClass == 'KalturaJobData')
		{
			if($enumValue == VirusScanBatchJobType::get()->apiValue(VirusScanBatchJobType::VIRUS_SCAN))
			{
				return new KalturaVirusScanJobData();
			}
		}
		
		return null;
	}
	
	/**
	 * @param string $baseClass
	 * @param string $enumValue
	 * @return string
	 */
	public static function getObjectClass($baseClass, $enumValue)
	{
		if($baseClass == 'kJobData')
		{
			if($enumValue == VirusScanBatchJobType::get()->coreValue(VirusScanBatchJobType::VIRUS_SCAN))
			{
				return 'kVirusScanJobData';
			}
		}
	
		if($baseClass == 'KalturaJobData')
		{
			if($enumValue == VirusScanBatchJobType::get()->apiValue(VirusScanBatchJobType::VIRUS_SCAN))
			{
				return 'KalturaVirusScanJobData';
			}
		}
		
		return null;
	}
}
