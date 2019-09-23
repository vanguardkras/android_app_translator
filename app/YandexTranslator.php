<?php

/**
 * Yandex translator.
 * 
 * @author Макс
 */
class YandexTranslator implements Processor
{
    private $ini_lang;
    private $key;
    private $lang;
    private $service;
    
    
    public function __construct(string $lang) 
    {
        $settings = parse_ini_file('settings.ini');
        
        $this->ini_lang = $settings['initial_lang'];
        $this->key = $settings['key'];
        $this->service = $settings['service'];
        $this->lang = $lang;
    }
    
    /**
     * Sends request to yandex and returns translated data.
     * 
     * @param string $data
     * @return string
     */
    public function change(string $data, $slashes = true): string
    {
        $data = explode('\\n', $data);
        
        foreach ($data as &$d) {
            if ($slashes) {
                $d = addslashes($d);
            }
            
            $d = str_replace('http', '. @@@http', $d);
            $d = str_replace('#', '. # ', $d);
            $d = str_replace('=', '. @@@@ ', $d);
            $d = $this->request($d);
            $d = str_replace('. @@@http', 'http', $d);
            $d = str_replace('. @@@@ ', '=', $d);
            $d = str_replace('. # ', '#', $d);
            
            $d = $this->rusFails($d);
            /*
            if ($slashes) {
                $d = stripslashes($d);
            }
			*/
        }
        
        return implode('\n', $data);
        
    }
    
    /**
     * Highlight Russian language fail translations, but makes one additional
     * attempt before.
     * @param string $text
     * @return string
     */
    private function rusFails(string $text): string
    {
        $pattern = '@([а-я]+)@ui';
        $try_again = parse_ini_file('settings.ini', true)['try_again'];
        
        if ($try_again === 'true') {
            $text = preg_replace_callback($pattern, function($matches) {
                $prom_res = $this->request($matches[0]);
                return $prom_res;
            }, $text);
        }
        
        return preg_replace($pattern, '{!!!}$1{!!!}', $text);
    }
    
    /**
     * Processes curl request to yandex.
     * 
     * @param string $data
     * @return mixed
     */
    private function request(string $data): string
    {
        
        $request = [
            'key' => $this->key,
            'text' => $data,
            'lang' => $this->ini_lang.'-'.$this->lang,
            'format' => 'plain',
        ];
        
        $con = curl_init($this->service);
        
        curl_setopt_array($con, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($request),
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $result = curl_exec($con);
        curl_close($con);
        
		$response = json_decode($result, true);
		
		if (isset($response['text'])) {
            return $response['text'][0];
		} else {
			return '';
		}
    }
}
