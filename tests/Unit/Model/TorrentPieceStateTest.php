<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Model\TorrentPieceState;
use PhpQbittorrent\Exception\ValidationException;

/**
 * TorrentPieceState 单元测试
 */
class TorrentPieceStateTest extends TestCase
{
    public function testValidPieceStateCreation(): void
    {
        $pieceState = new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 0);

        $this->assertEquals(TorrentPieceState::STATE_DOWNLOADED, $pieceState->getState());
        $this->assertEquals(0, $pieceState->getIndex());
        $this->assertTrue($pieceState->isDownloaded());
        $this->assertFalse($pieceState->isDownloading());
        $this->assertFalse($pieceState->isNotDownloaded());
    }

    public function testPieceStateFromState(): void
    {
        $pieceState = TorrentPieceState::fromState(TorrentPieceState::STATE_DOWNLOADING, 5);

        $this->assertEquals(TorrentPieceState::STATE_DOWNLOADING, $pieceState->getState());
        $this->assertEquals(5, $pieceState->getIndex());
        $this->assertTrue($pieceState->isDownloading());
        $this->assertFalse($pieceState->isDownloaded());
        $this->assertFalse($pieceState->isNotDownloaded());
    }

    public function testPieceStateFromArray(): void
    {
        $data = ['state' => TorrentPieceState::STATE_NOT_DOWNLOADED, 'index' => 10];
        $pieceState = TorrentPieceState::fromArray($data);

        $this->assertEquals(TorrentPieceState::STATE_NOT_DOWNLOADED, $pieceState->getState());
        $this->assertEquals(10, $pieceState->getIndex());
        $this->assertTrue($pieceState->isNotDownloaded());
    }

    public function testPieceStateFromJson(): void
    {
        $json = '{"state":2,"index":15}';
        $pieceState = TorrentPieceState::fromJson($json);

        $this->assertEquals(TorrentPieceState::STATE_DOWNLOADED, $pieceState->getState());
        $this->assertEquals(15, $pieceState->getIndex());
    }

    public function testStateMethods(): void
    {
        $notDownloaded = new TorrentPieceState(TorrentPieceState::STATE_NOT_DOWNLOADED, 0);
        $downloading = new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 1);
        $downloaded = new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 2);

        $this->assertTrue($notDownloaded->isNotDownloaded());
        $this->assertFalse($notDownloaded->isDownloading());
        $this->assertFalse($notDownloaded->isDownloaded());

        $this->assertFalse($downloading->isNotDownloaded());
        $this->assertTrue($downloading->isDownloading());
        $this->assertFalse($downloading->isDownloaded());

        $this->assertFalse($downloaded->isNotDownloaded());
        $this->assertFalse($downloaded->isDownloading());
        $this->assertTrue($downloaded->isDownloaded());
    }

    public function testGetStateDescription(): void
    {
        $notDownloaded = new TorrentPieceState(TorrentPieceState::STATE_NOT_DOWNLOADED, 0);
        $downloading = new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 1);
        $downloaded = new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 2);

        $this->assertEquals('Not downloaded', $notDownloaded->getStateDescription());
        $this->assertEquals('Now downloading', $downloading->getStateDescription());
        $this->assertEquals('Already downloaded', $downloaded->getStateDescription());
    }

    public function testSetters(): void
    {
        $pieceState = new TorrentPieceState(TorrentPieceState::STATE_NOT_DOWNLOADED, 0);

        $result = $pieceState->setState(TorrentPieceState::STATE_DOWNLOADED);
        $this->assertSame($pieceState, $result);
        $this->assertEquals(TorrentPieceState::STATE_DOWNLOADED, $pieceState->getState());

        $result = $pieceState->setIndex(25);
        $this->assertSame($pieceState, $result);
        $this->assertEquals(25, $pieceState->getIndex());
    }

    public function testToArray(): void
    {
        $pieceState = new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 10);
        $array = $pieceState->toArray();

        $expected = [
            'index' => 10,
            'state' => TorrentPieceState::STATE_DOWNLOADING,
            'state_description' => 'Now downloading',
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToJson(): void
    {
        $pieceState = new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 5);
        $json = $pieceState->toJson();

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $expected = [
            'index' => 5,
            'state' => TorrentPieceState::STATE_DOWNLOADED,
            'state_description' => 'Already downloaded',
        ];

        $this->assertEquals($expected, $decoded);
    }

    public function testToString(): void
    {
        $pieceState = new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 7);
        $string = (string)$pieceState;

        $this->assertEquals('Piece #7: Now downloading (1)', $string);
    }

    public function testEquals(): void
    {
        $piece1 = new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 5);
        $piece2 = new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 5);
        $piece3 = new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADING, 5);

        $this->assertTrue($piece1->equals($piece2));
        $this->assertFalse($piece1->equals($piece3));
        $this->assertFalse($piece2->equals($piece3));
    }

    public function testGetSummary(): void
    {
        $pieceState = new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, 12);
        $summary = $pieceState->getSummary();

        $this->assertEquals(12, $summary['index']);
        $this->assertEquals(TorrentPieceState::STATE_DOWNLOADED, $summary['state']);
        $this->assertEquals('Already downloaded', $summary['state_description']);
        $this->assertFalse($summary['is_not_downloaded']);
        $this->assertFalse($summary['is_downloading']);
        $this->assertTrue($summary['is_downloaded']);
    }

    public function testInvalidStateValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid state value: 3. Valid values are: 0, 1, 2');

        new TorrentPieceState(3, 0);
    }

    public function testNegativeIndexValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Piece index cannot be negative');

        new TorrentPieceState(TorrentPieceState::STATE_DOWNLOADED, -1);
    }

    public function testFromArrayMissingState(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required field: state');

        TorrentPieceState::fromArray(['index' => 0]);
    }

    public function testFromArrayMissingIndex(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required field: index');

        TorrentPieceState::fromArray(['state' => TorrentPieceState::STATE_DOWNLOADED]);
    }

    public function testFromArrayInvalidStateType(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Field "state" must be an integer');

        TorrentPieceState::fromArray(['state' => 'invalid', 'index' => 0]);
    }

    public function testFromArrayInvalidIndexType(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Field "index" must be an integer');

        TorrentPieceState::fromArray(['state' => TorrentPieceState::STATE_DOWNLOADED, 'index' => 'invalid']);
    }

    public function testFromJsonInvalidJson(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Invalid JSON:/');

        TorrentPieceState::fromJson('invalid json');
    }

    public function testFromJsonNonArrayJson(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('JSON must decode to an array');

        TorrentPieceState::fromJson('"string"');
    }

    public function testGetAllStates(): void
    {
        $states = TorrentPieceState::getAllStates();

        $this->assertIsArray($states);
        $this->assertArrayHasKey(0, $states);
        $this->assertArrayHasKey(1, $states);
        $this->assertArrayHasKey(2, $states);
        $this->assertEquals('Not downloaded', $states[0]);
        $this->assertEquals('Now downloading', $states[1]);
        $this->assertEquals('Already downloaded', $states[2]);
    }

    /**
     * @dataProvider stateProvider
     */
    public function testValidStates(int $state, string $description): void
    {
        $pieceState = new TorrentPieceState($state, 0);

        $this->assertEquals($state, $pieceState->getState());
        $this->assertEquals($description, $pieceState->getStateDescription());
    }

    public static function stateProvider(): array
    {
        return [
            'Not downloaded state' => [TorrentPieceState::STATE_NOT_DOWNLOADED, 'Not downloaded'],
            'Downloading state' => [TorrentPieceState::STATE_DOWNLOADING, 'Now downloading'],
            'Downloaded state' => [TorrentPieceState::STATE_DOWNLOADED, 'Already downloaded'],
        ];
    }
}