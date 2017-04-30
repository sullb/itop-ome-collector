<?php

class OMEBrandCollector extends Collector
{
    protected $omedata;
 
    public function Prepare()
    {
        $bResult = parent::Prepare();
        
        $this->omedata['Dell']['name'] = 'Dell';
        $this->omedata['Dell']['primary_key'] = 'Dell';

        return $bResult;
    }
 
    public function Fetch()
    {
        if(count($this->omedata)>0) {
            $name = array_keys($this->omedata)[0];
            $data = $this->omedata[$name];
            unset($this->omedata[$name]);
			return $data;
        }
        return false;
    }
}
