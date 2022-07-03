<?php

namespace App\Libraries\Super\Excel;

use Illuminate\Http\Request;
use App\Libraries\Super\HttpResponseHelper;

class ExcelReadHelper
{
    private static $_INS;  # 本类对象
    public $php_excel;  # Excel操作类对象
    private $_originRow = 2;  # 起始读取行数
    private $_finishRow = 0;  # 最大读取行数
    private $_allowMaxRow = 1500;   # 允许最大行数

    /**
     * ExcelReadHelper constructor.
     */
    final private function __construct()
    {
    }

    /**
     * 通过request生成新对象
     * @param Request $request
     * @param string $filename
     * @return ExcelReadHelper
     * @throws \PHPExcel_Reader_Exception
     */
    public static function NEW_FROM_REQUEST(Request $request, string $filename)
    {
        $self = new self();
        if (!$request->hasFile($filename)) throw new \Exception('上传文件失败');
        $inputFile = $request->file($filename);
        # 读取excel文件
        $inputFileType = \PHPExcel_IOFactory::identify($inputFile);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $self->php_excel = $objReader->load($inputFile);
        return $self;
    }

    /**
     * 通过storage生成新对象
     * @param string $filename
     * @return ExcelReadHelper
     * @throws \PHPExcel_Reader_Exception
     */
    final public static function NEW_FROM_STORAGE(string $filename)
    {
        $self = new self();
        # 读取excel文件
        $inputFileType = \PHPExcel_IOFactory::identify(storage_path($filename));
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $self->php_excel = $objReader->load($filename);
        return $self;
    }


    /**
     * @param Request $request
     * @param string $filename
     * @return ExcelReadHelper
     * @throws \Exception
     */
    final public static function INS(Request $request, string $filename): ExcelReadHelper
    {
        if (!self::$_INS) {
            self::$_INS = new self();
            if (!$request->hasFile($filename)) throw new \Exception('上传文件失败');
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
     * @throws \Exception
     */
    final public static function FROM_REQUEST(Request $request, string $filename): self
    {
        if (!self::$_INS) {
            self::$_INS = new self();
            if (!$request->hasFile($filename)) throw new \Exception('上传文件失败');
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
     * @throws \PHPExcel_Reader_Exception
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
     * @throws \PHPExcel_Reader_Exception
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
        $this->_originRow = $originRow > 0 ? $originRow : 1;
        return $this;
    }

    /**
     * 设置末尾行数
     * @param int $finishRow
     * @return ExcelReadHelper
     */
    final public function finishRow(int $finishRow): ExcelReadHelper
    {
        $this->_finishRow = $finishRow > 0 ?: 1;
        $this->_finishRow = $this->_finishRow <= $this->_originRow ?: 1;
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

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        if ($this->_originRow > $highestRow) $this->_originRow = intval($highestRow);
        if (($this->_finishRow == 0) || ($this->_finishRow > $highestRow)) $this->_finishRow = intval($highestRow);

        for ($i = $this->_originRow; $i <= $this->_finishRow; $i++) {
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

        if ($auto_close) $this->php_excel->disconnectWorksheets();

        return ['success' => $success, 'fail' => $fail];
    }

    /**
     * 根据索引获取Sheet
     * @param int $sheetIndex
     * @param \Closure|null $closure
     * @return array
     */
    final public function withSheetIndex(int $sheetIndex, \Closure $closure = null): array
    {
        $success = [];
        $fail = [];

        $this->php_excel->setActiveSheetIndex($sheetIndex);
        $sheet = $this->php_excel->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        if ($highestRow > $this->_allowMaxRow) {
            return [
                'status' => 421,
                'msg' => '数据行数太长，超过1500行，请缩短'
            ];
        }
        $highestColumn = $sheet->getHighestColumn();

        if ($this->_originRow > $highestRow) $this->_originRow = intval($highestRow);
        if (($this->_finishRow == 0) || ($this->_finishRow > $highestRow)) $this->_finishRow = intval($highestRow);

        for ($i = $this->_originRow; $i <= $this->_finishRow; $i++) {
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

        return [
            'status' => 200,
            'msg' => '成功',
            'success' => $success,
            'fail' => $fail
        ];
    }
}
