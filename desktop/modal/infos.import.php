<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
    echo '<div class="col-sm-12">';
        echo '<div class="col-sm-10">';
            echo '<input class="form-control" placeholder="{{Rechercher}}" style="margin-bottom:4px;" id="in_searchCmds" onkeyup="filterCmd()"/>';
        echo '</div>';
    echo '<a class="btn btn-success pull-right" id="bt_import" style="margin-bottom:4px;"><i class="fa fa-plus-circle"></i> {{Importer}}</a>';
    echo '</div>';

  echo '<div id="cmdsDisplay">';
  $cmds = cmd::all();
  foreach($cmds as $cmd)
  {
    if ($cmd->getType() == 'info')
    {
        $name = '#'.$cmd->getHumanName().'#';
        $isHistorized = $cmd->getIsHistorized();
        if ($isHistorized == 1) $historized = 'Historisée';
        else $historized = 'NON Historisée!';
        
        $div = '<div class="log col-sm-12" style="display:;padding-top:5px">';
        $div .= '<div class="form-group">';
        $div .= '<input type="checkbox" id="isEnable" class="expressionAttr col-sm-1" data-l1key="options" title="{{Cocher pour importer la commande}}" />';
        $div .= '<div class="col-sm-9">';
        $div .= '<input type="text" class="form-control" id="cmdName"  value="'.$name.'" readonly />';
        $div .= '</div>';
        $div .= '<label>'.$historized.'</label>';

        $div .= '</div>';
        $div .= '</div>';
        echo $div;
    }
  }
  echo '</div>';

?>

<script>
function filterCmd() {
    input = document.getElementById('in_searchCmds');
    filter = input.value.toUpperCase();
  
    $('#cmdsDisplay .log').each(function ()
    {
        data = $(this).find("#cmdName").val()
        if (data.toUpperCase().indexOf(filter) > -1)
        {
            $(this)[0].style.display = "";
        } else {
            $(this)[0].style.display = "none";
        }

    });
    
}
</script>
  

<?php include_file('desktop', 'jeelog', 'js', 'jeelog');?>
