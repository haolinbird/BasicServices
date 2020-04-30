<?php
namespace Lib\Util;
/**
 * Utilities for views.
 * @author Su Chao<suchaoabc@163.com>
 */
class View{
    /**
     * generate a combination of safe  paging params
     * @param int $total total rows
     * @param int $rowsPerPage how many rows to show in a page
     * @param int $pageNo
     */
    public static function generatePagingParams($total,$rowsPerPage,$pageNo)
    {
        $viewCfg = Sys::getAppCfg('View');
        if(empty($rowsPerPage))
        {
            $rowsPerPage = $viewCfg::DEFAULT_ROWS_PER_PAGE;
        }
        if($rowsPerPage < 1)$rowsPerPage = 1;
        if($total < 0)$total = 0;
        $totalPages = ceil($total/$rowsPerPage);
        if($pageNo < 1) $pageNo = 1;
        if($pageNo > $totalPages) $pageNo = $totalPages;
        $nextPage = $pageNo+1;
        $prevPage = $pageNo-1;
        if($nextPage > $totalPages)$nextPage = $totalPages;
        if($prevPage < 1)$prevPage = 1;
        $offset = ($pageNo-1)*$rowsPerPage;
        $paging = array('total'=>$total,'rows_per_page'=>$rowsPerPage,'page_no'=>$pageNo,'total_pages'=> $totalPages,'next_page'=>$nextPage,'prev_page'=>$prevPage,'offset'=>$offset,'max_display_page_nav_num'=>$viewCfg::MAX_DISPLAY_PAGE_NAV_NUM);
        return (object) $paging;
    }
}