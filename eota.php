<?php
// https://www.eota.eu/etassessments?filter1=1&filter1_search=&filter2=1&filter2_search=&filter3=1&filter3_search=

include_once "utils.php";

use Dom\HTMLDocument;
use Dom\HTMLElement;
use Dom\Node;
use Dom\XPath;

use const Dom\HTML_NO_DEFAULT_NS;

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

function add_to_csv(array $visited, $file, string $url, bool $use_keywords)
{
    echo "Adding $url.\n";
    // $dom = Dom\HTMLDocument::createFromString(fetch_page("https://www.eota.eu/etassessments?filter1=1&filter1_search=&filter2=1&filter2_search=&filter3=1&filter3_search="));
    $dom = Dom\HTMLDocument::createFromString(fetch_page($url), HTML_NO_DEFAULT_NS);
    // $dom = Dom\HTMLDocument::createFromFile("output.html", HTML_NO_DEFAULT_NS);
    $xpath = new XPath($dom);
    $elements = $xpath->query('//td[@data-label="ETA Number"]/a');

    $i = 0;
    /** @var DOMNode $e */
    foreach ($elements as $e) {
        $url = "https://www.eota.eu" . $e->attributes->item(0)->nodeValue;
        echo "ETA: $url \n";
        if (in_array($url, $visited)) {
            continue;
        }
        $visited[] = $url;
        $page = fetch_page($url);
        if ($use_keywords) {
            $matches = [];
            $keyword_pattern = "timber|wood|lumber|composite lightweight panel";
            if (preg_match($keyword_pattern, $page, $matches)) {
                echo "We found matching: " . json_encode($matches) . "\n";
            }
        }
        $dom = HTMLDocument::createFromString($page, HTML_NO_DEFAULT_NS);
        $x = new XPath($dom);
        $paragraphs = $x->query("//div[@class='text']//p");
        $paragraphs = iterator_to_array($paragraphs->getIterator());
        $values = [$url];
        $values = array_merge(
            $values,
            array_map(function ($node) {
                /** @var DOMNode $node */
                return clean($node->textContent);
            }, $paragraphs)
        );

        fputcsv($file, $values, ";", escape: "");
        // if ($i >= 10) {
        //     break;
        // }
        // $i++;
    }
}

function store_etas()
{
    $keys = [
        "URL",
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
    /** @var string[] $visited */
    $visited = [];

    $etas_file = fopen("outputs/etas.csv", "w");
    fputcsv($etas_file, $keys, ";", escape: "");

    // // P13
    add_to_csv(
        $visited,
        $etas_file,
        "https://www.eota.eu/etassessments?filter1=12&filter1_search=13&filter2=1&filter2_search=&filter3=1&filter3_search=#etas-results",
        false
    );

    // // P14
    add_to_csv(
        $visited,
        $etas_file,
        "https://www.eota.eu/etassessments?filter1=12&filter1_search=14&filter2=1&filter2_search=&filter3=1&filter3_search=#etas-results",
        false
    );

    // // All
    add_to_csv(
        $visited,
        $etas_file,
        "https://www.eota.eu/etassessments?filter1=1&filter1_search=&filter2=1&filter2_search=&filter3=1&filter3_search=",
        true
    );
    fclose($etas_file);
}

function store_eads()
{
    $eads_file = fopen("outputs/eads.csv", "w");
    fputcsv($eads_file, ["url", "EAD Number", "EAD Title", "OJEU", "Status", "Comment"], ";", escape: "");
    $page = fetch_page("https://www.eota.eu/eads");
    $dom = HTMLDocument::createFromString($page, HTML_NO_DEFAULT_NS);
    $xpath = new XPath($dom);
    $rows = $xpath->query("//div[@id='eads-results']//tr[position()>1]");
    foreach ($rows as $row) {
        /** @var DOMNode $url */
        $url = $xpath->query(".//a", $row)->item(0);
        if (!$url) {
            continue;
        }
        $url = "https://www.eota.eu" . $url->attributes->getNamedItem("href")->textContent;
        echo $url . "\n";
        $tds = array_map(function (Dom\Element $node) {
            return str_replace("Download", "", clean($node->textContent));
        }, iterator_to_array($xpath->query(".//td", $row)));
        fputcsv($eads_file, array_merge([$url], $tds), ";", escape: "");
    }
    fclose($eads_file);
}

function download_eads()
{
    $folder = "outputs/eads";
    mkdir($folder, true);
    $handle = fopen("outputs/eads.csv", "r");
    $i = 0;
    $header = fgetcsv($handle, separator: ";");
    echo json_encode($header) . "\n";

    while (($data = fgetcsv($handle, separator: ";")) !== false) {
        $url = $data[0];
        $name = preg_replace("/[^a-zA-Z0-9:-_]/", "", $data[1]);
        echo "[EAD $i\t] \e[0;33mDownloading\e[0m: $name";
        // 2018/C 019/04
        $ch = curl_init($url);
        $file_name = "$folder/$name.pdf";
        $fp = fopen($file_name, "wb");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        echo "\r[EAD $i\t] \e[0;34mDownloaded\e[0m: $name \n";
        usleep(500_000);
        $i++;
    }
}
