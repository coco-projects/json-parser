<?php

    use Coco\JsonParser\JsonParser;

    require '../vendor/autoload.php';

    $source = 'data/test2.json';

//    $parser = new JsonParser($source);
    $parser = JsonParser::parse($source);

    $parser = $parser->pointer('/data/user/-');

    foreach ($parser as $key => $value)
    {
        echo $parser->progress()->percentage();
        echo PHP_EOL;
        print_r($value);
        echo PHP_EOL;
        echo PHP_EOL;
    }