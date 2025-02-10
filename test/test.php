<?php

include_once __DIR__ . '/../php/context/SimpleContextBuilder.php';

$serialized = ContextSerializer::serialize([
    'recipients' => [
        'thomas.prinz@uni-jena.de'
    ],
    'attachments' => [
        new ContextVariable('result')
    ]
]);
echo $serialized . PHP_EOL;
$deserialized = ContextSerializer::deserialize($serialized);
var_dump($deserialized);
$deserialized['result'] = [
    'Hallo',
    'Welt'
];
$serialized = ContextSerializer::serialize($deserialized);
echo $serialized . PHP_EOL;
$deserialized = ContextSerializer::deserialize($serialized);
var_dump($deserialized);
$deserialized = CPNode::prepareContext($deserialized);
$serialized = ContextSerializer::serialize($deserialized);
echo $serialized . PHP_EOL;
$deserialized = ContextSerializer::deserialize($serialized);
echo "Final:" . PHP_EOL;
var_dump($deserialized);
?>