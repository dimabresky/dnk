<?php

// replace NavNum parameter for PAGEN_, when more then 1 ajax pager on page
if (isset($_REQUEST['PAGEN_2'])) {
   $arResult['NavNum'] = 1; 
}

