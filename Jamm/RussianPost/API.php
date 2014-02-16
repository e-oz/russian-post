<?php
namespace Jamm\RussianPost;

use Jamm\HTTP\IResponse;
use Jamm\HTTP\Request;
use Jamm\HTTP\Response;

class API
{
  private $soap_api_url = 'http://voh.russianpost.ru:8080/niips-operationhistory-web/OperationHistory';
  private $root_namespace = 'S';
  private $operation_history_namespace = 'ns4';

  public function __construct($soap_api_url = null)
  {
    if ($soap_api_url) {
      $this->setSoapApiUrl($soap_api_url);
    }
  }

  public function getOperationsHistory($tracking_number, $message_type = 0)
  {
    if (empty($tracking_number)) {
      return false;
    }
    $Response = $this->getOperationsHistoryResponse($tracking_number, $message_type);
    return $this->convertOperationsHistoryResponseToObjects($Response);
  }

  protected function getOperationsHistoryResponse($tracking_number, $message_type = 0)
  {
    $Request  = $this->getNewRequestObject();
    $Response = $this->getNewResponseObject();
    $Request->setMethod('POST');
    $Request->setData('<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">'.
      '<s:Header/>'.
      '<s:Body xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'.
      '<OperationHistoryRequest xmlns="http://russianpost.org/operationhistory/data">'.
      '<Barcode>'.$tracking_number.'</Barcode>'.
      '<MessageType>'.$message_type.'</MessageType>'.
      '</OperationHistoryRequest>'.
      '</s:Body>'.
      '</s:Envelope>');
    $Request->setHeader('Content-Type', 'text/xml; charset=utf-8');
    $Request->Send($this->getSoapApiUrl(), $Response);
    return $Response;
  }

  /**
   * @param string $root_namespace
   */
  public function setRootNamespace($root_namespace = 'S')
  {
    $this->root_namespace = $root_namespace;
  }

  /**
   * @param string $operation_history_namespace
   */
  public function setOperationHistoryNamespace($operation_history_namespace = 'ns4')
  {
    $this->operation_history_namespace = $operation_history_namespace;
  }

  protected function convertOperationsHistoryResponseToObjects(IResponse $Response)
  {
    $internal_errors_prev_setting  = libxml_use_internal_errors(true);
    $disable_entities_prev_setting = libxml_disable_entity_loader(true);
    libxml_clear_errors();
    $xml = simplexml_load_string($Response->getBody());
    libxml_use_internal_errors($internal_errors_prev_setting);
    libxml_disable_entity_loader($disable_entities_prev_setting);

    $namespaces = $xml->getNamespaces(true);
    $records    = $xml->children($namespaces[$this->root_namespace])->Body->children($namespaces[$this->operation_history_namespace])->OperationHistoryData->historyRecord;
    if (empty($records)) {
      return false;
    }
    $json = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $json;
  }

  /**
   * @return string
   */
  public function getSoapApiUrl()
  {
    return $this->soap_api_url;
  }

  /**
   * @param string $soap_api_url
   */
  public function setSoapApiUrl($soap_api_url)
  {
    $this->soap_api_url = $soap_api_url;
  }

  protected function getNewRequestObject()
  {
    return new Request();
  }

  protected function getNewResponseObject()
  {
    return new Response();
  }
} 
