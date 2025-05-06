<?php
// https://www.eota.eu/etassessments?filter1=1&filter1_search=&filter2=1&filter2_search=&filter3=1&filter3_search=

use Dom\HTMLDocument;
use Dom\HTMLElement;
use Dom\Node;
use Dom\XPath;

use const Dom\HTML_NO_DEFAULT_NS;

error_reporting(E_ERROR | E_PARSE);
function fetch_page(string $url): string
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:138.0) Gecko/20100101 Firefox/138.0",
        // "Accept: application/json, text/plain, */*",
        // "Accept-Language: en-US,en;q=0.5",
        // "Authorization: IhsggA2s2ppdoWKolIkxzNFCiu09moHub-lvKs8V4pZs49OT4hLhq3ypDctjKrdNyIA5GuBdFl-VCWULXQfkhLLWFlEFg1_bT9lJQGAj6iU",
        // "Content-Type: application/json; charset=UTF-8",
        // "Origin: https://standards.iteh.ai",
        // "Alt-Used: standards.iteh.ai",
        // "Connection: keep-alive",
        // "Referer: https://standards.iteh.ai/catalog/search",
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function clean($str)
{
    $str = str_replace(" ", " ", $str);
    $str = preg_replace("/\s+/", " ", $str);
    $str = trim($str);
    return $str;
}

// $dom = Dom\HTMLDocument::createFromString(fetch_page("https://www.eota.eu/etassessments?filter1=1&filter1_search=&filter2=1&filter2_search=&filter3=1&filter3_search="));
$dom = Dom\HTMLDocument::createFromFile("output.html", HTML_NO_DEFAULT_NS);
$xpath = new XPath($dom);

$elements = $xpath->query('//td[@data-label="ETA Number"]/a');

$keys = [
    "ETA Number",
    "Version",
    "Date of issue",
    "Trade Name",
    "Generic type and use",
    "Holder of assesment",
    "Holder Address",
    "Holder Postal Code",
    "Holder City",
    "Holder Country",
    "Issuing TAB",
    "Reference number",
    "EU Decisioin number(OJEU)",
    "System",
];

$total_keys = count($keys);

echo join(";", $keys) . "\n";

$i = 0;
/** @var DOMNode $e */
foreach ($elements as $e) {
    $url = "https://www.eota.eu" . $e->attributes->item(0)->nodeValue;
    $page = fetch_page($url);
    $dom = HTMLDocument::createFromString($page, HTML_NO_DEFAULT_NS);
    $x = new XPath($dom);
    $paragraphs = $x->query("//div[@class='text']//p");

    $paragraphs = iterator_to_array($paragraphs->getIterator());
    $values = array_map(function ($node) {
        /** @var DOMNode $node */
        return clean($node->textContent);
    }, $paragraphs);
    echo "$url;" . join(";", $values) . "\n";
    if ($i >= 5) {
        break;
    }
    $i++;
}
