--TEST--
EDI_EDIFACT_MappingProvider test 05
--FILE--
<?php

require_once dirname(__FILE__) . '/../tests.inc.php';
require_once 'EDI/EDIFACT/MappingProvider.php';

try {
    $node = EDI_EDIFACT_MappingProvider::find('CONTRL');
    echo $node->asXML();
} catch (Exception $exc) {
    echo $exc->getMessage();
    exit(1);
}

?>
--EXPECT--
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<message>
    <defaults>
        <data_element id="0065" value="CONTRL"/>
        <data_element id="0052" value="4"/>
        <data_element id="0054" value="1"/>
        <data_element id="0051" value="UN"/>
    </defaults>
    <segment id="UNH" maxrepeat="1" required="true"/>
    <segment id="UCI" maxrepeat="1" required="true"/>
    <group id="SG1" maxrepeat="999999">
        <segment id="UCM" maxrepeat="1" required="true"/>
        <group id="SG2" maxrepeat="999">
            <segment id="UCS" maxrepeat="1" required="true"/>
            <segment id="UCD" maxrepeat="99"/>
        </group>
    </group>
    <group id="SG3" maxrepeat="999999">
        <segment id="UCF" maxrepeat="1" required="true"/>
        <group id="SG4" maxrepeat="999999">
            <segment id="UCM" maxrepeat="1" required="true"/>
            <group id="SG5" maxrepeat="999">
                <segment id="UCS" maxrepeat="1" required="true"/>
                <segment id="UCD" maxrepeat="99"/>
            </group>
        </group>
    </group>
    <segment id="UNT" maxrepeat="1" required="true"/>
</message>
