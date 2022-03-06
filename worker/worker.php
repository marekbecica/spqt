<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/JobService.php';


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

// Connect to DB
$dsn = "mysql:host=$host;dbname=$db;charset=UTF8";
try {
    $pdo = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo $e->getMessage();
    exit('Unable to connect to database');
}
$jobService = new JobService($pdo);
$client = new Client();
// Loop through all available jobs
while ($job = $jobService->getAvailableJob()) {
    print_r("Processing job: {$job['url']}\n");
    // Lock job row with PROCESSING status
    if (!$jobService->setJobStatusProcessing($job['id'])) {
        print_r("Unable to lock job row for {$job['url']}, probably already locked by other worker.\n");
        continue;
    }
    try {
        // Send GET request to given URL
        $response = $client->get($job['url'], ['timeout' => 2]);
        // Save response http code with DONE status to the job
        $jobService->setJobResult($job['id'], JobService::JOB_STATUS_DONE, $response->getStatusCode());
        print_r("Updating {$job['url']}, with status DONE and http_code = {$response->getStatusCode()}.\n");
    } catch (GuzzleException $e) {
        // Save error code with ERROR status to the job
        error_log($e->getMessage());
        $httpCode = $e->getCode() ?? null;
        $jobService->setJobResult($job['id'], JobService::JOB_STATUS_ERROR, $httpCode);
        print_r("Updating {$job['url']}, with status ERROR and http_code = {$httpCode}.\n");
    }
}
print_r("All currently available jobs finished");