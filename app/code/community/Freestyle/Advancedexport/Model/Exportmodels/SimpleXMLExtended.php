<?php
/************************************************************************
  © 2013,2014, 2015 Freestyle Solutions.   All rights reserved.
  FREESTYLE SOLUTIONS, DYDACOMP, FREESTYLE COMMERCE, and all related logos 
  and designs are trademarks of Freestyle Solutions (formerly known as Dydacomp)
  or its affiliates.
  All other product and company names mentioned herein are used for
  identification purposes only, and may be trademarks of
  their respective companies.
************************************************************************/
class Freestyle_Advancedexport_Model_Exportmodels_SimpleXMLExtended 
extends SimpleXMLElement
{
   public function addCData($cdataText)
   {
       $node= dom_import_simplexml($this);
       $owner = $node->ownerDocument;
       $node->appendChild($owner->createCDATASection($cdataText));
   }
}
