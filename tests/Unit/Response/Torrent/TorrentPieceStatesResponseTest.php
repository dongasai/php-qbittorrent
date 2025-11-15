<?php
declare(strict_types=1);

namespace Tests\Unit\Response\Torrent;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Response\Torrent\TorrentPieceStatesResponse;
use PhpQbittorrent\Model\TorrentPieceState;
use PhpQbittorrent\Exception\ResponseParseException;
use PhpQbittorrent\Exception\ValidationException;

/**
 * TorrentPieceStatesResponse 单元测试
 */
class TorrentPieceStatesResponseTest extends TestCase
{
    private string $testHash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';

    public function testSuccessResponseCreation(): void
    {
        $pieceStates = [
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 0),
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 1),
            new TorrentPieceState(TorrentPieceState::STATE_NOT_DOWNLOADED, 2),
        ];
        $rawResponse = ['status' => 200];
        $responseData = [0, 1, 0]; // API response data

        $response = TorrentPieceStatesResponse::success($pieceStates, $this->testHash, $rawResponse, $responseData);

        $this->assertEquals($pieceStates, $response->getPieceStates());
        $this->assertEquals($this->testHash, $response->getTorrentHash());
        $this->assertEquals(3, $response->getCount());
        $this->assertTrue($response->hasPieces());
        $this->assertTrue($response->validate()->isValid());
    }

    public function testErrorResponseCreation(): void
    {
        $rawResponse = ['status' => 404];
        $responseData = ['error' => 'Torrent not found'];

        $response = TorrentPieceStatesResponse::error($rawResponse, $responseData, 'Custom error message');

        $this->assertEquals([], $response->getPieceStates());
        $this->assertEquals('', $response->getTorrentHash());
        $this->assertEquals(0, $response->getCount());
        $this->assertFalse($response->hasPieces());
    }

    public function testFromApiResponseValid(): void
    {
        $apiResponse = [
            'data' => [0, 1, 0, 2, 2, 1, 0], // Piece states array
            'headers' => ['Content-Type' => 'application/json'],
            'status_code' => 200,
            'body' => '[0,1,0,2,2,1,0]'
        ];

        $response = TorrentPieceStatesResponse::fromApiResponse($apiResponse, $this->testHash);

        $this->assertCount(6, $response->getPieceStates());
        $this->assertEquals($this->testHash, $response->getTorrentHash());
        $this->assertTrue($response->hasPieces());
        $this->assertEquals(6, $response->getCount());

        // 检查具体的piece状态
        $pieces = $response->getPieceStates();
        $this->assertTrue($pieces[0]->isDownloaded());
        $this->assertTrue($pieces[1]->isDownloading());
        $this->assertTrue($pieces[2]->isNotDownloaded());
        $this->assertTrue($pieces[3]->isDownloaded());
        $this->assertTrue($pieces[4]->isDownloading());
        $this->assertTrue($pieces[5]->isNotDownloaded());
    }

    public function testFromApiResponseEmpty(): void
    {
        $apiResponse = [
            'data' => [],
            'headers' => ['Content-Type' => 'application/json'],
            'status_code' => 200,
            'body' => '[]'
        ];

        $response = TorrentPieceStatesResponse::fromApiResponse($apiResponse, $this->testHash);

        $this->assertEquals([], $response->getPieceStates());
        $this->assertEquals($this->testHash, $response->getTorrentHash());
        $this->assertEquals(0, $response->getCount());
        $this->assertFalse($response->hasPieces());
        $this->assertEquals(0.0, $response->getDownloadProgress());
    }

    public function testFromApiResponseMissingData(): void
    {
        $this->expectException(ResponseParseException::class);
        $this->expectExceptionMessage('Missing data field in API response');

        $apiResponse = [
            'headers' => ['Content-Type' => 'application/json'],
            'status_code' => 200
        ];

        TorrentPieceStatesResponse::fromApiResponse($apiResponse, $this->testHash);
    }

    public function testFromApiResponseInvalidData(): void
    {
        $this->expectException(ResponseParseException::class);
        $this->expectExceptionMessage('Response data must be an array');

        $apiResponse = [
            'data' => 'not an array',
            'headers' => ['Content-Type' => 'application/json'],
            'status_code' => 200
        ];

        TorrentPieceStatesResponse::fromApiResponse($apiResponse, $this->testHash);
    }

    public function testFromApiResponseInvalidStateValue(): void
    {
        $this->expectException(ResponseParseException::class);
        $this->expectExceptionMessageMatches('/Invalid piece state value 3 at index \\d+/');

        $apiResponse = [
            'data' => [0, 1, 3], // 3 is invalid
            'headers' => ['Content-Type' => 'application/json'],
            'status_code' => 200,
            'body' => '[0,1,3]'
        ];

        TorrentPieceStatesResponse::fromApiResponse($apiResponse, $this->testHash);
    }

    public function testFromApiResponseNonIntegerState(): void
    {
        $this->expectException(ResponseParseException::class);
        $this->expectExceptionMessageMatches('/Piece state at index \\d+ must be an integer/');

        $apiResponse = [
            'data' => [0, 1, 'invalid'], // string is invalid
            'headers' => ['Content-Type' => 'application/json'],
            'status_code' => 200,
            'body' => '[0,1,"invalid"]'
        ];

        TorrentPieceStatesResponse::fromApiResponse($apiResponse, $this->testHash);
    }

    public function testFindByIndex(): void
    {
        $pieceStates = [
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 0),
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 5),
            new TorrentPieceState(TorrentPieceState::STATE_NOT_DOWNLOADED, 10),
        ];
        $response = TorrentPieceStatesResponse::success($pieceStates, $this->testHash, [], []);

        $found = $response->findByIndex(5);
        $notFound = $response->findByIndex(3);

        $this->assertNotNull($found);
        $this->assertEquals(5, $found->getIndex());
        $this->assertEquals(TorrentPieceState::STATE_DOWNLOADING, $found->getState());
        $this->assertNull($notFound);
    }

    public function testGetFilteredPieces(): void
    {
        $pieceStates = [
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 0),
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 1),
            new TorrentPieceState(TorrentPieceState::STATE_NOT_DOWNLOADED, 2),
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 3),
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 4),
            new TorrentPieceState(TorrentPieceState::STATE_NOT_DOWNLOADED, 5),
        ];
        $response = TorrentPieceStatesResponse::success($pieceStates, $this->testHash, [], []);

        $notDownloaded = $response->getNotDownloadedPieces();
        $downloading = $response->getDownloadingPieces();
        $downloaded = $response->getDownloadedPieces();

        $this->assertCount(2, $notDownloaded);
        $this->assertCount(2, $downloading);
        $this->assertCount(2, $downloaded);

        // 验证索引正确
        $this->assertEquals(2, $notDownloaded[0]->getIndex());
        $this->assertEquals(5, $notDownloaded[1]->getIndex());
        $this->assertEquals(1, $downloading[0]->getIndex());
        $this->assertEquals(4, $downloading[1]->getIndex());
        $this->assertEquals(0, $downloaded[0]->getIndex());
        $this->assertEquals(3, $downloaded[1]->getIndex());
    }

    public function testGetDownloadProgress(): void
    {
        // 测试空数据
        $emptyResponse = TorrentPieceStatesResponse::success([], $this->testHash, [], []);
        $this->assertEquals(0.0, $emptyResponse->getDownloadProgress());

        // 测试混合数据
        $pieceStates = [
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 0),  // 100%
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 1),  // 50%
            new TorrentPieceState(TorrentPieceState::STATE_NOT_DOWNLOADED, 2),  // 0%
        ];
        $response = TorrentPieceStatesResponse::success($pieceStates, $this->testHash, [], []);

        // (1 * 1.0 + 1 * 0.5 + 1 * 0.0) / 3 * 100 = 50%
        $this->assertEquals(50.0, $response->getDownloadProgress());
    }

    public function testGetCompletionStats(): void
    {
        $pieceStates = [
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 0),
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 1),
            new TorrentPieceState(TorrentPieceState::STATE_NOT_DOWNLOADED, 2),
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 3),
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 4),
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 5),
        ];
        $response = TorrentPieceStatesResponse::success($pieceStates, $this->testHash, [], []);

        $stats = $response->getCompletionStats();

        $this->assertEquals(6, $stats['total']);
        $this->assertEquals(2, $stats['not_downloaded']);
        $this->assertEquals(2, $stats['downloading']);
        $this->assertEquals(2, $stats['downloaded']);
        $this->assertEquals(33.33, $stats['not_downloaded_percent']); // 2/6 * 100
        $this->assertEquals(33.33, $stats['downloading_percent']); // 2/6 * 100
        $this->assertEquals(33.33, $stats['downloaded_percent']); // 2/6 * 100
        $this->assertEquals(50.0, $stats['download_progress']); // (2*1.0 + 2*0.5) / 6 * 100
    }

    public function testGetCompletionStatsEmpty(): void
    {
        $response = TorrentPieceStatesResponse::success([], $this->testHash, [], []);
        $stats = $response->getCompletionStats();

        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['not_downloaded']);
        $this->assertEquals(0, $stats['downloading']);
        $this->assertEquals(0, $stats['downloaded']);
        $this->assertEquals(0.0, $stats['not_downloaded_percent']);
        $this->assertEquals(0.0, $stats['downloading_percent']);
        $this->assertEquals(0.0, $stats['downloaded_percent']);
        $this->assertEquals(0.0, $stats['download_progress']);
    }

    public function testToArray(): void
    {
        $pieceStates = [
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 0),
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 1),
        ];
        $response = TorrentPieceStatesResponse::success($pieceStates, $this->testHash, [], []);

        $array = $response->toArray();

        $this->assertArrayHasKey('piece_states', $array);
        $this->assertArrayHasKey('torrent_hash', $array);
        $this->assertArrayHasKey('count', $array);
        $this->assertArrayHasKey('has_pieces', $array);
        $this->assertArrayHasKey('completion_stats', $array);

        $this->assertEquals(2, $array['count']);
        $this->assertTrue($array['has_pieces']);
        $this->assertEquals($this->testHash, $array['torrent_hash']);
    }

    public function testGetSummary(): void
    {
        $pieceStates = [
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 0),
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 1),
            new TorrentPieceState(TorrentPieceState::STATE_NOT_DOWNLOADED, 2),
        ];
        $response = TorrentPieceStatesResponse::success($pieceStates, $this->testHash, [], []);

        $summary = $response->getSummary();

        $this->assertArrayHasKey('torrent_hash', $summary);
        $this->assertArrayHasKey('pieces_count', $summary);
        $this->assertArrayHasKey('has_pieces', $summary);
        $this->assertArrayHasKey('download_progress', $summary);
        $this->assertArrayHasKey('completion_stats', $summary);
        $this->assertArrayHasKey('response_valid', $summary);
        $this->assertArrayHasKey('response_data_size', $summary);

        $this->assertEquals($this->testHash, $summary['torrent_hash']);
        $this->assertEquals(3, $summary['pieces_count']);
        $this->assertTrue($summary['has_pieces']);
        $this->assertTrue($summary['response_valid']);
    }

    public function testValidationWithInvalidPieceStates(): void
    {
        $pieceStates = [
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 0),
            'invalid piece state', // not TorrentPieceState instance
        ];
        $response = TorrentPieceStatesResponse::success($pieceStates, $this->testHash, [], []);

        $validation = $response->validate();
        $this->assertFalse($validation->isValid());
        $this->assertArrayHasKey('Piece state at index 1 is not a TorrentPieceState instance', $validation->getErrors());
    }

    public function testValidationWithInvalidTorrentHash(): void
    {
        $pieceStates = [
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 0),
        ];
        $invalidHash = 'invalid_hash';

        $response = new TorrentPieceStatesResponse($pieceStates, $invalidHash, [], []);

        $validation = $response->validate();
        $this->assertFalse($validation->isValid());
        $this->assertArrayHasKey('Torrent hash is invalid', $validation->getErrors());
    }

    public function testValidationWithValidTorrentHash(): void
    {
        $pieceStates = [
            new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 0),
        ];
        $validHash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';

        $response = new TorrentPieceStatesResponse($pieceStates, $validHash, [], []);

        $validation = $response->validate();
        $this->assertTrue($validation->isValid());
        $this->assertEmpty($validation->getErrors());
    }
}