<?php

namespace Jericho\Excel;

use App\Exceptions\ExcelInException;
use Carbon\Carbon;
use Closure;
use Exception;
use PHPExcel;
use PHPExcel_Cell;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use PHPExcel_Style_Alignment;
use PHPExcel_Writer_Exception;

class ExcelWriteHelper
{
    public static $VERSION_5 = '5';
    public static $VERSION_2007 = '2007';
    public static $VERSION_CSV = 'csv';
    public static $VERSIONS = [
        '5' => 'xls',
        '2007' => 'xlsx',
        'csv' => 'csv',
    ];
    public static $WRITE_TYPES = [
        '5' => 'Excel5',
        '2007' => 'Excel2007',
        'csv' => 'CSV',
    ];

    /**
     * 十进制转Excel列
     * @param int $num
     * @return string
     */
    final public static function int2Excel(int $num)
    {
        $az = 26;
        $m = (int)($num % $az);
        $q = (int)($num / $az);
        $letter = chr(ord('A') + $m);
        if ($q > 0) {
            return self::int2Excel($q - 1) . $letter;
        }
        return $letter;
    }

    /**
     * Excel列转十进制
     * @param string $str
     * @return float|int
     */
    final public static function excel2Int(string $str)
    {
        $num = 0;
        $strArr = str_split($str, 1);
        $len = count($strArr);
        foreach ($strArr as $k => $v) {
            $num += ((ord($v) - ord('A') + 1) * pow(26, $len - $k - 1));
        }
        return $num - 1;
    }

    /**
     * 获取字体颜色
     * @param string $colorName
     * @return mixed
     */
    final public static function getFontColor(string $colorName)
    {
        $colors = [
            'red' => 'FF0000',
            'black' => '000000',
        ];
        $fontColor = new \PHPExcel_Style_Color();
        return $fontColor->setRGB($colors[$colorName] ?? '000000');
    }

    /**
     * 提供下载
     * @param Closure $closure
     * @param string $filename
     * @param string $version
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    final public static function download(Closure $closure, string $filename, string $version = '5')
    {
        $objPHPExcel = new PHPExcel();
        $objPHPExcel = $closure($objPHPExcel);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, self::$WRITE_TYPES[$version] ?? self::$VERSION_5);
        $filename = "{$filename}." . self::$VERSIONS[$version] ?? 'xls';
        @ob_end_clean();
        header('Content-Type: text/html; charset=utf-8;');
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename={$filename}");
        $objWriter->save('php://output');
        $objPHPExcel->disconnectWorksheets();
        exit;

        //导出execl
        //        header('pragma:public');
        //        header("Content-type:application/vnd.ms-excel;charset=utf-8;name={$filename}.xlsx");
        //        header("Content-Disposition:attachment;filename={$filename}.xlsx");//attachment新窗口打印inline本窗口打印
        //        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        //        $objWriter->save('php://output');
        //        exit;
    }

    /**
     * 提供下载
     * @param Closure $closure
     * @param string $filename
     * @throws \PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    final public static function download2007(Closure $closure, string $filename)
    {
        @ob_end_clean();
        ob_start();
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel = $closure($objPHPExcel);
        header('Content-Type: text/html; charset=utf-8;');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        $objPHPExcel->disconnectWorksheets();
        exit;
    }

    /**
     * 保存到文件
     * @param Closure $closure
     * @param string $filename
     * @param string $version
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    final public static function save(Closure $closure, string $filename, string $version = '5')
    {
        $objPHPExcel = new PHPExcel();
        $objPHPExcel = $closure($objPHPExcel);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, self::$WRITE_TYPES[$version] ?? self::$VERSION_5);
        $filename = "{$filename}." . self::$VERSIONS[$version] ?? 'xls';
        $objWriter->save($filename);
        $objPHPExcel->disconnectWorksheets();
    }

    /**
     * Excel时间转日期时间
     * @param int $t
     * @return false|string
     */
    final public static function excelToDatetime(int $t)
    {
        return gmdate('Y-m-d H:i:s', intval(($t - 25569) * 3600 * 24));
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

    /**
     * 获取Excel时间类型
     * @param $date
     * @return int|string
     * @throws ExcelInException
     */
    final public static function getExcelDate($date)
    {
        $formats = [
            'Y-m-d' => '/^(\d{4})-(\d{1,2})-(\d{1,2})$/i',
            'Y/m/d' => '/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/i',
            'Y年m月d日' => '/^(\d{4})年(\d{1,2})月(\d{1,2})日$/i',
            'Y.m.d' => '/^(\d{4}).(\d{1,2}).(\d{2})$/i',
            'Y-m' => '/^(\d{4})-(\d{1,2})$/i',
            'Y/m' => '/^(\d{4})\/(\d{1,2})$/i',
            'Y年m月' => '/^(\d{4})年(\d{1,2})月$/i',
            'Y.m' => '/^(\d{4}).(\d{1,2})$/i',
        ];

        if (is_numeric($date)) {
            try {
                return self::excelToDatetime($date);
            } catch (Exception $e) {
                throw new ExcelInException('日期格式不正确，请使用Excel的日期格式，只支持：YYYY-MM-DD；YYYY/MM/DD；YYYY年MM月DD日；YYYY.MM.DD；YYYY-MM；YYYY/MM；YYYY年MM月；YYYY.MM；格式');
            }
        } elseif (is_string($date)) {
            foreach ($formats as $format => $preg)
                if (preg_match($preg, $date)) return Carbon::createFromFormat($format, $date)->format('Y-m-d H:i:s');

            throw new ExcelInException('日期格式不正确，请使用Excel的日期格式，只支持：YYYY-MM-DD；YYYY/MM/DD；YYYY年MM月DD日；YYYY.MM.DD；YYYY-MM；YYYY/MM；YYYY年MM月；YYYY.MM；格式');
        } else {
            throw new ExcelInException('日期格式不正确，请使用Excel的日期格式，只支持：YYYY-MM-DD；YYYY/MM/DD；YYYY年MM月DD日；YYYY.MM.DD；YYYY-MM；YYYY/MM；YYYY年MM月；YYYY.MM；格式');
        }
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
     * 生成错误报告
     * @param int $row
     * @param string $name
     * @param string $value
     * @param string $errorMessage
     * @param string $color
     * @return array
     */
    final public static function makeErrorResult(int $row, string $name, string $value = null, string $errorMessage = '', string $color = 'red')
    {
        return ['row' => $row, 'name' => $name, 'value' => $value?:'', 'error_message' => $errorMessage, 'color' => $color];
    }

    /**
     * 设置标准单元格数据
     * @param string $context
     * @param string $color
     * @param int $width
     * @param string $alignment_horizontal
     * @param string $alignment_vertical
     * @return array
     */
    final public static function setStdCell(string $context = '', string $color = 'black', int $width = 20, string $alignment_horizontal = 'left', string $alignment_vertical = 'center'): array
    {
        return [
            'context' => $context,
            'color' => $color,
            'width' => $width,
            'alignment_horizontal' => $alignment_horizontal,
            'alignment_vertical' => $alignment_vertical,
        ];
    }

    /**
     * 写入单元格数据
     * @param $sheet
     * @param array $datum
     * @param int $col
     * @param int $row
     */
    final public static function writeStdCell(&$sheet, array $datum, int $col, int $row): void
    {
        $col_for_excel = ExcelWriteHelper::int2Excel($col);
        [
            'context' => $context,
            'color' => $color,
            'width' => $width,
            'alignment_horizontal' => $alignment_horizontal,
            'alignment_vertical' => $alignment_vertical,
        ] = $datum;
        $sheet->setCellValueExplicit("$col_for_excel$row", $context);
        $sheet->getStyle("$col_for_excel$row")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
        $sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
        $sheet->getStyle("$col_for_excel$row")->getAlignment()->setHorizontal($alignment_horizontal);
        $sheet->getStyle("$col_for_excel$row")->getAlignment()->setVertical($alignment_vertical);
    }

    /**
     * 写入整行数据
     * @param $sheet
     * @param array $data
     * @param $row
     */
    final public static function writeStdRow(&$sheet, array $data, int $row)
    {
        foreach ($data as $col => $datum) {
            ExcelWriteHelper::writeStdCell($sheet, $datum, $col, $row);
        }
    }

    /**
     * 写入多行数据
     * @param $sheet
     * @param array $data
     * @param int $start_row
     */
    final public static function writeStdRows(&$sheet, array $data, int $start_row)
    {
        foreach ($data as $row_number => $datum) {
            $row = $start_row + $row_number;
            self::writeStdRow($sheet, $datum, $row);
        }
    }

    /**
     * 合并列
     * @param $sheet
     * @param string $value
     * @param string $original
     * @param string $finish
     * @param int $row
     * @param string $color
     * @param string $alignment_horizontal
     * @param string $alignment_vertical
     */
    final public static function comboColumns(&$sheet, string $value, string $original, string $finish, int $row = 1, string $color = '', string $alignment_horizontal = 'left', string $alignment_vertical = 'center')
    {
        $sheet->setCellValueExplicit("$original$row", $value);
        $sheet->getStyle("$original$row")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
        $sheet->mergeCells("$original$row:$finish$row");
        $sheet->getStyle("$original$row:$finish$row")->getAlignment()->setHorizontal($alignment_horizontal);
        $sheet->getStyle("$original$row:$finish$row")->getAlignment()->setVertical($alignment_vertical);
    }

}
