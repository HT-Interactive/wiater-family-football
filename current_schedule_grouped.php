<?php // Main Pick Selection Page

//Turn off timer for testing
$timer_on = false;

// Performing SQL query for correct week
$query = "SELECT * FROM game WHERE season_year='$this_season_year' AND season_type='$this_season_type' AND week='$this_week' ORDER BY start_time ASC";
$result = pg_query($query) or die('Query failed: ' . pg_last_error());

// Get all of the users picks
$pick_result = mysqli_query($db, "SELECT * FROM picks WHERE user_id='$this_user_id' AND group_id='$this_group_id' AND season_year='$this_season_year' AND season_type='$this_season_type' AND week='$this_week'");
while($user_pick = mysqli_fetch_array($pick_result)) {
  $user_picks[] = $user_pick;
}

//echo "<div class=\"row\">\n<div class=\"col-md-12\">\n<table class=\"pickTable\">\n";
//echo "<div>\n<div>\n<table class=\"pickTable\">\n";
if(!$timer_on) {
  echo "<div class=\"alert alert-warning\" role=\"alert\">Game Start Timer Inactive</div>\n";
}
echo "<table class=\"pickTable\">\n";

while ($games = pg_fetch_array($result, null, PGSQL_ASSOC)) {
  //print_r($games);

  extract($games,EXTR_PREFIX_ALL,"this"); //load all game variables from db_array

  if(strtotime($this_start_time) < time()) {
    $has_started = true;
  } else {
    $has_started = false;
  }
  if($this_finished=="t") {
    $has_finished = true;
  }else {
    $has_finished = false;
  }

  if(!isset($last_day_of_week) || $this_day_of_week != $last_day_of_week) { //print date row
    echo "<tr>\n<td colspan=\"6\">";
    echo date('l, F j Y',strtotime($this_start_time));
    echo "</td>\n</tr>\n";
  }

  $last_day_of_week = $this_day_of_week; //set last day to this day for next iteration

  $this_start_time_EST = date("g:iA T", strtotime($this_start_time));

  if(isset($user_picks)) { //at least some picks in db
    foreach($user_picks as $pick) {
      if($pick['game_id'] == $this_gsis_id) { //user has already picked game so diplay winner
        $this_winner = $pick['winner'];
        if($pick['score']) {
          $this_score = $pick['score'];
        } else {
          $this_score = "";
        }
      //Selection formatting, remember to change the football.js pickTeam script to match
        if($this_away_team == $this_winner) {
          $away_style = "picked";
          $home_style = "notPicked";
        } elseif($this_home_team == $this_winner) {
          $home_style = "picked";
          $away_style = "notPicked";        
        }

      // pass this_winner to a script that checks the actual_winner for the jesus_id in the nfl_db
      // if it returns true, print correct or add to score,,,,
      // if false, print LOSER and don't ++score
        if($has_started) {
          if(getGameWinner($this_gsis_id) == $this_winner) {
            if($has_finished) {
              $result_str = "correct";
              //echo "<span style=\"color:green;\">Correct</span>"; 
              // add point to picks table for user and gsis_id
              addPoint($db,$pick['pick_id'],1,false);
              updatePoints($db,$this_user_id,$this_group_id,$this_season_year,$this_season_type,$this_week,false);
            } else {
              $result_str = "winning";
              //echo "<span style=\"color:green;\">Winning</span>";
            }
          } elseif(getGameWinner($this_gsis_id) == "tied") {
              $result_str = "tied";
              //echo "<span style=\"color:blue;\">Tied</span>";
          } else {
            if($this_finished == "t") {
              $result_str = "incorrect";
              //echo "<span style=\"color:red;\">Loser</span>";
              addPoint($db,$pick['pick_id'],0,false);
              updatePoints($db,$this_user_id,$this_group_id,$this_season_year,$this_season_type,$this_week,false);
            } else {
              $result_str = "losing";
              //echo "<span style=\"color:red;\">Losing</span>";
            }
          }
        } else {
            
            $result_str = "not started";
        }
        
        break;

      } else { 
        $home_style = "notPicked";
        $away_style = "notPicked";
        $result_str = "not picked";
        $this_score = "";
      }
    }
  } else { //user has made no picks so default to black
    $home_style = "notPicked";
    $away_style = "notPicked";
    $result_str = "not picked";
    $this_score = "";
  }
  if($has_started && $timer_on) { // alert the user that it is too late
    $onclick_away_str = "alert('It's too late to turn back now.')";
    $onclick_home_str = "alert('It's too late to turn back now.')";
  } else { // add pickTeam script to element    
    $onclick_away_str = "pickTeam(this,'".$this_user_id."','".$this_group_id."','".$this_gsis_id."','".$this_season_year."','".$this_season_type."','".$this_week."','".$this_away_team."')";
    $onclick_home_str = "pickTeam(this,'".$this_user_id."','".$this_group_id."','".$this_gsis_id."','".$this_season_year."','".$this_season_type."','".$this_week."','".$this_home_team."')";
  }
  if(!isset($result_str)) {
      $result_str = "not picked";
  }
  switch($result_str) {
    case "correct":
        $result_span = sprintf("<span class=\"glyphicon glyphicon-%s\" aria-hidden=\"true\" data-toggle=\"tooltip\" data-placement=\"left\" title=\"%s\" style=\"color:%s;\"></span>","ok","Correct Pick.","green");
        break;
    case "incorrect":
        $result_span = sprintf("<span class=\"glyphicon glyphicon-%s\" aria-hidden=\"true\" data-toggle=\"tooltip\" data-placement=\"left\" title=\"%s\" style=\"color:%s;\"></span>","remove", "Incorrect Pick.", "red");
        break;
    case "winning":
        $result_span = sprintf("<span class=\"glyphicon glyphicon-%s\" aria-hidden=\"true\" data-toggle=\"tooltip\" data-placement=\"left\" title=\"%s\" style=\"color:%s;\"></span>","arrow-up", "Your Pick is winning.", "green");
        break;
    case "losing":
        $result_span = sprintf("<span class=\"glyphicon glyphicon-%s\" aria-hidden=\"true\" data-toggle=\"tooltip\" data-placement=\"left\" title=\"%s\" style=\"color:%s;\"></span>","arrow-down","Your Pick is losing.", "red");
        break;
    case "tied":
        $result_span = sprintf("<span class=\"glyphicon glyphicon-%s\" aria-hidden=\"true\" data-toggle=\"tooltip\" data-placement=\"left\" title=\"%s\" style=\"color:%s;\"></span>","sort", "The teams are tied.", "blue");
        break;
    case "not started":
        $result_span = sprintf("<span class=\"glyphicon glyphicon-%s\" aria-hidden=\"true\" data-toggle=\"tooltip\" data-placement=\"left\" title=\"%s\" style=\"color:%s;\"></span>","time", "The game hasn't started yet.", "green");
        break;
    case "not picked":
        $result_span = sprintf("<span class=\"glyphicon glyphicon-%s\" aria-hidden=\"true\" data-toggle=\"tooltip\" data-placement=\"left\" title=\"%s\" style=\"color:%s;\"></span>","exclamation-sign", "You haven't picked a winner for this game yet.", "orange");
        break;
  }
  //if(!isset($away_style)) { $away_style = 'notPicked'; }
  //if(!isset($home_style)) { $home_style = 'notPicked'; }
  
//Game Row start
  echo "<tr>\n"; //start new row in table

//Away Team Cell
  echo "<td><div id=\"$this_gsis_id"."_away\" onclick=\"$onclick_away_str\" class=\"teamCell away $away_style\">$this_away_team</div></td>\n";
//Away Team Score
  echo "<td>";
  if($has_started) {
    echo "<span class=\"badge\">$this_away_score</span>";
  }
  echo "</td>\n";
  echo "<td>at</td>\n";
//Home Team Score
  echo "<td>";
  if($has_started) {
    echo "<span class=\"badge\">$this_home_score</span>";
  }
  echo "</td>\n";
//Home Team Cell
  echo "<td><div id=\"$this_gsis_id"."_home\" onclick=\"$onclick_home_str\" class=\"teamCell home $home_style\">$this_home_team</div></td>\n";
//Result Cell
  echo "<td class=\"glyphCell\">";
  if(isset($result_span)) {
    echo $result_span;
   }
   echo "</td>\n";
//Game Date
  //echo "<td>at</td>";
  //echo "<td class=\"dayCell\">$this_day_of_week</td>";
  //echo "<td class=\"timeCell\">";
  //echo date('F j Y',strtotime($this_start_time));
 // echo "</td>";
  echo "<td class=\"dayCell\">at $this_start_time_EST\n";
  echo "</td>\n";
  
//Pick Result
  echo "<td class=\"resultCell\" onclick=\"showTimer('".$this_gsis_id."-countdown')\">";
  if(!$has_started) echo "<small>Expires in <span id=\"".$this_gsis_id."-countdown\"><img src=\"timer.png\" onload=\"startTimer('".$this_gsis_id."-countdown','".date(DATE_RFC2822,strtotime($this_start_time))."')\"></span></small>\n";
  echo "</td>\n";
  
  echo "</tr>\n";
  //if(!$has_started) echo "<tr class=\"expiresIn\"><td colspan=\"7\"><small>Expires in <span id=\"".$this_gsis_id."-countdown\"><img src=\"timer.png\" onload=\"startTimer('".$this_gsis_id."-countdown','".date(DATE_RFC2822,strtotime($this_start_time))."')\"></span></small></td></tr>\n";

} //End While

echo "</table>\n";
echo "<form class=\"form-inline\">\n";
echo "<div class=\"input-group input-group-sm\">
  <span class=\"input-group-addon\" id=\"basic-addon1\">Score of $this_away_team at $this_home_team</span>
  <input type=\"text\" id=\"score\" class=\"form-control\" name=\"score\" value=\"$this_score\" size=\"1\" />";

if(isset($this_gsis_id)) {
  if($has_started && $timer_on) {
    $current_score = $this_home_score + $this_away_score;
    $score_diff = $this_score - $current_score;
    echo "<span class=\"input-group-addon\" id=\"basic-addon2\">You are off by $score_diff points.</span>";
  } else {
    echo "<span class=\"input-group-btn\">
    <button class=\"btn btn-primary\" type=\"button\" onclick=\"enterScore('$this_user_id','$this_group_id','$this_gsis_id','$this_season_year','$this_season_type','$this_week',score.value)\">Submit</button>
    </span>";
  }
  
  echo "</div></form>";

} else {
  echo "<p>No games this week.</p>";
}

//echo "</div>\n</div>";

// Free resultset
pg_free_result($result);

// Closing connection
pg_close($db_nfl);
?>