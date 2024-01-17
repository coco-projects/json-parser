<?php

    use Coco\JsonParser\JsonParser;

    require '../vendor/autoload.php';

    $source = 'data/service.json';

//    $parser = new JsonParser($source);
    $parser = JsonParser::parse($source);

    $parser = $parser->pointer('/-/name');

    foreach ($parser as $key => $value)
    {
        echo $parser->progress()->percentage();
        echo PHP_EOL;
        print_r($value);
        echo PHP_EOL;
        echo PHP_EOL;
    }