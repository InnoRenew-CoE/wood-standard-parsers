<?php
function fetch_page(int $page): string
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://standards.iteh.ai/api/catalog/search");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        <<<DATA
{"errors":[],"availablePageSizes":[10,20,50,100],"page":$page,"pageSize":100,"total":227863,"readonlyFields":[],"statusesFilter":["I","P","W"],"sortBy":1,"bodyId":null,"icsId":null,"directiveId":null,"mandateId":null,"publicationDateRange":{"from":null,"to":null},"withdrawalDateRange":{"from":null,"to":null},"publicEnquiryEndRange":{"from":null,"to":null}}
DATA
    ); // Post Fields
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:138.0) Gecko/20100101 Firefox/138.0",
        "Accept: application/json, text/plain, */*",
        "Accept-Language: en-US,en;q=0.5",
        "Authorization: IhsggA2s2ppdoWKolIkxzNFCiu09moHub-lvKs8V4pZs49OT4hLhq3ypDctjKrdNyIA5GuBdFl-VCWULXQfkhLLWFlEFg1_bT9lJQGAj6iU",
        "Content-Type: application/json; charset=UTF-8",
        "Origin: https://standards.iteh.ai",
        "Alt-Used: standards.iteh.ai",
        "Connection: keep-alive",
        "Referer: https://standards.iteh.ai/catalog/search",
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

for ($i = 2277; $i <= 2280; $i++) {
    echo json_encode(json_decode(fetch_page($i)), JSON_PRETTY_PRINT);
    echo "\n--------------------- END OF PAGE $i ------------------------\n";
}
