<?php

class ProcessClass {
    public  $processed;
    private $config;
    
    public function __construct() {
        if(file_exists('config.ini')) {
            $this->config = parse_ini_file('config.ini');
        } else {
            die('Cant open config.ini');
        }
        
        $this->processed['shots']=$this->ShotsProcess();
        $this->processed['log']=$this->LogProcess();
    }
    
    public function ShotsProcess() {
        $dir = $this->config['ss_dir'];
        $count=0;
        // Getting the directory list
        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $timestamp = filemtime($dir.'/'.$entry);
                    if (isset($_GET['w']) and $_GET['w']=="today") {
                        $terms = "return date('Ymd') == date('Ymd', $timestamp);";
                    } elseif(isset($_GET['w']) and $_GET['w']=="all") { $terms = "return 1==1;"; }
                    if (eval($terms)) {
                        $retval[$count]['name'] = $entry;
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
                <img src="'.$this->config['ss_dir'].'/'.$a['name'].'"  class="img-thumbnail">
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
    
    public function LinkActivate($w) {
        if ($w=="today") { $this->processed['active_today'] = "active";}
        if ($w=="all") { $this->processed['active_all'] = "active";}
    }
    
    public function render($file) {
        $output = file_get_contents($file);
        preg_match_all("/(?<=\{{ )(.*?)(?=\ }})/", $output, $values);
        $this->LinkActivate($_GET['w']);

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
