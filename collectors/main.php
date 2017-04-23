<?php
require_once(APPROOT.'collectors/OMEModelCollector.inc.php');
require_once(APPROOT.'collectors/OMEServerCollector.inc.php');
require_once(APPROOT.'collectors/OMEWarrantyCollector.inc.php');
require_once(APPROOT.'collectors/OMENICCollector.inc.php');

// Register the collectors (one collector class per data synchro task to run)
// and tell the orchestrator in which order to run them

$iRank = 1;
Orchestrator::AddCollector($iRank++, 'OMEModelCollector');
Orchestrator::AddCollector($iRank++, 'OMEServerCollector');
Orchestrator::AddCollector($iRank++, 'OMEWarrantyCollector');
Orchestrator::AddCollector($iRank++, 'OMENICCollector');
