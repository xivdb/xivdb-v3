<?php

namespace App\Service\Search;

use Symfony\Component\HttpFoundation\Request;
use XIVCommon\Language\LanguageList;

class SearchRequest
{
    const STRING_WILDCARD       = 'wildcard';
    const STRING_MULTI_MATCH    = 'multi_match';
    const STRING_QUERY_STRING   = 'query_string';
    const STRING_TERM           = 'term';
    const STRING_PHRASE_PREFIX  = 'match_phrase_prefix';
    const STRING_FUZZY          = 'fuzzy';
    
    const MIN_LIMIT = 1;
    const MAX_LIMIT = 500;
    
    const STRING_ALGORITHMS = [
        self::STRING_WILDCARD,
        self::STRING_MULTI_MATCH,
        self::STRING_QUERY_STRING,
        self::STRING_TERM,
        self::STRING_PHRASE_PREFIX,
        self::STRING_FUZZY,
    ];
    
    // specific indexes
    public $indexes = '';
    // the search string
    public $string = '';
    // the string query algorithm to use
    public $stringAlgo = 'wildcard';
    // string search column
    public $stringColumn = 'Name_%s';
    // list of filters
    public $filters = '';
    // sort field
    public $sortField = 'ID';
    // sort order
    public $sortOrder = 'asc';
    // limit (per page)
    public $limit = 200;
    // limit start
    public $limitStart = 0;
    // page to start from
    public $page = 1;
    // language
    public $language = LanguageList::DEFAULT;
    
    /**
     * Build the search request from the http request
     */
    public function buildFromRequest(Request $request)
    {
        $this->indexes          = $request->get('indexes',        $this->indexes);
        $this->string           = $request->get('string',         $this->string);
        $this->stringAlgo       = $request->get('string_algo',    $this->stringAlgo);
        $this->stringColumn     = $request->get('string_column',  $this->stringColumn);
        $this->page             = $request->get('page',           $this->page);
        $this->sortField        = $request->get('sort_field',     $this->sortField);
        $this->sortOrder        = $request->get('sort_order',     $this->sortOrder);
        $this->limit            = $request->get('limit',          $this->limit);
        $this->language         = $request->get('language',       $this->language);
        $this->filters          = $request->get('filters',        $this->filters);
        
        // validate provided indexes
        if ($this->indexes) {
            $this->indexes = explode(',', $this->indexes);
            $validIndexes = explode(',', SearchData::indexes());
    
            // remove non valid ones
            foreach ($this->indexes as $i => $index) {
                if (!in_array($index, $validIndexes)) {
                    unset($this->indexes[$i]);
                }
            }
            
            $this->indexes = implode(',', $this->indexes);
        }
        
        // check limit
        $this->limit = $this->limit >= self::MIN_LIMIT ? $this->limit : self::MIN_LIMIT;
        $this->limit = $this->limit <= self::MAX_LIMIT ? $this->limit : self::MAX_LIMIT;
    
        // Override some
        $this->page             = $this->page < 1 ? 1 : $this->page;
        $this->limitStart       = ($this->limit * ($this->page - 1));
    
        // override by what is allowed
        $this->language         = LanguageList::get($this->language);
    
        // lower case it for the sake of performance and analyzer matching
        $this->string           = strtolower($this->string);
        $this->stringAlgo       = in_array($this->stringAlgo, self::STRING_ALGORITHMS) ? $this->stringAlgo : self::STRING_WILDCARD;
        $this->stringColumn     = sprintf($this->stringColumn, $this->language);
    }
    
    /**
     * Build from WebSocket message
     */
    public function buildFromMessage($message)
    {
    
    }
}
