<?php
	$id = $_GET['a'];
	$b = $_GET['b'];
	$ip = $_SERVER['REMOTE_ADDR'];

	$db = new PDO('sqlite:test2.db');

	$stmt = $db->prepare("SELECT COUNT(*) FROM RecipesVotes WHERE RecipeID=:id AND IP=:ip");
	$stmt->bindParam(':id',$id);
	$stmt->bindParam(':ip',$ip);
	$stmt->execute();
	$votes = $stmt->fetchAll()[0][0];
	
	// Om samma ip röstat innan:
	if($votes == 0) {
		$stmt = $db->prepare("UPDATE Recipes 
							SET Rating=Rating+:Betyg 
							WHERE rowid=:ID");
		if ($b > 0) {
			$b = 1;
		} else {
			$b = -1;
		}
		$stmt->bindParam(':Betyg', $b);
		$stmt->bindParam(':ID', $id);
		$stmt->execute();
		
		$stmt = $db->prepare("INSERT INTO RecipesVotes VALUES (:id,:ip)");
		$stmt->bindParam(':id', $id);
		$stmt->bindParam(':ip', $ip);
		$stmt->execute();
	}
	header("Location: recipe.php?id=$id");
	die(); 
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Ändrar betyg</title>
    </head>
    <body>
       
    </body>
</html>
