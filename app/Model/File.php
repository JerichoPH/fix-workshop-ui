<?php

namespace App\Model;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use stdClass;

/**
 * Class File
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $identity_code 编号
 * @property string $filename 文件存储名
 * @property string $original_filename 原始文件名
 * @property string $original_extension 原始文件扩展名
 * @property string $size 尺寸
 * @property string $filesystem_config_name 文件配置名
 * @property string $type 文件类型
 * @property-read string $render 渲染
 * @property-read stdClass $out_put 对象
 */
class File extends Base
{
    protected $guarded = [];

    public function __toString()
    {
        return $this->attributes['filename'];
    }

    /**
     * @param $value
     * @return object
     */
    final public function getOutPutAttribute(): stdClass
    {
        $filename = $this->attributes['filename'];
        $filesystem_config_name = $this->attributes['filesystem_config_name'];
        $url = config("filesystems.disks.$filesystem_config_name.url");
        $root = config("filesystems.disks.$filesystem_config_name.root");
        $save_path = "$root/$filename";

        return (object)[
            "value" => $filename,
            "save_path" => $save_path,
            // "url" => Storage::url("$url/$filename"),
            "url"=>"{$url}/{$filename}",
            "is_exist" => file_exists($save_path),
        ];
    }

    /**
     * 渲染
     * @param string $class
     * @param string $style
     * @param string $onclick
     * @return string
     */
    final public function render(string $class = '', string $style = '', string $onclick = ''): string
    {
        $file_obj = $this->getOutPutAttribute();
        $file_type = Str::upper($this->attributes['type']);

        switch ($file_type) {
            case 'IMAGE':
                return '<img src="' . $file_obj->url . '" class="' . $class . '" style="' . $style . '" onclick="' . $onclick . '"/>';
            case 'FILE':
                return '<a href="' . $file_obj->url . '" class="' . $class . '" style="' . $style . '" onclick="' . $onclick . '" target="_blank"><i class="fa fa-download"></i></a>';
            case 'PDF':
            default:
                return '';
        }
    }

    /**
     * 替换文件
     * @param File $source_file
     * @param UploadedFile $file
     * @param string $prefix
     * @param string $store_as
     * @param string $filesystem_config_name
     * @param string $type
     * @param Closure|null $callback
     * @return Builder|Model
     * @throws Exception
     */
    public static function replaceOne(
        File $source_file,
        UploadedFile $file,
        string $prefix,
        string $store_as,
        string $filesystem_config_name,
        string $type,
        Closure $callback = null
    )
    {
        // 原文件路径
        $source_file_save_path = $source_file->out_put->save_path;

        $saved_file = self::storeOne($file, $store_as, $filesystem_config_name, $type);

        // 删除原文件
        if ($source_file->out_put->is_exist) {
            unlink($source_file_save_path);
            $source_file->delete();
        }

        if ($callback) $callback($saved_file);

        return $saved_file;
    }

    /**
     * 上传单个文件
     * @param UploadedFile $file
     * @param string $prefix
     * @param string $store_as
     * @param string $filesystem_config_name
     * @param string $type
     * @param Closure|null $callback
     * @return Builder|Model
     */
    final public static function storeOne(
        UploadedFile $file,
        string $store_as,
        string $filesystem_config_name,
        string $type,
        Closure $callback = null
    )
    {
        $original_filename = $file->getClientOriginalName();
        $original_extension = $file->getClientOriginalExtension();
        $size = $file->getSize();

        $file->storeAs($store_as, $original_filename);

        $saved_file = (new File)->createOne([
            'filename' => $original_filename,
            'original_filename' => $original_filename,
            'original_extension' => $original_extension,
            'size' => $size,
            'filesystem_config_name' => $filesystem_config_name,
            'type' => $type,
        ]);

        if ($callback) {
            $callback($saved_file);
        }

        return $saved_file;
    }

    /**
     * 上传多个文件
     * @param array $files
     * @param string $prefix
     * @param string $store_as
     * @param string $filesystem_config_name
     * @param string $type
     * @param Closure|null $callback
     * @return array
     */
    final public static function storeBatch(
        array $files,
        string $prefix,
        string $store_as,
        string $filesystem_config_name,
        string $type,
        Closure $callback = null
    ): array
    {
        $saved_files = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $saved_files[] = self::storeOne($file, $store_as, $filesystem_config_name, $type);
            }
        }

        if ($callback) {
            $callback($saved_files);
        }

        return $saved_files;
    }
}
