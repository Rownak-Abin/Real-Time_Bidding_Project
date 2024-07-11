<?php

require './campaigns.php';
require './payloadValidation.php';

$bidRequestJson = file_get_contents('php://input');
$bidRequest = json_decode($bidRequestJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    jsonResponse(['error' => 'Invalid JSON in bid request'], 400);
}

//Validation checked for the payload
$validationResult = validateFields($bidRequest, $requiredFields);

if ($validationResult !== true) {
    jsonResponse(['error' => $validationResult], 400);
}

//Function to compare the bid request and campaigns
function handleBidRequest($bidRequest, $campaigns) {

    // Extract relevant parameters from the bid request
    $device = $bidRequest['device'] ?? null;
    $city = $device['geo']['city'] ?? null;
    $bidFloor = $bidRequest['imp'][0]['bidfloor'] ?? 0;
    $deviceType = $device['devicetype'] ?? null;
    $country = $device['geo']['country'] ?? null;
    $bidWidth = $bidRequest['imp'][0]['banner']['w'] ?? null;
    $bidHeight = $bidRequest['imp'][0]['banner']['h'] ?? null;
    $deviceOs = $device['os'] ?? null;

    // Filter campaigns based on bid request criteria
    $eligibleCampaigns = array_filter($campaigns, function($campaign) use ($country, $city, $bidWidth, $bidHeight, $bidFloor, $deviceOs) {
        $dimension = explode('x', $campaign['dimension']);
        $campaignWidth = $dimension[0] ?? null;
        $campaignHeight = $dimension[1] ?? null;
        $campaignDeviceOs = explode(',', $campaign['hs_os']);

        return ($campaign['country'] == $country) &&
               ($campaign['city'] == $city) &&
               ($campaignWidth == $bidWidth) &&
               ($campaignHeight == $bidHeight) &&
               ($campaign['price'] >= $bidFloor) &&
               (in_array($deviceOs, $campaignDeviceOs));
    });

    // Select the campaign with the highest bid price. Here usort perform 'QuickSort' algorithm for fast sorting.
    usort($eligibleCampaigns, function($a, $b) {
        return $b['price'] <=> $a['price'];
    });

    $selectedCampaign = $eligibleCampaigns[0] ?? null;

    // If no suitable campaign found
    if (!$selectedCampaign) {
        return jsonResponse(['error' => 'No suitable campaign found'], 204);
    }

    // Generate the response JSON
    $response = [
        "Bid_info" => [
            "Id" => $bidRequest['id'],
            "Price" => $bidFloor,
            "App" => $bidRequest['app']['name'],
            "Country" => $country,
            "City" => $city,
            "Operating System" => $deviceOs
        ],
        "Selected_campaign_info" => [
            'Campaign Name' => $selectedCampaign['campaignname'],
            'Advertiser' => $selectedCampaign['advertiser'],
            'Creative Type' => $selectedCampaign['creative_type'],
            'Image URL' => $selectedCampaign['image_url'],
            'Landing Page URL' => $selectedCampaign['url'],
            'width' => $bidWidth,
            'height' => $bidHeight
        ]
    ];

    return jsonResponse($response, 200);
}

//Used JSON Response of result
function jsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Calling the function
handleBidRequest($bidRequest, $campaigns);

?>
