<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Enum;

/**
 * 搜索分类枚举
 *
 * 定义搜索插件的分类
 */
enum SearchCategory: string
{
    case ALL = 'all';
    case ANIME = 'anime';
    case BOOKS = 'books';
    case GAMES = 'games';
    case MOVIES = 'movies';
    case MUSIC = 'music';
    case SOFTWARE = 'software';
    case TV = 'tv';
    case OTHER = 'other';

    /**
     * 获取搜索分类的显示名称
     *
     * @return string 显示名称
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::ALL => '全部',
            self::ANIME => '动漫',
            self::BOOKS => '书籍',
            self::GAMES => '游戏',
            self::MOVIES => '电影',
            self::MUSIC => '音乐',
            self::SOFTWARE => '软件',
            self::TV => '电视剧',
            self::OTHER => '其他',
        };
    }

    /**
     * 获取搜索分类的描述
     *
     * @return string 描述
     */
    public function getDescription(): string
    {
        return match($this) {
            self::ALL => '搜索所有分类的内容',
            self::ANIME => '搜索动漫和动画内容',
            self::BOOKS => '搜索电子书和文档',
            self::GAMES => '搜索游戏和游戏相关内容',
            self::MOVIES => '搜索电影和影片',
            self::MUSIC => '搜索音乐和音频文件',
            self::SOFTWARE => '搜索软件和应用程序',
            self::TV => '搜索电视剧和电视节目',
            self::OTHER => '搜索其他类型的内容',
        };
    }

    /**
     * 获取搜索分类的图标
     *
     * @return string 图标
     */
    public function getIcon(): string
    {
        return match($this) {
            self::ALL => '🔍',
            self::ANIME => '🎌',
            self::BOOKS => '📚',
            self::GAMES => '🎮',
            self::MOVIES => '🎬',
            self::MUSIC => '🎵',
            self::SOFTWARE => '💻',
            self::TV => '📺',
            self::OTHER => '📦',
        };
    }

    /**
     * 获取搜索分类的常用文件扩展名
     *
     * @return array<string> 常用文件扩展名
     */
    public function getCommonExtensions(): array
    {
        return match($this) {
            self::ALL => [],
            self::ANIME => ['mp4', 'mkv', 'avi', 'mov'],
            self::BOOKS => ['pdf', 'epub', 'mobi', 'azw3', 'djvu'],
            self::GAMES => ['iso', 'rar', 'zip', '7z', 'exe'],
            self::MOVIES => ['mp4', 'mkv', 'avi', 'mov', 'wmv'],
            self::MUSIC => ['mp3', 'flac', 'wav', 'aac', 'ogg'],
            self::SOFTWARE => ['iso', 'dmg', 'pkg', 'deb', 'rpm', 'exe'],
            self::TV => ['mp4', 'mkv', 'avi', 'mov', 'wmv'],
            self::OTHER => [],
        };
    }

    /**
     * 从字符串创建搜索分类枚举
     *
     * @param string $category 搜索分类字符串
     * @return self 搜索分类枚举
     */
    public static function fromString(string $category): self
    {
        try {
            return self::from($category);
        } catch (\ValueError $e) {
            return self::ALL;
        }
    }

    /**
     * 获取所有搜索分类
     *
     * @return array<self> 所有搜索分类
     */
    public static function getAllCategories(): array
    {
        return self::cases();
    }

    /**
     * 获取常用搜索分类
     *
     * @return array<self> 常用搜索分类
     */
    public static function getCommonCategories(): array
    {
        return [
            self::ALL,
            self::MOVIES,
            self::TV,
            self::MUSIC,
            self::GAMES,
            self::SOFTWARE,
        ];
    }

    /**
     * 获取媒体分类
     *
     * @return array<self> 媒体分类
     */
    public static function getMediaCategories(): array
    {
        return [
            self::MOVIES,
            self::TV,
            self::MUSIC,
            self::ANIME,
        ];
    }

    /**
     * 获取内容分类
     *
     * @return array<self> 内容分类
     */
    public static function getContentCategories(): array
    {
        return [
            self::BOOKS,
            self::GAMES,
            self::SOFTWARE,
            self::OTHER,
        ];
    }

    /**
     * 根据文件扩展名推测分类
     *
     * @param string $extension 文件扩展名
     * @return self 推测的分类
     */
    public static function guessFromExtension(string $extension): self
    {
        $extension = strtolower(ltrim($extension, '.'));

        $categories = [
            'mp4' => self::MOVIES,
            'mkv' => self::MOVIES,
            'avi' => self::MOVIES,
            'mov' => self::MOVIES,
            'mp3' => self::MUSIC,
            'flac' => self::MUSIC,
            'wav' => self::MUSIC,
            'pdf' => self::BOOKS,
            'epub' => self::BOOKS,
            'iso' => self::SOFTWARE,
            'exe' => self::SOFTWARE,
        ];

        return $categories[$extension] ?? self::OTHER;
    }
}