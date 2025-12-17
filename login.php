<!DOCTYPE html>
<html lang="en">
	 
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Login Page</title>
		<link rel="icon" href="images/favicon.ico" type="image/x-icon">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
		<style>
				body {
					margin: 0;
					padding: 0;
					min-height: 100vh;
					background-image: url('images/background-image.png');
					background-size: cover;
					background-position: center;
					display: flex;
					align-items: center;
					justify-content: center;
				}
		 
				.container {
					width: 100%;
					max-width: 400px;
					/* Set a maximum width for the container if needed */
					background-color: rgba(255, 255, 255, 0.8);
					/* Add a semi-transparent background color for better text visibility */
					padding: 20px;
					border-radius: 10px;
				}
		 
				.card-title,
				.form-group label {
					color: #000 !important;
				}
		 
				.typing-effect {
					color: #1A237E;
					/* Change the color to #ccddee */
					font-family: 'American Typewriter', American Typewriter, serif;
					/* Use Arial Black font */
				}
		</style>
	</head>
	 
	<body>
		<div class="container">
			<div class="card mx-auto">
				<div class="card-body">
					<div class="container text-center mb-3">
						<img id="logo" src="images/logo.png" alt="Company Logo" class="img-fluid rounded">
					</div>
					<div class="text-center">
						<h2 class="card-title">
							<span class="typing-effect"></span>
						</h2>
					</div>
					<form action="./configs/authenticate.php" method="post">
						<div class="form-group">
							<label for="username">Username:</label>
							<input type="text" class="form-control" id="username" name="username" required>
						</div>
						<div class="form-group">
							<label for="password">Password:</label>
							<input type="password" class="form-control" id="password" name="password" required>
						</div>
						<button type="submit" class="btn btn-primary btn-block">Login</button>
					</form>
				</div>
			</div>
		</div>
		 
		<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
		<script>
				document.addEventListener('DOMContentLoaded', function () {
					var options = {
						strings: ['HERS Portal'],
						typeSpeed: 80,
						backSpeed: 60,
						showCursor: false,
						loop: false
					};
		 
					var typed = new Typed('.typing-effect', options);
		 
					// Additional code to restart typing every 10 seconds
					//setInterval(function () {
					//	typed.reset();
					//}, 10000);
				});
		</script>
	</body>
 
</html>