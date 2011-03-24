<?php
$node = $variables['node'];
$analysis = $variables['node']->analysis;
$unigene = $node->analysis->tripal_analysis_unigene;

?>
<div id="tripal_analysis_unigene-base-box" class="tripal_analysis_unigene-info-box tripal-info-box">
  <div class="tripal_analysis_unigene-info-box-title tripal-info-box-title">Unigene Details</div>
  <div class="tripal_analysis_unigene-info-box-desc tripal-info-box-desc"></div>
   <table id="tripal_analysis_unigene-table-base" class="tripal_analysis_unigene-table tripal-table tripal-table-vert">
      <tr class="tripal_analysis_unigene-table-odd-row tripal-table-even-row">
        <th>Analysis Name</th>
        <td><?php print $analysis->name; ?></td>
      </tr>
      <tr class="tripal_analysis_unigene-table-odd-row tripal-table-odd-row">
        <th nowrap>Unigene Name</th>
        <td><?php print $unigene->unigene_name; ?></td>
      </tr>
      <tr class="tripal_analysis_unigene-table-odd-row tripal-table-even-row">
        <th nowrap>Software</th>
        <td><?php 
          print $analysis->program; 
          if($analysis->programversion){
             print " (" . $analysis->programversion . ")"; 
          }
          if($analysis->algorithm){
             print ". " . $analysis->algorithm; 
          }
          ?>
        </td>
      </tr>
      <tr class="tripal_analysis_unigene-table-odd-row tripal-table-odd-row">
        <th nowrap>Source</th>
        <td><?php 
          if($analysis->sourceuri){
             print "<a href=\"$analysis->sourceuri\">$analysis->sourcename</a>"; 
          } else {
             print $analysis->sourcename; 
          }
          if($analysis->sourceversion){
             print " (" . $analysis->sourceversion . ")"; 
          }
          ?>
          </td>
      </tr>
      <tr class="tripal_analysis_unigene-table-odd-row tripal-table-even-row">
        <th nowrap>Date constructed</th>
        <td><?php print preg_replace("/^(\d+-\d+-\d+) .*/","$1",$analysis->timeexecuted); ?></td>
      </tr>
      <tr class="tripal_analysis_unigene-table-odd-row tripal-table-odd-row">
        <th nowrap>Description</th>
        <td><?php print $analysis->description; ?></td>
      </tr> 
      <tr class="tripal_analysis_unigene-table-even-row tripal-table-odd-row">
        <th nowrap>Stats</th>
        <td>
             <?php if($unigene->num_reads){print "Number of reads: $unigene->num_reads<br>";} ?>
             <?php if($unigene->num_clusters){print "Number of clusters: $unigene->num_clusters<br>";} ?>
             <?php if($unigene->num_contigs){print "Number of contigs: $unigene->num_contigs<br>";} ?>
             <?php if($unigene->num_singlets){print "Number of singlets: $unigene->num_singlets<br>";} ?>
        </td>
      </tr>  
      <tr class="tripal_analysis_unigene-table-odd-row tripal-table-odd-row">
        <th>Organisms</th>
        <td><?php foreach($unigene->organisms as $organism){
               if($organism->nid){
                  print "<i><a href=\"".url("node/$organism->nid")."\">$organism->genus $organism->species</i></a><br>";
               } else {
                  print "<i>$organism->genus $organism->species</i><br>";
               }
            }
            ?>
        </td>
      </tr>       	                                
   </table>   
</div>
