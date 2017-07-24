<?php

header('Content-Type: application/json');
ob_start();
session_start();
$csv = array_map('str_getcsv', file('https://github.com/ra7veer/test/blob/master/data/dialog.csv'));
$json = file_get_contents("php://input");
$data = json_decode($json, true);
$output = [
            'speech' => '',
            'displayText' => ''
          ];


if(!empty($data))
{
  $typeValues = [];
  $apiSessionID = $data["sessionId"];
  $questionAsk = $data["result"]["resolvedQuery"];
  $Intent = $data["result"]["metadata"]["intentName"];

  if($Intent != null){
    if($Intent == "USE PREVIOUS"){
      $_SESSION["Intent"] = $_SESSION["Intent"];
    }
    else {
      $_SESSION["Intent"] = $Intent;
    }
  }


  //file_put_contents("JSON.text", $_SESSION["Intent"], true); //ACT AS A CONSOLE LOG

  foreach($data["result"]["parameters"] as $key => $value){
    $typeValues[$key] = $value;
  }


  define("DB_HOST","localhost");
  define("DB_USER","root");
  define("DB_PASS","");
  define("DB_NAME","dialog");
  $mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
  $sqlSearchQuery= "SELECT * FROM dialog ORDER BY RAND()";
  $result = $mysqli->query($sqlSearchQuery);



  foreach($csv as $arrayOfColumns)
  {
    if($output["speech"])
    {
      break;
    }

    while($row = $result->fetch_assoc())
    {
      foreach($typeValues as $type => $value)
      {
        if($value != null){
          $_SESSION["entityType"] = $type;
          $_SESSION["entityValue"] = $value;
        }

        if(empty($_SESSION["IntentFollowUp"])){
          if($row["eType"] == $_SESSION["entityType"] && $row["eVal"] == $_SESSION["entityValue"] && $row["intent"] == $_SESSION["Intent"])
          {
            $responseOut = $row["reply"];
          }
        }
        elseif(!empty($_SESSION["IntentFollowUp"]))
        {
          if($row["eType"] == $_SESSION["entityType"] && $row["eVal"] == $_SESSION["entityValue"] && $row["intent"] == $_SESSION["IntentFollowUp"])
          {
            $responseOut = $row["reply"];
          }
        }
      }
    }
  }

  if(empty($responseOut))
  {
    $getEntityType = [];
    $getEntityType = ['leave', 'allowance'];
    $getQUestionFromApi = $questionAsk;
    foreach ($getEntityType as $key) {
      if(preg_match("/$key/", $getQUestionFromApi)){
        $displayResponse = "What $key type would you like to know?";
        $_SESSION["IntentFollowUp"] = $Intent;
        break;
      }
      else {
        $displayResponse = "Sorry. I'm not ready for it yet. I'm a Beta.";
      }
    }
  }
  else
  {
    if(empty($_SESSION["IntentFollowUp"]))
    {
      $displayResponse = $responseOut;
    }
    elseif(!empty($_SESSION["IntentFollowUp"]))
    {
      $displayResponse = $responseOut;
      $_SESSION["Intent"] = $_SESSION["IntentFollowUp"];
      $_SESSION["IntentFollowUp"] = "";
    }

  }


  $output["speech"] = $displayResponse;
  $output["displayText"] = $displayResponse;

  ob_end_clean();
  echo json_encode($output);

}

//session_destroy();

?>
