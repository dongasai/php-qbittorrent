<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Response\Application\VersionResponse;
use PhpQbittorrent\Response\Application\BuildInfoResponse;
use PhpQbittorrent\Response\Transfer\GlobalTransferInfoResponse;

class ResponseTest extends TestCase
{
    public function testVersionResponseFromString(): void
    {
        $version = "v5.1.2";
        $response = VersionResponse::fromApiResponse($version);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals($version, $response->getVersion());
        $this->assertEmpty($response->getErrors());
    }

    public function testVersionResponseFromArray(): void
    {
        $versionData = ["v5.1.2"];
        $response = VersionResponse::fromApiResponse($versionData[0]);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals("v5.1.2", $response->getVersion());
        $this->assertEmpty($response->getErrors());
    }

    public function testVersionResponseFromJsonString(): void
    {
        $versionJson = '["v5.1.2"]';
        $response = VersionResponse::fromApiResponse($versionJson);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals("v5.1.2", $response->getVersion());
        $this->assertEmpty($response->getErrors());
    }

    public function testVersionResponseFailure(): void
    {
        $errors = ['Server error'];
        $response = VersionResponse::failure($errors);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('', $response->getVersion());
        $this->assertEquals($errors, $response->getErrors());
    }

    public function testBuildInfoResponse(): void
    {
        $buildData = [
            'qt' => '6.5.3',
            'libtorrent' => '2.0.8',
            'boost' => '1.81.0',
            'openssl' => '3.0.2',
            'bitness' => 64
        ];

        $response = BuildInfoResponse::fromApiResponse($buildData);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('6.5.3', $response->getQtVersion());
        $this->assertEquals('2.0.8', $response->getLibtorrentVersion());
        $this->assertEquals('1.81.0', $response->getBoostVersion());
        $this->assertEquals('3.0.2', $response->getOpensslVersion());
        $this->assertEquals(64, $response->getBitness());
        $this->assertTrue($response->is64Bit());

        $buildInfo = $response->getBuildInfo();
        $this->assertIsArray($buildInfo);
        $this->assertEquals($buildData, $buildInfo);
    }

    public function testBuildInfoResponseFailure(): void
    {
        $errors = ['Build info not available'];
        $response = BuildInfoResponse::failure($errors);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('', $response->getQtVersion());
        $this->assertEquals('', $response->getLibtorrentVersion());
        $this->assertEquals('', $response->getBoostVersion());
        $this->assertEquals('', $response->getOpensslVersion());
        $this->assertEquals(0, $response->getBitness());
        $this->assertFalse($response->is64Bit());
    }

    public function testGlobalTransferInfoResponse(): void
    {
        $transferData = [
            'dl_info_speed' => 1048576, // 1MB/s
            'dl_info_data' => 1073741824, // 1GB
            'up_info_speed' => 524288,   // 512KB/s
            'up_info_data' => 536870912, // 512MB
            'dl_rate_limit' => 0,
            'up_rate_limit' => 0,
            'dht_nodes' => 150,
            'connection_status' => 'connected'
        ];

        $response = GlobalTransferInfoResponse::fromApiResponse($transferData);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals(1048576, $response->getDownloadSpeed());
        $this->assertEquals(1073741824, $response->getDownloadedData());
        $this->assertEquals(524288, $response->getUploadSpeed());
        $this->assertEquals(536870912, $response->getUploadedData());
        $this->assertEquals(0, $response->getDownloadRateLimit());
        $this->assertEquals(0, $response->getUploadRateLimit());
        $this->assertEquals(150, $response->getDhtNodes());
        $this->assertEquals('connected', $response->getConnectionStatus());
    }

    public function testGlobalTransferInfoResponseFailure(): void
    {
        $errors = ['Transfer info not available'];
        $response = GlobalTransferInfoResponse::failure($errors);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals(0, $response->getDownloadSpeed());
        $this->assertEquals(0, $response->getDownloadedData());
        $this->assertEquals(0, $response->getUploadSpeed());
        $this->assertEquals(0, $response->getUploadedData());
        $this->assertEquals(0, $response->getDhtNodes());
        $this->assertEquals('disconnected', $response->getConnectionStatus());
    }

    public function testResponseFromArray(): void
    {
        $successData = [
            'success' => true,
            'data' => 'v5.1.2',
            'errors' => [],
            'statusCode' => 200,
            'headers' => ['Content-Type' => 'text/plain'],
            'rawResponse' => 'v5.1.2'
        ];

        $response = VersionResponse::fromArray($successData);
        $this->assertTrue($response->isSuccess());
        $this->assertEquals('v5.1.2', $response->getVersion());

        $failureData = [
            'success' => false,
            'data' => null,
            'errors' => ['Error occurred'],
            'statusCode' => 500,
            'headers' => [],
            'rawResponse' => ''
        ];

        $response = VersionResponse::fromArray($failureData);
        $this->assertFalse($response->isSuccess());
        $this->assertEquals(['Error occurred'], $response->getErrors());
    }

    public function testResponseStatusCodes(): void
    {
        $response = VersionResponse::success('v5.1.2', [], 200);
        $this->assertTrue($response->isSuccess());

        $response = VersionResponse::success('v5.1.2', [], 201);
        $this->assertTrue($response->isSuccess());

        $response = VersionResponse::success('v5.1.2', [], 204);
        $this->assertTrue($response->isSuccess());

        $response = VersionResponse::failure(['Error'], [], 400);
        $this->assertFalse($response->isSuccess());

        $response = VersionResponse::failure(['Error'], [], 500);
        $this->assertFalse($response->isSuccess());
    }

    public function testResponseHeadersAndStatusCode(): void
    {
        $headers = ['Content-Type' => 'text/plain', 'X-Custom' => 'value'];
        $statusCode = 200;
        $rawResponse = 'v5.1.2';

        $response = VersionResponse::success('v5.1.2', $headers, $statusCode, $rawResponse);

        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals($headers, $response->getHeaders());
        $this->assertEquals($rawResponse, $response->getRawResponse());
    }
}