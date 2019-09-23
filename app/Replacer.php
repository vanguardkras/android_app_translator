<?php

/**
 * Replaces an xml file with a new version of it.
 *
 * @author Макс
 */
class Replacer 
{
    
    /**
     * Data source file.
     * 
     * @var string 
     */
    private $file;
    
    /**
     * An instance of a processor class.
     * 
     * @var Processor 
     */
    private $processor;
    
    /**
     * Checks if file had xml header. 
     * 
     * @var bool 
     */
    private $xml_header = false;
    
    public function __construct(string $file, Processor $processor) 
    {
        $this->file = $file;
        $this->processor = $processor;
        
        $handler = fopen($file, 'r');
        $first_row = fgets($handler);
        
        if (stristr($first_row, '<?xml')) {
            $this->xml_header = true;
        }
    }
    
    /**
     * 
     * @return string XML format
     */
    public function convert(): string
    {
        $doc = new DOMDocument;
        $file = file_get_contents($this->file);
        $file = str_replace('<b>', '\<b\>', $file);
	$file = str_replace('</b>', '\<\/b\>', $file);
        if (@$doc->loadXML($file)) {
            $doc->encoding = 'utf-8';
            $this->replace($doc->childNodes[0]);

            $result = $doc->saveXML();

            if (!$this->xml_header) {
                $result = self::removeXMLHeader($result);
            }
        } else {
            $data = file_get_contents($this->file);
            $result = $this->processor->change($data, false);
        }
        
        return $result;
    }
    
    /**
     * Removes XML header.
     * 
     * @param string $result
     * @return string
     */
    private static function removeXMLHeader(string $result): string
    {
        $pattern = '@^<\?xml.+\?>\n@';
        return preg_replace($pattern, '', $result);
    }
    
    /**
     * Replaces data with new information.
     */
    private function replace(DOMElement $elem): void
    {
        foreach ($elem->childNodes as $el) {
            
            $children = $el->childNodes->length ?? 0;
            
            if ($children > 1) {
                $this->replace($el);
            } else {
                if ($el->nodeType === XML_ELEMENT_NODE) {
                    $el->nodeValue = $this->processor->change($el->nodeValue);
                }
            }
        }
    }
}
