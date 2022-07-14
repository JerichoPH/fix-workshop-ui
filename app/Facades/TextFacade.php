<?php

namespace App\Facades;

use App\Services\TextService;
use Illuminate\Support\Facades\Facade;

/**
 * Class TextFacade
 * @package App\Facades
 * @method static hump2underline(string $str): string
 * @method static underline2hump(string $str): string
 * @method static sub($str, $start, $length): string
 * @method static len(string $s)
 * @method static toArray(string $s)
 * @method static def($val, $default = '')
 * @method static enSecret($data, $key): string
 * @method static deSecret($data, $key): string
 * @method static rand($TYPE = 'Admix', $LENGTH = 32): string
 * @method static enAesCbc(string $text, string $key = null, string $iv = null): string
 * @method static deAesCbc(string $text, string $key = null, string $iv = null): string
 * @method static enAesCbc2(string $text, string $key = null, string $iv = null): string
 * @method static deAesCbc2(string $text, string $key = null, string $iv = null): string
 * @method static toAscii($str): string
 * @method static fromAscii($ascii)
 * @method static to32($num): string
 * @method static from32($str)
 * @method static to64($num): string
 * @method static from64($str)
 * @method static strip($str, bool $clear_html = false): string
 * @method static checkSign(array $data, string $secretKey, string $sign): bool
 * @method static makeSign(array $data, string $secretKey): string
 * @method static checkSign2(array $data, string $secretKey, string $sign): array
 * @method static to36($num): string
 * @method static from36($char): int
 * @method static inc36($char): string
 * @method static joinWithNotEmpty(?string $limit, array $data): string
 * @method static toLong(?string $string, int $length = 0): string
 */
class TextFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TextService::class;
    }
}
