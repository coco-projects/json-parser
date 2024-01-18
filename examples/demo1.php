<?php

    use Coco\JsonParser\JsonParser;

    require '../vendor/autoload.php';

    $source = 'data/test1.json';

    $parser = new JsonParser($source);
//    $parser = JsonParser::parse($source);

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