<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Unit\Torrent;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Request\Torrent\GetTorrentFilesRequest;

/**
 * GetTorrentFilesRequest单元测试
 */
class GetTorrentFilesRequestTest extends TestCase
{
    /**
     * 测试有效的哈希
     */
    public function testValidHash(): void
    {
        $request = GetTorrentFilesRequest::create('abcdef1234567890abcdef1234567890abcdef12');

        $validation = $request->validate();
        $this->assertTrue($validation->isValid());
        $this->assertEmpty($validation->getErrors());

        $this->assertEquals('/files', $request->getEndpoint());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('abcdef1234567890abcdef1234567890abcdef12', $request->getHash());
        $this->assertNull($request->getIndexes());
    }

    /**
     * 测试有效的哈希和索引
     */
    public function testValidHashWithIndexes(): void
    {
        $indexes = [0, 1, 2];
        $request = GetTorrentFilesRequest::create('abcdef1234567890abcdef1234567890abcdef12', $indexes);

        $validation = $request->validate();
        $this->assertTrue($validation->isValid());
        $this->assertEmpty($validation->getErrors());

        $this->assertEquals('/files', $request->getEndpoint());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('abcdef1234567890abcdef1234567890abcdef12', $request->getHash());
        $this->assertEquals($indexes, $request->getIndexes());
    }

    /**
     * 测试无效的空哈希
     */
    public function testEmptyHash(): void
    {
        $request = GetTorrentFilesRequest::create('');

        $validation = $request->validate();
        $this->assertFalse($validation->isValid());
        $errors = $validation->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('种子哈希不能为空', $errors[0]);
    }

    /**
     * 测试无效的哈希长度
     */
    public function testInvalidHashLength(): void
    {
        $request = GetTorrentFilesRequest::create('abc');

        $validation = $request->validate();
        $this->assertFalse($validation->isValid());
        $errors = $validation->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('哈希长度不能少于', $errors[0]);
    }

    /**
     * 测试过长的哈希
     */
    public function testTooLongHash(): void
    {
        $request = GetTorrentFilesRequest::create(str_repeat('a', 41));

        $validation = $request->validate();
        $this->assertFalse($validation->isValid());
        $errors = $validation->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('哈希长度不能超过', $errors[0]);
    }

    /**
     * 测试无效的哈希格式
     */
    public function testInvalidHashFormat(): void
    {
        $request = GetTorrentFilesRequest::create('gggggggggggggggggggggggggggggggggg');

        $validation = $request->validate();
        $this->assertFalse($validation->isValid());
        $errors = $validation->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('哈希格式无效', $errors[0]);
    }

    /**
     * 测试无效的索引数组
     */
    public function testInvalidIndexesNotArray(): void
    {
        $this->expectException(\TypeError::class);
        $request = GetTorrentFilesRequest::create('abcdef1234567890abcdef1234567890abcdef12', 'not_array');
    }

    /**
     * 测试无效的索引值
     */
    public function testInvalidIndexesNegative(): void
    {
        $request = GetTorrentFilesRequest::create('abcdef1234567890abcdef1234567890abcdef12', [-1]);

        $validation = $request->validate();
        $this->assertFalse($validation->isValid());
        $errors = $validation->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('文件索引必须是非负整数', $errors[0]);
    }

    /**
     * 测试toArray方法
     */
    public function testToArray(): void
    {
        $indexes = [0, 1, 2];
        $request = GetTorrentFilesRequest::create('abcdef1234567890abcdef1234567890abcdef1234567890', $indexes);

        $expected = [
            'hash' => 'abcdef1234567890abcdef1234567890abcdef1234567890',
            'indexes' => '0|1|2'
        ];

        $this->assertEquals($expected, $request->toArray());
    }

    /**
     * 测试toArray方法（无索引）
     */
    public function testToArrayWithoutIndexes(): void
    {
        $request = GetTorrentFilesRequest::create('abcdef1234567890abcdef1234567890abcdef1234567890');

        $expected = [
            'hash' => 'abcdef1234567890abcdef1234567890abcdef1234567890'
        ];

        $this->assertEquals($expected, $request->toArray());
    }

    /**
     * 测试getSummary方法
     */
    public function testGetSummary(): void
    {
        $indexes = [0, 1, 2];
        $request = GetTorrentFilesRequest::create('abcdef1234567890abcdef1234567890abcdef12', $indexes);

        $summary = $request->getSummary();

        $this->assertEquals('abcdef1234567890abcdef1234567890abcdef12', $summary['hash']);
        $this->assertEquals(40, $summary['hash_length']);
        $this->assertEquals(3, $summary['indexes_count']);
        $this->assertEquals('/files', $summary['endpoint']);
        $this->assertEquals('GET', $summary['method']);
    }
}