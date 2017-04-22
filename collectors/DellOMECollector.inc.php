<?php

class DellOMECollector extends Collector
{
	protected $nomedata;
    protected $omeorder;
    protected $luServerPK;
 
	public function Prepare()
	{
        $bResult = parent::Prepare();
        $omeurl = Utils::GetConfigurationValue('ome_url', '');
		$omeuser = Utils::GetConfigurationValue('ome_user', '');
		$omepass = Utils::GetConfigurationValue('ome_pass', '');
        $omereport = Utils::GetConfigurationValue('ome_report', 'Reports/26');
        $this->omeorder = explode(',',Utils::GetConfigurationValue('ome_wtype_order', ''));

        $omedata = $this->getWarranties($omeurl, $omeuser, $omepass, $omereport);
        $this->nomedata = $this->normaliseWarranties($omedata);

		return $bResult;
	}
 
	public function Fetch()
	{
        if(sizeof($this->nomedata)>0) {
            $serial = array_keys($this->nomedata)[0];
            $wdates = $this->nomedata[$serial];
            unset($this->nomedata[$serial]);
            $eow='';
            foreach($this->omeorder as $tow) {
                if(isset($wdates[$tow]))
                {
                    $eow = $wdates[$tow];
                    break;
                }
            }
            return array('primary_key' => '', 'serialnumber' => $serial, 'end_of_warranty' => $eow);
        }
		return false;
	}

    private function getWarranties($ome, $user, $pass, $report) {
        $ch = curl_init($ome . $report);
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
 
        foreach($xmlDoc->xpath("/GetReportResponse/GetReportResult/ReportDataRows/ArrayOfCellData") as $celldata)
        {  
            foreach($celldata as $columns)
            {  
                $cols = (array)$columns;
                $row[$cols['ColumnNumber']] = $cols['Data'];
            }
            $rows[] = $row;
        }
        return $rows;
    }

    private function normaliseWarranties($omedata) {
        $SERIAL_POS = 6;
        $EOW_POS = 10;
        $WTYPE_POS = 7;

        $ds = "";
        foreach($omedata as $d) {
            $eow_date = date_create($d[$EOW_POS]);
            if($eow_date >= date_create()) {
                if(!isset($ds[$d[$SERIAL_POS]]) 
                  || !isset($ds[$d[$SERIAL_POS]][$d[$WTYPE_POS]])
                  || date_create($ds[$d[$SERIAL_POS]][$d[$WTYPE_POS]]) < $eow_date ) {
                    $ds[$d[$SERIAL_POS]][$d[$WTYPE_POS]] = date_format($eow_date,"Y-m-d H:i:s");
                }
            }
        }
        return($ds);
    }

	protected function MustProcessBeforeSynchro()
	{
		return true;
	}

	protected function InitProcessBeforeSynchro()
	{
		$this->luServerPK = new LookupTable('SELECT Server', array('serialnumber'));
	}

	protected function ProcessLineBeforeSynchro(&$aLineData, $iLineIndex)
	{
		// Process each line of the CSV
		$this->luServerPK->Lookup($aLineData, array('serialnumber'), 'primary_key', $iLineIndex);
	}
}
