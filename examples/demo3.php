<?php

    use Coco\JsonParser\JsonParser;

    require '../vendor/autoload.php';

    $source = 'https://randomuser.me/api/1.4?seed=json-parser&results=5';

//    $parser = new JsonParser($source);
    $parser = JsonParser::parse($source);

    $parser->pointer('/results/-/gender')->traverse(function(mixed $value, string|int $key, JsonParser $parser) {

        print_r($value);
        echo PHP_EOL;
    });

