<?php
/**
 * @package plugins.elasticSearch
 * @subpackage model.errors
 */
class kESearchException extends kCoreException
{
    const SEARCH_TYPE_NOT_ALLOWED_ON_FIELD = 'Search type not allowed on field';
    const EMPTY_SEARCH_TERM_NOT_ALLOWED = 'Empty search term is not allowed on Field';
    const SEARCH_TYPE_NOT_ALLOWED_ON_UNIFIED_SEARCH = 'Search type is not allowed on unified search';
    const ELASTIC_SEARCH_ENGINE_ERROR = 'Elastic search engine error';
    const MISSING_PARAMS_FOR_DELETE = 'Missing params for delete';
}