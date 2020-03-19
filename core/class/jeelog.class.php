<?php

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class jeelog extends eqLogic {

    public static function logger($str = '', $level = 'debug')
    {
        if (is_array($str)) $str = json_encode($str);
        $function_name = debug_backtrace(false, 2)[1]['function'];
        $class_name = debug_backtrace(false, 2)[1]['class'];
        $msg = '['.$class_name.'] <'. $function_name .'> '.$str;
        log::add('jeelog', $level, $msg);
    }

    public static function cron() {
        foreach (eqLogic::byType('jeelog', true) as $eqLogic) {
            $autorefresh = $eqLogic->getConfiguration('autorefresh');
            if ($autorefresh != '') {
                try {
                    $c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
                    if ($c->isDue()) {
                        $eqLogic->refresh();
                    }
                } catch (Exception $exc) {
                    jeelog::logger('Expression cron non valide pour '.$eqLogic->getHumanName().':'.$autorefresh, 'error');
                }
            }
        }
    }

    /*     * *********************Méthodes d'instance************************* */
    public function refresh() {
        foreach ($this->getCmd() as $cmd)
        {
            jeelog::logger('cmd: '.json_encode($cmd));
            $cmd->execute();
        }
    }

    public function preInsert() {}
    public function postInsert() {}
    public function preSave() {}

    public function postSave()
    {
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
    }

    public function preUpdate() {}
    public function postUpdate() {}

    public function preRemove() {
        //delete data file:
        $eqId = $this->getId();
        $filePath = dirname(__FILE__).'/../../data/eq'.$eqId.'.txt';
        if (file_exists($filePath)) {
            unlink($filePath);
            jeelog::logger('Log file path deleted: '.$filePath);
        }
    }

    public function postRemove() {}

    public function toHtml($_version = 'dashboard')
    {
        $replace = $this->preToHtml($_version);
        if (!is_array($replace)) {
            return $replace;
        }
        $refresh = $this->getCmd(null, 'refresh');
        $replace['#refresh_id#'] = $refresh->getId();

        $version = jeedom::versionAlias($_version);

        //get data from file:
        $eqId = $this->getId();
        $filePath = dirname(__FILE__).'/../../data/eq'.$eqId.'.txt';
        if (file_exists($filePath)) {
            $data = file_get_contents($filePath);
            $data = str_replace("\n", "<br>", $data);
            $data = str_replace("<br><br>", "<br>", $data);
        } else {
            $data = "Pas de données récentes";
        }
        $replace['#jeelogData#'] = $data;

        $replace['#category#'] = $this->getPrimaryCategory();

        $html = template_replace($replace, getTemplate('core', $version, 'jeelog', 'jeelog'));
        return $html;
    }

}

class jeelogCmd extends cmd {
    public function dontRemoveCmd()
    {
        return true;
    }

    public function execute($_options = array())
    {
        $eqLogic = $this->getEqLogic();
        jeelog::logger('eqLogic: '.$eqLogic->getHumanName());
        $logs = $eqLogic->getConfiguration('logs', array());

        $logDelta = $eqLogic->getConfiguration('loglasttime', 8);
        $logDelta = $logDelta * 3600;

        $timeFormat = $eqLogic->getConfiguration('timeFormat', 'Y-m-d H:i:s');
        $scenarDetails = $eqLogic->getConfiguration('scenarDetails', 1);

        $timezone = 'Europe/Paris';
        $var = new DateTime('NOW', new DateTimeZone($timezone));
        $now = $var->format('Y-m-d H:i:s');
        $from = $var->sub(new DateInterval('PT'.$logDelta.'S'));
        $from = $from->format('Y-m-d H:i:s');

        jeelog::logger('from: '.$from.' now: '.$now.' timeFormat: '.$timeFormat);
        jeelog::logger('_options: '.json_encode($_options));

        $events = array(); //stock all events to sort them later by time
        $_isLogFile_ = False; //jeelog used to show a log file, do not treat it as array of events!
        try
        {
            foreach ($logs as $log)
            {
                $type = $log['type'];
                $argName = $log['argName']; //id of cmd info or scenario
                $isEnable = $log['isEnable'];

                if ($type == 'Cmd' AND $isEnable)
                {
                    $cmdType = $log['CmdType'];
                    $displayName = $log['displayName'];
                    $noRepeat = $log['noRepeat'];
                    $isInversed = $log['isInversed'];
                    jeelog::logger('Cmd -> displayName:'.$displayName);
                    $events = $this->getEqActivity($argName, $displayName, $cmdType, $isInversed, $noRepeat, $from, $now, $events);
                }

                if ($type == 'Scenar' AND $isEnable)
                {
                    $sc = scenario::byId($argName);
                    if (!is_object($sc)) continue;
                    $displayName = $log['displayName'];
                    jeelog::logger('Log scenario -> displayName:'.$displayName);
                    $events = $this->getScenarioActivity($sc, $displayName, $scenarDetails, $from, $events);
                }

                if ($type == 'Logfile' AND $isEnable)
                {
                    jeelog::logger('Log file -> argName: '.$argName);
                    $fileLines = $log['fileLines'];
                    $events = $this->getLogFile($argName, $fileLines);
                    $_isLogFile_ = True;
                }
            }
        }
        catch (Exception $e)
        {
            jeelog::logger(json_encode($e).' type: '.$type.' argName:'.$argName, 'error');
            return true;
        }

        jeelog::logger('events: '.json_encode($events));

        if ($_isLogFile_)
        {
            $data = '';
            foreach ($events as $line)
            {
                $thisData = filter_var($line, FILTER_SANITIZE_STRING);
                $data .= $thisData."\n";
            }
        }
        else
        {
            //sort all of them by time:
            usort($events, array('jeelogCmd','date_compare'));
            $events = array_reverse($events);

            //create full report:
            $data = '';
            foreach ($events as $event)
            {
                $date = $event[0];
                $newDate = date($timeFormat, strtotime($date));
                $thisData = $newDate.' | '.$event[1];
                $thisData = filter_var($thisData, FILTER_SANITIZE_STRING);
                $data .= $thisData."\n";
            }
        }


        //final log:
        if ($eqLogic->getConfiguration('showUpdate'))
        {
            $now = date($timeFormat);
            $data = $now.' | //Log mis à jour'."\n".$data;
        }

        //write to file:
        $dataPath = dirname(__FILE__).'/../../data/';
        if (!is_dir($dataPath))
        {
            jeelog::logger('mkdir data folder');
            if (mkdir($dataPath, 0777, true) === false )
            {
                jeelog::logger('Impossible de créer le dossier data', 'error');
            }
        }
        else
        {
            if ( !is_writable($dataPath))
            {
                jeelog::logger('Impossible d\'écrire dans le dossier data', 'error');
            }
        }

        $eqId = $eqLogic->getId();
        jeelog::logger('eqId: '.$eqId);
        try
        {
            $filePath = $dataPath.'eq'.$eqId.'.txt';
            jeelog::logger('Log file path: '.$filePath);
            $dataFile = fopen($filePath, 'w');
            fwrite($dataFile, $data);
            fclose($dataFile);
        }
        catch (Exception $e)
        {
            jeelog::logger('Impossible d\' écrire le fichier de données: '.json_encode($e), 'error');
            return false;
        }

        $eqLogic->refreshWidget();
        return true;
    }

    public function getEqActivity($cmdId, $name="", $type, $isInversed=false, $noRepeat=false, $from, $now, $events)
    {
        if ($name == "") $name = cmd::cmdToHumanReadable($cmdId);
        $cmdId = str_replace('#', '', $cmdId);
        $cmd = cmd::byId($cmdId);
        if (!is_object($cmd)) return $events;

        $_events = $events;

        try
        {
            jeelog::logger('getEqActivity -> name: '.$name);

            $isHistorized = $cmd->getIsHistorized();
            if ($isHistorized != 1)
            {
                jeelog::logger('getEqActivity -> Commande non historisée: '.$name, 'error');
                return $events;
            }

            $result = history::all($cmdId, $from, $now);
            jeelog::logger('getEqActivity -> result: '.json_encode($result));
            if (count($result) == 0 || !is_array($result)) return $_events;

            $prevDate = $from;
            $prevValue = null;
            for ($i = 0; $i < count($result); $i++)
            {
                $value = $result[$i]->getValue();
                if ($noRepeat && $value == $prevValue) continue;

                $date = $result[$i]->getDatetime();

                if ($type=='Présence')
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
                $_events = $events;
            }
            return $events;
        }
        catch (Exception $e)
        {
            jeelog::logger('getEqActivity: '.json_encode($e), 'error');
            return $_events;
        }
    }

    public function getScenarioActivity($sc, $name="", $details=true, $from, $events)
    {
        $scID = $sc->getId();
        if ($name == "") $name = $sc->getHumanName();

        $_events = $events;

        try
        {
            //read scenario log:
            $logPath = dirname(__FILE__).'/../../../../log/scenarioLog/scenario'.$scID.'.log';
            jeelog::logger('getScenarioActivity -> name: '.$name.' logPath: '.$logPath);
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
                if (stripos($line, 'Exécution de la commande') !== false AND $details)
                {
                    $var = explode(' avec', $line)[0];
                    $cmdCache .= "\n".str_repeat('&nbsp;', 29).'->'.explode('de la commande ', $var)[1];
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
                    $_events = $events;
                }

                // sous tache scenario  AT
                if (stripos($line, 'Lancement sous tâche') !== false)
                {
                    $var = explode(']', $line)[0];
                    $date = ltrim($var, '[');
                    if (strtotime($date) < strtotime($from)) return $events; //too old!

                    $data = $name.' | Programmation';
                    if ($cmdCache != '') $data .= $cmdCache;
                    array_push($events, array($date, $data));
                    $_events = $events;
                }
            }
            return $events;
        }
        catch (Exception $e)
        {
            jeelog::logger('getScenarioActivity: '.json_encode($e), 'error');
            return $_events;
        }
    }

    public function getLogFile($argName, $numLines=0)
    {
        $numLines = intval($numLines);
        $logFile = '../../log/'.$argName;
        $logFile = dirname(__FILE__).'/../../../../log/'.$argName;
        jeelog::logger('logFile: '.$logFile.' | numLines: '.$numLines);

        try
        {
            //read log file:
            $content = file_get_contents($logFile);
            $lines =  explode(PHP_EOL, $content);
            $lines = array_reverse($lines); //recent top

            //limit number of lines:
            if ($numLines > 0)
            {
                $lines = array_slice($lines, 0, $numLines);
            }
            return $lines;
        }
        catch (Exception $e)
        {
            jeelog::logger('getLogFile: '.json_encode($e), 'error');
            return [];
        }
    }

    public function date_compare($a, $b)
    {
        $t1 = strtotime($a[0]);
        $t2 = strtotime($b[0]);
        return $t1 - $t2;
    }
}


