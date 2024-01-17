<?php

    use Coco\JsonParser\JsonParser;

    require '../vendor/autoload.php';

    $source = 'https://randomuser.me/api/1.4?seed=json-parser&results=5';
    $source = 'data/service.json';

//    $parser = new JsonParser($source);
    $parser = JsonParser::parse($source);

    try
    {
        foreach ($parser as $key => $value)
        {
            print_r($value);
            echo PHP_EOL;
        }
    }
    catch (\Coco\JsonParser\Exceptions\SyntaxException $e)
    {
    }