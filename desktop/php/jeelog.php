<?php
if (!isConnect('admin')) {
  throw new Exception('{{401 - Accès non autorisé}}');
}

$plugin = plugin::byId('jeelog');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
  <div class="col-xs-12 eqLogicThumbnailDisplay" style="padding-left: 25px;">
    <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
        <div class="cursor eqLogicAction success" data-action="add">
          <i class="fas fa-plus-circle"></i>
          <br>
          <span>{{Ajouter}}</span>
      </div>
      <div class="cursor eqLogicAction" data-action="gotoPluginConf">
        <i class="fas fa-wrench"></i>
        <br>
        <span>{{Configuration}}</span>
      </div>
    </div>
    <legend><i class="fa fa-table"></i> {{Mes jeelogs}}</legend>
    <div class="eqLogicThumbnailContainer">
      <?php
        foreach ($eqLogics as $eqLogic) {
          $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
          echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
          echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
          echo '<br>';
          echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
          echo '</div>';
        }
      ?>
    </div>
  </div>

<div class="col-xs-12 eqLogic" style="padding-left: 25px;display: none;">
  <div class="input-group pull-right" style="display:inline-flex">
    <span class="input-group-btn">
      <a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}
      </a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fa fa-files-o"></i> {{Dupliquer}}
      </a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}
      </a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
    </span>
  </div>

  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
    <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
    <li role="presentation"><a href="#logtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Logs}}</a></li>
  </ul>

  <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">

  <div role="tabpanel" class="tab-pane active" id="eqlogictab">
    <br/>
    <form class="form-horizontal">
      <fieldset>
          <div class="form-group">
              <label class="col-sm-3 control-label">{{Nom de l'équipement jeelog}}</label>
              <div class="col-sm-3">
                  <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                  <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l\'équipement jeelog}}"/>
              </div>
          </div>
          <div class="form-group">
              <label class="col-sm-3 control-label" >{{Objet parent}}</label>
              <div class="col-sm-3">
                  <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                      <option value="">{{Aucun}}</option>
                      <?php
                        foreach (object::all() as $object) {
                         echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                        }
                      ?>
                 </select>
             </div>
         </div>
         <div class="form-group">
              <label class="col-sm-3 control-label">{{Catégorie}}</label>
              <div class="col-sm-9">
               <?php
                  foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                  echo '<label class="checkbox-inline">';
                  echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                  echo '</label>';
                  }
                ?>
             </div>
         </div>

        <div class="form-group">
          <label class="col-sm-3 control-label"></label>
          <div class="col-sm-9">
            <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
            <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
          </div>
        </div>

        <hr>
        <div class="form-group expertModeVisible">
          <label class="col-sm-2 control-label">{{Auto-actualisation (cron)}}</label>
          <div class="col-sm-2">
            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="autorefresh" placeholder="{{Auto-actualisation (cron)}}"/>
          </div>
          <div class="col-sm-1">
            <i class="fa fa-question-circle cursor floatright" id="bt_cronGenerator"></i>
          </div>
          <label class="col-sm-3 control-label">{{Afficher Mise à jour du log}}</label>
          <div class="col-sm-1">
           <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="showUpdate" checked/>
          </div>
          <label class="col-sm-2 control-label">{{Détails des scénarios}}</label>
          <div class="col-sm-1">
           <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="scenarDetails" checked/>
          </div>
        </div>

        <div class="form-group expertModeVisible">
          <label class="col-sm-2 control-label">{{Afficher (heures)}}</label>
          <div class="col-sm-2">
           <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="loglasttime" placeholder="8"/>
          </div>
          <label class="col-sm-2 control-label">{{Format de date (php)}}</label>
          <div class="col-sm-2">
           <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="timeFormat" placeholder="Y-m-d H:i:s"/>
          </div>
        </div>
        <hr>

        <div class="form-group expertModeVisible">
          <label class="col-sm-3 control-label">{{Design}}</label>
          <div class="col-sm-3">
           <label class="control-label">{{Fond (css)}}</label>
           <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="designBckColor" placeholder="rgba(128, 128, 128, 0.8)"/>
          </div>
          <div class="col-sm-3">
           <label class="control-label">{{Texte (css)}}</label>
           <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="designColor" placeholder="rgb(10, 10, 10)"/>
          </div>
        </div>

      </fieldset>
    </form>
  </div>

    <div role="tabpanel" class="tab-pane" id="logtab">
      <a class="btn btn-success pull-left" id="bt_importinfos" style="margin-top: 5px;" ><i class="fa fa-cog"></i> {{Import infos}}
      </a><a class="btn btn-success pull-right" id="bt_addLogfile" style="margin-top: 5px;"><i class="fa fa-plus-circle"></i> {{Ajouter Log}}
      </a><a class="btn btn-success pull-right" id="bt_addScenario" style="margin-top: 5px;"><i class="fa fa-plus-circle"></i> {{Ajouter Scenario}}
      </a><a class="btn btn-success pull-right" id="bt_addCmd" style="margin-top: 5px;"><i class="fa fa-plus-circle"></i> {{Ajouter Commande}}</a>
      <br/><br/>

      <div id="div_logs"></div>
    </div>
  </div>
</div>

<?php include_file('desktop', 'jeelog', 'js', 'jeelog');?>
<?php include_file('core', 'plugin.template', 'js');?>
