<?php

use Elasticsearch\ClientBuilder;
require 'vendor/autoload.php';


function indexSite($url, $elementType, $elementText){
    $client = ClientBuilder::create()->build();
    $indexName = transformUrlToIndexName($url);

    createIndex($indexName, $client);

    $dom = getDom($url);
        
    $element = findElement($dom, $elementType, $elementText);
    $array = transformToArray(getNextSiblingNode($element), $element->nodeName, $elementText);
    
    indexData($array, $indexName, $client);   
}
    
function getDom($url){
    $doc = new DOMDocument();
    $doc->loadHTMLFile($url);
    return $doc;
}

function createIndex($indexName, $client){
    $params = ['index' => $indexName];
    if ($client->indices()->exists($params)){
        return;
    }

    $params = [
        'index' => $indexName,
        'body' => [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 1
            ],
            'mappings' => [
                '_source' => [
                    'enabled' => true
                ],
                'properties' => [
                    'header' => [
                        'type' => 'keyword'
                    ],
                    'text' => [
                        'type' => 'text'
                    ],
                    'section' => [
                        'type' => 'keyword'
                    ]
                ]
            ]
        ]
    ];

    try {
        $response = $client->indices()->create($params);
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    } 
}

function indexData($array, $indexName, $client){
    foreach ($array as $i=>$row){
        $params = [
            'index' => $indexName,
            'id' => $i,
            'body' => $row
        ];
        try{
            $response = $client->index($params);
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        } 
    }
}

function transformUrlToIndexName($url){
    $url = strtolower($url);
    if (substr($url, -1) == "/"){
        $url = substr($url, 0, -1);
    }
    
    $indexName = str_replace("https://", "", $url);
    $indexName = str_replace("http://", "", $indexName);
    $indexName = str_replace("/", "-", $indexName);
    
    return $indexName;
}

function findElement($dom, $elementType, $elementText){
    $elementText = strtolower($elementText);
    if (strtolower($elementType) != "h"){
        foreach($dom->getElementsByTagName($elementType) as $element){
            $currentElementText = strtolower($element->nodeValue);
            if (strpos($currentElementText, $elementText)){
                return $element;
            }
        }
    }else{
        $headerNum = 1;
        $maxHeaderNum = 6;
        
        while ($headerNum <= $maxHeaderNum){
            $elementType = "h".$headerNum;
            foreach($dom->getElementsByTagName($elementType) as $element){
                $currentElementText = strtolower($element->nodeValue);
                if (strpos($currentElementText, $elementText)){
                    return $element;
                }
            }
            $headerNum++;
        }
    }
    
    echo "Element not found";
}

function transformToArray($element, $elementType, $sectionName){
    //TODO - elementType <> hXX
    $sectionName = getSectionNameFromText($sectionName);
    
    $headerNum = substr($elementType, -1);
    $headerNum++;
    $elementType = "h".$headerNum;
    $i=0;

    foreach($element->getElementsByTagName($elementType) as $child){
        $returnArray[$i] ['header'] = $child->nodeValue;
        $returnArray[$i] ['text'] = getNextSiblingNode($child)->nodeValue;
        $returnArray[$i] ['section'] = $sectionName;
        $i++;
    }

    return $returnArray;
}

function getNextSiblingNode($node)
{
    while ($node && ($node = $node->nextSibling)) {
        if ($node instanceof DOMElement) {
            break;
        }
    }
    return $node;
}

function getSectionNameFromText($text){
   return str_replace(" ", "-", $text); 
}

function getIndexData($url, $sectionName){
    $client = ClientBuilder::create()->build();
    
    $indexName = transformUrlToIndexName($url);
    $sectionName = getSectionNameFromText($sectionName);

    $params = [
        'index' => $indexName,
        'from' => 0,
        'size' => 100,
        'body' => [
            'query' => [
                'match' => [
                    'section' => $sectionName
                ]
            ]
        ]
    ];
    
    try {
        $response = $client->search($params);
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    } 
    
    foreach($response['hits']['hits'] as $i=>$record){
        $returnArray[$i]['header'] = $record['_source']['header'];
        $returnArray[$i]['text'] = $record['_source']['text'];
    }

    return $returnArray;
}

?>