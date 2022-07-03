<?php

namespace Jericho;
class FileSystem
{
    use FileSystemHelper;

    private $_sep = DIRECTORY_SEPARATOR;
    private $_pathinfo = [];
    private $_planform = "";

    protected function __construct()
    {
    }

    /**
     * 初始化
     * @param string $path
     * @param FileSystem|null $fs
     * @return FileSystem
     */
    public static function init(string $path, FileSystem $fs = null)
    {
        $instance = $fs ?: new self;
        $instance->_planform = DIRECTORY_SEPARATOR === "/" ? "Unix" : "Windows";
        $instance->_pathinfo = pathinfo($instance->formatStr($path));
        return $instance;
    }

    /**
     * 格式化字符串
     * @param string $path
     * @return string
     */
    final public function formatStr(string $path): string
    {
        if ($this->_planform === "Windows") $path = str_replace("/", "\\", $path);
        if ($this->_planform === "Unix") $path = str_replace("\\", "/", $path);
        return $path;
    }

    /**
     * 设置路径
     * @param string $path
     * @return $this
     */
    final public function setPath(string $path): self
    {
        $this->_pathinfo = $this->pathinfo($this->formatStr($path));
        return $this;
    }

    /**
     * 打印函数
     * @return string
     */
    final public function __toString(): string
    {
        return $this->current();
    }

    /**
     * 获取当前完整路径
     * @return string
     */
    final public function current(): string
    {
        return "{$this->_pathinfo['dirname']}{$this->_sep}{$this->_pathinfo['basename']}";
    }

    /**
     * 获取pathinfo
     * @return array
     */
    final public function getPathinfo(): array
    {
        return $this->_pathinfo;
    }

    /**
     * 设置pathinfo
     * @param string $filepath
     * @return array
     */
    final public function pathinfo(string $filepath): array
    {
        $path_parts = array();
        $path_parts ['dirname'] = rtrim(substr($filepath, 0, strrpos($filepath, DIRECTORY_SEPARATOR)), DIRECTORY_SEPARATOR);
        $path_parts ['basename'] = ltrim(substr($filepath, strrpos($filepath, DIRECTORY_SEPARATOR)), DIRECTORY_SEPARATOR);
        $path_parts ['extension'] = substr(strrchr($filepath, '.'), 1);
        $path_parts ['filename'] = ltrim(substr($path_parts ['basename'], 0, strrpos($path_parts ['basename'], '.')), DIRECTORY_SEPARATOR);
        return $path_parts;
    }

    /**
     * 添加目录
     * @param string $path
     * @return $this
     * @throws \Exception
     */
    final public function join(string $path): self
    {
        $this->setPath($this->current() . "{$this->_sep}{$path}");
        return $this;
    }

    /**
     * 添加多级目录
     * @param array $paths
     * @return $this
     * @throws \Exception
     */
    final public function joins(array $paths): self
    {
        foreach ($paths as $path) $this->join($path);
        return $this;
    }

    /**
     * 退回到上一级
     * @return $this
     */
    final public function prev(): self
    {
        self::init(dirname($this->current()), $this);
        return $this;
    }

    /**
     * 保存到文件
     * @param string $content
     * @return int
     */
    final public function write(string $content): int
    {
        return file_put_contents($this->current(), $content);
    }

    /**
     * 从文件读取
     * @return string
     */
    final public function read(): string
    {
        return file_get_contents($this->current());
    }

    /**
     * 保存到json文件
     * @param array $obj
     * @param int $opt
     * @return int
     */
    final public function toJson($obj = [], int $opt = 256): int
    {
        return file_put_contents($this->current(), json_encode($obj, $opt));
    }

    /**
     * 读取json文件
     * @param bool $is_array
     * @return mixed
     */
    final public function fromJson(bool $is_array = true)
    {
        $ret = [];
        if (is_file($this->current())) $ret = json_decode(file_get_contents($this->current()), $is_array);
        return $is_array ? (array)$ret : $ret;
    }

    /**
     * 创建多级目录
     * @param string|null $dir
     * @return bool
     */
    final public function makeDir(string $dir = null)
    {
        if ($dir === null) $dir = $this->current();
        return is_dir($dir) or $this->makeDir(dirname($dir)) and mkdir($dir);
    }

    /**
     * 创建多目录
     * @param array $dirs
     * @return int
     * @throws \Exception
     */
    final public function makeDirs(array $dirs): int
    {
        $ret = 0;
        foreach ($dirs as $dir) $ret += intval(FileSystem::init($this->current())->join($dir)->makeDir());
        return $ret;
    }

    /**
     * 获取文件列表
     * @param string|null $preg
     * @return array
     */
    final public function getFiles(string $preg = null): array
    {
        return $this->filter(
            scandir($this->current()),
            function ($file) use ($preg) {
                if (is_file(FileSystem::init($this->current())->join($file)->current()))
                    return $preg !== null ? preg_match($preg, $file) ? $file : null : $file;
                return null;
            }
        );
    }

    /**
     * 获取目录列表
     * @param string|null $preg
     * @return array
     */
    final public function getDirs(string $preg = null): array
    {
        return $this->filter(
            scandir($this->current()),
            function ($dir) use ($preg) {
                if ($dir !== '.' and $dir !== '..' and is_dir(FileSystem::init($this->current())->join($dir)->current()))
                    return $preg !== null ? preg_match($preg, $dir) ? $dir : null : $dir;
                return null;
            }
        );
    }

    /**
     * 删除文件
     * @return bool
     * @throws \Exception
     */
    final public function deleteFile(): bool
    {
        if (!is_file($this->current())) throw new \Exception('目标文件不存在');
        return unlink($this->current());
    }

    /**
     * 修改名称
     * @param string $new
     * @return bool
     * @throws \Exception
     */
    final public function rename(string $new): bool
    {
        $old_filename = $this->current();
        $new_filename = $this->prev()->join($new)->current();
        $ret = rename($old_filename, $new_filename);
        $this->setPath($new_filename);
        return $ret;
    }

}

trait FileSystemHelper
{
    /**
     * 过滤器
     * @param $obj
     * @param \Closure|null $closure
     * @return array
     */
    final public function filter($obj, \Closure $closure = null)
    {
        $new = [];
        foreach ($obj as $key => &$value) {
            $ret = $closure($value);
            if ($ret === null) continue;
            $new[$key] = $ret;
        }
        return $new;
    }
}
