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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class jeelog extends eqLogic {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */
    public static function cron() {
        foreach (eqLogic::byType('jeelog', true) as $eqLogic) {
            $autorefresh = $eqLogic->getConfiguration('autorefresh');
            if ($autorefresh != '') {
                try {
                    $c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
                    if ($c->isDue()) {
                        //message::add('jeelog', 'autorefresh isDue'.$eqLogic->getHumanName());
                        $eqLogic->refresh();
                    }
                } catch (Exception $exc) {
                    log::add('jeelog', 'error', __('Expression cron non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $autorefresh);
                }
            }
        }
    }


    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {

      }
     */



    /*     * *********************Méthodes d'instance************************* */
    public function refresh() {
        foreach ($this->getCmd() as $cmd)
        {
            $cmd->execute();
        }
    }

    public function preInsert() {

    }

    public function postInsert() {

    }

    public function preSave() {

    }

    public function postSave() {
        $refresh = $this->getCmd(null, 'refresh');
        if (!is_object($refresh)) {
            $refresh = new jeelogCmd();
            $refresh->setLogicalId('refresh');
            $refresh->setIsVisible(1);
            $refresh->setName(__('Rafraichir', __FILE__));
        }
        $refresh->setType('action');
        $refresh->setSubType('other');
        $refresh->setEqLogic_id($this->getId());
        $refresh->save();
      
      //echo $this->getConfiguration();
    }

    public function preUpdate() {

    }

    public function postUpdate() {

    }

    public function preRemove() {

    }

    public function postRemove() {

    }

    public function toHtml($_version = 'dashboard')
    {
        $replace = $this->preToHtml($_version);
        if (!is_array($replace)) {
            return $replace;
        }
        $refresh = $this->getCmd(null, 'refresh');
        $replace['#refresh_id#'] = $refresh->getId();

        $data = $this->getConfiguration('data', 0);
        $replace['#jeelogData#'] = $data;
      
      	$version = $_version;
      
      	if ($_version == 'dplan') $version = 'dashboard';
      
      	if ($_version == 'dashboard')
        {
          $replace['#width#'] = $this->getConfiguration('dashboardWidth', 360).'px';
          $replace['#height#'] = $this->getConfiguration('dashboardHeight', 144).'px';
        }
      	if ($_version == 'dview')
        {
          $replace['#width#'] = $this->getConfiguration('viewWidth', 450).'px';
          $replace['#height#'] = $this->getConfiguration('viewHeight', 560).'px';
        }
      
      	if ($_version == 'mview')
        {
          $replace['#width#'] = '97%; min-width:97%;';
          $replace['#height#'] = '500px';
        }
      
        $html = template_replace($replace, getTemplate('core', $version, 'jeelog', 'jeelog'));
        //cache::set('widgetHtml' . $_version . $this->getId(), $html, 0);
        return $html;
    }


    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class jeelogCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS*/
    public function dontRemoveCmd() {
        return true;
    }


    public function execute($_options = array()) {
        //message::add('jeelog', 'execute start');
        $eqLogic = $this->getEqLogic();
        $logs = $eqLogic->getConfiguration('logs', "Pas d'activité récente");

        $logDelta = $eqLogic->getConfiguration('loglasttime', 8);
        $logDelta = $logDelta * 3600;

        $timezone = 'Europe/Paris';
        $var = new DateTime('NOW', new DateTimeZone($timezone));
        $now = $var->format('Y-m-d H:i:s');
        $from = $var->sub(new DateInterval('PT'.$logDelta.'S'));
        $from = $from->format('Y-m-d H:i:s');

        $events = array(); //stock all events to sort them later by time

        try
        {
            foreach ($logs as $log)
            {
                $type = $log['type'];
                $argName = $log['argName']; //id of cmd info or scenario
                $isEnable = $log['isEnable'];
                $isInversed = $log['isInversed'];

                if ($type == 'Cmd' AND $isEnable)
                {
                    $cmdType = $log['CmdType'];
                    $displayName = $log['displayName'];
                    $events = $this->getEqActivity($argName, $displayName, $cmdType, $isInversed, $from, $now, $events);
                }

                if ($type == 'Scenar' AND $isEnable)
                {
                    $sc = scenario::byId($argName);
                    $displayName = $log['displayName'];
                    $events = $this->getScenarioActivity($sc, $displayName, $from, $events);
                }
            }
        }
        catch (Exception $e)
        {
            $msg = 'error: '.$e;
            message::add('JeeLog', 'execute error: '.$msg);
            return true;
        }

        //sort all of them by time:
        usort($events, array('jeelogCmd','date_compare'));
        $events = array_reverse($events);

        //create full report:
        $data = '';
        foreach ($events as $event)
        {
            $data .= $event[0].' | '.$event[1]."<br>";
        }

        //final log:
        $eqLogic->setConfiguration('data', $data);
        $eqLogic->save();

        $eqLogic->refreshWidget();
    }

    public function getEqActivity($cmdId, $name="", $type, $isInversed=false, $from, $now, $events)
    {
        if ($name == "") $name = cmd::cmdToHumanReadable($cmdId);
        $cmdId = str_replace('#', '', $cmdId);

        try
        {
            $result = history::all($cmdId, $from, $now);
            $prevDate = $from;
            $prevValue = null;
            for ($i = 0; $i < count($result); $i++)
            {
                $value = $result[$i]->getValue();
                $date = $result[$i]->getDatetime();

                if ($type=='Presence')
                {
                    if ($value >= 1) array_push($events, array($date, $type.' '.$name));
                }
                if ($type=='Valeur')
                {
                    array_push($events, array($date, $name.' | '.$value));
                }
              
              	if (strstr($type, ' | '))
                {
                  $var = explode(' | ', $type);
                  $states = array($var[0], $var[1]);
                  if ($isInversed) $states = array_reverse($states);
                  
                  if (($type=='Eteint | Allumé') || ($type=='Off | On'))
                  {
                      //Don't report duplicated values 1 or 2 sec after
                      if ((strtotime($date) <= strtotime($prevDate)+60) and ($prevValue == $value)) continue;
                  }
                  
                  if ($value >= 1) array_push($events, array($date, $name.' '.$states[1]));
                  else array_push($events, array($date, $name.' '.$states[0]));
                }

                $prevDate = $date;
                $prevValue = $value;
            }
            return $events;
        }
        catch (Exception $e)
        {
            message::add('JeeLog', 'getEqActivity Error: '.$e);
            return $events;
        }
    }

    public function getScenarioActivity($sc, $name="", $from, $events)
    {
        $scID = $sc->getId();
        if ($name == "") $name = $sc->getHumanName();

        try
        {
            //read scenario log:
            $logPath = dirname(__FILE__).'/../../../../log/scenarioLog/scenario'.$scID.'.log';
            if (!file_exists($logPath)) return $events;
            $file = fopen($logPath, 'r');
            $data = fread($file, filesize($logPath));
            fclose($file);

            $lines =  explode(PHP_EOL, $data);
            $lines = array_reverse($lines); //recent top

            //parse scenario log:
            $cmdCache = '';
            foreach($lines as $line)
            {
                if (stripos($line, 'Exécution de la commande') !== false)
                {
                    $var = explode(' avec', $line)[0];
                    $cmdCache .= "<br>".str_repeat('&nbsp;', 29).'->'.explode('de la commande ', $var)[1];
                }

                if (stripos($line, '------------------------------------') !== false) $cmdCache = '';


                //scenario has started:
                if (stripos($line, '[SCENARIO] Start :') !== false)
                {
                    $var = explode(']', $line)[0];
                    $date = ltrim($var, '[');
                    if (strtotime($date) < strtotime($from)) return $events; //too old!

                    $startedBy = '';
                    if (strstr($line, 'sur programmation')) $startedBy = ' | Programmation';

                    if (strstr($line, 'Lancement provoque par le scenario'))
                    {
                        $var = explode('par le scenario  : ', $line)[1];
                        $var = explode("'.", $var)[0];
                        $startedBy = ' Par scenario: '.$var;
                    }
                    else if (strstr($line, 'manuellement')) $startedBy = ' | Manuel';

                    if (strstr($line, 'en mode synchrone')) $startedBy = ' | Synchrone';

                    if (strstr($line, 'automatiquement sur evenement'))
                    {
                        $var = explode('evenement venant de : ', $line)[1];
                        $var = explode("'.", $var)[0];
                        $startedBy = ' Sur evenement: '.$var;
                    }
                    $data = $name.$startedBy;
                    if ($cmdCache != '') $data .= $cmdCache;
                    array_push($events, array($date, $data));
                }
            }
            return $events;
        }
        catch (Exception $e)
        {
            message::add('JeeLog', 'getScenarioActivity Error: '.$e);
            return $events;
        }
    }

    public function date_compare($a, $b)
    {
        $t1 = strtotime($a[0]);
        $t2 = strtotime($b[0]);
        return $t1 - $t2;
    }



    /*     * **********************Getteur Setteur*************************** */
}


