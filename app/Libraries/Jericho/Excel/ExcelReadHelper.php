<?php

namespace Jericho\Excel;

use Exception;
use Illuminate\Http\Request;
use PHPExcel_Reader_Exception;

class ExcelReadHelper
{
    private static $_INS;  # 本类对象
    public $php_excel;  # Excel操作类对象
    private $_origin_row = 2;  # 起始读取行数
    private $_finish_row = 0;  # 最大读取行数

    /**
     * ExcelReadHelper constructor.
     * @throws Exception
     */
    final public function __construct()
    {
    }

    /**
     * 通过request生成新对象
     * @param Request $request
     * @param string $filename
     * @return ExcelReadHelper
     * @throws PHPExcel_Reader_Exception
     */
    public static function NEW_FROM_REQUEST(Request $request, string $filename)
    {
        $self = new self();
        if (!$request->hasFile($filename)) throw new Exception('上传文件失败');
        $input_file = $request->file($filename);
        # 读取excel文件
        $inputFileType = \PHPExcel_IOFactory::identify($input_file);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $self->php_excel = $objReader->load($input_file);
        return $self;
    }

    /**
     * 通过storage生成新对象
     * @param string $filename
     * @return ExcelReadHelper
     * @throws PHPExcel_Reader_Exception
     */
    final public static function NEW_FROM_STORAGE(string $filename)
    {
        $self = new self();
        # 读取excel文件
        $inputFileType = \PHPExcel_IOFactory::identify($filename);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $self->php_excel = $objReader->load($filename);
        return $self;
    }


    /**
     * @param Request $request
     * @param string $filename
     * @return ExcelReadHelper
     * @throws Exception
     */
    final public static function INS(Request $request, string $filename): ExcelReadHelper
    {
        if (!self::$_INS) {
            self::$_INS = new self();
            if (!$request->hasFile($filename)) throw new Exception('上传文件失败');
            $inputFile = $request->file($filename);
            # 读取excel文件
            $inputFileType = \PHPExcel_IOFactory::identify($inputFile);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            self::$_INS->php_excel = null;
            self::$_INS->php_excel = $objReader->load($inputFile);
        }
        return self::$_INS;
    }

    /**
     * 通过上传文件
     * @param Request $request
     * @param string $filename
     * @return ExcelReadHelper
     * @throws Exception
     */
    final public static function FROM_REQUEST(Request $request, string $filename): self
    {
        if (!self::$_INS) {
            self::$_INS = new self();
            if (!$request->hasFile($filename)) throw new Exception('上传文件失败');
            $inputFile = $request->file($filename);
            # 读取excel文件
            $inputFileType = \PHPExcel_IOFactory::identify($inputFile);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            self::$_INS->php_excel = null;
            self::$_INS->php_excel = $objReader->load($inputFile);
        }
        return self::$_INS;
    }

    /**
     * 读取本地文件
     * @param string $filename
     * @return ExcelReadHelper
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws PHPExcel_Reader_Exception
     */
    public static function FROM_STORAGE(string $filename): self
    {
        if (!self::$_INS) {
            self::$_INS = new self();
            # 读取excel文件
            $inputFileType = \PHPExcel_IOFactory::identify($filename);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            self::$_INS->php_excel = null;
            self::$_INS->php_excel = $objReader->load($filename);
        }
        return self::$_INS;
    }

    /**
     * 直接读取文件
     * @param string $filename
     * @return ExcelReadHelper
     * @throws PHPExcel_Reader_Exception
     */
    final public function file(string $filename): ExcelReadHelper
    {
        # 读取excel文件
        $inputFileType = \PHPExcel_IOFactory::identify($filename);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $this->php_excel = $objReader->load($filename);
        return $this;
    }

    /**
     * 设置读取起始行数
     * @param int $originRow
     * @return ExcelReadHelper
     */
    final public function originRow(int $originRow): ExcelReadHelper
    {
        $this->_origin_row = $originRow > 0 ? $originRow : 1;
        return $this;
    }

    /**
     * 设置末尾行数
     * @param int $finishRow
     * @return ExcelReadHelper
     */
    final public function finishRow(int $finishRow): ExcelReadHelper
    {
        $this->_finish_row = $finishRow > 0 ?: 1;
        $this->_finish_row = $this->_finish_row <= $this->_origin_row ?: 1;
        return $this;
    }

    /**
     * 获取全部Excel内容
     * @return array
     */
    final public function all(): array
    {
        $total = [];

        foreach ($this->php_excel->getSheetNames() as $sheet_name) {
            $total[$sheet_name] = $this->withSheetName($sheet_name, null, false);
        }

        $this->php_excel->disconnectWorksheets();

        return $total;
    }

    /**
     * 根据Sheet名称读取内容
     * @param string $sheetName
     * @param \Closure|null $closure
     * @param bool $auto_close
     * @return array[]
     */
    final public function withSheetName(string $sheetName, \Closure $closure = null, bool $auto_close = true): array
    {
        $success = [];
        $fail = [];

        $sheet = $this->php_excel->getSheetByName($sheetName);

        $highest_row = $sheet->getHighestRow();
        $highest_column = $sheet->getHighestColumn();

        if ($this->_origin_row > $highest_row) $this->_origin_row = intval($highest_row);
        if (($this->_finish_row == 0) || ($this->_finish_row > $highest_row)) $this->_finish_row = intval($highest_row);

        for ($i = $this->_origin_row; $i <= $this->_finish_row; $i++) {
            $row = $sheet->rangeToArray('A' . $i . ':' . $highest_column . $i, NULL, TRUE, FALSE)[0];
            if ($closure) {
                $data = $closure($row);
                if ($data == null) {
                    $fail[] = $row;
                    continue;
                } else {
                    $success[] = $data;
                }
            } else {
                $success[] = $row;
            }
        }

        if ($auto_close) $this->php_excel->disconnectWorksheets();

        return ['success' => $success, 'fail' => $fail];
    }

    /**
     * 根据索引获取Sheet
     * @param int $sheetIndex
     * @param \Closure|null $closure
     * @return array
     * @throws \PHPExcel_Exception
     */
    final public function withSheetIndex(int $sheetIndex, \Closure $closure = null): array
    {
        $success = [];
        $fail = [];

        $this->php_excel->setActiveSheetIndex($sheetIndex);
        $sheet = $this->php_excel->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        if ($this->_origin_row > $highestRow) $this->_origin_row = intval($highestRow);
        if (($this->_finish_row == 0) || ($this->_finish_row > $highestRow)) $this->_finish_row = intval($highestRow);

        for ($i = $this->_origin_row; $i <= $this->_finish_row; $i++) {
            $row = $sheet->rangeToArray('A' . $i . ':' . $highestColumn . $i, NULL, TRUE, FALSE)[0];
            if ($closure) {
                $data = $closure($row);
                if ($data == null) {
                    $fail[] = $row;
                    continue;
                } else {
                    $success[] = $data;
                }
            } else {
                $success[] = $row;
            }
        }

        $this->php_excel->disconnectWorksheets();

        return ['success' => $success, 'fail' => $fail];
    }

    /**
     * Excel时间转日期时间
     * @param int $t
     * @return false|string
     */
    final public static function excelToDatetime(int $t)
    {
        return gmdate('Y-m-d', intval(($t - 25569) * 3600 * 24));
    }

    /**
     * Excel时间戳转Unix时间戳
     * @param int $t
     * @return int
     */
    final public static function excelToTimestamp(int $t)
    {
        return intval($t - 25569);
    }

    /**
     * 时间戳转Excel时间戳
     * @param int $t
     * @return int
     */
    final public static function timestampToExcel(int $t)
    {
        return $t + 25569;
    }
}
