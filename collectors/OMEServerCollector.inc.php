<?php

class OMEServerCollector extends Collector
{
    protected $omedata;
    protected $luServerPK;
	protected $oModelLookup;
    protected $oBrandLookup;
 
    public function Prepare()
    {
        $bResult = parent::Prepare();
        $omeurl = Utils::GetConfigurationValue('ome_url', '');
        $omeuser = Utils::GetConfigurationValue('ome_user', '');
        $omepass = Utils::GetConfigurationValue('ome_pass', '');

        $this->omedata = $this->getDevices($omeurl, $omeuser, $omepass);

        return $bResult;
    }
 
    public function Fetch()
    {
        if(count($this->omedata)>0) {
            $name = array_keys($this->omedata)[0];
            $data = $this->omedata[$name];
            unset($this->omedata[$name]);
			$data['org_id'] = Utils::GetConfigurationValue('default_org_name', '');
			return $data;
        }
        return false;
    }

    private function getDevices($ome, $user, $pass) {
        $ch = curl_init($ome . 'Devices/$top=10000');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "${user}:${pass}");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $response = '<?xml version="1.0" encoding="UTF-8"?>' . curl_exec($ch);
        $response = curl_exec($ch);
 
        $xmlDoc=new SimpleXMLElement($response);
		$rows = array();
        foreach($xmlDoc->xpath("/ArrayOfDevice/Device") as $device)
        {  
			$row = array();
			# Skip any that don't have a service tag
			if(empty($device->ServiceTag)) {
				continue;
			}
			
			$st = (string)$device->ServiceTag;
			$rows[$st]['primary_key'] = $st;
			$rows[$st]['name'] = explode('.', (string)$device->Name)[0];
			$rows[$st]['serialnumber'] = $st;
			$rows[$st]['brand_id'] = 'Dell';
			if(!empty((string)$device->SystemModel)) {
                $rows[$st]['model_id'] = trim((string)$device->SystemModel);
			}
            $rows[$st] = array_merge($rows[$st], ($this->getDevicesFullDetails($ome, $user, $pass, $device->Id)));
        }
		return $rows;
    }

    private function getDevicesFullDetails($ome, $user, $pass, $id) {
        $ch = curl_init($ome . 'Devices/' . $id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "${user}:${pass}");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $response = '<?xml version="1.0" encoding="UTF-8"?>' . curl_exec($ch);
        $response = curl_exec($ch);
 
        $xmlDoc=new SimpleXMLElement($response);
		$row = array();
        foreach($xmlDoc->xpath("/DeviceInventoryResponse/DeviceInventoryResult") as $dir) {
            $row['ram'] = (string)$dir->Memory->TotalMemory;
            $row['cpu'] = count($dir->Processor->Processor);
            return $row;
        }
        return array();
    }


    protected function MustProcessBeforeSynchro()
    {
        return true;
    }

    protected function InitProcessBeforeSynchro()
    {
        $this->luServerPK = new LookupTable('SELECT Server', array('serialnumber'));
		$this->oModelLookup = new LookupTable('SELECT Model', array('brand_id_friendlyname', 'name'));
        $this->oBrandLookup = new LookupTable('SELECT Brand', array('name'));
    }

    protected function ProcessLineBeforeSynchro(&$aLineData, $iLineIndex)
    {
        // Process each line of the CSV
        $this->luServerPK->Lookup($aLineData, array('serialnumber'), 'primary_key', $iLineIndex);
		$this->oModelLookup->Lookup($aLineData, array('brand_id', 'model_id'), 'model_id', $iLineIndex);
        $this->oBrandLookup->Lookup($aLineData, array('brand_id'), 'brand_id', $iLineIndex);
    }
}
