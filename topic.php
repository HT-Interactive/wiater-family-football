<?php
//create_cat.php
// Load Databases and Common functions
require("mysql.php");
include 'common.php';
include("get_winner.php"); //Site Functions
 
//try to guess the current week, function in get_winners
guessCurrentWeek();

include 'header.php';

if(isset($this_user_name)) { $_SESSION['signed_in'] = true; }
$con = $db;

$sql = "SELECT
			topic_id,
			topic_subject
		FROM
			topics
		WHERE
			topics.topic_id = '" . mysql_real_escape_string($_GET['id']) ."'";
			
$result = mysqli_query($con, $sql);

if(!$result)
{
	echo 'The topic could not be displayed, please try again later.';
}
else
{
	if(mysqli_num_rows($result) == 0)
	{
		echo 'This topic doesn&prime;t exist.';
	}
	else
	{
		while($row = mysqli_fetch_assoc($result))
		{
			//display post data
			echo '<table class="topic" border="1">
					<tr>
						<th colspan="2">' . $row['topic_subject'] . '</th>
					</tr>';
		
			//fetch the posts from the database
			$posts_sql = "SELECT
						posts.post_topic,
						posts.post_content,
						posts.post_date,
						posts.post_by,
						users.user_id,
						users.user_name
					FROM
						posts
					LEFT JOIN
						users
					ON
						posts.post_by = users.user_id
					WHERE
						posts.post_topic = '" . mysql_real_escape_string($_GET['id']) . "'";
						
			$posts_result = mysqli_query($con, $posts_sql);
			
			if(!$posts_result)
			{
				echo '<tr><td>The posts could not be displayed, please try again later.</tr></td></table>';
			}
			else
			{
			
				while($posts_row = mysqli_fetch_assoc($posts_result))
				{
					echo '<tr class="topic-post">
							<td class="user-post">' . $posts_row['user_name'] . '<br/>' . date('d-m-Y H:i', strtotime($posts_row['post_date'])) . '</td>
							<td class="post-content">' . htmlentities(stripslashes($posts_row['post_content'])) . '</td>
						  </tr>';
				}
			}
			
			if(!$_SESSION['signed_in'])
			{
				echo '<tr><td colspan=2>You must be <a href="signin.php">signed in</a> to reply. You can also <a href="signup.php">sign up</a> for an account.';
			}
			else
			{
				//show reply box
				echo '<tr><td colspan="2"><h2>Reply:</h2><br />
					<form method="post" action="reply.php?id=' . $row['topic_id'] . '">
						<textarea name="reply-content"></textarea><br /><br />
						<input type="submit" value="Submit reply" />
					</form></td></tr>';
			}
			
			//finish the table
			echo '</table>';
		}
	}
}

include 'footer.php';
?>