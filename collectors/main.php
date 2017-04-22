<?php
require_once(APPROOT.'collectors/OMEWarrantyCollector.inc.php');

// Register the collectors (one collector class per data synchro task to run)
// and tell the orchestrator in which order to run them

$iRank = 1;
Orchestrator::AddCollector($iRank++, 'OMEWarrantyCollector');

