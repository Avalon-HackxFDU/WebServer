<?php

error_reporting(E_ALL ^ E_NOTICE);
require_once './src/QcloudApi/QcloudApi.php';

$text = $_GET["src"];
$config = array('SecretId'       => 'AKIDkOr9HE6O4c55wbIwtfIjz742ueFebDmF',
                'SecretKey'      => 'siQzQgplV3Vq0xmTzJRM7lHfHj2DLI3b',
                'RequestMethod'  => 'POST',
                'DefaultRegion'  => 'gz');

$wenzhi = QcloudApi::load(QcloudApi::MODULE_WENZHI, $config);
$package = array('title'=> $text,'content'=> $text);

$a = $wenzhi->TextKeywords($package);
$b = $wenzhi->TextSentiment($package);

if ($a === false || $b === false) {
    $error = $wenzhi->getError();
    $res = array (
        "status" => false,
        "msg"    => $error->getCode() . $error->getMessage()
    );
    echo json_encode($res);
} else {
    $res = array (
            "status"    => true,
            "keywords"  => $a["keywords"],
            "sentiment" => array($b)
        );
    echo json_encode($res);
}
 