<!--Ansvarig: Hannes Birgersson-->
<?php
	
	function letterSearch($char, $Category, $stmt) {
		/*Ritar ut alla ingredienser som börjar på bokstaven $char, under kategorin $Category
		 *$stmt är en SQL query*/
		$a = $char;
		
		if ($a == "Ä" || $a == "Ä" || $a == "Ö"){
			$a = mb_strtolower($a, 'UTF-8');
		}
		
		$a= $a."%";
	
		$stmt->bindParam(':Category', $Category);
		$stmt->bindParam(':Letter', $a);
		$stmt->execute();
		$result = $stmt->fetchAll();
	
		echo "<div class='row'><ul>";
		echo "<h2>".$char."</h2>";
		$i = 0;
	
		foreach($result as $row) {
			$row['Ingredient'] = mb_ucfirst($row['Ingredient']);
			echo '<li><a href="javaScript:void(0);" class="Item">' . $row['Ingredient'] . '</a></li>';
			$i++;
			if($i %10 == 0) {
				echo "</div></ul></div>";
				echo "<div class='row'><ul>";
			}
		}
		echo "</div></ul></div>";
	}
	
	if (!function_exists('mb_ucfirst')) {
		function mb_ucfirst($str, $encoding = "UTF-8", $lower_str_end = false) {
			$first_letter = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding);
			$str_end = "";
			if ($lower_str_end) {
		$str_end = mb_strtolower(mb_substr($str, 1, mb_strlen($str, $encoding), $encoding), $encoding);
			}
			else {
		$str_end = mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
			}
			$str = $first_letter . $str_end;
			return $str;
		}
	}	
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
	</head>
	<body>
		<?php
			$q = $_GET['q'];
			$s = $_GET['s'];
			
			$db = new PDO('sqlite:test2.db');
			
			if ($s!='0'){
				$stmt = $db->prepare("SELECT Ingredient FROM Ingredients 
										WHERE Category LIKE :Category
										AND Ingredient LIKE :Letter 
										ORDER BY Ingredient");
				/*Kör en bokstavssökning för varje bokstav i $s*/
				for ($x = 0; $x < (strlen(utf8_decode($s))); $x++) {
					letterSearch(mb_substr($s, $x, 1, 'UTF-8'), $q, $stmt);
				}
			} else if ($s=='0'){
				/*$s som 0 resulterar i en lista med alla ingredienser ur en kategori*/
				$stmt = $db->prepare("SELECT Ingredient FROM Ingredients 
										WHERE Category LIKE :Category 
										ORDER BY Ingredient");
				$stmt->bindParam(':Category', $q);
				$stmt->execute();
			
				$result = $stmt->fetchAll();
				echo "<div class='row'><ul>";
				$i = 0;
				foreach($result as $row) {
					$row['Ingredient'] = mb_ucfirst($row['Ingredient']);
					echo '<li><a href="javaScript:void(0);" class="Item">' . $row['Ingredient'] . '</a></li>';
					$i++;
					if($i %10 == 0) {
						echo "</div></ul></div>";
						echo "<div class='row'><ul>";
					}
				}
				echo "</div></ul></div>";
			}
		?>
	</body>
</html>