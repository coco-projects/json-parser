<?php

    use Coco\JsonParser\JsonParser;

    require '../vendor/autoload.php';

    $source = 'data/service.json';

//    $parser = new JsonParser($source);
    $parser = JsonParser::parse($source);

    $parser->pointer('/-/name')->traverse(function(mixed $value, string|int $key, JsonParser $parser) {

        print_r($value);
        echo PHP_EOL;
    });

