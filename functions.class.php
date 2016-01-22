<?php

class ProcessClass {
    public  $processed;
    public  $when;
    private $config;
    
    public function __construct() {
        if(file_exists('config.ini')) {
            $this->config = parse_ini_file('config.ini');
        } else {
            die('Cant open config.ini');
        }
        
        $this->when =(isset($_GET['w']))?$_GET['w']:"null";
        
        $this->processed['shots']=$this->ShotsProcess();
        $this->processed['log']=$this->LogProcess();
        
        $this->LinkActivate($this->when);
    }
    
    public function ShotsProcess() {
        $dir = $this->config['ss_dir'];
        $count=0;
        // Getting the directory list
        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $timestamp = filemtime($dir.'/'.$entry);

                    if ($this->when=="today") {
                        $terms = date('Ymd') == date('Ymd', $timestamp);
                    } elseif ($this->when=="hour") {
                        $terms = strtotime('-1 hour') < $timestamp;
                    } elseif($this->when=="all") { 
                        $terms = true; 
                    } else { $terms = false; }
                    if ($terms) {
                        $retval[$count]['name'] = $entry;
                        $retval[$count]['path'] = str_replace("#", "%23", $entry);
                        $retval[$count]['date'] = $timestamp;
                        $retval[$count]['size'] = filesize($dir.'/'.$entry);
                    }
                }
                $count++;
            }
            closedir($handle);
        }
        if (!empty($retval)) {
            // Sort by time descending
            foreach ($retval as $key => $part) {
                $sort[$key] = $part['date'];
            }
            array_multisort($sort, SORT_DESC, $retval);

            // Convert to writeable html
            $out = "<ul class='thumbs'>";
            foreach ($retval as $a) {
                $out .= '<li>
                <img src="'.$this->config['ss_dir'].'/'.$a['path'].'"  class="img-thumbnail">
                <br/><b>'.substr($a['name'],0,-8).'</b> #'.substr($a['name'],-8,-4).' @ '.date("Y.m.d. H:i:s", $a['date']).' | '.round($a['size']/1024).'kB</li>';
            }
            $out .= "</ul>";
        } else { $out = "No data here"; }
        
        return $out;
    }
    
    public function LogProcess() {
        $file = file_get_contents($this->config['log_file']);

        return $file;
    }
    
    public function LinkActivate($w=null) {
        if ($w=="hour") { $this->processed['active_hour'] = "active";}
        if ($w=="today") { $this->processed['active_today'] = "active";}
        if ($w=="all") { $this->processed['active_all'] = "active";}
    }
    
    public function render($file) {
        $output = file_get_contents($file);
        preg_match_all("/(?<=\{{ )(.*?)(?=\ }})/", $output, $values);

        foreach($values[0] as $v) {
            if (isset($this->config[$v])) {
                $output = str_replace("{{ $v }}", $this->config[$v], $output);
            } elseif (isset($this->processed[$v])) {
                $output = str_replace("{{ $v }}", $this->processed[$v], $output);
            }
        }
        return $output;
    }
}
