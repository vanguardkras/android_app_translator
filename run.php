<?php

include_once 'app/Processor.php';
include_once 'app/Replacer.php';
include_once 'app/YandexTranslator.php';

function progress($current, $all) {
    $percent = round(($current / $all) * 100);
    echo 'Progress: ' . $percent . '/100%    ' . "\r";
}

$target_directory = 'values';

$files = scandir($target_directory);
$files = array_slice($files, 2);

$languages = parse_ini_file('settings.ini', true)['language_list'];

$languages_num = count($languages);
$files_num = count($files);

$lang_counter = 0;
foreach ($languages as $language) {

    $dir = 'values-' . $language;

    if (!is_dir($dir)) {
        mkdir('values-' . $language);
    }
    
    $files_counter = 0;
    foreach ($files as $file) {
        progress($lang_counter + ($files_counter / $files_num) ,$languages_num);
        
        $processor = new YandexTranslator($language);
        $replacer = (new Replacer($target_directory . DIRECTORY_SEPARATOR . $file, $processor));
        $result = str_replace('\<b\>', '<b>', $replacer->convert());
	$result = str_replace('\<\/b\>', '</b>', $result);
        file_put_contents('values-' . $language . DIRECTORY_SEPARATOR . $file, $result);
        $files_counter++;
    }
    $lang_counter++;
}
progress($languages_num, $languages_num);