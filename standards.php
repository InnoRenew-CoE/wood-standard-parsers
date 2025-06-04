<?php
function fetch_standards(int $page): string
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://standards.iteh.ai/api/catalog/search");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        <<<DATA
{"errors":[],"availablePageSizes":[10,20,50,100],"page":$page,"pageSize":100,"total":227863,"readonlyFields":[],"statusesFilter":["I","P","W"],"sortBy":1,"bodyId":null,"icsId":null,"directiveId":null,"mandateId":null,"publicationDateRange":{"from":null,"to":null},"withdrawalDateRange":{"from":null,"to":null},"publicEnquiryEndRange":{"from":null,"to":null},"organizationId":"14910298-4616-4a08-a877-b1ffbe81b63e"}
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

// committee (value), TC name (name), EN Reference (projectReference), title, scope, status, pdf-url, last-update
function store_standards()
{
    $file = fopen("outputs/standards.csv", "w");
    fputcsv($file, ["Committee", "TC Name", "EN Reference", "Title", "Status", "Last Update", "pdf"], ";", escape: "");
    $page = 0;
    $total = 0;
    while (true) {
        $json = json_decode(fetch_standards($page++), true);
        $data = $json["data"];
        $data_count = count($data);
        if ($data_count == 0) {
            break;
        }
        $total = $json["total"];
        foreach ($data as $project) {
            $tc_id = $project["body"]["value"];
            $tc_name = $project["body"]["name"];
            $lastUpdate = $project["lastUpdatedDate"];
            $refence = $project["projectReference"];
            $title = $project["title"];
            // $description = clean($project["scope"]);
            $status = $project["currentStage"]["description"];
            $pdf_url = $project["projectDocuments"][0]["previewUrl"];
            fputcsv($file, [$tc_id, $tc_name, $refence, $title, $status, $lastUpdate, $pdf_url], ";", escape: "");
        }
        $percentage = number_format((100.0 * $page) / $total, 4);
        echo "\r$page / $total ~ \e[1;95m$percentage%\e[0m";
    }
}

function download_standards()
{
    $i = 0; // Start from...
    $folder = "outputs/standards";
    mkdir($folder, true);
    $handle = fopen("outputs/standards.csv", "r+");
    $header = fgetcsv($handle, separator: ";", escape: "\\");
    echo json_encode($header) . "\n";
    /** @var string[][] $lines */
    $lines = [];

    try {
        setlocale(LC_ALL, "de_DE.UTF-8");
        $retry = 0;
        while (true) {
            if ($retry >= 5) {
                die("Unable to read line $i");
            }
            $data = fgetcsv($handle, separator: ";", escape: "");
            if ($data === false) {
                $retry++;
                echo "Retrying line $i: " . json_encode($data) . "\n";
                continue;
            }
            $retry = 0;
            if ($i < 18068) {
                $i++;
                continue;
            }
            $lines[] = $data;
            $i++;
        }
        fclose($handle);
        echo "We have " . count($lines) . " lines to parse\n";

        $i = 0;
        /** @var string[] $data */
        foreach ($lines as $data) {
            $name = preg_replace("/[^a-zA-Z0-9:-_\s]/", "", $data[2]);
            $status = $data[4];
            if ($status !== "Publishing") {
                continue;
            }
            $url = $data[6];
            echo "[Standard $i\t\t] \e[0;33mDownloading\e[0m: $name";
            // 2018/C 019/04
            $ch = curl_init($url);
            $file_name = "$folder/$name.pdf";
            $fp = fopen($file_name, "wb");
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            echo "\r[Standard $i\t\t] \e[0;34mDownloaded\e[0m: $name \n";
            usleep(500_000);
            $i++;
        }
    } catch (Exception $e) {
        var_dump($e);
        echo "An exception occured...\n";
    }
    echo "Ended for some reason! \n";
}
