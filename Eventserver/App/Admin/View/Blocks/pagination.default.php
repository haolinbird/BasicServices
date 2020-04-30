<script>
    var paging;
    function pagingInitFunc() {
        if (document.readyState == 'complete') {
            paging = {
                page_no : document.getElementsByName('page_no'),
                frm : document.getElementById('query_form'),
                submitForm : function(pageNo) {
                    if(pageNo){
                        for (var i = 0; i < paging.page_no.length; i++) {
                            paging.page_no[i].value = pageNo;
                        }
                    }
                    this.setValues();
                    if (this.frm)
                        this.frm.submit();
                }
                ,
                setValues: function()
                {
                    var rows_per_page = document.getElementsByName('rows_per_page');
                    var id_rows_per_page = document.getElementById('rows_per_page');
                    if(id_rows_per_page){
                        if(rows_per_page.length < 1)
                        {
                            var new_elem = document.createElement('input');
                            new_elem.setAttribute('name', 'rows_per_page');
                            new_elem.setAttribute('value', id_rows_per_page.value);
                            new_elem.setAttribute('type', 'hidden');
                            if(this.frm)
                            {
                                this.frm.appendChild(new_elem);
                            }
                        }
                        else
                        {
                            for(var i=0; i< rows_per_page.length; i++)
                            {
                                rows_per_page[i].setAttribute('value', id_rows_per_page.value);
                            }
                        }
                    }
                }
            };
            if(paging.frm)
            {
                paging.frm.onsubmit = function()
                {
                    paging.submitForm();
                    return false;
                }
            }
        }
    }

    if (document.addEventListener) {
        document.addEventListener('readystatechange', pagingInitFunc, true);
    } else if (document.attachEvent) {
        document.attachEvent('onreadystatechange', pagingInitFunc);
    }

</script>

<div class="pagination pagination-right">
    <?php
    if(isset($paging) && $paging->total_pages > 0):
    $pageSplit = floor($paging->max_display_page_nav_num/2);
    $lowerPage = $paging->page_no - $pageSplit;
    if($lowerPage <= 0)
    {
        $lowerPage = 1;
    }
    $upperPage = $lowerPage + $paging->max_display_page_nav_num - 1;
    if($upperPage > $paging->total_pages)$upperPage = $paging->total_pages;
    ?>
    <ul>
        <li>
            <a href="javascript:void(0);" onclick="paging.submitForm(1);">«</a>
        </li>
        <li>
            <a href="javascript:void(0);" onclick="paging.submitForm(<?php echo $paging -> prev_page; ?>);">‹</a>
        </li>
        <?php
        for($pageStart=$lowerPage;$pageStart<=$upperPage;$pageStart++):
        ?>
        <?php if($pageStart == $paging->page_no):?>
        <li class="current">
            <a href="javascript:void(0);"> <?php echo $pageStart; ?> </a>
        </li>
        <?php else: ?>
        <li>
            <a href="javascript:void(0);"  onclick="paging.submitForm(<?php echo $pageStart; ?>);"> <?php echo $pageStart; ?> </a>
        </li>
        <?php endif; ?>
        <?php endfor; ?>
        <li>
            <a href="javascript:void(0);" onclick="paging.submitForm(<?php echo $paging -> next_page; ?>);">›</a>
        </li>
        <li>
            <a href="javascript:void(0);" onclick="paging.submitForm(<?php echo $paging -> total_pages; ?>);">»</a>
        </li>
    </ul>
    <ul>
        <li>
            共<?php echo $paging->total;?>条记录 每页<input type="text" id="rows_per_page" style="margin-bottom:1px; width: 60px;" value="<?php echo $paging->rows_per_page;?>" />条
            <?php echo $paging -> page_no; ?>/<?php echo $paging -> total_pages; ?>页
            <input style="margin-bottom: 1px;width: 60px" id="page_no" type="text" name="page_no" value="<?php echo $paging -> page_no; ?>" maxlength="10" style="width:90px;" />
            <input style="margin-bottom: 1px;" type="button" value="GO" onclick="paging.submitForm($(this).prev().val());" />
        </li>
    </ul>
        <?php
    endif;
    ?>
    <div class="clearfix"></div>
</div>
