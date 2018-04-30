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
  echo '<a class="btn btn-success pull-right" id="bt_import" style="margin-top: 5px;"><i class="fa fa-plus-circle"></i> {{Importer}}</a>';

  $cmds = cmd::all();
  foreach($cmds as $cmd)
  {
    $type = $cmd->getType();
    if ($cmd->getType() == 'info')
    {
    	$name = $cmd->getHumanName();
      	
      	$div = '<div class="log col-sm-12" style="padding-top:5px">';
      	$div .= '<div class="form-group">';
      	$div .= '<input type="checkbox" id="isEnable" class="expressionAttr col-sm-1" data-l1key="options" style="width:20px" title="{{Cocher pour importer la commande}}" />';
      	$div .= '<div class="col-sm-10">';
		$div .= '<input type="text" class="form-control" id="cmdName"  value="'.$name.'" readonly />';
      	$div .= '</div>';
      	$div .= '</div>';
      	$div .= '</div>';
      echo $div;
  	}
  }

?>
  
<?php include_file('desktop', 'jeelog', 'js', 'jeelog');?>
