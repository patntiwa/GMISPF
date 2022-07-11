
<!DOCTYPE html>
<html lang="fr">
<head>

            <title>GMISPF :: Connexion</title>
		

			<meta name="viewport" content="width=device-width, initial-scale=1">
		
			<meta charset="utf-8">
			<link rel="stylesheet" type="text/css" href="./css/all.css">
		
			<link rel="stylesheet" type="text/css" href="./css/StylePage.css">
		
			<link rel="shortcut icon" type="images/x-icon" href="./images/beac-logo.ico">

			<style>
           .error {color: #FF0000;}
           </style>

			
</head>	

<body style="background: url(./images/IMG_20190903_153008.jpg) no-repeat;background-size:cover; height:150%; width: 100%; display: block;">
<div>				
<div class="corps">              
			<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">
					<h2 align="center">Connectez-Vous!</h2><br>
<?php   

       // define variables and set to empty values
		$UsernameErr = $mdpErr ="";
		$Username = $mdp = '';

		if (isset($_POST["connexion"])){

				    $Username=$_POST['Username'];
				    $mdp=$_POST['mdp'];
				
				 	include './Fonction/connexion.php';
				
				    $sql="SELECT * FROM utilisateur";

					$res=mysqli_query($base,$sql);
					$goodConnect = 0;

					while ($line = mysqli_fetch_assoc($res)) {
						
						if($Username != $line['Username']){
							$UsernameErr = "Erreur Nom d'utilisateur";
						}

						if($mdp != $line['mdp']){
							$mdpErr = "Erreur Mot de passe";
						}
						
						if ($Username == $line['Username'] && $mdp == $line['mdp']) {
							$goodConnect = 1;
							$Fonction = $line['Fonction'];
							session_start();
				      		$_SESSION['Username'] = $Username;
						}
						
					}

                    if ($goodConnect) {
                    	if ($Fonction == 'Prestataire') {
				      	?>
						    <script type="text/javascript">window.alert('Connexion reussie');</script>
				      		<script type="text/javascript"> window.location.href="accueil.php";</script>
				      	<?php
						}
						else{
						?>
						    <script type="text/javascript">window.alert('Connexion reussie');</script>
				      		<script type="text/javascript">window.location.href="accueil2.php";</script>
				      	<?php
						}
                    }

					else{
						echo "<script>window.alert('Erreur mot de passe nou nom utilisateur');</script>";
				      header('location:index.php');
					}

					mysqli_close($base);
		}
?>
									
					<table class="tableau">
								
								    <tr><td><label for="nom"><i class="fas fa-user"></i>Nom d'utilisateur:</label> <span class="error">* <?php echo $UsernameErr;?></span></td></tr>
							        
									<tr><td><input type="text" id="nom" name="Username" placeholder="Entrez votre Nom ou Votre Email " value="<?php echo $Username?>" required/></td></tr>

							       
								    <tr><td><label for="password"><i class="fa fa-key"></i>Mot de Passe :</label><span class="error">*<?php echo $mdpErr;?></span></td></tr>
							    
								    <tr><td><input type="password" id="password" name="mdp" placeholder="Entrez votre mot de passe"></td></tr>

								    <tr><td><input type="submit" value="connexion" name="connexion"></td></tr>
								   
								    <tr><td><p align='center' ><a href=inscription.php>Inscrivez-Vous ici !</a></p></td></tr>
							  
					 </table>						 
	     </form>			      
</div>			
</div>
</body>
</html>        