<?php

    use Coco\JsonParser\JsonParser;

    require '../vendor/autoload.php';

    $source = 'data/test1.json';

//    $parser = new JsonParser($source);
    $parser = JsonParser::parse($source);

    $parser->pointers([
        '/-/name',
        '/-/id',
    ])->traverse(function(mixed $value, string|int $key, JsonParser $parser) {

        print_r($value);
        echo PHP_EOL;
    });

