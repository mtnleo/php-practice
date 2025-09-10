<?php
	session_start();
		if (!isset($_SESSION['secret_number'])) {
			$_SESSION['secret_number'] = rand(1, 100);
		}

		$guessed = false;
		$message = '';

		if (isset($_GET['number'])) {
			// Convierte la entrada a un número entero para comparar de forma segura
			$user_guess = intval($_GET['number']);
			$secret_number = $_SESSION['secret_number'];
        	

			if ($user_guess > $secret_number) {
				$message = "<h2 style='color: red'>" . 'The number is smaller than ' . $user_guess . ' ' . ' </h2>';

			} elseif ($user_guess < $secret_number) {
				
				$message = "<h2 style='color: red'>" . 'The number is bigger than ' . $user_guess . ' ' . ' </h2>';


			} else {
				$message = "<h2 style='color: green'>" . 'You got it! The number is ' . $user_guess . ' ' . ' </h2>';
				$guessed = true;

				unset($_SESSION['secret_number']);
			}
		}
		
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document</title>
	<link rel="stylesheet" href="/style.css">
	<script src="index.js"></script>
</head>
<body>
	<div class="greeting">
		<h1>Welcome to<span> <?php echo "Guess the Number" ?> </span></h1>
		<h2>The number is <? $guessed ? $myNumber : '?' ?> </h2>

	</div>

	<div class="form">

		<form method='get' action="index.php" role="form" target="_blank">
			<label for="number">Guess the number</label>
			<input name='number' id='number' type="text">
		</form>
	</div>

	<div class="answer-container">
		<div class="answer">
			<?php
				echo $message;
			?>
	
		</div>

	</div>
</body>
</html>