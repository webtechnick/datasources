<?php
/**
 * A CakePHP datasource for Custom Google Search.
 *
 * Create a datasource in your config/database.php
 *  var $google_search = array(
 *    'datasource' => 'WebTechNick.GoogleSearchSource',
 *    'token' => 'YOUR CSE TOKEN',
 *    'format' => 'xml_no_dtd',
 *  ); 
 *
 * @version 0.1
 * @author Nick Baker <nick@webtechnick.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Xml');
class GoogleSearchSource extends DataSource{
  /**
    * Description of datasource
    * @access public
    */
  var $description = "Google Data Source";
  
  /**
    * Base url
    */
  var $url = 'http://www.google.com/cse';
  
  /**
    * Query array
    * @access public
    */
  var $query = null;
  
  /**
    * HttpSocket object
    * @access public
    */
  var $Http = null;
  
  /**
    * Requests Logs
    * @access private
    */
  var $__requestLog = array();
  
  /**
    * Schema
    * @access protected
    */
  var $_schema = array(
    'google' => array(
      'link' => array(
        'type' => 'string',
        'null' => true,
      ),
      'title' => array(
        'type' => 'string',
        'null' => true,
      ),
      'description' => array(
        'type' => 'text',
        'null' => true,
      ),
    )
  );
  
  /**
    * Append HttpSocket to Http
    */
  function __construct($config) {
    parent::__construct($config);
    App::import('HttpSocket');
    $this->Http = new HttpSocket();
  }
  
  /**
    * List sources for this datasource
    */
  function listSources(){
    return array('google');
  }
  
  /**
    * Describe the datasource
    */
  function describe(){
    return $this->_schema['google'];
  }
  
  /**
    * Run a find only if we have a term to actually search for
    *
    * @param $model ignored
    * @param array $queryData
    * @return array of results
    */
  function read($model, $queryData = array()){
    $retval = array();
    if(isset($queryData['conditions']['term'])){
      $this->query = array(
        'q' => $queryData['conditions']['term'],
        'cx' => $this->config['token'],
        'output' => $this->config['format'],
        'client' => 'google-csbe'
      );
      if(isset($queryData['offset'])){
        $this->query['start'] = $queryData['offset'];
      }
      $retval = $this->__makeRequest();
    }
    return $retval;
  }
	
  /**
    * Actually preform the request to Google
    *
    * @return mixed array of the resulting request or false if unablel to contact server
    * @access private
    */
  function __makeRequest(){
    $this->__requestLog[] = array('url' => $this->url, 'query' => $this->query);
    $result = $this->Http->get($this->url, $this->query);
    $retval = new SimpleExmlElement($result, LIBXML_NOCDATA);
    
    return $this->__parseResults($retval);
  }
  
  /**
    * Parse a resutls XML object into a CakePHP array
    */
  function __parseResults($xml_results){
    $retval = array();
    $retval['Spelling'] = $xml_results->Spelling->Suggestion;
    $retval['GoogleSearch'] = array();
    
    foreach($xml_results->RES->R as $result){
      $retval['GoogleSearch'][] = array(
        'link' => $result->U,
        'title' => $result->T,
        'description' => $result->S,
      );
    }
    return $retval;
  }
  
  /**
    * Play nice with the DebugKit
    * @param boolean sorted ignored
    * @param boolean clear will clear the log if set to true (default)
    */
  function getLog($sorted = false, $clear = true){
    $log = $this->__requestLog;
    if($clear){
      $this->__requestLog = array();
    }
    return array('log' => $log, 'count' => count($log), 'time' => 'Unknown');
  }
}
?>