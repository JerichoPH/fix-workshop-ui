<?php

namespace App\Libraries\Super\Excel;

class ExcelWriteHelper
{
    /**
     * 提供下载
     * @param \Closure $closure
     * @param string $filename
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final public static function download(\Closure $closure, string $filename)
    {
        @ob_end_clean();
        ob_start();
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel = $closure($objPHPExcel);
        header('Content-Type: text/html; charset=utf-8;');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        $objPHPExcel->disconnectWorksheets();
        exit;
    }

    /**
     * 提供下载
     * @param \Closure $closure
     * @param string $filename
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final public static function download2005(\Closure $closure, string $filename)
    {
        ob_end_clean();
        ob_start();
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel = $closure($objPHPExcel);
        header('Content-Type: text/html; charset=utf-8;');
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename={$filename}.xls");
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        $objPHPExcel->disconnectWorksheets();
        exit;
    }

    /**
     * 保存到文件
     * @param \Closure $closure
     * @param string $filename
     * @param string $suffix
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final public static function save(\Closure $closure, string $filename, $suffix = 'xls')
    {
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel = $closure($objPHPExcel);
        $objWriter = null;
        if ($suffix == 'xls') {
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        }
        if ($suffix == 'xlsx') {
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        }
        $objWriter->save("{$filename}.{$suffix}");
        $objPHPExcel->disconnectWorksheets();
    }
}
