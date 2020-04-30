<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>
<style>
.job-data{border:1px solid gray;height:300px;overflow-y:scroll;border-radius:5px;}    
</style>
<div id="rightside">
    <div class="contentcontainer">
        <div class="headings altheading">
            <h3><?php echo isset($tubeStats['name']) ? "stats of tube [{$tubeStats['name']}]" : null ?></h3>
        </div>
        <div class="contentbox">            
            <p><a href="/QueueMonitor/Stats">返回列表</a></p>
            <table width="100%" class="table table-hover table-bordered">
                <thead>
                    <tr>                        
                        <?php                        
                        foreach ($fields as $field) echo "<th scope=\"col\">{$field}</th>\n";                        
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($tubeStats)) {
                        $data = array();
                        $data[] = "<tr>";
                        foreach ($fields as $key => $val)
                            $data[] = "<td title=\"{$val}\">".(isset($tubeStats[$key]) ? $tubeStats[$key] : 0)."</td>";
                        $data[] = '</tr>';
                        echo implode("\n", $data);
                    }
                    ?>
                </tbody>
            </table>
            <br />
            <?php
            $jobHtml = array();
            foreach ($jobStatus as $statusName => $job) {
                $jobHtml[] = "<h5>Next job in \"{$statusName}\" state</h5>";
                if (empty($job)) {
                    $jobHtml[] = "<p>empty</p>";
                    continue;
                }                
                $jobHtml[] = '<table width="100%" class="table"><thead><tr>';
                foreach ($jobAttris as $attr) $jobHtml[] = "<th scope=\"col\">{$attr}</th>";
                $jobHtml[] = '</thead></tr><tbody><tr>';
                foreach ($jobAttris as $attr) $jobHtml[] = isset($job[$attr]) ? "<td>{$job[$attr]}</td>" : '<td></td>';
                $jobHtml[] = '</tr></tbody></table>';
                if (isset($job['data'])) $jobHtml[] = "<strong>Data</strong>:<pre class=\"job-data\">" . var_export($job['data'], true)  . "</pre>";
                $jobHtml[] = "<br />";
            }
            echo implode("\n", $jobHtml);
            ?>            
        </div>
    </div>
</div>    
<?php require __DIR__ . DS . '../footer.php'; ?>
